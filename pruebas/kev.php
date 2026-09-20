<?php
/**
 * Pruebas de lib/kev.php.
 * Ejecutar:  php pruebas/kev.php
 *
 * No tocan la base de datos: el acceso a datos y a la red vive en
 * cron/kev.php, las decisiones aqui. Las entradas de prueba tienen la misma
 * forma que las del catalogo real de CISA -se comprobo contra una descarga
 * real al escribir esto-, no cadenas inventadas.
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/kev.php';

// --- kev_pendientes() --------------------------------------------------------

$catalogo = [
    // El catalogo llega ordenado por dateAdded descendente: la mas reciente
    // primero, igual que lo sirve CISA de verdad.
    ['cveID' => 'CVE-2026-0003', 'dateAdded' => '2026-09-18'],
    ['cveID' => 'CVE-2026-0002', 'dateAdded' => '2026-09-18'],
    ['cveID' => 'CVE-2026-0001', 'dateAdded' => '2026-09-16'],
    ['cveID' => 'CVE-2025-9999', 'dateAdded' => '2025-01-01'],
];

comprobar(
    'sin puntero, devuelve todo el catalogo invertido -de mas antigua a mas nueva-',
    ['CVE-2025-9999', 'CVE-2026-0001', 'CVE-2026-0002', 'CVE-2026-0003'],
    array_column(kev_pendientes($catalogo, '', []), 'cveID')
);

comprobar(
    'con puntero por fecha y esa fecha ya vista del todo, solo entran las mas nuevas',
    ['CVE-2026-0001', 'CVE-2026-0002', 'CVE-2026-0003'],
    array_column(kev_pendientes($catalogo, '2025-01-01', ['CVE-2025-9999']), 'cveID')
);

comprobar(
    'el mismo dia del puntero, solo las que no estan en vistos',
    ['CVE-2026-0002', 'CVE-2026-0003'],
    array_column(kev_pendientes($catalogo, '2026-09-18', ['CVE-2026-0001']), 'cveID')
);

comprobar(
    'todo visto: no queda nada pendiente',
    [],
    array_column(kev_pendientes($catalogo, '2026-09-18', ['CVE-2026-0002', 'CVE-2026-0003']), 'cveID')
);

comprobar(
    'para en la primera ya vista y no sigue mirando entradas mas antiguas',
    ['CVE-2026-0002', 'CVE-2026-0003'],
    array_column(kev_pendientes($catalogo, '2026-09-16', ['CVE-2026-0001']), 'cveID')
);

comprobar(
    'entradas sin cveID o sin dateAdded se ignoran, no revientan',
    ['CVE-2026-0003'],
    array_column(
        kev_pendientes([['cveID' => '', 'dateAdded' => '2026-09-19'], ['cveID' => 'CVE-2026-0003', 'dateAdded' => '2026-09-18']], '', []),
        'cveID'
    )
);

// --- kev_puntero_siguiente() -------------------------------------------------

$pendientes = kev_pendientes($catalogo, '', []); // las cuatro, de mas antigua a mas nueva.

comprobar(
    'sin procesar nada, el puntero no se mueve',
    ['fecha' => '2025-01-01', 'vistos' => ['CVE-2025-9999']],
    kev_puntero_siguiente($pendientes, 0, '2025-01-01', ['CVE-2025-9999'])
);

comprobar(
    'procesando solo la primera, el puntero se queda en su fecha',
    ['fecha' => '2025-01-01', 'vistos' => ['CVE-2025-9999']],
    kev_puntero_siguiente($pendientes, 1, '', [])
);

comprobar(
    'cruzando a un dia mas nuevo, el puntero salta y olvida el dia anterior',
    ['fecha' => '2026-09-16', 'vistos' => ['CVE-2026-0001']],
    kev_puntero_siguiente($pendientes, 2, '', [])
);

comprobar(
    'procesando todo el lote, el puntero llega al ultimo dia con sus dos cveID',
    ['fecha' => '2026-09-18', 'vistos' => ['CVE-2026-0002', 'CVE-2026-0003']],
    kev_puntero_siguiente($pendientes, 4, '', [])
);

// Tres entradas del mismo dia, para probar que un corte a medias dentro de
// ese dia amplia la lista de vistas en vez de pisarla.
$mismo_dia = [
    ['cveID' => 'CVE-D-3', 'dateAdded' => '2026-09-18'],
    ['cveID' => 'CVE-D-2', 'dateAdded' => '2026-09-18'],
    ['cveID' => 'CVE-D-1', 'dateAdded' => '2026-09-18'],
];

comprobar(
    'a medias en un dia que ya tenia vistas: se amplian, no se pisan',
    ['fecha' => '2026-09-18', 'vistos' => ['CVE-D-1', 'CVE-D-2']],
    kev_puntero_siguiente(
        kev_pendientes($mismo_dia, '2026-09-18', ['CVE-D-1']),
        1,
        '2026-09-18',
        ['CVE-D-1']
    )
);

// --- kev_puntero_inicial() ---------------------------------------------------

comprobar(
    'la primera pasada salta a la fecha mas reciente y recuerda sus cveID',
    ['fecha' => '2026-09-18', 'vistos' => ['CVE-2026-0003', 'CVE-2026-0002']],
    kev_puntero_inicial($catalogo)
);

comprobar(
    'un catalogo vacio no revienta: puntero vacio',
    ['fecha' => '', 'vistos' => []],
    kev_puntero_inicial([])
);

// --- kev_proveedores_de() ----------------------------------------------------

// El mismo formato que construye procesar_alias(): alias_norm => proveedor_id.
$alias = [
    'mews'           => 1,
    'oracle opera'   => 3,
    'siteminder'     => 13,
];

comprobar(
    'vendorProject + product casan con un alias de dos palabras',
    [3],
    kev_proveedores_de(['vendorProject' => 'Oracle', 'product' => 'OPERA E&S', 'vulnerabilityName' => ''], $alias)
);

comprobar(
    'un vendorProject generico y sin match no devuelve nada',
    [],
    kev_proveedores_de(['vendorProject' => 'Zyxel', 'product' => 'Multiple Products', 'vulnerabilityName' => 'Zyxel Multiple Products Use of Hard-Coded Credentials Vulnerability'], $alias)
);

comprobar(
    'oracle solo -sin opera- no casa: el alias exige las dos palabras juntas',
    [],
    kev_proveedores_de(['vendorProject' => 'Oracle', 'product' => 'WebLogic Server', 'vulnerabilityName' => ''], $alias)
);

comprobar(
    'el nombre de la vulnerabilidad tambien cuenta como texto a cruzar',
    [13],
    kev_proveedores_de(['vendorProject' => 'Acme', 'product' => 'Widget', 'vulnerabilityName' => 'SiteMinder Authentication Bypass'], $alias)
);

// --- kev_categoria_legible() --------------------------------------------------

comprobar('pms se lee como property-management systems', 'hotel property-management systems (PMS)', kev_categoria_legible('pms'));
comprobar('channel-manager menciona la palabra hotel', true, str_contains(kev_categoria_legible('channel-manager'), 'hotel'));
comprobar('una categoria desconocida cae al generico, no revienta', 'hotel technology', kev_categoria_legible('algo-que-no-existe'));

// --- kev_entrada_a_item() -----------------------------------------------------

$vulnerabilidad = [
    'cveID'                      => 'CVE-2026-12345',
    'vendorProject'               => 'Oracle',
    'product'                     => 'OPERA Cloud',
    'vulnerabilityName'           => 'Oracle OPERA Cloud Remote Code Execution Vulnerability',
    'dateAdded'                   => '2026-09-18',
    'shortDescription'            => 'Oracle OPERA Cloud contains a vulnerability that allows remote code execution.',
    'requiredAction'              => 'Apply mitigations per vendor instructions.',
    'dueDate'                     => '2026-10-09',
    'knownRansomwareCampaignUse'  => 'Unknown',
];

$item = kev_entrada_a_item($vulnerabilidad, 'pms');

comprobar('el guid lleva el prefijo kev- y el cveID tal cual', 'kev-CVE-2026-12345', $item['guid']);
comprobar('la url apunta a la ficha del CVE en el NVD', 'https://nvd.nist.gov/vuln/detail/CVE-2026-12345', $item['url']);
comprobar('el titular cita el producto y el proveedor de CISA', true, str_contains($item['titulo'], 'OPERA Cloud') && str_contains($item['titulo'], 'Oracle'));
comprobar('el titular explica el tipo de producto en ingles y con la palabra hotel', true, str_contains($item['titulo'], 'hotel property-management systems (PMS)'));
comprobar('el resumen lleva la descripcion oficial de CISA', true, str_contains($item['resumen'], 'remote code execution'));
comprobar('el resumen lleva la accion requerida', true, str_contains($item['resumen'], 'Apply mitigations'));
comprobar('el resumen lleva el plazo de la agencia', true, str_contains($item['resumen'], '2026-10-09'));
comprobar('sin ransomware conocido, no se menciona', false, str_contains($item['resumen'], 'ransomware'));
comprobar('publicado es la fecha de CISA a medianoche UTC', '2026-09-18 00:00:00', $item['publicado']);
comprobar('el autor es CISA', 'CISA', $item['autor']);

$con_ransomware = kev_entrada_a_item(['cveID' => 'CVE-2026-1', 'knownRansomwareCampaignUse' => 'Known'], '');

comprobar(
    'el ransomware conocido de CISA si se menciona',
    true,
    str_contains($con_ransomware['resumen'], 'ransomware')
);

comprobar(
    'sin cveID no hay url que comprobar, y el item no deberia guardarse',
    '',
    kev_entrada_a_item([], '')['url']
);

resumen_pruebas('Pruebas de lib/kev.php');
