<?php
/**
 * Una edicion publicada. Es tambien la portada cuando es la mas reciente.
 *
 * Recibe $edicion, $bits y $base.
 *
 * La pagina esta pensada para escanearse antes que para leerse: primero el
 * sumario, que dice en diez segundos si esta semana te interesa algo, y
 * despues los bits. Un radar que obliga a leerlo entero para saber si tenia
 * algo no es un radar.
 *
 * HTML estatico: ni script, ni estilo en linea, ni una peticion a terceros.
 * La politica de seguridad del sitio es 'self' y esta pagina es la razon de
 * que pueda serlo.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';

$titulo = trim((string) $edicion['titulo']) !== ''
    ? (string) $edicion['titulo']
    : 'Edición ' . (int) $edicion['numero'];

$palabras = 0;
foreach ($bits as $bit) {
    $palabras += texto_contar_palabras((string) $bit['cuerpo']);
}

$url        = web_url_edicion($base, (string) $edicion['slug']);
$categorias = bits_categorias();
$madureces  = bits_madureces();
$tipos      = bits_tipos();

$descripcion = trim((string) $edicion['intro']) !== ''
    ? (string) $edicion['intro']
    : sprintf('%d bits de tecnología hotelera, %d minutos de lectura.', count($bits), web_minutos($palabras));

$enlace_activo = 'portada';
$alta_abierta  = $alta_abierta ?? false;
$secreto       = $secreto ?? '';
$fuentes       = $fuentes ?? [];
$ambitos       = web_ambitos();
$idiomas       = web_idiomas();

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
<meta name="theme-color" content="#12100d">
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

<main id="contenido">
  <article class="edicion">

    <header class="edicion-cabecera">
      <p class="sello">Edición <?= (int) $edicion['numero'] ?></p>
      <h1><?= web_e($titulo) ?></h1>
      <p class="datos">
        <time datetime="<?= web_e((string) $edicion['fecha_prevista']) ?>"><?= web_e(web_fecha_larga((string) $edicion['fecha_prevista'])) ?></time>
        <span class="punto">·</span>
        <?= count($bits) ?> bit<?= count($bits) === 1 ? '' : 's' ?>
        <span class="punto">·</span>
        <?= web_minutos($palabras) ?> min de lectura
      </p>

      <?php if (trim((string) $edicion['intro']) !== ''): ?>
        <div class="intro"><?= web_parrafos((string) $edicion['intro']) ?></div>
      <?php endif; ?>
    </header>

    <?php if ($bits): ?>
      <nav class="sumario" aria-labelledby="sumario-titulo">
        <h2 id="sumario-titulo">En esta edición</h2>
        <ol>
        <?php foreach ($bits as $bit): ?>
          <li>
            <a href="#bit-<?= (int) $bit['id'] ?>"><?= web_e($bit['titular']) ?></a>
            <span class="sumario-etiqueta"><?= web_e($categorias[bits_categoria_canonica((string) $bit['categoria'])] ?? $bit['categoria']) ?></span>
          </li>
        <?php endforeach; ?>
        </ol>
      </nav>
    <?php endif; ?>

    <div class="bits">
    <?php foreach ($bits as $indice => $bit): ?>
      <section class="bit" id="bit-<?= (int) $bit['id'] ?>" aria-labelledby="titular-<?= (int) $bit['id'] ?>">
        <p class="numero" aria-hidden="true"><?= $indice + 1 ?></p>

        <div class="bit-cuerpo">
          <h2 id="titular-<?= (int) $bit['id'] ?>"><?= web_e($bit['titular']) ?></h2>

          <p class="etiquetas">
            <span class="etiqueta etiqueta-categoria"><?= web_e($categorias[bits_categoria_canonica((string) $bit['categoria'])] ?? $bit['categoria']) ?></span>
            <span class="etiqueta"><?= web_e($tipos[$bit['tipo']] ?? $bit['tipo']) ?></span>
            <span class="etiqueta"><?= web_e($madureces[$bit['madurez']] ?? $bit['madurez']) ?></span>
            <?php if (($bit['idioma'] ?? 'es') !== 'es'): ?>
              <?php // El titular es el que publico el medio. Decir en que idioma
                    // esta evita que parezca un descuido: es la noticia tal cual
                    // la conto su fuente, sin traducir, que es lo que promete
                    // este radar. ?>
              <span class="etiqueta etiqueta-idioma">Titular en <?= web_e(mb_strtolower((string) ($idiomas[$bit['idioma']] ?? $bit['idioma']), 'UTF-8')) ?></span>
            <?php endif; ?>
          </p>

          <div class="texto"><?= web_parrafos((string) $bit['cuerpo']) ?></div>

          <?php if (trim((string) $bit['por_que']) !== ''): ?>
            <p class="por-que"><strong>Por qué importa.</strong> <?= web_e($bit['por_que']) ?></p>
          <?php endif; ?>

          <?php $menciona = web_proveedores($bit['proveedores'] ?? null); ?>
          <?php if ($menciona): ?>
            <p class="menciona">Menciona:
              <?php foreach ($menciona as $indice_p => $proveedor): ?><?= $indice_p > 0 ? ', ' : '' ?><a href="<?= web_e(web_url_proveedor($base, $proveedor['slug'])) ?>"><?= web_e($proveedor['nombre']) ?></a><?php endforeach; ?>
            </p>
          <?php endif; ?>

          <p class="pie-bit">
            <?php if (!empty($bit['url'])): ?>
              <a class="fuente" href="<?= web_e(web_url_clic($base, (int) $bit['id'], $secreto, (string) $bit['url'])) ?>" rel="nofollow noopener">
                <?= web_e($bit['fuente'] ?? 'Leer la fuente') ?> →
              </a>
            <?php endif; ?>
            <a class="volver" href="#sumario-titulo">Sumario ↑</a>
          </p>

          <?php
            // Todas las fuentes que cuentan la noticia, no solo la mejor. Que
            // cuatro medios independientes la cuenten es la mitad de la
            // informacion, y esconderla detras de un solo enlace la tiraba.
            $suyas = $fuentes[(int) ($bit['racimo_id'] ?? 0)] ?? [];
          ?>
          <?php if (count($suyas) > 1): ?>
            <details class="fuentes-bit">
              <summary><?= count($suyas) ?> fuentes lo cuentan</summary>

              <ul>
              <?php foreach ($suyas as $fuente): ?>
                <li>
                  <a href="<?= web_e($fuente['url']) ?>" rel="nofollow noopener"><?= web_e($fuente['fuente']) ?></a>
                  <span class="datos">
                    <?= web_e($ambitos[$fuente['region']] ?? $fuente['region']) ?>
                    <span class="punto">·</span>
                    <?= web_e($idiomas[$fuente['idioma']] ?? $fuente['idioma']) ?>
                    <span class="punto">·</span>
                    <time datetime="<?= web_e(substr((string) $fuente['publicado'], 0, 10)) ?>"><?= web_e(web_fecha_larga(substr((string) $fuente['publicado'], 0, 10))) ?></time>
                  </span>
                  <span class="titular-fuente"><?= web_e($fuente['titulo']) ?></span>
                </li>
              <?php endforeach; ?>
              </ul>
            </details>
          <?php endif; ?>
        </div>
      </section>
    <?php endforeach; ?>
    </div>

    <?php if (!$bits): ?>
      <p class="vacio">Esta edición se cerró sin bits publicados.</p>
    <?php endif; ?>

  </article>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
