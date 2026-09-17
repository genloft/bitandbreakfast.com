<?php
/**
 * Indice de ediciones, agrupadas por año. Recibe $ediciones y $base.
 *
 * Se agrupa por año porque un boletin semanal acumula cincuenta entradas al
 * año y una lista plana deja de ser navegable en el segundo.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';

$por_ano = [];

foreach ($ediciones as $edicion) {
    $ano = substr((string) $edicion['fecha_prevista'], 0, 4);
    $por_ano[$ano][] = $edicion;
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
<meta name="description" content="Todas las ediciones publicadas de Bit &amp; Breakfast, el radar de tecnología hotelera.">
<link rel="canonical" href="<?= web_e($base) ?>/archivo.html">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version) ?>">
<meta name="theme-color" content="#12100d">
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
    <h1>Archivo</h1>
    <p class="datos">
      <?php // "edicion" pierde la tilde en plural: edicion / ediciones. ?>
      <?= count($ediciones) ?> <?= count($ediciones) === 1 ? 'edición publicada' : 'ediciones publicadas' ?>
    </p>
  </header>

  <?php if (!$ediciones): ?>
    <p class="vacio">Todavía no hay ninguna edición publicada. La primera sale
    en cuanto se cierre en el panel.</p>
  <?php endif; ?>

  <?php foreach ($por_ano as $ano => $del_ano): ?>
    <section class="ano">
      <h2><?= web_e((string) $ano) ?></h2>

      <ul class="archivo">
      <?php foreach ($del_ano as $edicion): ?>
        <?php $suyo = $resumen[(int) $edicion['id']] ?? ['bits' => 0, 'temas' => []]; ?>
        <li>
          <?php // La fecha manda tambien aqui: el archivo se recorre buscando
                // "la semana del incidente de Oracle", no la edicion numero 7. ?>
          <a class="archivo-titulo" href="<?= web_e(web_url_edicion($base, (string) $edicion['slug'])) ?>">
            <?= web_e(trim((string) $edicion['titulo']) !== ''
                ? (string) $edicion['titulo']
                : web_fecha_larga((string) $edicion['fecha_prevista'])) ?>
          </a>
          <p class="datos">
            <time datetime="<?= web_e((string) $edicion['fecha_prevista']) ?>">nº <?= (int) $edicion['numero'] ?></time>
            <?php if ($suyo['bits'] > 0): ?>
              <span class="punto">·</span>
              <?= (int) $suyo['bits'] ?> bit<?= (int) $suyo['bits'] === 1 ? '' : 's' ?>
            <?php endif; ?>
          </p>

          <?php if ($suyo['temas']): ?>
            <?php // De que fue aquella semana, en iconos. Una lista de titulos
                  // con fechas no se puede ojear; esto si. ?>
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
