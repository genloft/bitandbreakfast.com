<?php
/**
 * robots.txt del sitio publicado. Recibe $base.
 *
 * Se cierra la zona privada y la que no tiene sentido indexar. No se usa
 * Disallow para esconder nada sensible: eso lo hace el .htaccess con un 403,
 * porque robots.txt es publico y una lista de rutas prohibidas es un mapa
 * para quien busca justo eso.
 */

declare(strict_types=1);

?>
User-agent: *
Allow: /
Disallow: /panel/
Disallow: /api/

# No hay directiva Sitemap porque no hay sitemap: el archivo enlaza a todas
# las ediciones y el sitio cabe entero en dos saltos desde la portada.
# <?= $base ?>/archivo.html
