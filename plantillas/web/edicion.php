<?php
/**
 * Una edicion publicada. Es tambien la portada cuando es la mas reciente.
 *
 * Recibe $edicion, $bits y $base. Lo que sale de aqui es HTML estatico: ni
 * script, ni estilo en linea, ni una sola peticion a terceros. La politica de
 * seguridad del sitio es 'self' y esta pagina es la razon de que pueda serlo.
 */

declare(strict_types=1);

$titulo = trim((string) $edicion['titulo']) !== ''
    ? (string) $edicion['titulo']
    : 'Edición ' . (int) $edicion['numero'];

$palabras = 0;
foreach ($bits as $bit) {
    $palabras += texto_contar_palabras((string) $bit['cuerpo']);
}

$url = web_url_edicion($base, (string) $edicion['slug']);

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= web_e($titulo) ?> · Bit &amp; Breakfast</title>
<meta name="description" content="<?= web_e(texto_recortar(trim((string) $edicion['intro']) !== '' ? (string) $edicion['intro'] : 'Radar de tecnología hotelera: ' . count($bits) . ' bits de la semana.', 160)) ?>">
<link rel="canonical" href="<?= web_e($url) ?>">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css">
<meta property="og:title" content="<?= web_e($titulo) ?>">
<meta property="og:type" content="article">
<meta property="og:url" content="<?= web_e($url) ?>">
</head>
<body>

<header class="cabecera">
  <p class="marca"><a href="<?= web_e($base) ?>/">Bit &amp; Breakfast</a></p>
  <p class="promesa">Cinco minutos de tecnología hotelera a la semana.</p>
</header>

<main>
  <article class="edicion">
    <h1><?= web_e($titulo) ?></h1>

    <p class="datos">
      Edición <?= (int) $edicion['numero'] ?> ·
      <time datetime="<?= web_e((string) $edicion['fecha_prevista']) ?>"><?= web_e(web_fecha_larga((string) $edicion['fecha_prevista'])) ?></time> ·
      <?= count($bits) ?> bits ·
      <?= web_minutos($palabras) ?> min
    </p>

    <?php if (trim((string) $edicion['intro']) !== ''): ?>
      <div class="intro"><?= web_parrafos((string) $edicion['intro']) ?></div>
    <?php endif; ?>

    <?php foreach ($bits as $bit): ?>
      <section class="bit" id="bit-<?= (int) $bit['id'] ?>">
        <h2><?= web_e($bit['titular']) ?></h2>

        <p class="etiquetas">
          <span class="etiqueta"><?= web_e($bit['categoria']) ?></span>
          <span class="etiqueta"><?= web_e($bit['tipo']) ?></span>
          <span class="etiqueta"><?= web_e($bit['madurez']) ?></span>
        </p>

        <?= web_parrafos((string) $bit['cuerpo']) ?>

        <?php if (trim((string) $bit['por_que']) !== ''): ?>
          <p class="por-que"><strong>Por qué importa.</strong> <?= web_e($bit['por_que']) ?></p>
        <?php endif; ?>

        <?php if (!empty($bit['url'])): ?>
          <p class="fuente">
            <a href="<?= web_e($bit['url']) ?>" rel="nofollow noopener">
              <?= web_e($bit['fuente'] ?? 'Leer la fuente') ?>
            </a>
          </p>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>

    <?php if (!$bits): ?>
      <p class="vacio">Esta edición se cerró sin bits publicados.</p>
    <?php endif; ?>
  </article>
</main>

<footer class="pie">
  <p><a href="<?= web_e($base) ?>/archivo.html">Ediciones anteriores</a> ·
     <a href="<?= web_e($base) ?>/feed.xml">RSS</a></p>
  <p class="letra-pequena">Bit &amp; Breakfast es un radar, no un agregador:
  filtra duro y enseña poco. Cada bit enlaza a su fuente original.</p>
</footer>

</body>
</html>
