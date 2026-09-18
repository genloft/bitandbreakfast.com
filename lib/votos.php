<?php
/**
 * Los votos del correo: "¿te ha servido esta noticia?", sí o no.
 *
 * La tabla `votos` llevaba en el esquema desde la fase 6 sin que nada la
 * escribiera -"Fichas de proveedor, buscador, votos, redacción asistida" en
 * docs/README.md, con "votos" como la unica pieza pendiente-. Este fichero
 * es lo que le falta: firmar el enlace de cada bit para cada destinatario y
 * comprobar esa firma cuando alguien pulsa.
 *
 * El token tiene que ser distinto por destinatario, no solo por bit: sin
 * eso, la clave unica (bit_id, token) dejaria un solo voto por bit en todo
 * el mundo, y el primero en pulsar decidiria por el resto de la lista. Por
 * eso la firma es HMAC de bit y suscriptor a la vez, y ese mismo HMAC hace
 * de token: no hace falta guardar el id del suscriptor en la tabla, y
 * pulsar el mismo enlace dos veces no cuenta dos votos, porque la segunda
 * insercion choca con la clave unica y se trata como "ya has votado", no
 * como un error.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

/** Los dos valores que puede llevar un voto. Nada intermedio a propósito:
 *  un tercer botón "regular" no se puede resumir en un panel. */
const VOTOS_VALORES = [1, -1];

/**
 * La firma de un bit para un destinatario. El mismo HMAC hace de token en
 * la tabla `votos`, así que dos destinatarios nunca pueden chocar entre
 * ellos, y el mismo destinatario pulsando dos veces el mismo botón sí.
 */
function votos_firma(int $bit_id, int $suscriptor_id, string $secreto): string
{
    return substr(hash_hmac('sha256', 'voto:' . $bit_id . ':' . $suscriptor_id, $secreto), 0, 32);
}

/**
 * El enlace completo que lleva el correo, listo para pegar en un
 * <a href>. $valor tiene que ser uno de VOTOS_VALORES.
 */
function votos_url(string $base, int $bit_id, int $suscriptor_id, int $valor, string $secreto): string
{
    return rtrim($base, '/') . '/api/votar.php?b=' . $bit_id . '&s=' . $suscriptor_id
        . '&v=' . $valor . '&t=' . votos_firma($bit_id, $suscriptor_id, $secreto);
}

/**
 * ¿Es valido este voto? Pura -sin base de datos-, para poder probarla sin
 * montar nada. Comprueba la forma antes que la firma: una firma valida
 * sobre datos sin sentido no deberia poder llegar a la base.
 */
function votos_valido(int $bit_id, int $suscriptor_id, int $valor, string $firma, string $secreto): bool
{
    if ($bit_id <= 0 || $suscriptor_id <= 0 || $firma === '') {
        return false;
    }

    if (!in_array($valor, VOTOS_VALORES, true)) {
        return false;
    }

    return hash_equals(votos_firma($bit_id, $suscriptor_id, $secreto), $firma);
}

/**
 * Registra un voto, o dice por que no.
 *
 * @return string 'ok', 'repetido' (ya habia un voto de este destinatario
 *                para este bit) o 'invalido' (firma o datos que no cuadran).
 */
function votos_registrar(
    int $bit_id,
    int $suscriptor_id,
    int $valor,
    string $firma,
    string $secreto,
    string $hash_ip
): string {
    if (!votos_valido($bit_id, $suscriptor_id, $valor, $firma, $secreto)) {
        return 'invalido';
    }

    try {
        bd()->prepare('INSERT INTO votos (bit_id, valor, token, hash_ip) VALUES (?, ?, ?, ?)')
            ->execute([$bit_id, $valor, $firma, $hash_ip]);
    } catch (PDOException $e) {
        // 23000: violacion de la clave unica (bit_id, token). No es un
        // fallo, es la misma persona pulsando el mismo enlace otra vez.
        if ($e->getCode() === '23000') {
            return 'repetido';
        }

        throw $e;
    }

    return 'ok';
}
