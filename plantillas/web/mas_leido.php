<?php
/**
 * Lo mas leido de los ultimos siete dias. Recibe $mas_leidos, $base y
 * $secreto.
 *
 * Va entre el rio del dia y "seguir tirando del hilo": quien ha llegado hasta
 * aqui ya ha visto lo de hoy, y esto es la version corta de "que me he
 * perdido esta semana". El ranking sale de clics reales -la misma cuenta que
 * guarda api/ir.php-, no de una opinion.
 *
 * Sin trafico de sobra, publicar_mas_leidos() devuelve la lista vacia y esta
 * seccion no se pinta: nunca hay un "lo mas leido" con una sola entrada.
 */

declare(strict_types=1);

$mas_leidos = $mas_leidos ?? [];
$base       = $base ?? '';
$secreto    = $secreto ?? '';

if (!$mas_leidos) {
    return;
}

$categorias = bits_categorias();

?>
<section class="masleido" aria-labelledby="masleido-titulo">
  <h2 id="masleido-titulo"><span class="pulso" aria-hidden="true"></span>Lo más leído esta semana</h2>

  <ol class="masleido-lista">
    <?php foreach ($mas_leidos as $indice => $bit): ?>
      <?php
        $tema   = bits_categoria_canonica((string) $bit['categoria']) ?: 'tecnologia-general';
        $enlace = !empty($bit['url'])
            ? web_url_clic($base, (int) $bit['id'], $secreto, (string) $bit['url'])
            : '';
      ?>
      <li data-tema="<?= web_e($tema) ?>">
        <span class="masleido-numero"><?= str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) ?></span>
        <span class="masleido-cuerpo">
          <?php if ($enlace !== ''): ?>
            <a href="<?= web_e($enlace) ?>" rel="nofollow noopener"><?= web_e((string) $bit['titular']) ?></a>
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
