<?php
/**
 * Indice de ediciones. Recibe $ediciones y $base.
 */

declare(strict_types=1);

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Archivo · Bit &amp; Breakfast</title>
<meta name="description" content="Todas las ediciones publicadas de Bit &amp; Breakfast.">
<link rel="canonical" href="<?= web_e($base) ?>/archivo.html">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css">
</head>
<body>

<header class="cabecera">
  <p class="marca"><a href="<?= web_e($base) ?>/">Bit &amp; Breakfast</a></p>
  <p class="promesa">Cinco minutos de tecnología hotelera a la semana.</p>
</header>

<main>
  <h1>Archivo</h1>

  <?php if (!$ediciones): ?>
    <p class="vacio">Todavía no hay ninguna edición publicada.</p>
  <?php else: ?>
    <ul class="archivo">
    <?php foreach ($ediciones as $edicion): ?>
      <li>
        <a href="<?= web_e(web_url_edicion($base, (string) $edicion['slug'])) ?>">
          <?= web_e(trim((string) $edicion['titulo']) !== ''
              ? (string) $edicion['titulo']
              : 'Edición ' . (int) $edicion['numero']) ?>
        </a>
        <span class="datos">
          nº <?= (int) $edicion['numero'] ?> ·
          <time datetime="<?= web_e((string) $edicion['fecha_prevista']) ?>"><?= web_e(web_fecha_larga((string) $edicion['fecha_prevista'])) ?></time>
        </span>
      </li>
    <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</main>

<footer class="pie">
  <p><a href="<?= web_e($base) ?>/">Última edición</a> ·
     <a href="<?= web_e($base) ?>/feed.xml">RSS</a></p>
</footer>

</body>
</html>
