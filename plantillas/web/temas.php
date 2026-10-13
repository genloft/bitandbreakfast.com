<?php
/**
 * Indice de temas.
 *
 * Once tematicas y lo que ha entrado en cada una. Es el mapa del radar: dice
 * de que se esta hablando en tecnologia hotelera y de que no.
 *
 * Recibe $temas, $medios y $base.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';

$enlace_activo = 'temas';
$alta_abierta  = $alta_abierta ?? false;
$temas         = $temas ?? [];
$medios        = $medios ?? [];

require_once __DIR__ . '/iconos.php';

$seo = [
    'titulo' => 'Temas de tecnología hotelera · Bit & Breakfast',
    'titulo_social' => 'Temas en Bit & Breakfast',
    'descripcion' => 'Los temas de tecnología hotelera que cubre Bit & Breakfast, con todo lo publicado en cada uno.',
    'url' => $base . '/temas.html',
    'tipo_pagina' => 'CollectionPage',
    'grafo' => [
        seo_lista(
            array_map(static fn (array $t): array => [
                'nombre' => (string) $t['nombre'],
                'url'    => web_url_tema($base, (string) $t['slug']),
            ], $temas),
            'Temas de tecnología hotelera'
        ),
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

<main id="contenido">
  <header class="edicion-cabecera">
    <p class="sello">Temas</p>
    <h1>De qué va esto</h1>
    <p class="datos"><?= count($temas) ?> temas con algo publicado</p>
  </header>

  <?php if (!$temas): ?>
    <p class="vacio">Todavía no hay nada publicado.</p>
  <?php endif; ?>

  <ul class="rejilla-fichas">
  <?php foreach ($temas as $tema): ?>
    <li data-tema="<?= web_e($tema['slug']) ?>">
      <a href="<?= web_e(web_url_tema($base, (string) $tema['slug'])) ?>">
        <span class="ficha-icono"><?= web_icono((string) $tema['slug']) ?></span>
        <span class="rejilla-nombre"><?= web_e($tema['nombre']) ?></span>
        <span class="rejilla-cuenta"><?= (int) $tema['bits'] ?> bit<?= (int) $tema['bits'] === 1 ? '' : 's' ?></span>
      </a>
    </li>
  <?php endforeach; ?>
  </ul>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
