<?php
/**
 * RSS 2.0 de las ediciones. Recibe $ediciones y $base.
 *
 * Un item por edicion y no uno por bit: el lector se suscribe a la edicion
 * semanal, que es la unidad que se promete. Veinte items sueltos cada martes
 * serian justo el agregador que este proyecto no quiere ser.
 */

declare(strict_types=1);

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";

?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
  <title>Bit &amp; Breakfast</title>
  <link><?= web_e($base) ?>/</link>
  <atom:link href="<?= web_e($base) ?>/feed.xml" rel="self" type="application/rss+xml"/>
  <description>Radar de tecnología hotelera. Cinco minutos de lectura a la semana.</description>
  <language>es</language>
  <lastBuildDate><?= web_fecha_rss(gmdate('Y-m-d H:i:s')) ?></lastBuildDate>

<?php foreach ($ediciones as $edicion): ?>
<?php $url = web_url_edicion($base, (string) $edicion['slug']); ?>
  <item>
    <title><?= web_e(trim((string) $edicion['titulo']) !== ''
        ? (string) $edicion['titulo']
        : 'Edición ' . (int) $edicion['numero']) ?></title>
    <link><?= web_e($url) ?></link>
    <guid isPermaLink="true"><?= web_e($url) ?></guid>
    <pubDate><?= web_fecha_rss((string) ($edicion['fecha_envio'] ?: $edicion['fecha_prevista'])) ?></pubDate>
    <description><?= web_e(trim((string) $edicion['intro']) !== ''
        ? (string) $edicion['intro']
        : 'Edición ' . (int) $edicion['numero'] . ' de Bit &amp; Breakfast.') ?></description>
  </item>
<?php endforeach; ?>
</channel>
</rss>
