<?php
/**
 * Explorador del archivo: buscar y filtrar. Recibe $base, $total y
 * $alta_abierta.
 *
 * Todo ocurre en el navegador contra indice.json, que genera
 * cron/publicar.php. No hay endpoint que consultar, asi que no hay nada que
 * se pueda tumbar ni limitar: el sitio sigue siendo ficheros estaticos.
 *
 * Los grupos de filtros se pintan vacios y los rellena el guion con lo que
 * haya en el indice. Asi no hay dos catalogos que mantener, y una categoria
 * nueva aparece sola en cuanto se publica el primer bit que la usa.
 *
 * Es el unico sitio del proyecto con JavaScript, es propio y sin
 * dependencias. Sin el, la pagina lo dice y manda al archivo: nadie se queda
 * sin poder llegar al contenido.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';

$enlace_activo = 'buscar';
$alta_abierta  = $alta_abierta ?? false;

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Explorar · Bit &amp; Breakfast</title>
<meta name="description" content="Busca y filtra en todo lo publicado por Bit &amp; Breakfast: por tema, ámbito, idioma y medio.">
<link rel="canonical" href="<?= web_e($base) ?>/buscar.html">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version) ?>">
<meta name="theme-color" content="#12100d">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="Explorar Bit &amp; Breakfast">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= web_e($base) ?>/buscar.html">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido" class="explorador">
  <header class="edicion-cabecera cabecera-explorador">
    <p class="sello">Explorar</p>
    <h1>En todo lo publicado</h1>
    <p class="datos"><?= (int) $total ?> bit<?= (int) $total === 1 ? '' : 's' ?> en el índice</p>
  </header>

  <div class="rail">
  <form class="buscador" role="search" action="<?= web_e($base) ?>/buscar.html" method="get">
    <label for="q">Buscar</label>
    <input id="q" name="q" type="search" autocomplete="off"
           placeholder="mews, ransomware, aepd…">
  </form>

  <div class="facetas" id="facetas">
    <div class="faceta" data-faceta="c">
      <h3 id="faceta-c">Temática</h3>
      <div class="opciones" role="group" aria-labelledby="faceta-c"></div>
    </div>

    <div class="faceta" data-faceta="a">
      <h3 id="faceta-a">Ámbito</h3>
      <div class="opciones" role="group" aria-labelledby="faceta-a"></div>
    </div>

    <div class="faceta" data-faceta="l">
      <h3 id="faceta-l">Idioma</h3>
      <div class="opciones" role="group" aria-labelledby="faceta-l"></div>
    </div>

    <div class="faceta" data-faceta="fu">
      <h3 id="faceta-fu">Fuente</h3>
      <div class="opciones" role="group" aria-labelledby="faceta-fu"></div>
    </div>

    <button type="button" class="limpiar" id="limpiar" hidden>Quitar todos los filtros</button>
  </div>
  </div>

  <div class="resultados-panel">
  <p id="estado" class="datos">Escribe o toca un filtro. Se busca en el titular,
  en el «por qué importa», en el cuerpo, en el medio y en las empresas
  mencionadas.</p>

  <ul id="resultados" class="fichas"></ul>

  <noscript>
    <p class="vacio">El explorador necesita JavaScript. Sin él, el
    <a href="<?= web_e($base) ?>/archivo.html">archivo</a> lleva a todas las
    ediciones, las <a href="<?= web_e($base) ?>/temas.html">fichas de tema</a>
    y las de <a href="<?= web_e($base) ?>/medios.html">medio</a> agrupan lo
    publicado en cada uno.</p>
  </noscript>

  </div>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

<script src="<?= web_e($base) ?>/buscar.js?v=<?= web_e($version_js) ?>" defer></script>
</body>
</html>
