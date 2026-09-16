<?php
/**
 * Vista del instalador web.
 *
 * La incluye instalar.php con estas variables ya resueltas:
 *   $estado     formulario | ocupado | ya_instalado | listo | terminado
 *   $errores    lista de mensajes en rojo
 *   $avisos     lista de mensajes en ambar
 *   $requisitos filas [etiqueta, cumple, detalle] (solo en formulario)
 *   $resultado  resumen de la instalacion (solo en listo)
 *   $ingesta    resumen de la primera ingesta, o null
 *   $minutos    minutos que le quedan al cerrojo ajeno (solo en ocupado)
 *   $dominio    dominio configurado
 *   $valores    valores del formulario ya normalizados
 *   $token      token de la API, releido de la configuracion (solo en listo)
 *   $borrado    si el instalador ha conseguido borrarse
 *   $csrf       testigo del formulario
 *
 * Los estilos van dentro del fichero a proposito: el instalador tiene que
 * poder pintarse cuando todavia no hay nada publicado en publico/.
 */

declare(strict_types=1);

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Instalador de Bit &amp; Breakfast</title>
<style>
  :root { color-scheme: light; }
  body { margin: 0; padding: 2rem 1rem; background: #f6f5f2; color: #1b1b1a;
         font: 16px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif; }
  main { max-width: 44rem; margin: 0 auto; background: #fff; padding: 2rem;
         border: 1px solid #e0ddd6; border-radius: 6px; }
  h1 { margin: 0 0 .25rem; font-size: 1.5rem; }
  h2 { font-size: 1.1rem; margin: 2rem 0 .75rem; }
  p.sub { margin: 0 0 2rem; color: #6b675e; }
  ol.pasos { padding-left: 1.2rem; }
  ol.pasos li { margin-bottom: 1.25rem; }
  label { display: block; margin: 1rem 0 .25rem; font-weight: 600; font-size: .9rem; }
  input { width: 100%; padding: .55rem .7rem; border: 1px solid #c9c5ba; border-radius: 4px;
          font: inherit; box-sizing: border-box; }
  small { color: #6b675e; display: block; margin-top: .25rem; }
  button { margin-top: 1.5rem; padding: .7rem 1.4rem; font: inherit; font-weight: 600;
           background: #1b1b1a; color: #fff; border: 0; border-radius: 4px; cursor: pointer; }
  button.suave { background: #fff; color: #1b1b1a; border: 1px solid #c9c5ba; }
  table { width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: .9rem; }
  td { padding: .4rem .5rem; border-bottom: 1px solid #eeebe4; vertical-align: top; }
  td:first-child { width: 1.5rem; }
  .ok { color: #1a7f37; } .mal { color: #b42318; }
  .aviso { background: #fff6e5; border-left: 3px solid #d98a00; padding: 1rem; margin: 1rem 0; }
  .error { background: #fdeceb; border-left: 3px solid #b42318; padding: 1rem; margin: 1rem 0; }
  .bien  { background: #eaf6ed; border-left: 3px solid #1a7f37; padding: 1rem; margin: 1rem 0; }
  code, pre { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .85rem; }
  pre { background: #f6f5f2; padding: .8rem; border-radius: 4px; overflow-x: auto;
        white-space: pre-wrap; word-break: break-all; }
  .acciones { display: flex; gap: .75rem; flex-wrap: wrap; align-items: center; }
</style>
</head>
<body>
<main>

<h1>Bit &amp; Breakfast</h1>

<?php foreach ($errores as $error): ?>
  <div class="error"><?= inst_e($error) ?></div>
<?php endforeach; ?>

<?php foreach ($avisos as $mensaje): ?>
  <div class="aviso"><?= inst_e($mensaje) ?></div>
<?php endforeach; ?>

<?php if ($estado === 'ocupado'): ?>

  <p class="sub">Instalación en curso.</p>

  <div class="aviso">
    <strong>Hay otra instalación empezada.</strong>
    <p>Otro navegador abrió el instalador antes y se quedó con el turno. Solo
    puede haber uno: así nadie se cuela a medias mientras tú configuras el
    sitio.</p>
    <p>Si has sido tú, vuelve a la pestaña donde lo abriste. Si la perdiste,
    el turno se libera solo dentro de <?= (int) $minutos ?> minuto(s), o
    borras a mano el fichero <code>config/.instalacion</code> desde el
    Administrador de archivos y recargas.</p>
  </div>

<?php elseif ($estado === 'ya_instalado'): ?>

  <p class="sub">El sitio ya está instalado.</p>

  <div class="<?= $borrado ? 'bien' : 'aviso' ?>">
    <?php if ($borrado): ?>
      <strong>Instalador retirado.</strong>
      <p>Existe <code>config/config.php</code>, así que no había nada que
      instalar y el instalador acaba de borrarse solo. Si un despliegue por Git
      lo vuelve a dejar en el servidor, desaparecerá otra vez en cuanto alguien
      abra esta dirección.</p>
    <?php else: ?>
      <strong>El instalador está inerte, pero sigue en el servidor.</strong>
      <p>Existe <code>config/config.php</code>, así que no hace nada. No se ha
      borrado ahora mismo por una de dos razones: otra pestaña está terminando
      la instalación, o los permisos no me dejan. Si dentro de un rato sigue
      aquí, bórralo a mano desde el Administrador de archivos.</p>
    <?php endif; ?>
    <p>Para reinstalar desde cero hay que borrar <code>config/config.php</code>
    y volver a desplegar. Ten en cuenta que el esquema empieza con
    <code>DROP TABLE</code>: perderías todo lo que haya en la base.</p>
  </div>

<?php elseif ($estado === 'terminado'): ?>

  <p class="sub">Listo.</p>

  <div class="<?= $borrado ? 'bien' : 'aviso' ?>">
    <?php if ($borrado): ?>
      <strong>El instalador se ha borrado.</strong>
      <p>Ya no existe <code>instalar.php</code> en el servidor. Si un despliegue
      por Git lo devuelve, se borrará solo la próxima vez que alguien abra esa
      dirección, porque ya hay configuración escrita.</p>
    <?php else: ?>
      <strong>No he podido borrar <code>instalar.php</code>.</strong>
      <p>La instalación está terminada y el fichero ya no hace nada, pero
      bórralo a mano desde el Administrador de archivos de hPanel.</p>
    <?php endif; ?>
  </div>

  <p>El sitio está en marcha en
  <a href="https://<?= inst_e($dominio) ?>/">https://<?= inst_e($dominio) ?>/</a>.</p>

  <p><small>Lo que falta es la tarea cron, si no la has creado ya: hPanel →
  Avanzado → Trabajos cron, cada hora,
  <code>cron/tareas.php</code>. Está explicado en <code>docs/INSTALACION.md</code>.</small></p>

<?php elseif ($estado === 'listo'): ?>

  <p class="sub">Instalación terminada. Queda arrancar el motor y retirar el instalador.</p>

  <div class="bien">
    <strong>Base de datos lista.</strong>
    <p><?= (int) $resultado['sentencias'] ?> sentencias ejecutadas,
       <?= (int) $resultado['fuentes'] ?> fuentes,
       <?= (int) $resultado['terminos'] ?> términos y
       <?= (int) $resultado['ajustes'] ?> ajustes.
       <?php if ($resultado['usuario'] !== ''): ?>
         Usuario del panel: <code><?= inst_e($resultado['usuario']) ?></code>.
       <?php endif; ?>
    </p>
  </div>

  <h2>1. La tarea programada</h2>

  <p>Una sola entrada de cron para todo el sistema. hPanel → <em>Avanzado →
  Trabajos cron</em> → <em>Crear nuevo trabajo cron</em>, tipo <em>Comando
  personalizado</em>, frecuencia <em>cada hora</em>, y este comando:</p>

  <pre><?= inst_e($resultado['cron']) ?></pre>

  <small>El registro va a <code><?= inst_e($resultado['logs']) ?></code>, fuera de
  <code>public_html</code>, para que no se pueda leer desde la web. Si la ruta
  de PHP no fuese la correcta, usa la que ofrezca el desplegable de esa misma
  pantalla de hPanel.</small>

  <h2>2. Primera pasada, sin esperar al cron</h2>

  <p>Rastrea unas cuantas fuentes ahora mismo para comprobar que la ingesta
  funciona. Tarda unos <?= (int) INST_PRESUPUESTO_INGESTA ?> segundos y puedes
  repetirla: el puntero sigue donde lo dejó.</p>

  <p><small>Si el servidor corta la página a media pasada, no se pierde nada:
  la ingesta guarda el puntero fuente a fuente, y el cron continúa por donde
  se quedó.</small></p>

  <?php if ($ingesta !== null): ?>
    <div class="bien">
      <strong>Ingesta ejecutada.</strong>
      <p><?= (int) $ingesta['fuentes'] ?> fuentes rastreadas,
         <?= (int) $ingesta['nuevos'] ?> entradas nuevas,
         <?= (int) $ingesta['sin_cambios'] ?> sin cambios,
         <?= (int) $ingesta['errores'] ?> con error.</p>
      <?php if ((int) $ingesta['errores'] > 0): ?>
        <small>Algún error suelto es normal: hay fuentes que cierran, cambian de
        dirección o tardan más de la cuenta. Una fuente que falle cinco veces
        seguidas se desactiva sola.</small>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <form method="post">
    <input type="hidden" name="csrf" value="<?= inst_e($csrf) ?>">
    <input type="hidden" name="accion" value="ingesta">
    <button type="submit" class="suave">
      <?= $ingesta === null ? 'Ejecutar la primera ingesta' : 'Ejecutar otra pasada' ?>
    </button>
  </form>

  <h2>3. Token de la API</h2>

  <p>Lo necesitarás en la fase 6, para la redacción asistida. Queda guardado en
  <code>config/config.php</code>, así que no hace falta que lo copies:</p>

  <pre><?= inst_e($token) ?></pre>

  <h2>4. Retirar el instalador</h2>

  <p>Este es el último paso. Borra <code>instalar.php</code> del servidor y
  libera el turno. Antes de pulsarlo, copia la línea de cron del punto 1:
  después de esto, esta página ya no existe.</p>

  <form method="post">
    <input type="hidden" name="csrf" value="<?= inst_e($csrf) ?>">
    <input type="hidden" name="accion" value="terminar">
    <button type="submit">Terminar y borrar el instalador</button>
  </form>

<?php else: ?>

  <p class="sub">El sitio todavía no está instalado. Esta página crea las
  tablas, carga el catálogo de fuentes y el diccionario, y escribe la
  configuración.</p>

  <h2>Comprobaciones del servidor</h2>

  <table>
    <?php foreach ($requisitos as $requisito): ?>
      <tr>
        <td class="<?= $requisito[1] ? 'ok' : 'mal' ?>"><?= $requisito[1] ? '✓' : '✗' ?></td>
        <td><?= inst_e($requisito[0]) ?></td>
        <td><small><?= inst_e($requisito[2]) ?></small></td>
      </tr>
    <?php endforeach; ?>
  </table>

  <?php if (!inst_requisitos_ok($requisitos)): ?>
    <div class="aviso">Arregla lo marcado en rojo antes de continuar. La
    instalación fallará a medias si falta una extensión o si
    <code>config/</code> no tiene permiso de escritura.</div>
  <?php endif; ?>

  <h2>Base de datos</h2>

  <p><small>Los datos que te dio hPanel al crear la base en <em>Bases de datos
  → MySQL</em>. El nombre y el usuario llevan el prefijo de tu cuenta, algo
  como <code>u123456789_bitb</code>. La base tiene que estar creada y
  vacía.</small></p>

  <form method="post" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= inst_e($csrf) ?>">
    <input type="hidden" name="accion" value="instalar">

    <label for="host">Servidor</label>
    <input id="host" name="host" value="<?= inst_e($valores['host']) ?>" required>
    <small>En Hostinger casi siempre es <code>localhost</code>.</small>

    <label for="nombre">Nombre de la base de datos</label>
    <input id="nombre" name="nombre" value="<?= inst_e($valores['nombre']) ?>" required>

    <label for="usuario">Usuario</label>
    <input id="usuario" name="usuario" value="<?= inst_e($valores['usuario']) ?>" required>

    <label for="clave">Contraseña</label>
    <input id="clave" name="clave" type="password">

    <label for="puerto">Puerto</label>
    <input id="puerto" name="puerto" value="<?= inst_e((string) $valores['puerto']) ?>">

    <h2>Sitio</h2>

    <label for="dominio">Dominio</label>
    <input id="dominio" name="dominio" value="<?= inst_e($valores['dominio']) ?>"
           placeholder="bitandbreakfast.com" required>
    <small>Sin <code>https://</code> y sin barra final.</small>

    <h2>Usuario del panel <small style="display:inline;font-weight:400">(opcional, hace falta en la fase 3)</small></h2>

    <label for="panel_usuario">Usuario</label>
    <input id="panel_usuario" name="panel_usuario" value="<?= inst_e($valores['panel_usuario']) ?>">

    <label for="panel_clave">Contraseña</label>
    <input id="panel_clave" name="panel_clave" type="password">
    <small>Mínimo 12 caracteres. Se guarda con <code>password_hash</code>, nunca en claro.</small>

    <div class="aviso" style="margin-top:1.5rem">
      <strong>Ojo:</strong> el esquema empieza con <code>DROP TABLE IF EXISTS</code>.
      Si la base ya tiene datos de Bit &amp; Breakfast, <strong>se borran</strong>.
    </div>

    <button type="submit">Instalar</button>
  </form>

<?php endif; ?>

</main>
</body>
</html>
