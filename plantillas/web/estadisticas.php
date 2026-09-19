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
        'tema' => 'Turismo internacional',
        'nota' => 'Esta y las dos siguientes ya no son sobre tecnología: son el tamaño real del sector en el que esa tecnología se usa.',
        'cifras' => [
            [
                'ambito'  => 'España',
                'valor'   => '96,8 M',
                'detalle' => 'turistas internacionales recibidos en 2025, máximo histórico (+3,2% sobre 2024)',
                'fuente'  => 'INE, Estadística de Movimientos Turísticos en Frontera (Frontur)',
                'fecha'   => 'año 2025 · publicado en enero de 2026',
                'url'     => 'https://www.ine.es/dyngs/Prensa/FRONTUR1225.htm',
            ],
            [
                'ambito'  => 'Global',
                'valor'   => '1.520 M',
                'detalle' => 'turistas internacionales en todo el mundo en 2025, un nuevo récord (+4% sobre 2024)',
                'fuente'  => 'UN Tourism, World Tourism Barometer',
                'fecha'   => 'año 2025 · publicado en enero de 2026',
                'url'     => 'https://www.untourism.int/news/international-tourist-arrivals-up-4-in-2025-reflecting-strong-travel-demand-around-the-world',
            ],
        ],
        'destacado' => 'España sola concentra más del 6% de todo el turismo internacional del planeta.',
    ],
    [
        'tema' => 'Contribución económica del turismo',
        'cifras' => [
            [
                'ambito'  => 'España',
                'valor'   => '16%',
                'detalle' => 'del PIB español lo aporta el sector de viajes y turismo, con más de 3,2 millones de empleos',
                'fuente'  => 'WTTC — World Travel & Tourism Council',
                'fecha'   => 'año 2025 · publicado en mayo de 2025',
                'url'     => 'https://wttc.org/news/el-sector-turistico-de-espana-podria-superar-los-260000-millones-de-euros-en-2025',
            ],
            [
                'ambito'  => 'Global',
                'valor'   => '9,9%',
                'detalle' => 'del PIB mundial (12 billones de dólares) lo aporta el sector, con 376 millones de empleos —uno de cada nueve del planeta—',
                'fuente'  => 'WTTC — World Travel & Tourism Council',
                'fecha'   => 'previsión 2026 · publicado en mayo de 2026',
                'url'     => 'https://wttc.org/news/global-travel-tourism-growth-to-outpace-wider-economy-by-1-5-times-over-the-next-decade',
            ],
        ],
        'destacado' => 'El turismo pesa en España mucho más que en el resto del mundo: un 16% del PIB frente a un 9,9% global.',
    ],
    [
        'tema' => 'Rendimiento hotelero',
        'cifras' => [
            [
                'ambito'  => 'España',
                'valor'   => '61,4%',
                'detalle' => 'de ocupación media en 2025, con un ADR de 127,7 € y un RevPAR de 89,7 €; récord histórico de pernoctaciones',
                'fuente'  => 'INE, Coyuntura Turística Hotelera (EOH/IPH/IRSH)',
                'fecha'   => 'año 2025 · publicado en enero de 2026',
                'url'     => 'https://ine.es/dyngs/Prensa/CTH1225.htm',
            ],
            [
                'ambito'  => 'Estados Unidos',
                'valor'   => '62,3%',
                'detalle' => 'de ocupación media en 2025, con un ADR de 160,54 $ y un RevPAR de 100,02 $ —el primer retroceso anual en ocupación y RevPAR desde 2020—',
                'fuente'  => 'STR / CoStar',
                'fecha'   => 'año 2025 · publicado en enero de 2026',
                'url'     => 'https://www.costar.com/products/str-benchmark/resources/press-releases/us-hotels-report-first-full-year-occupancy-revpar',
            ],
        ],
        'destacado' => 'España ganó terreno en 2025; el mercado hotelero más grande del mundo, por primera vez desde 2020, lo perdió.',
    ],
    [
        'tema' => 'Inversión hotelera',
        'cifras' => [
            [
                'ambito'  => 'España',
                'valor'   => '4.275 M€',
                'detalle' => 'invertidos en hoteles en España en 2025 -194 operaciones-, el segundo mejor registro histórico',
                'fuente'  => 'Colliers, Informe de Inversión Hotelera en España 2025',
                'fecha'   => 'año 2025 · publicado en enero de 2026',
                'url'     => 'https://www.colliers.com/es-es/research/informe-inversion-hotelera-en-espana-2025',
            ],
            [
                'ambito'  => 'España',
                'valor'   => '+30%',
                'detalle' => 'creció la inversión en hoteles ya en funcionamiento sobre 2024 -de 3.064 M€ a 3.986 M€-',
                'fuente'  => 'mismo informe',
                'fecha'   => '2025',
                'url'     => 'https://www.colliers.com/es-es/research/informe-inversion-hotelera-en-espana-2025',
            ],
        ],
        'destacado' => 'El segmento vacacional concentra ya el 55% de toda la inversión hotelera, y recupera el liderazgo frente al urbano.',
    ],
    [
        'tema' => 'Empleo en alojamiento',
        'cifras' => [
            [
                'ambito'  => 'España',
                'valor'   => '473.450',
                'detalle' => 'personas ocupadas en alojamiento en 2025 -media anual-, un 1,8% más que en 2024',
                'fuente'  => 'INE, Encuesta de Población Activa, vía Hostelería Digital',
                'fecha'   => 'año 2025 · publicado en enero de 2026',
                'url'     => 'https://www.hosteleriadigital.es/2026/01/28/epa-2025-32-000-trabajadores-menos-en-restauracion-y-8-000-mas-en-alojamiento/',
            ],
            [
                'ambito'  => 'España',
                'valor'   => '−2,3%',
                'detalle' => 'cayó el empleo en restauración en el mismo año -32.375 personas menos-, el otro lado del sector hostelero',
                'fuente'  => 'mismo informe',
                'fecha'   => '2025',
                'url'     => 'https://www.hosteleriadigital.es/2026/01/28/epa-2025-32-000-trabajadores-menos-en-restauracion-y-8-000-mas-en-alojamiento/',
            ],
        ],
        'destacado' => 'El alojamiento crece en empleo mientras la restauración lo pierde: "hostelería" no es una sola foto.',
    ],
    [
        'tema' => 'Gasto turístico',
        'cifras' => [
            [
                'ambito'  => 'España',
                'valor'   => '195 €',
                'detalle' => 'gasto medio diario de un turista internacional en 2025 -media anual-, un 4,9% más que en 2024',
                'fuente'  => 'INE, Encuesta de Gasto Turístico (Egatur)',
                'fecha'   => 'año 2025 · publicado en febrero de 2026',
                'url'     => 'https://www.ine.es/dyngs/Prensa/EGATUR1225.htm',
            ],
            [
                'ambito'  => 'España',
                'valor'   => '134.712 M€',
                'detalle' => 'gasto total de los turistas internacionales en España en 2025, un 6,8% más que en 2024',
                'fuente'  => 'mismo informe',
                'fecha'   => '2025',
                'url'     => 'https://www.ine.es/dyngs/Prensa/EGATUR1225.htm',
            ],
        ],
        'destacado' => 'El turista de hoy no solo es más numeroso: cada uno gasta más cada día que el del año pasado.',
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

  <p class="letra-pequena cuadro-revision">Datos revisados el <?= web_e(web_fecha_larga($revisado)) ?>, con revisión antes del <?= web_e(web_fecha_larga($limite_revision)) ?> como muy tarde —o en cuanto salga un informe nuevo, lo que llegue primero—. Si una cifra de aquí ya tiene más de un año, avísanos.</p>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
