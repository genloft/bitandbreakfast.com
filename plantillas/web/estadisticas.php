<?php
/**
 * Cifras: un cuadro de mandos con datos de fuera, no de la base propia.
 *
 * Toda la web se genera a partir de lo que el radar ha rastreado; esta
 * pagina es la unica excepcion a proposito. Son cifras de organismos y
 * estudios ajenos -INE, Eurostat, IBM, AEPD, UN Tourism, WTTC, STR/CoStar,
 * Colliers, informes del sector- puestas una junto a otra para que un
 * directivo vea de un vistazo donde esta España frente al resto: adopcion de
 * IA, peso economico real del turismo, inversion hotelera, empleo, gasto
 * turistico, rendimiento hotelero, y lo que cuesta no cuidar la
 * ciberseguridad.
 *
 * Por eso vive en un array escrito a mano y no en una consulta: no hay tabla
 * que resuma media docena de informes de media docena de organismos
 * distintos, y forzarla habria sido peor que admitir que esto se actualiza a
 * mano. La fecha de "Datos revisados el" de mas abajo es la unica promesa que
 * hace esta pagina; todas las cifras llevan su propia fecha y su propio
 * enlace para que se pueda comprobar cada una por separado. La misma fecha,
 * y el umbral a partir del cual esta caducada, viven en lib/cifras.php:
 * cron/mantenimiento.php la lee de ahi cada dia para avisar por correo si
 * esto lleva demasiado sin que alguien lo revise.
 *
 * "Quien firma cada cifra" tiene aqui el mismo peso que la cifra misma: la
 * seccion de fuentes de mas abajo no es una bibliografia de cortesia, es la
 * unica razon por la que esta pagina puede decir algo que un agregador de
 * titulares no podria.
 *
 * Recibe $base y $alta_abierta.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';

$enlace_activo = 'cifras';
$alta_abierta  = $alta_abierta ?? false;

// Fija, no gmdate('Y-m-d'): esta pagina no la genera el radar a partir de su
// propia base, alguien la revisa a mano. Si fuera la fecha del momento en
// que el cron regenera el sitio -que puede ser por cualquier otro bit-,
// diria "revisado hoy" sin que nadie hubiera mirado esto hoy. Vive en
// lib/cifras.php para que cron/mantenimiento.php pueda leerla sin ejecutar
// esta pagina.
$revisado         = cifras_revisado();
$limite_revision  = cifras_limite_revision($revisado);

// Quien esta detras de cada cifra. No es un adorno: es la seccion que este
// array hace posible que exista, y por eso cada fuente que se cite arriba
// tiene que tener su entrada aqui debajo.
$fuentes = [
    [
        'nombre'  => 'INE — Instituto Nacional de Estadística',
        'detalle' => 'Organismo oficial de estadística del Gobierno de España.',
        'url'     => 'https://www.ine.es/',
    ],
    [
        'nombre'  => 'Eurostat',
        'detalle' => 'Oficina de estadística de la Comisión Europea.',
        'url'     => 'https://ec.europa.eu/eurostat',
    ],
    [
        'nombre'  => 'IBM — Cost of a Data Breach Report',
        'detalle' => 'Informe anual de referencia del sector sobre el coste de las brechas de seguridad.',
        'url'     => 'https://www.ibm.com/reports/data-breach',
    ],
    [
        'nombre'  => 'AEPD — Agencia Española de Protección de Datos',
        'detalle' => 'Autoridad española de protección de datos.',
        'url'     => 'https://www.aepd.es/',
    ],
    [
        'nombre'  => 'RateGain, NYU SPS y HEDNA',
        'detalle' => 'Informe anual «State of Distribution» sobre comercialización hotelera.',
        'url'     => 'https://rategain.com/press-release/state-of-distribution-2026-launch/',
    ],
    [
        'nombre'  => 'Simon-Kucher y Allianz Partners',
        'detalle' => 'Consultora y asegurador de viajes; encuestas propias sobre comportamiento del viajero.',
        'url'     => 'https://www.allianz-partners.com/es_ES/sala-de-prensa/notas-de-prensa/noticias-2026/el-45-de-los-vajeros-recurre-a-la-ia-para-planificar-sus-vacaciones.html',
    ],
    [
        'nombre'  => 'Statista y Skyscanner',
        'detalle' => 'Plataforma de datos de mercado y buscador de viajes; encuestas propias.',
        'url'     => 'https://www.statista.com/topics/10887/artificial-intelligence-ai-use-in-travel-and-tourism/',
    ],
    [
        'nombre'  => 'UN Tourism (antes OMT)',
        'detalle' => 'Agencia de Naciones Unidas para el turismo; publica el World Tourism Barometer.',
        'url'     => 'https://www.untourism.int/',
    ],
    [
        'nombre'  => 'WTTC — World Travel & Tourism Council',
        'detalle' => 'Organización del sector turístico mundial; publica su Economic Impact Research con Oxford Economics.',
        'url'     => 'https://wttc.org/research/economic-impact',
    ],
    [
        'nombre'  => 'STR / CoStar',
        'detalle' => 'Proveedor de referencia de datos de rendimiento hotelero: ocupación, precio medio (ADR) e ingreso por habitación disponible (RevPAR).',
        'url'     => 'https://www.costar.com/products/str-benchmark',
    ],
    [
        'nombre'  => 'Colliers',
        'detalle' => 'Consultora inmobiliaria internacional; publica el informe anual de inversión hotelera en España.',
        'url'     => 'https://www.colliers.com/es-es/research/informe-inversion-hotelera-en-espana-2025',
    ],
    [
        'nombre'  => 'Hostelería Digital',
        'detalle' => 'Medio especializado del sector; aquí, vía el desglose por rama de actividad de la EPA del INE.',
        'url'     => 'https://www.hosteleriadigital.es/2026/01/28/epa-2025-32-000-trabajadores-menos-en-restauracion-y-8-000-mas-en-alojamiento/',
    ],
];

// Cada grupo compara Espana con la referencia que exista -Union Europea o
// el dato global del sector-, nunca dos cosas que no se puedan comparar. Si
// un informe solo cubre un lado, el grupo se queda con un solo lado en vez
// de inventar el que falta. Vive en lib/cifras.php como cifras_grupos():
// plantillas/web/tema.php necesita los mismos grupos para la ficha de cada
// tema.
$grupos = cifras_grupos();

$descripcion = 'IA, turismo, inversión, empleo y rendimiento hotelero: España frente al dato global, con fuente y fecha en cada cifra.';

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cifras · Bit &amp; Breakfast</title>
<meta name="description" content="<?= web_e($descripcion) ?>">
<link rel="canonical" href="<?= web_e($base) ?>/estadisticas.html">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="icon" href="<?= web_e($base) ?>/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version) ?>">
<meta name="theme-color" content="#12100d">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="Cifras de la tecnología hotelera, España frente al mundo">
<meta property="og:description" content="<?= web_e($descripcion) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= web_e($base) ?>/estadisticas.html">
<meta property="og:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido">
  <header class="edicion-cabecera">
    <p class="sello">Cifras</p>
    <h1>España frente al mundo</h1>
    <p class="datos">IA, turismo, inversión, empleo y rendimiento hotelero, con fuente y fecha en cada cifra</p>
  </header>

  <p class="intro">Ninguna de estas cifras la ha medido este radar: son de organismos y estudios ajenos, puestos aquí uno junto a otro para poder comparar. Cada una lleva su fuente y su fecha porque el criterio del resto del sitio también vale aquí: si no se puede comprobar, no se publica. Todas son del último año. Quién firma cada cifra importa tanto como la cifra misma, así que las fuentes están otra vez todas juntas al final, con quiénes son y un enlace.</p>

  <section class="cuadro" aria-label="Cuadro de mandos">
    <?php foreach ($grupos as $grupo): ?>
      <article id="<?= web_e(web_slug_seguro($grupo['tema'])) ?>" class="cuadro-tarjeta">
        <h2 class="cuadro-tema"><?= web_e($grupo['tema']) ?></h2>

        <?php if (!empty($grupo['nota'])): ?>
          <p class="cuadro-nota"><?= web_e($grupo['nota']) ?></p>
        <?php endif; ?>

        <div class="cuadro-cifras">
          <?php foreach ($grupo['cifras'] as $cifra): ?>
            <div class="cuadro-cifra">
              <p class="cuadro-ambito"><?= web_e($cifra['ambito']) ?></p>
              <p class="cuadro-valor"><?= web_e($cifra['valor']) ?></p>
              <p class="cuadro-detalle"><?= web_e($cifra['detalle']) ?></p>
              <p class="cuadro-fuente">
                <span class="cuadro-fuente-etiqueta">Fuente</span>
                <?php if (!empty($cifra['url'])): ?>
                  <a href="<?= web_e($cifra['url']) ?>" rel="nofollow noopener"><?= web_e($cifra['fuente']) ?></a>
                <?php else: ?>
                  <strong><?= web_e($cifra['fuente']) ?></strong>
                <?php endif; ?>
                <span class="punto">·</span><?= web_e($cifra['fecha']) ?>
              </p>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if (!empty($grupo['destacado'])): ?>
          <p class="cuadro-destacado"><?= web_e($grupo['destacado']) ?></p>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </section>

  <section class="fuentes" aria-labelledby="fuentes-titulo">
    <h2 id="fuentes-titulo">Quién firma estas cifras</h2>
    <p class="cuadro-nota">Doce organismos y estudios, ninguno de este sitio. Cuanto más se sabe de quién mide algo, mejor se sabe cuánto fiarse de lo que mide.</p>

    <dl class="fuentes-lista">
      <?php foreach ($fuentes as $fuente): ?>
        <div class="fuentes-fila">
          <dt><a href="<?= web_e($fuente['url']) ?>" rel="nofollow noopener"><?= web_e($fuente['nombre']) ?></a></dt>
          <dd><?= web_e($fuente['detalle']) ?></dd>
        </div>
      <?php endforeach; ?>
    </dl>
  </section>

  <p class="letra-pequena cuadro-revision">Datos revisados el <?= web_e(web_fecha_larga($revisado)) ?>, con revisión antes del <?= web_e(web_fecha_larga($limite_revision)) ?> como muy tarde —o en cuanto salga un informe nuevo, lo que llegue primero—. Si una cifra de aquí ya tiene más de un año, avísanos. Las mismas cifras, en <a href="<?= web_e($base) ?>/cifras.json">cifras.json</a>, para quien quiera citarlas sin rehacerlas.</p>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
