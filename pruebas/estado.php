<?php
/**
 * Pruebas de lib/estado.php.  Ejecutar:  php pruebas/estado.php
 *
 * De estas cuentas depende algo que no se ve: si el cron esta callado, la
 * portada se pone a trabajar en cada visita. Equivocarse por exceso convierte
 * una portada compartida en un martillo contra la base de datos; por defecto,
 * deja el radar congelado sin que nadie se entere.
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/estado.php';

$ahora    = 1_800_000_000;
$silencio = 7200;

// --- ¿Esta callado el cron? -------------------------------------------------

comprobar(
    'sin marca, se da por callado',
    true,
    estado_cron_callado(0, $ahora, $silencio)
);

comprobar(
    'recien pasado, no',
    false,
    estado_cron_callado($ahora - 60, $ahora, $silencio)
);

comprobar(
    'justo antes del limite, todavia no',
    false,
    estado_cron_callado($ahora - 7199, $ahora, $silencio)
);

comprobar(
    'cumplido el limite, si',
    true,
    estado_cron_callado($ahora - 7200, $ahora, $silencio)
);

comprobar(
    'dos dias sin pasar, si',
    true,
    estado_cron_callado($ahora - 172800, $ahora, $silencio)
);

// Una marca con fecha futura no es un cron vivo: es un reloj mal puesto, y
// creersela dejaria el sitio parado hasta que llegase esa fecha.
comprobar(
    'una marca del futuro no cuenta como senal de vida',
    true,
    estado_cron_callado($ahora + 90000, $ahora, $silencio)
);

// Un desfase pequeno entre el reloj del cron y el de la web es normal y no
// debe interpretarse como avería.
comprobar(
    'un desfase de unos minutos se tolera',
    false,
    estado_cron_callado($ahora + 120, $ahora, $silencio)
);

// --- Antiguedad -------------------------------------------------------------

comprobar('sin marca, no hay edad', null, estado_edad(0, $ahora));
comprobar('edad en segundos', 300, estado_edad($ahora - 300, $ahora));

// El reloj del servidor puede irse hacia atras; una edad negativa no significa
// nada para quien lee el informe.
comprobar('nunca una edad negativa', 0, estado_edad($ahora + 50, $ahora));

// --- Antiguedad en palabras -------------------------------------------------

comprobar('sin marca', 'nunca', estado_edad_texto(null));
comprobar('segundos', 'hace 45 s', estado_edad_texto(45));
comprobar('minutos', 'hace 30 min', estado_edad_texto(1800));
comprobar('horas', 'hace 5 h', estado_edad_texto(18000));
comprobar('dias', 'hace 3 días', estado_edad_texto(259200));

resumen_pruebas('Pruebas del estado del sitio');
