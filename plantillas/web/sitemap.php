<?php
/**
 * sitemap.xml: todas las direcciones que el sitio quiere que Google recorra.
 *
 * No existia porque el archivo ya enlaza a todo en dos saltos desde la
 * portada, y para un lector eso basta. Un rastreador no navega: sigue
 * <lastmod> para decidir que volver a mirar, y sin sitemap no tiene forma de
 * saber que un dia de hace tres meses no ha cambiado y uno de ayer si.
 *
 * Recibe $base, $dias, $temas y $medios: los mismos datos que ya calcula
 * publicar_pendiente() para el resto de paginas, no una consulta nueva.
 */

declare(strict_types=1);

$dias   = $dias ?? [];
$temas  = $temas ?? [];
$medios = $medios ?? [];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc><?= web_e($base) ?>/</loc>
    <changefreq>hourly</changefreq>
  </url>
  <url><loc><?= web_e($base) ?>/archivo.html</loc></url>
  <url><loc><?= web_e($base) ?>/buscar.html</loc></url>
  <url><loc><?= web_e($base) ?>/temas.html</loc></url>
  <url><loc><?= web_e($base) ?>/medios.html</loc></url>
  <url><loc><?= web_e($base) ?>/sobre.html</loc></url>
  <url><loc><?= web_e($base) ?>/estadisticas.html</loc></url>
  <url><loc><?= web_e($base) ?>/cumplimiento.html</loc></url>
  <url><loc><?= web_e($base) ?>/tendencias.html</loc></url>
  <url><loc><?= web_e($base) ?>/glosario.html</loc></url>
  <url><loc><?= web_e($base) ?>/agentica.html</loc></url>
  <url><loc><?= web_e($base) ?>/calendario.html</loc></url>

  <?php foreach ($dias as $dia): ?>
  <url>
    <loc><?= web_e(web_url_dia($base, (string) $dia['dia'])) ?></loc>
    <lastmod><?= web_e(substr((string) $dia['dia'], 0, 10)) ?></lastmod>
  </url>
  <?php endforeach; ?>

  <?php foreach ($temas as $tema): ?>
  <url><loc><?= web_e(web_url_tema($base, (string) $tema['slug'])) ?></loc></url>
  <?php endforeach; ?>

  <?php foreach ($medios as $medio): ?>
  <url><loc><?= web_e(web_url_medio($base, (string) $medio['slug'])) ?></loc></url>
  <?php endforeach; ?>
</urlset>
