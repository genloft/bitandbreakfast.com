<?php
/**
 * Un dia entero: todo lo que el radar descubrio esa fecha.
 *
 * Es la pagina que sustituye a la de una edicion. La diferencia no es de
 * nombre: una edicion era una decision -cuando cerrarla, que meter dentro- y
 * un dia es un hecho. Nadie tiene que decidir nada para que el 17 de
 * septiembre exista, y su direccion se adivina sin consultar el archivo.
 *
 * A diferencia de la portada, aqui cabe todo el dia, sin tope: quien entra en
 * la pagina de una fecha viene precisamente a por lo que no cupo en la
 * portada.
 *
 * Recibe $dia -con 'dia' y 'bits'-, $bits, $fuentes y $base.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';
$bits       = $bits ?? [];
$fuentes    = $fuentes ?? [];
$temas      = $temas ?? [];
$medios     = $medios ?? [];
$secreto    = $secreto ?? '';
$alta_abierta = $alta_abierta ?? false;

$fecha  = substr((string) ($dia['dia'] ?? ''), 0, 10);
$titulo = web_fecha_larga($fecha);
$url    = web_url_dia($base, $fecha);

$categorias = bits_categorias();
$ambitos    = web_ambitos();
$idiomas    = web_idiomas();

$palabras = 0;
foreach ($bits as $bit) {
    $palabras += texto_contar_palabras((string) $bit['cuerpo']);
}

$descripcion = sprintf(
    '%d noticias de tecnología hotelera descubiertas el %s.',
    count($bits),
    $titulo
);

$enlace_activo = 'archivo';

require_once __DIR__ . '/iconos.php';

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= web_e($titulo) ?> · Bit &amp; Breakfast</title>
<meta name="description" content="<?= web_e(texto_recortar($descripcion, 160)) ?>">
<link rel="canonical" href="<?= web_e($url) ?>">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version) ?>">
<meta name="theme-color" content="#f4f2ee">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="<?= web_e($titulo) ?>">
<meta property="og:description" content="<?= web_e(texto_recortar($descripcion, 160)) ?>">
<meta property="og:type" content="article">
<meta property="og:url" content="<?= web_e($url) ?>">
<meta property="og:locale" content="es_ES">
<meta name="twitter:card" content="summary">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<?php
  $migas = [
      ['nombre' => 'Portada', 'url' => $base . '/'],
      ['nombre' => 'Archivo', 'url' => $base . '/archivo.html'],
      ['nombre' => $titulo, 'url' => $url],
  ];
  require __DIR__ . '/migas.php';
?>

<main id="contenido">
  <article class="edicion">

    <header class="edicion-cabecera">
      <p class="sello">Un día</p>
      <h1>
        <time datetime="<?= web_e($fecha) ?>"><?= web_e($titulo) ?></time>
      </h1>
      <p class="datos">
        <?= count($bits) ?> noticia<?= count($bits) === 1 ? '' : 's' ?>
        <span class="punto">·</span>
        <?= web_minutos($palabras) ?> min de lectura
      </p>
    </header>

    <p class="rotulo">Lo que apareció en el radar</p>

    <div class="bits">
      <?php foreach ($bits as $indice => $bit): ?>
        <?php require __DIR__ . '/bit.php'; ?>
      <?php endforeach; ?>
    </div>

    <?php if (!$bits): ?>
      <p class="vacio">Ese día no pasó nada el filtro.</p>
    <?php endif; ?>

    <p class="mas-dias">
      <a href="<?= web_e($base) ?>/archivo.html">Todos los días &rarr;</a>
    </p>

  </article>

  <?php require __DIR__ . '/explorar.php'; ?>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
