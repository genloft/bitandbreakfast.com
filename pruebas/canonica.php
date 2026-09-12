<?php
/**
 * Pruebas de lib/canonica.php.  Ejecutar:  php pruebas/canonica.php
 *
 * Casos tomados de URLs reales de los feeds de la semilla. Si esto falla,
 * la misma noticia entra dos veces en la base de datos.
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/canonica.php';

// --- Parametros de seguimiento ----------------------------------------------

comprobar(
    'elimina los utm_ y la barra final',
    'https://skift.com/2026/01/02/articulo',
    canonica_normalizar('https://www.Skift.com/2026/01/02/articulo/?utm_source=rss&utm_medium=feed')
);

comprobar(
    'elimina fbclid, gclid y ref',
    'https://hoteldive.com/news/algo',
    canonica_normalizar('https://www.hoteldive.com/news/algo?fbclid=abc&gclid=def&ref=twitter')
);

comprobar(
    'conserva los parametros que si identifican el recurso',
    'https://example.com/noticia?id=7',
    canonica_normalizar('https://example.com/noticia?id=7&utm_campaign=enero#seccion')
);

comprobar(
    'ordena los parametros para que el hash no dependa del orden',
    'https://example.com/?a=1&b=2',
    canonica_normalizar('https://example.com?b=2&a=1')
);

// --- Host, esquema y puerto -------------------------------------------------

comprobar(
    'fuerza https y quita www',
    'https://example.com/a/b',
    canonica_normalizar('http://www.example.com/a/b/')
);

comprobar(
    'quita el puerto por defecto',
    'https://example.com/x',
    canonica_normalizar('https://example.com:443/x')
);

comprobar(
    'conserva un puerto no estandar',
    'https://example.com:8080/x',
    canonica_normalizar('https://example.com:8080/x')
);

comprobar(
    'la raiz conserva su barra',
    'https://example.com/',
    canonica_normalizar('https://example.com/')
);

// --- Variantes AMP ----------------------------------------------------------

comprobar(
    'resuelve el sufijo /amp',
    'https://example.com/noticia',
    canonica_normalizar('https://example.com/noticia/amp')
);

comprobar(
    'resuelve el prefijo /amp/',
    'https://example.com/noticia',
    canonica_normalizar('https://example.com/amp/noticia')
);

comprobar(
    'resuelve el parametro ?amp=1',
    'https://example.com/noticia',
    canonica_normalizar('https://example.com/noticia?amp=1')
);

// --- URLs inservibles -------------------------------------------------------

comprobar('descarta un esquema que no es http', '', canonica_normalizar('ftp://example.com/x'));
comprobar('descarta una cadena vacia',            '', canonica_normalizar(''));
comprobar('descarta texto que no es una URL',     '', canonica_normalizar('no soy una url'));

// --- Hash -------------------------------------------------------------------

comprobar(
    'el hash tiene 40 caracteres',
    40,
    strlen(canonica_hash(canonica_normalizar('https://example.com/x')))
);

comprobar(
    'dos variantes de la misma URL dan el mismo hash',
    canonica_hash(canonica_normalizar('https://www.example.com/noticia/?utm_source=rss')),
    canonica_hash(canonica_normalizar('http://example.com/noticia'))
);

// --- Resolucion de URLs relativas -------------------------------------------

comprobar(
    'resuelve una ruta absoluta contra el dominio del feed',
    'https://example.com/foo',
    canonica_absoluta('/foo', 'https://example.com/feed/rss.xml')
);

comprobar(
    'resuelve una ruta relativa contra el directorio del feed',
    'https://example.com/feed/bar.html',
    canonica_absoluta('bar.html', 'https://example.com/feed/rss.xml')
);

comprobar(
    'no toca una URL que ya es absoluta',
    'https://otro.com/x',
    canonica_absoluta('https://otro.com/x', 'https://example.com/feed/rss.xml')
);

resumen_pruebas('Pruebas de lib/canonica.php');
