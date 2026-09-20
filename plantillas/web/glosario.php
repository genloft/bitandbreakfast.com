<?php
/**
 * Glosario: las siglas del sector, en una frase cada una.
 *
 * La promesa del sitio es que un directivo no tenga que salir de aqui para
 * entender lo que lee. Un bit puede mencionar "NDC" o "RevPAR" sin
 * explicarlo -explicarlo cada vez seria repetirse en cada noticia-, y hasta
 * ahora quien no conocia la sigla tenia que buscarla en otro sitio. Esta
 * pagina es la unica excepcion a esa regla: la sigla se explica una vez,
 * aqui, y el resto del sitio enlaza a ella en vez de repetirse.
 *
 * Como /estadisticas.html, vive en un array escrito a mano: no son datos que
 * cambien, son definiciones, y no hay consulta que las genere. A diferencia
 * de /estadisticas.html, no caducan -un PMS sigue siendo un PMS el año que
 * viene- y por eso no lleva fecha de revision ni aviso de mantenimiento.
 *
 * Cada termino puede enlazar a un tema (el mismo catalogo de
 * bits_categorias()) para quien quiera ver que se ha publicado sobre eso,
 * no solo que significa.
 *
 * Los terminos en si viven en lib/glosario.php, no aqui: plantillas/web/tema.php
 * necesita poder preguntar que siglas pertenecen a un tema sin ejecutar esta
 * plantilla entera. Mismo patron que lib/cumplimiento.php.
 *
 * Recibe $base y $alta_abierta.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';

$enlace_activo = 'glosario';
$alta_abierta  = $alta_abierta ?? false;

require_once __DIR__ . '/iconos.php';

// Alfabetico por sigla, no por tema: es un diccionario, no un indice de
// categorias -eso ya lo tiene /temas.html-, y lo que se busca aqui es la
// palabra que no se entendio, no el area a la que pertenece.
$terminos = glosario_terminos();

$descripcion = 'Las siglas de la tecnología hotelera -PMS, RMS, NDC, RevPAR y el resto-, explicadas en una frase cada una.';

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Glosario · Bit &amp; Breakfast</title>
<meta name="description" content="<?= web_e($descripcion) ?>">
<link rel="canonical" href="<?= web_e($base) ?>/glosario.html">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="icon" href="<?= web_e($base) ?>/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version) ?>">
<meta name="theme-color" content="#12100d">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="Glosario de tecnología hotelera">
<meta property="og:description" content="<?= web_e($descripcion) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= web_e($base) ?>/glosario.html">
<meta property="og:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido">
  <header class="edicion-cabecera">
    <p class="sello">Glosario</p>
    <h1>Las siglas, en una frase</h1>
    <p class="datos"><?= count($terminos) ?> términos del sector, sin salir del sitio a buscarlos</p>
  </header>

  <p class="intro">Un bit menciona "NDC" o "RevPAR" sin explicarlo, porque explicarlo en cada noticia sería repetirse. Aquí se explica una vez. Ni caducan ni llevan fuente externa que citar: son definiciones, no cifras.</p>

  <dl class="glosario-lista">
    <?php foreach ($terminos as $termino): ?>
      <div class="glosario-fila" id="<?= web_e(web_slug_seguro($termino['sigla'])) ?>">
        <dt>
          <?= web_e($termino['sigla']) ?>
          <?php if ($termino['nombre'] !== $termino['sigla']): ?>
            <span class="glosario-nombre"><?= web_e($termino['nombre']) ?></span>
          <?php endif; ?>
        </dt>
        <dd>
          <?= web_e($termino['definicion']) ?>
          <?php if ($termino['tema'] !== null): ?>
            <a class="glosario-tema" href="<?= web_e(web_url_tema($base, $termino['tema'])) ?>">
              <?= web_icono($termino['tema'], 'icono icono-mini') ?>
              Ver lo publicado
            </a>
          <?php endif; ?>
        </dd>
      </div>
    <?php endforeach; ?>
  </dl>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
