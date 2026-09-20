<?php
/**
 * Que es Bit & Breakfast. Recibe $base y $alta_abierta.
 *
 * Una pagina, no un manifiesto. Quien llega desde un enlace suelto necesita
 * saber en veinte segundos que es esto, quien lo hace y por que deberia
 * fiarse. Todo lo demas sobra.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';

$enlace_activo = 'sobre';
$alta_abierta  = $alta_abierta ?? false;
$radar         = $radar ?? ['total' => 0, 'por_region' => []];

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Qué es · Bit &amp; Breakfast</title>
<meta name="description" content="Bit &amp; Breakfast es un radar de tecnología hotelera: rastrea el sector, agrupa lo que cuenta lo mismo y publica cada día lo que encuentra.">
<link rel="canonical" href="<?= web_e($base) ?>/sobre.html">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="icon" href="<?= web_e($base) ?>/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version) ?>">
<meta name="theme-color" content="#12100d">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="Qué es Bit &amp; Breakfast">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= web_e($base) ?>/sobre.html">
<meta property="og:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido">
  <header class="edicion-cabecera">
    <p class="sello">Qué es esto</p>
    <h1>Un agregador con criterio</h1>
    <p class="datos">Lo que aparece cada día en el radar</p>
  </header>

  <div class="texto pagina">
    <p>Bit &amp; Breakfast rastrea cada hora <?= $radar['total'] > 0 ? (int) $radar['total'] . ' fuentes activas' : 'un catálogo de fuentes' ?> de
    tecnología hotelera: prensa del sector, páginas de estado de los
    proveedores, registros de cambios, boletines oficiales y rondas de
    financiación. Agrupa las noticias que cuentan lo mismo, las puntúa y deja
    una cola de candidatos.</p>

    <p>De esa cola salen cada día los <strong>bits</strong> que pasan las puertas.
    Los elige la puntuación, no una persona: entran los mejor puntuados que
    además hablan de tecnología hotelera según el diccionario del sistema.
    Esto es un agregador, no una redacción, y conviene que lo sepas: por eso
    está escrito aquí y no en la letra pequeña.</p>

    <p>Lo que <em>no</em> hace es inventar. El titular es el de la fuente, y el
    párrafo es el resumen que publica la propia fuente, sin reescribir y sin
    adornos. Todo lo que lees aquí se puede comprobar pulsando el enlace.</p>

    <p><strong>Todo en español, y se dice cuándo es traducido.</strong> Lo que
    ya contó alguien -en cualquier idioma- se traduce con DeepL si hace falta,
    pero solo después de pasar todas las puertas: nunca se traduce el
    artículo entero, solo el titular y el resumen, y cada bit traducido lo
    dice en su propia cara -«Traducido del inglés»-, sin dejar de enlazar a
    la fuente original en su idioma. No se inventa una noticia que nadie ha
    contado, y no hay una sola línea aquí que no puedas leer.</p>

    <h2>Qué es un bit</h2>

    <p>Un titular, un párrafo corto y los medios que lo cuentan. Cuando lo
    escribe una persona lleva además una línea, <em>por qué importa</em>, que
    dice qué cambia esto para un hotel; los bits automáticos no la llevan,
    porque ese juicio no se puede automatizar sin inventárselo.</p>

    <h2>Cómo se decide qué entra</h2>

    <p>Cuenta el peso de la fuente, lo reciente que sea, si menciona
    proveedores del catálogo y si varias fuentes independientes cuentan lo
    mismo, que es la mejor señal de que algo importa de verdad. Y resta: hay un
    filtro específico contra el publirreportaje, porque medio sector vive de
    publicar notas de prensa como si fueran noticias.</p>

    <p>Además se cae todo lo que no llega a ser una noticia: los libros blancos
    y los seminarios disfrazados de artículo, los resúmenes que meten cinco
    noticias en un titular y lo que no trae ni un párrafo que leer.</p>

    <h2>Qué no vas a encontrar</h2>

    <ul>
      <li>Rondas de financiación de empresas que no vas a contratar nunca.</li>
      <li>Resúmenes de resúmenes.</li>
      <li>La misma noticia contada por cuatro medios distintos: se agrupan y se
      cuenta una vez.</li>
      <li>Publicidad. No la hay.</li>
    </ul>

    <p>Cada bit enlaza a su fuente original. La idea es que leas el bit y, si
    te toca de cerca, vayas a la fuente. Lo contrario sería quedarse con el
    tráfico de otro.</p>

    <h2>Más que el día a día</h2>

    <p>El río de bits es el centro, pero no es todo. <a href="<?= web_e($base) ?>/estadisticas.html">Cifras</a>
    pone a España frente al mundo en adopción de IA, cloud, comercio
    electrónico y ciberseguridad, con fuente y fecha en cada dato.
    <a href="<?= web_e($base) ?>/tendencias.html">Tendencias</a> dice qué tema
    sube y cuál baja este trimestre, comparado con el anterior.
    <a href="<?= web_e($base) ?>/glosario.html">Glosario</a> explica las
    siglas del sector sin salir del sitio -las tres, siempre a mano, bajo
    «Recursos» en el menú de arriba-. Y
    <a href="<?= web_e($base) ?>/medios.html">Medios</a> enseña de dónde sale
    cada noticia y cuánto aporta cada fuente.</p>
  </div>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
