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
    'cada edicion vive en su carpeta con un index dentro',
    'e/2026-w39-038/index.html',
    web_ruta_edicion('2026-w39-038')
);

comprobar(
    'la direccion publica de una edicion no lleva extension',
    'https://bitandbreakfast.com/e/2026-w39-038/',
    web_url_edicion('https://bitandbreakfast.com', '2026-w39-038')
);

comprobar(
    'la barra final de la base no se duplica',
    'https://bitandbreakfast.com/e/2026-w39-038/',
    web_url_edicion('https://bitandbreakfast.com/', '2026-w39-038')
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

// --- Fichas de proveedor ----------------------------------------------------

comprobar(
    'cada proveedor tiene su carpeta con un index dentro',
    'p/mews/index.html',
    web_ruta_proveedor('mews')
);

comprobar(
    'y su direccion sin extension',
    'https://bitandbreakfast.com/p/oracle-hospitality/',
    web_url_proveedor('https://bitandbreakfast.com', 'oracle-hospitality')
);

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
    'categoria'      => 'pms-gestion',
    'numero'         => 38,
    'slug'           => '2026-w39-038',
    'fecha_prevista' => '2026-09-22',
    'proveedores'    => 'oracle-hospitality|Oracle Hospitality',
    'fuente'         => 'Skift',
    'ambito'         => 'global',
    'idioma'         => 'en',
]);

// Las claves son de una letra porque el indice se descarga entero: con mil
// bits, los nombres largos repetidos son decenas de kilobytes.
comprobar('la fila lleva el identificador del bit', 7, $fila['i']);
comprobar('y el titular sin tocar', 'Oracle OPERA Cloud se cae durante cuatro horas', $fila['t']);
comprobar('y la edicion en la que salio', 38, $fila['n']);
comprobar('y los proveedores en texto plano', 'Oracle Hospitality', $fila['v']);
comprobar('y la fecha ya escrita en espanol', '22 de septiembre de 2026', $fila['d']);

// Las tres facetas que se pueden filtrar, ademas de la categoria.
comprobar('y la fuente', 'Skift', $fila['fu']);
comprobar('y el ambito', 'global', $fila['a']);
comprobar('y el idioma', 'en', $fila['l']);

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

resumen_pruebas('Pruebas de la fase 4: la web generada');
