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

comprobar('hay al menos cuarenta y cinco terminos', true, count($terminos) >= 45);

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

comprobar('un tema sin ningun termino devuelve una lista vacia', [], glosario_por_tema('inversion-mercado'));
comprobar('igual que el generico, que tampoco tiene ninguno', [], glosario_por_tema('tecnologia-general'));

comprobar(
    'un termino con tema null no aparece al pedir la cadena vacia',
    false,
    in_array('API', array_column(glosario_por_tema(''), 'sigla'), true)
);

comprobar(
    'los protocolos de reserva agentica -MCP, ACP y agentic booking- caen en distribucion-otas',
    true,
    count(array_intersect(
        ['MCP', 'ACP', 'Agentic booking'],
        array_column(glosario_por_tema('distribucion-otas'), 'sigla')
    )) === 3
);

comprobar(
    'las seis normas nuevas -NIS2, AI Act, EAA, Verifactu, SES.Hospedajes, CSRD- caen en cumplimiento junto a RGPD',
    true,
    count(array_intersect(
        ['NIS2', 'AI Act', 'EAA', 'Verifactu', 'SES.Hospedajes', 'CSRD', 'RGPD'],
        array_column(glosario_por_tema('cumplimiento'), 'sigla')
    )) === 7
);

$por_sigla = array_column(glosario_terminos(), 'tema', 'sigla');

foreach (['CDP', 'Middleware', 'iPaaS', 'Webhook', 'SSO'] as $sigla) {
    comprobar(
        "$sigla es generico, sin tema, igual que API y KPI",
        null,
        array_key_exists($sigla, $por_sigla) ? $por_sigla[$sigla] : 'NO ENCONTRADO'
    );
}

resumen_pruebas('Pruebas de lib/glosario.php');
