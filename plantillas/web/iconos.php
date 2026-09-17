<?php
/**
 * Iconos: un SVG por tematica, dibujados aqui mismo.
 *
 * En linea y no como fichero de iconos por tres motivos, en este orden:
 *
 *   1. La politica de seguridad del sitio no permite cargar nada de fuera, y
 *      un sprite externo es una peticion mas que puede fallar y dejar la
 *      pagina con huecos.
 *   2. Un icono en linea hereda el color del texto, asi que cada tematica
 *      pinta el suyo sin tener que mantener once ficheros de colores.
 *   3. Pesan menos que la peticion que costaria traerlos.
 *
 * Son trazos, no siluetas: a 20 pixeles y sobre fondo oscuro, una silueta
 * rellena se convierte en una mancha. Todos comparten caja de 24 y grosor de
 * linea, que es lo que hace que parezcan de la misma familia y no once iconos
 * distintos puestos juntos.
 *
 * Decorativos a proposito: el nombre de la tematica va escrito al lado, asi
 * que el icono no anade informacion y no debe leerlo nadie dos veces.
 */

declare(strict_types=1);

/**
 * El dibujo de una tematica. Devuelve el SVG entero, listo para pintar.
 */
function web_icono(string $categoria, string $clase = 'icono'): string
{
    $trazos = web_iconos()[$categoria] ?? web_iconos()['tecnologia-general'];

    return '<svg class="' . htmlspecialchars($clase, ENT_QUOTES) . '" viewBox="0 0 24 24" '
         . 'fill="none" stroke="currentColor" stroke-width="1.5" '
         . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
         . $trazos
         . '</svg>';
}

/**
 * Los trazos de cada tematica.
 *
 * El dibujo intenta ser lo que la tematica es para un hotel, no la metafora
 * mas bonita: PMS y CRS es el timbre del mostrador, distribucion es un nodo
 * que reparte, revenue es una curva que sube.
 */
function web_iconos(): array
{
    return [
        // Un chip: lo generico de verdad.
        'tecnologia-general' => '<rect x="7" y="7" width="10" height="10" rx="1.5"/>'
            . '<path d="M10 3v4M14 3v4M10 17v4M14 17v4M3 10h4M3 14h4M17 10h4M17 14h4"/>',

        // El timbre del mostrador de recepcion.
        'pms-crs' => '<path d="M4 17h16"/><path d="M6 14a6 6 0 0 1 12 0"/>'
            . '<path d="M12 8V6"/><circle cx="12" cy="5" r="1"/><path d="M4 20h16"/>',

        // Un nodo que reparte a tres sitios: eso es la distribucion.
        'distribucion-otas' => '<circle cx="5" cy="12" r="2"/><circle cx="19" cy="6" r="2"/>'
            . '<circle cx="19" cy="18" r="2"/><path d="M7 11l10-4M7 13l10 4"/>',

        // La curva que sube, que es de lo que va el revenue.
        'revenue-rms' => '<path d="M4 19V5"/><path d="M4 19h16"/>'
            . '<path d="M7 15l4-4 3 3 5-6"/><path d="M19 8V5h-3"/>',

        // Una tarjeta.
        'pagos-fraude' => '<rect x="2.5" y="5.5" width="19" height="13" rx="2"/>'
            . '<path d="M2.5 10h19"/><path d="M6 15h4"/>',

        // Un escudo, que es lo unico que se entiende en dos centimetros.
        'ciberseguridad-cumplimiento' => '<path d="M12 3l7 3v5.5c0 4.3-2.9 8.2-7 9.5-4.1-1.3-7-5.2-7-9.5V6l7-3z"/>'
            . '<path d="M9 12l2 2 4-4"/>',

        // Engranaje: operaciones.
        'operaciones-iot' => '<circle cx="12" cy="12" r="3"/>'
            . '<path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M19.1 4.9L17 7M7 17l-2.1 2.1"/>',

        // Una cama: el huesped.
        'experiencia-huesped' => '<path d="M3 18v-7"/><path d="M3 13h18v5"/>'
            . '<path d="M21 18v-4a3 3 0 0 0-3-3H3"/><circle cx="7.5" cy="8.5" r="1.8"/>',

        // Destellos: es lo que todo el mundo entiende ya por IA.
        'ia-aplicada' => '<path d="M12 3l1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3z"/>'
            . '<path d="M18 15l.8 2.2L21 18l-2.2.8L18 21l-.8-2.2L15 18l2.2-.8L18 15z"/>',

        // Una hoja.
        'sostenibilidad-energia' => '<path d="M20 4C10 4 4 9 4 16c0 2 .6 3.4.6 3.4S8 12 20 4z"/>'
            . '<path d="M4.6 19.4C7 20 18 20 20 4"/>',

        // Monedas apiladas: inversion.
        'inversion-mercado' => '<ellipse cx="12" cy="6" rx="7" ry="2.5"/>'
            . '<path d="M5 6v5c0 1.4 3.1 2.5 7 2.5s7-1.1 7-2.5V6"/>'
            . '<path d="M5 11v5c0 1.4 3.1 2.5 7 2.5s7-1.1 7-2.5v-5"/>',
    ];
}

/**
 * Iconos que no son de tematica: los tres o cuatro que usa la interfaz.
 */
function web_icono_ui(string $nombre, string $clase = 'icono'): string
{
    $trazos = [
        'reloj'  => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3 2"/>',
        'medio'  => '<path d="M4 5h11v14H4z"/><path d="M15 9h5v8a2 2 0 0 1-2 2h-3"/>'
                  . '<path d="M7 9h5M7 12h5M7 15h3"/>',
        'flecha' => '<path d="M5 12h13"/><path d="M13 6l6 6-6 6"/>',
        'lupa'   => '<circle cx="11" cy="11" r="6.5"/><path d="M16 16l4 4"/>',
    ][$nombre] ?? '';

    if ($trazos === '') {
        return '';
    }

    return '<svg class="' . htmlspecialchars($clase, ENT_QUOTES) . '" viewBox="0 0 24 24" '
         . 'fill="none" stroke="currentColor" stroke-width="1.5" '
         . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
         . $trazos
         . '</svg>';
}
