<?php
/**
 * Indice de medios.
 *
 * De quien estamos leyendo. Sirve al lector -si media edicion sale del mismo
 * sitio, aqui se ve- y sirve para saber que fuentes aportan y cuales llevan
 * meses sin pasar el filtro.
 *
 * Esta lista sola da la impresion de un catalogo pequeño, y no lo es: la
 * mayoria de fuentes activas simplemente no han tenido todavia una noticia
 * que pase el filtro de puntuacion. $radar (publicar_radar_total() en
 * cron/publicar.php) trae la cifra real del catalogo completo para que esa
 * diferencia se vea, no se explique con una frase vaga.
 *
 * Recibe $temas, $medios, $radar y $base.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';

$enlace_activo = 'medios';
$alta_abierta  = $alta_abierta ?? false;
$temas         = $temas ?? [];
$medios        = $medios ?? [];
$radar         = $radar ?? ['total' => 0, 'por_region' => []];

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
<meta property="og:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
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

  <?php
    $pendientes = $radar['total'] - count($medios);
    $etiquetas  = ['es' => 'de España', 'eu' => 'de Europa', 'global' => 'de alcance global'];
    $partes     = [];
    foreach ($etiquetas as $clave => $etiqueta) {
        $n = (int) ($radar['por_region'][$clave] ?? 0);
        if ($n > 0) {
            $partes[] = $n . ' ' . $etiqueta;
        }
    }
    $desglose = count($partes) > 1
        ? implode(', ', array_slice($partes, 0, -1)) . ' y ' . end($partes)
        : implode('', $partes);
  ?>
  <?php if ($pendientes > 0): ?>
    <p class="letra-pequena explorar-pie">
      El radar vigila <?= (int) $radar['total'] ?> fuentes activas<?php if ($desglose !== ''): ?> -<?= web_e($desglose) ?>-<?php endif; ?>.
      Aquí solo aparecen las <?= count($medios) ?> que ya han contado algo que
      pasó el filtro; las otras <?= $pendientes ?> siguen vigiladas y
      aparecerán en cuanto publiquen algo que lo pase.
    </p>
  <?php endif; ?>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
