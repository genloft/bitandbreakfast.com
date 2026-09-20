<?php
/**
 * Pruebas de lib/calendario.php. Ejecutar: php pruebas/calendario.php
 *
 * Dos cosas: la misma logica de caducidad que ya prueban pruebas/cifras.php
 * y pruebas/agentica.php, y calendario_proximos() -que es la unica logica
 * propia de esta pagina, la que decide que se enseña y que se descarta-.
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/calendario.php';

comprobar(
    'por debajo del umbral, no avisa',
    false,
    calendario_caducadas('2026-06-01', '2026-06-15', '')
);

comprobar(
    'justo un dia por debajo del umbral, no avisa',
    false,
    calendario_caducadas('2026-01-01', '2026-06-29', '')
);

comprobar(
    'justo en el umbral, avisa',
    true,
    calendario_caducadas('2026-01-01', '2026-06-30', '')
);

comprobar(
    'muy por encima del umbral, avisa',
    true,
    calendario_caducadas('2024-01-01', '2026-06-01', '')
);

comprobar(
    'caducada pero ya avisada para esta misma revision, no repite',
    false,
    calendario_caducadas('2024-01-01', '2026-06-01', '2024-01-01')
);

comprobar(
    'caducada y el ultimo aviso era de una revision anterior, avisa otra vez',
    true,
    calendario_caducadas('2025-06-01', '2026-06-01', '2024-01-01')
);

comprobar(
    'una fecha de revision ilegible no rompe nada, no avisa',
    false,
    calendario_caducadas('esto no es una fecha', '2026-06-01', '')
);

comprobar(
    'un "hoy" ilegible no rompe nada, no avisa',
    false,
    calendario_caducadas('2025-01-01', 'esto no es una fecha', '')
);

comprobar(
    'el umbral vive donde lo lee el mantenimiento, el mas largo de los tres',
    180,
    CALENDARIO_CADUCIDAD_DIAS
);

comprobar(
    'calendario_revisado() da una fecha con forma de fecha',
    1,
    (int) preg_match('/^\d{4}-\d{2}-\d{2}$/', calendario_revisado())
);

comprobar(
    'el limite de revision cae justo CALENDARIO_CADUCIDAD_DIAS despues',
    '2026-06-30',
    calendario_limite_revision('2026-01-01')
);

comprobar(
    'una fecha de revision ilegible no rompe nada, se devuelve tal cual',
    'esto no es una fecha',
    calendario_limite_revision('esto no es una fecha')
);

// --- calendario_eventos() ----------------------------------------------------

$eventos = calendario_eventos();

comprobar('hay al menos cuatro eventos en el catalogo', true, count($eventos) >= 4);

foreach ($eventos as $indice => $evento) {
    foreach (['nombre', 'fechas', 'fecha_fin', 'lugar', 'descripcion', 'fuente', 'url'] as $campo) {
        comprobar("el evento $indice trae el campo '$campo'", true, array_key_exists($campo, $evento));
    }

    comprobar(
        "la fecha_fin del evento $indice tiene forma de fecha",
        1,
        (int) preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $evento['fecha_fin'])
    );
}

// --- calendario_proximos() ----------------------------------------------------

$sinteticos = [
    ['nombre' => 'Ya pasado', 'fecha_fin' => '2026-01-01'],
    ['nombre' => 'El mas lejano', 'fecha_fin' => '2027-06-01'],
    ['nombre' => 'El mas cercano', 'fecha_fin' => '2026-10-01'],
    ['nombre' => 'Termina hoy mismo', 'fecha_fin' => '2026-09-20'],
];

$proximos = calendario_proximos($sinteticos, '2026-09-20');

comprobar('descarta lo que ya ha pasado', false, in_array('Ya pasado', array_column($proximos, 'nombre'), true));
comprobar('conserva lo que termina justo hoy', true, in_array('Termina hoy mismo', array_column($proximos, 'nombre'), true));
comprobar(
    'ordena del mas proximo al mas lejano',
    ['Termina hoy mismo', 'El mas cercano', 'El mas lejano'],
    array_column($proximos, 'nombre')
);

comprobar(
    'sin ningun evento por delante, la lista queda vacia -no rota-',
    [],
    calendario_proximos($sinteticos, '2028-01-01')
);

comprobar(
    'un "hoy" ilegible no rompe nada, devuelve la lista tal cual',
    $sinteticos,
    calendario_proximos($sinteticos, 'esto no es una fecha')
);

resumen_pruebas('Pruebas de lib/calendario.php');
