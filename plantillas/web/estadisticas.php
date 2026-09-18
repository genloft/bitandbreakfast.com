<?php
/**
 * Cifras: un cuadro de mandos con datos de fuera, no de la base propia.
 *
 * Toda la web se genera a partir de lo que el radar ha rastreado; esta
 * pagina es la unica excepcion a proposito. Son cifras de organismos y
 * estudios ajenos -INE, Eurostat, IBM, AEPD, informes del sector- puestas
 * una junto a otra para que un directivo vea de un vistazo donde esta
 * España frente al resto: adopcion de IA, y lo que cuesta no cuidar la
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
];

// Cada grupo compara Espana con la referencia que exista -Union Europea o
// el dato global del sector-, nunca dos cosas que no se puedan comparar. Si
// un informe solo cubre un lado, el grupo se queda con un solo lado en vez
// de inventar el que falta.
$grupos = [
    [
        'tema' => 'IA en la empresa, en general',
        'nota' => 'Esta y las dos siguientes -cloud y comercio electrónico- no son datos del sector hotelero: son la vara de medir de fondo, la misma para cualquier sector.',
        'cifras' => [
            [
                'ambito'  => 'España',
                'valor'   => cifras_valor_ia_espana() . '%',
                'detalle' => 'de las empresas de 10 o más empleados usa inteligencia artificial',
                'fuente'  => 'INE, Encuesta sobre el uso de TIC y comercio electrónico en las empresas',
                'fecha'   => 'dato de 2025 (1T) · publicado en octubre de 2025',
                'url'     => 'https://www.ine.es/dyngs/Prensa/ETICCE20241T2025.htm',
            ],
            [
                'ambito'  => 'Unión Europea',
                'valor'   => '20,0%',
                'detalle' => 'de las empresas de 10 o más empleados usa inteligencia artificial (media UE)',
                'fuente'  => 'Eurostat',
                'fecha'   => '2025 · publicado en diciembre de 2025',
                'url'     => 'https://ec.europa.eu/eurostat/web/products-eurostat-news/w/ddn-20251211-2',
            ],
        ],
        'destacado' => 'Por primera vez, España supera la media europea.',
    ],
    [
        'tema' => 'Cloud computing en la empresa',
        'cifras' => [
            [
                'ambito'  => 'España',
                'valor'   => '44,3%',
                'detalle' => 'de las empresas usa servicios de computación en la nube de pago',
                'fuente'  => 'INE, Encuesta sobre el uso de TIC y comercio electrónico en las empresas',
                'fecha'   => 'dato de 2024/2025 · publicado en octubre de 2025',
                'url'     => 'https://www.ine.es/dyngs/Prensa/ETICCE20241T2025.htm',
            ],
            [
                'ambito'  => 'Unión Europea',
                'valor'   => '52,7%',
                'detalle' => 'de las empresas usa servicios de computación en la nube de pago (media UE)',
                'fuente'  => 'Eurostat',
                'fecha'   => '2025 · publicado en febrero de 2026',
                'url'     => 'https://ec.europa.eu/eurostat/web/products-eurostat-news/w/ddn-20260203-1',
            ],
        ],
        'destacado' => 'Aquí España va por detrás: casi ocho puntos por debajo de la media europea.',
    ],
    [
        'tema' => 'Comercio electrónico',
        'cifras' => [
            [
                'ambito'  => 'España',
                'valor'   => '26,6%',
                'detalle' => 'de las empresas vendió por comercio electrónico en 2024',
                'fuente'  => 'INE, Encuesta sobre el uso de TIC y comercio electrónico en las empresas',
                'fecha'   => 'dato de 2024 · publicado en octubre de 2025',
                'url'     => 'https://www.ine.es/dyngs/Prensa/ETICCE20241T2025.htm',
            ],
            [
                'ambito'  => 'Unión Europea',
                'valor'   => '23,6%',
                'detalle' => 'de las empresas vendió por comercio electrónico en 2024 (media UE)',
                'fuente'  => 'Eurostat',
                'fecha'   => 'dato de 2024 · publicado en junio de 2026',
                'url'     => 'https://ec.europa.eu/eurostat/statistics-explained/index.php?title=E-commerce_statistics',
            ],
        ],
        'destacado' => 'Y aquí al revés: España supera la media europea en comercio electrónico.',
    ],
    [
        'tema' => 'IA en los hoteles',
        'cifras' => [
            [
                'ambito'  => 'Global',
                'valor'   => '82%',
                'detalle' => 'de los hoteles ampliará su uso de IA en 2026',
                'fuente'  => 'RateGain, NYU SPS y HEDNA — «State of Distribution 2026» (270+ cadenas, 58.000+ hoteles, 53 países)',
                'fecha'   => 'encuesta dic. 2024–nov. 2025 · publicado en 2026',
                'url'     => 'https://rategain.com/press-release/state-of-distribution-2026-launch/',
            ],
            [
                'ambito'  => 'Global',
                'valor'   => '< 1 de cada 10',
                'detalle' => 'hoteles ve un impacto real medible, aunque más de la mitad ya usa o adquiere IA generativa',
                'fuente'  => 'mismo informe',
                'fecha'   => '2026',
                'url'     => 'https://rategain.com/press-release/state-of-distribution-2026-launch/',
            ],
        ],
        'destacado' => 'La adopción va muy por delante del resultado: casi nadie mide todavía si de verdad funciona.',
    ],
    [
        'tema' => 'IA en el viajero',
        'cifras' => [
            [
                'ambito'  => 'España',
                'valor'   => '35–45%',
                'detalle' => 'de los viajeros ya usa IA para planificar sus vacaciones, según la encuesta; supera el 50% entre los 18 y los 34 años',
                'fuente'  => 'Simon-Kucher (Travel Trends 2026) y Allianz Partners',
                'fecha'   => '2026',
                'url'     => 'https://www.allianz-partners.com/es_ES/sala-de-prensa/notas-de-prensa/noticias-2026/el-45-de-los-vajeros-recurre-a-la-ia-para-planificar-sus-vacaciones.html',
            ],
            [
                'ambito'  => 'Global',
                'valor'   => '40–54%',
                'detalle' => 'de los viajeros ha usado ya una IA para planificar un viaje, según la encuesta',
                'fuente'  => 'Statista (~40%) y Skyscanner Travel Trends (54% en 2025)',
                'fecha'   => '2025–2026',
                'url'     => 'https://www.statista.com/topics/10887/artificial-intelligence-ai-use-in-travel-and-tourism/',
            ],
        ],
        'destacado' => 'España aparece, según varias encuestas, entre los países líderes de Europa en este uso.',
    ],
    [
        'tema' => 'Ciberseguridad hotelera',
        'cifras' => [
            [
                'ambito'  => 'Global',
                'valor'   => '4,03 M$',
                'detalle' => 'coste medio de una brecha de datos en hostelería en 2025 — sube, mientras la media de todos los sectores bajó a 4,44 M$',
                'fuente'  => 'IBM, Cost of a Data Breach Report',
                'fecha'   => '2025',
                'url'     => 'https://www.ibm.com/reports/data-breach',
            ],
            [
                'ambito'  => 'España',
                'valor'   => '919',
                'detalle' => 'notificaciones de brechas de datos personales a la AEPD en julio de 2026 — la cifra mensual más alta en un año, más del triple que en julio de 2025 (282); la propia AEPD señaló a los hoteles y a las empresas de gestión de reservas como uno de los focos del repunte',
                'fuente'  => 'AEPD, vía Gobierno de España',
                'fecha'   => 'julio de 2026',
                'url'     => 'https://www.moncloa.com/2026/08/20/aepd-ciberataques-hoteles-espana-verano-3418190',
            ],
        ],
        'destacado' => 'El coste sube donde nadie mira: la hostelería no es de los sectores más caros, pero es de los pocos que van a peor.',
    ],
];

$descripcion = 'IA, cloud, comercio electrónico y ciberseguridad en tecnología hotelera: España frente al dato global, con fuente y fecha en cada cifra.';

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cifras · Bit &amp; Breakfast</title>
<meta name="description" content="<?= web_e($descripcion) ?>">
<link rel="canonical" href="<?= web_e($base) ?>/estadisticas.html">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version) ?>">
<meta name="theme-color" content="#12100d">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="Cifras de la tecnología hotelera, España frente al mundo">
<meta property="og:description" content="<?= web_e($descripcion) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= web_e($base) ?>/estadisticas.html">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido">
  <header class="edicion-cabecera">
    <p class="sello">Cifras</p>
    <h1>España frente al mundo</h1>
    <p class="datos">IA, cloud, comercio electrónico y ciberseguridad, con fuente y fecha en cada cifra</p>
  </header>

  <p class="intro">Ninguna de estas cifras la ha medido este radar: son de organismos y estudios ajenos, puestos aquí uno junto a otro para poder comparar. Cada una lleva su fuente y su fecha porque el criterio del resto del sitio también vale aquí: si no se puede comprobar, no se publica. Todas son del último año. Quién firma cada cifra importa tanto como la cifra misma, así que las fuentes están otra vez todas juntas al final, con quiénes son y un enlace.</p>

  <section class="cuadro" aria-label="Cuadro de mandos">
    <?php foreach ($grupos as $grupo): ?>
      <article class="cuadro-tarjeta">
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
    <p class="cuadro-nota">Siete organismos y estudios, ninguno de este sitio. Cuanto más se sabe de quién mide algo, mejor se sabe cuánto fiarse de lo que mide.</p>

    <dl class="fuentes-lista">
      <?php foreach ($fuentes as $fuente): ?>
        <div class="fuentes-fila">
          <dt><a href="<?= web_e($fuente['url']) ?>" rel="nofollow noopener"><?= web_e($fuente['nombre']) ?></a></dt>
          <dd><?= web_e($fuente['detalle']) ?></dd>
        </div>
      <?php endforeach; ?>
    </dl>
  </section>

  <p class="letra-pequena cuadro-revision">Datos revisados el <?= web_e(web_fecha_larga($revisado)) ?>, con revisión antes del <?= web_e(web_fecha_larga($limite_revision)) ?> como muy tarde —o en cuanto salga un informe nuevo, lo que llegue primero—. Si una cifra de aquí ya tiene más de un año, avísanos.</p>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
