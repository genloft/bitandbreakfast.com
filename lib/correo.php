<?php
/**
 * Alta de suscriptores contra el proveedor de correo.
 *
 * El proyecto no guarda ni una direccion de correo en su base de datos: la
 * lista vive entera en el proveedor. Es la decision mas barata y la mas
 * segura, porque una base de datos en alojamiento compartido no es sitio para
 * una lista de correos, y porque el proveedor ya sabe gestionar bajas,
 * rebotes y doble confirmacion mejor de lo que se puede escribir aqui.
 *
 * Dos proveedores, uno u otro segun config/config.php:
 *
 *   mailerlite  POST https://connect.mailerlite.com/api/subscribers
 *               Authorization: Bearer <clave>
 *               El alta se crea con status "unconfirmed" y es el propio
 *               MailerLite quien manda el correo de confirmacion, siempre que
 *               el grupo tenga activada la doble confirmacion en su panel.
 *
 *   brevo       POST https://api.brevo.com/v3/contacts/doubleOptinConfirmation
 *               api-key: <clave>
 *               Brevo exige ademas el identificador de la plantilla de
 *               confirmacion y la URL de vuelta.
 *
 * Las cargas utiles se construyen en funciones puras, sin red, para que se
 * puedan comprobar en las pruebas: un cambio de nombre de campo no se ve
 * hasta que alguien intenta suscribirse de verdad.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

/** Segundos de espera de la peticion al proveedor. */
const CORREO_TIMEOUT = 10;

/**
 * La seccion 'correo' de la configuracion, con los valores que faltan a cero.
 */
function correo_conf(): array
{
    $conf = config('correo') ?? [];

    return [
        'proveedor'     => (string) ($conf['proveedor'] ?? 'mailerlite'),
        'api_key'       => (string) ($conf['api_key'] ?? ''),
        'lista'         => (string) ($conf['lista'] ?? ''),
        'doi_plantilla' => (int) ($conf['doi_plantilla'] ?? 0),
        'remitente'     => (string) ($conf['remitente'] ?? ''),
    ];
}

/**
 * ¿Se puede dar de alta a alguien ahora mismo?
 *
 * Sin clave no hay alta, y sin lista tampoco: dar de alta en ninguna lista es
 * perder el correo del lector sin decirselo.
 */
function correo_configurado(?array $conf = null): bool
{
    $conf = $conf ?? correo_conf();

    if ($conf['api_key'] === '' || $conf['lista'] === '') {
        return false;
    }

    // Brevo manda el correo de confirmacion con una plantilla suya y no
    // tiene valor por defecto: sin ella, el alta se queda a medias.
    if ($conf['proveedor'] === 'brevo' && $conf['doi_plantilla'] <= 0) {
        return false;
    }

    return true;
}

/**
 * Normaliza una direccion: sin espacios y con el dominio en minusculas.
 *
 * La parte local NO se toca. Segun el estandar distingue mayusculas, y aunque
 * casi ningun servidor lo aproveche, no es cosa nuestra decidirlo.
 */
function correo_normalizar(string $email): string
{
    $email = trim($email);
    $arroba = strrpos($email, '@');

    if ($arroba === false) {
        return $email;
    }

    return substr($email, 0, $arroba) . '@' . strtolower(substr($email, $arroba + 1));
}

/**
 * Validacion de la direccion.
 *
 * filter_var y poco mas: comprobar si el buzon existe no se puede hacer desde
 * aqui, y para eso esta justamente la doble confirmacion.
 */
function correo_valido(string $email): bool
{
    if ($email === '' || strlen($email) > 254) {
        return false;
    }

    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return false;
    }

    // Un salto de linea en la direccion es un intento de inyectar cabeceras.
    return !preg_match('/[\r\n\t]/', $email);
}

/**
 * Construye la peticion de alta. Funcion pura: no toca la red.
 *
 * @return array ['url' => string, 'cabeceras' => string[], 'cuerpo' => string]
 */
function correo_carga_alta(string $email, array $conf, string $url_vuelta = ''): array
{
    if ($conf['proveedor'] === 'brevo') {
        return [
            'url' => 'https://api.brevo.com/v3/contacts/doubleOptinConfirmation',
            'cabeceras' => [
                'api-key: ' . $conf['api_key'],
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            'cuerpo' => (string) json_encode([
                'email'          => $email,
                'includeListIds' => [(int) $conf['lista']],
                'templateId'     => (int) $conf['doi_plantilla'],
                'redirectionUrl' => $url_vuelta,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];
    }

    return [
        'url' => 'https://connect.mailerlite.com/api/subscribers',
        'cabeceras' => [
            'Authorization: Bearer ' . $conf['api_key'],
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        'cuerpo' => (string) json_encode([
            'email'  => $email,
            // El estado es lo que dispara la doble confirmacion: el alta queda
            // pendiente hasta que el lector pulsa el enlace del correo.
            'status' => 'unconfirmed',
            'groups' => [$conf['lista']],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ];
}

/**
 * Interpreta la respuesta del proveedor.
 *
 * Los dos devuelven 201 al crear y 200 al actualizar uno que ya existia. Se
 * tratan igual a proposito: al lector que se suscribe dos veces hay que
 * decirle lo mismo que al que se suscribe una, porque lo contrario delata
 * quien esta en la lista.
 *
 * @return array ['ok' => bool, 'mensaje' => string]
 */
function correo_interpretar(int $codigo, string $cuerpo): array
{
    if ($codigo === 200 || $codigo === 201 || $codigo === 202) {
        return ['ok' => true, 'mensaje' => ''];
    }

    if ($codigo === 422 || $codigo === 400) {
        return ['ok' => false, 'mensaje' => 'La dirección no parece válida para el proveedor de correo.'];
    }

    if ($codigo === 401 || $codigo === 403) {
        return ['ok' => false, 'mensaje' => 'El alta no está bien configurada. Avisa al administrador.'];
    }

    if ($codigo === 429) {
        return ['ok' => false, 'mensaje' => 'Demasiadas altas seguidas. Inténtalo dentro de un rato.'];
    }

    $detalle = trim(substr($cuerpo, 0, 200));

    return [
        'ok'      => false,
        'mensaje' => 'El proveedor de correo ha respondido ' . $codigo . ($detalle !== '' ? ': ' . $detalle : '.'),
    ];
}

/**
 * Ejecuta la peticion. Devuelve [codigo, cuerpo].
 */
function correo_peticion(array $carga): array
{
    $ch = curl_init($carga['url']);

    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $carga['cuerpo'],
        CURLOPT_HTTPHEADER     => $carga['cabeceras'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => CORREO_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $cuerpo = curl_exec($ch);
    $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error  = curl_error($ch);

    curl_close($ch);

    if ($cuerpo === false) {
        // El detalle de curl va al registro, no al lector: puede llevar la URL
        // del proveedor y no le dice nada a quien solo queria suscribirse.
        error_log('Bit & Breakfast, alta de correo: ' . $error);

        return [0, ''];
    }

    return [$codigo, (string) $cuerpo];
}

/**
 * Da de alta una direccion. Es la unica funcion que usa api/suscribir.php.
 *
 * @return array ['ok' => bool, 'mensaje' => string]
 */
function correo_alta(string $email, string $url_vuelta = ''): array
{
    $conf = correo_conf();

    if (!correo_configurado($conf)) {
        return ['ok' => false, 'mensaje' => 'El alta todavía no está abierta.'];
    }

    [$codigo, $cuerpo] = correo_peticion(correo_carga_alta($email, $conf, $url_vuelta));

    if ($codigo === 0) {
        return ['ok' => false, 'mensaje' => 'No se ha podido contactar con el proveedor de correo. Inténtalo más tarde.'];
    }

    return correo_interpretar($codigo, $cuerpo);
}
