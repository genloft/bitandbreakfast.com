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

// --- Minutos de lectura -----------------------------------------------------

comprobar('doscientas palabras son un minuto', 1, web_minutos(200));
comprobar('mil palabras son cinco minutos', 5, web_minutos(1000));
comprobar('lo que sobra redondea hacia arriba', 6, web_minutos(1001));
comprobar('una edicion vacia sigue siendo un minuto', 1, web_minutos(0));

resumen_pruebas('Pruebas de la fase 4: la web generada');
