<?php
/**
 * La banda del mapa en la portada.
 *
 * Va arriba, entre la cabecera y el rio de noticias. La tension de esta pieza
 * es toda suya: tiene que verse lo primero y no puede robarle el sitio a las
 * noticias. Se resuelve con la forma del lienzo, que es casi 3:1 -una franja
 * ancha y baja-, y con un tope de ancho para que en una pantalla grande no
 * crezca a lo alto sin freno. Medido: ocupa poco mas de media pantalla, y el
 * primer titular asoma por debajo en movil y en escritorio.
 *
 * Lo que aqui se enseña es el estado, no las noticias. Quien quiera el detalle
 * pulsa un nodo y se va a /mapa.html; quien solo pasaba, ha visto en dos
 * segundos que esta semana lo que esta rojo son los pagos y a que arrastran, y
 * sigue bajando a leer titulares, que es a lo que venia.
 *
 * Si no hay ni un nodo encendido, la banda no se pinta. Un mapa entero en gris
 * con una nota explicando que no ha pasado nada es peor que no tener mapa: la
 * primera impresion del sitio seria un dibujo apagado.
 *
 * Recibe $mapa y $base.
 */

declare(strict_types=1);

$mapa = $mapa ?? ['nodes' => []];

if (!($mapa['nodes'] ?? [])) {
    return;
}

$resumen = heatmap_resumen($mapa);

$mapa_variante = 'banda';

?>
<section class="mapa-banda" aria-labelledby="mapa-banda-titulo">

  <header class="mapa-banda-cabeza">
    <div class="mapa-banda-rotulo">
      <p class="sello">El mapa del stack</p>
      <h2 id="mapa-banda-titulo">Qué parte de tu sistema está caliente</h2>
    </div>

    <p class="mapa-banda-cifras">
      <span class="mapa-banda-cifra"><strong><?= (int) $resumen['encendidos'] ?></strong> de 24 nodos con novedades</span>
      <?php if ($resumen['altos'] > 0): ?>
        <span class="mapa-banda-cifra mapa-banda-cifra--alta"><strong><?= (int) $resumen['altos'] ?></strong> pide<?= $resumen['altos'] === 1 ? '' : 'n' ?> decisión</span>
      <?php endif; ?>
      <?php if (!empty($mapa['stale'])): ?>
        <span class="mapa-banda-cifra mapa-banda-cifra--vieja">Sin novedades desde hace más de una semana</span>
      <?php endif; ?>
    </p>
  </header>

  <?php require __DIR__ . '/mapa_grafo.php'; ?>

  <footer class="mapa-banda-pie">
    <p class="mapa-leyenda">
      <span class="mapa-leyenda-item"><?= web_icono_senal('riesgo', 'mapa-senal-icono mapa-leyenda-icono') ?> Riesgo</span>
      <span class="mapa-leyenda-item"><?= web_icono_senal('oportunidad', 'mapa-senal-icono mapa-leyenda-icono') ?> Oportunidad</span>
      <span class="mapa-leyenda-item">Punto más grande = pide decisión</span>
    </p>

    <p class="mapa-banda-mas">
      <a href="<?= web_e($base) ?>/mapa.html">Ver el mapa entero, con las noticias de cada nodo &rarr;</a>
    </p>
  </footer>

</section>
