<?php
/**
 * Pruebas de lib/glosario.php. Ejecutar: php pruebas/glosario.php
 *
 * No tocan la base de datos: son un array escrito a mano, igual que
 * lib/cifras.php y lib/cumplimiento.php.
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/glosario.php';
require_once dirname(__DIR__) . '/lib/bits.php';

$terminos = glosario_terminos();

comprobar('hay al menos veinte terminos', true, count($terminos) >= 20);

$categorias_validas = array_keys(bits_categorias());

foreach ($terminos as $indice => $termino) {
    foreach (['sigla', 'nombre', 'tema', 'definicion'] as $campo) {
        comprobar("el termino $indice trae el campo '$campo'", true, array_key_exists($campo, $termino));
    }

    if ($termino['tema'] !== null) {
        comprobar(
            "el tema de '{$termino['sigla']}' está en bits_categorias()",
            true,
            in_array($termino['tema'], $categorias_validas, true)
        );
    }
}

comprobar(
    'las siglas no se repiten',
    count($terminos),
    count(array_unique(array_column($terminos, 'sigla')))
);

// --- glosario_por_tema() ------------------------------------------------------

comprobar(
    'CRS y PMS aparecen en pms-crs, en el mismo orden alfabetico del glosario',
    ['CRS', 'PMS'],
    array_column(glosario_por_tema('pms-crs'), 'sigla')
);

comprobar('un tema sin ningun termino devuelve una lista vacia', [], glosario_por_tema('sostenibilidad-energia'));

comprobar(
    'un termino con tema null no aparece al pedir la cadena vacia',
    false,
    in_array('API', array_column(glosario_por_tema(''), 'sigla'), true)
);

resumen_pruebas('Pruebas de lib/glosario.php');
