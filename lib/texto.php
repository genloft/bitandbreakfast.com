<?php
/**
 * Normalizacion de texto y medida de similitud.
 *
 * De este fichero depende que el radar parezca curado o parezca un agregador:
 * si la normalizacion es mala, la misma noticia aparece tres veces en la
 * edicion. Por eso tiene pruebas propias en pruebas/texto.php.
 *
 * Decision de diseno: la similitud se mide sobre el titular normalizado SIN
 * palabras vacias, con el indice de Jaccard sobre shingles de tres caracteres.
 * Se conserva un espacio entre palabras para que los shingles que cruzan el
 * limite de palabra sigan aportando informacion de orden.
 *
 * Limitacion conocida y aceptada: esto no cruza idiomas. La misma noticia en
 * Skift y en Hosteltur da una similitud cercana a cero. Para esos casos, el
 * agrupador de la fase 2 se apoya en proveedores y cifras compartidas.
 */

/**
 * Quita acentos y diacriticos sin depender de intl ni de iconv, que no
 * siempre estan compilados en alojamiento compartido.
 */
function texto_sin_acentos(string $texto): string
{
    static $mapa = [
        'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a', 'ā' => 'a',
        'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e', 'ē' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i', 'ī' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o', 'ø' => 'o', 'ō' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u', 'ū' => 'u',
        'ñ' => 'n', 'ç' => 'c', 'ý' => 'y', 'ÿ' => 'y', 'ß' => 'ss',
        'Á' => 'a', 'À' => 'a', 'Ä' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Å' => 'a',
        'É' => 'e', 'È' => 'e', 'Ë' => 'e', 'Ê' => 'e',
        'Í' => 'i', 'Ì' => 'i', 'Ï' => 'i', 'Î' => 'i',
        'Ó' => 'o', 'Ò' => 'o', 'Ö' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ø' => 'o',
        'Ú' => 'u', 'Ù' => 'u', 'Ü' => 'u', 'Û' => 'u',
        'Ñ' => 'n', 'Ç' => 'c',
        // Comillas tipograficas y guiones largos, que llegan a mansalva
        // desde los feeds y romperian la comparacion.
        '’' => "'", '‘' => "'", '“' => '"', '”' => '"',
        '–' => '-', '—' => '-', '…' => '...', ' ' => ' ',
    ];

    return strtr($texto, $mapa);
}

/**
 * Palabras vacias en espanol e ingles. Se quitan antes de medir similitud:
 * dos titulares distintos comparten muchisimos "de", "la", "the" y "of", y
 * eso inflaba la puntuacion de parecido.
 */
function texto_palabras_vacias(): array
{
    static $vacias = null;

    if ($vacias === null) {
        $lista = [
            // Espanol
            'a', 'al', 'ante', 'con', 'contra', 'de', 'del', 'desde', 'e', 'el',
            'ella', 'ellos', 'en', 'entre', 'es', 'esta', 'este', 'esto', 'ha',
            'hacia', 'han', 'hasta', 'la', 'las', 'le', 'les', 'lo', 'los',
            'mas', 'me', 'mi', 'muy', 'no', 'nos', 'o', 'os', 'para', 'pero',
            'por', 'porque', 'que', 'se', 'segun', 'ser', 'si', 'sin', 'sobre',
            'son', 'su', 'sus', 'tras', 'un', 'una', 'uno', 'unos', 'unas',
            'y', 'ya',
            // Ingles
            'a', 'about', 'after', 'all', 'an', 'and', 'are', 'as', 'at', 'be',
            'been', 'but', 'by', 'can', 'for', 'from', 'has', 'have', 'how',
            'in', 'into', 'is', 'it', 'its', 'more', 'new', 'no', 'not', 'of',
            'on', 'or', 'over', 'that', 'the', 'their', 'they', 'this', 'to',
            'up', 'was', 'were', 'what', 'when', 'which', 'who', 'will',
            'with', 'you', 'your',
        ];
        $vacias = array_fill_keys($lista, true);
    }

    return $vacias;
}

/**
 * Normaliza un texto libre: sin HTML, sin acentos, en minusculas, sin signos
 * y con un solo espacio entre palabras.
 */
function texto_normalizar(string $texto): string
{
    $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $texto = strip_tags($texto);
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = texto_sin_acentos($texto);

    // Todo lo que no sea letra latina basica o digito pasa a ser separador.
    $limpio = preg_replace('/[^a-z0-9]+/u', ' ', $texto);

    // preg_replace con /u devuelve null si la cadena no es UTF-8 valido, y de
    // los feeds llega de todo. En ese caso se repite sin el modificador.
    if ($limpio === null) {
        $limpio = preg_replace('/[^a-z0-9]+/', ' ', $texto);
    }

    return trim(preg_replace('/\s+/', ' ', (string) $limpio));
}

/**
 * Normaliza y ademas quita las palabras vacias. Es lo que se guarda en
 * items.titulo_norm y lo que se compara.
 */
function texto_titulo_norm(string $titulo): string
{
    $vacias = texto_palabras_vacias();
    $utiles = [];

    foreach (explode(' ', texto_normalizar($titulo)) as $palabra) {
        if ($palabra === '' || isset($vacias[$palabra])) {
            continue;
        }
        $utiles[] = $palabra;
    }

    return implode(' ', $utiles);
}

/**
 * Shingles de n caracteres sobre un texto YA normalizado.
 * Devuelve las combinaciones distintas, que es lo que necesita Jaccard.
 */
function texto_shingles(string $normalizado, int $n = 3): array
{
    $largo = strlen($normalizado);

    if ($largo === 0) {
        return [];
    }
    // Un texto mas corto que la ventana es su propio unico shingle.
    if ($largo <= $n) {
        return [$normalizado => true];
    }

    $shingles = [];
    for ($i = 0; $i <= $largo - $n; $i++) {
        $shingles[substr($normalizado, $i, $n)] = true;
    }

    return $shingles;
}

/**
 * Indice de Jaccard entre dos titulares. Devuelve un valor entre 0 y 1.
 *
 * Se le pasan titulares en crudo: la funcion se encarga de normalizarlos, para
 * que ningun sitio de llamada pueda olvidarse de hacerlo.
 */
function texto_similitud(string $titulo_a, string $titulo_b, int $n = 3): float
{
    $a = texto_shingles(texto_titulo_norm($titulo_a), $n);
    $b = texto_shingles(texto_titulo_norm($titulo_b), $n);

    if (!$a || !$b) {
        return 0.0;
    }

    $interseccion = count(array_intersect_key($a, $b));
    $union        = count($a) + count($b) - $interseccion;

    return $union > 0 ? round($interseccion / $union, 4) : 0.0;
}

/**
 * Tokens poco frecuentes de un titular, para el indice invertido item_token.
 *
 * Sin esto habria que comparar cada item nuevo contra todos los de la ventana
 * de 72 horas. Con esto, solo contra los que comparten alguna palabra larga o
 * alguna cifra, que es donde estan los candidatos reales.
 */
function texto_tokens_clave(string $titulo, int $maximo = 8): array
{
    $tokens = [];

    foreach (explode(' ', texto_titulo_norm($titulo)) as $palabra) {
        if ($palabra === '') {
            continue;
        }
        // Palabras largas: nombres propios, productos, tecnicismos.
        // Cifras de cuatro o mas digitos: anos, importes, versiones.
        $es_largo = strlen($palabra) >= 5;
        $es_cifra = ctype_digit($palabra) && strlen($palabra) >= 4;

        if ($es_largo || $es_cifra) {
            $tokens[$palabra] = true;
        }
    }

    // strval no es adorno: las claves de un array en PHP se convierten a
    // entero cuando parecen un numero, asi que "2026" sale de array_keys()
    // como int 2026 y el resto como cadenas. Un token es siempre texto, y de
    // ahi sale a una columna VARCHAR y a comparaciones estrictas.
    $tokens = array_map('strval', array_keys($tokens));
    sort($tokens);

    return array_slice($tokens, 0, $maximo);
}

/**
 * Limpia el HTML que viene en el resumen de un feed dejando solo etiquetas
 * inofensivas y ningun atributo. La lista es blanca: lo que no esta, se cae.
 */
function texto_limpiar_html(string $html): string
{
    // strip_tags quita la etiqueta pero conserva su contenido, asi que un
    // <script> dejaria el codigo suelto dentro del texto. Los bloques de
    // script y style se eliminan enteros antes de nada.
    $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html);

    $permitidas = '<p><br><strong><b><em><i><ul><ol><li>';
    $limpio = strip_tags((string) $html, $permitidas);

    // strip_tags conserva los atributos, incluidos los onclick. Se eliminan
    // todos: no necesitamos ninguno para mostrar un resumen.
    return preg_replace('/<\s*([a-z0-9]+)[^>]*>/i', '<$1>', $limpio);
}

/**
 * Recorta respetando la ultima palabra completa. Se usa en la ingesta para no
 * guardar el articulo entero de los feeds que publican el texto completo.
 */
function texto_recortar(string $texto, int $maximo): string
{
    $texto = trim(preg_replace('/\s+/', ' ', $texto));

    if (mb_strlen($texto, 'UTF-8') <= $maximo) {
        return $texto;
    }

    $corte   = mb_substr($texto, 0, $maximo, 'UTF-8');
    $ultimo  = mb_strrpos($corte, ' ', 0, 'UTF-8');

    if ($ultimo !== false && $ultimo > $maximo * 0.6) {
        $corte = mb_substr($corte, 0, $ultimo, 'UTF-8');
    }

    return rtrim($corte, " ,.;:-") . '…';
}

/**
 * Cuenta palabras de verdad, para los contadores en vivo del panel.
 */
function texto_contar_palabras(string $texto): int
{
    $texto = trim(preg_replace('/\s+/', ' ', strip_tags($texto)));

    return $texto === '' ? 0 : substr_count($texto, ' ') + 1;
}
