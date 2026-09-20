<?php
/**
 * Lo mas util de los ultimos siete dias, segun quien lo ha leido en el
 * correo. Recibe $mas_votados, $base y $secreto.
 *
 * Mismo hueco que "Lo más leído" -y, a proposito, con el mismo marcado:
 * .masleido, .masleido-lista, .masleido-numero, .masleido-cuerpo se
 * reutilizan enteros, sin CSS nueva, porque la forma es identica: un
 * numero, un titular que enlaza y el nombre de la fuente debajo-. La
 * diferencia esta en la señal, no en el aspecto: un clic dice "esto llamo
 * la atencion", un voto dice "esto sirvio de verdad" -es la opinion de
 * quien ya lo ha leido entero, no solo el titular-, y por eso va primero.
 *
 * La tabla `votos` lleva en el esquema desde la fase 6 sin que nada la
 * enseñara: se recoge un "¿te ha servido esto?" por cada bit de cada
 * correo, y hasta ahora no se veia en ningun sitio.
 *
 * Sin votos de sobra, publicar_mas_votados() devuelve la lista vacia y
 * esta seccion no se pinta: misma regla que "Lo más leído", nunca un
 * ranking con una sola entrada.
 */

declare(strict_types=1);

$mas_votados = $mas_votados ?? [];
$base        = $base ?? '';
$secreto     = $secreto ?? '';

if (!$mas_votados) {
    return;
}

$categorias = bits_categorias();

?>
<section class="masleido" aria-labelledby="masvotado-titulo">
  <h2 id="masvotado-titulo"><span class="pulso" aria-hidden="true"></span>Lo más útil esta semana</h2>

  <ol class="masleido-lista">
    <?php foreach ($mas_votados as $indice => $bit): ?>
      <?php
        // target="_blank": mismo motivo que en mas_leido.php y bit.php, este
        // enlace lleva al original y no debe cerrar la portada.
        $tema   = bits_categoria_canonica((string) $bit['categoria']) ?: 'tecnologia-general';
        $enlace = !empty($bit['url'])
            ? web_url_clic($base, (int) $bit['id'], $secreto, (string) $bit['url'])
            : '';
      ?>
      <li data-tema="<?= web_e($tema) ?>">
        <span class="masleido-numero"><?= str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) ?></span>
        <span class="masleido-cuerpo">
          <?php if ($enlace !== ''): ?>
            <a href="<?= web_e($enlace) ?>" rel="nofollow noopener" target="_blank"><?= web_e((string) $bit['titular']) ?></a>
          <?php else: ?>
            <?= web_e((string) $bit['titular']) ?>
          <?php endif; ?>
          <?php if (!empty($bit['fuente'])): ?>
            <span class="datos"><?= web_e((string) $bit['fuente']) ?></span>
          <?php endif; ?>
        </span>
      </li>
    <?php endforeach; ?>
  </ol>
</section>
