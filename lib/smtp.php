<?php
/**
 * Cliente SMTP minimo.
 *
 * Escrito a mano porque el proyecto no usa Composer en produccion y porque lo
 * que hace falta es poco: conectar, autenticarse, mandar un mensaje y colgar.
 * No pretende ser PHPMailer; pretende ser legible de arriba abajo.
 *
 * Lo que SI hace, porque sin ello el correo no llega o llega mal:
 *
 *   - TLS siempre: implicito en el 465, con STARTTLS en el 587. Un AUTH LOGIN
 *     sin cifrar manda la contrasena del buzon en claro por la red.
 *   - Verifica el certificado. Desactivarlo es tan comun como absurdo: sin
 *     verificacion, el cifrado no protege de nadie.
 *   - Mensaje multiparte: texto plano y HTML. El texto plano no es un adorno,
 *     es lo que leen los filtros antispam y lo que ven los clientes que no
 *     cargan HTML.
 *   - Cabeceras propias -Message-ID, Date, List-Unsubscribe-, que son las que
 *     separan un boletin legitimo de algo que acaba en la carpeta de basura.
 *
 * Lo que NO hace, y conviene saberlo: no reintenta, no encola y no lee
 * rebotes. Quien llama decide que hacer con un fallo.
 *
 * La contrasena del buzon no aparece en ningun registro: los errores que se
 * devuelven llevan el codigo y el texto del servidor, nunca la linea enviada.
 */

declare(strict_types=1);

/** Segundos de espera al conectar y en cada lectura. */
const SMTP_ESPERA = 20;

/**
 * Manda un mensaje. Es la unica funcion que usa el resto del proyecto.
 *
 * @param array $conf     host, puerto, usuario, clave, seguridad, remitente, nombre
 * @param array $mensaje  para, asunto, texto, html, cabeceras
 *
 * @return array ['ok' => bool, 'mensaje' => string]
 */
function smtp_enviar(array $conf, array $mensaje): array
{
    $host   = (string) ($conf['host'] ?? '');
    $puerto = (int) ($conf['puerto'] ?? 465);
    $para   = (string) ($mensaje['para'] ?? '');

    if ($host === '' || $para === '') {
        return ['ok' => false, 'mensaje' => 'Falta el servidor o el destinatario.'];
    }

    // Una direccion con salto de linea dentro es un intento de inyectar
    // cabeceras, y aqui es donde se corta.
    if (!smtp_direccion_limpia($para) || !smtp_direccion_limpia((string) ($conf['remitente'] ?? ''))) {
        return ['ok' => false, 'mensaje' => 'La dirección no es válida.'];
    }

    $contexto = stream_context_create([
        'ssl' => [
            'verify_peer'       => true,
            'verify_peer_name'  => true,
            'allow_self_signed' => false,
            'SNI_enabled'       => true,
        ],
    ]);

    $destino = ($puerto === 465 ? 'ssl://' : 'tcp://') . $host . ':' . $puerto;
    $socket  = @stream_socket_client(
        $destino,
        $errno,
        $errstr,
        SMTP_ESPERA,
        STREAM_CLIENT_CONNECT,
        $contexto
    );

    if ($socket === false) {
        return ['ok' => false, 'mensaje' => 'No se ha podido conectar con el servidor de correo.'];
    }

    stream_set_timeout($socket, SMTP_ESPERA);

    try {
        return smtp_conversacion($socket, $conf, $mensaje, $host, $puerto);
    } catch (Throwable $e) {
        return ['ok' => false, 'mensaje' => $e->getMessage()];
    } finally {
        @fclose($socket);
    }
}

/**
 * La conversacion entera, en el orden que manda el protocolo.
 *
 * @param resource $socket
 */
function smtp_conversacion($socket, array $conf, array $mensaje, string $host, int $puerto): array
{
    $yo = smtp_saludo($conf);

    smtp_esperar($socket, [220]);
    smtp_orden($socket, 'EHLO ' . $yo, [250]);

    // En el 587 la conexion empieza en claro y se cifra aqui. Si el servidor
    // no quiere, se abandona: mandar la contrasena en claro no es una opcion
    // de reserva, es una fuga.
    if ($puerto !== 465) {
        smtp_orden($socket, 'STARTTLS', [220]);

        $cifrado = @stream_socket_enable_crypto(
            $socket,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );

        if ($cifrado !== true) {
            throw new RuntimeException('El servidor de correo no ha aceptado cifrar la conexión.');
        }

        smtp_orden($socket, 'EHLO ' . $yo, [250]);
    }

    $usuario = (string) ($conf['usuario'] ?? '');
    $clave   = (string) ($conf['clave'] ?? '');

    if ($usuario !== '') {
        smtp_orden($socket, 'AUTH LOGIN', [334]);
        smtp_orden($socket, base64_encode($usuario), [334], 'el usuario');
        smtp_orden($socket, base64_encode($clave), [235], 'la contraseña');
    }

    $remitente = (string) ($conf['remitente'] ?? $usuario);

    smtp_orden($socket, 'MAIL FROM:<' . $remitente . '>', [250]);
    smtp_orden($socket, 'RCPT TO:<' . (string) $mensaje['para'] . '>', [250, 251]);
    smtp_orden($socket, 'DATA', [354]);

    // El cuerpo va sin esperar respuesta linea a linea: el servidor solo
    // contesta al punto final.
    smtp_escribir($socket, smtp_cuerpo($conf, $mensaje) . "\r\n.");
    smtp_esperar($socket, [250]);

    // QUIT sin comprobar: si el mensaje ya se ha aceptado, que el adios falle
    // no cambia nada.
    @fwrite($socket, "QUIT\r\n");

    return ['ok' => true, 'mensaje' => ''];
}

/**
 * Con que nombre se presenta el cliente en el EHLO.
 *
 * Tiene que ser un nombre de maquina, no una direccion de correo. Se usa el
 * dominio del remitente, que es lo unico que se sabe seguro.
 */
function smtp_saludo(array $conf): string
{
    $remitente = (string) ($conf['remitente'] ?? '');
    $arroba    = strrpos($remitente, '@');
    $dominio   = $arroba === false ? '' : substr($remitente, $arroba + 1);

    return preg_match('/^[a-z0-9.-]+$/i', $dominio) === 1 ? $dominio : 'localhost';
}

/**
 * Manda una orden y comprueba el codigo de respuesta.
 *
 * @param resource $socket
 * @param int[]    $esperados
 */
function smtp_orden($socket, string $orden, array $esperados, string $que = ''): string
{
    smtp_escribir($socket, $orden);

    return smtp_esperar($socket, $esperados, $que);
}

/**
 * Escribe una linea con el final que manda el protocolo.
 *
 * @param resource $socket
 */
function smtp_escribir($socket, string $linea): void
{
    if (@fwrite($socket, $linea . "\r\n") === false) {
        throw new RuntimeException('Se ha cortado la conexión con el servidor de correo.');
    }
}

/**
 * Lee la respuesta -que puede ser de varias lineas- y comprueba el codigo.
 *
 * @param resource $socket
 * @param int[]    $esperados
 */
function smtp_esperar($socket, array $esperados, string $que = ''): string
{
    $respuesta = '';

    while (true) {
        $linea = @fgets($socket, 1024);

        if ($linea === false) {
            $info = stream_get_meta_data($socket);

            throw new RuntimeException(!empty($info['timed_out'])
                ? 'El servidor de correo no ha contestado a tiempo.'
                : 'Se ha cortado la conexión con el servidor de correo.');
        }

        $respuesta .= $linea;

        // La ultima linea de una respuesta lleva un espacio tras el codigo;
        // las intermedias, un guion.
        if (strlen($linea) >= 4 && $linea[3] === ' ') {
            break;
        }
    }

    $codigo = (int) substr($respuesta, 0, 3);

    if (!in_array($codigo, $esperados, true)) {
        throw new RuntimeException(smtp_explicar($codigo, $respuesta, $que));
    }

    return $respuesta;
}

/**
 * Traduce el fallo a algo que se pueda leer sin saber SMTP.
 *
 * Nunca incluye lo que se envio: en el paso de la clave, eso seria la clave.
 */
function smtp_explicar(int $codigo, string $respuesta, string $que): string
{
    if ($codigo === 535 || $codigo === 534 || ($codigo === 501 && $que !== '')) {
        return 'El servidor de correo ha rechazado ' . ($que !== '' ? $que : 'las credenciales')
             . '. Revisa el usuario y la contraseña del buzón.';
    }

    if ($codigo === 550 || $codigo === 553) {
        return 'El servidor de correo ha rechazado la dirección (' . $codigo . ').';
    }

    if ($codigo === 421 || $codigo === 450 || $codigo === 451 || $codigo === 452) {
        return 'El servidor de correo no acepta más mensajes ahora mismo (' . $codigo . ').';
    }

    return 'El servidor de correo ha respondido ' . $codigo . '.';
}

/**
 * ¿Es una direccion sin nada raro dentro?
 *
 * Lo que importa aqui no es si el buzon existe -eso lo dira el servidor- sino
 * que no lleve saltos de linea, que es como se inyectan cabeceras ajenas en
 * un correo.
 */
function smtp_direccion_limpia(string $direccion): bool
{
    if ($direccion === '' || strlen($direccion) > 254) {
        return false;
    }

    if (preg_match('/[\r\n\0<>,;]/', $direccion) === 1) {
        return false;
    }

    return filter_var($direccion, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Codifica una cabecera con acentos segun el RFC 2047.
 *
 * Un asunto en espanol lleva acentos casi siempre, y un asunto con acentos sin
 * codificar se ve como "EdiciÃ³n" en la mitad de los clientes.
 */
function smtp_cabecera_codificada(string $texto): string
{
    $texto = trim((string) preg_replace('/[\r\n]+/', ' ', $texto));

    if (preg_match('/^[\x20-\x7E]*$/', $texto) === 1) {
        return $texto;
    }

    return '=?UTF-8?B?' . base64_encode($texto) . '?=';
}

/**
 * El mensaje entero: cabeceras y cuerpo multiparte.
 *
 * Funcion pura, para poder comprobarla sin levantar un servidor de correo.
 */
function smtp_cuerpo(array $conf, array $mensaje): string
{
    $remitente = (string) ($conf['remitente'] ?? '');
    $nombre    = trim((string) ($conf['nombre'] ?? ''));
    $texto     = (string) ($mensaje['texto'] ?? '');
    $html      = (string) ($mensaje['html'] ?? '');
    $frontera  = 'bitb-' . bin2hex(random_bytes(12));

    $de = $nombre !== ''
        ? smtp_cabecera_codificada($nombre) . ' <' . $remitente . '>'
        : $remitente;

    $cabeceras = [
        'Date: ' . gmdate('D, d M Y H:i:s') . ' +0000',
        'From: ' . $de,
        'To: ' . (string) $mensaje['para'],
        'Subject: ' . smtp_cabecera_codificada((string) ($mensaje['asunto'] ?? '')),
        'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . smtp_saludo($conf) . '>',
        'MIME-Version: 1.0',
    ];

    foreach ((array) ($mensaje['cabeceras'] ?? []) as $extra) {
        $extra = trim((string) preg_replace('/[\r\n]+/', ' ', (string) $extra));

        if ($extra !== '') {
            $cabeceras[] = $extra;
        }
    }

    if ($html === '') {
        $cabeceras[] = 'Content-Type: text/plain; charset=UTF-8';
        $cabeceras[] = 'Content-Transfer-Encoding: base64';

        return implode("\r\n", $cabeceras) . "\r\n\r\n" . smtp_base64($texto);
    }

    $cabeceras[] = 'Content-Type: multipart/alternative; boundary="' . $frontera . '"';

    $partes = [
        '--' . $frontera,
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
        '',
        smtp_base64($texto),
        '--' . $frontera,
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
        '',
        smtp_base64($html),
        '--' . $frontera . '--',
    ];

    return implode("\r\n", $cabeceras) . "\r\n\r\n" . implode("\r\n", $partes);
}

/**
 * Base64 en lineas cortas.
 *
 * Se codifica todo el cuerpo en base64 y no en quoted-printable por un motivo
 * practico: asi ninguna linea puede pasarse del limite del protocolo ni
 * empezar por un punto, que son los dos accidentes clasicos que cortan un
 * correo por la mitad.
 */
function smtp_base64(string $texto): string
{
    return trim(chunk_split(base64_encode($texto), 76, "\r\n"));
}
