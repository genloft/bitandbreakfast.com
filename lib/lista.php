<?php
/**
 * La lista de suscriptores, cuando la lleva el propio sitio.
 *
 * Lo que decide quien esta en la lista no es este fichero: es el clic en el
 * correo de confirmacion. Aqui solo se guarda la peticion, se manda ese correo
 * y se espera. Un alta sin confirmar no recibe nunca una edicion.
 *
 * Tres reglas que no se negocian, porque son las que separan un boletin de una
 * molestia:
 *
 *   1. Doble confirmacion. Cualquiera puede escribir la direccion de otro en
 *      un formulario; solo el dueno del buzon puede pulsar el enlace.
 *   2. Baja en un clic, sin preguntar nada y sin pedir que inicie sesion
 *      nadie. El enlace va firmado y va en todos los envios.
 *   3. Al que ya estaba suscrito se le contesta lo mismo que al que no. Lo
 *      contrario permite averiguar quien esta en la lista probando
 *      direcciones.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/correo.php';
require_once __DIR__ . '/smtp.php';

/** Horas que vale el enlace de confirmacion. */
const LISTA_TESTIGO_HORAS = 72;

/**
 * Guarda la peticion de alta y manda el correo de confirmacion.
 *
 * @return array ['ok' => bool, 'mensaje' => string]
 */
function correo_alta_propia(string $email, array $conf, string $url_vuelta = '', string $temas = ''): array
{
    if (!correo_valido($email)) {
        return ['ok' => false, 'mensaje' => 'Esa dirección no parece válida.'];
    }

    if ((string) ajuste('correo_alta_abierta', '1') !== '1') {
        return ['ok' => false, 'mensaje' => 'El alta está cerrada ahora mismo.'];
    }

    $ya = lista_buscar($email);

    // A quien ya esta confirmado no se le manda nada -ya esta dentro- y se le
    // contesta lo mismo que a los demas.
    if ($ya !== null && $ya['estado'] === 'confirmado') {
        return ['ok' => true, 'mensaje' => ''];
    }

    $testigo = bin2hex(random_bytes(32));
    $id      = lista_guardar_pendiente($email, $testigo, $temas);

    if ($id <= 0) {
        return ['ok' => false, 'mensaje' => 'No se ha podido registrar el alta. Inténtalo más tarde.'];
    }

    $base    = rtrim((string) config_opcional('sitio.url', ''), '/');
    $enlace  = $base . '/api/confirmar.php?s=' . $id . '&t=' . $testigo;
    $envio   = smtp_enviar($conf, [
        'para'      => $email,
        'asunto'    => 'Confirma tu alta en Bit & Breakfast',
        'texto'     => lista_texto_confirmacion($enlace, $base),
        'html'      => lista_html_confirmacion($enlace, $base),
        'cabeceras' => ['Auto-Submitted: auto-generated'],
    ]);

    if (!$envio['ok']) {
        // El detalle va al registro, no al lector: habla de servidores de
        // correo y quien se estaba suscribiendo no puede hacer nada con eso.
        error_log('Bit & Breakfast, confirmacion de alta: ' . $envio['mensaje']);

        return ['ok' => false, 'mensaje' => 'No hemos podido mandarte el correo de confirmación. Inténtalo más tarde.'];
    }

    return ['ok' => true, 'mensaje' => ''];
}

/**
 * Busca una direccion en la lista.
 */
function lista_buscar(string $email): ?array
{
    $st = bd()->prepare('SELECT id, email, estado, testigo, pedido FROM suscriptores WHERE email = ? LIMIT 1');
    $st->execute([$email]);

    $fila = $st->fetch();

    return $fila === false ? null : $fila;
}

/**
 * Deja el alta como pendiente con un testigo nuevo.
 *
 * Si la direccion ya estaba -pendiente de confirmar, o dada de baja hace
 * tiempo y vuelve-, se reutiliza la fila y se renueva el testigo: la direccion
 * es unica en la tabla y tener dos filas de la misma persona solo sirve para
 * mandarle dos correos.
 *
 * $temas es una cadena separada por comas de slugs de bits_categorias() -por
 * ejemplo "ciberseguridad,cumplimiento"-, o cadena vacia para "todos los
 * temas", que es tambien el valor por defecto de la columna: quien vuelve a
 * pedir el alta sin elegir nada recupera el "todos" de siempre, no se queda
 * con la eleccion de la vez anterior.
 *
 * @return int Id del suscriptor, o 0 si no se ha podido.
 */
function lista_guardar_pendiente(string $email, string $testigo, string $temas = ''): int
{
    try {
        $sql = "INSERT INTO suscriptores (email, estado, testigo, huella, temas, pedido)
                VALUES (?, 'pendiente', ?, ?, ?, UTC_TIMESTAMP())
                ON DUPLICATE KEY UPDATE
                    estado    = 'pendiente',
                    testigo   = VALUES(testigo),
                    huella    = VALUES(huella),
                    temas     = VALUES(temas),
                    pedido    = UTC_TIMESTAMP(),
                    dado_baja = NULL";

        bd()->prepare($sql)->execute([$email, $testigo, lista_huella(), $temas]);

        $st = bd()->prepare('SELECT id FROM suscriptores WHERE email = ? LIMIT 1');
        $st->execute([$email]);

        return (int) $st->fetchColumn();
    } catch (Throwable $e) {
        error_log('Bit & Breakfast, alta en la lista: ' . $e->getMessage());

        return 0;
    }
}

/**
 * Huella de la IP: HMAC truncado, nunca la IP.
 *
 * Sirve para demostrar que el alta vino de algun sitio -que es lo que hay que
 * poder demostrar- y no sirve para saber de quien era.
 */
function lista_huella(): string
{
    $ip = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
    $ip = trim(explode(',', $ip)[0]);

    if ($ip === '') {
        return '';
    }

    return substr(hash_hmac('sha256', $ip, (string) config_opcional('secretos.secreto_hmac', 'sin-secreto')), 0, 32);
}

/**
 * Confirma un alta. El testigo es de un solo uso y caduca.
 *
 * @return bool true si la direccion queda confirmada.
 */
function lista_confirmar(int $id, string $testigo): bool
{
    if ($id <= 0 || strlen($testigo) !== 64 || !ctype_xdigit($testigo)) {
        return false;
    }

    $st = bd()->prepare('SELECT id, estado, testigo, pedido FROM suscriptores WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $fila = $st->fetch();

    if ($fila === false) {
        return false;
    }

    // Quien vuelve a pulsar el enlace de un alta ya confirmada no ha hecho
    // nada malo: se le dice que si.
    if ($fila['estado'] === 'confirmado') {
        return true;
    }

    // hash_equals y no ==: comparar testigos con == deja medir el tiempo de
    // respuesta y adivinarlos byte a byte.
    if (!hash_equals((string) $fila['testigo'], $testigo)) {
        return false;
    }

    if (strtotime((string) $fila['pedido']) < time() - LISTA_TESTIGO_HORAS * 3600) {
        return false;
    }

    $sql = "UPDATE suscriptores
               SET estado = 'confirmado', confirmado = UTC_TIMESTAMP(), testigo = ''
             WHERE id = ?";

    bd()->prepare($sql)->execute([$id]);

    return true;
}

/**
 * Firma del enlace de baja.
 *
 * Permanente a proposito, al reves que el testigo de confirmacion: el enlace
 * de baja tiene que seguir funcionando en un correo de hace dos anos.
 */
function lista_firma_baja(int $id): string
{
    $secreto = (string) config_opcional('secretos.secreto_hmac', '');

    return substr(hash_hmac('sha256', 'baja:' . $id, $secreto), 0, 32);
}

/**
 * Da de baja. No borra la fila: la marca.
 *
 * Guardar la baja es lo que impide volver a escribir a quien ya dijo que no,
 * incluso si esa direccion se vuelve a colar en un formulario. Se queda la
 * direccion y la fecha, y nada mas.
 */
function lista_baja(int $id, string $firma): bool
{
    if ($id <= 0 || !hash_equals(lista_firma_baja($id), $firma)) {
        return false;
    }

    $sql = "UPDATE suscriptores
               SET estado = 'baja', dado_baja = UTC_TIMESTAMP(), testigo = ''
             WHERE id = ?";

    bd()->prepare($sql)->execute([$id]);

    return true;
}

/**
 * Cuantos hay en cada estado. Para el panel y para la pagina de estado.
 */
function lista_cuentas(): array
{
    $cuentas = ['pendiente' => 0, 'confirmado' => 0, 'baja' => 0];

    try {
        $filas = bd()->query('SELECT estado, COUNT(*) AS cuantos FROM suscriptores GROUP BY estado');

        foreach ($filas === false ? [] : $filas->fetchAll() as $fila) {
            $cuentas[(string) $fila['estado']] = (int) $fila['cuantos'];
        }
    } catch (Throwable $e) {
        // Sin tabla todavia -antes de la primera migracion- no hay nadie.
        return $cuentas;
    }

    return $cuentas;
}

/**
 * El correo de confirmacion, en texto plano.
 *
 * Corto y sin adornos: lo unico que tiene que hacer es que se pulse el
 * enlace, y decir claramente que si no se pulsa no pasa nada.
 */
function lista_texto_confirmacion(string $enlace, string $base): string
{
    return "Has pedido recibir Bit & Breakfast, el radar semanal de tecnología hotelera.\n\n"
         . "Confirma el alta pulsando aquí:\n"
         . $enlace . "\n\n"
         . "Si no has sido tú, no hagas nada: sin ese clic no te apuntamos y esta\n"
         . "dirección no recibirá ningún envío.\n\n"
         . "El enlace caduca en tres días.\n\n"
         . "-- \n"
         . "Bit & Breakfast\n"
         . $base . "/\n";
}

/**
 * Y en HTML, con el mismo texto y la misma sobriedad.
 *
 * Sin imagenes, sin pixel de seguimiento y sin hoja de estilo externa: los
 * clientes de correo no la cargarian y lo unico que se consigue es pesar mas
 * y parecer mas spam.
 */
function lista_html_confirmacion(string $enlace, string $base): string
{
    $e = static fn (string $t): string => htmlspecialchars($t, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    return '<!doctype html><html lang="es"><head><meta charset="utf-8"></head>'
         . '<body style="margin:0;padding:24px;background:#12100d;color:#ece6d9;'
         . 'font-family:Georgia,\'Times New Roman\',serif;font-size:16px;line-height:1.6">'
         . '<div style="max-width:32rem;margin:0 auto">'
         . '<p style="font-size:28px;margin:0 0 24px">Bit<span style="color:#c9a66b">&amp;</span>Breakfast</p>'
         . '<p>Has pedido recibir <strong>Bit &amp; Breakfast</strong>, el radar semanal de '
         . 'tecnología hotelera.</p>'
         . '<p style="margin:28px 0"><a href="' . $e($enlace) . '" '
         . 'style="display:inline-block;padding:12px 20px;border:1px solid #c9a66b;'
         . 'color:#c9a66b;text-decoration:none">Confirmar el alta</a></p>'
         . '<p style="color:#a1968a;font-size:14px">Si no has sido tú, no hagas nada: sin ese '
         . 'clic no te apuntamos y esta dirección no recibirá ningún envío. El enlace caduca '
         . 'en tres días.</p>'
         . '<p style="color:#a1968a;font-size:14px">Si el botón no funciona, copia esta '
         . 'dirección en tu navegador:<br><span style="word-break:break-all">' . $e($enlace) . '</span></p>'
         . '<p style="color:#a1968a;font-size:14px;border-top:1px solid #2e2921;padding-top:16px">'
         . '<a href="' . $e($base) . '/" style="color:#a1968a">' . $e($base) . '</a></p>'
         . '</div></body></html>';
}
