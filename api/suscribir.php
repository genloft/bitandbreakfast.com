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
require_once dirname(__DIR__) . '/lib/bits.php';
require_once __DIR__ . '/respuesta.php';

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

    return substr(hash_hmac('sha256', $ip, (string) config_opcional('secretos.secreto_hmac', 'sin-secreto')), 0, 32);
}

// -----------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_responder(
        $base,
        'Aquí no hay nada',
        'Esta dirección solo atiende el formulario de alta.',
        405
    );
}

// La trampa: si viene rellena, es un robot. Se le contesta que todo ha ido
// bien, porque decirle que ha fallado solo le ensena a intentarlo mejor.
if (trim((string) ($_POST['web'] ?? '')) !== '') {
    api_responder($base, 'Ya casi está', 'Revisa tu correo y confirma el alta.');
}

if (alta_pasada_de_vueltas(alta_huella())) {
    api_responder(
        $base,
        'Demasiados intentos',
        'Se han pedido varias altas seguidas desde aquí. Inténtalo dentro de un rato.',
        429
    );
}

$email = correo_normalizar((string) ($_POST['email'] ?? ''));

if (!correo_valido($email)) {
    api_responder(
        $base,
        'Esa dirección no vale',
        'Revísala y vuelve a intentarlo: parece que le falta algo.',
        400
    );
}

// Los temas elegidos, filtrados contra el catalogo real: un valor que no
// esta en bits_categorias() no puede colarse en la columna -ni por un
// formulario manipulado, ni porque el catalogo cambie de nombres con el
// tiempo-. Vacio, o con todos marcados, significa "todos los temas", que es
// tambien el valor por defecto: elegir es una opcion, no un requisito.
$catalogo   = array_keys(bits_categorias());
$elegidos   = array_values(array_intersect((array) ($_POST['temas'] ?? []), $catalogo));
$temas      = count($elegidos) < count($catalogo) ? implode(',', $elegidos) : '';

// La alerta es texto libre -una sigla, un proveedor, una palabra suelta-,
// asi que aqui no hay catalogo contra el que filtrar como con los temas.
// Lo unico que hace falta es que no se cuele algo absurdamente largo: como
// mucho diez terminos, y el conjunto recortado al ancho real de la columna
// para que nunca falle la insercion, pase lo que pase con los terminos.
$terminos = array_filter(array_map(
    static fn (string $termino): string => mb_substr(trim($termino), 0, 25),
    explode(',', (string) ($_POST['alerta'] ?? ''))
), static fn (string $termino): bool => $termino !== '');
$alerta   = mb_substr(implode(',', array_slice(array_values($terminos), 0, 10)), 0, 300);

$resultado = correo_alta($email, $base . '/', $temas, $alerta);

if (!$resultado['ok']) {
    api_responder($base, 'No ha podido ser', $resultado['mensaje'], 502);
}

// Mismo mensaje tanto si la direccion era nueva como si ya estaba: lo
// contrario permitiria averiguar quien esta en la lista probando direcciones.
api_responder(
    $base,
    'Ya casi está',
    'Te hemos mandado un correo para confirmar el alta. Hasta que no pulses el enlace no te apuntamos, así que échale un ojo también a la carpeta de no deseados.'
);
