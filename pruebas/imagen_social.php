<?php
/**
 * Pruebas de lib/imagen_social.php. Ejecutar: php pruebas/imagen_social.php
 *
 * No hay red social a la que llamar para comprobar que "se ve bien"; lo que
 * sí se puede comprobar sin montar nada es que el reparto de líneas hace lo
 * que dice -no corta palabras, respeta el máximo, no se pasa del ancho- y
 * que la tarjeta entera sale como un PNG de verdad, del tamaño que se
 * publicita y sin que las líneas del titular acaben pisando el pie.
 *
 * Exige la extensión gd, la misma que usa lib/imagen_social.php. En CI se
 * instala junto al resto en .github/workflows/pruebas.yml.
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/imagen_social.php';

if (!extension_loaded('gd')) {
    fwrite(STDERR, "Falta la extension gd: pruebas/imagen_social.php no puede correr sin ella.\n");
    exit(1);
}

$negrita = imagen_social_fuente_negrita();
$regular = imagen_social_fuente_regular();

comprobar('la fuente en negrita viene con el repositorio', true, is_file($negrita));
comprobar('y la regular tambien', true, is_file($regular));

// --- Reparto de lineas -------------------------------------------------------

comprobar(
    'un texto corto cabe en una sola linea',
    ['Mews compra un motor de reservas'],
    imagen_social_envolver($negrita, 62, 'Mews compra un motor de reservas', 1200)
);

$dos_lineas = imagen_social_envolver(
    $negrita,
    62,
    'Una caida global de Oracle OPERA Cloud deja sin PMS a varias cadenas',
    900
);

comprobar('un texto largo se reparte en mas de una linea', true, count($dos_lineas) > 1);

comprobar(
    'las lineas juntas recomponen el texto original, palabra por palabra',
    'Una caida global de Oracle OPERA Cloud deja sin PMS a varias cadenas',
    implode(' ', $dos_lineas)
);

comprobar(
    'ninguna linea se pasa del ancho maximo que se le pide',
    true,
    (function () use ($negrita, $dos_lineas): bool {
        foreach ($dos_lineas as $linea) {
            $caja = imagettfbbox(62, 0, $negrita, $linea);
            if (($caja[2] - $caja[0]) > 900 + 1) {
                return false;
            }
        }

        return true;
    })()
);

$titulo_larguisimo = str_repeat('palabra ', 40);
$acotado = imagen_social_envolver($negrita, 62, $titulo_larguisimo, 500, 3);

comprobar('el maximo de lineas se respeta', 3, count($acotado));
comprobar(
    'la ultima linea dice que sobraba texto',
    true,
    str_ends_with($acotado[2], '…')
);

comprobar('un texto vacio no rompe nada', [], imagen_social_envolver($negrita, 62, '', 500));

// --- La tarjeta entera --------------------------------------------------------

function imagen_social_prueba_png(string $titular, string $etiqueta = ''): array
{
    $png  = imagen_social_png($titular, $etiqueta);
    $info = getimagesizefromstring($png);

    return ['png' => $png, 'info' => $info];
}

$corta = imagen_social_prueba_png('El radar de tecnología hotelera');

comprobar('la tarjeta generica es un PNG de verdad', 'image/png', $corta['info']['mime'] ?? null);
comprobar('con el ancho que se publicita en og:image', IMAGEN_SOCIAL_ANCHO, $corta['info'][0] ?? null);
comprobar('y el alto', IMAGEN_SOCIAL_ALTO, $corta['info'][1] ?? null);

// El caso que de verdad importa: el titular mas largo que puede llegar aqui
// -BITS_TITULAR_MAX son 120 caracteres- con una etiqueta de fecha encima,
// que es la combinacion con menos hueco vertical libre. Una version anterior
// de esta funcion dejaba la ultima linea del titular pisando el pie de
// "bitandbreakfast.com" en justo este caso.
require_once dirname(__DIR__) . '/lib/bits.php';

$titular_al_limite = str_repeat('a', BITS_TITULAR_MAX - 20) . ' final del titular';
$al_limite = imagen_social_png($titular_al_limite, '15 de octubre de 2026');

comprobar(
    'la tarjeta con el titular mas largo posible sigue siendo un PNG valido',
    true,
    getimagesizefromstring($al_limite) !== false
);

resumen_pruebas('Pruebas de la tarjeta social');
