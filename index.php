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

// El estilo va en publico/estilo.css, nunca en linea: la politica de
// seguridad de contenido del sitio no admite estilo incrustado. Si el fichero
// todavia no existe, la pagina sale sin adornos, que tampoco pasa nada.
echo '<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bit &amp; Breakfast</title>
<link rel="stylesheet" href="/estilo.css">
</head>
<body>
<main>
<h1>Bit &amp; Breakfast</h1>
<p>Todav&iacute;a no hay ninguna edici&oacute;n publicada.</p>
</main>
</body>
</html>
';
