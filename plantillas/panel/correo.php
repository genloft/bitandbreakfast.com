<?php
/**
 * Ajustes del buzon de correo y estado de la lista.
 *
 * Aqui es donde se teclea la contrasena del buzon, y es el unico sitio donde
 * debe teclearse: no va en el repositorio, no va en la base de datos y no se
 * la manda uno a nadie por chat. Se guarda en config/correo.php, que Apache
 * no sirve, con permisos 0600.
 *
 * El campo de la contrasena sale siempre vacio aunque haya una guardada. Una
 * contrasena rellena en un formulario es una contrasena que acaba en el
 * historial del navegador y en el gestor de contrasenas de quien pasaba por
 * ahi; si hay que cambiarla, se escribe entera.
 *
 * Recibe $buzon (sin la clave), $configurado y $cuentas.
 */

declare(strict_types=1);

?>
<h1>Correo</h1>

<p class="explicacion">
  El boletín sale por SMTP desde el buzón del propio dominio. Mientras falte
  cualquiera de estos datos, el alta no aparece en la web: más vale no ofrecer
  suscripción que ofrecerla y no poder mandar la confirmación.
</p>

<p class="estado-correo">
  <?php if ($configurado): ?>
    <strong>Configurado.</strong> El alta está abierta en la web.
  <?php else: ?>
    <strong>Sin configurar.</strong> El alta no se ofrece todavía.
  <?php endif; ?>
</p>

<h2>La lista</h2>

<ul class="cuentas">
  <li><strong><?= (int) $cuentas['confirmado'] ?></strong> confirmados</li>
  <li><strong><?= (int) $cuentas['pendiente'] ?></strong> pendientes de confirmar</li>
  <li><strong><?= (int) $cuentas['baja'] ?></strong> bajas</li>
</ul>

<h2>El buzón</h2>

<form method="post" action="index.php?p=correo" autocomplete="off">
  <input type="hidden" name="csrf" value="<?= panel_e(panel_csrf()) ?>">
  <input type="hidden" name="accion" value="guardar_correo">

  <p>
    <label for="host">Servidor de salida (SMTP)</label>
    <input id="host" name="host" type="text" required
           value="<?= panel_e($buzon['host'] !== '' ? $buzon['host'] : 'smtp.hostinger.com') ?>">
  </p>

  <p>
    <label for="puerto">Puerto</label>
    <select id="puerto" name="puerto">
      <option value="465"<?= (int) $buzon['puerto'] === 465 ? ' selected' : '' ?>>465 · SSL</option>
      <option value="587"<?= (int) $buzon['puerto'] === 587 ? ' selected' : '' ?>>587 · STARTTLS</option>
    </select>
  </p>

  <p>
    <label for="usuario_smtp">Usuario (la dirección completa)</label>
    <input id="usuario_smtp" name="usuario" type="text" required
           autocomplete="off" value="<?= panel_e($buzon['usuario']) ?>">
  </p>

  <p>
    <label for="clave_smtp">Contraseña del buzón</label>
    <input id="clave_smtp" name="clave" type="password" required
           autocomplete="new-password" placeholder="<?= $configurado ? 'guardada; escríbela otra vez para cambiarla' : '' ?>">
    <span class="pista">No se muestra nunca, ni aquí ni en ningún registro.</span>
  </p>

  <p>
    <label for="remitente">Remitente</label>
    <input id="remitente" name="remitente" type="email"
           value="<?= panel_e($buzon['remitente']) ?>">
    <span class="pista">Si lo dejas vacío, se usa el usuario.</span>
  </p>

  <p class="acciones">
    <button type="submit">Guardar</button>
  </p>
</form>

<h2>El aviso del cron</h2>

<p class="explicacion">
  Un correo con el parte de cada pasada: qué ha entrado, qué ha pasado al
  archivo y qué ha hecho cada tarea. El cron corre cada hora, así que
  «siempre» son veinticuatro correos al día, y salen por este mismo buzón, que
  tiene límite por hora. Con «solo cuando haya cambios» suelen ser uno o dos.
</p>

<form method="post" action="index.php?p=correo">
  <input type="hidden" name="csrf" value="<?= panel_e(panel_csrf()) ?>">
  <input type="hidden" name="accion" value="guardar_aviso">

  <p>
    <label for="cron_aviso">Cuándo avisar</label>
    <select id="cron_aviso" name="cron_aviso">
      <option value="siempre"<?= $aviso_modo === 'siempre' ? ' selected' : '' ?>>En cada pasada</option>
      <option value="cambios"<?= $aviso_modo === 'cambios' ? ' selected' : '' ?>>Solo cuando haya cambios</option>
      <option value="no"<?= $aviso_modo === 'no' ? ' selected' : '' ?>>Nunca</option>
    </select>
  </p>

  <p>
    <label for="cron_aviso_correo">A qué dirección</label>
    <input id="cron_aviso_correo" name="cron_aviso_correo" type="email"
           value="<?= panel_e($aviso_correo) ?>">
    <span class="pista">Si lo dejas vacío, al propio buzón.</span>
  </p>

  <p class="acciones"><button type="submit">Guardar</button></p>
</form>

<h2>El envío</h2>

<p class="explicacion">
  La edición cerrada sale sola, por tandas, porque el buzón tiene un límite de
  correos por hora. Aquí se ve cuál está en camino y cuánto le queda.
</p>

<?php if ($envio['edicion'] === 0): ?>
  <p class="estado-correo">No hay ninguna edición pendiente de enviar.</p>
<?php else: ?>
  <p class="estado-correo">
    <strong>Edición <?= (int) $envio['edicion'] ?></strong>:
    <?= (int) $envio['enviados'] ?> enviados,
    <?= (int) $envio['pendientes'] ?> pendientes.
  </p>

  <form method="post" action="index.php?p=correo">
    <input type="hidden" name="csrf" value="<?= panel_e(panel_csrf()) ?>">
    <input type="hidden" name="accion" value="enviar_tanda">
    <p class="acciones"><button type="submit">Mandar una tanda ahora</button></p>
  </form>
<?php endif; ?>

<h2>Probar</h2>

<p class="explicacion">
  Manda un correo de prueba. Merece la pena hacerlo ahora y no el martes con
  la edición cerrada esperando.
</p>

<form method="post" action="index.php?p=correo">
  <input type="hidden" name="csrf" value="<?= panel_e(panel_csrf()) ?>">
  <input type="hidden" name="accion" value="probar_correo">

  <p>
    <label for="prueba">Mandar una prueba a</label>
    <input id="prueba" name="destino" type="email" required
           value="<?= panel_e($buzon['usuario']) ?>">
  </p>

  <p class="acciones">
    <button type="submit">Mandar prueba</button>
  </p>
</form>
