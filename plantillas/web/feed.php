<?php
/**
 * RSS 2.0 de las ediciones. Recibe $ediciones, $base y $titulares.
 *
 * Un item por edicion y no uno por bit: el lector se suscribe a la edicion
 * semanal, que es la unidad que se promete. Veinte items sueltos cada martes
 * serian justo el agregador que este proyecto no quiere ser.
 *
 * Pero un item que solo dice "Edicion 1" no le sirve a nadie: quien lee por
 * RSS decide ahi si abre o no. Por eso la descripcion lleva los titulares de
 * la edicion, que es lo que lleva dentro.
 */

declare(strict_types=1);

$titulares = $titulares ?? [];

/**
 * La descripcion de una edicion: su intro si la tiene, y siempre la lista de
 * titulares. Devuelve HTML ya escapado para meter dentro de <description>.
 *
 * Con guarda porque una plantilla se puede incluir dos veces en la misma
 * ejecucion -las pruebas generan la web mas de una vez- y redeclarar una
 * funcion es un error fatal.
 */
if (!function_exists('feed_descripcion')):
function feed_descripcion(array $edicion, array $bits): string
{
    $partes = [];
    $intro  = trim((string) ($edicion['intro'] ?? ''));

    if ($intro !== '') {
        $partes[] = '<p>' . web_e($intro) . '</p>';
    }

    if (!$bits) {
        $partes[] = '<p>' . web_e('Edición ' . (int) $edicion['numero'] . ' de Bit & Breakfast.') . '</p>';

        return implode('', $partes);
    }

    $partes[] = '<p>' . web_e(count($bits) === 1 ? 'Un bit:' : count($bits) . ' bits:') . '</p>';
    $partes[] = '<ul>';

    foreach ($bits as $bit) {
        $partes[] = '<li>' . web_e((string) $bit['titular']) . '</li>';
    }

    $partes[] = '</ul>';

    return implode('', $partes);
}
endif;

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
    <description><?= web_e(feed_descripcion($edicion, $titulares[(int) $edicion['id']] ?? [])) ?></description>
  </item>
<?php endforeach; ?>
</channel>
</rss>
