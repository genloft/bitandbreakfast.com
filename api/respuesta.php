<?php
/**
 * La pagina que ven los endpoints publicos cuando tienen algo que contestar.
 *
 * Tres endpoints -alta, confirmacion y baja- terminan ensenando una pagina
 * con el aspecto del sitio y una frase. Tenerla tres veces garantizaba que un
 * dia serian tres paginas distintas.
 *
 * Se pinta con las mismas plantillas que el resto de la web, asi que hereda el
 * diseno sin saber nada de el.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/web.php';

/**
 * Pinta la respuesta y termina.
 *
 * @param string $base    URL del sitio, sin barra final.
 * @param string $titulo  Lo que se lee en grande.
 * @param string $mensaje El parrafo de debajo.
 * @param int    $codigo  Codigo HTTP.
 * @param string $vuelta  Texto del enlace de vuelta.
 */
function api_responder(
    string $base,
    string $titulo,
    string $mensaje,
    int $codigo = 200,
    string $vuelta = 'Volver a la última edición'
): void {
    http_response_code($codigo);
    header('Content-Type: text/html; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');

    $enlace_activo = '';
    $version       = web_version(dirname(__DIR__) . '/publico/estilo.css');

    echo '<!doctype html>', "\n";
    echo '<html lang="es">', "\n";
    echo '<head>', "\n";
    echo '<meta charset="utf-8">', "\n";
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">', "\n";
    echo '<meta name="robots" content="noindex,nofollow">', "\n";
    echo '<title>', web_e($titulo), ' · Bit &amp; Breakfast</title>', "\n";
    // Con la version colgada de la URL, como en el resto del sitio: la hoja
    // lleva un mes de cache y se reescribe siempre en el mismo sitio.
    echo '<link rel="stylesheet" href="', web_e($base), '/estilo.css?v=', web_e($version), '">', "\n";
    echo '<meta name="theme-color" content="#12100d">', "\n";
    echo '</head>', "\n<body>\n";

    require dirname(__DIR__) . '/plantillas/web/cabecera.php';

    echo '<main id="contenido">', "\n";
    echo '<header class="edicion-cabecera"><h1>', web_e($titulo), '</h1></header>', "\n";
    echo '<p class="alta-respuesta">', web_e($mensaje), '</p>', "\n";
    echo '<p><a href="', web_e($base), '/">', web_e($vuelta), '</a></p>', "\n";
    echo '</main>', "\n";

    require dirname(__DIR__) . '/plantillas/web/pie.php';

    echo "\n</body>\n</html>\n";

    exit;
}
