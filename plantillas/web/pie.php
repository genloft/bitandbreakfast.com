<?php
/**
 * Pie comun de la web publicada. Recibe $base.
 *
 * No lleva su propio formulario de alta: cada pagina ya incluye
 * suscribir.php, cuyo propio docblock explica por que -al final de la
 * pagina, nunca en una ventana emergente- es la unica forma de pedir el
 * correo que este sitio quiere usar. Hubo un widget flotante fijo en la
 * esquina, con su propio formulario suelto a /api/suscribir.php -sin la
 * trampa para robots ni el selector de temas del formulario real-, y
 * contradecia ese principio en la primera pagina que se abriera. Se quita
 * en vez de arreglarse, porque no aportaba nada que suscribir.php no
 * ofreciera ya mejor.
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

  <p class="letra-pequena">Bit &amp; Breakfast es un radar, no un agregador:
  filtra duro y enseña poco. Cada bit enlaza a su fuente original, y ninguno
  se publica sin pasar el mismo filtro de puntuación y comprobación -a mano
  o en modo automático, como explica <a href="<?= web_e($base) ?>/sobre.html">Qué es</a>-.</p>
</footer>
<script src="<?= web_e($base) ?>/dinamico.js?v=<?= web_e($version_js ?? '0') ?>" defer></script>
