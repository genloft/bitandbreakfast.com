<?php
/**
 * Pruebas de lib/envio.php. Ejecutar: php pruebas/envio.php
 *
 * envio_bits_para_tema() (§3.1) y envio_bits_para_alerta() (§3.2) de
 * docs/MEJORAS.md, las dos unicas piezas de este fichero que no tenian
 * pruebas hasta ahora. El resto -envio_asunto(), envio_texto(),
 * envio_html()- se queda fuera de esta prueba: no ha cambiado y no es
 * parte de este trabajo.
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

// --- envio_bits_para_alerta() ------------------------------------------------

$bits_alerta = [
    ['id' => 1, 'titular' => 'Mews lanza una integración con Booking.com', 'cuerpo' => 'El PMS suma un canal más.', 'proveedores' => 'mews|Mews'],
    ['id' => 2, 'titular' => 'Un ataque de ransomware paraliza una cadena', 'cuerpo' => 'El incidente duró dos días.', 'proveedores' => ''],
    ['id' => 3, 'titular' => 'Oracle Hospitality anuncia una función nueva', 'cuerpo' => 'Disponible desde este trimestre.', 'proveedores' => 'oracle-hospitality|Oracle Hospitality'],
    ['id' => 4, 'titular' => 'Un hotel de Sevilla estrena recepción sin personal', 'cuerpo' => 'El check-in ya es automático.', 'proveedores' => ''],
];

comprobar(
    'columna vacia -sin alerta- devuelve la edicion entera, sin tocarla',
    $bits_alerta,
    envio_bits_para_alerta($bits_alerta, '')
);

comprobar(
    'un termino que aparece en el titular se encuentra',
    [2],
    array_column(envio_bits_para_alerta($bits_alerta, 'ransomware'), 'id')
);

comprobar(
    'un termino que solo aparece en el nombre de un proveedor tambien',
    [1],
    array_column(envio_bits_para_alerta($bits_alerta, 'Mews'), 'id')
);

comprobar(
    'un termino mas corto que el nombre completo del proveedor encuentra igual',
    [3],
    array_column(envio_bits_para_alerta($bits_alerta, 'Oracle'), 'id')
);

comprobar(
    'no distingue mayusculas de minusculas',
    [1],
    array_column(envio_bits_para_alerta($bits_alerta, 'mews'), 'id')
);

comprobar(
    'varios terminos son una alerta cada uno, no hace falta que casen todos',
    [1, 2],
    array_column(envio_bits_para_alerta($bits_alerta, 'Mews, ransomware'), 'id')
);

comprobar(
    'un termino que no aparece en ningun sitio devuelve la lista vacia',
    [],
    envio_bits_para_alerta($bits_alerta, 'metabuscador')
);

resumen_pruebas('Pruebas de lib/envio.php');
