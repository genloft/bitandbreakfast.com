<?php
/**
 * Pie comun de la web publicada. Recibe $base y $alta_abierta.
 *
 * El alta de siempre -suscribir.php, al final de cada pagina- sigue siendo
 * la unica forma completa: con su selector de temas cuando toca y su
 * trampa para robots. El aviso flotante de aqui abajo no la sustituye, la
 * recuerda: aparece tarde -tras un poco de scroll o quince segundos, lo
 * que llegue antes- para no tapar lo primero que se lee, lleva su propio
 * boton de cerrar que no vuelve a preguntar en esa visita, y usa las
 * mismas clases .alta-formulario/.alta-fila/.trampa que el formulario de
 * verdad -mismo honeypot, nada que un robot pueda coincidir. Solo se
 * pinta si $alta_abierta: un aviso que empuja hacia un formulario cerrado
 * es peor que no llevar aviso.
 *
 * Hubo una version anterior -fija desde el primer pixel, sin cerrar, con
 * su propio formulario suelto sin trampa para robots- que contradecia el
 * principio de suscribir.php y se quito entera en vez de arreglarse. Esta
 * es la reescritura, no una reversion de aquella.
 */

declare(strict_types=1);

$alta_abierta = $alta_abierta ?? false;

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

<?php if ($alta_abierta): ?>
  <div class="flotante-alta" id="flotante-alta" hidden>
    <button type="button" class="flotante-alta-cerrar" aria-label="Cerrar este aviso">&times;</button>
    <p class="flotante-alta-titulo">Recíbelo cada mañana</p>
    <form class="alta-formulario" method="post" action="<?= web_e($base) ?>/api/suscribir.php">
      <label for="flotante-email">Tu correo</label>
      <div class="alta-fila">
        <input id="flotante-email" name="email" type="email" inputmode="email"
               autocomplete="email" placeholder="tu@hotel.com" required>
        <button type="submit">Suscribirme</button>
      </div>

      <?php /* Trampa para robots: un campo que nadie ve y que nadie rellena. */ ?>
      <div class="trampa" aria-hidden="true">
        <label for="flotante-web">No rellenes esto</label>
        <input id="flotante-web" name="web" type="text" tabindex="-1" autocomplete="off">
      </div>
    </form>
  </div>
<?php endif; ?>

<script src="<?= web_e($base) ?>/dinamico.js?v=<?= web_e($version_js ?? '0') ?>" defer></script>
<?php if ($alta_abierta): ?>
  <script src="<?= web_e($base) ?>/flotante.js?v=<?= web_e($version_js ?? '0') ?>" defer></script>
<?php endif; ?>
