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
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';

$enlace_activo = 'temas';
$alta_abierta  = $alta_abierta ?? false;
$categorias    = bits_categorias();
$otros         = $otros ?? [];

require_once __DIR__ . '/iconos.php';

$url = web_url_tema($base, (string) $tema['slug']);

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= web_e($tema['nombre']) ?> · Bit &amp; Breakfast</title>
<meta name="description" content="Todo lo que ha publicado Bit &amp; Breakfast sobre <?= web_e($tema['nombre']) ?> en tecnología hotelera.">
<link rel="canonical" href="<?= web_e($url) ?>">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version) ?>">
<meta name="theme-color" content="#060a18">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="<?= web_e($tema['nombre']) ?> en Bit &amp; Breakfast">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= web_e($url) ?>">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido">
  <header class="edicion-cabecera ficha-cabecera" data-tema="<?= web_e($tema['slug']) ?>">
    <p class="sello">Tema</p>

    <h1 class="ficha-titulo">
      <span class="ficha-icono"><?= web_icono((string) $tema['slug']) ?></span>
      <?= web_e($tema['nombre']) ?>
    </h1>

    <p class="datos">
      <?= (int) $tema['bits'] ?> bit<?= (int) $tema['bits'] === 1 ? '' : 's' ?> publicado<?= (int) $tema['bits'] === 1 ? '' : 's' ?>
    </p>
  </header>

  <?php if (!$bits): ?>
    <p class="vacio">Todavía no hay nada publicado en este tema.</p>
  <?php endif; ?>

  <ul class="lista-fichas">
  <?php foreach ($bits as $bit): ?>
    <li>
      <h2>
        <a href="<?= web_e(web_url_edicion($base, (string) $bit['slug'])) ?>#bit-<?= (int) $bit['id'] ?>">
          <?= web_e($bit['titular']) ?>
        </a>
      </h2>
      <p class="datos">
        <time datetime="<?= web_e((string) $bit['fecha_prevista']) ?>"><?= web_e(web_fecha_larga((string) $bit['fecha_prevista'])) ?></time>
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
