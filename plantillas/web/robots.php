<?php
/**
 * robots.txt del sitio publicado. Recibe $base.
 *
 * Se cierra la zona privada y la que no tiene sentido indexar. No se usa
 * Disallow para esconder nada sensible: eso lo hace el .htaccess con un 403,
 * porque robots.txt es publico y una lista de rutas prohibidas es un mapa
 * para quien busca justo eso.
 *
 * Los rastreadores de IA salen por su nombre, aunque las reglas sean las
 * mismas que las de *: un rastreador que tiene grupo propio ignora el de *,
 * asi que si algun dia se quiere cerrar a uno solo, basta con quitarlo de la
 * lista de lib/seo.php -seo_rastreadores_ia()- y nada mas. Y dejarlos entrar
 * es una decision, no un descuido: este sitio vive de que lo lean y lo citen,
 * tambien quien contesta por un asistente, y cada noticia ya enlaza a su
 * fuente original.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/lib/seo.php';

$agentes_ia = array_keys(seo_rastreadores_ia());

?>
User-agent: *
Allow: /
Disallow: /panel/
Disallow: /api/

# Rastreadores de IA: entrenamiento, busqueda y lectura a peticion de una
# persona. Mismas reglas que el resto.
<?php foreach ($agentes_ia as $agente): ?>
User-agent: <?= $agente ?>

<?php endforeach; ?>
Allow: /
Disallow: /panel/
Disallow: /api/

Sitemap: <?= $base ?>/sitemap.xml
