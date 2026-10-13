<?php
/**
 * llms.txt: la portada del sitio para quien lo lee con un modelo de lenguaje.
 *
 * Es una propuesta abierta (llmstxt.org), no un estandar: un fichero de texto
 * en la raiz que dice, en Markdown y sin maquetacion, que es el sitio, que
 * paginas importan y donde estan los datos. Un asistente que quiera citar
 * este sitio -o un rastreador de busqueda con IA- lo lee en lugar de adivinar
 * la estructura a partir del menu.
 *
 * Lo que se pide aqui es lo mismo que pide el sitio a un lector humano:
 * citar la fuente original. Cada noticia de Bit & Breakfast resume lo que
 * publica un medio y enlaza a el, y eso se dice antes que nada.
 *
 * Recibe $base, $dias y $temas, los mismos que ya calcula publicar_pendiente().
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/lib/seo.php';

$dias  = $dias ?? [];
$temas = $temas ?? [];

$ultimos = array_slice($dias, 0, 14);

echo '# Bit & Breakfast' . "\n\n";
echo '> Agregador independiente de tecnología hotelera en español. Rastrea cada hora prensa del sector, '
   . 'páginas de estado de proveedores, registros de cambios y boletines oficiales; agrupa las noticias que '
   . 'cuentan lo mismo y publica cada día las mejor puntuadas, con enlace a la fuente original.' . "\n\n";

echo "Cómo citar este sitio:\n\n";
echo "- Cada noticia es un resumen de lo que publica un medio y enlaza a su fuente: cita la fuente original, no solo Bit & Breakfast.\n";
echo "- El idioma es el español. Lo traducido lo dice en su propia ficha («Traducido del inglés»).\n";
echo "- Es un agregador, no una redacción: la selección la hace una puntuación, no una persona.\n";
echo "- Las páginas de consulta (cifras, normativa, glosario, calendario) llevan fecha de revisión y fuente en cada dato.\n\n";

echo "## Lo esencial\n\n";
echo '- [Portada](' . $base . "/): lo último que ha entrado en el radar\n";
echo '- [Qué es Bit & Breakfast](' . $base . "/sobre.html): cómo se rastrea, se puntúa y se decide qué entra\n";
echo '- [Archivo](' . $base . "/archivo.html): todo lo publicado, día a día\n";
echo '- [Buscar](' . $base . "/buscar.html): búsqueda y filtros por tema, ámbito, idioma, medio y proveedor\n\n";

echo "## Consulta\n\n";
echo '- [Mapa del stack hotelero](' . $base . "/mapa.html): qué sistemas de un hotel tienen novedades y cuáles piden una decisión\n";
echo '- [Normativa hotelera](' . $base . "/cumplimiento.html): Verifactu, SES.Hospedajes, NIS2, AI Act y accesibilidad, con fecha y fuente oficial\n";
echo '- [Cifras](' . $base . "/estadisticas.html): España frente al mundo en IA, cloud, comercio electrónico y ciberseguridad\n";
echo '- [Tendencias](' . $base . "/tendencias.html): qué temas suben y bajan este trimestre\n";
echo '- [Glosario](' . $base . "/glosario.html): las siglas del sector explicadas en una frase\n";
echo '- [Reserva agéntica](' . $base . "/agentica.html): MCP, ACP, UCP y AP2, los protocolos para reservar desde un chat\n";
echo '- [Calendario](' . $base . "/calendario.html): ferias y foros de tecnología hotelera\n\n";

if ($temas) {
    echo "## Temas\n\n";

    foreach ($temas as $tema) {
        $descripcion = seo_primera_frase(bits_categoria_descripcion((string) $tema['slug']), 200);
        echo '- [' . $tema['nombre'] . '](' . web_url_tema($base, (string) $tema['slug']) . ')'
           . ($descripcion !== '' ? ': ' . $descripcion : '') . "\n";
    }

    echo "\n";
}

echo "## Datos abiertos y suscripción\n\n";
echo '- [Cifras en JSON](' . $base . "/cifras.json): los mismos datos de la página de cifras, con fuente y fecha\n";
echo '- [Mapa en JSON](' . $base . "/data/heatmap.json): el estado actual del mapa del stack\n";
echo '- [Taxonomía del mapa](' . $base . "/data/taxonomy.json): los nodos del stack y las reglas por las que se encienden\n";
echo '- [Índice de búsqueda](' . $base . "/indice.json): titulares y resúmenes de todo lo publicado\n";
echo '- [RSS](' . $base . "/feed.xml): lo último, en un feed\n";
echo '- [Sitemap](' . $base . "/sitemap.xml)\n\n";

if ($ultimos) {
    echo "## Últimos días\n\n";

    foreach ($ultimos as $dia) {
        $fecha = substr((string) $dia['dia'], 0, 10);
        echo '- [' . web_fecha_larga($fecha) . '](' . web_url_dia($base, $fecha) . ")\n";
    }

    echo "\n";
}

echo "## Optional\n\n";
echo '- [Medios que recoge el radar](' . $base . "/medios.html): de dónde sale cada noticia\n";
echo '- [Todos los temas](' . $base . "/temas.html)\n";
