<?php
/**
 * La barra de arriba: cuando se actualizo esto y que cambio.
 *
 * Va encima de la cabecera, antes que nada, porque contesta la primera
 * pregunta de quien vuelve a un radar: ¿hay algo nuevo desde la ultima vez que
 * pase por aqui? Contestarla en la portada ahorra tener que leerla entera para
 * descubrir que no.
 *
 * Es una foto del momento en que se genero la pagina, no un dato en vivo: el
 * sitio es HTML estatico y esa es justamente la razon de que aguante. Si no ha
 * cambiado nada desde la vez anterior, se dice solo cuando fue; escribir "0
 * noticias nuevas" es ruido con forma de dato.
 *
 * Recibe $aviso -cuando, nuevos, archivados-.
 */

declare(strict_types=1);

$aviso = $aviso ?? [];

$cuando     = web_fecha_hora((string) ($aviso['cuando'] ?? ''));
$nuevos     = (int) ($aviso['nuevos'] ?? 0);
$archivados = (int) ($aviso['archivados'] ?? 0);

if ($cuando === '') {
    return;
}

?>
<div class="aviso-barra">
  <p class="aviso-texto">
    <span class="aviso-punto" aria-hidden="true"></span>

    <span class="aviso-cuando">Actualizado el <?= web_e($cuando) ?></span>

    <?php if ($nuevos > 0): ?>
      <span class="aviso-dato aviso-nuevos">
        <strong><?= $nuevos ?></strong> noticia<?= $nuevos === 1 ? '' : 's' ?> nueva<?= $nuevos === 1 ? '' : 's' ?>
      </span>
    <?php endif; ?>

    <?php if ($archivados > 0): ?>
      <span class="aviso-dato aviso-archivo">
        <strong><?= $archivados ?></strong>
        <?= $archivados === 1 ? 'pasó' : 'pasaron' ?> al
        <a href="<?= web_e($base) ?>/archivo.html">archivo</a>
      </span>
    <?php endif; ?>

    <?php if ($nuevos === 0 && $archivados === 0): ?>
      <span class="aviso-dato aviso-quieto">sin novedades desde la anterior</span>
    <?php endif; ?>
  </p>
</div>
