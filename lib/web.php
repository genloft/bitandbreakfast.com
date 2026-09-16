<?php
/**
 * Utilidades de la web generada.
 *
 * Calculo puro: fechas en espanol, rutas de los ficheros que se escriben y
 * escapado. El generador vive en cron/publicar.php y las plantillas en
 * plantillas/web/; aqui esta lo que las dos necesitan y se puede probar sin
 * montar nada.
 */

declare(strict_types=1);

/** Meses en espanol. No se usa strftime: esta obsoleta desde PHP 8.1. */
function web_meses(): array
{
    return [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];
}

/**
 * "22 de septiembre de 2026" a partir de una fecha de la base.
 */
function web_fecha_larga(string $fecha): string
{
    $tiempo = strtotime($fecha . ' UTC');

    if ($tiempo === false) {
        return '';
    }

    $meses = web_meses();

    return sprintf(
        '%d de %s de %d',
        (int) gmdate('j', $tiempo),
        $meses[(int) gmdate('n', $tiempo)],
        (int) gmdate('Y', $tiempo)
    );
}

/**
 * Fecha en el formato que exige RSS 2.0 (RFC 822).
 */
function web_fecha_rss(string $fecha): string
{
    $tiempo = strtotime($fecha . ' UTC');

    return gmdate('D, d M Y H:i:s +0000', $tiempo === false ? time() : $tiempo);
}

/**
 * Ruta relativa, dentro de publico/, del fichero de una edicion.
 *
 * Cada edicion vive en su propia carpeta con un index.html dentro, para que
 * la direccion publica sea /e/<slug>/ y no /e/<slug>.html. Una URL sin
 * extension no delata con que se genero y no hay que cambiarla el dia que
 * deje de ser un fichero.
 */
function web_ruta_edicion(string $slug): string
{
    return 'e/' . web_slug_seguro($slug) . '/index.html';
}

function web_url_edicion(string $base, string $slug): string
{
    return rtrim($base, '/') . '/e/' . web_slug_seguro($slug) . '/';
}

/**
 * Limpia un slug antes de convertirlo en ruta de fichero.
 *
 * El slug lo genera el sistema, no un formulario, pero de aqui sale un
 * mkdir: cualquier cosa que no sea letra, cifra o guion se queda fuera, y
 * con ella cualquier intento de subir de directorio.
 */
function web_slug_seguro(string $slug): string
{
    $limpio = preg_replace('/[^a-z0-9-]+/', '-', strtolower($slug));
    $limpio = trim((string) $limpio, '-');

    return $limpio === '' ? 'edicion' : $limpio;
}

/**
 * Escapa para HTML. Lleva prefijo como el resto del proyecto: una funcion
 * global llamada h() colisiona con cualquier cosa tarde o temprano.
 */
function web_e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Parrafos a partir de texto plano: una linea en blanco separa parrafos.
 *
 * El cuerpo de un bit se escribe en un textarea, sin HTML. Aqui se convierte
 * en marcado, y lo unico que se permite es el parrafo: ni enlaces, ni
 * negritas, ni nada que pueda llegar del formulario sin revisar.
 */
function web_parrafos(string $texto): string
{
    $bloques = preg_split('/\n\s*\n/', trim($texto)) ?: [];
    $html    = '';

    foreach ($bloques as $bloque) {
        $bloque = trim($bloque);

        if ($bloque === '') {
            continue;
        }

        $html .= '<p>' . nl2br(web_e($bloque), false) . "</p>\n";
    }

    return $html;
}

/**
 * Minutos de lectura, redondeando hacia arriba, a 200 palabras por minuto.
 */
function web_minutos(int $palabras): int
{
    return max(1, (int) ceil($palabras / 200));
}
