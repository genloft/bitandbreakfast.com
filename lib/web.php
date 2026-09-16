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

require_once __DIR__ . '/texto.php';

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
 * Fila del indice de busqueda a partir de un bit publicado.
 *
 * Las claves son de una letra a proposito: el indice se descarga entero en el
 * navegador y con mil bits la diferencia entre "titular" y "t" son cuarenta
 * kilobytes de nombres de campo repetidos.
 *
 * El campo 'b' es el texto sobre el que se busca, ya normalizado aqui con
 * texto_normalizar: asi el navegador solo tiene que normalizar lo que escribe
 * el lector, y las dos partes usan exactamente las mismas reglas.
 */
function web_fila_indice(array $bit): array
{
    $proveedores = web_proveedores($bit['proveedores'] ?? null);
    $nombres     = implode(' ', array_column($proveedores, 'nombre'));

    $buscable = texto_normalizar(
        (string) $bit['titular'] . ' ' .
        (string) ($bit['por_que'] ?? '') . ' ' .
        (string) ($bit['cuerpo'] ?? '') . ' ' .
        $nombres
    );

    return [
        'i' => (int) $bit['id'],
        't' => (string) $bit['titular'],
        'q' => (string) ($bit['por_que'] ?? ''),
        'c' => (string) $bit['categoria'],
        'n' => (int) $bit['numero'],
        's' => (string) $bit['slug'],
        'f' => (string) $bit['fecha_prevista'],
        // La fecha ya escrita: asi el buscador no repite los nombres de los
        // meses en JavaScript ni se arriesga a que los dos formatos difieran.
        'd' => web_fecha_larga((string) $bit['fecha_prevista']),
        'v' => $nombres,
        'b' => $buscable,
    ];
}

/**
 * Deshace la lista de proveedores que trae el generador en una sola columna.
 *
 * Viene como "slug|Nombre;;slug|Nombre" porque sacarla con un GROUP_CONCAT
 * evita una consulta por bit. Aqui vuelve a ser una lista.
 *
 * @return array Filas con 'slug' y 'nombre'.
 */
function web_proveedores(?string $empaquetados): array
{
    $empaquetados = trim((string) $empaquetados);

    if ($empaquetados === '') {
        return [];
    }

    $lista = [];

    foreach (explode(';;', $empaquetados) as $trozo) {
        $partes = explode('|', $trozo, 2);

        if (count($partes) !== 2 || $partes[0] === '') {
            continue;
        }

        $lista[] = ['slug' => $partes[0], 'nombre' => $partes[1]];
    }

    return $lista;
}

/**
 * Ruta y direccion de la ficha de un proveedor.
 *
 * Misma forma que las ediciones, /p/<slug>/, por la misma razon: una URL sin
 * extension no delata con que se genero y no hay que cambiarla el dia que
 * deje de ser un fichero.
 */
function web_ruta_proveedor(string $slug): string
{
    return 'p/' . web_slug_seguro($slug) . '/index.html';
}

function web_url_proveedor(string $base, string $slug): string
{
    return rtrim($base, '/') . '/p/' . web_slug_seguro($slug) . '/';
}

/**
 * Firma del enlace contado de un bit.
 *
 * Corta a proposito: dieciseis caracteres hexadecimales son 64 bits, de sobra
 * para que nadie adivine una firma valida, y lo bastante corto para que el
 * enlace siga siendo legible en el codigo fuente del correo.
 *
 * Sin firma, api/ir.php seria un redirector abierto, y un redirector abierto
 * en un dominio que manda correo se convierte en municion para phishing.
 */
function web_firma_clic(int $bit_id, string $secreto): string
{
    return substr(hash_hmac('sha256', 'clic:' . $bit_id, $secreto), 0, 16);
}

/**
 * Enlace contado hacia la fuente de un bit.
 *
 * Con el secreto vacio devuelve la URL de la fuente tal cual: mejor un enlace
 * que no cuenta que un enlace que no lleva a ningun sitio.
 */
function web_url_clic(string $base, int $bit_id, string $secreto, string $directa): string
{
    if ($secreto === '') {
        return $directa;
    }

    return rtrim($base, '/') . '/api/ir.php?b=' . $bit_id . '&t=' . web_firma_clic($bit_id, $secreto);
}

/**
 * Minutos de lectura, redondeando hacia arriba, a 200 palabras por minuto.
 */
function web_minutos(int $palabras): int
{
    return max(1, (int) ceil($palabras / 200));
}
