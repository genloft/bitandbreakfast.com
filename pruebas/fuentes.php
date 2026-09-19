<?php
/**
 * Pruebas de lib/fuentes.php.  Ejecutar:  php pruebas/fuentes.php
 *
 * Lo que valida antes de que una fuente nueva -desde el panel o desde una
 * migracion- llegue a la base: que el catalogo (tipo, idioma, region,
 * categoria) sea uno de los que el resto del sitio reconoce, y no un valor
 * inventado que luego no filtra ni se pinta.
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/fuentes.php';

function fuente_de(array $cambios = []): array
{
    return array_merge([
        'nombre'            => 'Hotel News Test',
        'url_feed'          => 'https://ejemplo.test/feed/',
        'url_sitio'         => 'https://ejemplo.test/',
        'tipo'              => 'prensa',
        'idioma'            => 'en',
        'region'            => 'global',
        'categoria_defecto' => 'tecnologia-general',
        'peso'              => 5,
        'notas'             => '',
    ], $cambios);
}

comprobar('una fuente completa y correcta no da ningun error', [], fuentes_validar(fuente_de()));

comprobar(
    'sin nombre, no vale',
    1,
    count(fuentes_validar(fuente_de(['nombre' => '  '])))
);

comprobar(
    'un nombre demasiado largo, no vale',
    1,
    count(fuentes_validar(fuente_de(['nombre' => str_repeat('a', FUENTES_NOMBRE_MAX + 1)])))
);

comprobar('sin url de feed, no vale', 1, count(fuentes_validar(fuente_de(['url_feed' => '']))));

comprobar(
    'una url que no es url, no vale',
    1,
    count(fuentes_validar(fuente_de(['url_feed' => 'esto no es una url'])))
);

comprobar(
    'una url sin esquema http, no vale',
    1,
    count(fuentes_validar(fuente_de(['url_feed' => 'ftp://ejemplo.test/feed'])))
);

comprobar(
    'un tipo que no esta en el catalogo, no vale',
    1,
    count(fuentes_validar(fuente_de(['tipo' => 'blog'])))
);

comprobar(
    'un idioma que no son dos letras minusculas, no vale',
    1,
    count(fuentes_validar(fuente_de(['idioma' => 'ESP'])))
);

comprobar(
    'un ambito que no esta en el catalogo, no vale',
    1,
    count(fuentes_validar(fuente_de(['region' => 'latam'])))
);

comprobar(
    'una categoria que no esta en el catalogo, no vale',
    1,
    count(fuentes_validar(fuente_de(['categoria_defecto' => 'inventada'])))
);

comprobar('un peso de cero, no vale', 1, count(fuentes_validar(fuente_de(['peso' => 0]))));
comprobar('un peso de once, no vale', 1, count(fuentes_validar(fuente_de(['peso' => 11]))));
comprobar('un peso que no es numero, no vale', 1, count(fuentes_validar(fuente_de(['peso' => 'alto']))));

comprobar(
    'unas notas demasiado largas, no valen',
    1,
    count(fuentes_validar(fuente_de(['notas' => str_repeat('a', FUENTES_NOTAS_MAX + 1)])))
);

comprobar(
    'todos los tipos del catalogo tienen nombre',
    9,
    count(fuentes_tipos())
);

comprobar(
    'todos los ambitos del catalogo tienen nombre',
    3,
    count(fuentes_regiones())
);

resumen_pruebas('Pruebas de lib/fuentes.php');
