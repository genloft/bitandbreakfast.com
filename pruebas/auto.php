<?php
/**
 * Pruebas de lib/auto.php.  Ejecutar:  php pruebas/auto.php
 *
 * La publicacion automatica levanta la premisa con la que nacio el proyecto
 * -ningun bit sin revision humana-, asi que lo que hace tiene que ser
 * especialmente predecible. Aqui se comprueba que no inventa: que el cuerpo
 * sale del resumen de la fuente, que la categoria sale del catalogo y que el
 * umbral corta donde tiene que cortar.
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/auto.php';

// --- Categoria --------------------------------------------------------------

comprobar(
    'la categoria sale de la fuente mejor puntuada',
    'ciberseguridad-cumplimiento',
    auto_categoria([
        ['categoria_defecto' => 'ciberseguridad-cumplimiento'],
        ['categoria_defecto' => 'pms-gestion'],
    ])
);

// Una categoria que no esta en el catalogo no puede colarse: luego no filtra.
comprobar(
    'una categoria desconocida se ignora y se pasa a la siguiente',
    'pms-gestion',
    auto_categoria([
        ['categoria_defecto' => 'lo-que-sea'],
        ['categoria_defecto' => 'pms-gestion'],
    ])
);

comprobar(
    'sin ninguna valida, la general',
    'tecnologia-general',
    auto_categoria([['categoria_defecto' => 'inventada']])
);

comprobar('sin items, la general', 'tecnologia-general', auto_categoria([]));

// --- Tipo -------------------------------------------------------------------

comprobar('una pagina de estado es un incidente', 'incidente', auto_tipo([['tipo' => 'estado']]));
comprobar('un boletin oficial es regulacion', 'regulacion', auto_tipo([['tipo' => 'normativa']]));
comprobar('una ronda es inversion', 'inversion', auto_tipo([['tipo' => 'financiacion']]));
comprobar('lo demas es producto', 'producto', auto_tipo([['tipo' => 'prensa']]));

comprobar(
    'si la primera fuente no dice nada, se mira la siguiente',
    'incidente',
    auto_tipo([['tipo' => 'prensa'], ['tipo' => 'estado']])
);

// --- Cuerpo -----------------------------------------------------------------

$largo = 'El corte afecto a los hoteles europeos durante cuatro horas, entre las '
       . 'ocho y diez de la manana y las doce y media, y obligo a las recepciones '
       . 'a volver al papel mientras duro la incidencia en toda la region.';

comprobar(
    'el cuerpo es el resumen de la fuente, tal cual',
    $largo,
    auto_cuerpo([['resumen_origen' => $largo, 'fuente' => 'Skift']])
);

// El HTML de los feeds no puede llegar al bit.
comprobar(
    'el resumen llega limpio de etiquetas',
    $largo,
    auto_cuerpo([['resumen_origen' => '<p>' . $largo . '</p>', 'fuente' => 'Skift']])
);

comprobar(
    'un resumen larguisimo se recorta por palabras',
    30,
    texto_contar_palabras(auto_cuerpo(
        [['resumen_origen' => trim(str_repeat('palabra ', 200)), 'fuente' => 'Skift']],
        30
    ))
);

// Sin resumen utilizable no se inventa nada: se dice quien lo cuenta.
comprobar(
    'sin resumen, se enumeran los medios',
    'Lo publican 3 medios: Skift, Hosteltur y PhocusWire. El resumen completo está en las fuentes.',
    auto_cuerpo([
        ['resumen_origen' => '', 'fuente' => 'Skift'],
        ['resumen_origen' => 'corto', 'fuente' => 'Hosteltur'],
        ['resumen_origen' => null, 'fuente' => 'PhocusWire'],
    ])
);

comprobar(
    'con un solo medio, en singular',
    'Lo publica Skift. El resumen completo está en la fuente.',
    auto_cuerpo([['resumen_origen' => '', 'fuente' => 'Skift']])
);

comprobar('sin nada de nada, cuerpo vacio', '', auto_cuerpo([]));

// --- Limpieza del resumen de los feeds --------------------------------------
//
// Los gestores de contenidos enganchan una coletilla al final de cada entrada
// que no es parte de la noticia y encima repite el titular.

comprobar(
    'se quita la coletilla de WordPress',
    'Cloudbeds lanza su RMS.',
    auto_limpiar('Cloudbeds lanza su RMS. The post Cloudbeds Announces Launch appeared first on LODGING Magazine.')
);

comprobar(
    'y la version en espanol',
    'Mews compra un motor de reservas.',
    auto_limpiar('Mews compra un motor de reservas. El artículo Mews compra se publicó primero en Hosteltur.')
);

comprobar(
    'y el "seguir leyendo"',
    'La AEPD multa a una cadena.',
    auto_limpiar('La AEPD multa a una cadena. Continue reading at Skift')
);

// Las entidades llegan sin decodificar en la mitad de los feeds.
comprobar(
    'las entidades se decodifican',
    'Oracle «cayó» durante 4 horas',
    auto_limpiar('Oracle &laquo;cay&oacute;&raquo; durante 4 horas')
);

comprobar(
    'el HTML se va entero',
    'Texto con negrita dentro.',
    auto_limpiar('<p>Texto con <b>negrita</b> dentro.</p>')
);

comprobar(
    'los puntos suspensivos entre corchetes no se quedan',
    'Empieza la noticia y sigue.',
    auto_limpiar('Empieza la noticia [&hellip;] y sigue.')
);

// --- Titulares --------------------------------------------------------------
//
// El titular llega del feed tal cual, y medio internet sigue publicando
// entidades sin decodificar. En la portada se leia "Soneva&#039;s".

comprobar(
    'las entidades del titular se decodifican',
    "Soneva's Neil Gallagher habla de lujo",
    auto_titular('Soneva&#039;s Neil Gallagher habla de lujo')
);

comprobar(
    'el HTML del titular se va',
    'Mews compra Atomize',
    auto_titular('<b>Mews</b> compra Atomize')
);

comprobar(
    'los espacios de sobra se juntan',
    'Cloudbeds lanza su RMS',
    auto_titular("Cloudbeds   lanza\n su RMS  ")
);

// Muchos feeds pegan el nombre del medio al final del titular. Debajo del bit
// ya se dice de donde sale, asi que ahi sobra.
comprobar(
    'el medio pegado al final se quita',
    'Oracle cae cuatro horas',
    auto_titular('Oracle cae cuatro horas | Skift', ['Skift'])
);

comprobar(
    'y con guion largo tambien',
    'La AEPD multa a una cadena',
    auto_titular('La AEPD multa a una cadena — Hosteltur', ['Hosteltur'])
);

// Si el titular es solo el nombre del medio, quitarlo dejaria el bit sin
// titular: se prefiere un titular pobre a ninguno.
comprobar(
    'nunca se deja el titular vacio',
    'Skift',
    auto_titular('Skift', ['Skift'])
);

// --- Recopilatorios ---------------------------------------------------------
//
// Hay boletines que publican cinco noticias en una sola entrada del feed. Eso
// no es un bit: es un indice, y el enlace no lleva a ninguna noticia.

comprobar(
    'cinco noticias en un titular no son una noticia',
    true,
    auto_es_recopilatorio(
        "Soneva's Neil Gallagher on Bare Luxury and What Stays When the SOPs Go, "
        . 'dormakaba Acquires Alliants, EU AI Act Is Now in Force'
    )
);

// Y las tres condiciones tienen que cumplirse a la vez, porque cualquiera de
// ellas sola se lleva por delante titulares normales.
comprobar(
    'un titular largo con incisos no lo es',
    false,
    auto_es_recopilatorio('Mews compra Atomize, el RMS sueco, por una cifra no revelada')
);

comprobar(
    'una enumeracion de nombres tampoco',
    false,
    auto_es_recopilatorio(
        'Oracle, Mews y Cloudbeds firman un acuerdo para integrar pagos en el motor de reservas'
    )
);

comprobar(
    'ni un titular largo sin comas',
    false,
    auto_es_recopilatorio(
        'La Agencia Espanola de Proteccion de Datos multa a una cadena hotelera por el registro de viajeros'
    )
);

// --- Categoria segun el diccionario -----------------------------------------

$diccionario = [
    ['termino' => 'pms',        'peso' => 6, 'categoria' => 'pms-gestion'],
    ['termino' => 'ransomware', 'peso' => 9, 'categoria' => 'ciberseguridad-cumplimiento'],
    ['termino' => 'ronda',      'peso' => 5, 'categoria' => 'inversion-mercado'],
    ['termino' => 'revolucion', 'peso' => -5, 'categoria' => ''],
];

comprobar(
    'gana el termino de mas peso que este presente',
    'ciberseguridad-cumplimiento',
    auto_categoria_diccionario('Ransomware en el PMS de una cadena', $diccionario)
);

comprobar(
    'con un solo termino, el suyo',
    'inversion-mercado',
    auto_categoria_diccionario('Cierra una ronda de doce millones', $diccionario)
);

// Si el diccionario no reconoce nada, que lo diga: quien llama decide.
comprobar(
    'sin nada reconocible, cadena vacia',
    '',
    auto_categoria_diccionario('Entrevista con el director del hotel', $diccionario)
);

comprobar(
    'un termino sin categoria no clasifica',
    '',
    auto_categoria_diccionario('La revolucion del sector', $diccionario)
);

// --- Medios y enumeracion ---------------------------------------------------

comprobar(
    'los medios no se repiten',
    ['Skift', 'Hosteltur'],
    auto_medios([
        ['fuente' => 'Skift'],
        ['fuente' => 'Hosteltur'],
        ['fuente' => 'Skift'],
    ])
);

comprobar('dos nombres se unen con y', 'Skift y Hosteltur', auto_enumerar(['Skift', 'Hosteltur']));
comprobar('tres, con comas y una y', 'A, B y C', auto_enumerar(['A', 'B', 'C']));
comprobar('uno solo, tal cual', 'Skift', auto_enumerar(['Skift']));

// --- Que racimos entran -----------------------------------------------------

$candidatos = [
    ['id' => 1, 'puntuacion' => 80],
    ['id' => 2, 'puntuacion' => 55],
    ['id' => 3, 'puntuacion' => 31],
    ['id' => 4, 'puntuacion' => 29],
    ['id' => 5, 'puntuacion' => 10],
];

comprobar(
    'entran los que llegan al umbral, hasta llenar los huecos',
    [1, 2, 3],
    array_column(auto_elegir($candidatos, 30, 10), 'id')
);

comprobar(
    'nunca mas de los huecos que quedan',
    [1, 2],
    array_column(auto_elegir($candidatos, 30, 2), 'id')
);

comprobar(
    'con la edicion llena no entra ninguno',
    [],
    auto_elegir($candidatos, 30, 0)
);

comprobar(
    'un umbral alto deja la edicion vacia',
    [],
    auto_elegir($candidatos, 90, 10)
);

// --- Cuando se cierra la edicion --------------------------------------------

comprobar(
    'una edicion llena se cierra',
    true,
    auto_toca_cerrar(20, 20, '2026-12-31', '2026-09-16')
);

comprobar(
    'una edicion a medias, pero con la fecha cumplida, tambien',
    true,
    auto_toca_cerrar(6, 20, '2026-09-16', '2026-09-16')
);

comprobar(
    'una edicion a medias y sin fecha, no',
    false,
    auto_toca_cerrar(6, 20, '2026-12-31', '2026-09-16')
);

// Una edicion sin bits es una pagina sin nada que ensenar.
comprobar(
    'una edicion vacia no se cierra aunque toque la fecha',
    false,
    auto_toca_cerrar(0, 20, '2026-09-01', '2026-09-16')
);

// Sin ninguna edicion publicada, la portada esta vacia: en cuanto hay con que
// llenarla, se cierra sin esperar al martes.
comprobar(
    'sin nada publicado, se cierra en cuanto hay suficientes bits',
    true,
    auto_toca_cerrar(6, 20, '2026-12-31', '2026-09-16', false, 6)
);

comprobar(
    'pero no con cuatro bits: eso no es una portada',
    false,
    auto_toca_cerrar(4, 20, '2026-12-31', '2026-09-16', false, 6)
);

// Con una edicion ya publicada no hay prisa: manda el calendario.
comprobar(
    'con el sitio ya lleno, se espera a la fecha',
    false,
    auto_toca_cerrar(6, 20, '2026-12-31', '2026-09-16', true, 6)
);

comprobar(
    'y vacia no se cierra ni aunque no haya nada publicado',
    false,
    auto_toca_cerrar(0, 20, '2026-12-31', '2026-09-16', false, 6)
);

resumen_pruebas('Pruebas de la publicacion automatica');
