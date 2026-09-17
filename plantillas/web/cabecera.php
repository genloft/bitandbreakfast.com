/**
 * Cabecera comun de la web publicada.
 *
 * El nombre centrado y grande, y debajo la navegacion: la cabecera de un
 * periodico. Antes iba a la derecha y con un dibujo al lado; el dibujo se fue
 * -un icono ilustrativo baja de categoria una publicacion- y la alineacion
 * tambien, porque un nombre a la derecha parece el logotipo de una empresa y
 * no la cabecera de algo que se lee.
 *
 * Todo el caracter esta en la tipografia: versalitas de una didona con el
 * interletrado muy abierto. No hace falta nada mas y por eso no hay nada mas.
 *
 * Recibe $base y, opcionalmente, $enlace_activo.
 */

declare(strict_types=1);

$enlace_activo = $enlace_activo ?? '';

?>
<?php require __DIR__ . '/aviso.php'; ?>

<header class="cabecera">
  <a class="logo" href="<?= web_e($base) ?>/" aria-label="Bit &amp; Breakfast, portada">
    <span class="logo-bloque">Bit<span class="logo-amp">&amp;</span>Breakfast</span>
  </a>

  <p class="promesa">Radar de tecnología hotelera <span class="promesa-punto">·</span> en español <span class="promesa-punto">·</span> cinco minutos a la semana</p>

  <nav class="menu" aria-label="Principal">
    <a href="<?= web_e($base) ?>/"<?= $enlace_activo === 'portada' ? ' aria-current="page"' : '' ?>>Última edición</a>
    <a href="<?= web_e($base) ?>/archivo.html"<?= $enlace_activo === 'archivo' ? ' aria-current="page"' : '' ?>>Archivo</a>
    <a href="<?= web_e($base) ?>/temas.html"<?= $enlace_activo === 'temas' ? ' aria-current="page"' : '' ?>>Temas</a>
    <a href="<?= web_e($base) ?>/medios.html"<?= $enlace_activo === 'medios' ? ' aria-current="page"' : '' ?>>Medios</a>
    <a href="<?= web_e($base) ?>/buscar.html"<?= $enlace_activo === 'buscar' ? ' aria-current="page"' : '' ?>>Buscar</a>
    <a href="<?= web_e($base) ?>/sobre.html"<?= $enlace_activo === 'sobre' ? ' aria-current="page"' : '' ?>>Qué es</a>
    <a href="<?= web_e($base) ?>/feed.xml">RSS</a>
  </nav>

</header>
