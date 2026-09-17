<?php
/**
 * Cabecera comun de la web publicada.
 *
 * El logotipo es tipografico y nada mas: un bloque rectangular de dos lineas
 * con el nombre. Hubo un dibujo -una taza con vapor de wifi- y se quito: la
 * broma se entendia, pero un icono ilustrativo baja de categoria una cabecera
 * que quiere parecer la de una publicacion y no la de una aplicacion.
 *
 * El rectangulo no sale de un borde: sale de que la primera linea se completa
 * con un filete hasta la anchura de la segunda. Asi el bloque es un rectangulo
 * de verdad -las dos lineas miden lo mismo- sin tener que forzar el
 * interletrado de "Bit &" para que cuadre con "Breakfast", que es lo que hace
 * que un logotipo parezca estirado.
 *
 * Recibe $base y, opcionalmente, $enlace_activo.
 */

declare(strict_types=1);

$enlace_activo = $enlace_activo ?? '';

?>
<header class="cabecera">
  <div class="cabecera-interior">
    <nav class="menu" aria-label="Principal">
      <a href="<?= web_e($base) ?>/"<?= $enlace_activo === 'portada' ? ' aria-current="page"' : '' ?>>Última edición</a>
      <a href="<?= web_e($base) ?>/archivo.html"<?= $enlace_activo === 'archivo' ? ' aria-current="page"' : '' ?>>Archivo</a>
      <a href="<?= web_e($base) ?>/buscar.html"<?= $enlace_activo === 'buscar' ? ' aria-current="page"' : '' ?>>Buscar</a>
      <a href="<?= web_e($base) ?>/temas.html"<?= $enlace_activo === 'temas' ? ' aria-current="page"' : '' ?>>Temas</a>
      <a href="<?= web_e($base) ?>/medios.html"<?= $enlace_activo === 'medios' ? ' aria-current="page"' : '' ?>>Medios</a>
      <a href="<?= web_e($base) ?>/sobre.html"<?= $enlace_activo === 'sobre' ? ' aria-current="page"' : '' ?>>Qué es</a>
      <a href="<?= web_e($base) ?>/feed.xml">RSS</a>
    </nav>

    <a class="logo" href="<?= web_e($base) ?>/" aria-label="Bit &amp; Breakfast, portada">
      <span class="logo-bloque" aria-hidden="true">
        <span class="logo-linea">
          <span class="logo-palabra">Bit</span>
          <span class="logo-amp">&amp;</span>
          <span class="logo-filete"></span>
        </span>
        <span class="logo-linea logo-linea-baja">
          <span class="logo-palabra">Breakfast</span>
        </span>
      </span>
    </a>

  </div>

  <p class="promesa">Radar de tecnología hotelera <span class="promesa-punto">·</span> en español <span class="promesa-punto">·</span> cinco minutos a la semana</p>
</header>
