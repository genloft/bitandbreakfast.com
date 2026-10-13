<?php
/**
 * Pruebas de lib/web.php.  Ejecutar:  php pruebas/web.php
 *
 * Lo que se prueba aqui es lo que el generador no puede equivocarse sin que
 * se note: las fechas que ve el lector, las rutas de los ficheros que escribe
 * y el escapado del texto que llega del panel.
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/web.php';

// --- Fechas -----------------------------------------------------------------

comprobar(
    'la fecha larga sale en espanol',
    '22 de septiembre de 2026',
    web_fecha_larga('2026-09-22')
);

// La fecha se interpreta en UTC pase lo que pase: si se leyera en la zona de
// PHP, en Madrid una fecha a medianoche caeria en el dia anterior.
comprobar(
    'la fecha no se desplaza un dia por la zona horaria',
    '1 de enero de 2026',
    web_fecha_larga('2026-01-01')
);

comprobar(
    'una fecha ilegible no rompe la pagina',
    '',
    web_fecha_larga('esto no es una fecha')
);

comprobar(
    'la fecha del RSS va en el formato que exige RSS 2.0',
    'Tue, 22 Sep 2026 00:00:00 +0000',
    web_fecha_rss('2026-09-22')
);

// --- Rutas ------------------------------------------------------------------

comprobar(
    'la direccion publica de un dia no lleva extension',
    'https://bitandbreakfast.com/d/2026-09-17/',
    web_url_dia('https://bitandbreakfast.com', '2026-09-17')
);

comprobar(
    'la barra final de la base no se duplica',
    'https://bitandbreakfast.com/d/2026-09-17/',
    web_url_dia('https://bitandbreakfast.com/', '2026-09-17')
);

// La direccion de un dia es su fecha, y de ahi sale un mkdir: nada de lo que
// llegue puede subir de directorio.
comprobar(
    'cada dia tiene su carpeta con un index dentro',
    'd/2026-09-17/index.html',
    web_ruta_dia('2026-09-17')
);

comprobar(
    'una fecha con hora se queda en el dia',
    'd/2026-09-17/index.html',
    web_ruta_dia('2026-09-17 18:42:00')
);

// Hoy y ayer antes que la fecha: es lo que contesta la pregunta de quien
// entra, que es si esto es de ahora o de la semana pasada.
comprobar('el dia de hoy se llama Hoy', 'Hoy', web_dia_titulo('2026-09-17', '2026-09-17'));
comprobar('el anterior, Ayer', 'Ayer', web_dia_titulo('2026-09-16', '2026-09-17'));
comprobar(
    'y a partir de ahi, la fecha',
    '15 de septiembre de 2026',
    web_dia_titulo('2026-09-15', '2026-09-17')
);

comprobar(
    'el mes encabeza el archivo con mayuscula',
    'Septiembre de 2026',
    web_mes_largo('2026-09-01')
);

// De un slug sale un mkdir, asi que no puede llevar nada que suba de
// directorio ni caracteres que el sistema de ficheros interprete.
comprobar(
    'un slug con puntos y barras se queda en nada peligroso',
    'etc-passwd',
    web_slug_seguro('../../etc/passwd')
);

comprobar(
    'un slug vacio no deja la ruta sin nombre',
    'edicion',
    web_slug_seguro('///')
);

comprobar(
    'un slug normal no se toca',
    '2026-w39-038',
    web_slug_seguro('2026-w39-038')
);

// --- Fichas de tema y de medio ----------------------------------------------

comprobar(
    'cada tema tiene su carpeta con un index dentro',
    't/pms-crs/index.html',
    web_ruta_tema('pms-crs')
);

comprobar(
    'y cada medio su direccion sin extension',
    'https://bitandbreakfast.com/m/hosteltur/',
    web_url_medio('https://bitandbreakfast.com', 'hosteltur')
);

// El slug de un medio sale de su nombre: no hay columna donde guardarlo.
comprobar('el nombre de un medio da un slug legible', 'smart-travel-news', web_slug_medio('Smart Travel News'));
comprobar('y los acentos no dejan agujeros', 'nexotur', web_slug_medio('Nexotur'));

// La lista viene empaquetada en una sola columna para no hacer una consulta
// por bit.
comprobar(
    'deshace la lista empaquetada de proveedores',
    [['slug' => 'mews', 'nombre' => 'Mews'], ['slug' => 'duetto', 'nombre' => 'Duetto']],
    web_proveedores('mews|Mews;;duetto|Duetto')
);

comprobar(
    'un bit sin proveedores no da lista',
    [],
    web_proveedores(null)
);

comprobar(
    'un trozo mal formado se descarta sin romper',
    [['slug' => 'mews', 'nombre' => 'Mews']],
    web_proveedores('mews|Mews;;basura;;|Sin slug')
);

// Un nombre con barra vertical no puede partir la fila en tres.
comprobar(
    'el nombre puede llevar la barra que separa',
    [['slug' => 'ab', 'nombre' => 'A|B']],
    web_proveedores('ab|A|B')
);

// --- Texto ------------------------------------------------------------------

comprobar(
    'el escapado cierra las comillas y los signos de etiqueta',
    '&lt;script&gt;alert(&#039;x&#039;)&lt;/script&gt;',
    web_e('<script>alert(\'x\')</script>')
);

// El cuerpo del bit se escribe en un textarea, sin HTML: aqui se convierte en
// parrafos y nada mas. Si algo escapara sin escapar, este caso lo caza.
comprobar(
    'una linea en blanco separa parrafos',
    "<p>Primero.</p>\n<p>Segundo.</p>\n",
    web_parrafos("Primero.\n\nSegundo.")
);

// nl2br inserta la etiqueta ANTES del salto y conserva el salto: el HTML
// queda igual de valido y el fichero generado sigue siendo legible a mano.
comprobar(
    'un salto suelto se queda dentro del parrafo',
    "<p>Primero.<br>\nSegundo.</p>\n",
    web_parrafos("Primero.\nSegundo.")
);

comprobar(
    'el HTML que venga del panel sale escapado, no interpretado',
    "<p>&lt;b&gt;no&lt;/b&gt;</p>\n",
    web_parrafos('<b>no</b>')
);

comprobar(
    'un cuerpo vacio no deja un parrafo vacio',
    '',
    web_parrafos("   \n\n  ")
);

// --- Indice de busqueda -----------------------------------------------------

$fila = web_fila_indice([
    'id'             => 7,
    'titular'        => 'Oracle OPERA Cloud se cae durante cuatro horas',
    'por_que'        => 'Si tu PMS es OPERA Cloud, el jueves volviste al papel.',
    'cuerpo'         => 'El corte afectó a media Europa.',
    'categoria'      => 'pms-crs',
    'dia'            => '2026-09-22',
    'proveedores'    => 'oracle-hospitality|Oracle Hospitality',
    'fuente'         => 'Skift',
    'ambito'         => 'global',
    'idioma'         => 'en',
]);

// Las claves son de una letra porque el indice se descarga entero: con mil
// bits, los nombres largos repetidos son decenas de kilobytes.
comprobar('la fila lleva el identificador del bit', 7, $fila['i']);
comprobar('y el titular sin tocar', 'Oracle OPERA Cloud se cae durante cuatro horas', $fila['t']);
comprobar('y el dia en que se descubrio', '2026-09-22', $fila['w']);
comprobar('y los proveedores en texto plano', 'Oracle Hospitality', $fila['v']);
comprobar('y tambien en una lista, para poder filtrar por uno', ['Oracle Hospitality'], $fila['pv']);
comprobar('y la fecha ya escrita en espanol', '22 de septiembre de 2026', $fila['d']);

// Las demas facetas que se pueden filtrar, ademas de la categoria.
comprobar('y la fuente', 'Skift', $fila['fu']);
comprobar('y el ambito', 'global', $fila['a']);
comprobar('y el idioma', 'en', $fila['l']);

comprobar(
    'sin proveedores, la lista para filtrar sale vacia, no ausente',
    [],
    web_fila_indice([
        'id' => 8, 'titular' => 't', 'categoria' => 'pms-crs', 'dia' => '2026-09-22',
    ])['pv']
);

// La fuente tambien se puede buscar por texto: "skift" tiene que encontrar
// sus noticias.
comprobar(
    'el texto buscable incluye la fuente',
    true,
    str_contains($fila['b'], 'skift')
);

// El campo buscable viene ya normalizado desde PHP para que el navegador solo
// tenga que normalizar lo que escribe el lector, con las mismas reglas.
comprobar(
    'el texto buscable va normalizado',
    true,
    str_contains($fila['b'], 'oracle opera cloud se cae durante cuatro horas')
);

comprobar(
    'el texto buscable incluye el por que importa',
    true,
    str_contains($fila['b'], 'volviste al papel')
);

comprobar(
    'y el cuerpo, sin acentos',
    true,
    str_contains($fila['b'], 'afecto a media europa')
);

comprobar(
    'y el nombre del proveedor',
    true,
    str_contains($fila['b'], 'oracle hospitality')
);

// --- Enlaces contados -------------------------------------------------------

comprobar(
    'la firma del clic es estable para el mismo bit y secreto',
    web_firma_clic(42, 'secreto'),
    web_firma_clic(42, 'secreto')
);

comprobar(
    'cambiar de bit cambia la firma',
    false,
    web_firma_clic(42, 'secreto') === web_firma_clic(43, 'secreto')
);

// Si el secreto cambia, las firmas viejas dejan de valer. Es lo que se quiere:
// los enlaces de correos ya enviados caducan con el secreto.
comprobar(
    'cambiar el secreto invalida las firmas',
    false,
    web_firma_clic(42, 'secreto') === web_firma_clic(42, 'otro')
);

comprobar('la firma son 16 caracteres', 16, strlen(web_firma_clic(42, 'secreto')));

comprobar(
    'el enlace contado apunta al endpoint con el bit y la firma',
    'https://bitandbreakfast.com/api/ir.php?b=42&t=' . web_firma_clic(42, 'secreto'),
    web_url_clic('https://bitandbreakfast.com', 42, 'secreto', 'https://skift.com/noticia')
);

// Mejor un enlace que no cuenta que un enlace que no lleva a ningun sitio.
comprobar(
    'sin secreto, el enlace va directo a la fuente',
    'https://skift.com/noticia',
    web_url_clic('https://bitandbreakfast.com', 42, '', 'https://skift.com/noticia')
);

// --- Minutos de lectura -----------------------------------------------------

comprobar('doscientas palabras son un minuto', 1, web_minutos(200));
comprobar('mil palabras son cinco minutos', 5, web_minutos(1000));
comprobar('lo que sobra redondea hacia arriba', 6, web_minutos(1001));
comprobar('una edicion vacia sigue siendo un minuto', 1, web_minutos(0));

// --- Categorias viejas ------------------------------------------------------
//
// El catalogo cambio para hablar el mismo idioma que las fuentes y el
// diccionario. Los bits escritos antes siguen en la base con el nombre de
// entonces, y tienen que seguir teniendo nombre y filtro.

comprobar('una categoria del catalogo se queda igual', 'revenue-rms', bits_categoria_canonica('revenue-rms'));
comprobar('el nombre viejo lleva al nuevo', 'pms-crs', bits_categoria_canonica('pms-gestion'));
comprobar('y el de distribucion tambien', 'distribucion-otas', bits_categoria_canonica('distribucion-revenue'));
comprobar('el combinado de ciberseguridad y cumplimiento cae en ciberseguridad', 'ciberseguridad', bits_categoria_canonica('ciberseguridad-cumplimiento'));
comprobar('lo que no se reconoce, cadena vacia', '', bits_categoria_canonica('lo-que-sea'));

// El indice del buscador guarda la categoria buena: si guardase la vieja, el
// filtro de la web -que se pinta con el catalogo- no encontraria esos bits.
comprobar('el indice guarda la categoria del catalogo', 'pms-crs', $fila['c']);

// --- Lo que sobra en publico/ -----------------------------------------------
//
// Una edicion retirada deja su carpeta escrita. Si no se barre, la noticia
// sigue servida en su direccion de siempre y retirarla no ha servido de nada.

comprobar(
    'sobra lo que ya no esta publicado',
    ['1-vieja'],
    web_sobran(['1-vieja', '2-actual'], ['2-actual'])
);

comprobar(
    'con todo publicado, no sobra nada',
    [],
    web_sobran(['2-actual'], ['2-actual', '3-siguiente'])
);

// Los slugs vivos se normalizan igual que al escribirlos; si no, la carpeta
// de una edicion viva se tomaria por huerfana y se borraria la buena.
comprobar(
    'los slugs se comparan ya normalizados',
    [],
    web_sobran(['2-edicion-de-prueba'], ['2-Edicion De Prueba'])
);

comprobar(
    'los puntos de scandir no cuentan',
    ['1-vieja'],
    web_sobran(['.', '..', '1-vieja'], [])
);

// --- Que ninguna plantilla se olvide de abrir PHP ---------------------------
//
// Un fichero sin <?php al principio es PHP valido: todo lo que hay dentro es
// texto y se imprime tal cual. php -l no dice nada, las pruebas tampoco, y lo
// que se ve en produccion es el comentario de cabecera escrito encima de la
// portada. Paso exactamente eso, asi que aqui queda la guardia.

$sin_abrir = [];

foreach (glob(dirname(__DIR__) . '/plantillas/**/*.php') ?: [] as $plantilla) {
    if (!str_starts_with((string) file_get_contents($plantilla), '<?php')) {
        $sin_abrir[] = basename(dirname($plantilla)) . '/' . basename($plantilla);
    }
}

comprobar('todas las plantillas abren PHP en la primera linea', [], $sin_abrir);

// --- Que el mapa lleve su guion alla donde se pinte ---------------------------
//
// Esta prueba nace de un fallo publicado: la banda del mapa estaba en la
// portada y mapa.js solo en /mapa.html. La portada salia perfecta -el dibujo
// se pinta entero desde PHP- pero muerta: no se arrastraba, no ampliaba, no
// enseñaba el "por que" al pasar por encima y no se plegaba a los cinco
// segundos. Y no se veia probando /mapa.html, que era justo la pagina que si
// lo tenia.
//
// Una pagina que pinta el mapa y no carga su guion es siempre un error, asi
// que se comprueba aqui y no se confia en acordarse.
$sin_guion = [];

foreach (glob(dirname(__DIR__) . '/plantillas/web/*.php') ?: [] as $plantilla) {
    $codigo = (string) file_get_contents($plantilla);

    // Solo las paginas completas: los trozos que se incrustan no cargan guiones
    // -un <script> en mitad del <main> correria antes de que exista la pagina-.
    if (!str_contains($codigo, '<!doctype')) {
        continue;
    }

    $pinta_mapa = str_contains($codigo, "/mapa_banda.php")
        || str_contains($codigo, "/mapa_grafo.php");

    if ($pinta_mapa && !str_contains($codigo, '/mapa.js')) {
        $sin_guion[] = basename($plantilla);
    }
}

comprobar('toda pagina que pinta el mapa carga mapa.js', [], $sin_guion);

// --- La hora del panel ------------------------------------------------------
//
// El panel de la cabecera dice cuando sera la siguiente actualizacion, y para
// eso basta la hora: o es dentro de un rato o el cron esta parado, y las dos
// cosas se ven igual de bien sin la fecha.

comprobar('la hora sale en la zona del lector', '01:05', web_hora('2026-09-17 23:05:00'));
comprobar('y una hora invalida no rompe nada', '', web_hora('esto no es una fecha'));

// --- SEO y GEO: datos estructurados -------------------------------------------
//
// Lo que se prueba aqui es lo que un buscador -o un modelo que resume la
// pagina- no perdona: JSON valido, un titular que no pueda cerrar el bloque,
// y que todas las paginas pasen por la misma cabecera en vez de volver a
// copiarla y divergir, que es justo lo que paso antes de lib/seo.php.

$bloque = seo_json([['@type' => 'WebPage', 'name' => 'Titular </script><b>x</b> & "comillas"']]);

comprobar('el JSON-LD va en un bloque de datos', true, str_starts_with($bloque, '<script type="application/ld+json">'));
comprobar(
    'un titular con </script> no puede cerrar el bloque',
    1,
    substr_count($bloque, '</script>')
);

$json_interior = substr($bloque, strlen('<script type="application/ld+json">'), -strlen('</script>'));
$decodificado  = json_decode($json_interior, true);

comprobar('el JSON-LD es JSON valido', true, is_array($decodificado));
comprobar('lleva el contexto de schema.org', 'https://schema.org', $decodificado['@context'] ?? null);
comprobar(
    'y el texto escapado vuelve intacto al leerlo',
    'Titular </script><b>x</b> & "comillas"',
    $decodificado['@graph'][0]['name'] ?? null
);

$org = seo_organizacion('https://ejemplo.test/');
comprobar('la organizacion tiene un @id estable', 'https://ejemplo.test/#organizacion', $org['@id']);
comprobar('y la barra final de la base no se duplica', 'https://ejemplo.test/', $org['url']);

$sitio = seo_sitio('https://ejemplo.test');
comprobar('el sitio declara quien lo publica', ['@id' => 'https://ejemplo.test/#organizacion'], $sitio['publisher']);
comprobar(
    'y su buscador apunta a buscar.html',
    'https://ejemplo.test/buscar.html?q={search_term_string}',
    $sitio['potentialAction']['target']['urlTemplate']
);

$migas_seo = seo_migas([
    ['nombre' => 'Portada', 'url' => 'https://ejemplo.test/'],
    ['nombre' => 'Temas', 'url' => 'https://ejemplo.test/temas.html'],
]);
comprobar('las migas se numeran desde 1', [1, 2], array_column($migas_seo['itemListElement'], 'position'));
comprobar('y cada miga lleva su URL', 'https://ejemplo.test/temas.html', $migas_seo['itemListElement'][1]['item']);

$pagina_seo = seo_pagina([
    'url'         => 'https://ejemplo.test/a.html',
    'nombre'      => 'A',
    'descripcion' => 'Una pagina',
    'modificado'  => '2026-10-01',
    'extra'       => ['about' => 'algo'],
], 'https://ejemplo.test');

comprobar('la pagina cuelga del sitio', ['@id' => 'https://ejemplo.test/#sitio'], $pagina_seo['isPartOf']);
comprobar('y lleva su fecha de revision', '2026-10-01', $pagina_seo['dateModified']);
comprobar('y sin fecha de publicacion no se inventa una', false, isset($pagina_seo['datePublished']));
comprobar('los campos extra se mezclan', 'algo', $pagina_seo['about']);

$lista_seo = seo_lista([['nombre' => 'Uno', 'url' => 'https://ejemplo.test/1'], ['nombre' => 'Dos', 'url' => 'https://ejemplo.test/2']], 'Cosas');
comprobar('la lista cuenta sus elementos', 2, $lista_seo['numberOfItems']);
comprobar('y lleva nombre cuando se le da', 'Cosas', $lista_seo['name']);

$glosario_seo = seo_glosario(
    [['sigla' => 'PMS', 'nombre' => 'Property Management System', 'definicion' => 'El sistema del hotel.']],
    'https://ejemplo.test/glosario.html'
);
comprobar('cada sigla del glosario tiene el ancla de su pagina', 'https://ejemplo.test/glosario.html#pms', $glosario_seo['hasDefinedTerm'][0]['@id']);

comprobar(
    'una frase se corta en el primer punto',
    'El PMS lleva las reservas.',
    seo_primera_frase('El PMS lleva las reservas. Y el CRS las centraliza.')
);
comprobar(
    'y las siglas con punto no la cortan',
    'Verifactu y SES.Hospedajes son obligatorios.',
    seo_primera_frase('Verifactu y SES.Hospedajes son obligatorios. Hay plazos.')
);
comprobar('un texto vacio da una descripcion vacia', '', seo_primera_frase('   '));

comprobar(
    'los rastreadores de IA principales estan en la lista',
    [],
    array_diff(['GPTBot', 'ClaudeBot', 'PerplexityBot', 'OAI-SearchBot', 'Google-Extended'], array_keys(seo_rastreadores_ia()))
);

// Toda pagina completa pinta su <head> con seo.php. La unica excepcion es la
// provisional -la que sale antes de que haya nada publicado, y que no
// necesita datos estructurados de un sitio todavia vacio-.
$sin_seo = [];

foreach (glob(dirname(__DIR__) . '/plantillas/web/*.php') ?: [] as $plantilla) {
    $codigo = (string) file_get_contents($plantilla);

    if (!str_contains($codigo, '<!doctype') || basename($plantilla) === 'provisional.php') {
        continue;
    }

    if (!str_contains($codigo, "/seo.php'") || !str_contains($codigo, '$seo = [')) {
        $sin_seo[] = basename($plantilla);
    }
}

comprobar('toda pagina completa declara $seo y pinta su cabecera con seo.php', [], $sin_seo);

resumen_pruebas('Pruebas de la fase 4: la web generada');
