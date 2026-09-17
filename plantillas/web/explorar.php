<?php
/**
 * "Seguir tirando del hilo": los temas y los medios, al pie de la pagina.
 *
 * Al final y no arriba: quien ha llegado hasta aqui ya ha leido lo que venia a
 * leer y lo siguiente que quiere es tirar del hilo. Arriba seria un menu que
 * estorba; aqui es una puerta.
 *
 * Estaba copiado en la portada y en cada ficha. Ahora vive en un sitio.
 *
 * Recibe $temas, $medios y $base.
 */

declare(strict_types=1);

$temas  = $temas ?? [];
$medios = $medios ?? [];

?>
  <?php if ($temas || $medios): ?>
    <section class="explorar" aria-labelledby="explorar-titulo">
      <h2 id="explorar-titulo">Seguir tirando del hilo</h2>

      <?php if ($temas): ?>
        <h3 class="explorar-grupo">Por tema</h3>
        <ul class="nube nube-temas">
        <?php foreach ($temas as $tema): ?>
          <li data-tema="<?= web_e($tema['slug']) ?>">
            <a href="<?= web_e(web_url_tema($base, (string) $tema['slug'])) ?>">
              <?= web_icono((string) $tema['slug'], 'icono icono-mini') ?>
              <span class="nube-nombre"><?= web_e($tema['nombre']) ?></span>
              <span class="nube-cuenta"><?= (int) $tema['bits'] ?></span>
            </a>
          </li>
        <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if ($medios): ?>
        <h3 class="explorar-grupo">Medios que hemos leído</h3>
        <ul class="nube nube-medios">
        <?php foreach ($medios as $medio): ?>
          <li>
            <a href="<?= web_e(web_url_medio($base, (string) $medio['slug'])) ?>">
              <span class="nube-nombre"><?= web_e($medio['nombre']) ?></span>
              <span class="nube-cuenta"><?= (int) $medio['bits'] ?></span>
            </a>
          </li>
        <?php endforeach; ?>
        </ul>

        <p class="letra-pequena explorar-pie">
          Son los medios de los que ha salido algo publicado. El radar rastrea
          bastantes más: los que no aparecen aquí es que esta temporada no han
          contado nada que pasara el filtro.
        </p>
      <?php endif; ?>
    </section>
  <?php endif; ?>
