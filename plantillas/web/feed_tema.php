<?php
/**
 * RSS 2.0 de un solo tema. Recibe $tema -slug y nombre-, $bits y $base.
 *
 * Lo mismo que feed.xml, pero filtrado a una categoria. A quien solo le
 * interesa Pagos y fraude o Revenue y RMS, suscribirse al feed general es
 * suscribirse a diez temas para leer el suyo. Vive aparte de feed.php, no
 * detras de un parametro mas, para que un cambio pensado para este feed no
 * pueda romper el general por accidente.
 *
 * Mismo formato que el general: el enlace de cada item va al ancla del bit
 * en la pagina de su dia, no directo a la fuente, porque el contexto -las
 * demas fuentes que cuentan lo mismo, el tema- es lo que este sitio anade.
 */

declare(strict_types=1);

$tema = $tema ?? ['slug' => '', 'nombre' => ''];
$bits = $bits ?? [];

// Cincuenta es lo que cabe en un lector sin que parezca un volcado, igual
// que en el feed general.
$bits = array_slice($bits, 0, 50);

$actualizado = $bits
    ? web_fecha_rss(substr((string) $bits[0]['dia'], 0, 10))
    : web_fecha_rss(gmdate('Y-m-d'));

$url_tema = web_url_tema($base, (string) $tema['slug']);
$url_self = rtrim($url_tema, '/') . '/feed.xml';

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>Bit &amp; Breakfast · <?= web_e((string) $tema['nombre']) ?></title>
    <link><?= web_e($url_tema) ?></link>
    <description>Tecnología hotelera en español: solo <?= web_e((string) $tema['nombre']) ?>, con enlace a la fuente.</description>
    <language>es-ES</language>
    <lastBuildDate><?= web_e($actualizado) ?></lastBuildDate>
    <atom:link href="<?= web_e($url_self) ?>" rel="self" type="application/rss+xml"/>

    <?php foreach ($bits as $bit): ?>
      <?php
        $dia    = substr((string) $bit['dia'], 0, 10);
        $enlace = web_url_dia($base, $dia) . '#bit-' . (int) $bit['id'];

        $descripcion = '<p>' . web_e(texto_recortar((string) $bit['cuerpo'], 400)) . '</p>';

        if (trim((string) ($bit['por_que'] ?? '')) !== '') {
            $descripcion .= '<p><strong>Por qué importa.</strong> ' . web_e((string) $bit['por_que']) . '</p>';
        }

        if (!empty($bit['fuente'])) {
            $descripcion .= '<p>' . web_e('Fuente: ' . (string) $bit['fuente']) . '</p>';
        }
      ?>
    <item>
      <title><?= web_e((string) $bit['titular']) ?></title>
      <link><?= web_e($enlace) ?></link>
      <guid isPermaLink="false">bitandbreakfast-bit-<?= (int) $bit['id'] ?></guid>
      <pubDate><?= web_e(web_fecha_rss($dia)) ?></pubDate>
      <description><?= web_e($descripcion) ?></description>
    </item>
    <?php endforeach; ?>
  </channel>
</rss>
