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
 *
 * Cada URL lleva <lastmod> cuando hay una fecha real que darle -el dia de
 * un bit, la revision a mano de una pagina de Recursos-. Las paginas sin
 * una fecha propia que contar -portada, temas.html, medios.html, sobre.html,
 * buscar.html, archivo.html- se quedan sin el campo: inventar una fecha
 * seria peor que no darla.
 */

declare(strict_types=1);

$dias   = $dias ?? [];
$temas  = $temas ?? [];
$medios = $medios ?? [];

// Tendencias no se revisa a mano como Cifras o Cumplimiento: se recalcula
// entera cada vez que hay un bit nuevo, asi que su fecha real es la del
// bit mas reciente, no la del dia en que se toco por ultima vez la
// plantilla.
$tendencias_lastmod = substr((string) ($dias[0]['dia'] ?? ''), 0, 10);

// El glosario no lleva una fecha de revision propia -no caduca como Cifras
// o Cumplimiento, se actualiza cuando entra una sigla nueva-, asi que su
// fecha real es la del ultimo despliegue que toco su fichero de datos.
$glosario_lastmod = gmdate('Y-m-d', (int) @filemtime(__DIR__ . '/../../lib/glosario.php'));

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
  <url>
    <loc><?= web_e($base) ?>/estadisticas.html</loc>
    <lastmod><?= web_e(cifras_revisado()) ?></lastmod>
  </url>
  <url>
    <loc><?= web_e($base) ?>/cumplimiento.html</loc>
    <lastmod><?= web_e(cumplimiento_revisado()) ?></lastmod>
  </url>
  <?php if ($tendencias_lastmod !== ''): ?>
  <url>
    <loc><?= web_e($base) ?>/tendencias.html</loc>
    <lastmod><?= web_e($tendencias_lastmod) ?></lastmod>
  </url>
  <?php else: ?>
  <url><loc><?= web_e($base) ?>/tendencias.html</loc></url>
  <?php endif; ?>
  <url>
    <loc><?= web_e($base) ?>/glosario.html</loc>
    <lastmod><?= web_e($glosario_lastmod) ?></lastmod>
  </url>
  <url>
    <loc><?= web_e($base) ?>/agentica.html</loc>
    <lastmod><?= web_e(agentica_revisado()) ?></lastmod>
  </url>
  <url>
    <loc><?= web_e($base) ?>/calendario.html</loc>
    <lastmod><?= web_e(calendario_revisado()) ?></lastmod>
  </url>

  <?php foreach ($dias as $dia): ?>
  <url>
    <loc><?= web_e(web_url_dia($base, (string) $dia['dia'])) ?></loc>
    <lastmod><?= web_e(substr((string) $dia['dia'], 0, 10)) ?></lastmod>
  </url>
  <?php endforeach; ?>

  <?php foreach ($temas as $tema): ?>
  <url>
    <loc><?= web_e(web_url_tema($base, (string) $tema['slug'])) ?></loc>
    <?php if (!empty($tema['ultimo'])): ?>
    <lastmod><?= web_e((string) $tema['ultimo']) ?></lastmod>
    <?php endif; ?>
  </url>
  <?php endforeach; ?>

  <?php foreach ($medios as $medio): ?>
  <url>
    <loc><?= web_e(web_url_medio($base, (string) $medio['slug'])) ?></loc>
    <?php if (!empty($medio['ultimo'])): ?>
    <lastmod><?= web_e((string) $medio['ultimo']) ?></lastmod>
    <?php endif; ?>
  </url>
  <?php endforeach; ?>
</urlset>
