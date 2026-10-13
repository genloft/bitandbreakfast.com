<?php
/**
 * Calendario: las ferias, congresos y foros que un directivo del sector ya
 * tiene marcados en su propia agenda.
 *
 * Como /estadisticas.html y /cumplimiento.html, no sale de la base propia:
 * es una lista escrita a mano, con fecha y fuente en cada evento, revisada
 * dos veces al año. Lo que enseña no es el catálogo completo -eso ya lo
 * hace el propio organizador de cada feria-, es la selección que de verdad
 * importa para la tecnología hotelera en España.
 *
 * Solo enseña lo que queda por delante: calendario_proximos(), en
 * lib/calendario.php, descarta lo que ya ha pasado. Un evento que ya
 * ocurrió no es información para esta página, es ruido -y, si son todos,
 * es la señal de que toca revisarla-.
 *
 * Recibe $base y $alta_abierta.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';

$enlace_activo = 'calendario';
$alta_abierta  = $alta_abierta ?? false;

$hoy      = gmdate('Y-m-d');
$revisado = calendario_revisado();
$eventos  = calendario_proximos(calendario_eventos(), $hoy);

$limite_revision = calendario_limite_revision($revisado);

$descripcion = 'FITUR, HIP, TIS, el IHTF y el ITH Hotel Energy Meetings: las ferias y foros de tecnología hotelera que quedan por delante, con fecha, lugar y fuente en cada uno.';

$seo = [
    'titulo' => 'Calendario de ferias y foros de tecnología hotelera · Bit & Breakfast',
    'titulo_social' => 'Calendario: las ferias y foros del sector hotelero',
    'descripcion' => $descripcion,
    'url' => $base . '/calendario.html',
    'modificado' => calendario_revisado(),
    'theme' => '#12100d',
];

?><!doctype html>
<html lang="es">
<head>
<?php require __DIR__ . '/seo.php'; ?>
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido">
  <header class="edicion-cabecera">
    <p class="sello">Calendario</p>
    <h1>Lo que queda por delante</h1>
    <p class="datos">FITUR, HIP, TIS, el IHTF y el ITH Hotel Energy Meetings, con fecha, lugar y fuente</p>
  </header>

  <p class="intro">Ni un directorio de ferias ni una agenda completa del sector: la selección de citas de tecnología hotelera que de verdad importa para quien decide en un hotel español, revisada dos veces al año. Cada una lleva su fuente y su enlace, y en cuanto pasa deja de aparecer aquí -es la señal de que toca revisar la lista, no un fallo de la página-.</p>

  <?php if ($eventos): ?>
    <ol class="cuadro calendario-lista" aria-label="Próximos eventos del sector">
      <?php foreach ($eventos as $evento): ?>
        <li class="cuadro-tarjeta">
          <h2 class="cuadro-tema"><?= web_e($evento['nombre']) ?></h2>
          <p class="cuadro-nota"><?= web_e($evento['lugar']) ?><?php if (!empty($evento['edicion'])): ?><span class="punto">·</span><?= web_e($evento['edicion']) ?><?php endif; ?></p>
          <p class="cuadro-valor"><?= web_e($evento['fechas']) ?></p>
          <p class="cuadro-detalle"><?= web_e($evento['descripcion']) ?></p>
          <p class="cuadro-fuente">
            <span class="cuadro-fuente-etiqueta">Fuente</span>
            <a href="<?= web_e($evento['url']) ?>" rel="nofollow noopener"><?= web_e($evento['fuente']) ?></a>
          </p>
        </li>
      <?php endforeach; ?>
    </ol>
  <?php else: ?>
    <p class="cuadro-nota">No queda ningún evento por delante en esta lista: toca revisarla y añadir los siguientes.</p>
  <?php endif; ?>

  <p class="letra-pequena cuadro-revision">Lista revisada el <?= web_e(web_fecha_larga($revisado)) ?>, con revisión antes del <?= web_e(web_fecha_larga($limite_revision)) ?> como muy tarde. Si una fecha de aquí ha cambiado, avísanos.</p>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
