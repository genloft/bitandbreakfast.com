<?php
/**
 * Reglas de la publicacion automatica.
 *
 * El proyecto nacio con una premisa: ningun bit se publica sin que lo lea una
 * persona. Esto la levanta, a peticion expresa del dueno del sitio. Conviene
 * saber lo que se cambia: deja de ser un radar curado y pasa a ser un
 * agregador con criterio. El criterio sigue estando -la puntuacion, la
 * agrupacion, el filtro anti-publirreportaje-, pero ya no hay nadie mirando.
 *
 * Por eso el modo automatico no inventa: no escribe analisis, no rellena el
 * "por que importa" y no resume con sus palabras. Coge el titular del racimo,
 * el resumen que publica la propia fuente y la lista de medios que lo
 * cuentan. Todo lo que sale es comprobable.
 *
 * Calculo puro, sin base de datos: lo que toca la base esta en cron/auto.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/texto.php';
require_once __DIR__ . '/bits.php';

/**
 * Version de los criterios automaticos.
 *
 * Cuando sube, lo ya publicado sin revision humana se vuelve a pasar por el
 * filtro una sola vez. Sin esto, una edicion publicada con criterios flojos se
 * queda ahi para siempre y solo mejora lo que venga despues, que es
 * exactamente lo que paso con la primera.
 *
 * Vive aqui, con las reglas, y no en cron/auto.php: asi la pagina de estado
 * puede decir que version de criterios lleva el codigo desplegado sin arrastrar
 * media tarea del cron.
 */
const AUTO_CRITERIOS = 2;

/**
 * Categoria del bit a partir de las fuentes que lo cuentan.
 *
 * Se usa la categoria por defecto de la fuente mejor puntuada, que es la que
 * el catalogo le asigno al darla de alta. Si no encaja con el catalogo de
 * categorias, se cae a la general: mejor eso que una categoria inventada que
 * luego no filtra.
 */
function auto_categoria(array $items): string
{
    foreach ($items as $item) {
        $categoria = (string) ($item['categoria_defecto'] ?? '');

        if (array_key_exists($categoria, bits_categorias())) {
            return $categoria;
        }
    }

    return 'tecnologia-general';
}

/**
 * Tipo del bit segun el tipo de la fuente.
 *
 * El mismo atajo que usa la puntuacion: una pagina de estado solo publica
 * incidentes y un boletin oficial solo normativa. No es exacto, pero no exige
 * clasificar el texto y acierta casi siempre.
 */
function auto_tipo(array $items): string
{
    foreach ($items as $item) {
        $tipo = match ((string) ($item['tipo'] ?? '')) {
            'estado'       => 'incidente',
            'normativa'    => 'regulacion',
            'financiacion' => 'inversion',
            default        => '',
        };

        if ($tipo !== '') {
            return $tipo;
        }
    }

    return 'producto';
}

/**
 * Cuerpo del bit: el resumen que publica la propia fuente, limpio y recortado.
 *
 * Si ninguna fuente trae resumen utilizable, se devuelve una frase de hechos
 * con los medios que lo cuentan. Es lo unico que se puede afirmar sin leer la
 * noticia, y es preferible a un bit vacio.
 */
function auto_cuerpo(array $items, int $max_palabras = BITS_CUERPO_MAX): string
{
    foreach ($items as $item) {
        $resumen = auto_limpiar((string) ($item['resumen_origen'] ?? ''));

        if (texto_contar_palabras($resumen) >= BITS_CUERPO_MIN) {
            return auto_recortar_palabras($resumen, $max_palabras);
        }
    }

    $medios = auto_medios($items);

    if (!$medios) {
        return '';
    }

    return count($medios) === 1
        ? sprintf('Lo publica %s. El resumen completo está en la fuente.', $medios[0])
        : sprintf(
            'Lo publican %d medios: %s. El resumen completo está en las fuentes.',
            count($medios),
            auto_enumerar($medios)
        );
}

/**
 * Deja el resumen de un feed en algo publicable.
 *
 * Los feeds vienen con de todo: HTML, entidades sin decodificar y sobre todo
 * la coletilla que engancha el gestor de contenidos al final de cada entrada
 * -"The post X appeared first on Y", "Continue reading"-, que no es parte de
 * la noticia y encima repite el titular.
 */
function auto_limpiar(string $bruto): string
{
    $texto = html_entity_decode(
        strip_tags(texto_limpiar_html($bruto)),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    $coletillas = [
        // WordPress y compañia, en ingles y en espanol.
        '/\s*The post.*$/su',
        '/\s*El art[ií]culo.*?(aparec[eió]|se public[oó]).*$/sui',
        '/\s*(Continue reading|Read more|Read the full|Leer m[aá]s|Seguir leyendo|Sigue leyendo).*$/sui',
        // La entradilla cortada que dejan muchos feeds.
        '/\s*\[\s*[…\.]+\s*\]\s*/u',
    ];

    foreach ($coletillas as $patron) {
        $texto = (string) preg_replace($patron, ' ', $texto);
    }

    $texto = (string) preg_replace('/\s+/u', ' ', $texto);

    // Ademas de espacios, se quitan los guiones y barras con los que muchos
    // feeds separan el resumen de la firma del medio.
    return trim($texto, " \t\n\r\0\x0B-–—·|");
}

/**
 * Categoria a partir del diccionario, que es quien sabe de que va la noticia.
 *
 * Gana el termino presente de mas peso que tenga categoria. Es mucho mejor
 * senal que la categoria por defecto de la fuente, que en un medio generalista
 * es siempre la misma y deja toda la edicion en "tecnologia general".
 *
 * @param array $terminos Filas con 'termino', 'peso' y 'categoria'.
 */
function auto_categoria_diccionario(string $texto, array $terminos): string
{
    $aguja  = ' ' . texto_normalizar($texto) . ' ';
    $mejor  = '';
    $cuanto = 0;

    foreach ($terminos as $fila) {
        $peso      = (int) ($fila['peso'] ?? 0);
        $categoria = (string) ($fila['categoria'] ?? '');

        if ($peso <= $cuanto || $categoria === '' || !array_key_exists($categoria, bits_categorias())) {
            continue;
        }

        $termino = texto_normalizar((string) $fila['termino']);

        if ($termino !== '' && str_contains($aguja, ' ' . $termino . ' ')) {
            $mejor  = $categoria;
            $cuanto = $peso;
        }
    }

    return $mejor;
}

/**
 * Recorta a un numero de palabras sin partir la ultima.
 */
function auto_recortar_palabras(string $texto, int $maximo): string
{
    $palabras = preg_split('/\s+/', trim($texto)) ?: [];

    if (count($palabras) <= $maximo) {
        return trim($texto);
    }

    return rtrim(implode(' ', array_slice($palabras, 0, $maximo)), ' ,;:.') . '…';
}

/**
 * Nombres de los medios que cuentan la noticia, sin repetir y en orden.
 */
function auto_medios(array $items): array
{
    $medios = [];

    foreach ($items as $item) {
        $nombre = trim((string) ($item['fuente'] ?? ''));

        if ($nombre !== '' && !in_array($nombre, $medios, true)) {
            $medios[] = $nombre;
        }
    }

    return $medios;
}

/**
 * "A, B y C", que es como se enumera en espanol.
 */
function auto_enumerar(array $nombres): string
{
    if (count($nombres) < 2) {
        return implode('', $nombres);
    }

    $ultimo = array_pop($nombres);

    return implode(', ', $nombres) . ' y ' . $ultimo;
}

/**
 * ¿Toca cerrar la edicion y publicarla?
 *
 * Dos motivos: que este llena o que haya llegado su fecha. Nunca se cierra
 * vacia, porque una edicion sin bits es una pagina sin nada que enviar.
 */
function auto_toca_cerrar(int $bits, int $tope, string $fecha_prevista, string $hoy): bool
{
    if ($bits <= 0) {
        return false;
    }

    return $bits >= $tope || $fecha_prevista <= $hoy;
}

/**
 * Los racimos que entran en esta tanda.
 *
 * @param array $candidatos Filas con 'id' y 'puntuacion', de mas a menos.
 * @param int   $umbral     Puntuacion minima para publicar sin revision.
 * @param int   $huecos     Cuantos caben todavia en la edicion.
 */
function auto_elegir(array $candidatos, int $umbral, int $huecos): array
{
    if ($huecos <= 0) {
        return [];
    }

    $elegidos = [];

    foreach ($candidatos as $candidato) {
        if ((int) $candidato['puntuacion'] < $umbral) {
            // Vienen ordenados de mas a menos: en cuanto uno no llega, los
            // siguientes tampoco.
            break;
        }

        $elegidos[] = $candidato;

        if (count($elegidos) >= $huecos) {
            break;
        }
    }

    return $elegidos;
}
