<?php
/**
 * Pruebas de lib/agentica.php. Ejecutar: php pruebas/agentica.php
 *
 * Lo que se prueba es la unica decision que toma cron/mantenimiento.php:
 * si toca avisar de que /agentica.html lleva demasiado sin revisarse. Mismas
 * pruebas que pruebas/cifras.php, con el plazo mas corto de esta pagina.
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/agentica.php';

comprobar(
    'por debajo del umbral, no avisa',
    false,
    agentica_caducadas('2026-06-01', '2026-06-15', '')
);

comprobar(
    'justo un dia por debajo del umbral, no avisa',
    false,
    agentica_caducadas('2026-01-01', '2026-03-31', '')
);

comprobar(
    'justo en el umbral, avisa',
    true,
    agentica_caducadas('2026-01-01', '2026-04-01', '')
);

comprobar(
    'muy por encima del umbral, avisa',
    true,
    agentica_caducadas('2025-01-01', '2026-06-01', '')
);

comprobar(
    'caducada pero ya avisada para esta misma revision, no repite',
    false,
    agentica_caducadas('2025-01-01', '2026-06-01', '2025-01-01')
);

comprobar(
    'caducada y el ultimo aviso era de una revision anterior, avisa otra vez',
    true,
    agentica_caducadas('2025-06-01', '2026-06-01', '2025-01-01')
);

comprobar(
    'una fecha de revision ilegible no rompe nada, no avisa',
    false,
    agentica_caducadas('esto no es una fecha', '2026-06-01', '')
);

comprobar(
    'un "hoy" ilegible no rompe nada, no avisa',
    false,
    agentica_caducadas('2025-01-01', 'esto no es una fecha', '')
);

comprobar(
    'el umbral vive donde lo lee el mantenimiento, mas corto que el de Cifras',
    90,
    AGENTICA_CADUCIDAD_DIAS
);

comprobar(
    'agentica_revisado() da una fecha con forma de fecha',
    1,
    (int) preg_match('/^\d{4}-\d{2}-\d{2}$/', agentica_revisado())
);

comprobar(
    'el limite de revision cae justo AGENTICA_CADUCIDAD_DIAS despues',
    '2026-04-01',
    agentica_limite_revision('2026-01-01')
);

comprobar(
    'el limite de revision cruza de mes y de anio sin problema',
    '2027-01-02',
    agentica_limite_revision('2026-10-04')
);

comprobar(
    'una fecha de revision ilegible no rompe nada, se devuelve tal cual',
    'esto no es una fecha',
    agentica_limite_revision('esto no es una fecha')
);

resumen_pruebas('Pruebas de lib/agentica.php');
