<?php
/**
 * Que es Bit & Breakfast. Recibe $base y $alta_abierta.
 *
 * Una pagina, no un manifiesto. Quien llega desde un enlace suelto necesita
 * saber en veinte segundos que es esto, quien lo hace y por que deberia
 * fiarse. Todo lo demas sobra.
 */

declare(strict_types=1);

$enlace_activo = 'sobre';
$alta_abierta  = $alta_abierta ?? false;

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Qué es · Bit &amp; Breakfast</title>
<meta name="description" content="Bit &amp; Breakfast es un radar de tecnología hotelera: rastrea el sector, agrupa lo que cuenta lo mismo y publica quince o veinte bits a la semana.">
<link rel="canonical" href="<?= web_e($base) ?>/sobre.html">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css">
<meta name="theme-color" content="#f6f5f2">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="Qué es Bit &amp; Breakfast">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= web_e($base) ?>/sobre.html">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido">
  <header class="edicion-cabecera">
    <p class="sello">Qué es esto</p>
    <h1>Un radar, no un agregador</h1>
    <p class="datos">Cinco minutos de lectura a la semana</p>
  </header>

  <div class="texto pagina">
    <p>Bit &amp; Breakfast rastrea cada hora medio centenar largo de fuentes de
    tecnología hotelera: prensa del sector, páginas de estado de los
    proveedores, registros de cambios, boletines oficiales y rondas de
    financiación. Agrupa las noticias que cuentan lo mismo, las puntúa y deja
    una cola de candidatos.</p>

    <p>De esa cola salen cada semana entre quince y veinte <strong>bits</strong>.
    Los elige y los escribe una persona. Ninguno se publica sin que alguien lo
    haya leído entero y haya decidido que merece tu tiempo.</p>

    <h2>Qué es un bit</h2>

    <p>Un titular, un párrafo corto y una línea que se llama <em>por qué
    importa</em>: qué cambia esto para un hotel. Si esa línea no se puede
    escribir, la noticia no entra. Es el filtro que separa un radar de una
    lista de enlaces.</p>

    <h2>Cómo se decide qué entra</h2>

    <p>Cuenta el peso de la fuente, lo reciente que sea, si menciona
    proveedores del catálogo y si varias fuentes independientes cuentan lo
    mismo, que es la mejor señal de que algo importa de verdad. Y resta: hay un
    filtro específico contra el publirreportaje, porque medio sector vive de
    publicar notas de prensa como si fueran noticias.</p>

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
  </div>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
