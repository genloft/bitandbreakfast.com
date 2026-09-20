<?php
/**
 * Estado del radar, en JSON.
 *
 * Existe por un motivo muy concreto: el sitio se desarrolla sin acceso al
 * servidor. No hay SSH, no hay base de datos a mano y el registro del cron
 * vive dentro del panel del alojamiento. Cuando la portada se queda quieta,
 * desde fuera no se puede distinguir "no hay noticias nuevas" de "la ingesta
 * lleva dos dias fallando". Esta pagina lo dice en una linea.
 *
 * Solo salen cuentas y fechas: cuantos items hay en cola, cuando corrio el
 * cron, cuanto hay publicado. Ni configuracion, ni credenciales, ni
 * direcciones de correo, ni nada que identifique a nadie. Todo lo que hay
 * aqui se puede deducir mirando la web con calma; la diferencia es que asi se
 * mira en un segundo.
 */

declare(strict_types=1);

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/estado.php';
require_once __DIR__ . '/lib/auto.php';
require_once __DIR__ . '/lib/correo.php';
require_once __DIR__ . '/lib/traducir.php';
require_once __DIR__ . '/lib/cifras.php';
require_once __DIR__ . '/lib/cumplimiento.php';
require_once __DIR__ . '/lib/agentica.php';

date_default_timezone_set('UTC');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

// Es el unico fichero del sitio pensado para que lo lea un desconocido. Si el
// alojamiento trae display_errors encendido -que es lo normal-, cualquier
// aviso imprimiria la ruta del servidor dentro del JSON, que es justo lo que
// la cabecera de arriba promete que no pasa.
ini_set('display_errors', '0');

$raiz  = __DIR__;
$ahora = time();

/** Segundos que vale la respuesta guardada. */
const SALUD_CACHE = 60;

/**
 * Una consulta de una sola celda, que nunca revienta la pagina.
 *
 * Si la base contesta mal, interesa el resto del informe -sobre todo cuando
 * el cron esta callado- mucho mas que el error de esa fila concreta.
 */
function salud_valor(string $sql, $defecto = null)
{
    try {
        $sentencia = bd()->query($sql);

        return $sentencia === false ? $defecto : $sentencia->fetchColumn();
    } catch (Throwable $e) {
        return $defecto;
    }
}

/**
 * Las fuentes que han fallado hoy, con el motivo en una palabra.
 */
function salud_fallando(): array
{
    // La pregunta no es "¿ha fallado hoy?" sino "¿esta fallando ahora?": una
    // fuente que fallo esta manana y se arreglo a mediodia no es un problema,
    // y mientras salga en esta lista parece que si. Se mira la ULTIMA lectura
    // de cada fuente, no las de todo el dia.
    $sql = "SELECT f.nombre, u.mensaje
              FROM fuentes f
              JOIN log_ingesta u ON u.id = (
                    SELECT l.id FROM log_ingesta l
                     WHERE l.fuente_id = f.id
                     ORDER BY l.inicio DESC, l.id DESC
                     LIMIT 1)
             WHERE u.resultado = 'error'
             ORDER BY f.nombre
             LIMIT 12";

    $salida = [];

    try {
        $filas = bd()->query($sql);

        foreach ($filas === false ? [] : $filas->fetchAll() as $fila) {
            $salida[(string) $fila['nombre']] = estado_motivo((string) ($fila['mensaje'] ?? ''));
        }
    } catch (Throwable $e) {
        return [];
    }

    return $salida;
}

/**
 * Los motivos de descarte mas repetidos, con su cuenta.
 *
 * Solo de lo reciente -dos dias-: los motivos de hace un mes cuentan una
 * politica editorial que a lo mejor ya se ha cambiado, y lo que hace falta
 * saber es por donde se esta cayendo lo de ahora.
 *
 * @return array [motivo => cuantos]
 */
function salud_descartes(): array
{
    $sql = "SELECT motivo_descarte, COUNT(*) AS cuantos
              FROM racimos
             WHERE estado = 'descartado'
               AND motivo_descarte <> ''
               AND ultimo_visto > (NOW() - INTERVAL 2 DAY)
             GROUP BY motivo_descarte
             ORDER BY cuantos DESC
             LIMIT 8";

    $salida = [];

    try {
        $filas = bd()->query($sql);

        foreach ($filas === false ? [] : $filas->fetchAll() as $fila) {
            // El prefijo 'automatico: ' lo llevan todos y no distingue nada.
            $motivo = trim(str_replace('automatico:', '', (string) $fila['motivo_descarte']));
            $salida[$motivo] = (int) $fila['cuantos'];
        }
    } catch (Throwable $e) {
        return [];
    }

    return $salida;
}

/**
 * MariaDB o MySQL, sin numero de version.
 */
function salud_motor(): string
{
    try {
        $version = (string) bd()->getAttribute(PDO::ATTR_SERVER_VERSION);
    } catch (Throwable $e) {
        return 'desconocido';
    }

    return stripos($version, 'mariadb') !== false ? 'mariadb' : 'mysql';
}

/**
 * Las marcas que dejan en disco el cron y la portada.
 */
function salud_marcas(string $raiz, int $ahora): array
{
    $marcas = [
        'cron'     => $raiz . ESTADO_MARCA_CRON,
        'portada'  => $raiz . '/cache/.publicar',
        'trabajo'  => $raiz . '/cache/.trabajo',
        'generado' => $raiz . '/publico/archivo.html',
    ];

    $salida = [];

    foreach ($marcas as $nombre => $ruta) {
        $edad = estado_edad(estado_marca($ruta), $ahora);

        $salida[$nombre] = [
            'segundos' => $edad,
            'cuando'   => estado_edad_texto($edad),
        ];
    }

    return $salida;
}

// -----------------------------------------------------------------------------

// Todo el sitio es estatico precisamente para aguantar visitas; esta pagina es
// el unico punto que siempre pregunta a la base de datos, y no pide contrasena.
// Sin esto, un bucle de peticiones desde una sola maquina tumba la base del
// plan compartido y con ella la generacion de la web. Un minuto de cache no le
// quita utilidad a un informe que habla de minutos y de horas.
$cache = $raiz . '/cache/salud.json';

if (is_file($cache) && $ahora - (int) @filemtime($cache) < SALUD_CACHE) {
    $guardado = (string) @file_get_contents($cache);

    if ($guardado !== '') {
        echo $guardado;
        exit;
    }
}

if (!is_file($raiz . '/config/config.php')) {
    http_response_code(503);
    echo json_encode(['instalado' => false], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
    exit;
}

$marcas   = salud_marcas($raiz, $ahora);
$lista    = lista_cuentas();
$callado  = estado_cron_callado(estado_marca($raiz . ESTADO_MARCA_CRON), $ahora, 7200);

$informe = [
    'instalado' => true,
    'ahora'     => gmdate('Y-m-d H:i:s'),
    'cron'      => [
        'ultima_vez' => $marcas['cron']['cuando'],
        'callado'    => $callado,
        // Con el cron callado, la portada mantiene el sitio a pulso: mas lento,
        // pero vivo. Decirlo aqui evita el susto de ver "callado: true".
        'suplencia'  => $callado ? 'la portada empuja la cadena en cada visita' : 'no hace falta',
    ],
    'web'       => [
        'generada'       => $marcas['generado']['cuando'],
        'ultimo_intento' => $marcas['portada']['cuando'],
        // La diferencia entre las dos marcas es la que importa: la primera se
        // pone antes de empezar y la segunda al terminar. Si el intento es
        // reciente y el trabajo no, es que algo se queda a medias.
        'ultimo_trabajo' => trim((string) @file_get_contents($raiz . '/cache/.trabajo')) ?: 'nunca',
    ],
    // La version de criterios que lleva el codigo frente a la que se ha
    // aplicado ya a lo publicado. Si la primera es mayor, hay una revision
    // pendiente; si son distintas y no bajan nunca, es que el despliegue se
    // quedo atras.
    // El esquema: cual fue la ultima migracion aplicada y cuantas hay escritas.
    // Sin esto, una migracion que falla es invisible desde fuera -el error va
    // al registro del cron, que vive en el servidor- y lo unico que se nota es
    // que una funcion nueva no hace nada. Esta linea contesta esa pregunta.
    'esquema' => [
        'ultima'     => (string) salud_valor(
            "SELECT valor FROM ajustes WHERE clave = 'migracion_ultima'",
            ''
        ) ?: 'ninguna',
        'escritas'   => count(glob(__DIR__ . '/sql/migraciones/*.sql') ?: []),
        // La familia del servidor, no su version: MariaDB acepta cosas que
        // MySQL no -ADD COLUMN IF NOT EXISTS, sin ir mas lejos- y las
        // migraciones se escriben contando con una de las dos. La version
        // exacta no sale: esta pagina es publica y un numero de parche es
        // media pista para quien busque por donde entrar.
        'motor'      => salud_motor(),
        // Vacio es lo normal. Con algo dentro, ahi esta el problema: esa
        // migracion y todas las de detras no se han aplicado, y el codigo
        // nuevo esta esperando algo que no existe.
        'error'      => (string) salud_valor(
            "SELECT valor FROM ajustes WHERE clave = 'migracion_error'",
            ''
        ),
    ],
    'criterios' => [
        'codigo'    => AUTO_CRITERIOS,
        'aplicados' => (int) salud_valor("SELECT valor FROM ajustes WHERE clave = 'auto_criterios'", 0),
    ],
    'fuentes'   => [
        'activas'      => (int) salud_valor('SELECT COUNT(*) FROM fuentes WHERE activa = 1', 0),
        'con_error_hoy' => (int) salud_valor(
            "SELECT COUNT(DISTINCT fuente_id) FROM log_ingesta
              WHERE resultado = 'error' AND inicio > (NOW() - INTERVAL 1 DAY)",
            0
        ),
        // Encendidas pero en penitencia: encadenaron fallos y no se les pide
        // nada hasta que pase el plazo. Si este numero sube y no baja, el
        // problema no es de los feeds, es de la IP del alojamiento.
        'dormidas'     => (int) salud_valor(
            'SELECT COUNT(*) FROM fuentes WHERE activa = 1 AND dormida_hasta > UTC_TIMESTAMP()',
            0
        ),
        'ultima_lectura' => (string) (salud_valor('SELECT MAX(inicio) FROM log_ingesta', '') ?: 'nunca'),
        // Las que estan fallando ahora mismo, con el motivo. Las rutas del
        // servidor se quitan: esta pagina es publica.
        'fallando'      => salud_fallando(),
        'nuevos_24h'   => (int) salud_valor(
            'SELECT COALESCE(SUM(nuevos), 0) FROM log_ingesta WHERE inicio > (NOW() - INTERVAL 1 DAY)',
            0
        ),
    ],
    'cola'      => [
        'items_sin_agrupar' => (int) salud_valor("SELECT COUNT(*) FROM items WHERE estado = 'nuevo'", 0),
        'racimos_candidatos' => (int) salud_valor("SELECT COUNT(*) FROM racimos WHERE estado = 'candidato'", 0),
        'racimos_descartados' => (int) salud_valor("SELECT COUNT(*) FROM racimos WHERE estado = 'descartado'", 0),
        // Por que se cae lo que se cae, de mas a menos. Es la respuesta a la
        // unica pregunta que se hace de verdad cuando la portada trae poco: no
        // "cuantos se han descartado" sino "por donde". Sin esto hay que
        // entrar a la base de datos para saber si el filtro esta afinado o
        // roto, y a la base de datos no se entra desde fuera.
        'descartes' => salud_descartes(),
    ],
    // El boletin: si el buzon esta configurado y cuanta gente hay. Son cuentas,
    // nunca direcciones.
    'boletin'   => [
        'buzon'          => correo_configurado() ? 'configurado' : 'sin configurar',
        'confirmados'    => $lista['confirmado'],
        'pendientes'     => $lista['pendiente'],
        'sin_enviar'     => (int) salud_valor(
            "SELECT COUNT(*) FROM ediciones WHERE estado = 'cerrada' AND fecha_envio IS NULL",
            0
        ),
    ],
    // El traductor: si esta puesto y cuanto le queda del mes. Sin el, el
    // sitio solo publica lo que venga en espanol, que son cuatro medios de
    // setenta: es la primera explicacion de una portada corta.
    'traductor' => [
        // Cual de los dos esta trabajando: el bueno, el de respaldo, o ninguno.
        'proveedor' => (string) (traducir_conf()['proveedor'] ?: 'ninguno'),
        'estado'  => traducir_configurado() ? 'conectado' : 'sin configurar',
        // La cuota de DeepL va por caracteres y por mes; la del respaldo, por
        // palabras y por dia. Se enseñan las dos porque la que aprieta es la
        // del que este trabajando.
        'gastado' => (int) traducir_cuota()['gastado'],
        'queda'   => (int) traducir_cuota()['queda'],
        'palabras_hoy' => max(0, TRADUCIR_PALABRAS_DIA - traducir_palabras_libres()),
        'palabras_libres' => traducir_palabras_libres(),
        'solo_es' => (string) ajuste('auto_solo_espanol', '1') === '1',
    ],
    // Ya no se cuentan ediciones, que no existen: se cuenta lo que hay
    // publicado y lo que ha entrado hoy, que es lo que contesta la pregunta
    // de si esto se esta llenando o esta parado.
    // /estadisticas.html no sale de esta base: alguien la revisa a mano, y
    // cron/mantenimiento.php avisa por correo cuando lleva demasiado sin
    // hacerlo. Esta pagina cuenta lo mismo que ese cron para que se pueda
    // comprobar desde fuera sin esperar al aviso.
    'cifras' => [
        'revisado'         => cifras_revisado(),
        'dias_sin_revisar' => (int) floor(($ahora - (strtotime(cifras_revisado() . ' UTC') ?: $ahora)) / 86400),
        'caduca_en_dias'   => CIFRAS_CADUCIDAD_DIAS,
        'caducada'         => cifras_caducadas(
            cifras_revisado(),
            gmdate('Y-m-d', $ahora),
            (string) salud_valor("SELECT valor FROM ajustes WHERE clave = 'cifras_aviso_revisado'", '')
        ),
    ],
    // Mismo control que 'cifras', para /agentica.html, con un plazo mas
    // corto: los protocolos de reserva agentica cambian de mes en mes.
    'agentica' => [
        'revisado'         => agentica_revisado(),
        'dias_sin_revisar' => (int) floor(($ahora - (strtotime(agentica_revisado() . ' UTC') ?: $ahora)) / 86400),
        'caduca_en_dias'   => AGENTICA_CADUCIDAD_DIAS,
        'caducada'         => agentica_caducadas(
            agentica_revisado(),
            gmdate('Y-m-d', $ahora),
            (string) salud_valor("SELECT valor FROM ajustes WHERE clave = 'agentica_aviso_revisado'", '')
        ),
    ],
    // Mismo control que 'cifras', para /cumplimiento.html. La diferencia
    // -aquí no hay un plazo fijo, la caducidad es la fecha pendiente más
    // próxima de la propia tabla normativa- vive en cumplimiento_limite_revision(),
    // no aquí: esto solo enseña el resultado.
    'cumplimiento' => [
        'revisado'         => cumplimiento_revisado(),
        'limite_revision'  => cumplimiento_limite_revision(cumplimiento_fechas(), gmdate('Y-m-d', $ahora), cumplimiento_revisado()),
        'caducada'         => cumplimiento_caducadas(
            cumplimiento_limite_revision(cumplimiento_fechas(), gmdate('Y-m-d', $ahora), cumplimiento_revisado()),
            gmdate('Y-m-d', $ahora),
            (string) salud_valor("SELECT valor FROM ajustes WHERE clave = 'cumplimiento_aviso_revisado'", ''),
            cumplimiento_revisado()
        ),
    ],
    'contenido' => [
        'bits'        => (int) salud_valor("SELECT COUNT(*) FROM bits WHERE estado = 'publicado'", 0),
        'hoy'         => (int) salud_valor("SELECT COUNT(*) FROM bits WHERE estado = 'publicado' AND dia = UTC_DATE()", 0),
        'ayer'        => (int) salud_valor("SELECT COUNT(*) FROM bits WHERE estado = 'publicado' AND dia = DATE_SUB(UTC_DATE(), INTERVAL 1 DAY)", 0),
        'dias'        => (int) salud_valor("SELECT COUNT(DISTINCT dia) FROM bits WHERE estado = 'publicado'", 0),
        'traducidos'  => (int) salud_valor("SELECT COUNT(*) FROM bits WHERE estado = 'publicado' AND traducido_de IS NOT NULL", 0),
        'sin_revisar' => (int) salud_valor("SELECT COUNT(*) FROM bits WHERE redactado_por = 'ia' AND revisado = 0", 0),
    ],
    // Cuentas, nunca quien vota ni qué bit en concreto: la tabla votos no
    // guarda la identidad de quien pulsa, y esta pagina tampoco la inventa
    // cruzando datos. Sirve para lo mismo que el resto de /salud.php: saber
    // si algo se esta moviendo sin entrar a la base.
    'votos' => [
        'total'     => (int) salud_valor('SELECT COUNT(*) FROM votos', 0),
        'positivos' => (int) salud_valor('SELECT COUNT(*) FROM votos WHERE valor > 0', 0),
        'negativos' => (int) salud_valor('SELECT COUNT(*) FROM votos WHERE valor < 0', 0),
    ],
];

$json = json_encode($informe, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
      . PHP_EOL;

@file_put_contents($cache, $json, LOCK_EX);

echo $json;
