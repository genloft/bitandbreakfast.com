<?php
/**
 * Caja de alta compacta para el principio de la portada. Recibe $base y
 * $alta_abierta.
 *
 * Una sola fila -frase, correo y boton- para que no se coma la pantalla ni
 * empuje el mapa y los titulares: quien viene a leer los tiene debajo sin
 * bajar. Manda al mismo /api/suscribir.php que el bloque del pie y el aviso
 * flotante, con la misma trampa para robots; sin JavaScript funciona igual.
 */

declare(strict_types=1);

$alta_abierta = $alta_abierta ?? false;

if (!$alta_abierta) {
    return;
}

?>
<section class="alta alta-inicial" aria-labelledby="alta-inicial-titulo">
  <h2 id="alta-inicial-titulo">Recíbelo cada mañana</h2>
  <form class="alta-formulario" method="post" action="<?= web_e($base) ?>/api/suscribir.php">
    <label for="alta-inicial-email">Tu correo</label>
    <div class="alta-fila">
      <input id="alta-inicial-email" name="email" type="email" inputmode="email"
             autocomplete="email" placeholder="tu@hotel.com" required>
      <button type="submit">Suscribirme</button>
    </div>

    <?php /* Trampa para robots: un campo que nadie ve y que nadie rellena. */ ?>
    <div class="trampa" aria-hidden="true">
      <label for="alta-inicial-web">No rellenes esto</label>
      <input id="alta-inicial-web" name="web" type="text" tabindex="-1" autocomplete="off">
    </div>

    <p class="letra-pequena">Un correo al día con lo que de verdad ha pasado en
    tecnología hotelera. Te pedimos confirmar el alta y puedes darte de baja
    cuando quieras.</p>
  </form>
</section>
