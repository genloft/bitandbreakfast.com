<?php
/**
 * Indice de medios.
 *
 * De quien estamos leyendo. Sirve al lector -si media edicion sale del mismo
 * sitio, aqui se ve- y sirve para saber que fuentes aportan y cuales llevan
 * meses sin pasar el filtro.
 *
 * Recibe $temas, $medios y $base.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';

$enlace_activo = 'medios';
$alta_abierta  = $alta_abierta ?? false;
$temas         = $temas ?? [];
$medios        = $medios ?? [];

require_once __DIR__ . '/iconos.php';

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Medios · Bit &amp; Breakfast</title>
<meta name="description" content="Los medios de los que Bit &amp; Breakfast ha publicado algo, con su recuento.">
<link rel="canonical" href="<?= web_e($base) ?>/medios.html">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="icon" href="<?= web_e($base) ?>/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version) ?>">
<meta name="theme-color" content="#060a18">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="Medios en Bit &amp; Breakfast">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= web_e($base) ?>/medios.html">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido">
  <header class="edicion-cabecera">
    <p class="sello">Medios</p>
    <h1>A quién leemos</h1>
    <p class="datos"><?= count($medios) ?> medios con algo publicado</p>
  </header>

  <?php if (!$medios): ?>
    <p class="vacio">Todavía no hay nada publicado.</p>
  <?php endif; ?>

  <ul class="rejilla-fichas rejilla-medios">
  <?php foreach ($medios as $medio): ?>
    <li>
      <a href="<?= web_e(web_url_medio($base, (string) $medio['slug'])) ?>">
        <span class="ficha-icono"><?= web_icono_ui('medio') ?></span>
        <span class="rejilla-nombre"><?= web_e($medio['nombre']) ?></span>
        <span class="rejilla-cuenta"><?= (int) $medio['bits'] ?> bit<?= (int) $medio['bits'] === 1 ? '' : 's' ?></span>
      </a>
    </li>
  <?php endforeach; ?>
  </ul>

  <p class="letra-pequena explorar-pie">
    El radar rastrea bastantes más medios de los que aparecen aquí: estos son
    los que han contado algo que pasó el filtro.
  </p>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
