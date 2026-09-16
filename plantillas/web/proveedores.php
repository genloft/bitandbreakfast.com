<?php
/**
 * Indice de proveedores con bits publicados.
 *
 * Recibe $proveedores, $base y $alta_abierta. Cada fila trae 'nombre',
 * 'slug', 'categoria' y 'bits'.
 *
 * Solo salen los que tienen algo publicado: un catalogo de cuarenta y siete
 * fichas vacias no ayuda a nadie y ademas es mala senal para un buscador.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';

$enlace_activo = 'proveedores';
$alta_abierta  = $alta_abierta ?? false;

$por_categoria = [];

foreach ($proveedores as $proveedor) {
    $por_categoria[(string) $proveedor['categoria']][] = $proveedor;
}

ksort($por_categoria);

$etiquetas = [
    'pms'                  => 'PMS y gestión',
    'channel-manager'      => 'Channel managers',
    'motor-reserva'        => 'Motores de reserva',
    'distribucion'         => 'Distribución',
    'rms'                  => 'Revenue management',
    'inteligencia-mercado' => 'Inteligencia de mercado',
    'crm-marketing'        => 'CRM y marketing',
    'mensajeria'           => 'Mensajería',
    'operaciones'          => 'Operaciones',
    'accesos'              => 'Accesos',
    'pagos'                => 'Pagos',
];

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Proveedores · Bit &amp; Breakfast</title>
<meta name="description" content="Los proveedores de tecnología hotelera de los que ha hablado Bit &amp; Breakfast, con todo lo publicado sobre cada uno.">
<link rel="canonical" href="<?= web_e($base) ?>/proveedores.html">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version) ?>">
<meta name="theme-color" content="#12100d">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="Proveedores en Bit &amp; Breakfast">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= web_e($base) ?>/proveedores.html">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido">
  <header class="edicion-cabecera">
    <p class="sello">Proveedores</p>
    <h1>De quién hemos hablado</h1>
    <p class="datos"><?= count($proveedores) ?> proveedor<?= count($proveedores) === 1 ? '' : 'es' ?> con algo publicado</p>
  </header>

  <?php if (!$proveedores): ?>
    <p class="vacio">Todavía no hay ninguna ficha. Aparecen solas en cuanto se
    publica el primer bit que menciona a un proveedor del catálogo.</p>
  <?php endif; ?>

  <?php foreach ($por_categoria as $categoria => $del_grupo): ?>
    <section class="ano">
      <h2><?= web_e($etiquetas[$categoria] ?? $categoria) ?></h2>

      <ul class="proveedores">
      <?php foreach ($del_grupo as $proveedor): ?>
        <li>
          <a href="<?= web_e(web_url_proveedor($base, (string) $proveedor['slug'])) ?>"><?= web_e($proveedor['nombre']) ?></a>
          <span class="cuenta"><?= (int) $proveedor['bits'] ?></span>
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
