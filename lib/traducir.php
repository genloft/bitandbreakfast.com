<?php
/**
 * Traduccion automatica al espanol.
 *
 * Hasta ahora este sitio solo publicaba lo que alguien contaba en espanol, y
 * la razon era buena: traducir a maquina es poner en boca de un medio algo que
 * no ha dicho. Pero el efecto era que, de setenta fuentes, publicaban ocho: la
 * portada se quedaba en ocho noticias mientras el radar leia mil doscientas
 * entradas al dia. Una promesa que se cumple dejando la casa vacia no es una
 * promesa, es una excusa.
 *
 * Asi que se traduce, y se dice. Tres reglas que no se negocian:
 *
 *   1. Se traduce el titular y el resumen, nunca el articulo: el articulo es
 *      del medio y se lee en el medio. Lo que hay aqui es una ficha con
 *      enlace, y eso no cambia porque ahora este en espanol.
 *   2. Cada bit traducido lo dice en su cara -"traducido del ingles"- y
 *      mantiene el enlace y el nombre de la fuente. Nadie tiene que adivinar
 *      que esas palabras no son las que escribio el periodista.
 *   3. Solo se traduce lo que ya ha pasado todas las puertas. Traducir antes
 *      seria gastar cuota en las novecientas noticias que se van a descartar.
 *
 * El proveedor es DeepL, por el plan gratuito -medio millon de caracteres al
 * mes, que a titular y resumen dan para miles de noticias- y porque es el que
 * mejor traduce al espanol. La clave la teclea el dueno del sitio en el panel
 * y se guarda en config/traductor.php, fuera del repositorio y con permisos
 * 0600, igual que la del buzon.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

/** Segundos de espera de la peticion al traductor. */
const TRADUCIR_TIMEOUT = 12;

/** Caracteres que se dejan sin gastar del plan, por si acaso. */
const TRADUCIR_COLCHON = 20000;

/**
 * La configuracion del traductor, con los huecos a cero.
 *
 * Vive en su propio fichero por lo mismo que el buzon: lo escribe el panel, y
 * un error escribiendolo no puede llevarse por delante la configuracion de la
 * base de datos.
 */
function traducir_conf(): array
{
    static $conf = null;

    if ($conf !== null) {
        return $conf;
    }

    $ruta  = dirname(__DIR__) . '/config/traductor.php';
    $datos = is_readable($ruta) ? @require $ruta : [];
    $datos = is_array($datos) ? $datos : [];

    $conf = [
        'proveedor'  => (string) ($datos['proveedor'] ?? 'deepl'),
        'clave'      => (string) ($datos['clave'] ?? ''),
        // Las claves del plan gratuito acaban en ':fx' y van a otro dominio.
        // Se deduce de la clave para que no haya un ajuste mas que equivocar.
        'plan'       => str_ends_with((string) ($datos['clave'] ?? ''), ':fx') ? 'free' : 'pro',
        'limite_mes' => (int) ($datos['limite_mes'] ?? 500000),
    ];

    return $conf;
}

/**
 * ¿Hay traductor ahora mismo?
 */
function traducir_configurado(?array $conf = null): bool
{
    $conf = $conf ?? traducir_conf();

    return trim((string) $conf['clave']) !== '';
}

/**
 * La direccion de la API segun el plan.
 */
function traducir_url(array $conf): string
{
    return ($conf['plan'] ?? 'free') === 'free'
        ? 'https://api-free.deepl.com/v2/translate'
        : 'https://api.deepl.com/v2/translate';
}

/**
 * Cuantos caracteres se llevan gastados este mes, y cuantos caben.
 *
 * La cuenta la lleva este sitio y no se le pregunta a DeepL en cada pasada:
 * una peticion mas por bit para saber algo que ya sabemos seria pagar dos
 * veces el mismo viaje. El mes se guarda con la cuenta, asi que al cambiar de
 * mes se reinicia sola.
 *
 * @return array ['gastado' => int, 'queda' => int]
 */
function traducir_cuota(?array $conf = null): array
{
    $conf = $conf ?? traducir_conf();
    $mes  = gmdate('Y-m');

    $gastado = (string) ajuste('traductor_mes', '') === $mes
        ? (int) ajuste('traductor_gastado', '0')
        : 0;

    $tope = max(0, (int) $conf['limite_mes'] - TRADUCIR_COLCHON);

    return ['gastado' => $gastado, 'queda' => max(0, $tope - $gastado)];
}

/**
 * Apunta lo gastado. Se llama despues de traducir, no antes: lo que no llega a
 * salir no se cobra.
 */
function traducir_apuntar(int $caracteres): void
{
    $mes = gmdate('Y-m');

    if ((string) ajuste('traductor_mes', '') !== $mes) {
        ajuste_guardar('traductor_mes', $mes);
        ajuste_guardar('traductor_gastado', '0');
    }

    ajuste_guardar(
        'traductor_gastado',
        (string) ((int) ajuste('traductor_gastado', '0') + max(0, $caracteres))
    );
}

/**
 * Traduce varios textos de una vez.
 *
 * De una vez porque DeepL cobra por caracter pero cuesta por peticion: mandar
 * el titular y el resumen juntos es la mitad de viajes y exactamente el mismo
 * gasto. El orden de la respuesta es el de la peticion, y en eso se confia
 * -lo promete su documentacion-, pero si vuelven menos de los que fueron se
 * da por fallida: emparejar mal un titular con otro resumen es peor que no
 * traducir.
 *
 * @param string[] $textos
 * @param string   $origen Codigo de dos letras, o '' para que lo adivine.
 *
 * @return array ['ok' => bool, 'textos' => string[], 'mensaje' => string]
 */
function traducir_textos(array $textos, string $origen = '', ?array $conf = null): array
{
    $conf   = $conf ?? traducir_conf();
    $textos = array_values(array_map('strval', $textos));

    if (!traducir_configurado($conf)) {
        return ['ok' => false, 'textos' => [], 'mensaje' => 'sin traductor configurado'];
    }

    if (!$textos) {
        return ['ok' => true, 'textos' => [], 'mensaje' => ''];
    }

    $caracteres = 0;

    foreach ($textos as $texto) {
        $caracteres += mb_strlen($texto, 'UTF-8');
    }

    $cuota = traducir_cuota($conf);

    if ($caracteres > $cuota['queda']) {
        return ['ok' => false, 'textos' => [], 'mensaje' => 'cuota del mes agotada'];
    }

    $campos = [
        'target_lang' => 'ES',
        // Sin formato que preservar: lo que entra es texto plano, y decirlo
        // evita que DeepL se invente etiquetas al ver un signo de menor que.
        'tag_handling' => 'xml',
        'split_sentences' => '1',
    ];

    if ($origen !== '' && $origen !== 'es') {
        $campos['source_lang'] = strtoupper(substr($origen, 0, 2));
    }

    $cuerpo = http_build_query($campos);

    foreach ($textos as $texto) {
        $cuerpo .= '&text=' . rawurlencode($texto);
    }

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL            => traducir_url($conf),
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $cuerpo,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => TRADUCIR_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER     => [
            'Authorization: DeepL-Auth-Key ' . trim((string) $conf['clave']),
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: BitAndBreakfast/1.0',
        ],
    ]);

    $respuesta = curl_exec($ch);
    $codigo    = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error     = curl_error($ch);

    curl_close($ch);

    if ($respuesta === false) {
        return ['ok' => false, 'textos' => [], 'mensaje' => 'no se pudo conectar: ' . $error];
    }

    if ($codigo === 456) {
        // DeepL dice que la cuota se acabo. Se apunta el mes entero como
        // gastado para no volver a intentarlo en cada pasada.
        ajuste_guardar('traductor_mes', gmdate('Y-m'));
        ajuste_guardar('traductor_gastado', (string) (int) $conf['limite_mes']);

        return ['ok' => false, 'textos' => [], 'mensaje' => 'cuota del mes agotada'];
    }

    if ($codigo !== 200) {
        return ['ok' => false, 'textos' => [], 'mensaje' => 'el traductor contesta HTTP ' . $codigo];
    }

    $datos = json_decode((string) $respuesta, true);
    $salida = [];

    foreach ($datos['translations'] ?? [] as $traduccion) {
        $salida[] = (string) ($traduccion['text'] ?? '');
    }

    if (count($salida) !== count($textos)) {
        return ['ok' => false, 'textos' => [], 'mensaje' => 'el traductor devuelve otra cosa'];
    }

    traducir_apuntar($caracteres);

    return ['ok' => true, 'textos' => $salida, 'mensaje' => ''];
}

/**
 * Guarda la clave del traductor en config/traductor.php.
 *
 * Igual que el buzon: fichero aparte, 0600 y escritura atomica. La clave no
 * pasa por la base de datos ni por el repositorio en ningun momento.
 *
 * @return array ['ok' => bool, 'mensaje' => string]
 */
function traducir_guardar(array $datos): array
{
    $clave = trim((string) ($datos['clave'] ?? ''));
    $tope  = (int) ($datos['limite_mes'] ?? 500000);

    // Borrar la clave es una operacion legitima: es como se apaga esto.
    if ($clave === '') {
        $ruta = dirname(__DIR__) . '/config/traductor.php';

        if (is_file($ruta) && !@unlink($ruta)) {
            return ['ok' => false, 'mensaje' => 'No se ha podido borrar config/traductor.php.'];
        }

        return ['ok' => true, 'mensaje' => 'Traductor desconectado.'];
    }

    // Las claves de DeepL son un UUID, con ':fx' al final en el plan gratuito.
    if (!preg_match('/^[0-9a-f-]{36}(:fx)?$/i', $clave)) {
        return ['ok' => false, 'mensaje' => 'Esa no parece una clave de DeepL.'];
    }

    if ($tope < 1000) {
        return ['ok' => false, 'mensaje' => 'El límite mensual es demasiado pequeño.'];
    }

    $ruta     = dirname(__DIR__) . '/config/traductor.php';
    $temporal = $ruta . '.' . getmypid();

    $contenido = implode(PHP_EOL, [
        '<?php',
        '/**',
        ' * Clave del traductor automatico.',
        ' *',
        ' * Lo escribe el panel: no se edita a mano y no esta en el repositorio.',
        ' */',
        '',
        'return [',
        "    'proveedor'  => 'deepl',",
        "    'clave'      => " . var_export($clave, true) . ',',
        "    'limite_mes' => " . var_export($tope, true) . ',',
        '];',
        '',
    ]);

    if (@file_put_contents($temporal, $contenido) === false) {
        return ['ok' => false, 'mensaje' => 'No se ha podido escribir config/traductor.php. Revisa los permisos.'];
    }

    @chmod($temporal, 0600);

    if (!@rename($temporal, $ruta)) {
        @unlink($temporal);

        return ['ok' => false, 'mensaje' => 'No se ha podido reemplazar config/traductor.php.'];
    }

    return ['ok' => true, 'mensaje' => 'Traductor conectado.'];
}
