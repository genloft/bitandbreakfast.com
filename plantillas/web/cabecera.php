<?php
/**
 * Cabecera comun de la web publicada.
 *
 * Dos piezas, como en un periodico: una barra de navegacion con el sello a la
 * izquierda, y debajo el nombre ocupando el ancho entero. Todo dentro de
 * filetes, que es como esta maquetado el resto del sitio.
 *
 * El sello es un circulo negro con la inicial. Hubo un dibujo -una taza con
 * vapor de wifi- y se quito: un icono ilustrativo baja de categoria una
 * cabecera que quiere parecer la de una publicacion. Una letra dentro de un
 * circulo no ilustra nada, y por eso funciona.
 *
 * Recibe $base y, opcionalmente, $enlace_activo.
 */

declare(strict_types=1);

$enlace_activo = $enlace_activo ?? '';

?>
<?php require __DIR__ . '/aviso.php'; ?>

<header class="cabecera">
  <div class="cabecera-barra">
    <a class="sello-marca" href="<?= web_e($base) ?>/" aria-label="Bit &amp; Breakfast, portada">B</a>

    <nav class="menu" aria-label="Principal">
      <a href="<?= web_e($base) ?>/"<?= $enlace_activo === 'portada' ? ' aria-current="page"' : '' ?>>Portada</a>
      <a href="<?= web_e($base) ?>/temas.html"<?= $enlace_activo === 'temas' ? ' aria-current="page"' : '' ?>>Temas</a>
      <a href="<?= web_e($base) ?>/medios.html"<?= $enlace_activo === 'medios' ? ' aria-current="page"' : '' ?>>Medios</a>
      <a href="<?= web_e($base) ?>/archivo.html"<?= $enlace_activo === 'archivo' ? ' aria-current="page"' : '' ?>>Archivo</a>
      <a href="<?= web_e($base) ?>/sobre.html"<?= $enlace_activo === 'sobre' ? ' aria-current="page"' : '' ?>>Qué es</a>
      <span class="menu-fin"></span>
      <a href="<?= web_e($base) ?>/buscar.html"<?= $enlace_activo === 'buscar' ? ' aria-current="page"' : '' ?>>Buscar</a>
      <a href="<?= web_e($base) ?>/feed.xml">RSS</a>
    </nav>
  </div>

  <a class="logo" href="<?= web_e($base) ?>/" aria-label="Bit &amp; Breakfast, portada">
    <span class="logo-bloque">Bit<span class="logo-amp">&amp;</span>Breakfast</span>
  </a>

  <div class="cabecera-pie">
    <p class="promesa">Agregador de tecnología hotelera <span class="promesa-punto">·</span> en español</p>
    <p class="promesa">Lo que aparece cada día</p>
  </div>
</header>
