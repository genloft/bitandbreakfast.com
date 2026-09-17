<?php
/**
 * RSS 2.0 de lo ultimo publicado. Recibe $rio y $base.
 *
 * Un item por noticia, y no uno por edicion como antes. El cambio viene de lo
 * mismo que el resto: esto es un agregador, y a un agregador se le pide el
 * hilo, no el numero de la revista. Quien lee por RSS quiere que le llegue la
 * noticia cuando aparece, con su enlace a la fuente, no un aviso semanal de
 * que hay veinte cosas esperando en una pagina.
 *
 * El enlace del item va a la pagina del dia, al ancla de la noticia: asi se
 * llega al bit con su contexto -las demas fuentes que lo cuentan, el tema- y
 * desde ahi al medio original. Mandar directo a la fuente ahorraria un clic y
 * quitaria lo unico que este sitio anade.
 */

declare(strict_types=1);

$rio  = $rio ?? [];
$bits = [];

foreach ($rio as $tramo) {
    foreach ($tramo['bits'] as $bit) {
        $bit['dia'] = $bit['dia'] ?? $tramo['dia'];
        $bits[]     = $bit;
    }
}

// Cincuenta es lo que cabe en un lector sin que parezca un volcado. Lo que se
// cae por abajo sigue en la web, que es donde vive.
$bits = array_slice($bits, 0, 50);

$actualizado = $bits
    ? web_fecha_rss(substr((string) $bits[0]['dia'], 0, 10))
    : web_fecha_rss(gmdate('Y-m-d'));

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>Bit &amp; Breakfast</title>
    <link><?= web_e($base) ?>/</link>
    <description>Tecnología hotelera en español: lo que aparece cada día, con enlace a la fuente.</description>
    <language>es-ES</language>
    <lastBuildDate><?= web_e($actualizado) ?></lastBuildDate>
    <atom:link href="<?= web_e($base) ?>/feed.xml" rel="self" type="application/rss+xml"/>

    <?php foreach ($bits as $bit): ?>
      <?php
        $dia  = substr((string) $bit['dia'], 0, 10);
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
