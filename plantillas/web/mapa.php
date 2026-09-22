<?php
/**
 * El mapa del stack, entero: /mapa.html
 *
 * La portada enseña el estado; esto enseña por que. Las veinticuatro casillas
 * arriba, y debajo cada nodo con las noticias que lo han encendido, agrupados
 * por area. Sin JavaScript se ve exactamente lo mismo, solo que en vertical:
 * todas las secciones estan escritas en el HTML y no hay ni una que se traiga
 * despues. El guion de mapa.js no añade contenido, solo lo recoloca en un
 * panel lateral para no perder de vista la rejilla.
 *
 * Por que un nodo apagado tambien tiene su seccion: en un mapa de riesgos,
 * "de esto no ha pasado nada este mes" es informacion, y a veces la mejor.
 * Una casilla que no se puede pulsar deja al lector sin saber si es que no ha
 * pasado nada o si es que el mapa esta roto.
 *
 * Recibe $mapa, $base y lo comun de todas las paginas.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js   = $version_js ?? '0';
$version_mapa = $version_mapa ?? '0';
$mapa         = $mapa ?? ['nodes' => [], 'briefs' => []];

$enlace_activo = 'mapa';
$alta_abierta  = $alta_abierta ?? false;

require_once __DIR__ . '/iconos.php';

$resumen = heatmap_resumen($mapa);

$descripcion = sprintf(
    'El stack tecnológico de un hotel en veinticuatro nodos: %d con novedades y %d que piden una decisión, con las noticias que lo explican.',
    $resumen['encendidos'],
    $resumen['altos']
);

$mapa_variante = 'completo';

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>El mapa del stack · Bit &amp; Breakfast</title>
<meta name="description" content="<?= web_e($descripcion) ?>">
<link rel="canonical" href="<?= web_e($base) ?>/mapa.html">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="icon" href="<?= web_e($base) ?>/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version) ?>">
<meta name="theme-color" content="#f4f2ee">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="El mapa del stack hotelero">
<meta property="og:description" content="<?= web_e($descripcion) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= web_e($base) ?>/mapa.html">
<meta property="og:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido" class="mapa-pagina">
  <header class="edicion-cabecera">
    <p class="sello">El mapa del stack</p>
    <h1>Qué parte de tu sistema está caliente</h1>
    <p class="datos">
      <?= (int) $resumen['encendidos'] ?> de 24 nodos con novedades<?php if ($resumen['altos'] > 0): ?> · <?= (int) $resumen['altos'] ?> piden una decisión<?php endif; ?>
    </p>
  </header>

  <p class="intro">El stack de un hotel, partido en veinticuatro piezas. Cada noticia que este radar publica se reparte entre las piezas que menciona, y cada pieza vale lo que su noticia más urgente: nunca la suma, porque cinco noticias menores no son una emergencia. Todo caduca solo —una brecha envejece en una semana, una norma en un mes— y como mucho tres nodos pueden estar en rojo a la vez, que es lo que separa un mapa de una alarma que nadie mira.</p>

  <p class="intro">Ninguna casilla se enciende por una corazonada: lo hace porque el texto de la noticia nombra algo de esa pieza. La regla está publicada, nodo a nodo, en <a href="<?= web_e($base) ?>/data/taxonomy.json">taxonomy.json</a>, y el estado del día entero en <a href="<?= web_e($base) ?>/data/heatmap.json">heatmap.json</a>.</p>

  <?php if (!empty($mapa['stale'])): ?>
    <p class="aviso-viejo">Este mapa no recibe una noticia nueva desde hace más de una semana. Lo que hay sigue vigente, pero no es de hoy.</p>
  <?php endif; ?>

  <?php require __DIR__ . '/mapa_grafo.php'; ?>

  <?php foreach (stack_areas() as $area_id => $area_label): ?>
    <section class="mapa-detalle" aria-labelledby="detalle-<?= web_e($area_id) ?>">
      <h2 class="mapa-detalle-area" id="detalle-<?= web_e($area_id) ?>"><?= web_e($area_label) ?></h2>

      <?php foreach (stack_nodos_de($area_id) as $nodo_id): ?>
        <?php
          $estado = $mapa['nodes'][$nodo_id] ?? null;
          $briefs = $estado === null ? [] : heatmap_briefs_de($mapa, $nodo_id);
        ?>
        <article class="mapa-nodo<?= $estado === null ? ' mapa-nodo--apagado' : ' mapa-nodo--' . web_e($estado['signal']) ?>"
                 id="nodo-<?= web_e($nodo_id) ?>"
                 data-nodo="<?= web_e($nodo_id) ?>"
                 aria-labelledby="nodo-<?= web_e($nodo_id) ?>-titulo"
                 tabindex="-1">

          <header class="mapa-nodo-cabeza">
            <h3 id="nodo-<?= web_e($nodo_id) ?>-titulo"><?= web_e(stack_etiqueta($nodo_id)) ?></h3>

            <?php if ($estado === null): ?>
              <p class="mapa-nodo-estado mapa-nodo-estado--apagado">En calma</p>
            <?php else: ?>
              <p class="mapa-nodo-estado mapa-nodo-estado--<?= web_e($estado['level']) ?> mapa-nodo-estado--<?= web_e($estado['signal']) ?>">
                <?= web_icono_senal($estado['signal']) ?>
                <?= $estado['signal'] === 'riesgo' ? 'Riesgo' : 'Oportunidad' ?>
                <span class="mapa-nodo-nivel">nivel <?= web_e($estado['level']) ?></span>
              </p>
            <?php endif; ?>
          </header>

          <?php if (!empty($estado['atenuado'])): ?>
            <?php // Sin esta linea, el nodo diria "nivel medio" con una noticia
                  // de 4 sobre 5 justo debajo, y el lector concluiria que el
                  // mapa se equivoca. Se equivoca menos explicandolo. ?>
            <p class="mapa-nodo-nota">Este nodo daría nivel alto, pero ya hay tres en rojo y solo caben tres. Su noticia mantiene su puntuación entera, aquí abajo.</p>
          <?php endif; ?>

          <?php if (!$briefs): ?>
            <p class="mapa-nodo-vacio">Ninguna de las noticias de las últimas semanas toca esta pieza del stack.</p>
          <?php else: ?>
            <ol class="mapa-briefs">
              <?php foreach ($briefs as $brief): ?>
                <li class="mapa-brief mapa-brief--<?= web_e($brief['level']) ?>">
                  <p class="mapa-brief-marcas">
                    <span class="mapa-brief-marca mapa-brief-marca--<?= web_e($brief['signal']) ?>">
                      <?= $brief['signal'] === 'riesgo' ? 'Riesgo' : 'Oportunidad' ?> <?= (int) $brief['score'] ?>/5
                    </span>
                    <?php if ($brief['confidence'] === 'baja'): ?>
                      <span class="mapa-brief-marca mapa-brief-marca--duda" title="La noticia no nombra ninguna palabra propia de este nodo: se ha clasificado por su temática.">Encaje dudoso</span>
                    <?php endif; ?>
                    <span class="mapa-brief-fecha"><?= web_e(web_fecha_larga($brief['published_at'])) ?></span>
                  </p>

                  <h4 class="mapa-brief-titular">
                    <a href="<?= web_e(web_url_dia($base, $brief['published_at'])) ?>#bit-<?= (int) $brief['bit_id'] ?>"><?= web_e($brief['headline']) ?></a>
                  </h4>

                  <p class="mapa-brief-trigger"><?= web_e($brief['trigger']) ?></p>

                  <?php if ($brief['por_que'] !== ''): ?>
                    <p class="mapa-brief-porque"><span class="mapa-brief-etiqueta">Por qué importa</span> <?= web_e($brief['por_que']) ?></p>
                  <?php endif; ?>

                  <p class="mapa-brief-fuente">
                    <?php if (!empty($brief['sources'][0]['url'])): ?>
                      <a href="<?= web_e((string) $brief['sources'][0]['url']) ?>" rel="nofollow noopener"><?= web_e((string) $brief['sources'][0]['publisher']) ?></a>
                    <?php else: ?>
                      <?= web_e((string) $brief['sources'][0]['publisher']) ?>
                    <?php endif; ?>
                    <?php if ((int) $brief['medios'] > 1): ?>
                      <span class="mapa-brief-medios">y <?= (int) $brief['medios'] - 1 ?> medio<?= (int) $brief['medios'] === 2 ? '' : 's' ?> más</span>
                    <?php endif; ?>
                  </p>
                </li>
              <?php endforeach; ?>
            </ol>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </section>
  <?php endforeach; ?>

  <?php // El panel lateral. Nace vacio: mapa.js mete dentro la seccion del
        // nodo que se pulse y la devuelve a su sitio al cerrar, asi que no hay
        // ni una palabra duplicada entre el HTML y el guion. Sin JavaScript
        // este <dialog> no se abre nunca y no estorba: las secciones siguen
        // ahi arriba, en su orden. ?>
  <dialog class="mapa-panel" id="mapa-panel" aria-label="Detalle del nodo">
    <form method="dialog" class="mapa-panel-cerrar">
      <button value="cerrar" aria-label="Cerrar el detalle">Cerrar</button>
    </form>
    <div class="mapa-panel-cuerpo" id="mapa-panel-cuerpo"></div>
  </dialog>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

<script src="<?= web_e($base) ?>/mapa.js?v=<?= web_e($version_mapa) ?>" defer></script>

</body>
</html>
