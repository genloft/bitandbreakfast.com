<?php
/**
 * Alta en el boletin, con doble confirmacion.
 *
 * Es el unico punto del sitio publico que acepta un POST de cualquiera, asi
 * que lleva cuatro cerrojos:
 *
 *   1. Solo POST.
 *   2. Trampa para robots: un campo oculto que un humano nunca rellena.
 *   3. Limite por IP, contado en ficheros dentro de cache/. La IP no se
 *      guarda: se guarda su HMAC con el secreto del sitio, que sirve para
 *      contar y no para saber quien es.
 *   4. La direccion se valida antes de salir a la red.
 *
 * Quien decide de verdad si el lector entra en la lista es el correo de
 * confirmacion que manda el proveedor. Aqui no se guarda ninguna direccion:
 * la lista vive entera en el proveedor.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/web.php';
require_once dirname(__DIR__) . '/lib/correo.php';

/** Altas permitidas por IP y hora. */
const ALTA_LIMITE = 5;

date_default_timezone_set('UTC');

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

$config = config();
$base   = rtrim((string) ($config['sitio']['url'] ?? ''), '/');

/**
 * Cuenta los intentos recientes de una IP y dice si se ha pasado.
 *
 * Un fichero por huella, con las marcas de tiempo de la ultima hora. En
 * alojamiento compartido no hay Redis ni memoria compartida, y una tabla en
 * la base para esto seria escribir en disco igual pero con mas pasos.
 */
function alta_pasada_de_vueltas(string $huella): bool
{
    $carpeta = (string) (config('rutas.cache') ?? dirname(__DIR__) . '/cache') . '/altas';

    if (!is_dir($carpeta) && !@mkdir($carpeta, 0755, true) && !is_dir($carpeta)) {
        return false;   // sin sitio donde contar, mejor dejar pasar que cerrar
    }

    $ruta   = $carpeta . '/' . $huella . '.txt';
    $ahora  = time();
    $marcas = [];

    if (is_file($ruta)) {
        foreach (explode("\n", (string) @file_get_contents($ruta)) as $linea) {
            $marca = (int) trim($linea);

            if ($marca > 0 && $ahora - $marca < 3600) {
                $marcas[] = $marca;
            }
        }
    }

    if (count($marcas) >= ALTA_LIMITE) {
        return true;
    }

    $marcas[] = $ahora;
    @file_put_contents($ruta, implode("\n", $marcas), LOCK_EX);

    return false;
}

/**
 * Huella de la IP: HMAC con el secreto del sitio, truncado.
 *
 * Sirve para contar intentos y no para identificar a nadie. Sin el secreto no
 * se puede rehacer, y el secreto no sale del servidor.
 */
function alta_huella(): string
{
    $ip = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
    $ip = trim(explode(',', $ip)[0]);

    return substr(hash_hmac('sha256', $ip, (string) (config('secretos.secreto_hmac') ?? 'sin-secreto')), 0, 32);
}

/**
 * Pinta la respuesta con el mismo aspecto que el resto del sitio y termina.
 */
function alta_responder(string $titulo, string $mensaje, int $codigo = 200): void
{
    global $base;

    http_response_code($codigo);
    header('Content-Type: text/html; charset=utf-8');

    $enlace_activo = '';

    echo '<!doctype html>', "\n";
    echo '<html lang="es">', "\n";
    echo '<head>', "\n";
    echo '<meta charset="utf-8">', "\n";
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">', "\n";
    echo '<meta name="robots" content="noindex,nofollow">', "\n";
    echo '<title>', web_e($titulo), ' · Bit &amp; Breakfast</title>', "\n";
    echo '<link rel="stylesheet" href="', web_e($base), '/estilo.css">', "\n";
    echo '</head>', "\n<body>\n";

    require dirname(__DIR__) . '/plantillas/web/cabecera.php';

    echo '<main id="contenido">', "\n";
    echo '<header class="edicion-cabecera"><h1>', web_e($titulo), '</h1></header>', "\n";
    echo '<p class="alta-respuesta">', web_e($mensaje), '</p>', "\n";
    echo '<p><a href="', web_e($base), '/">Volver a la última edición</a></p>', "\n";
    echo '</main>', "\n";

    require dirname(__DIR__) . '/plantillas/web/pie.php';

    echo "\n</body>\n</html>\n";

    exit;
}

// -----------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    alta_responder(
        'Aquí no hay nada',
        'Esta dirección solo atiende el formulario de alta.',
        405
    );
}

// La trampa: si viene rellena, es un robot. Se le contesta que todo ha ido
// bien, porque decirle que ha fallado solo le ensena a intentarlo mejor.
if (trim((string) ($_POST['web'] ?? '')) !== '') {
    alta_responder('Ya casi está', 'Revisa tu correo y confirma el alta.');
}

if (alta_pasada_de_vueltas(alta_huella())) {
    alta_responder(
        'Demasiados intentos',
        'Se han pedido varias altas seguidas desde aquí. Inténtalo dentro de un rato.',
        429
    );
}

$email = correo_normalizar((string) ($_POST['email'] ?? ''));

if (!correo_valido($email)) {
    alta_responder(
        'Esa dirección no vale',
        'Revísala y vuelve a intentarlo: parece que le falta algo.',
        400
    );
}

$resultado = correo_alta($email, $base . '/');

if (!$resultado['ok']) {
    alta_responder('No ha podido ser', $resultado['mensaje'], 502);
}

// Mismo mensaje tanto si la direccion era nueva como si ya estaba: lo
// contrario permitiria averiguar quien esta en la lista probando direcciones.
alta_responder(
    'Ya casi está',
    'Te hemos mandado un correo para confirmar el alta. Hasta que no pulses el enlace no te apuntamos, así que échale un ojo también a la carpeta de no deseados.'
);
