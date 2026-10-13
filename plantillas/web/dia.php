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

$destacadas = array_slice(array_map(static fn (array $b): string => (string) $b['titular'], $bits), 0, 2);

$descripcion = sprintf(
    '%d noticias de tecnología hotelera del %s%s',
    count($bits),
    $titulo,
    $destacadas ? '. Destacan: ' . implode('; ', $destacadas) . '.' : '.'
);

$enlace_activo = 'archivo';
$imagen_og     = web_url_dia($base, $fecha) . 'og.png';

require_once __DIR__ . '/iconos.php';

$seo = [
    'titulo' => 'Tecnología hotelera, ' . $titulo . ' · Bit & Breakfast',
    'titulo_social' => 'Tecnología hotelera: lo que apareció el ' . $titulo,
    'descripcion' => texto_recortar($descripcion, 160),
    'url' => $url,
    'tipo' => 'article',
    'tipo_pagina' => 'CollectionPage',
    'theme' => '#f4f2ee',
    'imagen' => $imagen_og,
    'imagen_alt' => $bits ? (string) $bits[0]['titular'] : 'Bit & Breakfast, ' . $titulo,
    'publicado' => $fecha,
    'modificado' => $fecha,
    'grafo' => [
        seo_lista(
            array_map(static fn (array $b): array => [
                'nombre' => (string) $b['titular'],
                'url'    => $url . '#bit-' . (int) $b['id'],
            ], $bits),
            'Noticias del ' . $titulo
        ),
    ],
];

?><!doctype html>
<html lang="es">
<head>
<?php require __DIR__ . '/seo.php'; ?>
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
