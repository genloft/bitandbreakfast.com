<?php
/**
 * El archivo: un dia por linea, agrupados por mes. Recibe $dias y $base.
 *
 * Antes eran ediciones agrupadas por año, porque un boletin semanal hace
 * cincuenta entradas al año. Con un dia por dia son trescientos sesenta y
 * cinco, asi que el grupo pasa a ser el mes: una lista de doce bloques se
 * recorre con la vista, una de trescientas no.
 *
 * Y se recorre buscando "el dia del incidente de Oracle", que es la razon de
 * que cada linea lleve su cuenta y sus temas en iconos: sin eso, esto es una
 * lista de fechas, que no dice absolutamente nada.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';
$dias       = $dias ?? [];

$por_mes = [];
$total   = 0;

foreach ($dias as $dia) {
    $fecha = substr((string) $dia['dia'], 0, 10);
    $por_mes[substr($fecha, 0, 7)][] = $dia;
    $total += (int) $dia['bits'];
}

$enlace_activo = 'archivo';
$resumen       = $resumen ?? [];
$categorias    = bits_categorias();

require_once __DIR__ . '/iconos.php';
$alta_abierta  = $alta_abierta ?? false;

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Archivo · Bit &amp; Breakfast</title>
<meta name="description" content="Todo lo publicado en Bit &amp; Breakfast, día a día: el agregador de tecnología hotelera en español.">
<link rel="canonical" href="<?= web_e($base) ?>/archivo.html">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="icon" href="<?= web_e($base) ?>/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version) ?>">
<meta name="theme-color" content="#f4f2ee">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="Archivo de Bit &amp; Breakfast">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= web_e($base) ?>/archivo.html">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido">
  <header class="edicion-cabecera">
    <p class="sello">Archivo</p>
    <h1>Día a día</h1>
    <p class="datos">
      <?= count($dias) ?> día<?= count($dias) === 1 ? '' : 's' ?>
      <span class="punto">·</span>
      <?= $total ?> noticia<?= $total === 1 ? '' : 's' ?>
    </p>
  </header>

  <?php if (!$dias): ?>
    <p class="vacio">Todavía no hay nada publicado. El radar está leyendo.</p>
  <?php endif; ?>

  <?php foreach ($por_mes as $mes => $del_mes): ?>
    <section class="ano">
      <h2><?= web_e(web_mes_largo($mes . '-01')) ?></h2>

      <ul class="archivo">
      <?php foreach ($del_mes as $dia): ?>
        <?php
          $fecha = substr((string) $dia['dia'], 0, 10);
          $suyo  = $resumen[$fecha] ?? ['bits' => (int) $dia['bits'], 'temas' => []];
        ?>
        <li>
          <a class="archivo-titulo" href="<?= web_e(web_url_dia($base, $fecha)) ?>">
            <?= web_e(web_dia_titulo($fecha)) ?>
          </a>
          <p class="datos">
            <time datetime="<?= web_e($fecha) ?>"><?= web_e($fecha) ?></time>
            <span class="punto">·</span>
            <?= (int) $dia['bits'] ?> noticia<?= (int) $dia['bits'] === 1 ? '' : 's' ?>
          </p>

          <?php if ($suyo['temas']): ?>
            <?php // De que fue aquel dia, en iconos. Una lista de fechas no se
                  // puede ojear; esto si. ?>
            <p class="archivo-temas">
            <?php foreach ($suyo['temas'] as $tema): ?>
              <span class="archivo-tema" data-tema="<?= web_e($tema) ?>"
                    title="<?= web_e($categorias[$tema] ?? $tema) ?>">
                <?= web_icono($tema, 'icono icono-mini') ?>
              </span>
            <?php endforeach; ?>
            </p>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
      </ul>
    </section>
  <?php endforeach; ?>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
