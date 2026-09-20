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
    <a href="<?= web_e($base) ?>/cumplimiento.html">Cumplimiento</a>
    <a href="<?= web_e($base) ?>/tendencias.html">Tendencias</a>
    <a href="<?= web_e($base) ?>/glosario.html">Glosario</a>
    <a href="<?= web_e($base) ?>/agentica.html">Reserva agéntica</a>
    <a href="<?= web_e($base) ?>/calendario.html">Calendario</a>
    <a href="<?= web_e($base) ?>/sobre.html">Qué es</a>
    <a href="<?= web_e($base) ?>/legal.html">Aviso legal</a>
    <a href="<?= web_e($base) ?>/feed.xml">RSS</a>
  </nav>

  <div class="flotante-newsletter">
    <form method="post" action="<?= web_e($base) ?>/api/suscribir.php">
      <label for="flotante-email"><strong>Suscríbete a la newsletter</strong></label>
      <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
        <input id="flotante-email" name="email" type="email" placeholder="Tu correo electrónico" required>
        <button type="submit">Alta</button>
      </div>
    </form>
  </div>

  <p class="letra-pequena">Bit &amp; Breakfast es un radar, no un agregador:
  filtra duro y enseña poco. Cada bit enlaza a su fuente original, y ninguno
  se publica sin pasar el mismo filtro de puntuación y comprobación -a mano
  o en modo automático, como explica <a href="<?= web_e($base) ?>/sobre.html">Qué es</a>-.</p>
</footer>
<script src="<?= web_e($base) ?>/dinamico.js?v=<?= web_e($version_js ?? '0') ?>" defer></script>
