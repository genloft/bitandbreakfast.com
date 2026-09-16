<?php
/**
 * Ficha de un proveedor: todo lo que se ha publicado sobre el.
 *
 * Recibe $proveedor, $bits, $base y $alta_abierta.
 *
 * Es la pagina que convierte el archivo en algo consultable. Cuando alguien
 * se plantea cambiar de PMS, la pregunta no es "que paso esta semana" sino
 * "que ha pasado con Mews en el ultimo ano", y esta pagina la contesta de una
 * vez.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';

$enlace_activo = 'proveedores';
$alta_abierta  = $alta_abierta ?? false;
$url           = web_url_proveedor($base, (string) $proveedor['slug']);

$descripcion = sprintf(
    '%d bit%s publicado%s sobre %s en Bit & Breakfast.',
    count($bits),
    count($bits) === 1 ? '' : 's',
    count($bits) === 1 ? '' : 's',
    (string) $proveedor['nombre']
);

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= web_e($proveedor['nombre']) ?> · Bit &amp; Breakfast</title>
<meta name="description" content="<?= web_e($descripcion) ?>">
<link rel="canonical" href="<?= web_e($url) ?>">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version) ?>">
<meta name="theme-color" content="#12100d">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="<?= web_e($proveedor['nombre']) ?> en Bit &amp; Breakfast">
<meta property="og:description" content="<?= web_e($descripcion) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= web_e($url) ?>">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido">
  <header class="edicion-cabecera">
    <p class="sello">Proveedor</p>
    <h1><?= web_e($proveedor['nombre']) ?></h1>
    <p class="datos">
      <?= count($bits) ?> bit<?= count($bits) === 1 ? '' : 's' ?>
      <span class="punto">·</span>
      <a href="<?= web_e($base) ?>/proveedores.html">todos los proveedores</a>
    </p>
  </header>

  <?php if (!$bits): ?>
    <p class="vacio">Todavía no hemos publicado nada sobre este proveedor.</p>
  <?php else: ?>

    <ul class="fichas">
    <?php foreach ($bits as $bit): ?>
      <li>
        <h2>
          <a href="<?= web_e(web_url_edicion($base, (string) $bit['slug'])) ?>#bit-<?= (int) $bit['id'] ?>"><?= web_e($bit['titular']) ?></a>
        </h2>
        <p class="datos">
          Edición <?= (int) $bit['numero'] ?>
          <span class="punto">·</span>
          <time datetime="<?= web_e((string) $bit['fecha_prevista']) ?>"><?= web_e(web_fecha_larga((string) $bit['fecha_prevista'])) ?></time>
        </p>
        <?php if (trim((string) $bit['por_que']) !== ''): ?>
          <p class="resumen"><?= web_e($bit['por_que']) ?></p>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
    </ul>

    <p class="letra-pequena">Estas menciones las detecta el sistema buscando el
    nombre del proveedor y sus alias en el titular y el resumen de las fuentes.
    Que aparezca aquí no significa que la noticia vaya sobre él.</p>

  <?php endif; ?>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
