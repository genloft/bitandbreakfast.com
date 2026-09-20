<?php
/**
 * Alta de suscriptores contra el proveedor de correo.
 *
 * El proyecto nacio sin guardar ni una direccion de correo: la lista vivia
 * entera en el proveedor, que ya sabe gestionar bajas, rebotes y doble
 * confirmacion mejor de lo que se puede escribir aqui. Sigue siendo la mejor
 * opcion y por eso sigue estando.
 *
 * Pero el correo que hay es un buzon SMTP del propio alojamiento, no una
 * cuenta de proveedor, y con un buzon SMTP la lista no puede vivir en ningun
 * otro sitio. De ahi el tercer proveedor, 'propio', que guarda lo minimo en la
 * tabla suscriptores y manda los correos el mismo. Lo que no cambia es la
 * promesa: doble confirmacion siempre, baja en un clic desde cualquier envio,
 * y ni un dato mas de los que hacen falta para escribir.
 *
 * Tres proveedores, uno u otro segun config/config.php:
 *
 *   propio      No sale a ninguna API: guarda el alta como pendiente, manda
 *               el correo de confirmacion por SMTP y espera al clic. Es el que
 *               se usa cuando hay buzon propio configurado.
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

// La lista propia vive aparte para que este fichero siga siendo legible:
// aqui estan los proveedores, alli la tabla y los correos.
require_once __DIR__ . '/lista.php';

/** Segundos de espera de la peticion al proveedor. */
const CORREO_TIMEOUT = 10;

/**
 * La seccion 'correo' de la configuracion, con los valores que faltan a cero.
 */
function correo_conf(): array
{
    $conf = config('correo') ?? [];

    // El buzon propio se guarda aparte, en config/correo.php, porque lo
    // escribe el panel: asi un error escribiendo ese fichero no puede
    // llevarse por delante la configuracion de la base de datos.
    $buzon = correo_buzon();

    return [
        // Si hay buzon configurado, manda el buzon. Es lo que hay puesto de
        // verdad, y preguntarle al dueno del sitio "y ahora pon proveedor:
        // propio" seria una tarea mas sin ninguna razon.
        'proveedor'     => (string) ($conf['proveedor'] ?? ($buzon['host'] !== '' ? 'propio' : 'mailerlite')),
        'api_key'       => (string) ($conf['api_key'] ?? ''),
        'lista'         => (string) ($conf['lista'] ?? ''),
        'doi_plantilla' => (int) ($conf['doi_plantilla'] ?? 0),
        'remitente'     => (string) ($conf['remitente'] ?? $buzon['remitente']),
        'nombre'        => (string) ($conf['nombre'] ?? 'Bit & Breakfast'),
        'host'          => $buzon['host'],
        'puerto'        => $buzon['puerto'],
        'usuario'       => $buzon['usuario'],
        'clave'         => $buzon['clave'],
    ];
}

/**
 * Los datos del buzon SMTP, que viven en su propio fichero.
 *
 * config/correo.php no esta en el repositorio y lo escribe el panel. Si no
 * existe, todo queda a cero y el alta simplemente no esta abierta.
 */
function correo_buzon(): array
{
    static $buzon = null;

    if ($buzon !== null) {
        return $buzon;
    }

    $ruta  = dirname(__DIR__) . '/config/correo.php';
    $datos = is_readable($ruta) ? @require $ruta : [];
    $datos = is_array($datos) ? $datos : [];

    $buzon = [
        'host'      => (string) ($datos['host'] ?? ''),
        'puerto'    => (int) ($datos['puerto'] ?? 465),
        'usuario'   => (string) ($datos['usuario'] ?? ''),
        'clave'     => (string) ($datos['clave'] ?? ''),
        'remitente' => (string) ($datos['remitente'] ?? ($datos['usuario'] ?? '')),
    ];

    return $buzon;
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

    if ($conf['proveedor'] === 'propio') {
        // Con buzon propio hacen falta las cuatro cosas: sin cualquiera de
        // ellas el correo de confirmacion no sale, y un alta cuya confirmacion
        // no sale es peor que no tener alta.
        return $conf['host'] !== ''
            && $conf['usuario'] !== ''
            && $conf['clave'] !== ''
            && correo_valido($conf['remitente']);
    }

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
 * Guarda los datos del buzon en config/correo.php.
 *
 * Lo escribe el panel, nunca nadie desde fuera, y por eso no vive en la base
 * de datos: una contrasena de buzon en una tabla se acaba copiando en un
 * volcado, y un volcado se acaba mandando por correo a alguien. En un fichero
 * de configuracion esta donde estan las demas credenciales del sitio.
 *
 * Se escribe con rename sobre un temporal, como todo lo que escribe este
 * proyecto: un fichero de configuracion a medias deja el sitio sin correo y,
 * peor, sin poder arreglarlo desde el panel.
 *
 * @return array ['ok' => bool, 'mensaje' => string]
 */
function correo_guardar_buzon(array $datos): array
{
    $host      = trim((string) ($datos['host'] ?? ''));
    $puerto    = (int) ($datos['puerto'] ?? 465);
    $usuario   = trim((string) ($datos['usuario'] ?? ''));
    $clave     = (string) ($datos['clave'] ?? '');
    $remitente = correo_normalizar((string) ($datos['remitente'] ?? $usuario));

    if (!preg_match('/^[a-z0-9.-]+$/i', $host)) {
        return ['ok' => false, 'mensaje' => 'El servidor no parece un nombre de máquina.'];
    }

    if (!in_array($puerto, [465, 587, 25, 2525], true)) {
        return ['ok' => false, 'mensaje' => 'El puerto tiene que ser 465 o 587.'];
    }

    if (!correo_valido($remitente)) {
        return ['ok' => false, 'mensaje' => 'El remitente no es una dirección válida.'];
    }

    if ($usuario === '' || $clave === '') {
        return ['ok' => false, 'mensaje' => 'Hacen falta el usuario y la contraseña del buzón.'];
    }

    $ruta      = dirname(__DIR__) . '/config/correo.php';
    $temporal  = $ruta . '.' . getmypid();
    // Las lineas sueltas y unidas con PHP_EOL, que es mas facil de leer -y de
    // no romper- que una cadena con saltos dentro.
    $contenido = implode(PHP_EOL, [
        '<?php',
        '/**',
        ' * Datos del buzon de correo.',
        ' *',
        ' * Lo escribe el panel: no se edita a mano y no esta en el repositorio. Si',
        ' * cambias la contrasena del buzon, cambiala tambien aqui desde el panel.',
        ' */',
        '',
        'return [',
        "    'host'      => " . var_export($host, true) . ',',
        "    'puerto'    => " . var_export($puerto, true) . ',',
        "    'usuario'   => " . var_export($usuario, true) . ',',
        "    'clave'     => " . var_export($clave, true) . ',',
        "    'remitente' => " . var_export($remitente, true) . ',',
        '];',
        '',
    ]);

    if (@file_put_contents($temporal, $contenido) === false) {
        return ['ok' => false, 'mensaje' => 'No se ha podido escribir config/correo.php. Revisa los permisos de la carpeta.'];
    }

    // Antes de moverlo: que no lo pueda leer nadie mas que el propio sitio.
    @chmod($temporal, 0600);

    if (!@rename($temporal, $ruta)) {
        @unlink($temporal);

        return ['ok' => false, 'mensaje' => 'No se ha podido reemplazar config/correo.php.'];
    }

    return ['ok' => true, 'mensaje' => ''];
}

/**
 * Manda un correo de prueba, para no descubrir que algo falla el martes.
 *
 * @return array ['ok' => bool, 'mensaje' => string]
 */
function correo_probar(string $destino): array
{
    $conf = correo_conf();

    if (!correo_configurado($conf) || $conf['proveedor'] !== 'propio') {
        return ['ok' => false, 'mensaje' => 'Todavía faltan datos del buzón.'];
    }

    if (!correo_valido($destino)) {
        return ['ok' => false, 'mensaje' => 'Esa dirección de prueba no es válida.'];
    }

    require_once __DIR__ . '/smtp.php';

    $envio = smtp_enviar($conf, [
        'para'   => $destino,
        'asunto' => 'Prueba de Bit & Breakfast',
        'texto'  => 'Si lees esto, el buzon esta bien configurado y el boletin puede salir.',
        'html'   => '<p>Si lees esto, el buzón está bien configurado y el boletín puede salir.</p>',
        'cabeceras' => ['Auto-Submitted: auto-generated'],
    ]);

    return $envio;
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
 * $temas -slugs de bits_categorias() separados por comas, vacio para todos-
 * y $alerta -terminos libres separados por comas, vacio para ninguno- solo
 * tienen efecto con el buzon propio: MailerLite y Brevo llevan su propia
 * lista y su propia segmentacion, ajena a las tablas de este sitio.
 *
 * @return array ['ok' => bool, 'mensaje' => string]
 */
function correo_alta(string $email, string $url_vuelta = '', string $temas = '', string $alerta = ''): array
{
    $conf = correo_conf();

    if (!correo_configurado($conf)) {
        return ['ok' => false, 'mensaje' => 'El alta todavía no está abierta.'];
    }

    if ($conf['proveedor'] === 'propio') {
        return correo_alta_propia($email, $conf, $url_vuelta, $temas, $alerta);
    }

    [$codigo, $cuerpo] = correo_peticion(correo_carga_alta($email, $conf, $url_vuelta));

    if ($codigo === 0) {
        return ['ok' => false, 'mensaje' => 'No se ha podido contactar con el proveedor de correo. Inténtalo más tarde.'];
    }

    return correo_interpretar($codigo, $cuerpo);
}
