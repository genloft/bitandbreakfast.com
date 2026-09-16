<?php
/**
 * La edicion abierta: cabecera, bits en orden y cierre.
 *
 * El orden importa porque es el del correo, asi que se toca aqui con dos
 * botones y no arrastrando: sin JavaScript en linea la politica de seguridad
 * queda intacta, y para veinte bits subir y bajar es suficiente.
 */

declare(strict_types=1);

?>
<h1>Edición <?= (int) $edicion['numero'] ?>
  <span class="limite"><?= panel_e($edicion['slug']) ?></span>
</h1>

<form method="post" action="index.php?p=edicion" class="cabecera-edicion">
  <input type="hidden" name="csrf" value="<?= panel_e(panel_csrf()) ?>">
  <input type="hidden" name="accion" value="guardar_edicion">

  <label for="titulo">Título de la edición</label>
  <input id="titulo" name="titulo" maxlength="190" value="<?= panel_e($edicion['titulo']) ?>">

  <label for="intro">Entradilla</label>
  <textarea id="intro" name="intro" rows="3"><?= panel_e($edicion['intro']) ?></textarea>

  <label for="fecha_prevista">Fecha prevista de envío</label>
  <input id="fecha_prevista" name="fecha_prevista" type="date" value="<?= panel_e($edicion['fecha_prevista']) ?>">

  <button type="submit" class="suave">Guardar</button>
</form>

<h2><?= count($bits) ?> bits · <?= (int) $revision['palabras'] ?> palabras</h2>

<?php foreach ($revision['avisos'] as $texto): ?>
  <p class="aviso aviso-suave"><?= panel_e($texto) ?></p>
<?php endforeach; ?>

<?php if (!$bits): ?>

  <p class="vacio">La edición está vacía. Los bits entran solos al aprobarlos
  desde la cola.</p>

<?php else: ?>

  <ol class="bits">
  <?php foreach ($bits as $indice => $bit): ?>
    <li>
      <div class="cuerpo">
        <h3><a href="index.php?p=bit&amp;id=<?= (int) $bit['id'] ?>"><?= panel_e($bit['titular']) ?></a></h3>
        <p class="meta">
          <?= panel_e($bit['estado']) ?> ·
          <?= panel_e($bit['categoria']) ?> ·
          <?= panel_e((string) ($bit['region'] ?? 'global')) ?> ·
          <?= texto_contar_palabras((string) $bit['cuerpo']) ?> palabras
        </p>
      </div>

      <div class="acciones">
        <form method="post" action="index.php?p=edicion">
          <input type="hidden" name="csrf" value="<?= panel_e(panel_csrf()) ?>">
          <input type="hidden" name="accion" value="mover_bit">
          <input type="hidden" name="id" value="<?= (int) $bit['id'] ?>">
          <button type="submit" name="direccion" value="subir" class="suave"
                  <?= $indice === 0 ? 'disabled' : '' ?> aria-label="Subir">↑</button>
          <button type="submit" name="direccion" value="bajar" class="suave"
                  <?= $indice === count($bits) - 1 ? 'disabled' : '' ?> aria-label="Bajar">↓</button>
        </form>

        <form method="post" action="index.php?p=edicion">
          <input type="hidden" name="csrf" value="<?= panel_e(panel_csrf()) ?>">
          <input type="hidden" name="accion" value="sacar_bit">
          <input type="hidden" name="id" value="<?= (int) $bit['id'] ?>">
          <button type="submit" class="suave">Sacar</button>
        </form>
      </div>
    </li>
  <?php endforeach; ?>
  </ol>

<?php endif; ?>

<?php if ($sueltos): ?>
  <h2>Bits fuera de la edición</h2>
  <ul class="sueltos">
  <?php foreach ($sueltos as $suelto): ?>
    <li>
      <a href="index.php?p=bit&amp;id=<?= (int) $suelto['id'] ?>"><?= panel_e($suelto['titular']) ?></a>
      <span class="limite"><?= panel_e($suelto['estado']) ?></span>
      <form method="post" action="index.php?p=edicion">
        <input type="hidden" name="csrf" value="<?= panel_e(panel_csrf()) ?>">
        <input type="hidden" name="accion" value="meter_bit">
        <input type="hidden" name="id" value="<?= (int) $suelto['id'] ?>">
        <button type="submit" class="suave">Meter</button>
      </form>
    </li>
  <?php endforeach; ?>
  </ul>
<?php endif; ?>

<form method="post" action="index.php?p=edicion" class="cerrar">
  <input type="hidden" name="csrf" value="<?= panel_e(panel_csrf()) ?>">
  <input type="hidden" name="accion" value="cerrar_edicion">
  <button type="submit"<?= $revision['errores'] ? ' disabled' : '' ?>>Cerrar la edición</button>
  <?php foreach ($revision['errores'] as $texto): ?>
    <span class="limite"><?= panel_e($texto) ?></span>
  <?php endforeach; ?>
</form>
