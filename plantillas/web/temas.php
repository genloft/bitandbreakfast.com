<?php
/**
 * Indice de temas.
 *
 * Once tematicas y lo que ha entrado en cada una. Es el mapa del radar: dice
 * de que se esta hablando en tecnologia hotelera y de que no.
 *
 * Recibe $temas, $medios y $base.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';

$enlace_activo = 'temas';
$alta_abierta  = $alta_abierta ?? false;
$temas         = $temas ?? [];
$medios        = $medios ?? [];

require_once __DIR__ . '/iconos.php';

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Temas · Bit &amp; Breakfast</title>
<meta name="description" content="Los temas de tecnologia hotelera que cubre Bit &amp; Breakfast, con todo lo publicado en cada uno.">
<link rel="canonical" href="<?= web_e($base) ?>/temas.html">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="icon" href="<?= web_e($base) ?>/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version) ?>">
<meta name="theme-color" content="#060a18">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="Temas en Bit &amp; Breakfast">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= web_e($base) ?>/temas.html">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido">
  <header class="edicion-cabecera">
    <p class="sello">Temas</p>
    <h1>De qué va esto</h1>
    <p class="datos"><?= count($temas) ?> temas con algo publicado</p>
  </header>

  <?php if (!$temas): ?>
    <p class="vacio">Todavía no hay nada publicado.</p>
  <?php endif; ?>

  <ul class="rejilla-fichas">
  <?php foreach ($temas as $tema): ?>
    <li data-tema="<?= web_e($tema['slug']) ?>">
      <a href="<?= web_e(web_url_tema($base, (string) $tema['slug'])) ?>">
        <span class="ficha-icono"><?= web_icono((string) $tema['slug']) ?></span>
        <span class="rejilla-nombre"><?= web_e($tema['nombre']) ?></span>
        <span class="rejilla-cuenta"><?= (int) $tema['bits'] ?> bit<?= (int) $tema['bits'] === 1 ? '' : 's' ?></span>
      </a>
    </li>
  <?php endforeach; ?>
  </ul>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
