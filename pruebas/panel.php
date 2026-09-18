<?php
/**
 * Pruebas de lib/bits.php.  Ejecutar:  php pruebas/panel.php
 *
 * Las reglas del bit y de la edicion, que es lo que el panel no puede dejar
 * pasar: un titular que no cabe en un asunto de correo, un cuerpo que rompe
 * la promesa de los cinco minutos, o una edicion con algo a medias dentro.
 *
 * Sin base de datos: la sesion y las consultas viven en lib/panel.php y
 * panel/datos.php, y aqui solo entra lo que se puede probar sin servidor.
 * La unica excepcion es datos_formatear_candidato(), que vive en
 * panel/datos.php pero no toca la base: solo da forma a lo que ya
 * devuelven datos_cola() y datos_items_racimo(), asi que se prueba aqui.
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/bits.php';
require_once dirname(__DIR__) . '/panel/datos.php';

/**
 * Un cuerpo de las palabras que se pidan, para no contarlas a mano.
 */
function palabras(int $cuantas): string
{
    return trim(str_repeat('palabra ', $cuantas));
}

$valido = [
    'titular'   => 'Oracle OPERA Cloud sufre una caida global de cuatro horas',
    'cuerpo'    => palabras(40),
    'por_que'   => 'Si tu PMS es OPERA Cloud, esto explica por que el jueves no pudiste hacer check-in.',
    'categoria' => 'pms-crs',
    'madurez'   => 'anuncio',
    'tipo'      => 'incidente',
];

// --- Validacion de un bit ---------------------------------------------------

comprobar('un bit bien escrito no da errores', [], bits_validar($valido));

comprobar(
    'el titular no puede quedarse vacio',
    1,
    count(bits_validar(['titular' => '   '] + $valido))
);

comprobar(
    'el titular no puede pasar del limite',
    1,
    count(bits_validar(['titular' => str_repeat('a', BITS_TITULAR_MAX + 1)] + $valido))
);

comprobar(
    'el titular justo en el limite pasa',
    [],
    bits_validar(['titular' => str_repeat('a', BITS_TITULAR_MAX)] + $valido)
);

// Los acentos cuentan como un caracter, no como dos: mb_strlen y no strlen.
comprobar(
    'un titular lleno de acentos se mide en caracteres, no en bytes',
    [],
    bits_validar(['titular' => str_repeat('á', BITS_TITULAR_MAX)] + $valido)
);

comprobar(
    'un cuerpo demasiado corto se rechaza',
    1,
    count(bits_validar(['cuerpo' => palabras(BITS_CUERPO_MIN - 1)] + $valido))
);

comprobar(
    'un cuerpo demasiado largo se rechaza',
    1,
    count(bits_validar(['cuerpo' => palabras(BITS_CUERPO_MAX + 1)] + $valido))
);

comprobar(
    'un cuerpo en el minimo justo pasa',
    [],
    bits_validar(['cuerpo' => palabras(BITS_CUERPO_MIN)] + $valido)
);

comprobar(
    'sin el por que importa no hay bit',
    1,
    count(bits_validar(['por_que' => ''] + $valido))
);

comprobar(
    'la categoria tiene que estar en el catalogo',
    1,
    count(bits_validar(['categoria' => 'lo-que-sea'] + $valido))
);

comprobar(
    'la madurez tiene que estar en el catalogo',
    1,
    count(bits_validar(['madurez' => 'inventada'] + $valido))
);

comprobar(
    'un bit vacio del todo acumula todos los errores',
    6,
    count(bits_validar([]))
);

// --- Revision de la edicion -------------------------------------------------

$conf = ['edicion_max_bits' => 20, 'edicion_cuota_es_eu' => 30];

function bit_de(string $estado, string $region, int $palabras = 40): array
{
    return ['estado' => $estado, 'region' => $region, 'cuerpo' => palabras($palabras)];
}

$edicion_buena = [
    bit_de('aprobado', 'es'),
    bit_de('aprobado', 'eu'),
    bit_de('aprobado', 'global'),
    bit_de('aprobado', 'global'),
];

$revision = bits_revisar_edicion($edicion_buena, $conf);

comprobar('una edicion correcta se puede cerrar', [], $revision['errores']);
comprobar('la mitad de fuentes europeas no levanta aviso de cuota', [], $revision['avisos']);
comprobar('cuenta las palabras de toda la edicion', 160, $revision['palabras']);

comprobar(
    'una edicion vacia no se cierra',
    1,
    count(bits_revisar_edicion([], $conf)['errores'])
);

comprobar(
    'un borrador suelto impide cerrar',
    1,
    count(bits_revisar_edicion(
        array_merge($edicion_buena, [bit_de('borrador', 'es')]),
        $conf
    )['errores'])
);

// El recuento tiene que mirar la edicion entera aunque encuentre un borrador
// en la primera posicion: si se cortara ahi, el aviso de cuota saldria mal.
comprobar(
    'un borrador el primero no corta el recuento de palabras',
    200,
    bits_revisar_edicion(
        array_merge([bit_de('borrador', 'es')], $edicion_buena),
        $conf
    )['palabras']
);

comprobar(
    'pasarse del tope de bits impide cerrar',
    1,
    count(bits_revisar_edicion(array_fill(0, 21, bit_de('aprobado', 'es')), $conf)['errores'])
);

// La cuota es intencion editorial, no regla: avisa pero deja cerrar.
$floja = bits_revisar_edicion([
    bit_de('aprobado', 'es'),
    bit_de('aprobado', 'global'),
    bit_de('aprobado', 'global'),
    bit_de('aprobado', 'global'),
], $conf);

comprobar('pocas fuentes europeas avisan', 1, count($floja['avisos']));
comprobar('pero no impiden cerrar', [], $floja['errores']);

// Cinco minutos de lectura son unas mil palabras.
comprobar(
    'una edicion larguisima avisa de que rompe los cinco minutos',
    1,
    count(bits_revisar_edicion(array_fill(0, 15, bit_de('aprobado', 'es', 100)), $conf)['avisos'])
);

// --- Slug de la edicion -----------------------------------------------------

comprobar(
    'el slug lleva el ano, la semana ISO y el numero',
    '2026-w39-038',
    bits_slug_edicion(38, '2026-09-22')
);

comprobar(
    'la semana va con dos cifras',
    '2026-w02-002',
    bits_slug_edicion(2, '2026-01-05')
);

// --- El candidato tal como lo manda api/candidatos.php -----------------------

$racimo_candidato = [
    'id' => 9, 'titulo_representativo' => 'Oracle OPERA Cloud se cae en Europa',
    'puntuacion' => 42, 'fuentes' => 2,
    'primer_visto' => '2026-09-18 08:00:00', 'ultimo_visto' => '2026-09-18 10:00:00',
];

$items_candidato = [
    ['titulo' => 'Caida global', 'url' => 'https://a.test/x', 'fuente' => 'Skift', 'idioma' => 'en', 'resumen_origen' => 'x', 'publicado' => '2026-09-18 07:50:00'],
];

$candidato_formateado = datos_formatear_candidato($racimo_candidato, $items_candidato);

comprobar('el candidato lleva su id como entero', 9, $candidato_formateado['id']);
comprobar('y el titulo', 'Oracle OPERA Cloud se cae en Europa', $candidato_formateado['titulo']);
comprobar('y sus items, en la misma forma', 1, count($candidato_formateado['items']));
comprobar('con la fuente de cada item', 'Skift', $candidato_formateado['items'][0]['fuente']);

comprobar(
    'sin items, la lista sale vacia, no ausente',
    [],
    datos_formatear_candidato($racimo_candidato, [])['items']
);

resumen_pruebas('Pruebas de la fase 3: reglas del bit y de la edicion');
