<?php
/**
 * Arranque del sitio.
 *
 * Con el sitio ya generado, este fichero no se ejecuta: el .htaccess sirve
 * publico/ como si fuera la raiz del dominio y Apache no toca PHP. Solo entra
 * en juego en tres casos, y los tres son el mismo: algo no esta en su sitio.
 *
 *   sin config/config.php        -> al instalador
 *   sin la web generada          -> se genera aqui mismo y se sirve
 *   sin poder generarla          -> portada provisional, nunca un 403 seco
 *
 * Lo segundo es lo importante y es reciente. Antes, la web solo se generaba
 * desde el cron, asi que un despliegue nuevo se quedaba con lo que hubiera en
 * publico/ -que no esta en el repositorio, y por tanto no se actualiza nunca
 * con un git pull- hasta que el cron se despertara. Si el cron estaba mal
 * configurado, eso era "para siempre". Ahora la primera visita lo arregla.
 */

declare(strict_types=1);

date_default_timezone_set('UTC');

const ARRANQUE_ESPERA = 300;   // segundos entre intentos de generar

$raiz    = __DIR__;
$config  = $raiz . '/config/config.php';
$portada = $raiz . '/publico/index.html';

// El archivo lo escribe siempre el generador, tenga o no ediciones. Es la
// huella de cuando paso por aqui por ultima vez.
$huella  = $raiz . '/publico/archivo.html';

// -----------------------------------------------------------------------------
// Sin configuracion no hay sitio: al instalador
// -----------------------------------------------------------------------------

if (!is_file($config)) {
    if (is_file($raiz . '/instalar.php')) {
        // La ruta se calcula desde SCRIPT_NAME por si el proyecto no cuelga de
        // la raiz del dominio.
        $base = rtrim(str_replace('\\', '/', dirname((string) $_SERVER['SCRIPT_NAME'])), '/');

        header('Location: ' . $base . '/instalar.php', true, 302);
        exit;
    }

    arranque_provisional();
}

// -----------------------------------------------------------------------------
// Sin web generada: generarla ahora
// -----------------------------------------------------------------------------

if (arranque_toca_intentar($raiz)) {
    if (arranque_sin_contenido($raiz)) {
        // Sitio recien puesto en marcha: cada visita empuja la cadena entera un
        // paso -rastrear, agrupar, publicar y generar-, como mucho una vez cada
        // cinco minutos. Asi se llena solo aunque el cron no este bien puesto,
        // que es exactamente lo que ha pasado aqui. En cuanto hay una edicion
        // publicada esto deja de ejecutarse y el sitio vuelve a ser estatico.
        arranque_cadena($raiz);
    } elseif (arranque_hay_que_generar($raiz, $huella)) {
        arranque_tarea($raiz, 'publicar', 'publicar_pendiente', 15);
    }
}

// -----------------------------------------------------------------------------
// Servir
// -----------------------------------------------------------------------------

// Se lee entera antes de contestar: si se volcara con readfile() y fallase a
// medias, ya no habria forma de cambiar la cabecera ni el codigo de estado.
// Un fichero vacio cuenta como portada rota y cae a la provisional.
$contenido = is_file($portada) ? @file_get_contents($portada) : false;

if ($contenido !== false && $contenido !== '') {
    header('Content-Type: text/html; charset=utf-8');
    echo $contenido;
    exit;
}

arranque_provisional();

// -----------------------------------------------------------------------------

/**
 * ¿El sitio no tiene todavia nada publicado?
 *
 * Se mira el indice de busqueda, que el generador escribe siempre y lleva
 * dentro todos los bits publicados. Sin fichero o con la lista vacia, no hay
 * contenido.
 */
function arranque_sin_contenido(string $raiz): bool
{
    $indice = $raiz . '/publico/indice.json';

    if (!is_file($indice)) {
        return true;
    }

    $datos = json_decode((string) @file_get_contents($indice), true);

    return !is_array($datos) || empty($datos['bits']);
}

/**
 * Una vuelta completa de la cadena, con presupuestos cortos.
 *
 * El orden importa y es el mismo que usa el cron: primero traer, luego
 * agrupar, luego decidir que se publica, y al final escribir la web.
 */
function arranque_cadena(string $raiz): void
{
    @set_time_limit(90);

    arranque_tarea($raiz, 'ingesta',  'ingesta_lote',       12);
    arranque_tarea($raiz, 'procesar', 'procesar_lote',      10);
    arranque_tarea($raiz, 'auto',     'auto_publicar_lote',  6);
    arranque_tarea($raiz, 'publicar', 'publicar_pendiente', 12);
}

/**
 * Ejecuta una tarea del cron desde la web, sin dejar que un fallo suyo deje
 * al visitante sin pagina.
 */
function arranque_tarea(string $raiz, string $fichero, string $funcion, int $segundos): void
{
    try {
        require_once $raiz . '/cron/' . $fichero . '.php';

        if (function_exists($funcion)) {
            $funcion(microtime(true) + $segundos);
        }
    } catch (Throwable $e) {
        error_log('Bit & Breakfast, ' . $funcion . ' desde la web: ' . $e->getMessage());
    }
}

/**
 * ¿La web publicada esta al dia?
 *
 * Dos motivos para regenerar: que no exista, o que las plantillas sean mas
 * nuevas que lo generado. Lo segundo es lo que hace que un despliegue se vea
 * solo con abrir la portada, sin esperar al cron ni depender de que este bien
 * configurado. Son catorce llamadas a filemtime; para lo que cuesta descubrir
 * dos semanas despues que el sitio seguia con el diseno viejo, sale barato.
 */
function arranque_hay_que_generar(string $raiz, string $huella): bool
{
    if (!is_file($huella)) {
        return true;
    }

    $generado = (int) @filemtime($huella);

    foreach (glob($raiz . '/plantillas/web/*.php') ?: [] as $plantilla) {
        if ((int) @filemtime($plantilla) > $generado) {
            return true;
        }
    }

    return false;
}

/**
 * ¿Toca intentar generar?
 *
 * Con un cortafuegos de tiempo: si la generacion falla -la base caida, un
 * permiso mal puesto-, sin esto cada visita volveria a intentarlo y una
 * portada compartida se convertiria en un martillo contra la base de datos.
 */
function arranque_toca_intentar(string $raiz): bool
{
    $marca = $raiz . '/cache/.publicar';

    if (is_file($marca) && time() - (int) @filemtime($marca) < ARRANQUE_ESPERA) {
        return false;
    }

    // La marca se pone ANTES de intentarlo, no despues: si el intento muere a
    // la mitad, el siguiente visitante tampoco debe repetirlo en el acto.
    @touch($marca);

    return true;
}

/**
 * La portada provisional, desde la misma plantilla que usa el generador.
 */
function arranque_provisional(): void
{
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    header('Retry-After: 600');

    require_once __DIR__ . '/lib/web.php';

    $base       = '';
    $version    = web_version(__DIR__ . '/publico/estilo.css');
    $version_js = '0';

    require __DIR__ . '/plantillas/web/provisional.php';
    exit;
}
