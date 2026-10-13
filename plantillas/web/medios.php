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

$seo = [
    'titulo' => 'Medios que recoge el radar de tecnología hotelera · Bit & Breakfast',
    'titulo_social' => 'Medios en Bit & Breakfast',
    'descripcion' => 'Los medios de los que Bit & Breakfast ha publicado algo, con su recuento.',
    'url' => $base . '/medios.html',
    'tipo_pagina' => 'CollectionPage',
    'grafo' => [
        seo_lista(
            array_map(static fn (array $m): array => [
                'nombre' => (string) $m['nombre'],
                'url'    => web_url_medio($base, (string) $m['slug']),
            ], $medios),
            'Medios del radar'
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
        <?php if (!empty($medio['alta'])): ?>
          <span class="rejilla-alta">en el radar desde <?= web_e(web_mes_largo((string) $medio['alta'])) ?></span>
        <?php endif; ?>
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
