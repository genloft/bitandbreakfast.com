<?php
/**
 * La portada: el rio de lo ultimo, en un solo flujo.
 *
 * Esto sustituye a "la ultima edicion hace de portada", y el cambio es el que
 * mas se nota de todos. Una edicion solo existia cuando se cerraba, asi que
 * todo lo que el radar encontraba hoy estaba invisible hasta mañana, y la
 * portada llevaba siempre un dia de retraso sobre lo que el sistema sabia.
 * Aqui una noticia esta publicada en cuanto esta escrita.
 *
 * Por eso manda el dia de descubrimiento y no el de publicacion del medio: lo
 * segundo lo sabe cualquiera, lo primero es lo unico que este sitio puede
 * contar de verdad. "Esto es lo que ha aparecido hoy en el radar" es una
 * promesa que se puede cumplir; "esto es todo lo que se ha publicado hoy en el
 * mundo" no.
 *
 * $rio llega partido por dias -asi lo necesitan el feed y la comprobacion de
 * "cuantos dias hay"-, pero aqui se aplana: una caja de dia por cada tramo
 * volvia a parecer una edicion, justo lo que este sitio dejo de ser. Cada
 * noticia lleva su fecha encima -"Hoy", "Ayer" o el dia completo, via
 * web_dia_titulo()- para no perder esa informacion al quitar la cabecera que
 * antes la llevaba una sola vez por grupo.
 *
 * Recibe $rio -tramos con 'dia' y 'bits'-, $dias, $fuentes, $mas_leidos,
 * $mas_votados, $tendencias y $base.
 *
 * HTML estatico: ni script, ni estilo en linea, ni una peticion a terceros.
 * La politica de seguridad del sitio es 'self' y esta pagina es la razon de
 * que pueda serlo.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';
$rio        = $rio ?? [];
$dias       = $dias ?? [];
$fuentes    = $fuentes ?? [];
$temas      = $temas ?? [];
$medios     = $medios ?? [];
$mas_leidos = $mas_leidos ?? [];
$mas_votados = $mas_votados ?? [];
$tendencias = $tendencias ?? [];
$secreto    = $secreto ?? '';
$alta_abierta = $alta_abierta ?? false;
$mapa       = $mapa ?? ['nodes' => []];

// Los dos destacados de arriba no son un menu, son un titular: una cifra
// grande y una linea de que va. La de Tendencias sale sola del mismo dato
// que ya calcula publicar_pendiente() para tendencias.html -el movimiento
// mas grande del trimestre-, asi que cambia cuando cambia el trimestre. La
// de Cifras es fija a proposito -esa pagina la revisa una persona, no el
// radar-, pero el numero mismo sale de cifras_valor_ia_espana(), la misma
// funcion que usa el primer grupo de estadisticas.php: citarlo aqui a mano,
// por su cuenta, habria dejado que este titular seguiera diciendo un dato
// que la propia pagina de Cifras ya hubiera dejado atras.
$cifra_cifras = cifras_valor_ia_espana() . ' %';
$pie_cifras   = 'España ya supera la media de la UE en adopción de IA';

$tendencia_top = $tendencias[0] ?? null;

if ($tendencia_top === null) {
    $cifra_tendencias = '?';
    $pie_tendencias   = 'Qué sube y qué baja este trimestre, tema a tema';
} elseif ($tendencia_top['nuevo']) {
    $cifra_tendencias = 'NUEVO';
    $pie_tendencias   = $tendencia_top['nombre'] . ', que no existía hace un trimestre';
} elseif ($tendencia_top['porcentaje'] !== null) {
    $cifra_tendencias = ($tendencia_top['delta'] > 0 ? '+' : '−') . abs($tendencia_top['porcentaje']) . ' %';
    $pie_tendencias   = $tendencia_top['nombre'] . ', este trimestre';
} else {
    $cifra_tendencias = $tendencia_top['delta'] > 0 ? '▲' : '▼';
    $pie_tendencias   = $tendencia_top['nombre'] . ', este trimestre';
}

$categorias = bits_categorias();
$ambitos    = web_ambitos();
$idiomas    = web_idiomas();

$total = 0;
foreach ($rio as $tramo) {
    $total += count($tramo['bits']);
}

$descripcion = sprintf(
    'Lo último en tecnología hotelera: %d noticias de %d días, con enlace a la fuente.',
    $total,
    count($rio)
);

$enlace_activo = 'portada';

require_once __DIR__ . '/iconos.php';

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bit &amp; Breakfast · Tecnología hotelera, en español</title>
<meta name="description" content="<?= web_e($descripcion) ?>">
<link rel="canonical" href="<?= web_e($base) ?>/">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="icon" href="<?= web_e($base) ?>/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version) ?>">
<meta name="theme-color" content="#f4f2ee">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="Bit &amp; Breakfast">
<meta property="og:description" content="<?= web_e($descripcion) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= web_e($base) ?>/">
<meta property="og:locale" content="es_ES">
<meta property="og:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido">

  <?php // El mapa del stack va aqui y no mas abajo por una razon y su
        // contraria a la vez: tiene que verse lo primero -es lo unico de esta
        // portada que dice algo del conjunto y no de una noticia suelta- y no
        // puede quedarse con la pagina. Se resuelve por densidad: veinticuatro
        // casillas en una franja de dos dedos. Quien viene a leer titulares
        // los tiene a un golpe de rueda, y ha visto de paso que esta semana lo
        // que esta en rojo son los pagos. Si no hay ni un nodo encendido, la
        // banda no se pinta sola: abrir el sitio con una rejilla apagada es
        // peor que abrirlo sin mapa. ?>
  <?php require __DIR__ . '/mapa_banda.php'; ?>

  <article class="edicion">

    <?php
      // Aplanado a proposito: vease el comentario de arriba. El orden ya
      // venia por dia y, dentro de el, del mas relevante al menos -es el
      // mismo que traia publicar_bits()-, asi que aplanar no reordena nada.
      $bits_planos = [];
      foreach ($rio as $tramo) {
          foreach ($tramo['bits'] as $bit) {
              $bits_planos[] = $bit;
          }
      }
    ?>

    <div class="bits">
      <?php foreach ($bits_planos as $indice => $bit): ?>
        <?php require __DIR__ . '/bit.php'; ?>
      <?php endforeach; ?>
    </div>

    <?php if (!$bits_planos): ?>
      <p class="vacio">Todavía no hay nada publicado. El radar está leyendo.</p>
    <?php endif; ?>

    <?php if (count($dias) > count($rio)): ?>
      <p class="mas-dias">
        <a href="<?= web_e($base) ?>/archivo.html">Los <?= count($dias) ?> días anteriores &rarr;</a>
      </p>
    <?php endif; ?>

  </article>

  <?php // Tres cifras del sector, para el que ha terminado de leer. Van aqui
        // abajo y no arriba: el mapa ya ocupa la cabecera, y dos bloques de
        // contexto por delante del primer titular convierten un agregador de
        // noticias en un cuadro de mandos con noticias al fondo. ?>
  <?php require __DIR__ . '/cifras_tira.php'; ?>

  <?php // El voto pesa mas que el clic -es la opinion de quien ya ha leido
        // el bit entero, no solo el titular-, por eso va primero. ?>
  <?php require __DIR__ . '/mas_votado.php'; ?>

  <?php require __DIR__ . '/mas_leido.php'; ?>

  <?php require __DIR__ . '/explorar.php'; ?>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
