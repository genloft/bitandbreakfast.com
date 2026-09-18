<?php
/**
 * Pie comun de la web publicada. Recibe $base.
 */

declare(strict_types=1);

?>
<footer class="pie">
  <nav class="menu" aria-label="Pie">
    <a href="<?= web_e($base) ?>/">Lo último</a>
    <a href="<?= web_e($base) ?>/archivo.html">Archivo</a>
    <a href="<?= web_e($base) ?>/buscar.html">Buscar</a>
    <a href="<?= web_e($base) ?>/temas.html">Temas</a>
    <a href="<?= web_e($base) ?>/medios.html">Medios</a>
    <a href="<?= web_e($base) ?>/estadisticas.html">Cifras</a>
    <a href="<?= web_e($base) ?>/tendencias.html">Tendencias</a>
    <a href="<?= web_e($base) ?>/sobre.html">Qué es</a>
    <a href="<?= web_e($base) ?>/feed.xml">RSS</a>
  </nav>

  <p class="letra-pequena">Bit &amp; Breakfast es un radar, no un agregador:
  filtra duro y enseña poco. Cada bit enlaza a su fuente original, y ninguno
  se publica sin que lo haya leído una persona.</p>
</footer>
