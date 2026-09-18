<?php
/**
 * Pruebas de lib/cifras.php. Ejecutar: php pruebas/cifras.php
 *
 * Lo que se prueba es la unica decision que toma cron/mantenimiento.php:
 * si toca avisar de que /estadisticas.html lleva demasiado sin revisarse.
 * Sin correo ni base de datos, para poder probarla sin montar nada.
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/cifras.php';

comprobar(
    'por debajo del umbral, no avisa',
    false,
    cifras_caducadas('2026-06-01', '2026-06-15', '')
);

comprobar(
    'justo un dia por debajo del umbral, no avisa',
    false,
    cifras_caducadas('2026-01-01', '2026-04-30', '')
);

comprobar(
    'justo en el umbral, avisa',
    true,
    cifras_caducadas('2026-01-01', '2026-05-01', '')
);

comprobar(
    'muy por encima del umbral, avisa',
    true,
    cifras_caducadas('2025-01-01', '2026-06-01', '')
);

comprobar(
    'caducada pero ya avisada para esta misma revision, no repite',
    false,
    cifras_caducadas('2025-01-01', '2026-06-01', '2025-01-01')
);

comprobar(
    'caducada y el ultimo aviso era de una revision anterior, avisa otra vez',
    true,
    cifras_caducadas('2025-06-01', '2026-06-01', '2025-01-01')
);

comprobar(
    'una fecha de revision ilegible no rompe nada, no avisa',
    false,
    cifras_caducadas('esto no es una fecha', '2026-06-01', '')
);

comprobar(
    'un "hoy" ilegible no rompe nada, no avisa',
    false,
    cifras_caducadas('2025-01-01', 'esto no es una fecha', '')
);

comprobar(
    'el umbral vive donde lo lee el mantenimiento',
    120,
    CIFRAS_CADUCIDAD_DIAS
);

comprobar(
    'cifras_revisado() da una fecha con forma de fecha',
    1,
    (int) preg_match('/^\d{4}-\d{2}-\d{2}$/', cifras_revisado())
);

comprobar(
    'el limite de revision cae justo CIFRAS_CADUCIDAD_DIAS despues',
    '2026-05-01',
    cifras_limite_revision('2026-01-01')
);

comprobar(
    'el limite de revision cruza de mes y de anio sin problema',
    '2027-01-30',
    cifras_limite_revision('2026-10-02')
);

comprobar(
    'una fecha de revision ilegible no rompe nada, se devuelve tal cual',
    'esto no es una fecha',
    cifras_limite_revision('esto no es una fecha')
);

comprobar(
    'el valor de IA en España tiene forma de porcentaje, sin el simbolo',
    1,
    (int) preg_match('/^\d+,\d$/', cifras_valor_ia_espana())
);

resumen_pruebas('Pruebas de lib/cifras.php');
