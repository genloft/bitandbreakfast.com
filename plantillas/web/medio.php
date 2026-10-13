<?php
/**
 * Ficha de un medio: todo lo que ha salido de el.
 *
 * Sirve para dos cosas distintas y las dos importan. Al lector le dice a quien
 * esta leyendo de verdad -si media edicion sale de un mismo sitio, se ve-. Y a
 * quien monta el radar le dice que fuentes estan aportando y cuales llevan
 * meses sin pasar el filtro.
 *
 * Recibe $medio -slug, nombre, bits, url-, $bits y $base.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';

$enlace_activo = 'medios';
$alta_abierta  = $alta_abierta ?? false;
$categorias    = bits_categorias();
$otros         = $otros ?? [];   // otros medios

require_once __DIR__ . '/iconos.php';

$url = web_url_medio($base, (string) $medio['slug']);

$seo = [
    'titulo' => $medio['nombre'] . ': noticias que recoge Bit & Breakfast',
    'titulo_social' => $medio['nombre'] . ' en Bit & Breakfast',
    'descripcion' => sprintf(
        '%d noticia%s de tecnología hotelera de %s recogida%s en Bit & Breakfast, con enlace al original.',
        (int) $medio['bits'],
        (int) $medio['bits'] === 1 ? '' : 's',
        $medio['nombre'],
        (int) $medio['bits'] === 1 ? '' : 's'
    ),
    'url' => $url,
    'tipo_pagina' => 'CollectionPage',
    'modificado' => (string) ($medio['ultimo'] ?? ''),
    'grafo' => [
        seo_lista(
            array_map(static fn (array $b): array => [
                'nombre' => (string) $b['titular'],
                'url'    => web_url_dia($base, (string) $b['dia']) . '#bit-' . (int) $b['id'],
            ], array_slice($bits, 0, 30)),
            'Noticias de ' . $medio['nombre']
        ),
    ],
    'pagina_extra' => [
        'about' => array_filter([
            '@type' => 'Organization',
            'name'  => (string) $medio['nombre'],
            'url'   => (string) ($medio['url'] ?? ''),
        ]),
    ],
    'theme' => '#060a18',
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
      ['nombre' => 'Medios', 'url' => $base . '/medios.html'],
      ['nombre' => (string) $medio['nombre'], 'url' => $url],
  ];
  require __DIR__ . '/migas.php';
?>

<main id="contenido">
  <header class="edicion-cabecera ficha-cabecera">
    <p class="sello">Medio</p>

    <h1 class="ficha-titulo">
      <span class="ficha-icono"><?= web_icono_ui('medio') ?></span>
      <?= web_e($medio['nombre']) ?>
    </h1>

    <p class="datos">
      <?= (int) $medio['bits'] ?> bit<?= (int) $medio['bits'] === 1 ? '' : 's' ?> publicado<?= (int) $medio['bits'] === 1 ? '' : 's' ?>
      <?php if (!empty($medio['alta'])): ?>
        <span class="punto">·</span>
        en el radar desde el <time datetime="<?= web_e((string) $medio['alta']) ?>"><?= web_e(web_fecha_larga((string) $medio['alta'])) ?></time>
      <?php endif; ?>
      <?php if (!empty($medio['url'])): ?>
        <span class="punto">·</span>
        <a href="<?= web_e($medio['url']) ?>" rel="nofollow noopener">ir al medio</a>
      <?php endif; ?>
    </p>
  </header>

  <?php if (!$bits): ?>
    <p class="vacio">Todavía no ha salido nada de este medio.</p>
  <?php endif; ?>

  <ul class="lista-fichas">
  <?php foreach ($bits as $bit): ?>
    <li>
      <h2>
        <a href="<?= web_e(web_url_dia($base, (string) $bit['dia'])) ?>#bit-<?= (int) $bit['id'] ?>">
          <?= web_e($bit['titular']) ?>
        </a>
      </h2>
      <?php $suyo = bits_categoria_canonica((string) $bit['categoria']) ?: 'tecnologia-general'; ?>
      <p class="datos" data-tema="<?= web_e($suyo) ?>">
        <time datetime="<?= web_e(substr((string) $bit['dia'], 0, 10)) ?>"><?= web_e(web_fecha_larga(substr((string) $bit['dia'], 0, 10))) ?></time>
        <span class="punto">·</span>
        <a href="<?= web_e(web_url_tema($base, $suyo)) ?>"><?= web_e($categorias[$suyo] ?? $suyo) ?></a>
      </p>
      <?php if (trim((string) ($bit['por_que'] ?? '')) !== ''): ?>
        <p class="resumen"><?= web_e($bit['por_que']) ?></p>
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
  </ul>

  <?php if ($otros): ?>
    <section class="explorar">
      <h3 class="explorar-grupo">Otros medios</h3>
      <ul class="nube nube-medios">
      <?php foreach ($otros as $otro): ?>
        <li>
          <a href="<?= web_e(web_url_medio($base, (string) $otro['slug'])) ?>">
            <span class="nube-nombre"><?= web_e($otro['nombre']) ?></span>
            <span class="nube-cuenta"><?= (int) $otro['bits'] ?></span>
          </a>
        </li>
      <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
