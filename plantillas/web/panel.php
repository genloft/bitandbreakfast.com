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
<div class="panel" role="complementary" aria-label="Estado del radar">
  <dl class="panel-relojes">
    <div class="panel-reloj">
      <dt>Actualizado</dt>
      <dd><span class="pulso" aria-hidden="true"></span> <time datetime="<?= web_e(str_replace(' ', 'T', (string) $panel['cuando']) . 'Z') ?>"><?= web_e($cuando) ?></time></dd>
    </div>

    <?php if ($siguiente !== ''): ?>
      <div class="panel-reloj">
        <dt>Siguiente</dt>
        <dd><time datetime="<?= web_e(str_replace(' ', 'T', $siguiente) . 'Z') ?>">~<?= web_e(web_hora($siguiente)) ?></time></dd>
      </div>
    <?php endif; ?>
  </dl>

  <dl class="panel-cifras">
    <?php foreach ($filas as $nombre => $cifra): ?>
      <div class="panel-cifra">
        <dt><?= web_e($nombre) ?></dt>
        <dd>
          <span class="panel-nuevas<?= (int) $cifra['nuevas'] > 0 ? ' panel-nuevas-hay' : '' ?>">
            +<?= (int) $cifra['nuevas'] ?>
          </span>
          <span class="panel-total"><?= (int) $cifra['total'] ?></span>
        </dd>
      </div>
    <?php endforeach; ?>
  </dl>
</div>
