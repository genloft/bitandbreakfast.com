<?php
/**
 * La reserva agéntica: los protocolos que dejan reservar sin salir de un
 * chat, y si eso deja sin canal directo a un hotel.
 *
 * §2.4 de docs/MEJORAS.md pedía, como mínimo, esto: una página explicativa
 * permanente en Recursos, no una categoría nueva del catálogo de temas. Una
 * categoría habría exigido reclasificar bits ya publicados y repartir de
 * nuevo el diccionario -el mismo trabajo que costó separar ciberseguridad
 * de cumplimiento-, apostando fuerte por un tema que todavía se mueve mes a
 * mes. Esta página cubre la misma pregunta sin esa apuesta.
 *
 * Como /cumplimiento.html y /estadisticas.html: hechos con fecha y fuente,
 * revisados a mano -lib/agentica.php lleva la fecha y el plazo, más corto
 * que el de Cifras porque este terreno cambia más rápido-, nunca una
 * opinión sobre hacia dónde va esto.
 *
 * Recibe $base y $alta_abierta.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';

$enlace_activo = 'agentica';
$alta_abierta  = $alta_abierta ?? false;

$revisado        = agentica_revisado();
$limite_revision = agentica_limite_revision($revisado);

$descripcion = 'MCP, ACP, UCP y AP2: los protocolos que dejan reservar un hotel sin salir de un chat, explicados con fecha y fuente, y si de verdad dejan sin canal directo a un hotel.';

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reserva agéntica · Bit &amp; Breakfast</title>
<meta name="description" content="<?= web_e($descripcion) ?>">
<link rel="canonical" href="<?= web_e($base) ?>/agentica.html">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="icon" href="<?= web_e($base) ?>/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version) ?>">
<meta name="theme-color" content="#12100d">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="Reserva agéntica: ¿desaparece mi canal directo?">
<meta property="og:description" content="<?= web_e($descripcion) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= web_e($base) ?>/agentica.html">
<meta property="og:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido">
  <header class="edicion-cabecera">
    <p class="sello">Reserva agéntica</p>
    <h1>¿Desaparece mi canal directo?</h1>
    <p class="datos">Los protocolos que dejan reservar sin salir de un chat, con fecha y fuente</p>
  </header>

  <div class="texto pagina">

    <p class="intro">Hasta hace poco, «reserva agéntica» era una idea. Desde
    2025 son protocolos con nombre, con quien los firma y, en algún caso, ya
    con hoteles reales conectados. Esta página no opina sobre hacia dónde va
    esto -nadie lo sabe todavía-: cuenta qué ha pasado ya, con fecha y
    fuente, igual que <a href="<?= web_e($base) ?>/cumplimiento.html">Cumplimiento</a>.</p>

    <h2>Qué ha pasado ya</h2>

    <p><strong>MCP</strong> (Model Context Protocol) es la capa que deja que
    una IA -un chatbot, un agente- consulte los sistemas de un hotel:
    tarifas, disponibilidad, tipos de habitación. No reserva por sí solo: es
    la puerta que hace posible que un hotel aparezca, con datos reales,
    dentro de una búsqueda hecha por IA. A mediados de 2026 ya había más de
    10.000 servidores MCP en producción según sus propios impulsores, y
    Amadeus lo señala como el primer peldaño hacia protocolos de compra
    completos.</p>

    <p><strong>ACP</strong> (Agentic Commerce Protocol), de OpenAI y Stripe,
    sí llegó a dejar comprar sin salir de ChatGPT: «Instant Checkout»,
    lanzado en septiembre de 2025. Duró seis meses. El 24 de marzo de 2026
    OpenAI lo retiró por completo, y viajes fue el caso más citado de por
    qué no funcionaba: una tarifa de hotel cambia de precio en tiempo real,
    las condiciones de cancelación varían según la tarifa, y cuando algo
    sale mal -un vuelo cancelado, una disputa con el banco- alguien tiene
    que gestionarlo, y ese alguien no puede ser un chat. En su lugar, ACP
    pasó a un modelo «de descubrimiento»: ayuda a encontrar y comparar, pero
    la reserva se sigue completando en la web del propio hotel.</p>

    <p><strong>UCP</strong> (Universal Commerce Protocol) es la apuesta más
    reciente y la más directamente hotelera: Google lo amplió a alojamiento
    en mayo de 2026, con Amadeus, Booking.com, Expedia Group, Hilton,
    Marriott y Trip.com como socios de desarrollo. Dentro de UCP vive
    <strong>AP2</strong> (Agent Payments Protocol), la capa de pago -agnóstica
    de método, de tarjeta a criptomoneda- para cuando un agente de IA
    ejecuta una reserva de verdad. A diferencia del intento de OpenAI, UCP
    nace ya pensado para el ciclo completo de una reserva de hotel, no
    adaptado desde el comercio electrónico genérico.</p>

    <h2>¿Desaparece mi canal directo?</h2>

    <p>Con lo que se sabe hoy, no es la pregunta correcta. El propio tropiezo
    de OpenAI en viajes -la razón por la que retiró Instant Checkout- fue
    justo que un chat no puede sustituir a un motor de reservas: no gestiona
    tarifas dinámicas, políticas de cancelación ni una incidencia
    postventa. El modelo al que ACP ha migrado no compite con la web del
    hotel: la usa como destino final. Y UCP, aunque nace con ambición mayor,
    sigue necesitando que el hotel exista como sistema que responda con
    tarifa y disponibilidad reales -lo que hace MCP-, no lo sustituye.</p>

    <p>La pregunta que sí tiene datos detrás es otra: qué canal gana la
    reserva antes de que el viajero llegue a pagar. Ahí es donde entra la
    cifra que ya recoge <a href="<?= web_e($base) ?>/estadisticas.html#mix-de-canal-directo-y-ota">Cifras</a>:
    el canal directo del hotel ya iguala a las OTAs por primera vez, según
    la última edición del mismo informe que sigue esta página. Si la
    reserva agéntica acaba pareciéndose a algo ya conocido, lo más probable
    -a la vista de cómo se está construyendo- es un metabuscador más, no un
    sustituto del motor de reservas propio.</p>

    <h2>Los términos, en el glosario</h2>

    <p>MCP, ACP y el resto tienen su propia entrada, con la definición corta
    de siempre: <a href="<?= web_e($base) ?>/glosario.html#mcp">MCP</a>,
    <a href="<?= web_e($base) ?>/glosario.html#acp">ACP</a> y
    <a href="<?= web_e($base) ?>/glosario.html#agentic-booking">reserva
    agéntica</a>.</p>

  </div>

  <p class="letra-pequena cuadro-revision">Página revisada el <?= web_e(web_fecha_larga($revisado)) ?>, con revisión antes del <?= web_e(web_fecha_larga($limite_revision)) ?> como muy tarde: este terreno cambia de mes en mes. Si un dato de aquí ha quedado desfasado, avísanos.</p>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
