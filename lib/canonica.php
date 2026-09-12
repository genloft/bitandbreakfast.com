<?php
/**
 * Canonicalizacion de URLs.
 *
 * Deduplicar exige normalizar antes: la misma noticia llega con utm_source
 * distinto desde el feed, desde el boletin del medio y desde el agregador.
 * El SHA-1 de la URL canonica es la clave unica de items, asi que un fallo
 * aqui se traduce en duplicados en la edicion.
 *
 * Tiene pruebas propias en pruebas/canonica.php.
 */

/**
 * Parametros de seguimiento que se eliminan siempre.
 * Los que terminan en * se tratan como prefijo.
 */
function canonica_parametros_basura(): array
{
    return [
        'utm_*', 'fbclid', 'gclid', 'gclsrc', 'dclid', 'msclkid',
        'mc_cid', 'mc_eid', 'ref', 'referrer', 'source', 'src',
        'igshid', 'igsh', 'cmpid', 'ncid', 'spm', 'vero_id', 'vero_conv',
        '_hsenc', '_hsmi', 'hsCtaTracking', 'oly_enc_id', 'oly_anon_id',
        'trk', 'trkCampaign', 'sh', 'share', 'amp',
    ];
}

/**
 * Convierte una URL en su forma canonica.
 *
 * Devuelve cadena vacia si la URL no es utilizable (sin host, o con un
 * esquema que no sea http o https).
 */
function canonica_normalizar(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    $partes = parse_url($url);
    if ($partes === false || empty($partes['host'])) {
        return '';
    }

    $esquema = strtolower($partes['scheme'] ?? 'https');
    if ($esquema !== 'http' && $esquema !== 'https') {
        return '';
    }

    // Se fuerza https: el mismo articulo servido por http y por https es el
    // mismo articulo, y practicamente todo el sector ya redirige a https.
    $esquema = 'https';

    $host = strtolower($partes['host']);
    if (str_starts_with($host, 'www.')) {
        $host = substr($host, 4);
    }

    // El puerto por defecto no forma parte de la identidad de la URL.
    $puerto = '';
    if (!empty($partes['port']) && !in_array((int) $partes['port'], [80, 443], true)) {
        $puerto = ':' . (int) $partes['port'];
    }

    $ruta = $partes['path'] ?? '/';
    $ruta = canonica_limpiar_amp($ruta);

    // Se quita la barra final salvo que la ruta sea solo la raiz.
    if (strlen($ruta) > 1) {
        $ruta = rtrim($ruta, '/');
    }
    if ($ruta === '') {
        $ruta = '/';
    }

    $consulta = canonica_limpiar_consulta($partes['query'] ?? '');

    // El fragmento nunca identifica un recurso distinto en el servidor.
    return $esquema . '://' . $host . $puerto . $ruta . ($consulta !== '' ? '?' . $consulta : '');
}

/**
 * Resuelve las variantes AMP: /articulo/amp, /amp/articulo y /articulo.amp.
 */
function canonica_limpiar_amp(string $ruta): string
{
    $ruta = preg_replace('#/amp/?$#i', '', $ruta);
    $ruta = preg_replace('#^/amp/#i', '/', $ruta);
    $ruta = preg_replace('#\.amp$#i', '', $ruta);

    return $ruta === '' ? '/' : $ruta;
}

/**
 * Deja en la cadena de consulta solo los parametros que de verdad identifican
 * el recurso, y los ordena para que dos URLs equivalentes den el mismo hash.
 */
function canonica_limpiar_consulta(string $consulta): string
{
    if ($consulta === '') {
        return '';
    }

    $basura = canonica_parametros_basura();
    $utiles = [];

    foreach (explode('&', $consulta) as $par) {
        if ($par === '') {
            continue;
        }

        $trozos = explode('=', $par, 2);
        $nombre = urldecode($trozos[0]);
        $valor  = isset($trozos[1]) ? urldecode($trozos[1]) : null;

        if ($nombre === '' || canonica_es_basura($nombre, $basura)) {
            continue;
        }

        $utiles[$nombre] = $valor;
    }

    if (!$utiles) {
        return '';
    }

    // El orden de los parametros no cambia el recurso, pero si cambiaria el
    // hash. Se ordenan para que ?a=1&b=2 y ?b=2&a=1 sean la misma URL.
    ksort($utiles);

    $partes = [];
    foreach ($utiles as $nombre => $valor) {
        $partes[] = $valor === null
            ? rawurlencode($nombre)
            : rawurlencode($nombre) . '=' . rawurlencode($valor);
    }

    return implode('&', $partes);
}

/**
 * Comprueba un nombre de parametro contra la lista de basura, admitiendo
 * comodines de prefijo del tipo utm_*.
 */
function canonica_es_basura(string $nombre, array $basura): bool
{
    $nombre = strtolower($nombre);

    foreach ($basura as $patron) {
        $patron = strtolower($patron);

        if (str_ends_with($patron, '*')) {
            if (str_starts_with($nombre, substr($patron, 0, -1))) {
                return true;
            }
        } elseif ($nombre === $patron) {
            return true;
        }
    }

    return false;
}

/**
 * Clave unica de un item: SHA-1 de la URL canonica, 40 caracteres.
 */
function canonica_hash(string $url_canonica): string
{
    return sha1($url_canonica);
}

/**
 * Resuelve una URL relativa contra la del feed. Algunos feeds publican
 * enlaces relativos y sin esto se perderian.
 */
function canonica_absoluta(string $url, string $base): string
{
    $url = trim($url);

    if ($url === '' || preg_match('#^https?://#i', $url)) {
        return $url;
    }

    $b = parse_url($base);
    if ($b === false || empty($b['host'])) {
        return $url;
    }

    $raiz = ($b['scheme'] ?? 'https') . '://' . $b['host'];

    if (str_starts_with($url, '//')) {
        return ($b['scheme'] ?? 'https') . ':' . $url;
    }
    if (str_starts_with($url, '/')) {
        return $raiz . $url;
    }

    $directorio = rtrim(dirname($b['path'] ?? '/'), '/');

    return $raiz . $directorio . '/' . $url;
}
