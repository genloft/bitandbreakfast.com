<?php
/**
 * Tres cifras del sector, en una tira, al pie del rio.
 *
 * Cifras es de las mejores paginas del sitio y era la menos visitada, porque
 * su unica puerta era un icono en la cabecera: nadie pulsa un icono de una
 * pagina que no sabe que existe. Tres numeros grandes con su titular si se
 * leen de paso, y el que le interese uno ya sabe que hay mas detras.
 *
 * Va **despues** de las noticias y no antes. Arriba ya esta el mapa, y dos
 * bloques de contexto por delante del primer titular convertirian un agregador
 * de noticias en un cuadro de mandos con noticias al fondo, que es otro
 * producto. Aqui abajo recoge al que ha terminado de leer, que es justo cuando
 * un dato de fondo se agradece.
 *
 * Los valores no se escriben aqui: salen de cifras_destacadas(), que los lee
 * del mismo sitio que pinta /estadisticas.html. Copiarlos habria dejado la
 * portada diciendo un numero que la propia pagina de Cifras ya hubiera
 * corregido, que es exactamente el fallo que este sitio no se puede permitir.
 *
 * Recibe $base.
 */

declare(strict_types=1);

$destacadas = cifras_destacadas();

if (!$destacadas) {
    return;
}

?>
<section class="cifras-tira" aria-labelledby="cifras-tira-titulo">
  <header class="cifras-tira-cabeza">
    <p class="sello">Las cifras</p>
    <h2 id="cifras-tira-titulo">De qué tamaño es todo esto</h2>
  </header>

  <ul class="cifras-tira-lista">
    <?php foreach ($destacadas as $cifra): ?>
      <li>
        <p class="cifras-tira-valor"><?= web_e($cifra['valor']) ?></p>
        <p class="cifras-tira-rotulo"><?= web_e($cifra['rotulo']) ?></p>
        <p class="cifras-tira-tema"><?= web_e($cifra['tema']) ?></p>
      </li>
    <?php endforeach; ?>
  </ul>

  <p class="cifras-tira-mas">
    <a href="<?= web_e($base) ?>/estadisticas.html">Las <?= count(cifras_grupos()) ?> series completas, con su fuente y su fecha &rarr;</a>
  </p>
</section>
