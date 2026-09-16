<?php
/**
 * Pantalla de entrada al panel.
 */

declare(strict_types=1);

?>
<h1>Panel de curación</h1>

<form method="post" action="index.php?p=entrar" autocomplete="off">
  <input type="hidden" name="csrf" value="<?= panel_e(panel_csrf()) ?>">

  <label for="usuario">Usuario</label>
  <input id="usuario" name="usuario" autocapitalize="none" required>

  <label for="clave">Contraseña</label>
  <input id="clave" name="clave" type="password" required>

  <button type="submit">Entrar</button>
</form>

<p class="nota">El usuario se creó al instalar. Si no lo creaste entonces,
hay que añadirlo a mano en la tabla <code>usuarios</code> con
<code>password_hash</code>.</p>
