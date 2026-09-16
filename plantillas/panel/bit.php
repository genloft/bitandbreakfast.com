<?php
/**
 * Edicion de un bit.
 *
 * A la izquierda se escribe; a la derecha estan las fuentes del racimo, que
 * es de donde sale lo que se escribe. Tenerlas al lado evita el viaje de ida
 * y vuelta a otra pestaña, que es donde se pierde el hilo.
 */

declare(strict_types=1);

$categorias = bits_categorias();
$madureces  = bits_madureces();
$tipos      = bits_tipos();

?>
<h1>Bit en <?= panel_e($bit['estado']) ?></h1>

<div class="dos-columnas">

  <form method="post" action="index.php?p=bit&amp;id=<?= (int) $bit['id'] ?>" class="editor">
    <input type="hidden" name="csrf" value="<?= panel_e(panel_csrf()) ?>">
    <input type="hidden" name="id" value="<?= (int) $bit['id'] ?>">

    <label for="titular">Titular <span class="limite">máximo <?= BITS_TITULAR_MAX ?> caracteres</span></label>
    <input id="titular" name="titular" maxlength="<?= BITS_TITULAR_MAX ?>"
           value="<?= panel_e($bit['titular']) ?>" required>

    <label for="cuerpo">Cuerpo
      <span class="limite">entre <?= BITS_CUERPO_MIN ?> y <?= BITS_CUERPO_MAX ?> palabras;
      ahora <?= texto_contar_palabras((string) $bit['cuerpo']) ?></span>
    </label>
    <textarea id="cuerpo" name="cuerpo" rows="8"><?= panel_e($bit['cuerpo']) ?></textarea>

    <label for="por_que">Por qué importa
      <span class="limite">máximo <?= BITS_PORQUE_MAX ?> caracteres</span>
    </label>
    <textarea id="por_que" name="por_que" rows="3" maxlength="<?= BITS_PORQUE_MAX ?>"><?= panel_e($bit['por_que']) ?></textarea>

    <div class="campos">
      <div>
        <label for="categoria">Categoría</label>
        <select id="categoria" name="categoria">
          <?php foreach ($categorias as $clave => $etiqueta): ?>
            <option value="<?= panel_e($clave) ?>"<?= $bit['categoria'] === $clave ? ' selected' : '' ?>><?= panel_e($etiqueta) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="madurez">Madurez</label>
        <select id="madurez" name="madurez">
          <?php foreach ($madureces as $clave => $etiqueta): ?>
            <option value="<?= panel_e($clave) ?>"<?= $bit['madurez'] === $clave ? ' selected' : '' ?>><?= panel_e($etiqueta) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="tipo">Tipo</label>
        <select id="tipo" name="tipo">
          <?php foreach ($tipos as $clave => $etiqueta): ?>
            <option value="<?= panel_e($clave) ?>"<?= $bit['tipo'] === $clave ? ' selected' : '' ?>><?= panel_e($etiqueta) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="botones">
      <button type="submit" name="accion" value="guardar_bit" class="suave">Guardar borrador</button>
      <button type="submit" name="accion" value="aprobar_bit">Aprobar y meter en la edición</button>
    </div>
  </form>

  <aside class="fuentes">
    <h2>De dónde sale</h2>

    <?php if ($racimo !== null): ?>
      <p class="nota">Racimo <?= (int) $racimo['id'] ?>,
      puntuación <?= (int) $racimo['puntuacion'] ?>.</p>
    <?php endif; ?>

    <?php if (!$items): ?>
      <p class="vacio">El racimo ya no tiene items. Puede que se descartaran
      después de crear el bit.</p>
    <?php endif; ?>

    <ol class="items">
    <?php foreach ($items as $item): ?>
      <li>
        <a href="<?= panel_e($item['url']) ?>" rel="noopener noreferrer" target="_blank"><?= panel_e($item['titulo']) ?></a>
        <p class="meta"><?= panel_e($item['fuente']) ?> ·
           <?= panel_e($item['region']) ?> ·
           <?= panel_e(substr((string) $item['publicado'], 0, 16)) ?> ·
           <?= (int) $item['puntuacion'] ?> puntos</p>
        <?php if (trim((string) $item['resumen_origen']) !== ''): ?>
          <p class="resumen"><?= panel_e(texto_recortar(strip_tags((string) $item['resumen_origen']), 320)) ?></p>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
    </ol>

    <form method="post" action="index.php?p=bit&amp;id=<?= (int) $bit['id'] ?>" class="peligro">
      <input type="hidden" name="csrf" value="<?= panel_e(panel_csrf()) ?>">
      <input type="hidden" name="accion" value="borrar_bit">
      <input type="hidden" name="id" value="<?= (int) $bit['id'] ?>">
      <button type="submit" class="suave">Borrar el bit y devolver el racimo a la cola</button>
    </form>
  </aside>

</div>
