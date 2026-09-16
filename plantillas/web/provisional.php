<?php
/**
 * La portada mientras no hay ninguna edicion publicada.
 *
 * La escriben dos sitios distintos y por eso vive aqui: el instalador, al
 * terminar, y index.php cuando la raiz se pide sin que haya nada generado.
 * Usa la misma cabecera, el mismo pie y la misma hoja de estilo que el sitio
 * de verdad, para que el primer dia no parezca otra web.
 *
 * Recibe $base, que puede ser cadena vacia para enlaces relativos a la raiz.
 */

declare(strict_types=1);

$base          = $base ?? '';
$enlace_activo = 'portada';
$alta_abierta  = $alta_abierta ?? false;

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bit &amp; Breakfast · Radar de tecnología hotelera</title>
<meta name="description" content="Radar de tecnología hotelera. Cinco minutos de lectura a la semana. La primera edición está en camino.">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css">
<meta name="theme-color" content="#f6f5f2">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="Bit &amp; Breakfast">
<meta property="og:description" content="Radar de tecnología hotelera. Cinco minutos de lectura a la semana.">
<meta property="og:type" content="website">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido">
  <header class="edicion-cabecera">
    <p class="sello">Próximamente</p>
    <h1>La primera edición está en camino</h1>
    <p class="datos">Sale los martes</p>
  </header>

  <div class="intro">
    <p>Bit &amp; Breakfast rastrea el sector, agrupa las noticias que cuentan lo
    mismo y se queda con las quince o veinte que de verdad cambian algo en un
    hotel. Ni una más.</p>
    <p>Es un radar, no un agregador: filtra duro y enseña poco.</p>
  </div>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
