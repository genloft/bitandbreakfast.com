<?php
/**
 * Lectura y cache de robots.txt.
 *
 * No esta en el arbol del documento de especificacion, pero prefiero un
 * fichero de cincuenta lineas a meter esto dentro de feed.php: son dos cosas
 * distintas y se prueban por separado.
 *
 * El fichero se cachea 24 horas en disco. Sin cache, rastrear 90 fuentes
 * significaria descargar robots.txt 90 veces al dia sin necesidad.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/feed.php';   // feed_descargar()

/**
 * Lo que se lanza cuando el robots.txt del dominio dice que no.
 *
 * Tiene tipo propio para poder distinguirlo de un fallo: un 403 de paso se
 * reintenta dentro de un rato, pero un "no" escrito en robots.txt es una
 * decision del medio, no una averia, y se respeta hasta que la cambie. Quien
 * lo captura sabe asi que no tiene sentido volver mañana.
 */
class RobotsProhibido extends RuntimeException
{
}

/**
 * Dice si podemos pedir una URL segun el robots.txt de su dominio.
 * Ante la duda (error de red, fichero ilegible), se permite: un robots.txt
 * caido no es una prohibicion.
 */
function robots_permite(string $url): bool
{
    $partes = parse_url($url);
    if (empty($partes['host'])) {
        return false;
    }

    $reglas = robots_reglas(($partes['scheme'] ?? 'https') . '://' . $partes['host']);
    if ($reglas === []) {
        return true;
    }

    $ruta = $partes['path'] ?? '/';
    if (!empty($partes['query'])) {
        $ruta .= '?' . $partes['query'];
    }

    // Gana la regla mas larga que case, que es lo que dice el estandar.
    $mejor_permiso  = '';
    $mejor_prohibe  = '';

    foreach ($reglas['permitir'] as $patron) {
        if (robots_casa($ruta, $patron) && strlen($patron) > strlen($mejor_permiso)) {
            $mejor_permiso = $patron;
        }
    }
    foreach ($reglas['prohibir'] as $patron) {
        if (robots_casa($ruta, $patron) && strlen($patron) > strlen($mejor_prohibe)) {
            $mejor_prohibe = $patron;
        }
    }

    if ($mejor_prohibe === '') {
        return true;
    }

    return strlen($mejor_permiso) >= strlen($mejor_prohibe);
}

/**
 * Devuelve las reglas aplicables a nuestro bot, con cache en disco de 24 horas.
 */
function robots_reglas(string $origen): array
{
    static $memoria = [];

    if (isset($memoria[$origen])) {
        return $memoria[$origen];
    }

    $dir = config('rutas.cache') ?: dirname(__DIR__) . '/cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $fichero = $dir . '/robots_' . sha1($origen) . '.txt';
    $texto   = null;

    if (is_readable($fichero) && (time() - filemtime($fichero)) < 86400) {
        $texto = file_get_contents($fichero);
    }

    if ($texto === null) {
        $respuesta = feed_descargar($origen . '/robots.txt');
        // 404 o error: no hay robots.txt, no hay restricciones. Se cachea el
        // vacio igualmente para no volver a preguntar en 24 horas.
        $texto = ($respuesta['codigo'] === 200) ? $respuesta['cuerpo'] : '';
        @file_put_contents($fichero, $texto);
    }

    $memoria[$origen] = robots_analizar($texto);

    return $memoria[$origen];
}

/**
 * Extrae las reglas del grupo que nos aplica: primero el grupo especifico de
 * BitAndBreakfastBot y, si no existe, el grupo de *.
 */
function robots_analizar(string $texto): array
{
    if (trim($texto) === '') {
        return [];
    }

    $grupos  = [];
    $actual  = [];
    $ultimo_era_agente = false;

    foreach (preg_split('/\r\n|\r|\n/', $texto) as $linea) {
        $linea = trim(preg_replace('/#.*$/', '', $linea));
        if ($linea === '' || !str_contains($linea, ':')) {
            continue;
        }

        [$campo, $valor] = array_map('trim', explode(':', $linea, 2));
        $campo = strtolower($campo);

        if ($campo === 'user-agent') {
            if (!$ultimo_era_agente) {
                $actual = [];   // empieza un grupo nuevo
            }
            $actual[] = strtolower($valor);
            $ultimo_era_agente = true;
            continue;
        }

        $ultimo_era_agente = false;

        if ($campo !== 'allow' && $campo !== 'disallow') {
            continue;
        }

        foreach ($actual as $agente) {
            $grupos[$agente][$campo === 'allow' ? 'permitir' : 'prohibir'][] = $valor;
        }
    }

    $nuestro = 'bitandbreakfastbot';
    $clave   = null;

    foreach (array_keys($grupos) as $agente) {
        if (str_contains($agente, $nuestro)) {
            $clave = $agente;
            break;
        }
    }
    if ($clave === null) {
        $clave = isset($grupos['*']) ? '*' : null;
    }
    if ($clave === null) {
        return [];
    }

    return [
        'permitir' => $grupos[$clave]['permitir'] ?? [],
        'prohibir' => array_filter($grupos[$clave]['prohibir'] ?? [], fn($p) => $p !== ''),
    ];
}

/**
 * Compara una ruta con un patron de robots.txt, con soporte de * y de $.
 */
function robots_casa(string $ruta, string $patron): bool
{
    if ($patron === '') {
        return false;
    }

    $regex = preg_quote($patron, '#');
    $regex = str_replace('\*', '.*', $regex);

    if (str_ends_with($regex, '\$')) {
        $regex = substr($regex, 0, -2) . '$';
    }

    return (bool) preg_match('#^' . $regex . '#', $ruta);
}
