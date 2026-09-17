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
 *   sin cron que responda        -> la visita empuja la cadena entera
 *   sin poder generarla          -> portada provisional, nunca un 403 seco
 *
 * Lo segundo es lo importante y es reciente. Antes, la web solo se generaba
 * desde el cron, asi que un despliegue nuevo se quedaba con lo que hubiera en
 * publico/ -que no esta en el repositorio, y por tanto no se actualiza nunca
 * con un git pull- hasta que el cron se despertara. Si el cron estaba mal
 * configurado, eso era "para siempre". Ahora la primera visita lo arregla.
 *
 * Lo tercero es la consecuencia de mirar la realidad: el cron se configura en
 * el panel del alojamiento, fuera del repositorio, y equivocarse alli es
 * facil. Mientras el cron no de senales de vida, el radar se mantiene con las
 * visitas: una cada cinco minutos empuja la cadena un paso. Es mas lento y
 * mas tosco que el cron, pero la diferencia entre un sitio lento y un sitio
 * muerto no es de grado. En cuanto el cron vuelve a latir, esto se apaga solo.
 */

declare(strict_types=1);

require_once __DIR__ . '/lib/estado.php';

date_default_timezone_set('UTC');

const ARRANQUE_ESPERA = 300;     // segundos entre intentos de generar
const ARRANQUE_ESPERA_SOLO = 120;// ... o menos, si la web va sola y hay prisa
const ARRANQUE_SILENCIO = 7200;  // sin cron en dos horas, tira la web del carro

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

$vacio   = arranque_sin_contenido($raiz);
$solo    = $vacio || arranque_sin_cron($raiz);
$trabajo = '';

// Cuando la web va sola, se intenta mas a menudo: cada pasada avanza un lote y
// la cola de un arranque son cientos de items. Con el cron vivo no hace falta.
if (arranque_toca_intentar($raiz, $solo ? ARRANQUE_ESPERA_SOLO : ARRANQUE_ESPERA)) {
    if ($solo) {
        // Sitio recien puesto en marcha, o cron que no contesta: cada visita
        // empuja la cadena entera un paso -rastrear, agrupar, publicar y
        // generar-. Asi el radar se llena y se mantiene aunque el cron no este
        // bien puesto, que es exactamente lo que ha pasado aqui. En cuanto el
        // cron da senales de vida esto deja de ejecutarse y el sitio vuelve a
        // ser estatico.
        $trabajo = 'cadena';
    } elseif (arranque_criterios_pendientes($raiz)) {
        // Antes que generar: la revision reescribe los bits y ademas obliga a
        // generar de nuevo, asi que hacerlo al reves seria publicar dos veces
        // y, con despliegues seguidos, dejar la revision siempre para luego.
        $trabajo = 'revisar';
    } elseif (arranque_hay_que_generar($raiz, $huella)) {
        $trabajo = 'publicar';
    }
}

// Si hay portada que servir y el servidor sabe cerrar la respuesta antes de
// terminar el proceso, se sirve primero y se trabaja despues: el visitante no
// tiene por que esperar a que se rastreen cuarenta feeds. Sin portada no hay
// nada que adelantar, y entonces toca trabajar antes.
$despues = !$vacio && function_exists('fastcgi_finish_request');

if ($trabajo !== '' && !$despues) {
    arranque_trabajar($raiz, $trabajo);
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

    if ($trabajo !== '' && $despues) {
        // El visitante ya tiene su pagina. Lo que queda es trabajo de fondo, y
        // que se corte a la mitad no rompe nada: cada tarea trabaja por lotes
        // con puntero y la siguiente visita sigue por donde se quedo.
        @ignore_user_abort(true);
        fastcgi_finish_request();
        arranque_trabajar($raiz, $trabajo, true);
    }

    exit;
}

if ($trabajo !== '' && $despues) {
    arranque_trabajar($raiz, $trabajo);
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
 * ¿El cron lleva demasiado tiempo sin dar senales?
 *
 * El cron toca cache/.cron cada vez que corre. Sin esa marca -o con una vieja-
 * se da por hecho que no hay cron y la web se mantiene sola. Es la diferencia
 * entre un radar que va lento y un radar congelado.
 */
function arranque_sin_cron(string $raiz): bool
{
    return estado_cron_callado(estado_ultimo_cron($raiz), time(), ARRANQUE_SILENCIO);
}

/**
 * Hace el trabajo que toque, sea la cadena entera o solo generar.
 */
function arranque_trabajar(string $raiz, string $trabajo, bool $holgado = false): void
{
    if ($trabajo === 'cadena') {
        arranque_cadena($raiz, $holgado);
        return;
    }

    if ($trabajo === 'revisar') {
        @set_time_limit($holgado ? 120 : 60);
        arranque_tarea($raiz, 'auto', 'auto_publicar_lote', $holgado ? 20 : 8);
    }

    arranque_tarea($raiz, 'publicar', 'publicar_pendiente', $holgado ? 25 : 15);
}

/**
 * ¿Hay una revision pendiente de los criterios nuevos?
 *
 * Cuando un despliegue sube los criterios, lo ya publicado sigue escrito con
 * los viejos hasta que el cron se despierta. Con un cron horario eso es hasta
 * una hora sirviendo lo que ya se sabe que esta mal, y el despliegue es
 * justamente el momento en que alguien esta mirando. Una consulta cada cinco
 * minutos como mucho, que es cuando se llega hasta aqui.
 */
function arranque_criterios_pendientes(string $raiz): bool
{
    try {
        require_once $raiz . '/lib/db.php';
        require_once $raiz . '/lib/auto.php';

        return (int) ajuste('auto_criterios', '0') < AUTO_CRITERIOS;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Una vuelta completa de la cadena, con presupuestos cortos.
 *
 * El orden importa y es el mismo que usa el cron: primero traer, luego
 * agrupar, luego decidir que se publica, y al final escribir la web.
 */
function arranque_cadena(string $raiz, bool $holgado = false): void
{
    @set_time_limit($holgado ? 180 : 90);

    // Con la respuesta ya enviada nadie espera, asi que cada tarea puede
    // trabajar de verdad en lugar de ir a trocitos.
    arranque_tarea($raiz, 'ingesta',  'ingesta_lote',       $holgado ? 25 : 12);
    arranque_tarea($raiz, 'procesar', 'procesar_lote',      $holgado ? 35 : 10);
    arranque_tarea($raiz, 'auto',     'auto_publicar_lote', $holgado ? 15 : 6);
    arranque_tarea($raiz, 'publicar', 'publicar_pendiente', $holgado ? 25 : 12);
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
function arranque_toca_intentar(string $raiz, int $espera = ARRANQUE_ESPERA): bool
{
    $marca = $raiz . '/cache/.publicar';

    if (is_file($marca) && time() - (int) @filemtime($marca) < $espera) {
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
