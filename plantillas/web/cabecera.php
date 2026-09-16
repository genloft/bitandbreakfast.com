<?php
/**
 * Cabecera comun de la web publicada.
 *
 * El logotipo va arriba a la derecha y grande, en texto: es una marca
 * tipografica, no una imagen. Asi escala solo en cualquier pantalla, pesa
 * cero, se lee con el lector de pantalla y no hay que mantener cuatro PNG.
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
      <a href="<?= web_e($base) ?>/feed.xml">RSS</a>
    </nav>

    <a class="logo" href="<?= web_e($base) ?>/">
      <span class="logo-uno">Bit</span><span class="logo-amp">&amp;</span><span class="logo-dos">Breakfast</span>
    </a>
  </div>

  <p class="promesa">Radar de tecnología hotelera. Cinco minutos de lectura a la semana.</p>
</header>
