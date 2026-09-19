<?php
/**
 * El catalogo de fuentes: todas las que se consultan, su estado y por que
 * las que no aportan nada no lo hacen.
 *
 * El diagnostico de cada fila cubre las dos preguntas que de verdad importan
 * y que antes solo se podian contestar por SQL directo: "¿esta fuente esta
 * fallando de verdad, o simplemente no cuenta nada que pase el filtro?", y
 * "¿desde cuando la escuchamos?".
 *
 * Recibe $fuentes -de datos_fuentes()-, $fuentes_tipos, $fuentes_regiones y
 * $categorias.
 */

declare(strict_types=1);

?>
<h1>Fuentes</h1>

<p class="explicacion">
  <?= count($fuentes) ?> en el catálogo. El diagnóstico de cada una es de los
  últimos catorce días: cuánto ha traído, cuánto se ha descartado -y por
  qué-, y cuánto ha llegado a publicarse. Una fuente puede fallar por dos
  motivos muy distintos: porque el feed no responde, o porque responde bien y
  lo que cuenta no pasa el filtro. Aquí se distinguen.
</p>

<ul class="catalogo">
<?php foreach ($fuentes as $fuente): ?>
  <?php
    $d = $fuente['diagnostico'];

    $estado_clase = 'activa';
    $estado_texto = 'Activa';

    if ((int) $fuente['activa'] !== 1) {
        $estado_clase = 'apagada';
        $estado_texto = 'Desactivada a mano';
    } elseif (!empty($fuente['dormida_hasta']) && strtotime((string) $fuente['dormida_hasta']) > time()) {
        // "Cuando" sin "por que" no basta para decidir si hay que tocarla a
        // mano: dormida_hasta dice solo cuando se reintenta, no que la
        // desperto. El ultimo_error es el mismo fallo que la durmio -es la
        // ultima pasada, y una pasada que duerme es una pasada que fallo-,
        // asi que se enseñan los dos juntos.
        $estado_clase = 'dormida';
        $estado_texto = 'Dormida hasta ' . panel_e(substr((string) $fuente['dormida_hasta'], 0, 16));

        if ($fuente['ultimo_error'] !== null) {
            $estado_texto .= ' (' . panel_e($fuente['ultimo_error']) . ')';
        }
    } elseif ($fuente['ultimo_error'] !== null) {
        $estado_clase = 'fallando';
        $estado_texto = 'Fallando: ' . panel_e($fuente['ultimo_error']);
    }
  ?>
  <li class="fuente-fila">
    <div class="cuerpo">
      <h2>
        <a href="<?= panel_e((string) $fuente['url_sitio']) ?>" rel="nofollow noopener" target="_blank"><?= panel_e((string) $fuente['nombre']) ?></a>
        <span class="badge badge-<?= panel_e($estado_clase) ?>"><?= $estado_texto ?></span>
      </h2>

      <p class="meta">
        <?= panel_e($fuentes_tipos[$fuente['tipo']] ?? (string) $fuente['tipo']) ?>
        <span class="punto">·</span>
        <?= panel_e(strtoupper((string) $fuente['idioma'])) ?>/<?= panel_e($fuentes_regiones[$fuente['region']] ?? (string) $fuente['region']) ?>
        <span class="punto">·</span>
        peso <?= (int) $fuente['peso'] ?>
        <span class="punto">·</span>
        <?= panel_e($categorias[$fuente['categoria_defecto']] ?? (string) $fuente['categoria_defecto']) ?>
        <span class="punto">·</span>
        <?= $fuente['fecha_alta'] !== null
            ? 'añadida el ' . panel_e((string) $fuente['fecha_alta'])
            : 'de antes de llevar esta cuenta' ?>
      </p>

      <p class="diagnostico">
        En 14 días: <strong><?= $d['total'] ?></strong> entrada<?= $d['total'] === 1 ? '' : 's' ?>,
        <strong><?= $d['descartados'] ?></strong> descartada<?= $d['descartados'] === 1 ? '' : 's' ?><?php if ($fuente['motivo_principal'] !== null): ?> (sobre todo: <?= panel_e($fuente['motivo_principal']) ?>)<?php endif; ?>,
        <strong><?= $d['en_cola'] ?></strong> en cola,
        <strong><?= $d['publicados'] ?></strong> publicada<?= $d['publicados'] === 1 ? '' : 's' ?>.
        <?php if ($d['total'] === 0 && (int) $fuente['activa'] === 1 && $fuente['ultimo_error'] === null): ?>
          <span class="letra-pequena">Nada en catorce días y sin error: puede que el feed esté vacío desde hace tiempo.</span>
        <?php endif; ?>
      </p>

      <?php if (trim((string) $fuente['notas']) !== ''): ?>
        <p class="letra-pequena"><?= panel_e((string) $fuente['notas']) ?></p>
      <?php endif; ?>
    </div>

    <div class="acciones">
      <form method="post" action="index.php?p=fuentes">
        <input type="hidden" name="csrf" value="<?= panel_e(panel_csrf()) ?>">
        <input type="hidden" name="accion" value="<?= (int) $fuente['activa'] === 1 ? 'desactivar_fuente' : 'activar_fuente' ?>">
        <input type="hidden" name="id" value="<?= (int) $fuente['id'] ?>">
        <button type="submit" class="suave"><?= (int) $fuente['activa'] === 1 ? 'Desactivar' : 'Activar' ?></button>
      </form>
    </div>
  </li>
<?php endforeach; ?>
</ul>

<h2>Añadir una fuente</h2>

<p class="explicacion">
  Empezará a leerse en la próxima pasada del cron. El feed tiene que ser RSS
  o Atom -no una página web-, y conviene comprobar antes que responde y que
  su <code>robots.txt</code> no prohíbe leerlo.
</p>

<form method="post" action="index.php?p=fuentes" class="editor">
  <input type="hidden" name="csrf" value="<?= panel_e(panel_csrf()) ?>">
  <input type="hidden" name="accion" value="crear_fuente">

  <div class="campos">
    <p>
      <label for="f_nombre">Nombre</label>
      <input id="f_nombre" name="nombre" type="text" required maxlength="120">
    </p>

    <p>
      <label for="f_url_feed">URL del feed</label>
      <input id="f_url_feed" name="url_feed" type="url" required placeholder="https://ejemplo.com/feed/">
    </p>

    <p>
      <label for="f_url_sitio">URL del sitio</label>
      <input id="f_url_sitio" name="url_sitio" type="url" placeholder="https://ejemplo.com/">
    </p>

    <p>
      <label for="f_tipo">Tipo</label>
      <select id="f_tipo" name="tipo">
        <?php foreach ($fuentes_tipos as $clave => $nombre): ?>
          <option value="<?= panel_e($clave) ?>"<?= $clave === 'prensa' ? ' selected' : '' ?>><?= panel_e($nombre) ?></option>
        <?php endforeach; ?>
      </select>
    </p>

    <p>
      <label for="f_idioma">Idioma (dos letras)</label>
      <input id="f_idioma" name="idioma" type="text" maxlength="2" pattern="[a-z]{2}" value="en">
    </p>

    <p>
      <label for="f_region">Ámbito</label>
      <select id="f_region" name="region">
        <?php foreach ($fuentes_regiones as $clave => $nombre): ?>
          <option value="<?= panel_e($clave) ?>"<?= $clave === 'global' ? ' selected' : '' ?>><?= panel_e($nombre) ?></option>
        <?php endforeach; ?>
      </select>
    </p>

    <p>
      <label for="f_categoria">Categoría por defecto</label>
      <select id="f_categoria" name="categoria_defecto">
        <?php foreach ($categorias as $clave => $nombre): ?>
          <option value="<?= panel_e($clave) ?>"<?= $clave === 'tecnologia-general' ? ' selected' : '' ?>><?= panel_e($nombre) ?></option>
        <?php endforeach; ?>
      </select>
    </p>

    <p>
      <label for="f_peso">Peso (1-10)</label>
      <input id="f_peso" name="peso" type="number" min="1" max="10" value="5">
    </p>
  </div>

  <p>
    <label for="f_notas">Notas</label>
    <input id="f_notas" name="notas" type="text" maxlength="500" placeholder="Por qué esta fuente, o qué conviene vigilar">
  </p>

  <p class="acciones">
    <button type="submit">Añadir fuente</button>
  </p>
</form>
