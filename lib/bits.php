<?php
/**
 * Reglas de los bits y de la edicion semanal.
 *
 * Calculo puro, como lib/puntuar.php: ni base de datos ni sesion. Aqui esta
 * lo que hace que una edicion sea publicable o no, que es justo lo que hay
 * que poder probar sin montar medio sistema.
 *
 * El formato del bit no es negociable y por eso vive en constantes: la
 * promesa al lector son cinco minutos de lectura a la semana, y esa promesa
 * se cumple o se rompe aqui, no en el generador.
 */

declare(strict_types=1);

require_once __DIR__ . '/texto.php';

/** Un titular que no cabe en una linea de correo deja de ser un titular. */
const BITS_TITULAR_MAX = 120;

/** Palabras del cuerpo. Veinte bits por cien palabras son cinco minutos. */
const BITS_CUERPO_MIN = 25;
const BITS_CUERPO_MAX = 110;

/** El "por que importa" es una frase, no un parrafo. */
const BITS_PORQUE_MAX = 220;

/**
 * Categorias tematicas. Las mismas que usan las fuentes y el diccionario.
 */
function bits_categorias(): array
{
    return [
        'tecnologia-general'          => 'Tecnología general',
        'pms-gestion'                 => 'PMS y gestión',
        'distribucion-revenue'        => 'Distribución y revenue',
        'ciberseguridad-cumplimiento' => 'Ciberseguridad y cumplimiento',
        'operaciones-personal'        => 'Operaciones y personal',
        'experiencia-huesped'         => 'Experiencia del huésped',
        'sostenibilidad-energia'      => 'Sostenibilidad y energía',
        'inversion-mercado'           => 'Inversión y mercado',
    ];
}

function bits_madureces(): array
{
    return [
        'rumor'      => 'Rumor',
        'anuncio'    => 'Anuncio',
        'disponible' => 'Disponible',
        'implantado' => 'Implantado',
    ];
}

function bits_tipos(): array
{
    return [
        'producto'   => 'Producto',
        'inversion'  => 'Inversión',
        'regulacion' => 'Regulación',
        'caso_real'  => 'Caso real',
        'incidente'  => 'Incidente',
    ];
}

/**
 * Valida un bit antes de guardarlo. Devuelve la lista de errores.
 *
 * Los limites de longitud se comprueban en caracteres para el titular, que es
 * lo que ocupa sitio en la pantalla, y en palabras para el cuerpo, que es lo
 * que cuesta tiempo de lectura.
 */
function bits_validar(array $bit): array
{
    $errores = [];

    $titular = trim((string) ($bit['titular'] ?? ''));
    $cuerpo  = trim((string) ($bit['cuerpo'] ?? ''));
    $porque  = trim((string) ($bit['por_que'] ?? ''));

    if ($titular === '') {
        $errores[] = 'El titular no puede quedarse vacío.';
    } elseif (mb_strlen($titular) > BITS_TITULAR_MAX) {
        $errores[] = sprintf(
            'El titular tiene %d caracteres y el máximo son %d.',
            mb_strlen($titular),
            BITS_TITULAR_MAX
        );
    }

    $palabras = texto_contar_palabras($cuerpo);

    if ($palabras < BITS_CUERPO_MIN) {
        $errores[] = sprintf(
            'El cuerpo tiene %d palabras y hacen falta al menos %d.',
            $palabras,
            BITS_CUERPO_MIN
        );
    } elseif ($palabras > BITS_CUERPO_MAX) {
        $errores[] = sprintf(
            'El cuerpo tiene %d palabras y el máximo son %d.',
            $palabras,
            BITS_CUERPO_MAX
        );
    }

    if ($porque === '') {
        $errores[] = 'Falta el "por qué importa": es lo que distingue un radar de un agregador.';
    } elseif (mb_strlen($porque) > BITS_PORQUE_MAX) {
        $errores[] = sprintf(
            'El "por qué importa" tiene %d caracteres y el máximo son %d.',
            mb_strlen($porque),
            BITS_PORQUE_MAX
        );
    }

    if (!array_key_exists((string) ($bit['categoria'] ?? ''), bits_categorias())) {
        $errores[] = 'La categoría no es una de las del catálogo.';
    }

    if (!array_key_exists((string) ($bit['madurez'] ?? ''), bits_madureces())) {
        $errores[] = 'La madurez no es una de las del catálogo.';
    }

    if (!array_key_exists((string) ($bit['tipo'] ?? ''), bits_tipos())) {
        $errores[] = 'El tipo no es uno de los del catálogo.';
    }

    return $errores;
}

/**
 * Revisa una edicion entera antes de cerrarla.
 *
 * Devuelve ['errores' => [...], 'avisos' => [...], 'palabras' => int]. Los
 * errores impiden cerrar; los avisos solo se enseñan. La distincion importa:
 * la cuota de fuentes en espanol es una intencion editorial, no una regla, y
 * una semana floja en Europa no puede bloquear el envio.
 *
 * @param array $bits  Filas con 'estado', 'cuerpo' y 'region'.
 * @param array $conf  edicion_max_bits y edicion_cuota_es_eu.
 */
function bits_revisar_edicion(array $bits, array $conf): array
{
    $errores   = [];
    $avisos    = [];
    $palabras  = 0;
    $es_eu     = 0;
    $borradores = 0;

    // El recorrido no se corta en el primer borrador: los recuentos de abajo
    // tienen que mirar la edicion entera para que el aviso de cuota no salga
    // calculado a medias.
    foreach ($bits as $bit) {
        $palabras += texto_contar_palabras((string) ($bit['cuerpo'] ?? ''));

        if (in_array((string) ($bit['region'] ?? ''), ['es', 'eu'], true)) {
            $es_eu++;
        }

        if ((string) ($bit['estado'] ?? '') === 'borrador') {
            $borradores++;
        }
    }

    if ($borradores > 0) {
        $errores[] = sprintf(
            'Quedan %d bits en borrador: apruébalos todos antes de cerrar.',
            $borradores
        );
    }

    $total = count($bits);
    $tope  = (int) ($conf['edicion_max_bits'] ?? 20);
    $cuota = (int) ($conf['edicion_cuota_es_eu'] ?? 30);

    if ($total === 0) {
        $errores[] = 'La edición no tiene ni un bit.';
    }

    if ($total > $tope) {
        $errores[] = sprintf('La edición tiene %d bits y el tope son %d.', $total, $tope);
    }

    if ($total > 0 && $cuota > 0 && ($es_eu * 100) < ($cuota * $total)) {
        $avisos[] = sprintf(
            'Solo %d de %d bits vienen de fuentes españolas o europeas; la intención es al menos el %d%%.',
            $es_eu,
            $total,
            $cuota
        );
    }

    // Cinco minutos a 200 palabras por minuto son mil palabras largas.
    if ($palabras > 1200) {
        $avisos[] = sprintf(
            'La edición son %d palabras, más de los cinco minutos que promete la cabecera.',
            $palabras
        );
    }

    return ['errores' => $errores, 'avisos' => $avisos, 'palabras' => $palabras];
}

/**
 * Slug de una edicion a partir de su numero y su fecha: 2026-w38-bits.
 */
function bits_slug_edicion(int $numero, string $fecha): string
{
    // La fecha se interpreta en UTC y se formatea en UTC. Mezclar strtotime,
    // que usa la zona de PHP, con gmdate, que no, hace que en Madrid una
    // fecha a medianoche caiga en el dia anterior y cambie de semana.
    $tiempo = strtotime($fecha . ' UTC');
    $tiempo = $tiempo === false ? time() : $tiempo;

    // 'o' y no 'Y': el año de la semana ISO, que en fin de año no
    // coincide con el del calendario y produciria slugs como 2027-w53.
    return sprintf('%s-w%s-%03d', gmdate('o', $tiempo), gmdate('W', $tiempo), $numero);
}
