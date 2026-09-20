<?php
/**
 * Cumplimiento: el radar normativo, con fechas.
 *
 * Como /estadisticas.html, la otra excepción a que todo salga de la base
 * propia: aquí no hay juicio que emitir -"el modo automático no inventa"
 * también vale para una persona escribiendo esta tabla a mano-, son fechas
 * y boletines oficiales con enlace. Y como Cifras, la revisa una persona y
 * lleva su propia fecha de revisión, con una diferencia: allí la caducidad
 * es un plazo fijo porque no hay ninguna fecha propia de la que colgarse;
 * aquí la caducidad es la fecha pendiente más próxima de la propia tabla,
 * calculada en lib/cumplimiento.php.
 *
 * Los datos -cada norma, su estado y su fuente- viven en
 * lib/cumplimiento.php y no aquí: cron/mantenimiento.php necesita las
 * mismas fechas para calcular cuándo caduca la revisión, así que hace
 * falta poder leerlas sin ejecutar esta plantilla.
 *
 * Recibe $base y $alta_abierta.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';

$enlace_activo = 'cumplimiento';
$alta_abierta  = $alta_abierta ?? false;

$hoy      = gmdate('Y-m-d');
$revisado = cumplimiento_revisado();
$normas   = cumplimiento_normas();

$limite_revision = cumplimiento_limite_revision(cumplimiento_fechas(), $hoy, $revisado);

$descripcion = 'El calendario normativo de la tecnología hotelera en España: Verifactu, SES.Hospedajes, accesibilidad digital, NIS2, el AI Act y el alquiler de corta duración, con fecha y fuente en cada norma.';

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cumplimiento · Bit &amp; Breakfast</title>
<meta name="description" content="<?= web_e($descripcion) ?>">
<link rel="canonical" href="<?= web_e($base) ?>/cumplimiento.html">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="icon" href="<?= web_e($base) ?>/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version) ?>">
<meta name="theme-color" content="#12100d">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="Cumplimiento: el calendario normativo del sector hotelero">
<meta property="og:description" content="<?= web_e($descripcion) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= web_e($base) ?>/cumplimiento.html">
<meta property="og:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido">
  <header class="edicion-cabecera">
    <p class="sello">Cumplimiento</p>
    <h1>El calendario normativo</h1>
    <p class="datos">Verifactu, SES.Hospedajes, accesibilidad, NIS2, el AI Act y el alquiler de corta duración, con fecha y fuente en cada norma</p>
  </header>

  <p class="intro">Aquí no hay ningún juicio que hacer, solo fechas y boletines oficiales: lo mismo que dice el resto del sitio de sí mismo -si no se puede comprobar, no se publica- vale también para una tabla escrita a mano. Cada norma lleva su fuente y su fecha, con enlace para comprobarla. Lo que cambia respecto a <a href="<?= web_e($base) ?>/estadisticas.html">Cifras</a> es que aquí la revisión no caduca a plazo fijo: caduca en cuanto llega la fecha pendiente más próxima de esta misma tabla.</p>

  <ol class="cuadro cumplimiento-lista" aria-label="Calendario normativo">
    <?php foreach ($normas as $norma): ?>
      <li id="<?= web_e(web_slug_seguro($norma['norma'])) ?>" class="cuadro-tarjeta cumplimiento-norma cumplimiento-<?= web_e($norma['estado']) ?>">
        <h2 class="cuadro-tema"><?= web_e($norma['norma']) ?></h2>
        <p class="cuadro-nota"><?= web_e($norma['ambito']) ?></p>

        <?php if ($norma['estado'] === 'plazo' && !empty($norma['fecha_cuenta_atras'])): ?>
          <?php $dias = cumplimiento_dias_hasta($norma['fecha_cuenta_atras'], $hoy); ?>
          <p class="cuadro-ambito">Cuenta atrás</p>
          <p class="cuadro-valor">
            <?php if ($dias !== null && $dias > 0): ?>
              <?= (int) $dias ?> día<?= $dias === 1 ? '' : 's' ?>
            <?php else: ?>
              Ya vencido
            <?php endif; ?>
          </p>
        <?php elseif ($norma['estado'] === 'vigente'): ?>
          <p class="cuadro-ambito">Ya vigente</p>
        <?php elseif ($norma['estado'] === 'contexto'): ?>
          <p class="cuadro-ambito">No aplica a hoteles</p>
        <?php else: ?>
          <p class="cuadro-ambito">En trámite</p>
        <?php endif; ?>

        <p class="cuadro-detalle"><?= web_e($norma['texto']) ?></p>
        <p class="cuadro-detalle"><strong>A quién aplica.</strong> <?= web_e($norma['aplica']) ?></p>
        <p class="cuadro-detalle"><?= web_e($norma['detalle']) ?></p>

        <p class="cuadro-fuente">
          <span class="cuadro-fuente-etiqueta">Fuente</span>
          <a href="<?= web_e($norma['url']) ?>" rel="nofollow noopener"><?= web_e($norma['fuente']) ?></a>
        </p>
      </li>
    <?php endforeach; ?>
  </ol>

  <p class="letra-pequena cuadro-revision">Datos revisados el <?= web_e(web_fecha_larga($revisado)) ?>, con revisión antes del <?= web_e(web_fecha_larga($limite_revision)) ?> como muy tarde -la fecha pendiente más próxima de esta misma tabla-. Si una fecha de aquí ha cambiado, avísanos.</p>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
