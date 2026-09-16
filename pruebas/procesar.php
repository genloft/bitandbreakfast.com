<?php
/**
 * Pruebas de lib/puntuar.php y lib/agrupar.php.
 * Ejecutar:  php pruebas/procesar.php
 *
 * No tocan la base de datos: la fase 2 esta partida justo para esto, con el
 * acceso a datos en cron/procesar.php y las decisiones aqui. Los casos son
 * titulares del estilo de los que llegan de verdad, no cadenas inventadas.
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/puntuar.php';
require_once dirname(__DIR__) . '/lib/agrupar.php';

// Los mismos defectos que usa el cron, sin copiarlos: tenerlos duplicados
// garantizaba que antes o despues la prueba y la produccion se separaran.
$conf = puntuar_defectos();

$terminos = [
    ['termino' => 'ransomware',    'peso' => 9],
    ['termino' => 'zero-day',      'peso' => 8],
    ['termino' => 'pms',           'peso' => 6],
    ['termino' => 'lider mundial', 'peso' => -5],
    ['termino' => 'socio estrategico', 'peso' => -4],
];

// --- Terminos del diccionario en un texto -----------------------------------

comprobar(
    'encuentra un termino positivo del titular',
    ['positivo' => 9, 'negativo' => 0],
    puntuar_terminos_en('Ransomware cierra un hotel de Barcelona', $terminos)
);

// El diccionario guarda "zero-day" con guion y la normalizacion convierte
// cualquier signo en espacio: si solo se normalizara un lado, no casarian.
comprobar(
    'un termino con guion casa con el titular normalizado',
    ['positivo' => 8, 'negativo' => 0],
    puntuar_terminos_en('Ataque zero-day contra OPERA Cloud', $terminos)
);

comprobar(
    'no casa dentro de otra palabra',
    ['positivo' => 0, 'negativo' => 0],
    puntuar_terminos_en('Los ransomwares del verano', $terminos)
);

comprobar(
    'suma varios terminos del mismo texto',
    ['positivo' => 15, 'negativo' => 0],
    puntuar_terminos_en('Ransomware en el PMS de la cadena', $terminos)
);

comprobar(
    'los terminos de publirreportaje restan',
    ['positivo' => 6, 'negativo' => -5],
    puntuar_terminos_en('El lider mundial en PMS presenta su novedad', $terminos)
);

// --- Puntos del diccionario -------------------------------------------------

comprobar(
    'el titular cuenta doble y el resumen simple',
    ['puntos' => 24, 'publirreportaje' => false],
    puntuar_diccionario('Ransomware en una cadena hotelera', 'El PMS quedo fuera de servicio', $terminos, 30)
);

comprobar(
    'el tope limita lo que puede aportar el diccionario',
    ['puntos' => 30, 'publirreportaje' => false],
    puntuar_diccionario('Ransomware y zero-day contra el PMS', null, $terminos, 30)
);

comprobar(
    'un marcador fuerte en el titular enciende el publirreportaje',
    ['puntos' => -10, 'publirreportaje' => true],
    puntuar_diccionario('El lider mundial presenta su novedad', null, $terminos, 30)
);

// Lo negativo no se topa: el filtro anti-publirreportaje tiene que poder
// hundir un candidato por debajo de todo lo demas.
comprobar(
    'un marcador flojo y suelto resta pero no condena',
    ['puntos' => -4, 'publirreportaje' => false],
    puntuar_diccionario('Acuerdo entre dos compañias', 'Se convierte en socio estrategico del grupo', $terminos, 30)
);

// --- Frescura ---------------------------------------------------------------

$ahora = strtotime('2026-09-16 12:00:00 UTC');

comprobar(
    'menos de 24 horas puntua alto',
    10,
    puntuar_frescura('2026-09-16 09:00:00', $conf, $ahora)
);

comprobar(
    'entre 24 y 72 horas puntua medio',
    5,
    puntuar_frescura('2026-09-14 12:00:00', $conf, $ahora)
);

comprobar(
    'dentro de la semana puntua poco',
    2,
    puntuar_frescura('2026-09-11 12:00:00', $conf, $ahora)
);

comprobar(
    'mas de una semana no puntua',
    0,
    puntuar_frescura('2026-08-30 12:00:00', $conf, $ahora)
);

// Hay feeds con el reloj mal puesto; una fecha futura no es una primicia.
comprobar(
    'una fecha en el futuro cuenta como recien publicada',
    10,
    puntuar_frescura('2026-09-20 12:00:00', $conf, $ahora)
);

comprobar(
    'una fecha ilegible no puntua ni rompe',
    0,
    puntuar_frescura('no es una fecha', $conf, $ahora)
);

// --- Bonus por tipo de fuente -----------------------------------------------

comprobar(
    'una pagina de estado es un incidente',
    12,
    puntuar_bonus_fuente('estado', $conf)
);

comprobar(
    'un boletin oficial es regulacion',
    10,
    puntuar_bonus_fuente('normativa', $conf)
);

comprobar(
    'un changelog tiene su bonus',
    6,
    puntuar_bonus_fuente('changelog', $conf)
);

comprobar(
    'la prensa general no lleva bonus',
    0,
    puntuar_bonus_fuente('prensa', $conf)
);

// --- Puntuacion de un item --------------------------------------------------

$item = [
    'peso_fuente'        => 5,
    'tipo_fuente'        => 'prensa',
    'publicado'          => '2026-09-16 09:00:00',
    'puntos_diccionario' => 18,
    'publirreportaje'    => false,
    'proveedores'        => 1,
];

// 5*3 de fuente + 18 de diccionario + 10 de frescura + 8 de proveedor.
comprobar(
    'suma todos los componentes de un item',
    51,
    puntuar_item($item, $conf, $ahora)
);

comprobar(
    'sin proveedores mencionados no hay esos ocho puntos',
    43,
    puntuar_item(['proveedores' => 0] + $item, $conf, $ahora)
);

comprobar(
    'el publirreportaje resta su penalizacion entera',
    39,
    puntuar_item(['publirreportaje' => true] + $item, $conf, $ahora)
);

comprobar(
    'una pagina de estado suma su bonus de incidente',
    63,
    puntuar_item(['tipo_fuente' => 'estado'] + $item, $conf, $ahora)
);

// --- Puntuacion de un racimo ------------------------------------------------

comprobar(
    'el racimo vale su mejor item mas las fuentes que lo cuentan',
    58,
    puntuar_racimo(40, 3, $conf)
);

comprobar(
    'la corroboracion se corta en cinco fuentes',
    70,
    puntuar_racimo(40, 9, $conf)
);

comprobar(
    'un racimo de un solo item suma una fuente',
    46,
    puntuar_racimo(40, 1, $conf)
);

// --- Proveedores mencionados ------------------------------------------------

$alias = [
    'mews'         => 1,
    'cloudbeds'    => 2,
    'oracle opera' => 3,
    'opera cloud'  => 3,
    'siteminder'   => 4,
];

comprobar(
    'encuentra un proveedor en el titular',
    [1],
    agrupar_proveedores_en('Mews compra un RMS europeo', null, $alias)
);

comprobar(
    'un alias de dos palabras tambien se encuentra',
    [3],
    agrupar_proveedores_en('Caida de Oracle OPERA en Europa', null, $alias)
);

comprobar(
    'dos alias del mismo proveedor no lo duplican',
    [3],
    agrupar_proveedores_en('Oracle OPERA y OPERA Cloud', null, $alias)
);

comprobar(
    'tambien mira el resumen del feed',
    [2, 4],
    agrupar_proveedores_en('Integracion entre dos plataformas', 'Cloudbeds anuncia conexion con SiteMinder', $alias)
);

comprobar(
    'no confunde un proveedor con una palabra que lo contiene',
    [],
    agrupar_proveedores_en('Mewsic, la radio de los hoteles', null, $alias)
);

// --- Decision de agrupacion -------------------------------------------------

comprobar(
    'con similitud alta se agrupa sin mas condiciones',
    7,
    agrupar_elegir([['racimo_id' => 7, 'similitud' => 0.52, 'proveedores_comunes' => 0]], $conf)['racimo_id']
);

comprobar(
    'con similitud floja y proveedores compartidos tambien se agrupa',
    9,
    agrupar_elegir([['racimo_id' => 9, 'similitud' => 0.33, 'proveedores_comunes' => 2]], $conf)['racimo_id']
);

comprobar(
    'con similitud floja y un solo proveedor comun, no se agrupa',
    null,
    agrupar_elegir([['racimo_id' => 9, 'similitud' => 0.33, 'proveedores_comunes' => 1]], $conf)
);

comprobar(
    'por debajo del umbral bajo no salvan ni los proveedores',
    null,
    agrupar_elegir([['racimo_id' => 9, 'similitud' => 0.21, 'proveedores_comunes' => 5]], $conf)
);

comprobar(
    'sin candidatos no hay racimo',
    null,
    agrupar_elegir([], $conf)
);

comprobar(
    'entre varios candidatos gana el mas parecido',
    2,
    agrupar_elegir([
        ['racimo_id' => 1, 'similitud' => 0.46, 'proveedores_comunes' => 3],
        ['racimo_id' => 2, 'similitud' => 0.71, 'proveedores_comunes' => 0],
        ['racimo_id' => 3, 'similitud' => 0.10, 'proveedores_comunes' => 9],
    ], $conf)['racimo_id']
);

comprobar(
    'a igual similitud gana el que comparte mas proveedores',
    5,
    agrupar_elegir([
        ['racimo_id' => 4, 'similitud' => 0.50, 'proveedores_comunes' => 1],
        ['racimo_id' => 5, 'similitud' => 0.50, 'proveedores_comunes' => 4],
    ], $conf)['racimo_id']
);

// Empate total: la noticia se acumula donde ya estaba en vez de repartirse.
comprobar(
    'a igualdad total gana el racimo mas antiguo',
    11,
    agrupar_elegir([
        ['racimo_id' => 40, 'similitud' => 0.50, 'proveedores_comunes' => 2],
        ['racimo_id' => 11, 'similitud' => 0.50, 'proveedores_comunes' => 2],
    ], $conf)['racimo_id']
);

comprobar(
    'cuenta los proveedores que comparten dos items',
    1,
    agrupar_proveedores_comunes([1, 2, 3], [3, 4])
);

comprobar(
    'sin proveedores comunes, cero',
    0,
    agrupar_proveedores_comunes([1, 2], [3, 4])
);

// --- Fusion de racimos ------------------------------------------------------
//
// Un item que se parece mucho a dos racimos a la vez significa que esos dos
// racimos cuentan lo mismo y nacieron separados. Es el unico momento en que
// eso se puede saber.

comprobar(
    'los racimos muy parecidos al item se fusionan con el elegido',
    [12],
    agrupar_fusionables([
        ['racimo_id' => 8,  'similitud' => 0.60, 'proveedores_comunes' => 0],
        ['racimo_id' => 12, 'similitud' => 0.50, 'proveedores_comunes' => 0],
    ], 8, $conf)
);

// La puerta de los proveedores sirve para enganchar un item, no para declarar
// que dos racimos enteros son la misma noticia.
comprobar(
    'un racimo que solo pasa por proveedores no se fusiona',
    [],
    agrupar_fusionables([
        ['racimo_id' => 8,  'similitud' => 0.60, 'proveedores_comunes' => 0],
        ['racimo_id' => 12, 'similitud' => 0.33, 'proveedores_comunes' => 4],
    ], 8, $conf)
);

comprobar(
    'el racimo destino nunca se fusiona consigo mismo',
    [],
    agrupar_fusionables([
        ['racimo_id' => 8, 'similitud' => 0.90, 'proveedores_comunes' => 2],
    ], 8, $conf)
);

comprobar(
    'varios items del mismo racimo no lo cuentan dos veces',
    [12],
    agrupar_fusionables([
        ['racimo_id' => 12, 'similitud' => 0.80, 'proveedores_comunes' => 0],
        ['racimo_id' => 12, 'similitud' => 0.55, 'proveedores_comunes' => 0],
    ], 8, $conf)
);

// --- Los umbrales contra la similitud de verdad ------------------------------
//
// Todo lo anterior le pasa la similitud a mano. Estas dos comprueban que los
// numeros elegidos, 0.45 y 0.30, separan de verdad lo que tienen que separar.

comprobar_rango(
    'dos titulares del mismo suceso pasan el umbral alto',
    (float) $conf['agrupar_umbral_alto'],
    1.0,
    texto_similitud(
        'Oracle OPERA Cloud sufre una caida global de cuatro horas',
        'Caida global de Oracle OPERA Cloud durante cuatro horas'
    )
);

comprobar_rango(
    'dos noticias distintas no llegan ni al umbral bajo',
    0.0,
    (float) $conf['agrupar_umbral_bajo'],
    texto_similitud(
        'Mews compra un motor de reservas europeo',
        'Cloudbeds lanza una integracion de pagos con Adyen'
    )
);

resumen_pruebas('Pruebas de la fase 2: agrupacion y puntuacion');
