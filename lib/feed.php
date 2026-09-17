<?php
/**
 * Descarga y parseo de feeds RSS 2.0, Atom y RDF (RSS 1.0).
 *
 * No se vendoriza SimplePie a proposito: trae su propia capa de descarga y de
 * cache en disco que duplican lo que ya hacemos con ETag y Last-Modified, y
 * son medio megabyte de codigo ajeno en el repositorio. Esto son doscientas
 * lineas que se leen de una sentada.
 */

require_once __DIR__ . '/db.php';        // config() y ajuste()
require_once __DIR__ . '/canonica.php';
require_once __DIR__ . '/texto.php';

/**
 * Espera lo necesario para no hacer mas de una peticion por segundo al mismo
 * dominio. La cuenta es por ejecucion del cron, que es donde se concentran
 * las peticiones seguidas.
 */
function feed_pausa_dominio(string $url): void
{
    static $ultima = [];

    $host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');
    if ($host === '') {
        return;
    }

    $pausa = (float) (config('rastreador.pausa_dominio') ?? 1);

    if (isset($ultima[$host])) {
        $transcurrido = microtime(true) - $ultima[$host];
        if ($transcurrido < $pausa) {
            usleep((int) (($pausa - $transcurrido) * 1000000));
        }
    }

    $ultima[$host] = microtime(true);
}

/**
 * Descarga un feed usando las cabeceras condicionales guardadas.
 *
 * Devuelve un array con:
 *   codigo        int    codigo HTTP (0 si ni siquiera hubo respuesta)
 *   cuerpo        string cuerpo de la respuesta
 *   etag          string ETag nuevo, si lo hay
 *   last_modified string Last-Modified nuevo, si lo hay
 *   error         string mensaje de error de red, vacio si todo fue bien
 */
function feed_descargar(string $url, ?string $etag = null, ?string $last_modified = null): array
{
    $resultado = feed_peticion($url, $etag, $last_modified, feed_agente());

    // Hay cortafuegos que devuelven 403 a cualquier agente que lleve la palabra
    // "bot" dentro, aunque el feed sea publico y este ahi para leerlo. Tres de
    // las fuentes en espanol del catalogo hacen justo eso. Se reintenta una vez
    // presentandose sin esa palabra -el nombre y la direccion del sitio siguen
    // ahi, no se finge ser otro-, y solo cuando la respuesta ha sido un no por
    // quien pregunta, no por lo que se pregunta.
    if (in_array($resultado['codigo'], [401, 403, 406, 429], true)) {
        $segundo = feed_peticion($url, $etag, $last_modified, feed_agente(false));

        if ($segundo['codigo'] >= 200 && $segundo['codigo'] < 400) {
            return $segundo;
        }
    }

    return $resultado;
}

/**
 * Como se presenta el rastreador.
 *
 * Con la palabra "bot" por defecto, que es lo correcto: quien recibe la
 * peticion tiene derecho a saber que no es una persona. Sin ella en el
 * reintento, porque hay cortafuegos que la usan como unica regla.
 */
function feed_agente(bool $declarado = true): string
{
    $defecto = 'Mozilla/5.0 (compatible; BitAndBreakfastBot/1.0; +https://bitandbreakfast.com/bot)';

    // Sin configuracion -en las pruebas, por ejemplo- se usa el de casa.
    $agente = (string) (config_opcional('rastreador.user_agent') ?: $defecto);

    if ($declarado) {
        return $agente;
    }

    return trim((string) preg_replace('/\s*bot\b/i', '', $agente)) ?: 'Mozilla/5.0';
}

/**
 * Una peticion, con el agente que se le diga.
 */
function feed_peticion(string $url, ?string $etag, ?string $last_modified, string $agente): array
{
    $resultado = [
        'codigo' => 0, 'cuerpo' => '', 'etag' => null,
        'last_modified' => null, 'error' => '',
    ];

    feed_pausa_dominio($url);

    $cabeceras = ['Accept: application/rss+xml, application/atom+xml, application/xml;q=0.9, */*;q=0.8'];
    if (!empty($etag)) {
        $cabeceras[] = 'If-None-Match: ' . $etag;
    }
    if (!empty($last_modified)) {
        $cabeceras[] = 'If-Modified-Since: ' . $last_modified;
    }

    $recibidas = [];
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => (int) (config('rastreador.timeout') ?? 10),
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_USERAGENT      => $agente,
        CURLOPT_HTTPHEADER     => $cabeceras,
        // Acepta gzip: los feeds grandes ocupan la cuarta parte y el
        // alojamiento compartido agradece cada byte.
        CURLOPT_ENCODING       => '',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HEADERFUNCTION => function ($ch, $linea) use (&$recibidas) {
            $trozos = explode(':', $linea, 2);
            if (count($trozos) === 2) {
                $recibidas[strtolower(trim($trozos[0]))] = trim($trozos[1]);
            }
            return strlen($linea);
        },
        // Corta la descarga si el feed es desproporcionado.
        CURLOPT_NOPROGRESS     => false,
        CURLOPT_PROGRESSFUNCTION => function ($ch, $descargar_total, $descargado) {
            $tope = (int) (config('rastreador.max_bytes') ?? 5242880);
            return $descargado > $tope ? 1 : 0;
        },
    ]);

    $cuerpo = curl_exec($ch);

    if ($cuerpo === false) {
        $resultado['error'] = curl_error($ch) ?: 'error de red desconocido';
    } else {
        $resultado['cuerpo'] = $cuerpo;
    }

    $resultado['codigo']        = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $resultado['etag']          = $recibidas['etag'] ?? null;
    $resultado['last_modified'] = $recibidas['last-modified'] ?? null;

    curl_close($ch);

    return $resultado;
}

/**
 * Convierte el XML de un feed en una lista plana de entradas.
 *
 * Cada entrada devuelve: guid, url, titulo, resumen, autor y publicado
 * (fecha en UTC con formato de MySQL, o null si el feed no la trae).
 */
function feed_parsear(string $xml, string $url_base = ''): array
{
    if (trim($xml) === '') {
        return [];
    }

    // Hay feeds que empiezan con espacios o con un BOM y eso rompe libxml.
    $xml = preg_replace('/^[\x00-\x20]+/', '', $xml);

    $anterior = libxml_use_internal_errors(true);
    // LIBXML_NONET impide que el parser salga a la red a buscar una DTD.
    // No se usa LIBXML_NOENT: expandir entidades es justo lo que abre XXE.
    $raiz = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($anterior);

    if ($raiz === false) {
        return [];
    }

    $nombre = strtolower($raiz->getName());

    if ($nombre === 'rss') {
        return feed_parsear_rss($raiz, $url_base);
    }
    if ($nombre === 'feed') {
        return feed_parsear_atom($raiz, $url_base);
    }
    if ($nombre === 'rdf') {
        return feed_parsear_rdf($raiz, $url_base);
    }

    return [];
}

/**
 * RSS 2.0: rss > channel > item
 */
function feed_parsear_rss(SimpleXMLElement $raiz, string $url_base): array
{
    $entradas = [];

    foreach ($raiz->channel->item ?? [] as $item) {
        $enlace = trim((string) $item->link);
        $guid   = trim((string) $item->guid);

        // Hay feeds que dejan link vacio y ponen la URL en guid.
        if ($enlace === '' && filter_var($guid, FILTER_VALIDATE_URL)) {
            $enlace = $guid;
        }

        $dc      = $item->children('http://purl.org/dc/elements/1.1/');
        $content = $item->children('http://purl.org/rss/1.0/modules/content/');

        $resumen = (string) $item->description;
        if ($resumen === '' && isset($content->encoded)) {
            $resumen = (string) $content->encoded;
        }

        $autor = trim((string) $item->author);
        if ($autor === '' && isset($dc->creator)) {
            $autor = trim((string) $dc->creator);
        }

        $fecha = (string) $item->pubDate;
        if ($fecha === '' && isset($dc->date)) {
            $fecha = (string) $dc->date;
        }

        $entradas[] = feed_entrada(
            $guid !== '' ? $guid : $enlace,
            canonica_absoluta($enlace, $url_base),
            (string) $item->title,
            $resumen,
            $autor,
            $fecha
        );
    }

    return feed_filtrar($entradas);
}

/**
 * Atom: feed > entry
 */
function feed_parsear_atom(SimpleXMLElement $raiz, string $url_base): array
{
    $entradas = [];

    foreach ($raiz->entry ?? [] as $entrada) {
        // Se prefiere el enlace alternate; si no lo hay, el primero que venga.
        $enlace = '';
        foreach ($entrada->link ?? [] as $link) {
            $rel = (string) $link['rel'];
            if ($rel === '' || $rel === 'alternate') {
                $enlace = (string) $link['href'];
                break;
            }
            if ($enlace === '') {
                $enlace = (string) $link['href'];
            }
        }

        $resumen = (string) $entrada->summary;
        if ($resumen === '') {
            $resumen = (string) $entrada->content;
        }

        $fecha = (string) $entrada->published;
        if ($fecha === '') {
            $fecha = (string) $entrada->updated;
        }

        $entradas[] = feed_entrada(
            (string) $entrada->id,
            canonica_absoluta($enlace, $url_base),
            (string) $entrada->title,
            $resumen,
            (string) ($entrada->author->name ?? ''),
            $fecha
        );
    }

    return feed_filtrar($entradas);
}

/**
 * RDF (RSS 1.0): los item cuelgan de la raiz, no de channel, y viven en el
 * espacio de nombres de RSS 1.0, asi que hay que pedirlos explicitamente.
 */
function feed_parsear_rdf(SimpleXMLElement $raiz, string $url_base): array
{
    $entradas = [];
    $items    = $raiz->children('http://purl.org/rss/1.0/')->item ?? [];

    foreach ($items as $item) {
        $dc    = $item->children('http://purl.org/dc/elements/1.1/');
        $fecha = isset($dc->date) ? (string) $dc->date : (string) $item->pubDate;

        $entradas[] = feed_entrada(
            (string) $item->attributes('http://www.w3.org/1999/02/22-rdf-syntax-ns#')->about,
            canonica_absoluta((string) $item->link, $url_base),
            (string) $item->title,
            (string) $item->description,
            isset($dc->creator) ? (string) $dc->creator : '',
            $fecha
        );
    }

    return feed_filtrar($entradas);
}

/**
 * Da forma uniforme a una entrada, limpia el HTML y recorta el resumen.
 *
 * El recorte no es cosmetico: hay feeds que publican el articulo completo y
 * no queremos guardar texto ajeno entero, solo lo justo para puntuar y para
 * que yo decida en el panel.
 */
function feed_entrada(string $guid, string $url, string $titulo, string $resumen, string $autor, string $fecha): array
{
    $tope = (int) (ajuste('ingesta_resumen_max', 1200));

    return [
        'guid'      => texto_recortar(trim($guid), 480),
        'url'       => trim($url),
        'titulo'    => texto_recortar(trim(html_entity_decode(strip_tags($titulo), ENT_QUOTES | ENT_HTML5, 'UTF-8')), 480),
        'resumen'   => texto_recortar(strip_tags(texto_limpiar_html($resumen)), $tope),
        'autor'     => texto_recortar(trim(strip_tags($autor)), 150),
        'publicado' => feed_fecha($fecha),
    ];
}

/**
 * Normaliza cualquier formato de fecha de feed a UTC.
 * Devuelve null si no hay forma de entenderla: mejor null que una fecha
 * inventada, porque la frescura puntua.
 */
function feed_fecha(string $fecha): ?string
{
    $fecha = trim($fecha);
    if ($fecha === '') {
        return null;
    }

    $marca = strtotime($fecha);
    if ($marca === false || $marca <= 0) {
        return null;
    }

    // Un feed con fecha futura suele ser un error de zona horaria del medio.
    // Se admite un margen de un dia y se descarta lo demas.
    if ($marca > time() + 86400) {
        return null;
    }

    return gmdate('Y-m-d H:i:s', $marca);
}

/**
 * Descarta entradas inservibles: sin titulo o sin URL utilizable.
 */
function feed_filtrar(array $entradas): array
{
    $validas = [];

    foreach ($entradas as $entrada) {
        if ($entrada['titulo'] === '' || $entrada['url'] === '') {
            continue;
        }
        if (canonica_normalizar($entrada['url']) === '') {
            continue;
        }
        $validas[] = $entrada;
    }

    return $validas;
}

/**
 * Cuantas horas se deja dormir a una fuente que lleva $fallos seguidos.
 *
 * Los cuatro primeros no cuentan: un feed falla por mil razones de un rato
 * -el servidor reiniciando, la red, un 502 de paso- y castigar eso seria
 * dejar de leer a quien no ha hecho nada. A partir del quinto, el plazo crece
 * pero no sin limite: una semana es suficiente para que una IP compartida
 * deje de estar en una lista negra, y mas seria dar la fuente por perdida.
 *
 * @return int Horas de sueno, o 0 si todavia no toca dormir.
 */
function feed_sueno(int $fallos): int
{
    return match (true) {
        $fallos >= 20 => 168,
        $fallos >= 10 => 72,
        $fallos >= 7  => 24,
        $fallos >= 5  => 6,
        default       => 0,
    };
}
