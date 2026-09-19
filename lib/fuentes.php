<?php
/**
 * El catalogo de fuentes: que se valida antes de darlo de alta.
 *
 * Calculo puro, como lib/bits.php: ni base de datos ni sesion. Las consultas
 * que sí tocan la tabla `fuentes` viven en panel/datos.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/texto.php';
require_once __DIR__ . '/bits.php';

/** Mismo limite que la columna VARCHAR(120) de fuentes.nombre. */
const FUENTES_NOMBRE_MAX = 120;

/** Mismo limite que la columna VARCHAR(500) de fuentes.notas. */
const FUENTES_NOTAS_MAX = 500;

/**
 * Los tipos de fuente del catalogo. El mismo ENUM de sql/esquema.sql, para
 * que el formulario del panel no pueda ofrecer un valor que la base rechace.
 */
function fuentes_tipos(): array
{
    return [
        'prensa'        => 'Prensa',
        'changelog'     => 'Changelog de producto',
        'estado'        => 'Página de estado',
        'empleo'        => 'Empleo',
        'financiacion'  => 'Financiación',
        'normativa'     => 'Normativa',
        'investigacion' => 'Investigación',
        'ferias'        => 'Ferias',
        'general'       => 'General',
    ];
}

/** Los ámbitos del catálogo. Mismo ENUM que fuentes.region. */
function fuentes_regiones(): array
{
    return [
        'es'     => 'España',
        'eu'     => 'Europa',
        'global' => 'Global',
    ];
}

/**
 * Valida una fuente antes de darla de alta. Devuelve la lista de errores.
 *
 * Mismo criterio de las últimas migraciones de fuentes -014 en adelante-,
 * ahora exigido en el propio formulario: nombre y url_feed no pueden faltar,
 * el resto del catálogo tiene que reconocer tipo, idioma y región, y el peso
 * se queda en la escala 1-10 que ya usan las 59 fuentes existentes.
 */
function fuentes_validar(array $datos): array
{
    $errores = [];

    $nombre   = trim((string) ($datos['nombre'] ?? ''));
    $url_feed = trim((string) ($datos['url_feed'] ?? ''));
    $idioma   = trim((string) ($datos['idioma'] ?? ''));
    $tipo     = (string) ($datos['tipo'] ?? '');
    $region   = (string) ($datos['region'] ?? '');
    $categoria = (string) ($datos['categoria_defecto'] ?? '');
    $peso     = $datos['peso'] ?? null;
    $notas    = trim((string) ($datos['notas'] ?? ''));

    if ($nombre === '') {
        $errores[] = 'Falta el nombre de la fuente.';
    } elseif (mb_strlen($nombre) > FUENTES_NOMBRE_MAX) {
        $errores[] = sprintf('El nombre tiene %d caracteres y el máximo son %d.', mb_strlen($nombre), FUENTES_NOMBRE_MAX);
    }

    if ($url_feed === '') {
        $errores[] = 'Falta la URL del feed (RSS o Atom).';
    } elseif (!filter_var($url_feed, FILTER_VALIDATE_URL) || !str_starts_with($url_feed, 'http')) {
        $errores[] = 'La URL del feed no parece una dirección válida.';
    }

    if (!array_key_exists($tipo, fuentes_tipos())) {
        $errores[] = 'El tipo no está en el catálogo.';
    }

    if (!preg_match('/^[a-z]{2}$/', $idioma)) {
        $errores[] = 'El idioma va en dos letras minúsculas, como "es" o "en".';
    }

    if (!array_key_exists($region, fuentes_regiones())) {
        $errores[] = 'El ámbito no está en el catálogo.';
    }

    if (!array_key_exists($categoria, bits_categorias())) {
        $errores[] = 'La categoría por defecto no está en el catálogo.';
    }

    if (!is_numeric($peso) || (int) $peso < 1 || (int) $peso > 10) {
        $errores[] = 'El peso va del 1 al 10.';
    }

    if (mb_strlen($notas) > FUENTES_NOTAS_MAX) {
        $errores[] = sprintf('Las notas tienen %d caracteres y el máximo son %d.', mb_strlen($notas), FUENTES_NOTAS_MAX);
    }

    return $errores;
}
