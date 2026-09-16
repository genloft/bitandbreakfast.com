<?php
/**
 * Despachador de tareas programadas.
 *
 * Una sola entrada de cron para todo el sistema, porque el plan de
 * alojamiento puede no permitir mas. Este fichero mira el reloj y decide que
 * toca en cada ejecucion.
 *
 *   Cada ejecucion : ingesta y, cuando exista, procesado
 *   A las 05:00 UTC: mantenimiento
 *
 * Uso normal (desde cron, cada hora):
 *   php cron/tareas.php
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

$config = config();

date_default_timezone_set('UTC');
error_reporting(E_ALL);
ini_set('display_errors', $config['depuracion'] ? '1' : '0');

$arranque    = microtime(true);

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
}

/**
 * Decide si una tarea toca en esta ejecucion.
 */
function tareas_toca(string $tarea, string $forzada): bool
{
    if ($forzada !== '') {
        return $forzada === $tarea;
    }

    $hora = (int) gmdate('G');

    return match ($tarea) {
        'ingesta', 'procesar' => true,
        'mantenimiento'       => $hora === 5,
        'publicar'            => false,   // lo dispara el cierre de edicion
        default               => false,
    };
}

$tareas = [
    'ingesta'       => dirname(__DIR__) . '/cron/ingesta.php',
    'procesar'      => dirname(__DIR__) . '/cron/procesar.php',
    'publicar'      => dirname(__DIR__) . '/cron/publicar.php',
    'mantenimiento' => dirname(__DIR__) . '/cron/mantenimiento.php',
];

tareas_log('--- arranca el despachador' . ($forzada !== '' ? " (forzado: $forzada)" : ''));

foreach ($tareas as $nombre => $fichero) {
    if (!tareas_toca($nombre, $forzada)) {
        continue;
    }

    // Las tareas de fases posteriores todavia no existen. Que falte un
    // fichero no es un error: es que aun no hemos llegado a esa fase.
    if (!is_readable($fichero)) {
        continue;
    }

    if (microtime(true) - $arranque >= $techo) {
        tareas_log("sin presupuesto para $nombre, queda para la proxima pasada");
        break;
    }

    try {
        require_once $fichero;

        $funcion = match ($nombre) {
            'ingesta'       => 'ingesta_lote',
            'procesar'      => 'procesar_lote',
            'publicar'      => 'publicar_pendiente',
            'mantenimiento' => 'mantenimiento_diario',
        };

        if (!function_exists($funcion)) {
            tareas_log("aviso: $fichero no define $funcion()");
            continue;
        }

        // Cada tarea empieza a contar su presupuesto cuando le toca, sin
        // pasarse nunca del techo de la ejecucion entera.
        $limite  = min($arranque + $techo, microtime(true) + $presupuesto);
        $resumen = $funcion($limite);

        tareas_log($nombre . ': ' . json_encode($resumen, JSON_UNESCAPED_UNICODE));
    } catch (Throwable $e) {
        // Una tarea rota no puede impedir que corran las demas.
        tareas_log("ERROR en $nombre: " . $e->getMessage());
        error_log('Bit & Breakfast, error en ' . $nombre . ': ' . $e->getMessage());
    }
}

tareas_log(sprintf('--- fin, %.1f segundos', microtime(true) - $arranque));
