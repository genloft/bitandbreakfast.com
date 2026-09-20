<?php
/**
 * El panel de cifras, a la derecha del logo.
 *
 * Contesta de un vistazo las dos preguntas de quien vuelve a un agregador:
 * ¿esto sigue vivo? y ¿ha crecido desde la ultima vez? Por eso van juntos los
 * dos relojes -cuando fue, cuando sera- y las tres parejas de cuentas.
 *
 * Cada pareja es lo mismo dicho dos veces a distinta escala: lo que ha entrado
 * desde la generacion anterior y lo que hay en total. El numero pequeño en
 * rojo es el que dice si merece la pena volver a mirar; el grande, el tamaño
 * de lo que hay detras.
 *
 * Lo de "siguiente" es una estimacion y no una promesa: sale de la cadencia
 * que el cron mide al pasar. Se dice con un "~" delante por eso mismo, porque
 * dar una hora exacta que puede no cumplirse es peor que no darla.
 *
 * Es una foto del momento en que se genero la pagina. El sitio es HTML
 * estatico y esa es justamente la razon de que aguante. El punto que
 * pulsa junto a "Actualizado" no lo desmiente -sigue siendo una foto-, solo
 * dice que detras hay un radar que no se ha parado, no un volcado suelto.
 *
 * Recibe $panel.
 */

declare(strict_types=1);

$panel = $panel ?? [];

$cuando    = web_fecha_hora((string) ($panel['cuando'] ?? ''));
$siguiente = (string) ($panel['siguiente'] ?? '');

if ($cuando === '') {
    return;
}

$filas = [
    'Noticias' => $panel['noticias'] ?? ['nuevas' => 0, 'total' => 0],
    'Medios'   => $panel['medios']   ?? ['nuevas' => 0, 'total' => 0],
    'Temas'    => $panel['temas']    ?? ['nuevas' => 0, 'total' => 0],
];

?>
<div class="panel-banners" role="complementary" aria-label="Métricas del radar">
  <div class="banner-item banner-revpar">
    <dt>RevPAR</dt>
    <dd><?= web_e($panel['revpar'] ?? '€ 114,20') ?></dd>
  </div>
</div>
