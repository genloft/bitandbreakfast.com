<?php
/**
 * Lo que necesita /calendario.html para saber qué eventos del sector quedan
 * por delante, y el mantenimiento diario para saber si esa página está al
 * día.
 *
 * Mismo patrón que lib/cifras.php: los datos viven fuera de la plantilla, a
 * mano, revisados con fecha -esta página no rastrea ninguna agenda, cuenta
 * las ferias y foros que un directivo del sector ya tiene marcados en su
 * calendario-. El plazo de revisión es más largo que el de Cifras o
 * Agéntica: un calendario de ferias no cambia de mes en mes, cambia cuando
 * el organizador anuncia la fecha del año siguiente, algo que aquí ocurre
 * dos veces al año como mucho.
 */

declare(strict_types=1);

/**
 * Pasados estos días sin que una persona revise /calendario.html, el
 * mantenimiento diario avisa por correo.
 *
 * Ciento ochenta días son seis meses: esta página se anuncia a sí misma
 * como actualizada dos veces al año, así que el aviso llega justo cuando
 * toca la siguiente revisión, no antes.
 */
const CALENDARIO_CADUCIDAD_DIAS = 180;

/**
 * La fecha en que una persona revisó por última vez /calendario.html.
 *
 * Fija, escrita a mano, nunca gmdate(): igual que cifras_revisado(), esta
 * página no se pone al día sola.
 */
function calendario_revisado(): string
{
    return '2026-09-20';
}

/**
 * Los eventos del sector que un directivo hotelero ya tiene marcados en su
 * calendario: ferias, congresos y foros de tecnología hotelera, con fecha,
 * lugar y fuente en cada uno.
 *
 * 'fecha_fin' es la que decide qué se enseña en /calendario.html
 * -calendario_proximos() descarta lo que ya ha pasado-, no una fecha
 * cualquiera del texto: el último día del evento, o el único día si dura
 * uno solo. Para el ITH Hotel Energy Meetings -una gira por varias
 * ciudades, no un congreso con una sola fecha- es la fecha de la última
 * parada anunciada.
 */
function calendario_eventos(): array
{
    return [
        [
            'nombre'      => 'TIS · Tourism Innovation Summit',
            'edicion'     => '7.ª edición',
            'fechas'      => '6-8 de octubre de 2026',
            'fecha_fin'   => '2026-10-08',
            'lugar'       => 'FIBES, Sevilla',
            'descripcion' => 'Cumbre global de innovación turística: más de 8.000 profesionales y 400 ponentes internacionales, con la distribución y la tecnología de revenue como uno de sus ejes.',
            'fuente'      => 'TIS — Tourism Innovation Summit',
            'url'         => 'https://www.tisglobalsummit.com/about-tis/',
        ],
        [
            'nombre'      => 'ITH Hotel Energy Meetings · Tour 2026',
            'edicion'     => null,
            'fechas'      => 'Madrid (30 de septiembre), Barcelona (20 de octubre), Málaga (27 de octubre) y Benidorm (17 de noviembre) de 2026',
            'fecha_fin'   => '2026-11-17',
            'lugar'       => 'Varias ciudades españolas',
            'descripcion' => 'Gira de jornadas del Instituto Tecnológico Hotelero sobre eficiencia energética y descarbonización del hotel: la cita más práctica del calendario para quien decide sobre climatización, autoconsumo o gestión energética.',
            'fuente'      => 'Instituto Tecnológico Hotelero (ITH)',
            'url'         => 'https://www.ithotelero.com/events/',
        ],
        [
            'nombre'      => 'FITURTechY, dentro de FITUR',
            'edicion'     => null,
            'fechas'      => '20-24 de enero de 2027',
            'fecha_fin'   => '2027-01-24',
            'lugar'       => 'IFEMA Madrid',
            'descripcion' => 'La sección de tecnología turística de FITUR, organizada con el Instituto Tecnológico Hotelero: el primer punto de encuentro del año para ver de un vistazo qué proveedores y qué tecnología entran en el radar del sector.',
            'fuente'      => 'IFEMA MADRID — FITUR',
            'url'         => 'https://www.ifema.es/en/fitur',
        ],
        [
            'nombre'      => 'HIP · Hospitality Innovation Planet',
            'edicion'     => '11.ª edición',
            'fechas'      => '1-3 de marzo de 2027',
            'fecha_fin'   => '2027-03-03',
            'lugar'       => 'IFEMA Madrid',
            'descripcion' => 'La mayor feria de hostelería y foodservice de Europa, con el Hospitality 4.0 Congress dentro: más de 750 ponentes sobre las tendencias tecnológicas más recientes del sector.',
            'fuente'      => 'IFEMA MADRID — HIP',
            'url'         => 'https://www.ifema.es/hip',
        ],
        [
            'nombre'      => 'IHTF EU · International Hotel Technology Forum',
            'edicion'     => '24.ª edición',
            'fechas'      => '13-15 de abril de 2027',
            'fecha_fin'   => '2027-04-15',
            'lugar'       => 'Barcelona',
            'descripcion' => 'Foro para propietarios y directivos de cadenas hoteleras centrado en la tecnología con impacto medible en experiencia de huésped, ingresos y operaciones.',
            'fuente'      => 'Arena International — IHTF EU',
            'url'         => 'https://www.arena-international.com/event/ihtf/',
        ],
    ];
}

/**
 * Los eventos que todavía quedan por delante de $hoy, ordenados del más
 * próximo al más lejano.
 *
 * Un calendario no enseña lo que ya ha pasado: a diferencia de
 * cifras_grupos() o cumplimiento_normas(), que se leen enteros siempre,
 * aquí lo pasado deja de ser información y pasa a ser ruido. Cuando no
 * queda ninguno, /calendario.html lo dice en vez de enseñar una lista
 * vacía sin explicación -señal, además, de que toca la revisión.
 */
function calendario_proximos(array $eventos, string $hoy): array
{
    $hoy_ts = strtotime($hoy . ' UTC');

    if ($hoy_ts === false) {
        return $eventos;
    }

    $proximos = array_values(array_filter(
        $eventos,
        static function (array $evento) use ($hoy_ts): bool {
            $fin_ts = strtotime($evento['fecha_fin'] . ' UTC');

            return $fin_ts === false || $fin_ts >= $hoy_ts;
        }
    ));

    usort(
        $proximos,
        static fn (array $a, array $b): int => strcmp($a['fecha_fin'], $b['fecha_fin'])
    );

    return $proximos;
}

/**
 * Hasta cuándo "revisado el $revisado" sigue siendo una promesa vigente.
 * Misma lógica que cifras_limite_revision(), con CALENDARIO_CADUCIDAD_DIAS.
 */
function calendario_limite_revision(string $revisado): string
{
    $revisado_ts = strtotime($revisado . ' UTC');

    if ($revisado_ts === false) {
        return $revisado;
    }

    return gmdate('Y-m-d', $revisado_ts + CALENDARIO_CADUCIDAD_DIAS * 86400);
}

/**
 * Si toca avisar de que /calendario.html lleva demasiado sin revisarse.
 * Misma lógica que cifras_caducadas(), con CALENDARIO_CADUCIDAD_DIAS.
 */
function calendario_caducadas(string $revisado, string $hoy, string $ultimo_aviso): bool
{
    $revisado_ts = strtotime($revisado . ' UTC');
    $hoy_ts      = strtotime($hoy . ' UTC');

    if ($revisado_ts === false || $hoy_ts === false) {
        return false;
    }

    $dias = (int) floor(($hoy_ts - $revisado_ts) / 86400);

    if ($dias < CALENDARIO_CADUCIDAD_DIAS) {
        return false;
    }

    return $ultimo_aviso !== $revisado;
}
