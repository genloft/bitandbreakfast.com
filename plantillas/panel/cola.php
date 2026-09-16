<?php
/**
 * La cola de candidatos: racimos por puntuacion, de mas a menos.
 *
 * Cada fila enseña lo justo para decidir sin abrir nada: el titular del mejor
 * item, cuantas fuentes lo cuentan y cuando se vio por ultima vez. Las dos
 * acciones posibles estan ahi mismo, porque curar es decidir rapido muchas
 * veces seguidas.
 */

declare(strict_types=1);

?>
<h1>Cola de candidatos</h1>

<?php if (!$cola): ?>

  <p class="vacio">No hay candidatos. O el cron todavía no ha procesado nada,
  o ya has pasado por todos. Los racimos aparecen aquí en cuanto
  <code>cron/procesar.php</code> los agrupa.</p>

<?php else: ?>

  <p class="nota"><?= count($cola) ?> candidatos. La puntuación es la del
  racimo: su mejor item más un extra por cada fuente distinta que lo cuenta.</p>

  <ul class="cola">
  <?php foreach ($cola as $racimo): ?>
    <li>
      <div class="puntos" title="Puntuación del racimo"><?= (int) $racimo['puntuacion'] ?></div>

      <div class="cuerpo">
        <h2><?= panel_e($racimo['titulo_representativo']) ?></h2>
        <p class="meta">
          <?= (int) $racimo['fuentes'] ?> fuente<?= (int) $racimo['fuentes'] === 1 ? '' : 's' ?>,
          <?= (int) $racimo['n_items'] ?> item<?= (int) $racimo['n_items'] === 1 ? '' : 's' ?>,
          visto por última vez el <?= panel_e(substr((string) $racimo['ultimo_visto'], 0, 16)) ?>
        </p>
      </div>

      <div class="acciones">
        <form method="post" action="index.php?p=cola">
          <input type="hidden" name="csrf" value="<?= panel_e(panel_csrf()) ?>">
          <input type="hidden" name="accion" value="crear_bit">
          <input type="hidden" name="racimo_id" value="<?= (int) $racimo['id'] ?>">
          <button type="submit">Escribir bit</button>
        </form>

        <form method="post" action="index.php?p=cola">
          <input type="hidden" name="csrf" value="<?= panel_e(panel_csrf()) ?>">
          <input type="hidden" name="accion" value="descartar">
          <input type="hidden" name="racimo_id" value="<?= (int) $racimo['id'] ?>">
          <input type="text" name="motivo" placeholder="motivo" maxlength="110" aria-label="Motivo del descarte">
          <button type="submit" class="suave">Descartar</button>
        </form>
      </div>
    </li>
  <?php endforeach; ?>
  </ul>

<?php endif; ?>
