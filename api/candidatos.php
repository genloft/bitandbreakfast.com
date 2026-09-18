<?php
/**
 * La cola de curación, en JSON.
 *
 * `secretos.token_api` lleva en `config/config.ejemplo.php` desde antes de
 * que existiera este fichero, con el comentario "cabecera de
 * api/candidatos.php y api/bits.php": un token generado y guardado por el
 * instalador para dos endpoints que nunca llegaron a escribirse. Este es el
 * primero de los dos -el de solo lectura-.
 *
 * Sirve para mirar la cola sin entrar al panel: quien quiera revisar
 * candidatos desde otro sitio -un móvil, una hoja, una herramienta de
 * redacción asistida que ayude a escribir el bit antes de pegarlo en el
 * panel- necesita los mismos datos que ya pinta panel/index.php, pero en un
 * formato que no sea HTML pensado para un navegador.
 *
 * api/bits.php -crear o publicar un bit por API, no solo leerlo- se queda
 * sin escribir a propósito: escribir en la cola de publicación es un cambio
 * de proceso editorial, no solo una función más, y ese es exactamente el
 * tipo de decisión que este sitio reserva para una persona.
 *
 * Autenticación por cabecera, no por sesión: esto no lo abre un navegador
 * con una cookie de panel/, lo abre una herramienta aparte. El mismo token
 * que ya genera el instalador y que hasta ahora no leía nadie.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/panel/datos.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

/**
 * El token de la cabecera Authorization: Bearer <token>, o cadena vacia si
 * no viene con esa forma.
 *
 * REDIRECT_HTTP_AUTHORIZATION ademas de HTTP_AUTHORIZATION: en un hosting
 * compartido con PHP como CGI, Apache no siempre deja pasar Authorization
 * con su nombre normal, y esta es la variable con la que sobrevive un
 * cambio de contexto interno.
 */
function candidatos_token_cabecera(): string
{
    $cabecera = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

    if (!str_starts_with($cabecera, 'Bearer ')) {
        return '';
    }

    return substr($cabecera, 7);
}

function candidatos_salir(int $codigo, string $error): void
{
    http_response_code($codigo);
    echo json_encode(['error' => $error], JSON_UNESCAPED_UNICODE), "\n";
    exit;
}

$token = (string) config('secretos.token_api');

if ($token === '' || !hash_equals($token, candidatos_token_cabecera())) {
    candidatos_salir(401, 'Falta un token valido en la cabecera Authorization: Bearer <token>.');
}

$limite = (int) ($_GET['limite'] ?? 60);
$limite = max(1, min(60, $limite));

// El formateo vive en panel/datos.php, no aqui: es la misma cola que ya
// consulta panel/index.php, y asi solo hay un sitio que tocar el dia que
// cambie la forma de un candidato.
$candidatos = array_map(
    static fn (array $racimo): array => datos_formatear_candidato($racimo, datos_items_racimo((int) $racimo['id'])),
    datos_cola($limite)
);

echo json_encode(['candidatos' => $candidatos], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
