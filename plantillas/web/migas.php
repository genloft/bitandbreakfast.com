<?php
/**
 * Migas de pan invisibles: BreadcrumbList de schema.org.
 *
 * Solo microdatos, sin ningun <ol> visible: estas paginas ya tienen menu y
 * un enlace de "volver", y una fila mas de navegacion no anadiria nada para
 * quien lee. Lo que si anade es contexto para quien no lee la pagina como
 * una persona -un buscador, o una IA que la resuma-: que esta ficha de tema
 * cuelga de Temas, y Temas de la portada, no solo cual es su URL.
 *
 * Mismo criterio que el resto del sitio: itemprop, no JSON-LD, porque un
 * <script> en linea rompe el script-src 'self' que el sitio respeta a
 * proposito.
 *
 * Recibe $migas: lista ordenada de ['nombre' => .., 'url' => ..], desde la
 * portada hasta la pagina actual inclusive.
 */

declare(strict_types=1);

$migas = $migas ?? [];

if (!$migas) {
    return;
}

?>
<ol itemscope itemtype="https://schema.org/BreadcrumbList" hidden>
  <?php foreach ($migas as $indice => $miga): ?>
    <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
      <link itemprop="item" href="<?= web_e($miga['url']) ?>">
      <meta itemprop="name" content="<?= web_e($miga['nombre']) ?>">
      <meta itemprop="position" content="<?= (int) ($indice + 1) ?>">
    </li>
  <?php endforeach; ?>
</ol>
