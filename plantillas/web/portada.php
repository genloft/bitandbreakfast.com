<?php
/**
 * La portada: el rio de lo ultimo, partido por dias.
 *
 * Esto sustituye a "la ultima edicion hace de portada", y el cambio es el que
 * mas se nota de todos. Una edicion solo existia cuando se cerraba, asi que
 * todo lo que el radar encontraba hoy estaba invisible hasta mañana, y la
 * portada llevaba siempre un dia de retraso sobre lo que el sistema sabia.
 * Aqui una noticia esta publicada en cuanto esta escrita.
 *
 * Por eso manda el dia de descubrimiento y no el de publicacion del medio: lo
 * segundo lo sabe cualquiera, lo primero es lo unico que este sitio puede
 * contar de verdad. "Esto es lo que ha aparecido hoy en el radar" es una
 * promesa que se puede cumplir; "esto es todo lo que se ha publicado hoy en el
 * mundo" no.
 *
 * Recibe $rio -tramos con 'dia' y 'bits'-, $dias, $fuentes y $base.
 *
 * HTML estatico: ni script, ni estilo en linea, ni una peticion a terceros.
 * La politica de seguridad del sitio es 'self' y esta pagina es la razon de
 * que pueda serlo.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';
$rio        = $rio ?? [];
$dias       = $dias ?? [];
$fuentes    = $fuentes ?? [];
$temas      = $temas ?? [];
$medios     = $medios ?? [];
$secreto    = $secreto ?? '';
$alta_abierta = $alta_abierta ?? false;

$categorias = bits_categorias();
$ambitos    = web_ambitos();
$idiomas    = web_idiomas();

$total = 0;
foreach ($rio as $tramo) {
    $total += count($tramo['bits']);
}

$hoy_bits = isset($rio[0]) ? count($rio[0]['bits']) : 0;

$descripcion = sprintf(
    'Lo último en tecnología hotelera: %d noticias de %d días, con enlace a la fuente.',
    $total,
    count($rio)
);

$enlace_activo = 'portada';

require_once __DIR__ . '/iconos.php';

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bit &amp; Breakfast · Tecnología hotelera, en español</title>
<meta name="description" content="<?= web_e($descripcion) ?>">
<link rel="canonical" href="<?= web_e($base) ?>/">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version) ?>">
<meta name="theme-color" content="#f4f2ee">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="Bit &amp; Breakfast">
<meta property="og:description" content="<?= web_e($descripcion) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= web_e($base) ?>/">
<meta property="og:locale" content="es_ES">
<meta name="twitter:card" content="summary">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido">
  <article class="edicion">

    <?php foreach ($rio as $tramo_i => $tramo): ?>
      <?php $bits = $tramo['bits']; ?>

      <header class="dia-cabecera<?= $tramo_i === 0 ? ' dia-cabecera-primera' : '' ?>">
        <h2 class="dia-titulo">
          <a href="<?= web_e(web_url_dia($base, (string) $tramo['dia'])) ?>">
            <time datetime="<?= web_e((string) $tramo['dia']) ?>"><?= web_e(web_dia_titulo((string) $tramo['dia'])) ?></time>
          </a>
        </h2>
        <p class="datos"><?= count($bits) ?> noticia<?= count($bits) === 1 ? '' : 's' ?></p>
      </header>

      <div class="bits">
        <?php foreach ($bits as $indice => $bit): ?>
          <?php
            // El destacado solo en el primer tramo. Mas abajo, una pieza a
            // doble columna en medio de la pagina no dice "esto importa mas",
            // dice "aqui se ha roto algo".
            $indice = $tramo_i === 0 ? $indice : $indice + 1;
            require __DIR__ . '/bit.php';
          ?>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>

    <?php if (!$rio): ?>
      <p class="vacio">Todavía no hay nada publicado. El radar está leyendo.</p>
    <?php endif; ?>

    <?php if (count($dias) > count($rio)): ?>
      <p class="mas-dias">
        <a href="<?= web_e($base) ?>/archivo.html">Los <?= count($dias) ?> días anteriores &rarr;</a>
      </p>
    <?php endif; ?>

  </article>

  <?php require __DIR__ . '/explorar.php'; ?>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
