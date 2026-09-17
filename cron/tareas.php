<?php
/**
 * Despachador de tareas programadas.
 *
 * Una sola entrada de cron para todo el sistema, porque el plan de
 * alojamiento puede no permitir mas. Este fichero mira el reloj y decide que
 * toca en cada ejecucion.
 *
 *   Cada ejecucion : ingesta, procesado, publicacion automatica, generador
 *                    y envio del boletin
 *   A las 05:00 UTC: mantenimiento, una vez al dia
 *
 * Uso normal (desde cron, cada cinco minutos):
 *   php cron/tareas.php
 *
 * Cada cinco y no cada hora porque el catalogo de fuentes es grande: una
 * pasada lee un lote y la siguiente sigue por donde iba, asi que la frecuencia
 * del cron es lo que decide cuantas veces al dia se recorre el catalogo
 * entero. Con el cron horario, doscientas fuentes tardaban medio dia en dar
 * una vuelta. Todo lo que hay aqui esta escrito para aguantar esa cadencia:
 * cerrojo para que dos pasadas no se pisen, tareas que salen enseguida cuando
 * no hay nada que hacer y avisos con un minimo de tiempo entre uno y otro.
 *
 * Forzar una tarea concreta (desde SSH, para probar):
 *   php cron/tareas.php ingesta
 *
 * Cada tarea trabaja por lotes con puntero, asi que agotar el presupuesto de
 * tiempo no pierde trabajo: lo reparte entre varias pasadas.
 */

// Este fichero no debe ejecutarse nunca desde el navegador. El .htaccess ya
// lo impide, pero un .htaccess ignorado no puede ser el unico cerrojo.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Solo por linea de comandos.\n");
}

require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/estado.php';
require_once dirname(__DIR__) . '/lib/migrar.php';

// Lo primero de todo, antes incluso de leer la configuracion: dejar constancia
// de que el cron esta vivo. La portada mira esta marca para decidir si tiene
// que mantener el sitio ella sola, y para eso le vale saber que el cron llego
// a arrancar, aunque despues fallara.
estado_latir(dirname(__DIR__));

// Y acto seguido, el cerrojo. Si la pasada anterior sigue viva, esta se va por
// donde ha venido sin escribir nada: no es un error, es que la anterior
// todavia esta trabajando y el puntero es suyo.
require_once dirname(__DIR__) . '/lib/cerrojo.php';

$cerrojo = cerrojo_coger(dirname(__DIR__));

if ($cerrojo === null) {
    printf("[%s] la pasada anterior sigue corriendo; esta se salta
", gmdate('Y-m-d H:i:s'));
    exit(0);
}

$config = config();

date_default_timezone_set('UTC');
error_reporting(E_ALL);
ini_set('display_errors', $config['depuracion'] ? '1' : '0');

$arranque    = microtime(true);

// El registro de la pasada, que despues es el cuerpo del aviso por correo.
$GLOBALS['tareas_lineas']  = [];
$GLOBALS['tareas_errores'] = 0;

// El presupuesto es POR TAREA, no para la ejecucion entera. Compartirlo era
// un error: la ingesta se lo gastaba casi todo y el procesado arrancaba ya
// sin tiempo, asi que la cola de items nuevos no bajaba nunca. Como esto solo
// corre por linea de comandos, donde PHP no impone limite de ejecucion, el
// unico limite que importa es el que nos ponemos aqui.
$presupuesto = (float) ($config['presupuesto_cron'] ?? 25);

// Y un techo para la ejecucion completa, para no dejar corriendo media hora
// un cron que se haya atascado.
$techo   = (float) ($config['presupuesto_cron_total'] ?? $presupuesto * 3);
$forzada = $argv[1] ?? '';

/**
 * Escribe una linea con marca de tiempo. La salida la recoge el cron y la
 * manda al fichero de registro indicado en la tarea de hPanel.
 */
function tareas_log(string $mensaje): void
{
    printf("[%s] %s\n", gmdate('Y-m-d H:i:s'), $mensaje);

    // Y se guarda en memoria: el mismo registro es el cuerpo del aviso que
    // sale por correo al terminar. Dos formatos del mismo parte habrian
    // acabado contando cosas distintas.
    $GLOBALS['tareas_lineas'][] = $mensaje;
}

/**
 * Manda el parte de la pasada por correo.
 *
 * Tres modos, en el ajuste cron_aviso:
 *
 *   siempre  en cuanto se cumpla el minimo de tiempo entre avisos.
 *   cambios  ademas, solo si ha entrado algo, se ha archivado algo o ha
 *            fallado alguna tarea. Es el valor de fabrica.
 *   no       nunca.
 *
 * Y, por encima de los tres, un suelo de tiempo: cron_aviso_minutos. Esto se
 * escribio cuando el cron corria cada hora y "un aviso por pasada" queria
 * decir veinticuatro correos al dia. Con el cron cada cinco minutos, lo mismo
 * son doscientos ochenta y ocho: no es un aviso, es una averia. Asi que las
 * pasadas que caen dentro del plazo no callan lo que han hecho, lo suman, y el
 * primer aviso que sale lo cuenta todo junto.
 *
 * Sale por el mismo buzon que el boletin, asi que comparte su limite por hora.
 */
function tareas_avisar(array $resumenes): void
{
    $modo = (string) ajuste('cron_aviso', 'cambios');

    if ($modo === 'no') {
        return;
    }

    // Lo de esta pasada se suma a lo que quedo pendiente de contar.
    $nuevos     = (int) ajuste('cron_aviso_nuevos', '0')
                + (int) ($resumenes['publicar']['nuevos'] ?? 0);
    $archivados = (int) ajuste('cron_aviso_archivados', '0')
                + (int) ($resumenes['publicar']['archivados'] ?? 0);
    $errores    = (int) ajuste('cron_aviso_errores', '0')
                + (int) ($GLOBALS['tareas_errores'] ?? 0);

    $minutos = max(0, (int) ajuste('cron_aviso_minutos', '60'));
    $ultimo  = (int) ajuste('cron_aviso_ultimo', '0');

    if (time() - $ultimo < $minutos * 60) {
        // Todavia no toca. Se guarda la cuenta para el proximo y se sale sin
        // mandar nada: lo que ha pasado no se pierde, solo espera.
        ajuste_guardar('cron_aviso_nuevos', (string) $nuevos);
        ajuste_guardar('cron_aviso_archivados', (string) $archivados);
        ajuste_guardar('cron_aviso_errores', (string) $errores);

        return;
    }

    if ($modo === 'cambios' && $nuevos === 0 && $archivados === 0 && $errores === 0) {
        return;
    }

    // La cuenta se pone a cero aqui y no despues de mandar: si el correo
    // falla, se pierde un parte; si no se pusiera a cero, cada pasada
    // reintentaria con la cuenta creciendo y el buzon acabaria bloqueado.
    ajuste_guardar('cron_aviso_ultimo', (string) time());
    ajuste_guardar('cron_aviso_nuevos', '0');
    ajuste_guardar('cron_aviso_archivados', '0');
    ajuste_guardar('cron_aviso_errores', '0');

    require_once dirname(__DIR__) . '/lib/correo.php';
    require_once dirname(__DIR__) . '/lib/smtp.php';
    require_once dirname(__DIR__) . '/lib/envio.php';

    $conf = correo_conf();

    if (!correo_configurado($conf) || $conf['proveedor'] !== 'propio') {
        // Sin buzon no hay aviso, y no es un error: el sitio funciona igual.
        return;
    }

    $destino = trim((string) ajuste('cron_aviso_correo', ''));
    $destino = $destino !== '' ? $destino : (string) $conf['usuario'];

    if (!correo_valido($destino)) {
        return;
    }

    $datos = ['nuevos' => $nuevos, 'archivados' => $archivados, 'errores' => $errores];
    $base  = rtrim((string) config_opcional('sitio.url', ''), '/');

    $envio = smtp_enviar($conf, [
        'para'   => $destino,
        'asunto' => aviso_asunto($datos),
        'texto'  => aviso_cuerpo($datos, $GLOBALS['tareas_lineas'] ?? [], $base),
        'cabeceras' => [
            'Auto-Submitted: auto-generated',
            'Precedence: bulk',
        ],
    ]);

    if (!$envio['ok']) {
        error_log('Bit & Breakfast, aviso del cron: ' . $envio['mensaje']);
    }
}

/**
 * Decide si una tarea toca en esta ejecucion.
 *
 * Casi todas tocan siempre: trabajan por lotes y salen enseguida cuando no
 * hay nada que hacer, asi que preguntar cada cinco minutos es barato. La
 * excepcion es el mantenimiento, que es cara y va una vez al dia.
 */
function tareas_toca(string $tarea, string $forzada): bool
{
    if ($forzada !== '') {
        return $forzada === $tarea;
    }

    return match ($tarea) {
        'ingesta', 'procesar', 'auto' => true,
        // El envio va por tandas cortas por el limite del buzon, asi que le
        // toca en cada pasada: sale enseguida cuando no hay nada que mandar.
        'enviar'              => true,
        'mantenimiento'       => tareas_toca_mantenimiento(),
        // Mira si hay algo que publicar y sale enseguida si no lo hay: la
        // comprobacion es una firma, no una regeneracion.
        'publicar'            => true,
        default               => false,
    };
}

/**
 * El mantenimiento: a partir de las 05:00 UTC y una sola vez al dia.
 *
 * Con el cron horario bastaba con mirar la hora. Con el cron cada cinco
 * minutos, "son las cinco" es verdad doce veces seguidas, asi que hace falta
 * recordar el dia en que se hizo. De paso deja de importar que la pasada de
 * las 05:00 en punto se pierda: vale cualquiera de las de esa mañana.
 */
function tareas_toca_mantenimiento(): bool
{
    $hoy = gmdate('Y-m-d');

    if ((int) gmdate('G') < 5) {
        return false;
    }

    return (string) ajuste('mantenimiento_dia', '') !== $hoy;
}

$tareas = [
    'ingesta'       => dirname(__DIR__) . '/cron/ingesta.php',
    'procesar'      => dirname(__DIR__) . '/cron/procesar.php',
    'auto'          => dirname(__DIR__) . '/cron/auto.php',
    'publicar'      => dirname(__DIR__) . '/cron/publicar.php',
    'enviar'        => dirname(__DIR__) . '/cron/enviar.php',
    'mantenimiento' => dirname(__DIR__) . '/cron/mantenimiento.php',
];

$resumenes = [];

tareas_log('--- arranca el despachador' . ($forzada !== '' ? " (forzado: $forzada)" : ''));

// Antes que nada, el esquema. Un despliegue puede traer una tabla nueva, y el
// codigo que la usa ya esta ahi: si no se aplica ahora, la primera tarea que
// la toque revienta.
//
// Con su try, y no por gusto: esto corre antes que las tareas, asi que
// cualquier cosa que lance aqui se lleva la pasada entera -sin ingesta, sin
// publicar, sin nada- y desde fuera parece que el cron ha dejado de correr.
// Una migracion rota tiene que poder convivir con un sitio que sigue vivo.
try {
    $migracion = migrar_pendientes(dirname(__DIR__));
} catch (Throwable $e) {
    $migracion = ['aplicadas' => [], 'error' => 'excepcion: ' . $e->getMessage()];
    $GLOBALS['tareas_errores']++;
}

foreach ($migracion['aplicadas'] as $aplicada) {
    tareas_log('migracion aplicada: ' . $aplicada);
}

if ($migracion['error'] !== '') {
    tareas_log('ERROR de migracion, ' . $migracion['error']);
}

foreach ($tareas as $nombre => $fichero) {
    if (!tareas_toca($nombre, $forzada)) {
        continue;
    }

    // Las tareas de fases posteriores todavia no existen. Que falte un
    // fichero no es un error: es que aun no hemos llegado a esa fase.
    if (!is_readable($fichero)) {
        continue;
    }

    // El generador y el envio no entran en el reparto: son lo unico que el
    // lector llega a ver, y una pasada que rastrea, agrupa y publica pero no
    // genera la web ni manda el correo no ha servido de nada. Los dos salen
    // enseguida cuando no hay nada que hacer -uno compara una firma y el otro
    // busca una edicion sin enviar-, asi que saltarse el techo les cuesta
    // milisegundos en el caso normal.
    $imprescindible = in_array($nombre, ['publicar', 'enviar'], true);

    if (!$imprescindible && microtime(true) - $arranque >= $techo) {
        tareas_log("sin presupuesto para $nombre, queda para la proxima pasada");
        continue;
    }

    try {
        require_once $fichero;

        $funcion = match ($nombre) {
            'ingesta'       => 'ingesta_lote',
            'procesar'      => 'procesar_lote',
            'auto'          => 'auto_publicar_lote',
            'publicar'      => 'publicar_pendiente',
            'enviar'        => 'enviar_lote',
            'mantenimiento' => 'mantenimiento_diario',
        };

        if (!function_exists($funcion)) {
            tareas_log("aviso: $fichero no define $funcion()");
            continue;
        }

        // Cada tarea empieza a contar su presupuesto cuando le toca, sin
        // pasarse nunca del techo de la ejecucion entera. El generador y el
        // envio son la excepcion, por lo dicho arriba: su presupuesto es suyo.
        $limite = $imprescindible
            ? microtime(true) + $presupuesto
            : min($arranque + $techo, microtime(true) + $presupuesto);
        $resumen = $funcion($limite);
        $resumenes[$nombre] = is_array($resumen) ? $resumen : [];

        if ($nombre === 'mantenimiento') {
            ajuste_guardar('mantenimiento_dia', gmdate('Y-m-d'));
        }

        tareas_log($nombre . ': ' . json_encode($resumen, JSON_UNESCAPED_UNICODE));
    } catch (Throwable $e) {
        // Una tarea rota no puede impedir que corran las demas.
        $GLOBALS['tareas_errores']++;
        tareas_log("ERROR en $nombre: " . $e->getMessage());
        error_log('Bit & Breakfast, error en ' . $nombre . ': ' . $e->getMessage());
    }
}

tareas_log(sprintf('--- fin, %.1f segundos', microtime(true) - $arranque));

// El aviso, lo ultimo de todo: asi el parte lleva dentro la pasada entera,
// incluido lo que haya fallado.
try {
    tareas_avisar($resumenes);
} catch (Throwable $e) {
    // Que no salga el aviso no puede hacer que la pasada cuente como fallida.
    error_log('Bit & Breakfast, aviso del cron: ' . $e->getMessage());
}
