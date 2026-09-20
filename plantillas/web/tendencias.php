<?php
/**
 * Tendencias: que tema sube y cual baja, con los datos propios del radar.
 *
 * Es la version con datos de casa de la pagina de Cifras: aquella compara
 * España con el mundo usando informes ajenos; esta compara este trimestre
 * con el anterior usando lo que el propio radar ha publicado. Por eso no
 * lleva fuentes que citar -la fuente es esta misma base- y por eso, a
 * diferencia de Cifras, se regenera sola cada vez que hay bits nuevos: no
 * hay nada aqui que alguien tenga que revisar a mano.
 *
 * "Sube" o "baja" no es un juicio: el modo automatico no dice si eso es
 * bueno o malo, solo cuenta. Un tema que sube puede ser una moda pasajera y
 * uno que baja puede ser una guerra de precios que ya no hace ruido.
 *
 * Recibe $base, $tendencias y $alta_abierta.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';
$tendencias = $tendencias ?? [];

$enlace_activo = 'tendencias';
$alta_abierta  = $alta_abierta ?? false;

require_once __DIR__ . '/iconos.php';

$descripcion = 'Qué temas de tecnología hotelera suben y cuáles bajan: los últimos 90 días frente a los 90 anteriores.';

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tendencias · Bit &amp; Breakfast</title>
<meta name="description" content="<?= web_e($descripcion) ?>">
<link rel="canonical" href="<?= web_e($base) ?>/tendencias.html">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="icon" href="<?= web_e($base) ?>/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version) ?>">
<meta name="theme-color" content="#12100d">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="Tendencias en tecnología hotelera">
<meta property="og:description" content="<?= web_e($descripcion) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= web_e($base) ?>/tendencias.html">
<meta property="og:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido">
  <header class="edicion-cabecera">
    <p class="sello">Tendencias</p>
    <h1>Qué sube y qué baja</h1>
    <p class="datos">Los últimos 90 días frente a los 90 anteriores, tema a tema</p>
  </header>

  <p class="intro">Nada de esto lo dice un informe externo: sale de lo que este radar ha publicado. No es un trimestre natural -del 1 de enero al 31 de marzo-, son dos ventanas de 90 días completas y del mismo tamaño: comparar un trimestre a medias con uno ya cerrado siempre da una caída falsa. Y no dice si subir es bueno: solo cuenta.</p>

  <?php if ($tendencias): ?>
    <section class="cuadro" aria-label="Tendencias por tema">
      <?php foreach ($tendencias as $t): ?>
        <?php
          if ($t['nuevo']) {
              $frase = 'Tema nuevo: no había ningún bit de esto hace un trimestre.';
          } elseif ($t['delta'] === 0) {
              $frase = 'Sin cambios respecto al trimestre anterior.';
          } elseif ($t['porcentaje'] !== null) {
              $frase = ($t['delta'] > 0 ? 'Sube' : 'Baja') . ' un ' . abs($t['porcentaje']) . '% respecto al trimestre anterior.';
          } else {
              $frase = ($t['delta'] > 0 ? 'Sube' : 'Baja') . ' respecto al trimestre anterior, que no tenía ningún bit de esto.';
          }
        ?>
        <article class="cuadro-tarjeta">
          <h2 class="cuadro-tema ficha-titulo">
            <span class="ficha-icono"><?= web_icono($t['slug']) ?></span>
            <?= web_e($t['nombre']) ?>
          </h2>

          <div class="cuadro-cifras">
            <div class="cuadro-cifra">
              <p class="cuadro-ambito">Últimos 90 días</p>
              <p class="cuadro-valor"><?= (int) $t['actual'] ?></p>
              <p class="cuadro-detalle">bit<?= (int) $t['actual'] === 1 ? '' : 's' ?> publicado<?= (int) $t['actual'] === 1 ? '' : 's' ?></p>
            </div>
            <div class="cuadro-cifra">
              <p class="cuadro-ambito">90 días anteriores</p>
              <p class="cuadro-valor"><?= (int) $t['anterior'] ?></p>
              <p class="cuadro-detalle">bit<?= (int) $t['anterior'] === 1 ? '' : 's' ?> publicado<?= (int) $t['anterior'] === 1 ? '' : 's' ?></p>
            </div>
          </div>

          <p class="cuadro-destacado"><?= web_e($frase) ?></p>
        </article>
      <?php endforeach; ?>
    </section>
  <?php else: ?>
    <p class="vacio">Todavía no hay ciento ochenta días de historia para comparar un periodo con el anterior. Vuelve más adelante.</p>
  <?php endif; ?>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
