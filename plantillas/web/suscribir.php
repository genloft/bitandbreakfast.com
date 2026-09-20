<?php
/**
 * Bloque de suscripcion. Recibe $base, $alta_abierta y $alta_temas.
 *
 * Va al final de la pagina y no en una ventana emergente, y no interrumpe la
 * lectura: quien ha llegado hasta abajo ya sabe si le interesa. Un radar que
 * tapa la noticia para pedir el correo se contradice a si mismo.
 *
 * Sin JavaScript: es un formulario que envia a /api/suscribir.php, y la
 * pagina de respuesta la pinta ese mismo endpoint.
 *
 * El selector de temas -$alta_temas- solo aparece con el buzon propio:
 * MailerLite y Brevo llevan su propia lista, ajena a la columna 'temas' de
 * suscriptores, y enseñar el selector igual seria prometer un filtro que no
 * hace nada.
 */

declare(strict_types=1);

$alta_temas = $alta_temas ?? false;

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

      <?php if ($alta_temas): ?>
        <?php // Colapsado por defecto: elegir es una opcion, no un requisito,
              // y la mayoria no necesita tocar esto para suscribirse. Sin
              // marcar nada se recibe todo, exactamente como hasta ahora. ?>
        <details class="alta-temas">
          <summary>Elegir temas (opcional)</summary>
          <p class="letra-pequena">Sin marcar nada, recibes todos los temas cada día. Marca solo los que te interesan.</p>
          <div class="alta-temas-lista">
            <?php foreach (bits_categorias() as $slug => $nombre): ?>
              <label class="alta-tema">
                <input type="checkbox" name="temas[]" value="<?= web_e($slug) ?>">
                <?= web_e($nombre) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </details>

        <details class="alta-temas">
          <summary>Avisarme de palabras o proveedores (opcional)</summary>
          <p class="letra-pequena">«Mews, ransomware» te avisa solo de los bits que los mencionen. Sin escribir nada, no se filtra por esto.</p>
          <label for="alta-alerta">Palabras o proveedores, separados por comas</label>
          <div class="alta-fila">
            <input id="alta-alerta" name="alerta" type="text" placeholder="Mews, ransomware">
          </div>
        </details>
      <?php endif; ?>

      <?php /* Trampa para robots: un campo que nadie ve y que nadie rellena. */ ?>
      <div class="trampa" aria-hidden="true">
        <label for="alta-web">No rellenes esto</label>
        <input id="alta-web" name="web" type="text" tabindex="-1" autocomplete="off">
      </div>

      <p class="letra-pequena">Te llegará un correo para confirmar. Si no lo
      confirmas, no te apuntamos. Puedes darte de baja desde cualquier envío.
      Más sobre qué se hace con tu correo en el
      <a href="<?= web_e($base) ?>/legal.html">aviso legal</a>.</p>
    </form>

  <?php else: ?>

    <p>El alta todavía no está abierta. Mientras tanto, el
    <a href="<?= web_e($base) ?>/feed.xml">RSS</a> lleva lo mismo.</p>

  <?php endif; ?>
</section>
