<?php
/**
 * Una edicion publicada. Es tambien la portada cuando es la mas reciente.
 *
 * Recibe $edicion, $bits y $base.
 *
 * La pagina esta pensada para ojearse antes que para leerse: la primera pieza
 * destacada y el resto en columnas, como una portada. Hubo un sumario lateral
 * y se quito: repetia los titulares que estaban dos dedos mas abajo y en una
 * columna estrecha se apelotonaba. Un indice sirve cuando hay mucho que
 * recorrer; esto se ve de una pasada.
 *
 * HTML estatico: ni script, ni estilo en linea, ni una peticion a terceros.
 * La politica de seguridad del sitio es 'self' y esta pagina es la razon de
 * que pueda serlo.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';

// La edicion se llama por su fecha, no por su numero. El numero sigue estando
// -en el sello, en el archivo y en la URL-, pero lo que le dice algo al lector
// es "17 de septiembre": el numero solo le dice cuantas van.
$fecha  = web_fecha_larga((string) $edicion['fecha_prevista']);
$titulo = trim((string) $edicion['titulo']) !== ''
    ? (string) $edicion['titulo']
    : $fecha;

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
$medios        = $medios ?? [];
$temas         = $temas ?? [];

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
      <h1>
        <time datetime="<?= web_e((string) $edicion['fecha_prevista']) ?>"><?= web_e($titulo) ?></time>
      </h1>
      <p class="datos">
        <?= count($bits) ?> bit<?= count($bits) === 1 ? '' : 's' ?>
        <span class="punto">·</span>
        <?= web_minutos($palabras) ?> min de lectura
      </p>

      <?php if (trim((string) $edicion['intro']) !== ''): ?>
        <div class="intro"><?= web_parrafos((string) $edicion['intro']) ?></div>
      <?php endif; ?>
    </header>

    <?php
      // Sin indice lateral ni sumario: repetia los titulares que estan dos
      // dedos mas abajo. La primera pieza va destacada y el resto en columnas,
      // que es como se ojea una portada.
    ?>
    <p class="rotulo">Las noticias de hoy</p>

    <div class="bits">
    <?php foreach ($bits as $indice => $bit): ?>
      <?php $tema = bits_categoria_canonica((string) $bit['categoria']) ?: 'tecnologia-general'; ?>
      <section class="bit<?= $indice === 0 ? ' bit-lead' : '' ?>" id="bit-<?= (int) $bit['id'] ?>"
               data-tema="<?= web_e($tema) ?>"
               aria-labelledby="titular-<?= (int) $bit['id'] ?>">
        <div class="bit-carril" aria-hidden="true">
          <p class="numero"><?= str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) ?></p>
          <span class="bit-icono"><?= web_icono($tema) ?></span>
        </div>

        <div class="bit-cuerpo">
          <h2 id="titular-<?= (int) $bit['id'] ?>"><?= web_e($bit['titular']) ?></h2>

          <p class="etiquetas">
            <a class="etiqueta etiqueta-categoria" href="<?= web_e(web_url_tema($base, $tema)) ?>"><?= web_e($categorias[$tema] ?? $bit['categoria']) ?></a>
            <?php // El tipo y la madurez se quedan fuera de la portada: en un
                  // titular no anaden nada y convierten la linea de arriba en
                  // una fila de tres cosas iguales. Siguen en el dato del bit
                  // para el explorador y el correo. ?>
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
            <?php // Los proveedores ya no tienen ficha propia -este radar habla de
                  // temas, no de marcas-, pero seguir nombrandolos si sirve: el
                  // enlace lleva al explorador buscando ese nombre. ?>
            <p class="menciona">Menciona:
              <?php foreach ($menciona as $indice_p => $proveedor): ?><?= $indice_p > 0 ? ', ' : '' ?><a href="<?= web_e($base) ?>/buscar.html?q=<?= web_e(rawurlencode((string) $proveedor['nombre'])) ?>"><?= web_e($proveedor['nombre']) ?></a><?php endforeach; ?>
            </p>
          <?php endif; ?>

          <?php
            // Todas las fuentes que cuentan la noticia, no solo la mejor. Que
            // cuatro medios independientes la cuenten es la mitad de la
            // informacion, y esconderla detras de un solo enlace la tiraba.
            $suyas = $fuentes[(int) ($bit['racimo_id'] ?? 0)] ?? [];
            $unica = count($suyas) === 1 ? $suyas[0] : null;
          ?>

          <p class="pie-bit">
            <?php if (!empty($bit['url'])): ?>
              <a class="fuente" href="<?= web_e(web_url_clic($base, (int) $bit['id'], $secreto, (string) $bit['url'])) ?>" rel="nofollow noopener">
                <?= web_e($bit['fuente'] ?? 'Leer la fuente') ?> →
              </a>
              <?php if (!empty($bit['fuente'])): ?>
                <a class="ficha-medio" href="<?= web_e(web_url_medio($base, web_slug_medio((string) $bit['fuente']))) ?>">ficha</a>
              <?php endif; ?>
            <?php endif; ?>

            <?php if ($unica !== null): ?>
              <?php // Con una sola fuente no hay desplegable que abrir, asi que
                    // sus datos van aqui: de donde es, en que idioma publica y
                    // cuando lo conto. Es la mitad de lo que hace falta para
                    // saber cuanto fiarse de una noticia. ?>
              <span class="datos">
                <?= web_e($ambitos[$unica['region']] ?? $unica['region']) ?>
                <span class="punto">·</span>
                <?= web_e($idiomas[$unica['idioma']] ?? $unica['idioma']) ?>
                <span class="punto">·</span>
                <time datetime="<?= web_e(substr((string) $unica['publicado'], 0, 10)) ?>"><?= web_e(web_fecha_larga(substr((string) $unica['publicado'], 0, 10))) ?></time>
              </span>
            <?php endif; ?>

          </p>

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

  <?php if ($temas || $medios): ?>
    <?php // Al final y no arriba: quien ha llegado hasta aqui ya ha leido la
          // edicion y lo siguiente que quiere es tirar del hilo. Arriba seria
          // un menu que estorba; aqui es una puerta. ?>
    <section class="explorar" aria-labelledby="explorar-titulo">
      <h2 id="explorar-titulo">Seguir tirando del hilo</h2>

      <?php if ($temas): ?>
        <h3 class="explorar-grupo">Por tema</h3>
        <ul class="nube nube-temas">
        <?php foreach ($temas as $tema): ?>
          <li data-tema="<?= web_e($tema['slug']) ?>">
            <a href="<?= web_e(web_url_tema($base, (string) $tema['slug'])) ?>">
              <?= web_icono((string) $tema['slug'], 'icono icono-mini') ?>
              <span class="nube-nombre"><?= web_e($tema['nombre']) ?></span>
              <span class="nube-cuenta"><?= (int) $tema['bits'] ?></span>
            </a>
          </li>
        <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if ($medios): ?>
        <h3 class="explorar-grupo">Medios que hemos leído</h3>
        <ul class="nube nube-medios">
        <?php foreach ($medios as $medio): ?>
          <li>
            <a href="<?= web_e(web_url_medio($base, (string) $medio['slug'])) ?>">
              <span class="nube-nombre"><?= web_e($medio['nombre']) ?></span>
              <span class="nube-cuenta"><?= (int) $medio['bits'] ?></span>
            </a>
          </li>
        <?php endforeach; ?>
        </ul>

        <p class="letra-pequena explorar-pie">
          Son los medios de los que ha salido algo publicado. El radar rastrea
          bastantes más: los que no aparecen aquí es que esta temporada no han
          contado nada que pasara el filtro.
        </p>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
