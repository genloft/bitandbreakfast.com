<?php
/**
 * Arranque del sitio.
 *
 * Con mod_rewrite activo este fichero casi no se ejecuta: el .htaccess sirve
 * publico/ como si fuera la raiz del dominio. Es la red de seguridad para
 * cuando la reescritura no esta disponible, y hace el mismo trabajo que ella:
 *
 *   sin config/config.php  -> al instalador
 *   con portada generada   -> se sirve la portada
 *   sin portada            -> un 503 con un mensaje legible, nunca un 403
 *                             seco de directorio vacio
 */

declare(strict_types=1);

$config  = __DIR__ . '/config/config.php';
$portada = __DIR__ . '/publico/index.html';

if (!is_file($config) && is_file(__DIR__ . '/instalar.php')) {
    // La ruta se calcula desde SCRIPT_NAME por si el proyecto no cuelga de la
    // raiz del dominio.
    $base = rtrim(str_replace('\\', '/', dirname((string) $_SERVER['SCRIPT_NAME'])), '/');

    header('Location: ' . $base . '/instalar.php', true, 302);
    exit;
}

// Se lee entera antes de contestar: si se volcara con readfile() y fallase a
// medias, ya no habria forma de cambiar la cabecera ni el codigo de estado.
// Un fichero vacio cuenta como portada rota y cae al mensaje de abajo.
$contenido = is_file($portada) ? @file_get_contents($portada) : false;

if ($contenido !== false && $contenido !== '') {
    header('Content-Type: text/html; charset=utf-8');
    echo $contenido;
    exit;
}

http_response_code(503);
header('Content-Type: text/html; charset=utf-8');
header('Retry-After: 3600');

// La misma portada provisional que escribe el instalador, desde la misma
// plantilla: no hay una segunda version del diseno esperando a quedarse vieja.
require_once __DIR__ . '/lib/web.php';

$base = '';

require __DIR__ . '/plantillas/web/provisional.php';
