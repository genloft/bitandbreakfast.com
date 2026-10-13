<?php
/**
 * El <head> de cada pagina, escrito una sola vez.
 *
 * Antes cada plantilla llevaba su propia copia de estas veinte lineas, y las
 * copias habian ido divergiendo: unas tenian og:description y otras no, solo
 * dos llevaban og:locale, ninguna llevaba twitter:title ni las dimensiones de
 * la imagen, y ninguna declaraba datos estructurados. Un cambio de SEO eran
 * diecisiete ediciones, y la diecioho se olvidaba.
 *
 * Cada pagina declara en $seo lo que la distingue y esta plantilla pinta todo
 * lo demas. Claves de $seo:
 *
 *   titulo         el <title> completo
 *   titulo_social  og:title y twitter:title (por defecto, el titulo)
 *   descripcion    meta description, og:description y twitter:description
 *   url            la canonica, absoluta
 *   tipo           og:type: website (por defecto) o article
 *   tipo_pagina    el @type de schema.org: WebPage, CollectionPage, AboutPage...
 *   theme          theme-color
 *   robots         por defecto "index, follow" con vistas previas grandes
 *   imagen         la tarjeta social (por defecto, la generica)
 *   imagen_alt     su texto alternativo
 *   rss            ['titulo' => .., 'url' => ..] si la pagina tiene su propio feed
 *   publicado      fecha ISO en que se publico (paginas de dia)
 *   modificado     fecha ISO de la ultima revision real
 *   grafo          nodos de schema.org extra: listas, datasets, vocabularios
 *   pagina_extra   campos que se mezclan en el nodo de la propia pagina (about...)
 *
 * Recibe $base, $version y $seo.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/lib/seo.php';

$seo = $seo ?? [];

$seo_url         = (string) ($seo['url'] ?? rtrim($base, '/') . '/');
$seo_titulo      = (string) ($seo['titulo'] ?? SEO_NOMBRE);
$seo_social      = (string) ($seo['titulo_social'] ?? $seo_titulo);
$seo_desc        = trim((string) ($seo['descripcion'] ?? 'Agregador de tecnología hotelera en español: lo que aparece cada día en el radar del sector.'));
$seo_tipo        = (string) ($seo['tipo'] ?? 'website');
$seo_theme       = (string) ($seo['theme'] ?? '#12100d');
$seo_robots      = (string) ($seo['robots'] ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1');
$seo_imagen      = (string) ($seo['imagen'] ?? web_url_imagen_generica($base));
$seo_imagen_alt  = (string) ($seo['imagen_alt'] ?? 'Bit & Breakfast, el radar de tecnología hotelera en español');
$seo_rss         = $seo['rss'] ?? ['titulo' => SEO_NOMBRE, 'url' => rtrim($base, '/') . '/feed.xml'];
$seo_publicado   = (string) ($seo['publicado'] ?? '');
$seo_modificado  = (string) ($seo['modificado'] ?? '');
$seo_indexable   = !str_contains($seo_robots, 'noindex');

// El grafo: organizacion y sitio en todas las paginas, la pagina en si y lo
// que traiga cada una. Una pagina que no se indexa no necesita nada de esto.
$seo_grafo = [];

if ($seo_indexable) {
    $seo_grafo[] = seo_organizacion($base);
    $seo_grafo[] = seo_sitio($base);
    $seo_grafo[] = seo_pagina([
        'url'         => $seo_url,
        'nombre'      => $seo_social,
        'descripcion' => $seo_desc,
        'tipo'        => (string) ($seo['tipo_pagina'] ?? 'WebPage'),
        'imagen'      => $seo_imagen,
        'publicado'   => $seo_publicado,
        'modificado'  => $seo_modificado,
        'extra'       => (array) ($seo['pagina_extra'] ?? []),
    ], $base);

    foreach ((array) ($seo['grafo'] ?? []) as $nodo) {
        $seo_grafo[] = $nodo;
    }
}

?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= web_e($seo_titulo) ?></title>
<meta name="description" content="<?= web_e($seo_desc) ?>">
<link rel="canonical" href="<?= web_e($seo_url) ?>">
<meta name="robots" content="<?= web_e($seo_robots) ?>">
<?php if ($seo_indexable): ?>
<link rel="alternate" hreflang="es" href="<?= web_e($seo_url) ?>">
<link rel="alternate" hreflang="x-default" href="<?= web_e($seo_url) ?>">
<?php endif; ?>
<link rel="alternate" type="application/rss+xml" title="<?= web_e((string) $seo_rss['titulo']) ?>" href="<?= web_e((string) $seo_rss['url']) ?>">
<link rel="icon" href="<?= web_e($base) ?>/favicon.svg" type="image/svg+xml">
<link rel="preload" href="<?= web_e($base) ?>/fuentes/BigShoulders-Titular-Bold.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version ?? '0') ?>">
<meta name="theme-color" content="<?= web_e($seo_theme) ?>">
<meta property="og:site_name" content="<?= web_e(SEO_NOMBRE) ?>">
<meta property="og:locale" content="es_ES">
<meta property="og:type" content="<?= web_e($seo_tipo) ?>">
<meta property="og:title" content="<?= web_e($seo_social) ?>">
<meta property="og:description" content="<?= web_e($seo_desc) ?>">
<meta property="og:url" content="<?= web_e($seo_url) ?>">
<meta property="og:image" content="<?= web_e($seo_imagen) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="<?= web_e($seo_imagen_alt) ?>">
<?php if ($seo_tipo === 'article' && $seo_publicado !== ''): ?>
<meta property="article:published_time" content="<?= web_e($seo_publicado) ?>">
<?php if ($seo_modificado !== ''): ?>
<meta property="article:modified_time" content="<?= web_e($seo_modificado) ?>">
<?php endif; ?>
<meta property="article:section" content="Tecnología hotelera">
<?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= web_e($seo_social) ?>">
<meta name="twitter:description" content="<?= web_e($seo_desc) ?>">
<meta name="twitter:image" content="<?= web_e($seo_imagen) ?>">
<meta name="twitter:image:alt" content="<?= web_e($seo_imagen_alt) ?>">
<?php if ($seo_grafo): ?>
<?= seo_json($seo_grafo) . "\n" ?>
<?php endif; ?>
