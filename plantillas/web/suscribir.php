<?php
/**
 * Bloque de suscripcion. Recibe $base y $alta_abierta.
 *
 * Va al final de la pagina y no en una ventana emergente, y no interrumpe la
 * lectura: quien ha llegado hasta abajo ya sabe si le interesa. Un radar que
 * tapa la noticia para pedir el correo se contradice a si mismo.
 *
 * Sin JavaScript: es un formulario que envia a /api/suscribir.php, y la
 * pagina de respuesta la pinta ese mismo endpoint.
 */

declare(strict_types=1);

?>
<section class="alta" aria-labelledby="alta-titulo">
  <h2 id="alta-titulo">Recíbelo cada mañana</h2>

  <?php if ($alta_abierta): ?>

    <p>Un correo al día con lo que de verdad ha pasado en tecnología
    hotelera. Sin publirreportajes y sin resúmenes de resúmenes.</p>

    <form class="alta-formulario" method="post" action="<?= web_e($base) ?>/api/suscribir.php">
      <label for="alta-email">Tu correo</label>
      <div class="alta-fila">
        <input id="alta-email" name="email" type="email" inputmode="email"
               autocomplete="email" placeholder="tu@hotel.com" required>
        <button type="submit">Suscribirme</button>
      </div>

      <?php /* Trampa para robots: un campo que nadie ve y que nadie rellena. */ ?>
      <div class="trampa" aria-hidden="true">
        <label for="alta-web">No rellenes esto</label>
        <input id="alta-web" name="web" type="text" tabindex="-1" autocomplete="off">
      </div>

      <p class="letra-pequena">Te llegará un correo para confirmar. Si no lo
      confirmas, no te apuntamos. Puedes darte de baja desde cualquier envío.</p>
    </form>

  <?php else: ?>

    <p>El alta todavía no está abierta. Mientras tanto, el
    <a href="<?= web_e($base) ?>/feed.xml">RSS</a> lleva lo mismo.</p>

  <?php endif; ?>
</section>
