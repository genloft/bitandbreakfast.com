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
 * cron, que ediciones hay publicadas. Ni configuracion, ni credenciales, ni
 * direcciones de correo, ni nada que identifique a nadie. Todo lo que hay
 * aqui se puede deducir mirando la web con calma; la diferencia es que asi se
 * mira en un segundo.
 */

declare(strict_types=1);

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/estado.php';
require_once __DIR__ . '/lib/auto.php';

date_default_timezone_set('UTC');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

$raiz  = __DIR__;
$ahora = time();

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
 * Una consulta de una sola columna, en lista.
 */
function salud_lista(string $sql): array
{
    try {
        $sentencia = bd()->query($sql);

        return $sentencia === false ? [] : array_map('strval', $sentencia->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Las marcas que dejan en disco el cron y la portada.
 */
function salud_marcas(string $raiz, int $ahora): array
{
    $marcas = [
        'cron'     => $raiz . ESTADO_MARCA_CRON,
        'portada'  => $raiz . '/cache/.publicar',
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

if (!is_file($raiz . '/config/config.php')) {
    http_response_code(503);
    echo json_encode(['instalado' => false], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
    exit;
}

$marcas   = salud_marcas($raiz, $ahora);
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
        'generada'      => $marcas['generado']['cuando'],
        'ultimo_intento' => $marcas['portada']['cuando'],
    ],
    // La version de criterios que lleva el codigo frente a la que se ha
    // aplicado ya a lo publicado. Si la primera es mayor, hay una revision
    // pendiente; si son distintas y no bajan nunca, es que el despliegue se
    // quedo atras.
    'criterios' => [
        'codigo'    => AUTO_CRITERIOS,
        'aplicados' => (int) salud_valor("SELECT valor FROM ajustes WHERE clave = 'auto_criterios'", 0),
    ],
    'fuentes'   => [
        'activas'      => (int) salud_valor('SELECT COUNT(*) FROM fuentes WHERE activa = 1', 0),
        'con_error'    => (int) salud_valor(
            "SELECT COUNT(DISTINCT fuente_id) FROM log_ingesta
              WHERE resultado = 'error' AND inicio > (NOW() - INTERVAL 1 DAY)",
            0
        ),
        'ultima_lectura' => (string) (salud_valor('SELECT MAX(inicio) FROM log_ingesta', '') ?: 'nunca'),
        // Solo el nombre, nunca el mensaje del error: un mensaje de PDO lleva
        // rutas del servidor dentro y esta pagina la ve cualquiera.
        'fallando'     => salud_lista(
            "SELECT f.nombre FROM fuentes f
               JOIN log_ingesta l ON l.fuente_id = f.id
              WHERE l.resultado = 'error' AND l.inicio > (NOW() - INTERVAL 1 DAY)
              GROUP BY f.id, f.nombre
              ORDER BY f.nombre
              LIMIT 12"
        ),
        'nuevos_24h'   => (int) salud_valor(
            'SELECT COALESCE(SUM(nuevos), 0) FROM log_ingesta WHERE inicio > (NOW() - INTERVAL 1 DAY)',
            0
        ),
    ],
    'cola'      => [
        'items_sin_agrupar' => (int) salud_valor("SELECT COUNT(*) FROM items WHERE estado = 'nuevo'", 0),
        'racimos_candidatos' => (int) salud_valor("SELECT COUNT(*) FROM racimos WHERE estado = 'candidato'", 0),
        'racimos_descartados' => (int) salud_valor("SELECT COUNT(*) FROM racimos WHERE estado = 'descartado'", 0),
    ],
    'ediciones' => [
        'publicadas' => (int) salud_valor("SELECT COUNT(*) FROM ediciones WHERE estado <> 'abierta'", 0),
        // Los bits de la edicion abierta cuentan aunque todavia no esten en
        // estado 'publicado': lo estaran en cuanto se cierre, y lo que interesa
        // saber aqui es si se esta llenando.
        'abierta'    => (int) salud_valor("SELECT COUNT(*) FROM bits WHERE edicion_id IN (SELECT id FROM ediciones WHERE estado = 'abierta')", 0),
        'bits'       => (int) salud_valor("SELECT COUNT(*) FROM bits WHERE estado = 'publicado'", 0),
        'sin_revisar' => (int) salud_valor("SELECT COUNT(*) FROM bits WHERE redactado_por = 'ia' AND revisado = 0", 0),
    ],
];

echo json_encode($informe, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
