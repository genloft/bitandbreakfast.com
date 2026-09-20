<?php
/**
 * Ficha de un medio: todo lo que ha salido de el.
 *
 * Sirve para dos cosas distintas y las dos importan. Al lector le dice a quien
 * esta leyendo de verdad -si media edicion sale de un mismo sitio, se ve-. Y a
 * quien monta el radar le dice que fuentes estan aportando y cuales llevan
 * meses sin pasar el filtro.
 *
 * Recibe $medio -slug, nombre, bits, url-, $bits y $base.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';

$enlace_activo = 'medios';
$alta_abierta  = $alta_abierta ?? false;
$categorias    = bits_categorias();
$otros         = $otros ?? [];   // otros medios

require_once __DIR__ . '/iconos.php';

$url = web_url_medio($base, (string) $medio['slug']);

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= web_e($medio['nombre']) ?> · Bit &amp; Breakfast</title>
<meta name="description" content="Lo que ha publicado Bit &amp; Breakfast a partir de <?= web_e($medio['nombre']) ?>.">
<link rel="canonical" href="<?= web_e($url) ?>">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="icon" href="<?= web_e($base) ?>/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version) ?>">
<meta name="theme-color" content="#060a18">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="<?= web_e($medio['nombre']) ?> en Bit &amp; Breakfast">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= web_e($url) ?>">
<meta property="og:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<?php
  $migas = [
      ['nombre' => 'Portada', 'url' => $base . '/'],
      ['nombre' => 'Medios', 'url' => $base . '/medios.html'],
      ['nombre' => (string) $medio['nombre'], 'url' => $url],
  ];
  require __DIR__ . '/migas.php';
?>

<main id="contenido">
  <header class="edicion-cabecera ficha-cabecera">
    <p class="sello">Medio</p>

    <h1 class="ficha-titulo">
      <span class="ficha-icono"><?= web_icono_ui('medio') ?></span>
      <?= web_e($medio['nombre']) ?>
    </h1>

    <p class="datos">
      <?= (int) $medio['bits'] ?> bit<?= (int) $medio['bits'] === 1 ? '' : 's' ?> publicado<?= (int) $medio['bits'] === 1 ? '' : 's' ?>
      <?php if (!empty($medio['url'])): ?>
        <span class="punto">·</span>
        <a href="<?= web_e($medio['url']) ?>" rel="nofollow noopener">ir al medio</a>
      <?php endif; ?>
    </p>
  </header>

  <?php if (!$bits): ?>
    <p class="vacio">Todavía no ha salido nada de este medio.</p>
  <?php endif; ?>

  <ul class="lista-fichas">
  <?php foreach ($bits as $bit): ?>
    <li>
      <h2>
        <a href="<?= web_e(web_url_dia($base, (string) $bit['dia'])) ?>#bit-<?= (int) $bit['id'] ?>">
          <?= web_e($bit['titular']) ?>
        </a>
      </h2>
      <?php $suyo = bits_categoria_canonica((string) $bit['categoria']) ?: 'tecnologia-general'; ?>
      <p class="datos" data-tema="<?= web_e($suyo) ?>">
        <time datetime="<?= web_e(substr((string) $bit['dia'], 0, 10)) ?>"><?= web_e(web_fecha_larga(substr((string) $bit['dia'], 0, 10))) ?></time>
        <span class="punto">·</span>
        <a href="<?= web_e(web_url_tema($base, $suyo)) ?>"><?= web_e($categorias[$suyo] ?? $suyo) ?></a>
      </p>
      <?php if (trim((string) ($bit['por_que'] ?? '')) !== ''): ?>
        <p class="resumen"><?= web_e($bit['por_que']) ?></p>
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
  </ul>

  <?php if ($otros): ?>
    <section class="explorar">
      <h3 class="explorar-grupo">Otros medios</h3>
      <ul class="nube nube-medios">
      <?php foreach ($otros as $otro): ?>
        <li>
          <a href="<?= web_e(web_url_medio($base, (string) $otro['slug'])) ?>">
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
