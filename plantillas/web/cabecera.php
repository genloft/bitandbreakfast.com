<?php
/**
 * Cabecera comun de la web publicada.
 *
 * El logotipo son dos piezas: una marca dibujada y el nombre en tipografia.
 *
 * La marca es una taza cuyo vapor son las ondas de una senal. Es la broma que
 * ya estaba en el nombre -bit y desayuno- contada en un dibujo, y funciona a
 * treinta pixeles, que es donde se ve de verdad un logotipo. Va en SVG en
 * linea: escala sola, pesa lo que ocupa, hereda el color del texto y no hay
 * cuatro PNG que mantener.
 *
 * El nombre sigue siendo tipografico y sigue mandando: la marca acompana, no
 * sustituye. Arriba a la derecha, grande, con el ampersand en cursiva y en
 * laton, que es el unico adorno de toda la casa.
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
      <a href="<?= web_e($base) ?>/proveedores.html"<?= $enlace_activo === 'proveedores' ? ' aria-current="page"' : '' ?>>Proveedores</a>
      <a href="<?= web_e($base) ?>/sobre.html"<?= $enlace_activo === 'sobre' ? ' aria-current="page"' : '' ?>>Qué es</a>
      <a href="<?= web_e($base) ?>/feed.xml">RSS</a>
    </nav>

    <a class="logo" href="<?= web_e($base) ?>/" aria-label="Bit &amp; Breakfast, portada">
      <svg class="marca" viewBox="0 0 48 48" fill="none" stroke="currentColor"
           stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"
           aria-hidden="true" focusable="false">
        <?php /* El vapor: tres ondas que salen del borde de la taza. */ ?>
        <path class="marca-onda" d="M19.8 21.8a6 6 0 0 1 8.4 0"/>
        <path class="marca-onda" d="M16.9 18.9a10 10 0 0 1 14.2 0"/>
        <path class="marca-onda" d="M14.1 16.1a14 14 0 0 1 19.8 0"/>
        <?php /* La taza, el asa y el plato. */ ?>
        <path class="marca-taza" d="M12 26h21v4a9 9 0 0 1-9 9h-3a9 9 0 0 1-9-9v-4z"/>
        <path class="marca-taza" d="M33 28.5a4.5 4.5 0 0 1 0 8"/>
        <path class="marca-taza" d="M9 42h30"/>
      </svg>

      <span class="logo-texto">
        <span class="logo-uno">Bit</span><span class="logo-amp">&amp;</span><span class="logo-dos">Breakfast</span>
      </span>
    </a>
  </div>

  <p class="promesa">Radar de tecnología hotelera <span class="promesa-punto">·</span> en español <span class="promesa-punto">·</span> cinco minutos a la semana</p>
</header>
