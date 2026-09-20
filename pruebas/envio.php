<?php
/**
 * Pruebas de lib/envio.php. Ejecutar: php pruebas/envio.php
 *
 * Solo envio_bits_para_tema(): es la unica funcion nueva de §3.1 de
 * docs/MEJORAS.md, y la unica pieza de este fichero que no tenia pruebas
 * hasta ahora. El resto -envio_asunto(), envio_texto(), envio_html()- se
 * queda fuera de esta prueba: no ha cambiado y no es parte de este trabajo.
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/envio.php';

$bits = [
    ['id' => 1, 'categoria' => 'ciberseguridad'],
    ['id' => 2, 'categoria' => 'cumplimiento'],
    ['id' => 3, 'categoria' => 'pms-crs'],
];

comprobar(
    'columna vacia -"todos"- devuelve la edicion entera, sin tocarla',
    $bits,
    envio_bits_para_tema($bits, '')
);

comprobar(
    'un solo tema elegido deja solo los bits de ese tema',
    [1],
    array_column(envio_bits_para_tema($bits, 'ciberseguridad'), 'id')
);

comprobar(
    'varios temas elegidos, en el orden original de la edicion',
    [1, 2],
    array_column(envio_bits_para_tema($bits, 'ciberseguridad,cumplimiento'), 'id')
);

comprobar(
    'un tema que no trae nada hoy devuelve la lista vacia',
    [],
    envio_bits_para_tema($bits, 'inversion-mercado')
);

comprobar(
    'la categoria vieja de un bit se reconoce igual que la nueva',
    [3],
    array_column(envio_bits_para_tema($bits, 'pms-crs'), 'id')
);

$bits_categoria_vieja = [['id' => 9, 'categoria' => 'pms-gestion']];

comprobar(
    'un bit con el nombre viejo de categoria tambien se filtra bien',
    [9],
    array_column(envio_bits_para_tema($bits_categoria_vieja, 'pms-crs'), 'id')
);

comprobar(
    'una edicion vacia no rompe nada, con o sin filtro',
    [],
    envio_bits_para_tema([], 'ciberseguridad')
);

resumen_pruebas('Pruebas de lib/envio.php');
