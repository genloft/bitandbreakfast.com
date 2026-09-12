<?php
/**
 * Pruebas de lib/texto.php.  Ejecutar:  php pruebas/texto.php
 *
 * Los casos son titulares reales de las fuentes de la semilla, no inventados:
 * si la normalizacion falla con un titular de Skift, falla en produccion.
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/texto.php';

// --- Acentos y caracteres tipograficos --------------------------------------

comprobar(
    'quita acentos del espanol',
    'canon espanol accion',
    texto_sin_acentos('cañón español acción')
);

comprobar(
    'convierte las comillas tipograficas de los feeds',
    "hotel's plan",
    texto_sin_acentos('hotel’s plan')
);

// --- Normalizacion ----------------------------------------------------------

comprobar(
    'normaliza minusculas, signos y espacios',
    'oracle lanza opera cloud 2026',
    texto_normalizar('  ORACLE  lanza  OPERA Cloud, 2026!  ')
);

comprobar(
    'elimina el HTML que viene en los titulares',
    'mews compra un rms',
    texto_normalizar('<b>Mews</b> compra un RMS')
);

comprobar(
    'decodifica entidades HTML',
    'marriott hilton',
    texto_normalizar('Marriott &amp; Hilton')
);

// --- Titular normalizado (sin palabras vacias) ------------------------------

comprobar(
    'quita palabras vacias en ingles',
    'marriott taps ai startup automate front desk operations',
    texto_titulo_norm('Marriott taps AI startup to automate the front desk operations')
);

comprobar(
    'quita palabras vacias en espanol',
    'hosteltur analiza mercado pms espana',
    texto_titulo_norm('Hosteltur analiza el mercado de los PMS en España')
);

// --- Shingles ---------------------------------------------------------------

comprobar(
    'un texto mas corto que la ventana es un unico shingle',
    ['ab' => true],
    texto_shingles('ab', 3)
);

comprobar(
    'cuenta correcta de shingles de tres caracteres',
    4,          // "abcdef" -> abc, bcd, cde, def
    count(texto_shingles('abcdef', 3))
);

comprobar(
    'los shingles repetidos no se duplican',
    2,          // "ababab" -> solo aba y bab
    count(texto_shingles('ababab', 3))
);

// --- Similitud --------------------------------------------------------------

comprobar(
    'un titular identico da similitud 1',
    1.0,
    texto_similitud(
        'Marriott taps AI startup to automate front desk',
        'Marriott taps AI startup to automate front desk'
    )
);

comprobar_rango(
    'la misma noticia con redaccion distinta supera el umbral alto',
    0.45, 1.0,
    texto_similitud(
        'Marriott taps AI startup to automate front desk operations',
        'Marriott turns to AI startup to automate front-desk operations'
    )
);

comprobar_rango(
    'dos noticias sin relacion se quedan muy por debajo del umbral bajo',
    0.0, 0.20,
    texto_similitud(
        'Marriott taps AI startup to automate front desk operations',
        'PCI DSS 4.0 entra en vigor para los pagos con tarjeta'
    )
);

comprobar_rango(
    'la misma noticia en ingles y en espanol NO agrupa: limitacion conocida',
    0.0, 0.30,
    texto_similitud(
        'Marriott taps AI startup to automate front desk operations',
        'Marriott ficha a una startup de IA para automatizar la recepcion'
    )
);

comprobar(
    'un titular vacio no rompe la comparacion',
    0.0,
    texto_similitud('', 'Marriott taps AI startup')
);

// --- Tokens clave para el indice invertido ----------------------------------

comprobar(
    'extrae palabras largas y cifras de cuatro digitos, ordenadas',
    ['2026', 'cloud', 'hoteles', 'lanza', 'opera', 'oracle'],
    texto_tokens_clave('Oracle lanza OPERA Cloud en 2026 para hoteles')
);

comprobar(
    'no devuelve palabras cortas ni vacias',
    ['marriott', 'startup'],
    texto_tokens_clave('Marriott and a startup')
);

// --- Limpieza de HTML -------------------------------------------------------

comprobar(
    'la lista blanca conserva el formato basico y tira los atributos',
    '<p>Texto <strong>importante</strong></p>',
    texto_limpiar_html('<p class="x">Texto <strong>importante</strong></p>')
);

comprobar(
    'elimina script y los manejadores de eventos',
    '<p>Hola</p>',
    texto_limpiar_html('<p onclick="robar()">Hola</p><script>malo()</script>')
);

// --- Recorte y conteo -------------------------------------------------------

comprobar(
    'no toca un texto que ya cabe',
    'Corto',
    texto_recortar('Corto', 20)
);

comprobar(
    'recorta por palabra completa y anade puntos suspensivos',
    'Hotel Technology…',
    texto_recortar('Hotel Technology News publica su informe', 20)
);

comprobar(
    'cuenta las palabras del cuerpo de un bit',
    9,
    texto_contar_palabras('  Oracle lanza OPERA Cloud para hoteles independientes en Europa  ')
);

comprobar(
    'un texto vacio tiene cero palabras',
    0,
    texto_contar_palabras('   ')
);

resumen_pruebas('Pruebas de lib/texto.php');
