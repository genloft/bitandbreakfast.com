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
require_once dirname(__DIR__) . '/lib/bits.php';

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

// --- cifras_grupos() y cifras_por_tema() -------------------------------------

$grupos = cifras_grupos();

comprobar('hay al menos diez grupos en el cuadro de mandos', true, count($grupos) >= 10);

$categorias_validas = array_keys(bits_categorias());

foreach ($grupos as $indice => $grupo) {
    foreach (['tema', 'categoria', 'cifras'] as $campo) {
        comprobar("el grupo $indice trae el campo '$campo'", true, array_key_exists($campo, $grupo));
    }

    comprobar("el grupo $indice trae al menos una cifra", true, count($grupo['cifras']) > 0);

    if ($grupo['categoria'] !== null) {
        comprobar(
            "la categoria del grupo $indice está en bits_categorias()",
            true,
            in_array($grupo['categoria'], $categorias_validas, true)
        );
    }

    foreach ($grupo['cifras'] as $cifra) {
        foreach (['ambito', 'valor', 'detalle', 'fuente', 'fecha'] as $campo) {
            comprobar("cada cifra del grupo $indice trae '$campo'", true, array_key_exists($campo, $cifra));
        }
    }
}

comprobar(
    'IA en los hoteles aparece al pedir ia-aplicada',
    true,
    in_array('IA en los hoteles', array_column(cifras_por_tema('ia-aplicada'), 'tema'), true)
);

comprobar(
    'la vara de medir de fondo -IA en la empresa- no aparece en ningun tema hotelero',
    false,
    in_array('IA en la empresa, en general', array_column(cifras_por_tema('ia-aplicada'), 'tema'), true)
);

comprobar('un tema sin ningun grupo devuelve una lista vacia', [], cifras_por_tema('sostenibilidad-energia'));

comprobar(
    'el mix de canal directo y OTA aparece al pedir distribucion-otas',
    true,
    in_array('Mix de canal directo y OTA', array_column(cifras_por_tema('distribucion-otas'), 'tema'), true)
);

// --- cifras_exportar() --------------------------------------------------------

$exportado = cifras_exportar();

foreach (['revisado', 'limite_revision', 'grupos'] as $campo) {
    comprobar("cifras_exportar() trae el campo '$campo'", true, array_key_exists($campo, $exportado));
}

comprobar('cifras_exportar() usa la misma fecha de revision que la pagina', cifras_revisado(), $exportado['revisado']);
comprobar('y el mismo limite de revision', cifras_limite_revision(cifras_revisado()), $exportado['limite_revision']);
comprobar('y exporta los mismos grupos, no una copia distinta', cifras_grupos(), $exportado['grupos']);

// --- Las tres cifras de la portada ---------------------------------------------
//
// Se eligen por el nombre del grupo. Si alguien renombra uno, la portada se
// queda sin ese numero y no falla nada: la tira se pinta con dos, o con
// ninguna, y nadie se entera. Estas comprobaciones son la unica alarma.

$destacadas = cifras_destacadas();

comprobar('la portada saca tres cifras, ni mas ni menos', 3, count($destacadas));

comprobar(
    'y las tres traen valor, rotulo y tema',
    [],
    array_values(array_filter(
        $destacadas,
        static fn (array $c): bool => ($c['valor'] ?? '') === ''
            || ($c['rotulo'] ?? '') === ''
            || ($c['tema'] ?? '') === ''
    ))
);

// El valor no se copia: se lee del mismo sitio que pinta /estadisticas.html.
// Si se copiara, la portada acabaria diciendo un numero que la propia pagina
// de Cifras ya hubiera corregido.
$temas_vivos = array_column(cifras_grupos(), 'tema');

comprobar(
    'los grupos que cita la portada siguen existiendo en Cifras',
    [],
    array_values(array_diff(array_column($destacadas, 'tema'), $temas_vivos))
);

resumen_pruebas('Pruebas de lib/cifras.php');
