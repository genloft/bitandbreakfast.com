<?php
/**
 * Datos estructurados (schema.org, JSON-LD) y utilidades de SEO y GEO.
 *
 * Calculo puro, sin base de datos ni plantillas, para poder probarlo en CI
 * sin montar nada. plantillas/web/seo.php lo usa para pintar el <head> de
 * todas las paginas.
 *
 * JSON-LD y no microdatos para lo que describe a la pagina entera -sitio,
 * organizacion, migas, listas-: un bloque <script type="application/ld+json">
 * es un bloque de DATOS, no se ejecuta, y por eso no lo toca el script-src
 * 'self' de la politica de seguridad. (Los microdatos de cada noticia siguen
 * en bit.php, pegados al texto visible que describen.)
 *
 * Un solo grafo por pagina, con @id estables: la organizacion y el sitio se
 * declaran una vez y el resto los referencia. Es lo que permite a un buscador
 * -o a un modelo que resume la pagina- saber que "el que publica esto" es la
 * misma entidad en las treinta paginas del sitio.
 */

declare(strict_types=1);

require_once __DIR__ . '/web.php';     // web_slug_seguro()
require_once __DIR__ . '/texto.php';   // texto_recortar()

/** El nombre con el que el sitio se presenta en todas partes. */
const SEO_NOMBRE = 'Bit & Breakfast';

/**
 * Serializa un grafo a un bloque <script type="application/ld+json">.
 *
 * JSON_HEX_TAG y JSON_HEX_AMP escapan < > &: un titular que lleve "</script>"
 * no puede cerrar el bloque y colar marcado. Es JSON valido igualmente.
 */
function seo_json(array $grafo): string
{
    $json = json_encode(
        ['@context' => 'https://schema.org', '@graph' => array_values($grafo)],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_PARTIAL_OUTPUT_ON_ERROR
    );

    return '<script type="application/ld+json">' . ($json === false ? '{}' : $json) . '</script>';
}

function seo_id(string $base, string $ancla): string
{
    return rtrim($base, '/') . '/#' . $ancla;
}

/** Quien publica: una sola entidad, referenciada desde todo lo demas. */
function seo_organizacion(string $base): array
{
    $base = rtrim($base, '/');

    return [
        '@type'       => 'Organization',
        '@id'         => seo_id($base, 'organizacion'),
        'name'        => SEO_NOMBRE,
        'alternateName' => ['Bit and Breakfast', 'Bit&Breakfast'],
        'url'         => $base . '/',
        'description' => 'Agregador independiente de tecnología hotelera en español: rastrea el sector, agrupa lo que cuenta lo mismo y publica cada día lo que aparece en el radar.',
        'logo'        => [
            '@type'  => 'ImageObject',
            'url'    => $base . '/favicon.svg',
            'width'  => 512,
            'height' => 512,
        ],
        'knowsAbout'  => [
            'Tecnología hotelera', 'PMS', 'Channel manager', 'Revenue management',
            'Distribución hotelera', 'Inteligencia artificial en hoteles', 'Ciberseguridad hotelera',
        ],
        'knowsLanguage' => 'es',
    ];
}

/** El sitio, con su buscador declarado para que se pueda usar desde fuera. */
function seo_sitio(string $base): array
{
    $base = rtrim($base, '/');

    return [
        '@type'      => 'WebSite',
        '@id'        => seo_id($base, 'sitio'),
        'name'       => SEO_NOMBRE,
        'alternateName' => 'Bit and Breakfast',
        'url'        => $base . '/',
        'inLanguage' => 'es-ES',
        'description' => 'El radar de tecnología hotelera en español.',
        'publisher'  => ['@id' => seo_id($base, 'organizacion')],
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => $base . '/buscar.html?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];
}

/** Una imagen de tarjeta social: 1200x630, que es lo que genera imagen_social.php. */
function seo_imagen(string $url): array
{
    return ['@type' => 'ImageObject', 'url' => $url, 'width' => 1200, 'height' => 630];
}

/**
 * El nodo de la pagina en si.
 *
 * @param array $p  Claves: url, nombre, descripcion y, opcionales, tipo
 *                  (WebPage, CollectionPage, AboutPage...), imagen, publicado,
 *                  modificado, extra (campos que se mezclan tal cual).
 */
function seo_pagina(array $p, string $base): array
{
    $nodo = [
        '@type'      => $p['tipo'] ?? 'WebPage',
        '@id'        => $p['url'] . '#pagina',
        'url'        => $p['url'],
        'name'       => $p['nombre'],
        'description' => $p['descripcion'],
        'inLanguage' => 'es-ES',
        'isPartOf'   => ['@id' => seo_id($base, 'sitio')],
        'publisher'  => ['@id' => seo_id($base, 'organizacion')],
    ];

    if (!empty($p['imagen'])) {
        $nodo['primaryImageOfPage'] = seo_imagen((string) $p['imagen']);
        $nodo['image']              = (string) $p['imagen'];
    }
    if (!empty($p['publicado'])) {
        $nodo['datePublished'] = (string) $p['publicado'];
    }
    if (!empty($p['modificado'])) {
        $nodo['dateModified'] = (string) $p['modificado'];
    }

    return $nodo + (array) ($p['extra'] ?? []);
}

/**
 * Lista ordenada de enlaces: ItemList.
 *
 * @param array $items Filas con 'nombre' y 'url'.
 */
function seo_lista(array $items, string $nombre = ''): array
{
    $elementos = [];
    $posicion  = 0;

    foreach ($items as $item) {
        $posicion++;
        $elementos[] = [
            '@type'    => 'ListItem',
            'position' => $posicion,
            'url'      => (string) $item['url'],
            'name'     => (string) $item['nombre'],
        ];
    }

    $lista = ['@type' => 'ItemList', 'numberOfItems' => count($elementos), 'itemListElement' => $elementos];

    return $nombre === '' ? $lista : ['name' => $nombre] + $lista;
}

/**
 * Migas de pan: BreadcrumbList.
 *
 * @param array $migas Filas con 'nombre' y 'url', de la portada a la pagina actual.
 */
function seo_migas(array $migas): array
{
    $elementos = [];

    foreach (array_values($migas) as $i => $miga) {
        $elementos[] = [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'name'     => (string) $miga['nombre'],
            'item'     => (string) $miga['url'],
        ];
    }

    return ['@type' => 'BreadcrumbList', 'itemListElement' => $elementos];
}

/**
 * El glosario como vocabulario: DefinedTermSet.
 *
 * Es lo mas citable que tiene el sitio: una sigla y una frase que la
 * explica, con ancla propia -/glosario.html#pms- a la que enlazar.
 *
 * @param array $terminos Filas de glosario_terminos().
 */
function seo_glosario(array $terminos, string $url): array
{
    $definidos = [];

    foreach ($terminos as $t) {
        $definidos[] = [
            '@type'            => 'DefinedTerm',
            '@id'              => $url . '#' . web_slug_seguro((string) $t['sigla']),
            'name'             => (string) $t['sigla'],
            'alternateName'    => (string) $t['nombre'],
            'description'      => (string) $t['definicion'],
            'inDefinedTermSet' => ['@id' => $url . '#glosario'],
        ];
    }

    return [
        '@type'          => 'DefinedTermSet',
        '@id'            => $url . '#glosario',
        'name'           => 'Glosario de tecnología hotelera',
        'inLanguage'     => 'es-ES',
        'hasDefinedTerm' => $definidos,
    ];
}

/**
 * Un conjunto de datos publico y descargable: Dataset.
 *
 * Es lo que hace que cifras.json o heatmap.json se encuentren como datos y
 * no solo como un fichero suelto.
 *
 * @param array $descargas Filas con 'url' y 'formato' (tipo MIME).
 */
function seo_dataset(string $url, string $nombre, string $descripcion, string $modificado, array $descargas, string $base): array
{
    $distribucion = [];

    foreach ($descargas as $d) {
        $distribucion[] = [
            '@type'          => 'DataDownload',
            'contentUrl'     => (string) $d['url'],
            'encodingFormat' => (string) $d['formato'],
        ];
    }

    $dataset = [
        '@type'        => 'Dataset',
        '@id'          => $url . '#datos',
        'name'         => $nombre,
        'description'  => $descripcion,
        'url'          => $url,
        'inLanguage'   => 'es-ES',
        'creator'      => ['@id' => seo_id($base, 'organizacion')],
        'publisher'    => ['@id' => seo_id($base, 'organizacion')],
        'isAccessibleForFree' => true,
        'distribution' => $distribucion,
    ];

    if ($modificado !== '') {
        $dataset['dateModified'] = $modificado;
    }

    return $dataset;
}

/**
 * Los rastreadores de IA que el sitio recibe de buena gana.
 *
 * Aparecen por su nombre en robots.txt: un rastreador con grupo propio
 * ignora el grupo de *, asi que cada uno repite las mismas reglas. Tres
 * familias -entrenamiento, busqueda y lectura a peticion de un usuario-
 * porque la mayoria de las empresas las separan y asi cada una se decide por
 * separado.
 *
 * @return array Nombre del rastreador => familia.
 */
function seo_rastreadores_ia(): array
{
    return [
        // Entrenamiento y mejora de modelos.
        'GPTBot'              => 'entrenamiento',
        'ClaudeBot'           => 'entrenamiento',
        'Google-Extended'     => 'entrenamiento',
        'Applebot-Extended'   => 'entrenamiento',
        'CCBot'               => 'entrenamiento',
        'Meta-ExternalAgent'  => 'entrenamiento',
        // Busqueda: lo que alimenta las respuestas con enlace.
        'OAI-SearchBot'       => 'busqueda',
        'Claude-SearchBot'    => 'busqueda',
        'PerplexityBot'       => 'busqueda',
        'DuckAssistBot'       => 'busqueda',
        'Applebot'            => 'busqueda',
        // Lectura a peticion de una persona concreta.
        'ChatGPT-User'        => 'usuario',
        'Claude-User'         => 'usuario',
        'Perplexity-User'     => 'usuario',
        'MistralAI-User'      => 'usuario',
    ];
}

/**
 * La primera frase de un texto, recortada si hace falta.
 *
 * Para meta descriptions y para llms.txt: una descripcion larga de una
 * categoria sirve para leerla en su pagina, no para un resultado de busqueda
 * ni para una linea de indice.
 */
function seo_primera_frase(string $texto, int $maximo = 160): string
{
    $texto = trim((string) preg_replace('/\s+/', ' ', $texto));

    if ($texto === '') {
        return '';
    }

    // Hasta el primer punto seguido de espacio y mayuscula: las siglas con
    // punto interno -"S.A.", "SES.Hospedajes"- no cortan la frase.
    if (preg_match('/^(.+?[.!?])\s+\p{Lu}/u', $texto, $m) === 1) {
        $texto = $m[1];
    }

    return texto_recortar($texto, $maximo);
}
