<?php
/**
 * Boletin de vulnerabilidades: cruza el catalogo KEV de CISA -JSON publico,
 * sin clave, vulnerabilidades explotadas de verdad, no teoricas- contra el
 * catalogo de proveedores. Cuando una entrada nombra a uno, se guarda como
 * item normal para que la agrupe, la puntue y la publique el mismo cauce de
 * siempre: cron/procesar.php y cron/auto.php no saben ni les importa de
 * donde ha salido.
 *
 * Se ejecuta desde cron/tareas.php, o directamente por linea de comandos:
 *   php cron/kev.php
 *
 * Un solo fichero JSON y no doscientas fuentes, asi que no hace falta el
 * puntero circular por id que usa la ingesta: aqui el puntero es por fecha
 * -kev_puntero_fecha y kev_puntero_vistos en la tabla ajustes-, para no
 * volver a mirar en cada pasada el catalogo entero. La descarga en si ya
 * cuesta poco gracias a ETag/Last-Modified -un 304 en cuanto CISA no ha
 * anadido nada-, pero cruzar mil y pico entradas contra el alias cada cinco
 * minutos seria trabajo de sobra para no encontrar nada nuevo. El calculo
 * -que entradas son nuevas, como avanza el puntero, que proveedores
 * menciona cada una- vive en lib/kev.php, sin tocar la base de datos.
 */

require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/feed.php';
require_once dirname(__DIR__) . '/lib/kev.php';
require_once dirname(__DIR__) . '/cron/ingesta.php';   // ingesta_guardar_item()
require_once dirname(__DIR__) . '/cron/procesar.php';  // procesar_alias()

const KEV_URL = 'https://www.cisa.gov/sites/default/files/feeds/known_exploited_vulnerabilities.json';

/**
 * Ejecuta un lote de cruce contra el catalogo KEV.
 *
 * @param float $limite Marca de tiempo (microtime) a partir de la cual se
 *                       deja de empezar entradas nuevas.
 * @return array Resumen para el registro.
 */
function kev_lote(float $limite): array
{
    $resumen = ['descargado' => false, 'nuevas' => 0, 'guardadas' => 0, 'errores' => 0];

    $fuente = kev_fuente();

    // Sin la fila de fuentes -la migracion todavia no ha corrido, o alguien
    // la ha desactivado a mano desde el panel- no hay donde anclar los
    // items ni sentido en seguir. Ninguno de los dos casos es un error de
    // esta pasada.
    if ($fuente === null || (int) $fuente['activa'] !== 1) {
        return $resumen;
    }

    $respuesta = feed_peticion(
        KEV_URL,
        (string) ajuste('kev_etag', ''),
        (string) ajuste('kev_last_modified', ''),
        feed_agente()
    );

    if ($respuesta['codigo'] === 304) {
        return $resumen;
    }

    if ($respuesta['codigo'] !== 200) {
        $resumen['errores']++;
        error_log(
            'Bit & Breakfast, KEV: HTTP ' . $respuesta['codigo']
                . ($respuesta['error'] !== '' ? ' - ' . $respuesta['error'] : '')
        );

        return $resumen;
    }

    $catalogo         = json_decode($respuesta['cuerpo'], true);
    $vulnerabilidades = is_array($catalogo['vulnerabilities'] ?? null) ? $catalogo['vulnerabilities'] : [];

    if (!$vulnerabilidades) {
        $resumen['errores']++;
        error_log('Bit & Breakfast, KEV: el catalogo responde pero no trae vulnerabilidades legibles');

        return $resumen;
    }

    $resumen['descargado'] = true;

    // Las cabeceras condicionales se guardan aunque no haya nada mas que
    // hacer: son lo que convierte la proxima pasada, si CISA no ha tocado el
    // catalogo, en un 304 de milisegundos en vez de una descarga entera.
    ajuste_guardar('kev_etag', (string) ($respuesta['etag'] ?? ''));
    ajuste_guardar('kev_last_modified', (string) ($respuesta['last_modified'] ?? ''));

    if (ajuste('kev_puntero_fecha', null) === null) {
        // Primera vez que corre esta tarea: se pone al dia en silencio, sin
        // convertir el catalogo historico en un aluvion de avisos. Ver
        // kev_puntero_inicial() para el motivo completo.
        kev_guardar_puntero(kev_puntero_inicial($vulnerabilidades));

        return $resumen;
    }

    $puntero_fecha  = (string) ajuste('kev_puntero_fecha', '');
    $puntero_vistos = kev_leer_vistos();
    $pendientes     = kev_pendientes($vulnerabilidades, $puntero_fecha, $puntero_vistos);

    $resumen['nuevas'] = count($pendientes);

    if (!$pendientes) {
        return $resumen;
    }

    $alias      = procesar_alias();
    $categorias = kev_categorias_proveedores();
    $procesadas = 0;

    foreach ($pendientes as $vulnerabilidad) {
        if (microtime(true) >= $limite) {
            break;
        }

        $procesadas++;

        $proveedores = kev_proveedores_de($vulnerabilidad, $alias);

        // La inmensa mayoria de entradas del KEV no habla de nada de
        // nuestro catalogo -Cisco, Fortinet, el kernel de Linux-, y esta
        // vuelta no es un fallo: es la puerta haciendo su trabajo.
        if (!$proveedores) {
            continue;
        }

        $entrada = kev_entrada_a_item($vulnerabilidad, $categorias[$proveedores[0]] ?? '');

        if ($entrada['url'] === '') {
            continue;
        }

        try {
            if (ingesta_guardar_item($fuente, $entrada)) {
                $resumen['guardadas']++;
            }
        } catch (Throwable $e) {
            $resumen['errores']++;
            error_log('Bit & Breakfast, KEV: no se pudo guardar ' . $entrada['guid'] . ': ' . $e->getMessage());
        }
    }

    kev_guardar_puntero(kev_puntero_siguiente($pendientes, $procesadas, $puntero_fecha, $puntero_vistos));

    return $resumen;
}

/**
 * La fila de fuentes que representa el KEV, o null si la migracion 023
 * todavia no ha corrido. No poder encontrarla no es un error de esta
 * pasada: es que el despliegue esta a medias, y la siguiente migracion la
 * traera.
 */
function kev_fuente(): ?array
{
    $st = bd()->prepare("SELECT id, idioma, activa FROM fuentes WHERE gestion = 'manual' AND nombre = 'CISA KEV' LIMIT 1");
    $st->execute();
    $fuente = $st->fetch();

    return $fuente !== false ? $fuente : null;
}

/**
 * La categoria de cada proveedor del catalogo: 'id' => categoria, para
 * poder nombrar en el titular que tipo de producto toca sin una consulta
 * mas por cada entrada del KEV que haga match.
 */
function kev_categorias_proveedores(): array
{
    $categorias = [];

    foreach (bd()->query('SELECT id, categoria FROM proveedores') as $fila) {
        $categorias[(int) $fila['id']] = (string) $fila['categoria'];
    }

    return $categorias;
}

/**
 * Los cveID ya vistos del dia que marca kev_puntero_fecha, guardados como
 * JSON porque no son un numero ni caben en una sola columna de ajustes.
 */
function kev_leer_vistos(): array
{
    $crudo  = (string) ajuste('kev_puntero_vistos', '[]');
    $vistos = json_decode($crudo, true);

    return is_array($vistos) ? array_map('strval', $vistos) : [];
}

function kev_guardar_puntero(array $puntero): void
{
    ajuste_guardar('kev_puntero_fecha', (string) $puntero['fecha']);
    ajuste_guardar('kev_puntero_vistos', (string) json_encode(array_values($puntero['vistos']), JSON_UNESCAPED_UNICODE));
}

// Ejecucion directa por linea de comandos, para poder probar sin esperar al cron.
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {
    $limite  = microtime(true) + (float) (config('presupuesto_cron') ?? 25);
    $resumen = kev_lote($limite);

    printf(
        "kev: descargado=%s, %d nuevas, %d guardadas, %d errores\n",
        $resumen['descargado'] ? 'si' : 'no',
        $resumen['nuevas'],
        $resumen['guardadas'],
        $resumen['errores']
    );
}
