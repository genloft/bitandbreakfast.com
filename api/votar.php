<?php
/**
 * Un voto desde el correo: "¿te ha servido esta noticia?", sí o no.
 *
 * Un solo clic, sin pedir que confirme nada, igual que la baja: cualquier
 * paso de mas aqui es un paso que casi nadie da. El enlace va firmado con
 * HMAC de bit y suscriptor a la vez -no solo de bit-, para que dos
 * destinatarios nunca puedan chocar entre ellos y cada uno solo pueda
 * contar su propio voto una vez.
 *
 * Mismo riesgo que ya acepta api/baja.php y no evita: un escaner de correo
 * que abra los enlaces del cuerpo antes de que el destinatario lo lea
 * gastaria el voto de esa persona sin que ella hubiera pulsado nada. No se
 * intenta distinguir aqui -ir.php sí lo hace para los clics, pero ahi un
 * falso positivo es ruido en una puntuacion, y aqui seria un voto entero
 * perdido, asi que la proteccion real es otra: la clave unica de la tabla
 * hace que ese primer clic, sea de quien sea, sea el unico que cuenta, e
 * "ya has votado" es una respuesta tan tranquila como un voto de verdad.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/votos.php';
require_once __DIR__ . '/respuesta.php';

date_default_timezone_set('UTC');

$base = rtrim((string) config_opcional('sitio.url', ''), '/');

$bit_id        = (int) ($_GET['b'] ?? 0);
$suscriptor_id = (int) ($_GET['s'] ?? 0);
$valor         = (int) ($_GET['v'] ?? 0);
$firma         = (string) ($_GET['t'] ?? '');

$secreto = (string) config('secretos.secreto_hmac');

// Misma huella que ir.php para los clics: nunca la IP en si, y detras de un
// proxy compartido REMOTE_ADDR solo, sin X-Forwarded-For, ensenaria siempre
// la misma direccion para todo el mundo.
$ip = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
$ip = trim(explode(',', $ip)[0]);

$hash_ip = hash_hmac('sha256', $ip, (string) config('secretos.sal_hash'));

$resultado = votos_registrar($bit_id, $suscriptor_id, $valor, $firma, $secreto, $hash_ip);

if ($resultado === 'invalido') {
    api_responder(
        $base,
        'Ese enlace no vale',
        'No hemos podido registrar ese voto con este enlace.',
        400,
        'Volver a la portada'
    );
}

$mensaje = $valor > 0
    ? 'Gracias. Nos sirve saber qué noticias de verdad cambian algo en un hotel.'
    : 'Gracias por decirlo. Esta noticia no se repetirá en ese formato.';

api_responder(
    $base,
    $resultado === 'repetido' ? 'Ya teníamos tu voto' : 'Voto registrado',
    $resultado === 'repetido' ? 'Ya habíamos contado tu voto para esta noticia.' : $mensaje,
    200,
    'Ver la última edición'
);
