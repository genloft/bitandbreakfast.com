<?php
/**
 * Buscador del archivo. Recibe $base, $total y $alta_abierta.
 *
 * La busqueda ocurre entera en el navegador contra indice.json, que genera
 * cron/publicar.php. No hay endpoint que consultar, asi que no hay nada que
 * se pueda tumbar ni limitar: el sitio sigue siendo ficheros estaticos.
 *
 * Es el unico sitio del proyecto con JavaScript, y es propio y sin
 * dependencias. La politica de seguridad del sitio permite script-src 'self',
 * asi que no hace falta excepcion ninguna.
 *
 * Sin JavaScript la pagina lo dice y manda al archivo, que lleva a todas las
 * ediciones. Nadie se queda sin poder llegar al contenido.
 */

declare(strict_types=1);

$enlace_activo = 'buscar';
$alta_abierta  = $alta_abierta ?? false;

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Buscar · Bit &amp; Breakfast</title>
<meta name="description" content="Busca en todo lo publicado por Bit &amp; Breakfast: proveedores, incidentes, regulación y tecnología hotelera.">
<link rel="canonical" href="<?= web_e($base) ?>/buscar.html">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css">
<meta name="theme-color" content="#f6f5f2">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="Buscar en Bit &amp; Breakfast">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= web_e($base) ?>/buscar.html">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido">
  <header class="edicion-cabecera">
    <p class="sello">Buscar</p>
    <h1>En todo lo publicado</h1>
    <p class="datos"><?= (int) $total ?> bit<?= (int) $total === 1 ? '' : 's' ?> en el índice</p>
  </header>

  <form class="buscador" role="search" action="<?= web_e($base) ?>/buscar.html" method="get">
    <label for="q">Qué buscas</label>
    <input id="q" name="q" type="search" autocomplete="off"
           placeholder="mews, ransomware, aepd…" autofocus>
  </form>

  <p id="estado" class="datos">Escribe para buscar. Se busca en el titular, en
  el «por qué importa», en el cuerpo y en los proveedores mencionados.</p>

  <ul id="resultados" class="fichas"></ul>

  <noscript>
    <p class="vacio">El buscador necesita JavaScript. Sin él, el
    <a href="<?= web_e($base) ?>/archivo.html">archivo</a> lleva a todas las
    ediciones y el navegador puede buscar dentro de cada una.</p>
  </noscript>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

<script src="<?= web_e($base) ?>/buscar.js" defer></script>
</body>
</html>
