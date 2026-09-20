<?php
/**
 * La tarjeta que ve quien recibe un enlace por WhatsApp o LinkedIn, en vez de
 * un enlace desnudo con og:image vacío.
 *
 * No es una foto: papel, un filete, el sello y el titular, el mismo
 * vocabulario que ya tiene la web, dibujado en un lienzo de 1200x630. "Sin
 * fotos, y a proposito" -docs/README.md- sigue siendo verdad; lo que faltaba
 * no era una imagen bonita, era decir algo cuando una red social pide una.
 *
 * Y tiene que ser PNG, no SVG: ninguna red que enseña vista previa -WhatsApp,
 * LinkedIn, Facebook, X- rasteriza SVG en og:image, así que el mismo truco
 * que sirve para favicon.svg no sirve aquí. La tipografía es Big Shoulders
 * (SIL Open Font License, en plantillas/web/fuentes/), condensada como pide
 * --titular en estilo.php, y solo se usa aquí: no se carga en el navegador,
 * así que no toca la regla de "nada de Google Fonts" del CSP.
 */

declare(strict_types=1);

const IMAGEN_SOCIAL_ANCHO = 1200;
const IMAGEN_SOCIAL_ALTO  = 630;

function imagen_social_fuente_negrita(): string
{
    return dirname(__DIR__) . '/plantillas/web/fuentes/BigShoulders-Bold.ttf';
}

function imagen_social_fuente_regular(): string
{
    return dirname(__DIR__) . '/plantillas/web/fuentes/BigShoulders-Regular.ttf';
}

/**
 * Reparte un texto en líneas que quepan en $ancho_max con esa fuente y ese
 * tamaño. Pura -no crea ninguna imagen, solo mide-, para poder probarla sin
 * generar nada.
 *
 * @return string[] Como mucho $maximo líneas; si sobraba texto, la última
 *                   lleva "…".
 */
function imagen_social_envolver(string $fuente, float $tamano, string $texto, int $ancho_max, int $maximo = 4): array
{
    $palabras = preg_split('/\s+/', trim($texto)) ?: [];
    $lineas   = [];
    $actual   = '';

    foreach ($palabras as $palabra) {
        if ($palabra === '') {
            continue;
        }

        $prueba = $actual === '' ? $palabra : $actual . ' ' . $palabra;
        $caja   = imagettfbbox($tamano, 0, $fuente, $prueba);
        $ancho  = $caja[2] - $caja[0];

        if ($ancho > $ancho_max && $actual !== '') {
            $lineas[] = $actual;
            $actual   = $palabra;
        } else {
            $actual = $prueba;
        }
    }

    if ($actual !== '') {
        $lineas[] = $actual;
    }

    if (count($lineas) > $maximo) {
        $lineas = array_slice($lineas, 0, $maximo);
        $lineas[$maximo - 1] = rtrim($lineas[$maximo - 1]) . '…';
    }

    return $lineas;
}

/**
 * Centra un texto en ($cx, $cy). Hace falta el bounding box entero -no basta
 * la anchura- porque imagettftext posiciona por la línea de base, y una "B"
 * mayúscula no arranca a la misma altura a la que baja una "g".
 */
function imagen_social_texto_centrado(
    GdImage $lienzo,
    string $fuente,
    float $tamano,
    int $color,
    int $cx,
    int $cy,
    string $texto
): void {
    $caja  = imagettfbbox($tamano, 0, $fuente, $texto);
    $ancho = $caja[2] - $caja[0];
    $alto  = $caja[1] - $caja[7];
    $x     = (int) round($cx - $ancho / 2 - $caja[0]);
    $y     = (int) round($cy + $alto / 2 - $caja[1]);

    imagettftext($lienzo, $tamano, 0, $x, $y, $color, $fuente, $texto);
}

/**
 * La tarjeta tipográfica entera, lista para escribir a fichero con
 * publicar_escribir() -que es binario-safe, así que un PNG le vale igual que
 * el HTML de siempre-.
 *
 * $etiqueta es el rótulo pequeño encima del titular -la fecha, en una
 * tarjeta de día-; vacío, no se pinta.
 *
 * @return string El PNG entero.
 */
function imagen_social_png(string $titular, string $etiqueta = ''): string
{
    $ancho  = IMAGEN_SOCIAL_ANCHO;
    $alto   = IMAGEN_SOCIAL_ALTO;
    $margen = 72;

    $lienzo = imagecreatetruecolor($ancho, $alto);

    // Los mismos --papel, --tinta, --apagado y --filete-fino de
    // plantillas/web/estilo.php: la tarjeta tiene que ser reconocible como
    // del mismo sitio, no una pieza de marketing aparte.
    $papel       = imagecolorallocate($lienzo, 0xf4, 0xf2, 0xee);
    $tinta       = imagecolorallocate($lienzo, 0x0d, 0x0d, 0x0d);
    $apagado     = imagecolorallocate($lienzo, 0x5f, 0x5c, 0x57);
    $filete_fino = imagecolorallocate($lienzo, 0xcf, 0xca, 0xc2);

    imagefilledrectangle($lienzo, 0, 0, $ancho, $alto, $papel);

    $negrita = imagen_social_fuente_negrita();
    $regular = imagen_social_fuente_regular();

    // El sello: el mismo círculo negro con la "B" que favicon.svg y la
    // cabecera de la web -"un círculo negro con la inicial", cabecera.php-.
    // La tarjeta reutiliza el dibujo en vez de inventar uno nuevo.
    $radio = 44;
    $cx    = $margen + $radio;
    $cy    = $margen + $radio;
    imagefilledellipse($lienzo, $cx, $cy, $radio * 2, $radio * 2, $tinta);
    imagen_social_texto_centrado($lienzo, $negrita, 40, $papel, $cx, $cy + 2, 'B');

    // El nombre, al lado del sello. En la propia web el sello basta -el
    // contexto ya dice dónde está quien mira-, pero una tarjeta que circula
    // suelta por WhatsApp no tiene ese contexto.
    imagettftext($lienzo, 30, 0, $cx + $radio + 24, $cy + 11, $tinta, $regular, 'BIT & BREAKFAST');

    // El filete: la misma retícula de líneas finas que separa secciones en
    // toda la web, aquí separando la cabecera del titular.
    $y_filete = $margen + $radio * 2 + 36;
    imagefilledrectangle($lienzo, $margen, $y_filete, $ancho - $margen, $y_filete + 2, $filete_fino);

    $y_cursor = $y_filete + 56;

    if ($etiqueta !== '') {
        imagettftext($lienzo, 26, 0, $margen, $y_cursor, $apagado, $regular, mb_strtoupper($etiqueta, 'UTF-8'));
        $y_cursor += 50;
    }

    // El tamaño se elige, no se da por hecho: cuatro líneas de un titular
    // largo a 62px no caben en el hueco que queda encima del pie sin pisarlo
    // -pasó en la prueba con el titular más largo que admite BITS_TITULAR_MAX-,
    // así que se prueba de mayor a menor y se toma el primero que sí quepa.
    $ancho_util  = $ancho - $margen * 2;
    $y_pie       = $alto - $margen + 8;
    $limite_bajo = $y_pie - 34;
    $disponible  = max(0, $limite_bajo - $y_cursor);

    $tamano_titular = 40;
    $lineas         = imagen_social_envolver($negrita, 40, $titular, $ancho_util, 4);

    foreach ([62, 56, 50, 44, 40] as $intento) {
        $candidatas   = imagen_social_envolver($negrita, $intento, $titular, $ancho_util, 4);
        $interlineado = (int) round($intento * 1.28);

        if (count($candidatas) * $interlineado <= $disponible || $intento === 40) {
            $tamano_titular = $intento;
            $lineas         = $candidatas;
            break;
        }
    }

    $interlineado = (int) round($tamano_titular * 1.28);

    foreach ($lineas as $linea) {
        $y_cursor += $interlineado;
        imagettftext($lienzo, $tamano_titular, 0, $margen, $y_cursor, $tinta, $negrita, $linea);
    }

    // La dirección, pequeña y abajo: quien reciba solo la imagen -una
    // captura, un reenvío sin el enlace- sigue sabiendo de dónde sale.
    imagettftext($lienzo, 22, 0, $margen, $alto - $margen + 8, $apagado, $regular, 'bitandbreakfast.com');

    ob_start();
    imagepng($lienzo);
    $png = (string) ob_get_clean();

    imagedestroy($lienzo);

    return $png;
}
