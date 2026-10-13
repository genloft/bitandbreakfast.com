<?php
/**
 * Ficha de un tema: todo lo publicado sobre el.
 *
 * Sustituye a las fichas de proveedor, y el cambio no es cosmetico. Una ficha
 * de proveedor contesta "que se ha dicho de Mews", que es una pregunta de
 * Mews. Una ficha de tema contesta "que esta pasando con los pagos", que es la
 * pregunta de un hotel. El radar es para el segundo.
 *
 * Recibe $tema -slug, nombre, bits-, $bits y $base.
 *
 * Lleva su propio RSS -t/<tema>/feed.xml, escrito por cron/publicar.php-
 * porque a quien solo le interesa Pagos y fraude o Revenue y RMS suscribirse
 * al feed general es suscribirse a diez temas para leer uno.
 *
 * Encima de la lista de bits van cuatro cosas que ya existían en otro
 * sitio del propio proyecto, no datos nuevos: qué es el tema
 * (bits_categoria_descripcion(), evergreen), las siglas del glosario que
 * enlazan aquí (glosario_por_tema(), el camino de vuelta que ya preveía
 * plantillas/web/glosario.php), los hitos normativos que le tocan
 * (cumplimiento_por_tema()) y las cifras que le tocan (cifras_por_tema()).
 * Las cuatro son opcionales -un tema puede no tener ninguna cifra o ninguna
 * norma asociada- y no se pintan cuando vienen vacías.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';

$enlace_activo = 'temas';
$alta_abierta  = $alta_abierta ?? false;
$categorias    = bits_categorias();
$otros         = $otros ?? [];

require_once __DIR__ . '/iconos.php';

$url      = web_url_tema($base, (string) $tema['slug']);
$url_feed = rtrim($url, '/') . '/feed.xml';

$descripcion_tema = bits_categoria_descripcion((string) $tema['slug']);
$siglas_tema      = glosario_por_tema((string) $tema['slug']);
$normas_tema      = cumplimiento_por_tema((string) $tema['slug']);
$cifras_tema      = cifras_por_tema((string) $tema['slug']);

$seo = [
    'titulo' => $tema['nombre'] . ': noticias de tecnología hotelera · Bit & Breakfast',
    'titulo_social' => $tema['nombre'] . ' en tecnología hotelera',
    'descripcion' => $descripcion_tema !== ''
        ? seo_primera_frase($descripcion_tema)
        : 'Todo lo que ha publicado Bit & Breakfast sobre ' . $tema['nombre'] . ' en tecnología hotelera.',
    'url' => $url,
    'tipo_pagina' => 'CollectionPage',
    'grafo' => [
        seo_lista(
            array_map(static fn (array $b): array => [
                'nombre' => (string) $b['titular'],
                'url'    => web_url_dia($base, (string) ($b['dia'] ?? '')) . '#bit-' . (int) $b['id'],
            ], array_slice($bits, 0, 30)),
            $tema['nombre']
        ),
    ],
    'theme' => '#060a18',
    'rss' => ['titulo' => 'Bit & Breakfast · ' . $tema['nombre'], 'url' => $url_feed],
];

?><!doctype html>
<html lang="es">
<head>
<?php require __DIR__ . '/seo.php'; ?>
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<?php
  $migas = [
      ['nombre' => 'Portada', 'url' => $base . '/'],
      ['nombre' => 'Temas', 'url' => $base . '/temas.html'],
      ['nombre' => (string) $tema['nombre'], 'url' => $url],
  ];
  require __DIR__ . '/migas.php';
?>

<main id="contenido">
  <header class="edicion-cabecera ficha-cabecera" data-tema="<?= web_e($tema['slug']) ?>">
    <p class="sello">Tema</p>

    <h1 class="ficha-titulo">
      <span class="ficha-icono"><?= web_icono((string) $tema['slug']) ?></span>
      <?= web_e($tema['nombre']) ?>
    </h1>

    <p class="datos">
      <?= (int) $tema['bits'] ?> bit<?= (int) $tema['bits'] === 1 ? '' : 's' ?> publicado<?= (int) $tema['bits'] === 1 ? '' : 's' ?>
      <span class="punto">·</span>
      <a href="<?= web_e($url_feed) ?>">RSS de este tema</a>
    </p>
  </header>

  <?php if ($descripcion_tema !== ''): ?>
    <p class="intro"><?= web_e($descripcion_tema) ?></p>
  <?php endif; ?>

  <?php if ($siglas_tema || $normas_tema || $cifras_tema): ?>
    <section class="explorar" aria-labelledby="relacionado-titulo">
      <h2 id="relacionado-titulo">Más sobre este tema</h2>

      <?php if ($siglas_tema): ?>
        <h3 class="explorar-grupo">Siglas del glosario</h3>
        <ul class="nube nube-siglas">
          <?php foreach ($siglas_tema as $sigla): ?>
            <li>
              <a href="<?= web_e($base) ?>/glosario.html#<?= web_e(web_slug_seguro($sigla['sigla'])) ?>">
                <span class="nube-nombre"><?= web_e($sigla['sigla']) ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if ($normas_tema): ?>
        <h3 class="explorar-grupo">Cumplimiento</h3>
        <ul class="nube nube-siglas">
          <?php foreach ($normas_tema as $norma): ?>
            <li>
              <a href="<?= web_e($base) ?>/cumplimiento.html#<?= web_e(web_slug_seguro($norma['norma'])) ?>">
                <span class="nube-nombre"><?= web_e($norma['norma']) ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if ($cifras_tema): ?>
        <h3 class="explorar-grupo">Cifras</h3>
        <ul class="nube nube-siglas">
          <?php foreach ($cifras_tema as $grupo): ?>
            <li>
              <a href="<?= web_e($base) ?>/estadisticas.html#<?= web_e(web_slug_seguro($grupo['tema'])) ?>">
                <span class="nube-nombre"><?= web_e($grupo['tema']) ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <?php if (!$bits): ?>
    <p class="vacio">Todavía no hay nada publicado en este tema.</p>
  <?php endif; ?>

  <ul class="lista-fichas">
  <?php foreach ($bits as $bit): ?>
    <li>
      <h2>
        <a href="<?= web_e(web_url_dia($base, (string) $bit['dia'])) ?>#bit-<?= (int) $bit['id'] ?>">
          <?= web_e($bit['titular']) ?>
        </a>
      </h2>
      <p class="datos">
        <time datetime="<?= web_e(substr((string) $bit['dia'], 0, 10)) ?>"><?= web_e(web_fecha_larga(substr((string) $bit['dia'], 0, 10))) ?></time>
        <?php if (!empty($bit['fuente'])): ?>
          <span class="punto">·</span>
          <a href="<?= web_e(web_url_medio($base, web_slug_medio((string) $bit['fuente']))) ?>"><?= web_e($bit['fuente']) ?></a>
        <?php endif; ?>
      </p>
      <?php if (trim((string) ($bit['por_que'] ?? '')) !== ''): ?>
        <p class="resumen"><?= web_e($bit['por_que']) ?></p>
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
  </ul>

  <?php if ($otros): ?>
    <section class="explorar">
      <h3 class="explorar-grupo">Otros temas</h3>
      <ul class="nube nube-temas">
      <?php foreach ($otros as $otro): ?>
        <li data-tema="<?= web_e($otro['slug']) ?>">
          <a href="<?= web_e(web_url_tema($base, (string) $otro['slug'])) ?>">
            <?= web_icono((string) $otro['slug'], 'icono icono-mini') ?>
            <span class="nube-nombre"><?= web_e($otro['nombre']) ?></span>
            <span class="nube-cuenta"><?= (int) $otro['bits'] ?></span>
          </a>
        </li>
      <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
