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

if (arranque_hay_que_generar($raiz, $huella) && arranque_toca_intentar($raiz)) {
    try {
        require_once $raiz . '/cron/publicar.php';

        // Presupuesto corto: esto corre dentro de una peticion web, no en el
        // cron. Lo que no entre lo termina la siguiente visita o el cron.
        publicar_pendiente(microtime(true) + 15);
    } catch (Throwable $e) {
        // Que falle no puede dejar al visitante sin pagina: se sigue adelante
        // y se le sirve lo que haya, o la provisional.
        error_log('Bit & Breakfast, generacion desde la web: ' . $e->getMessage());
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
