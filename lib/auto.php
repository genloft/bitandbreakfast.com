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
const AUTO_CRITERIOS = 6;

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
        $categoria = bits_categoria_canonica((string) ($item['categoria_defecto'] ?? ''));

        if ($categoria !== '') {
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
    $texto = auto_decodificar(strip_tags(texto_limpiar_html($bruto)));

    $coletillas = [
        // WordPress y compañia, en ingles y en espanol.
        '/\s*The post.*$/su',
        '/\s*El art[ií]culo.*?(aparec[eió]|se public[oó]).*$/sui',
        '/\s*(Continue reading|Read more|Read the full|Leer m[aá]s|Seguir leyendo|Sigue leyendo).*$/sui',
        // La entradilla cortada que dejan muchos feeds.
        '/\s*\[\s*[…\.]+\s*\]\s*/u',
        // La firma que deja Drupal en el resumen: "mcottam Mon, 09/14/2026 -
        // 12:31". Es el usuario que publico y la hora, no la noticia.
        '/\s*\S+\s+(Mon|Tue|Wed|Thu|Fri|Sat|Sun),\s*\d{1,2}\/\d{1,2}\/\d{4}\s*-\s*\d{1,2}:\d{2}\s*/u',
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
 * Decodifica las entidades hasta que dejan de ser entidades.
 *
 * Una vez no basta: hay feeds que escapan el HTML que ya venia escapado, asi
 * que "&amp;#039;" se queda en "&#039;" y se publica tal cual. Se repite, con
 * un tope, porque un texto que se decodifica sin fin es un texto que alguien
 * ha preparado para que esto no termine nunca.
 */
function auto_decodificar(string $texto): string
{
    for ($vuelta = 0; $vuelta < 3; $vuelta++) {
        $antes = $texto;
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if ($texto === $antes) {
            break;
        }
    }

    return $texto;
}

/**
 * Quita del cuerpo el titular, cuando el feed lo repite al principio.
 *
 * Muchos gestores meten el titular como primera linea del resumen. En el bit
 * queda el mismo texto dos veces seguidas, una en grande y otra en pequeno,
 * y lo poco que el resumen anadia se pierde al recortar.
 */
function auto_sin_titular(string $cuerpo, string $titular): string
{
    $titular = trim($titular);

    if ($titular === '' || $cuerpo === '') {
        return $cuerpo;
    }

    $largo = mb_strlen($titular);

    if (mb_strtolower(mb_substr($cuerpo, 0, $largo)) !== mb_strtolower($titular)) {
        return $cuerpo;
    }

    $resto = ltrim(mb_substr($cuerpo, $largo), " \t\n\r.:;,-–—|·");

    // Si el resumen no era mas que el titular, se deja como estaba: quien
    // llama decide si un cuerpo vacio vale, y aqui no se puede saber.
    return trim($resto) !== '' ? $resto : $cuerpo;
}

/**
 * ¿Esto es material promocional en vez de una noticia?
 *
 * Los medios del sector publican en el mismo feed sus noticias y sus libros
 * blancos, seminarios y guias descargables. Para el lector no es lo mismo: lo
 * segundo es un formulario, no una noticia, y el bit le estaria prometiendo
 * algo que no hay. El diccionario no lo caza porque habla de tecnologia
 * hotelera con el mismo vocabulario que una noticia de verdad.
 */
function auto_es_promocional(string $texto): bool
{
    $aguja = ' ' . texto_normalizar($texto) . ' ';

    $marcas = [
        'e-book', 'ebook', 'libro blanco', 'whitepaper', 'white paper',
        'webinar', 'seminario web', 'contenido patrocinado', 'sponsored content',
        'download the', 'descarga la guia', 'descarga el informe',
        'register now', 'inscribete', 'reserva tu plaza',
    ];

    foreach ($marcas as $marca) {
        if (str_contains($aguja, ' ' . texto_normalizar($marca) . ' ')) {
            return true;
        }
    }

    return false;
}

/**
 * Deja el titular del racimo en algo publicable.
 *
 * El titular llega tal cual del feed, y la mitad de los feeds no decodifican
 * las entidades: en la portada se leia "Soneva&#039;s Neil Gallagher", porque
 * el generador escapa lo que le dan y lo que le daban ya venia escapado.
 *
 * Se limpia aparte del cuerpo porque un titular no lleva coletillas de gestor
 * de contenidos, pero si arrastra el nombre del medio pegado al final -"...
 * | Skift", "... - Hosteltur"-, que en un radar que ya dice la fuente debajo
 * sobra.
 *
 * @param array $medios Nombres de los medios que cuentan la noticia.
 */
function auto_titular(string $bruto, array $medios = []): string
{
    $titular = auto_decodificar(strip_tags(texto_limpiar_html($bruto)));
    $titular = trim((string) preg_replace('/\s+/u', ' ', $titular));

    foreach ($medios as $medio) {
        $medio = trim((string) $medio);

        if ($medio === '') {
            continue;
        }

        $patron = '/\s*[-–—|·]\s*' . preg_quote($medio, '/') . '\s*$/ui';
        $corto  = (string) preg_replace($patron, '', $titular);

        // Solo si queda titular: hay medios cuyo nombre es la noticia entera.
        if (trim($corto) !== '') {
            $titular = trim($corto);
        }
    }

    return $titular;
}

/**
 * ¿Es esto un recopilatorio y no una noticia?
 *
 * Algunos boletines publican en su feed una sola entrada con cinco noticias
 * distintas separadas por comas -"Soneva's Neil Gallagher on Bare Luxury...,
 * dormakaba Acquires Alliants, EU AI Act Is Now..."-. Como titular de un bit
 * no vale: no cuenta una cosa, cuenta cinco a medias, y el enlace lleva a un
 * indice, no a la noticia.
 *
 * Tres condiciones a la vez, porque cualquiera de ellas por separado se lleva
 * por delante titulares normales: muy largo, partido en tres o mas trozos por
 * comas, y con tres trozos que son frases enteras. Un titular espanol con dos
 * incisos rara vez llega a noventa caracteres con tres trozos de tres
 * palabras; uno de estos no baja de ahi nunca.
 */
function auto_es_recopilatorio(string $titular): bool
{
    if (mb_strlen($titular) < 90) {
        return false;
    }

    $trozos = explode(',', $titular);

    if (count($trozos) < 3) {
        return false;
    }

    $frases = 0;

    foreach ($trozos as $trozo) {
        if (texto_contar_palabras(trim($trozo)) >= 3) {
            $frases++;
        }
    }

    return $frases >= 3;
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
        $categoria = bits_categoria_canonica((string) ($fila['categoria'] ?? ''));

        if ($peso <= $cuanto || $categoria === '') {
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
 * Tres motivos: que este llena, que haya llegado su fecha, o que el sitio no
 * tenga todavia ninguna edicion publicada y esta ya tenga con que llenar una
 * portada. Nunca se cierra vacia, porque una edicion sin bits es una pagina
 * sin nada que ensenar.
 *
 * El tercer motivo no es un capricho. La edicion se cierra el martes, asi que
 * un sitio que se queda sin ediciones publicadas -porque es nuevo, o porque
 * una revision retiro lo que no pasaba el filtro- ensena una portada vacia
 * hasta el martes siguiente. Seis dias de nada. Una portada vacia no es una
 * espera: es un sitio roto, y quien pasa por el no vuelve.
 *
 * @param bool $hay_publicadas ¿Hay ya alguna edicion cerrada o enviada?
 * @param int  $suelo          Bits que hacen una portada digna de ese arranque.
 */
function auto_toca_cerrar(
    int $bits,
    int $tope,
    string $fecha_prevista,
    string $hoy,
    bool $hay_publicadas = true,
    int $suelo = 6
): bool {
    if ($bits <= 0) {
        return false;
    }

    if ($bits >= $tope) {
        return true;
    }

    if (!$hay_publicadas && $bits >= max(1, $suelo)) {
        return true;
    }

    return $fecha_prevista <= $hoy;
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
