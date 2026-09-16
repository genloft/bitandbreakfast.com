<?php
/**
 * Redireccion contada hacia la fuente original de un bit.
 *
 * Los enlaces de la web y del correo no apuntan directamente al medio, sino
 * aqui. Se cuenta el clic y se redirige. Sin esto no hay forma de saber que
 * bits se leen, y sin saberlo la puntuacion nunca deja de ser una teoria.
 *
 * El enlace va firmado con HMAC. No es por secretismo: es que sin firma este
 * endpoint seria un redirector abierto, y un redirector abierto en un dominio
 * con reputacion de correo se convierte en munición para phishing en cuestion
 * de semanas. Con firma, solo redirige a donde el propio sitio ha decidido.
 *
 * Lo que se guarda de quien pulsa: nada. Ni IP, ni identificador. Solo el
 * HMAC del agente de usuario, y unicamente para descontar los prefetch de
 * Gmail y los escaneres de correo, que pulsan todos los enlaces de un envio
 * en el mismo segundo.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/db.php';

date_default_timezone_set('UTC');

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');

/**
 * Firma de un bit. La misma funcion la usa el generador para escribir el
 * enlace, asi que vive en lib/ y no aqui.
 */
require_once dirname(__DIR__) . '/lib/web.php';

$bit_id = (int) ($_GET['b'] ?? 0);
$firma  = (string) ($_GET['t'] ?? '');

if ($bit_id <= 0 || $firma === '') {
    ir_fuera(400, 'Enlace incompleto.');
}

if (!hash_equals(web_firma_clic($bit_id, (string) config('secretos.secreto_hmac')), $firma)) {
    // Firma mala: o el enlace se ha manipulado, o los secretos han cambiado
    // despues de enviar el correo. En los dos casos, no se redirige.
    ir_fuera(403, 'Este enlace no es válido.');
}

$sql = "SELECT b.id, b.edicion_id,
               (SELECT i.url FROM items i
                 WHERE i.racimo_id = b.racimo_id AND i.estado <> 'descartado'
                 ORDER BY i.puntuacion DESC, i.id ASC LIMIT 1) AS url
          FROM bits b
         WHERE b.id = ? AND b.estado = 'publicado'";

$st = bd()->prepare($sql);
$st->execute([$bit_id]);
$bit = $st->fetch();

if (!$bit || empty($bit['url'])) {
    ir_fuera(404, 'Ese bit ya no tiene fuente que enseñar.');
}

// El destino sale de la base, no de la URL, asi que no hay nada que validar
// contra una lista: solo puede ser una direccion que la ingesta guardo.
$destino = (string) $bit['url'];

try {
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

    $sql = 'INSERT INTO clics (bit_id, edicion_id, hash_ua, sospechoso) VALUES (?, ?, ?, ?)';
    bd()->prepare($sql)->execute([
        (int) $bit['id'],
        $bit['edicion_id'] !== null ? (int) $bit['edicion_id'] : null,
        hash_hmac('sha256', $ua, (string) config('secretos.sal_hash')),
        ir_sospechoso($ua) ? 1 : 0,
    ]);
} catch (Throwable $e) {
    // Contar es secundario: si la base falla, el lector va igualmente a su
    // noticia. Lo que no puede es quedarse sin ella por una estadistica.
    error_log('Bit & Breakfast, clic no contado: ' . $e->getMessage());
}

header('Location: ' . $destino, true, 302);
exit;

/**
 * Marca los clics que casi seguro no son de un humano.
 *
 * Gmail y varios antivirus de correo abren todos los enlaces de un envio para
 * comprobarlos. No se descartan, se marcan: descartarlos por su cuenta seria
 * decidir con una lista de cadenas que envejece sola.
 */
function ir_sospechoso(string $ua): bool
{
    if ($ua === '') {
        return true;
    }

    return (bool) preg_match('/bot|crawl|spider|preview|scan|monitor|proxy|fetch/i', $ua);
}

/**
 * Sale con un mensaje corto y sin adornos. Quien llega aqui con un enlace
 * roto no necesita una pagina bonita, necesita entender que ha pasado.
 */
function ir_fuera(int $codigo, string $mensaje): void
{
    http_response_code($codigo);
    header('Content-Type: text/plain; charset=utf-8');

    echo $mensaje, "\n";
    exit;
}
