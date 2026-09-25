<?php
/**
 * Pruebas del mapa del stack. Ejecutar: php pruebas/heatmap.php
 *
 * Aqui se prueba lo que un error no delata a simple vista. Que una casilla
 * salga roja se ve; que salga roja por sumar cinco noticias pequenas en vez
 * de coger la mayor, no: el mapa parece correcto y esta mintiendo. Lo mismo
 * con el decay -un mapa que no envejece se ve estupendo el primer mes- y con
 * el tope de tres alarmas.
 *
 * No toca la base de datos: heatmap_componer() recibe los bits, no los pide.
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/heatmap.php';
require_once dirname(__DIR__) . '/lib/stack_grafo.php';
require_once dirname(__DIR__) . '/lib/stack_lexico.php';

/** Un bit de mentira con lo justo para clasificarlo y puntuarlo. */
function bit_falso(array $campos = []): array
{
    return $campos + [
        'id'         => 1,
        'racimo_id'  => 1,
        'titular'    => 'Una noticia',
        'cuerpo'     => '',
        'por_que'    => '',
        'categoria'  => 'tecnologia-general',
        'tipo'       => 'producto',
        'madurez'    => 'anuncio',
        'dia'        => '2026-09-22',
        'url'        => 'https://ejemplo.test/n',
        'fuente'     => 'Un medio',
    ];
}

// --- La taxonomia es un contrato --------------------------------------------

comprobar(
    'hay veinticuatro nodos',
    24,
    count(stack_nodos())
);

comprobar(
    'hay seis areas',
    6,
    count(stack_areas())
);

$sin_area = [];

foreach (stack_nodos() as $id => $nodo) {
    if (!isset(stack_areas()[$nodo['area']])) {
        $sin_area[] = $id;
    }
}

comprobar('ningun nodo apunta a un area que no existe', [], $sin_area);

// Si un nodo se queda fuera de stack_nodos_de(), desaparece del mapa sin que
// nada falle: la rejilla se pinta con una casilla menos y nadie lo nota.
$repartidos = 0;

foreach (array_keys(stack_areas()) as $area) {
    $repartidos += count(stack_nodos_de($area));
}

comprobar('las areas reparten los veinticuatro nodos, sin perder ninguno', 24, $repartidos);

$nodos_taxonomia = array_column(stack_taxonomia()['nodes'], 'id');

comprobar(
    'taxonomy.json lleva los mismos nodos que el catalogo',
    array_keys(stack_nodos()),
    $nodos_taxonomia
);

// --- Los alias no pueden comerse palabras que los contienen -----------------

comprobar(
    'un alias corto no salta dentro de otra palabra',
    [],
    stack_menciones('Es posible que la OTAN publique algo sobre el apiario')
);

comprobar(
    'el alias suelto si salta',
    true,
    array_key_exists('ota-metas', stack_menciones('Booking com cambia sus comisiones'))
);

comprobar(
    'los acentos y las mayusculas no estorban',
    true,
    array_key_exists('gobierno-ia', stack_menciones('La AEPD publica una guía sobre protección de datos'))
);

// --- Clasificacion -----------------------------------------------------------

comprobar(
    'la noticia va al nodo que mas alias suyos menciona',
    'pagos',
    stack_clasificar(bit_falso([
        'titular' => 'Adyen y Redsys actualizan su pasarela de pagos',
        'cuerpo'  => 'La tokenizacion cambia para cumplir PCI DSS.',
    ]))[0]
);

comprobar(
    'como mucho tres nodos por noticia',
    3,
    count(stack_clasificar(bit_falso([
        'titular' => 'El PMS conecta con el channel manager, el CRM y la pasarela de pagos',
        'cuerpo'  => 'La API del property management system llega al revenue management y al wifi del hotel.',
    ])))
);

comprobar(
    'sin un solo alias, la categoria hace de red de seguridad',
    ['pms'],
    stack_clasificar(bit_falso([
        'titular'   => 'Una cadena renueva su sistema',
        'categoria' => 'pms-crs',
    ]))
);

comprobar(
    'lo que entra por la red de seguridad queda marcado como tal',
    'categoria',
    stack_clasificar_detalle(bit_falso([
        'titular'   => 'Una cadena renueva su sistema',
        'categoria' => 'pms-crs',
    ]))['origen']
);

comprobar(
    'sin alias y sin categoria util, la noticia no entra en el mapa',
    [],
    stack_clasificar(bit_falso(['titular' => 'Una cadena presenta resultados']))
);

// --- El lexico: de donde sale ahora la senal ------------------------------------
//
// Estas son las pruebas que faltaban la primera vez. La rubrica anterior leia
// bits.tipo y bits.madurez, que solo rellena una persona en el panel, y este
// sitio publica solo: los cincuenta y tres bits vivos en produccion llevaban
// los tres el valor por defecto y el mapa salia con los dieciseis nodos
// identicos. Ninguna prueba lo detecto porque todas usaban bits inventados con
// el tipo puesto a mano.

comprobar(
    'un termino fuerte, solo, ya dice que hay un problema',
    true,
    lexico_leer(bit_falso(['cuerpo' => 'El fabricante anuncia el fin de soporte para enero.']))['riesgo']
);

comprobar(
    'un termino debil, solo, no basta',
    false,
    lexico_leer(bit_falso([
        'cuerpo' => 'Detras de las cifras aparece una vulnerabilidad que los destinos tardan en reconocer.',
    ]))['riesgo']
);

comprobar(
    'pero dos debiles si',
    true,
    lexico_leer(bit_falso(['cuerpo' => 'La caida de reservas es un riesgo para el sector.']))['riesgo']
);

comprobar(
    'y uno debil en una tematica que ya es de riesgo, tambien',
    true,
    lexico_leer(bit_falso([
        'cuerpo'    => 'Los investigadores describen una vulnerabilidad en el sistema.',
        'categoria' => 'ciberseguridad',
    ]))['riesgo']
);

comprobar(
    'un anuncio de producto normal no es riesgo',
    'oportunidad',
    heatmap_senal(bit_falso([
        'titular' => 'Cloudbeds lanza un RMS con datos unificados',
        'cuerpo'  => 'La herramienta se apoya en los datos que ya fluyen por el negocio hotelero.',
    ]))
);

comprobar(
    'una noticia con un problema nombrado si lo es, aunque nadie toque el panel',
    'riesgo',
    heatmap_senal(bit_falso([
        'titular' => 'El proveedor anuncia una subida de precios',
        'cuerpo'  => 'La nueva tarifa entra en vigor en enero.',
    ]))
);

// La puntuacion tambien tiene que salir del texto, que es lo unico que varia
// cuando publica el modo automatico.
comprobar(
    'un anuncio suelto se queda en el suelo de lo publicable',
    3,
    heatmap_puntuar(bit_falso(['cuerpo' => 'La cadena presenta su nueva herramienta.']), 1)
);

comprobar(
    'un problema nombrado obliga a decidir',
    4,
    heatmap_puntuar(bit_falso(['cuerpo' => 'El proveedor anuncia el fin de soporte.']), 1)
);

comprobar(
    'y si ademas corre, es de esta semana',
    5,
    heatmap_puntuar(bit_falso([
        'cuerpo' => 'Hay explotacion activa de la brecha de seguridad detectada.',
    ]), 1)
);

comprobar(
    'que compren a tu proveedor sube aunque no sea ni bueno ni malo',
    4,
    heatmap_puntuar(bit_falso([
        'cuerpo' => 'Guesty completa la adquisicion del PMS frances Smily.',
    ]), 1)
);

comprobar(
    'dos medios distintos contandolo tambien cuentan',
    4,
    heatmap_puntuar(bit_falso(['cuerpo' => 'La cadena presenta su nueva herramienta.']), 2)
);

// --- Alias debiles: los falsos positivos que se vieron en produccion -----------

comprobar(
    'una conectividad aerea no es un channel manager',
    [],
    stack_clasificar(bit_falso([
        'titular' => 'Comodoro Rivadavia potencia atractivos y conectividad aerea',
        'cuerpo'  => 'El destino promociona la naturaleza y una red de vuelos semanales.',
    ]))
);

comprobar(
    'una vulnerabilidad en sentido figurado no es ciberseguridad',
    [],
    stack_clasificar(bit_falso([
        'titular' => 'La dependencia del turismo internacional',
        'cuerpo'  => 'Aparece una vulnerabilidad que muchos destinos tardan en reconocer.',
    ]))
);

comprobar(
    'pero un alias debil acompanado de otro si enciende el nodo',
    'channel-manager',
    stack_clasificar(bit_falso([
        'titular' => 'El channel manager amplia su conectividad con nuevos canales',
    ]))[0]
);

comprobar(
    'y las noticias genericas de IA ya no caen en el nodo de los chatbots',
    '',
    stack_nodo_de_categoria('ia-aplicada')
);

// --- Desempate: a igual puntuacion manda el riesgo -----------------------------

$empate = heatmap_agregar([
    [
        'id' => 'bit-bueno', 'node_ids' => ['pms'], 'signal' => 'oportunidad',
        'score' => 4, 'level' => 'alto', 'published_at' => '2026-09-22', 'encaje' => 'alias',
    ],
    [
        'id' => 'bit-malo', 'node_ids' => ['pms'], 'signal' => 'riesgo',
        'score' => 4, 'level' => 'alto', 'published_at' => '2026-09-21', 'encaje' => 'alias',
    ],
]);

comprobar(
    'con la misma puntuacion, el nodo se pinta del color del problema',
    'riesgo',
    $empate['pms']['signal']
);

comprobar(
    'y no se pierde ninguna de las dos noticias por el camino',
    2,
    count($empate['pms']['brief_ids'])
);

comprobar(
    'el volumen cuenta las que nombran la pieza, no las que caen por tematica',
    1,
    heatmap_agregar([
        [
            'id' => 'bit-1', 'node_ids' => ['pms'], 'signal' => 'oportunidad',
            'score' => 3, 'level' => 'medio', 'published_at' => '2026-09-22', 'encaje' => 'alias',
        ],
        [
            'id' => 'bit-2', 'node_ids' => ['pms'], 'signal' => 'oportunidad',
            'score' => 3, 'level' => 'medio', 'published_at' => '2026-09-22', 'encaje' => 'categoria',
        ],
    ])['pms']['noticias']
);

// --- Senal --------------------------------------------------------------------

comprobar('un incidente es riesgo', 'riesgo', heatmap_senal(bit_falso(['tipo' => 'incidente'])));
comprobar('una norma es riesgo', 'riesgo', heatmap_senal(bit_falso(['tipo' => 'regulacion'])));
comprobar('un producto es oportunidad', 'oportunidad', heatmap_senal(bit_falso(['tipo' => 'producto'])));

comprobar(
    'un producto de un fabricante de seguridad sigue siendo oportunidad',
    'oportunidad',
    heatmap_senal(bit_falso(['tipo' => 'producto', 'categoria' => 'ciberseguridad']))
);

// --- Puntuacion ----------------------------------------------------------------

comprobar(
    'una vulnerabilidad contada por varios medios llega al tope',
    5,
    heatmap_puntuar(bit_falso(['tipo' => 'incidente', 'categoria' => 'ciberseguridad']), 4)
);

comprobar(
    'un producto anunciado, contado por un solo medio, se queda a vigilar',
    3,
    heatmap_puntuar(bit_falso(['tipo' => 'producto']), 1)
);

comprobar(
    'el mismo producto ya disponible obliga a decidir',
    4,
    heatmap_puntuar(bit_falso(['tipo' => 'producto', 'madurez' => 'disponible']), 1)
);

comprobar(
    'un rumor no obliga a nada',
    2,
    heatmap_puntuar(bit_falso(['tipo' => 'producto', 'madurez' => 'rumor']), 1)
);

comprobar(
    'la puntuacion nunca se sale de la escala',
    5,
    heatmap_puntuar(bit_falso(['tipo' => 'incidente', 'categoria' => 'ciberseguridad']), 9)
);

// --- Decay ---------------------------------------------------------------------

comprobar(
    'recien publicado vale lo que vale',
    4,
    heatmap_vigente(4, 'incidente', '2026-09-22', '2026-09-22')
);

comprobar(
    'una brecha pierde un punto por semana',
    3,
    heatmap_vigente(4, 'incidente', '2026-09-15', '2026-09-22')
);

comprobar(
    'una norma aguanta ese mismo plazo entera',
    4,
    heatmap_vigente(4, 'regulacion', '2026-09-15', '2026-09-22')
);

comprobar(
    'pasada la caducidad se cae del mapa aunque la puntuacion diera positivo',
    0,
    heatmap_vigente(5, 'incidente', '2026-08-01', '2026-09-22')
);

comprobar(
    'un bit con fecha de manana se trata como de hoy, no se premia',
    4,
    heatmap_vigente(4, 'incidente', '2026-09-30', '2026-09-22')
);

comprobar('nivel alto a partir de cuatro', 'alto', heatmap_nivel(4));
comprobar('nivel medio en tres', 'medio', heatmap_nivel(3));
comprobar('nivel bajo por debajo', 'bajo', heatmap_nivel(2));

// --- Agregacion: el maximo, nunca la suma ---------------------------------------

$menores = [];

for ($i = 1; $i <= 5; $i++) {
    $menores[] = [
        'id'           => 'bit-' . $i,
        'node_ids'     => ['pms'],
        'signal'       => 'oportunidad',
        'score'        => 3,
        'level'        => 'medio',
        'published_at' => '2026-09-22',
    ];
}

$agregado = heatmap_agregar($menores);

comprobar(
    'cinco noticias de tres no encienden el nodo en rojo',
    3,
    $agregado['pms']['score']
);

comprobar(
    'y el nodo se queda con las cinco, aunque solo mande la mayor',
    5,
    count($agregado['pms']['brief_ids'])
);

// --- Fatiga de alerta: como mucho tres nodos en rojo ----------------------------

$muchos = [];
$rojos  = ['pms', 'rms', 'pagos', 'ciberseguridad', 'integraciones'];

// Los cinco por encima del umbral rojo: si no, no habria nada que recortar.
// Dos de cinco y tres de cuatro, para comprobar de paso que el empate lo
// desempata el orden del catalogo y no el azar.
$puntos = [5, 5, 4, 4, 4];

foreach ($rojos as $i => $nodo) {
    $muchos[] = [
        'id'           => 'bit-r' . $i,
        'node_ids'     => [$nodo],
        'signal'       => 'riesgo',
        'score'        => $puntos[$i],
        'level'        => heatmap_nivel($puntos[$i]),
        'published_at' => '2026-09-22',
    ];
}

$agregado = heatmap_agregar($muchos);
$altos    = [];

foreach ($agregado as $id => $nodo) {
    if ($nodo['level'] === 'alto') {
        $altos[] = $id;
    }
}

comprobar('nunca mas de tres nodos en nivel alto', 3, count($altos));

comprobar(
    'los tres que se quedan son los de mas puntuacion',
    ['pms', 'rms', 'pagos'],
    $altos
);

comprobar(
    'al que baja de nivel no se le toca la puntuacion: se marca, no se miente',
    [4, true],
    [$agregado['ciberseguridad']['score'], $agregado['ciberseguridad']['atenuado'] ?? false]
);

// --- El mapa entero ---------------------------------------------------------------

$bits = [
    bit_falso([
        'id'        => 10,
        'racimo_id' => 10,
        'titular'   => 'Una vulnerabilidad afecta al PMS de media Europa',
        'cuerpo'    => 'El fabricante publica un parche. La vulnerabilidad permite robo de credenciales.',
        'categoria' => 'ciberseguridad',
        'tipo'      => 'incidente',
        'dia'       => '2026-09-22',
    ]),
    bit_falso([
        'id'        => 11,
        'racimo_id' => 11,
        'titular'   => 'Un chatbot nuevo para recepcion',
        'cuerpo'    => 'El asistente virtual contesta en cinco idiomas.',
        'tipo'      => 'producto',
        'madurez'   => 'rumor',
        'dia'       => '2026-09-21',
    ]),
    bit_falso([
        'id'        => 12,
        'racimo_id' => 12,
        'titular'   => 'Cambia el precio de una API de disponibilidad',
        'cuerpo'    => 'El middleware de integracion tendra que reducir llamadas.',
        'tipo'      => 'regulacion',
        'dia'       => '2026-09-20',
    ]),
];

// El corte va despues de los tres bits, asi que los tres entran. Se pasa una
// fecha a mano y no heatmap_semana(): lo que se prueba aqui es el compositor,
// y atarlo al calendario haria que estas comprobaciones cambiaran de resultado
// segun el dia en que se ejecuten.
$mapa = heatmap_componer($bits, [10 => [1, 2, 3], 11 => [1], 12 => [1]], '2026-09-23', 'https://x.test');

comprobar(
    'un rumor de puntuacion dos no llega al mapa',
    2,
    count($mapa['briefs'])
);

comprobar(
    'el brief mas urgente va primero',
    'bit-10',
    $mapa['briefs'][0]['id']
);

comprobar(
    'el nodo principal del incidente queda en rojo',
    'alto',
    $mapa['nodes']['pms']['level']
);

comprobar(
    'el enlace del brief apunta al dia del bit, no a la fuente',
    'https://x.test/d/2026-09-22/#bit-10',
    $mapa['briefs'][0]['url']
);

comprobar(
    'todos los nodos encendidos existen en la taxonomia',
    [],
    array_values(array_diff(array_keys($mapa['nodes']), array_keys(stack_nodos())))
);

comprobar(
    'un mapa con noticias de hoy no se marca como viejo',
    false,
    $mapa['stale']
);

comprobar(
    'el mismo mapa un mes despues se marca como viejo',
    true,
    heatmap_componer($bits, [], '2026-10-22')['stale']
);

// --- El corte semanal ----------------------------------------------------------

comprobar(
    'la semana de un miercoles es su lunes',
    '2026-09-21',
    heatmap_semana('2026-09-23')
);

comprobar(
    'la de un lunes es el mismo lunes',
    '2026-09-21',
    heatmap_semana('2026-09-21')
);

comprobar(
    'y la de un domingo sigue siendo el lunes de esa semana, no el siguiente',
    '2026-09-21',
    heatmap_semana('2026-09-27')
);

comprobar(
    'una fecha ilegible no rompe el mapa: devuelve una semana valida',
    true,
    (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', heatmap_semana('esto no es una fecha'))
);

// Lo publicado dentro de la semana en curso no entra todavia: sale en el rio
// hoy y en el mapa el lunes que viene. Es lo que hace que el mapa sea "el de
// esta semana" y no uno que se mueve cada mañana.
comprobar(
    'una noticia de esta misma semana espera al lunes que viene',
    0,
    count(heatmap_componer($bits, [], '2026-09-16')['briefs'])
);

comprobar(
    'y el mapa dice de que semana es',
    '2026-09-23',
    $mapa['semana']
);

comprobar(
    'sin briefs vigentes, el mapa se queda vacio y lo dice',
    [0, true],
    (static function () use ($bits): array {
        $viejo = heatmap_componer($bits, [], '2027-01-01');

        return [count($viejo['nodes']), $viejo['stale']];
    })()
);

comprobar(
    'los briefs de un nodo se recuperan enteros, no por su id',
    ['bit-10'],
    array_column(heatmap_briefs_de($mapa, 'pms'), 'id')
);

// --- El disparador: las palabras del bit, no otras --------------------------------

comprobar(
    'el disparador corta por frases y no por la mitad de una cifra',
    'El fabricante publica un parche.',
    heatmap_disparador(bit_falso([
        'cuerpo' => 'El fabricante publica un parche. Afecta a 1.200 hoteles en doce paises europeos.',
    ]), 40)
);

comprobar(
    'sin cuerpo, el disparador es el titular y nunca queda vacio',
    'Una noticia',
    heatmap_disparador(bit_falso(['cuerpo' => '']))
);

// --- La geometria del dibujo -------------------------------------------------
//
// Esto es lo que no se ve fallar. Un nodo sin coordenada no da error: se cae
// del dibujo en silencio, y nadie lo nota hasta que alguien pregunta por que
// su area no sale nunca. Una arista a un nodo inexistente tampoco: se salta y
// ya. Por eso estas cuatro comprobaciones valen mas que las del color.

$sin_sitio = array_values(array_diff(array_keys(stack_nodos()), array_keys(grafo_posiciones())));

comprobar('los veinticuatro nodos tienen su sitio en el dibujo', [], $sin_sitio);

$fantasmas = array_values(array_diff(array_keys(grafo_posiciones()), array_keys(stack_nodos())));

comprobar('y no hay coordenadas de nodos que ya no existen', [], $fantasmas);

$rotos = [];
$conectados = [];

foreach (grafo_enlaces() as $i => [$desde, $hasta]) {
    foreach ([$desde, $hasta] as $punta) {
        if (!isset(stack_nodos()[$punta])) {
            $rotos[] = $punta;
        }

        $conectados[$punta] = true;
    }

    if ($desde === $hasta) {
        $rotos[] = 'enlace ' . $i . ' sale y entra en ' . $desde;
    }
}

comprobar('ninguna conexion apunta a un nodo que no existe', [], $rotos);

// Un nodo suelto en un mapa de contagio es un nodo que no contagia a nadie, y
// eso en un hotel no pasa: si sale suelto, es que falta la arista.
$sueltos = array_values(array_diff(array_keys(stack_nodos()), array_keys($conectados)));

comprobar('ningun nodo se queda sin una sola conexion', [], $sueltos);

// Las duplicadas se pintan dos veces exactas, una encima de otra: no se ven,
// pero engordan el trazo justo ahi y descuadran el peso visual del dibujo.
$vistas = [];
$repetidas = [];

foreach (grafo_enlaces() as [$desde, $hasta]) {
    $par = $desde < $hasta ? $desde . '|' . $hasta : $hasta . '|' . $desde;

    if (isset($vistas[$par])) {
        $repetidas[] = $par;
    }

    $vistas[$par] = true;
}

comprobar('no hay conexiones repetidas', [], $repetidas);

// Todo tiene que caber en el lienzo, contando el halo del nodo mas gordo y las
// dos lineas de etiqueta que le cuelgan debajo. Un nodo que se sale se recorta
// sin avisar.
$fuera = [];
$lienzo = grafo_lienzo();
$radio_max = grafo_radio(['score' => 5, 'signal' => 'riesgo', 'level' => 'alto', 'noticias' => 999]);
$halo = (int) round($radio_max * 2.5);

foreach (grafo_posiciones() as $id => $punto) {
    if ($punto['x'] - $halo < 0 || $punto['x'] + $halo > $lienzo['ancho']
        || $punto['y'] - $halo < 0 || $punto['y'] + $radio_max + 32 > $lienzo['alto']) {
        $fuera[] = $id;
    }
}

comprobar('ningun nodo se sale del lienzo, ni con su halo ni con sus etiquetas', [], $fuera);

// Las etiquetas del dibujo no pueden ser largas: el hueco de un nodo son unas
// cien unidades y a partir de ahi se comen al vecino. Veinte caracteres es lo
// que cabe en dos lineas sin invadir nada.
$largas = [];

foreach (array_keys(stack_nodos()) as $id) {
    if (mb_strlen(stack_etiqueta_corta($id), 'UTF-8') > 20) {
        $largas[] = $id;
    }
}

comprobar('ninguna etiqueta del dibujo pasa de veinte caracteres', [], $largas);

comprobar(
    'la etiqueta corta solo sustituye a la larga donde hace falta',
    ['CDP', 'PMS'],
    [stack_etiqueta_corta('cdp-datos'), stack_etiqueta_corta('pms')]
);

comprobar(
    'y la larga se queda intacta para la ficha y el JSON',
    'CDP y dato del huésped',
    stack_etiqueta('cdp-datos')
);

// --- El contagio: las aristas se tinen desde el nodo mas caliente -------------

$mapa_grafo = [
    'nodes' => [
        'pms'   => ['signal' => 'riesgo', 'score' => 5, 'level' => 'alto', 'brief_ids' => ['bit-1']],
        'pagos' => ['signal' => 'oportunidad', 'score' => 3, 'level' => 'medio', 'brief_ids' => ['bit-2']],
    ],
];

$aristas = grafo_aristas($mapa_grafo);

comprobar(
    'hay una arista por conexion del catalogo',
    count(grafo_enlaces()),
    count($aristas)
);

$vivas = array_filter($aristas, static fn(array $a): bool => str_contains($a['clase'], '--viva'));

comprobar(
    'solo se tinen las conexiones que tocan un nodo encendido',
    true,
    count($vivas) > 0 && count($vivas) < count($aristas)
);

// Teñir solo lo que sale de un nodo en rojo. Con nueve encendidos, teñir todo
// lo que toque cualquiera de ellos pinta tres cuartas partes del dibujo, y un
// mapa en el que casi todo es rojo no avisa de nada.
comprobar(
    'las conexiones tenidas son minoria',
    true,
    count($vivas) * 2 < count($aristas)
);

/** La arista de una pareja concreta, en el orden en que esta en el catalogo. */
function arista_de(array $aristas, string $a, string $b): ?array
{
    foreach (grafo_enlaces() as $i => [$desde, $hasta]) {
        if (($desde === $a && $hasta === $b) || ($desde === $b && $hasta === $a)) {
            return $aristas[$i] ?? null;
        }
    }

    return null;
}

// pms-pagos une un rojo de 5 con un verde de 3: gana el rojo. Si ganara el
// otro, una linea que sale de una alarma se pintaria de color tranquilo.
comprobar(
    'entre dos nodos encendidos manda el mas caliente',
    'mapa-arista mapa-arista--viva mapa-arista--riesgo',
    arista_de($aristas, 'pms', 'pagos')['clase'] ?? ''
);

// pagos esta en verde de nivel medio y erp-finanzas apagado: se marca, pero no
// se tine. El color se reserva para lo que pide una decision.
comprobar(
    'un nodo de nivel medio marca sus conexiones sin tenirlas',
    'mapa-arista mapa-arista--tibia',
    arista_de($aristas, 'pagos', 'erp-finanzas')['clase'] ?? ''
);

// Y lo que no toca nada encendido se queda de fondo.
comprobar(
    'una conexion entre dos nodos en calma es una linea de fondo',
    'mapa-arista',
    arista_de($aristas, 'rrhh', 'housekeeping')['clase'] ?? ''
);

comprobar(
    'un nodo apagado es mas pequeno que cualquiera encendido',
    true,
    grafo_radio(null) < grafo_radio(['score' => 3, 'noticias' => 1])
);

comprobar(
    'a igual volumen, el que pide decision es mas gordo',
    true,
    grafo_radio(['score' => 5, 'noticias' => 1]) > grafo_radio(['score' => 4, 'noticias' => 1])
);

// El volumen es la mitad del tamano, y es la que salva el mapa en una semana
// tranquila: si todo empata en puntuacion, esto es lo unico que sigue
// distinguiendo doce noticias de una.
comprobar(
    'a igual puntuacion, doce noticias pesan mas que una',
    true,
    grafo_radio(['score' => 3, 'noticias' => 12]) > grafo_radio(['score' => 3, 'noticias' => 1])
);

// Y esta es la que sujeta el diseno: el volumen manda dentro de su franja,
// nunca fuera. Un monton de notas de prensa no puede verse mas gordo que una
// vulnerabilidad que hay que parchear esta semana.
comprobar(
    'ni un nodo con cien noticias adelanta a uno de mas puntuacion',
    true,
    grafo_radio(['score' => 3, 'noticias' => 100]) < grafo_radio(['score' => 4, 'noticias' => 1])
        && grafo_radio(['score' => 4, 'noticias' => 100]) < grafo_radio(['score' => 5, 'noticias' => 1])
);

comprobar(
    'y nunca pasa del tope que cabe en el lienzo',
    24,
    grafo_radio(['score' => 5, 'noticias' => 9999])
);

/**
 * Cuanto se aparta la curva de la recta entre sus dos extremos.
 *
 * Se mide en vez de comparar la cadena entera contra un literal, que es como
 * estaba y estaba mal: ataba la prueba a la formula, asi que al cambiar la
 * curvatura fallaba sin que nada se hubiera roto. Lo que importa de una curva
 * aqui son tres cosas -de donde sale, adonde llega y cuanto se separa-, y eso
 * es lo que se comprueba.
 */
function desvio_de(array $a, array $b): float
{
    if (!preg_match('/Q ([\d.-]+) ([\d.-]+)/', grafo_curva($a, $b), $trozos)) {
        return -1.0;
    }

    $medio_x = $a['x'] + ($b['x'] - $a['x']) / 2;
    $medio_y = $a['y'] + ($b['y'] - $a['y']) / 2;

    return sqrt(
        (((float) $trozos[1]) - $medio_x) ** 2 + (((float) $trozos[2]) - $medio_y) ** 2
    );
}

// Si el redondeo se comiera un extremo, la linea saldria despegada del nodo.
$curva = grafo_curva(['x' => 100, 'y' => 100], ['x' => 200, 'y' => 200]);

comprobar(
    'la curva sale de un nodo y entra en el otro',
    true,
    str_starts_with($curva, 'M 100 100 Q') && str_ends_with($curva, ' 200 200')
);

// Un tramo corto se curva poco -un catorceavo de su largo- y uno largo deja de
// curvarse mas: sin ese tope, las aristas largas del PMS salian disparadas por
// el centro del dibujo y lo llenaban de arcos.
comprobar_rango(
    'la curvatura de un tramo corto es proporcional a su largo',
    4.9,
    5.1,
    desvio_de(['x' => 0, 'y' => 0], ['x' => 70, 'y' => 0])
);

comprobar_rango(
    'y la de uno largo se queda en el tope',
    19.9,
    20.1,
    desvio_de(['x' => 0, 'y' => 0], ['x' => 1000, 'y' => 0])
);

resumen_pruebas('Mapa del stack');
