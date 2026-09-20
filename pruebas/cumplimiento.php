<?php
/**
 * Pruebas de lib/cumplimiento.php. Ejecutar: php pruebas/cumplimiento.php
 *
 * Mismo espíritu que pruebas/cifras.php: sin correo ni base de datos, para
 * poder probarlo sin montar nada. La diferencia con Cifras está toda aquí:
 * la caducidad de esta página no es un plazo fijo, es la fecha pendiente
 * más próxima de su propia tabla.
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/cumplimiento.php';
require_once dirname(__DIR__) . '/lib/bits.php';

// --- cumplimiento_dias_hasta -------------------------------------------------

comprobar('una fecha futura da un numero positivo', 10, cumplimiento_dias_hasta('2026-10-10', '2026-09-30'));
comprobar('hoy mismo son cero dias', 0, cumplimiento_dias_hasta('2026-09-30', '2026-09-30'));
comprobar('una fecha pasada da un numero negativo', -5, cumplimiento_dias_hasta('2026-09-25', '2026-09-30'));
comprobar('una fecha ilegible no rompe nada', null, cumplimiento_dias_hasta('esto no es una fecha', '2026-09-30'));
comprobar('un "hoy" ilegible tampoco', null, cumplimiento_dias_hasta('2026-09-30', 'esto no es una fecha'));

// --- cumplimiento_limite_revision --------------------------------------------

comprobar(
    'toma la mas proxima de varias fechas futuras',
    '2026-12-02',
    cumplimiento_limite_revision(['2027-01-01', '2026-12-02', '2028-08-02'], '2026-09-20', '2026-09-20')
);

comprobar(
    'ignora las fechas ya pasadas',
    '2027-01-01',
    cumplimiento_limite_revision(['2024-12-02', '2025-06-28', '2027-01-01'], '2026-09-20', '2026-09-20')
);

comprobar(
    'sin ninguna fecha futura, cae al plazo de respaldo desde la revision',
    '2027-03-19',
    cumplimiento_limite_revision(['2024-12-02', '2025-06-28'], '2026-09-20', '2026-09-20')
);

comprobar(
    'una tabla vacia tambien cae al plazo de respaldo',
    '2027-03-19',
    cumplimiento_limite_revision([], '2026-09-20', '2026-09-20')
);

comprobar(
    'una fecha de revision ilegible sin fechas futuras se devuelve tal cual',
    'esto no es una fecha',
    cumplimiento_limite_revision([], '2026-09-20', 'esto no es una fecha')
);

comprobar('el plazo de respaldo vive donde lo lee este fichero', 180, CUMPLIMIENTO_CADUCIDAD_RESPALDO_DIAS);

// --- cumplimiento_caducadas ---------------------------------------------------

comprobar('antes del limite, no avisa', false, cumplimiento_caducadas('2027-01-01', '2026-09-20', '', '2026-09-20'));
comprobar('justo en el limite, avisa', true, cumplimiento_caducadas('2026-09-20', '2026-09-20', '', '2026-09-20'));
comprobar('pasado el limite, avisa', true, cumplimiento_caducadas('2026-09-01', '2026-09-20', '', '2026-09-20'));

comprobar(
    'caducada pero ya avisada para esta misma revision, no repite',
    false,
    cumplimiento_caducadas('2026-09-01', '2026-09-20', '2026-09-20', '2026-09-20')
);

comprobar(
    'caducada y el ultimo aviso era de una revision anterior, avisa otra vez',
    true,
    cumplimiento_caducadas('2026-09-01', '2026-09-20', '2026-06-01', '2026-09-20')
);

comprobar(
    'un limite ilegible no rompe nada, no avisa',
    false,
    cumplimiento_caducadas('esto no es una fecha', '2026-09-20', '', '2026-09-20')
);

// --- cumplimiento_revisado ----------------------------------------------------

comprobar(
    'cumplimiento_revisado() da una fecha con forma de fecha',
    1,
    (int) preg_match('/^\d{4}-\d{2}-\d{2}$/', cumplimiento_revisado())
);

// --- cumplimiento_normas y cumplimiento_fechas -------------------------------
//
// No se prueba el contenido -eso es dato, no logica-, sino la forma: que
// cada norma trae lo que la plantilla necesita para pintarla sin explotar,
// y que las fechas que ve cron/mantenimiento.php son exactamente las
// mismas que la tabla, ni una de mas ni una de menos.

$normas = cumplimiento_normas();

comprobar('hay al menos una norma en el calendario', true, count($normas) > 0);

$estados_validos = ['plazo', 'vigente', 'tramite', 'contexto'];

$categorias_validas = array_keys(bits_categorias());

foreach ($normas as $indice => $norma) {
    foreach (['norma', 'ambito', 'fecha', 'estado', 'texto', 'aplica', 'detalle', 'fuente', 'url', 'temas'] as $campo) {
        comprobar("la norma $indice trae el campo '$campo'", true, array_key_exists($campo, $norma));
    }

    comprobar(
        "la norma $indice tiene un estado del catalogo",
        true,
        in_array($norma['estado'], $estados_validos, true)
    );

    comprobar("los temas de la norma $indice son un array", true, is_array($norma['temas']));

    foreach ($norma['temas'] as $tema) {
        comprobar("el tema '$tema' de la norma $indice está en bits_categorias()", true, in_array($tema, $categorias_validas, true));
    }

    comprobar(
        "la fecha de la norma $indice tiene forma de fecha",
        1,
        (int) preg_match('/^\d{4}-\d{2}-\d{2}$/', $norma['fecha'])
    );

    if ($norma['estado'] === 'plazo') {
        comprobar(
            "la norma $indice, en plazo, trae fecha_cuenta_atras",
            true,
            !empty($norma['fecha_cuenta_atras'])
        );
    }
}

$fechas_norma = [];
foreach ($normas as $norma) {
    $fechas_norma[] = $norma['fecha'];

    if (!empty($norma['fecha_cuenta_atras'])) {
        $fechas_norma[] = $norma['fecha_cuenta_atras'];
    }
}

comprobar(
    'cumplimiento_fechas() trae exactamente las fechas de la tabla, ni una de mas ni de menos',
    $fechas_norma,
    cumplimiento_fechas()
);

// --- cumplimiento_por_tema() --------------------------------------------------

comprobar(
    'NIS2 aparece en ciberseguridad',
    true,
    in_array('NIS2', array_column(cumplimiento_por_tema('ciberseguridad'), 'norma'), true)
);

comprobar(
    'Verifactu no aparece en ciberseguridad',
    false,
    in_array('Verifactu', array_column(cumplimiento_por_tema('ciberseguridad'), 'norma'), true)
);

comprobar(
    'el alquiler de corta duracion -no aplica a hoteles- no aparece en ningun tema',
    false,
    in_array('Reglamento de alquiler de corta duración', array_column(cumplimiento_por_tema('cumplimiento'), 'norma'), true)
);

comprobar('un tema sin ninguna norma devuelve una lista vacia', [], cumplimiento_por_tema('sostenibilidad-energia'));

resumen_pruebas('Pruebas de lib/cumplimiento.php');
