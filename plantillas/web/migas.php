<?php
/**
 * Migas de pan invisibles: BreadcrumbList de schema.org, en JSON-LD.
 *
 * Solo datos, sin ningun <ol> visible: estas paginas ya tienen menu y un
 * enlace de "volver", y una fila mas de navegacion no anadiria nada para
 * quien lee. Lo que si anade es contexto para quien no lee la pagina como
 * una persona -un buscador, o una IA que la resuma-: que esta ficha de tema
 * cuelga de Temas, y Temas de la portada, no solo cual es su URL.
 *
 * Antes eran microdatos dentro de un <ol hidden>. Un <script
 * type="application/ld+json"> es un bloque de datos, no se ejecuta, y por
 * eso el script-src 'self' del sitio no lo toca; y no depende de marcado
 * oculto, que es lo que los buscadores miran con mas recelo.
 *
 * Recibe $migas: lista ordenada de ['nombre' => .., 'url' => ..], desde la
 * portada hasta la pagina actual inclusive.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/lib/seo.php';

$migas = $migas ?? [];

if (!$migas) {
    return;
}

?>
<?= seo_json([['@id' => end($migas)['url'] . '#migas'] + seo_migas($migas)]) . "\n" ?>
