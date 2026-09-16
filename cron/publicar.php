<?php
/**
 * Generador de la web estatica.
 *
 * Se ejecuta desde cron/tareas.php, o directamente por linea de comandos:
 *   php cron/publicar.php
 *
 * Convierte las ediciones cerradas en HTML dentro de publico/. A partir de
 * ahi el sitio lo sirve Apache sin tocar PHP ni la base de datos, que es lo
 * unico que aguanta una portada compartida el dia que una edicion se comparta
 * en LinkedIn.
 *
 * Lo que escribe:
 *
 *   index.html          la ultima edicion, que hace de portada
 *   e/<slug>/index.html cada edicion
 *   archivo.html        el indice de todas
 *   feed.xml            RSS de las ediciones
 *   estilo.css          la hoja del sitio
 *   robots.txt
 *
 * No regenera en cada pasada: calcula una firma de lo publicable y solo
 * trabaja si ha cambiado. Asi el cron horario no reescribe seis ficheros cada
 * hora para nada, y a la vez cualquier correccion de un bit ya publicado se
 * recoge sola en la siguiente vuelta.
 */

require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/texto.php';
require_once dirname(__DIR__) . '/lib/web.php';
require_once dirname(__DIR__) . '/lib/bits.php';
require_once dirname(__DIR__) . '/lib/correo.php';

/**
 * Publica lo que haya pendiente.
 *
 * @param float $limite Marca de tiempo a partir de la cual no se empiezan
 *                      ediciones nuevas.
 */
function publicar_pendiente(float $limite): array
{
    $ediciones = publicar_ediciones();
    $firma     = publicar_firma($ediciones);

    if ($firma === (string) ajuste('publicar_firma', '')) {
        return ['ediciones' => 0, 'ficheros' => 0, 'estado' => 'sin cambios'];
    }

    $config  = config();
    $base    = rtrim((string) ($config['sitio']['url'] ?? ''), '/');
    $publico = (string) ($config['rutas']['publico'] ?? dirname(__DIR__) . '/publico');

    $ficheros = 0;
    $hechas   = 0;

    // Si no hay proveedor de correo configurado, el bloque de alta se pinta
    // como "todavia no". Mejor eso que un formulario que no lleva a ningun
    // sitio.
    $alta = correo_configurado();

    // La hoja de estilo y robots.txt no dependen de las ediciones, pero se
    // escriben aqui: son parte de la salida y no tienen otro sitio donde vivir.
    $ficheros += publicar_escribir($publico . '/estilo.css', publicar_plantilla('estilo', [])) ? 1 : 0;
    $ficheros += publicar_escribir($publico . '/robots.txt', publicar_plantilla('robots', ['base' => $base])) ? 1 : 0;

    foreach ($ediciones as $indice => $edicion) {
        if (microtime(true) >= $limite) {
            // Sin firma guardada, la proxima pasada vuelve a empezar. Se
            // reescriben ficheros ya escritos, pero nunca queda una edicion
            // sin generar por haberse quedado sin tiempo.
            return ['ediciones' => $hechas, 'ficheros' => $ficheros, 'estado' => 'a medias'];
        }

        $datos = [
            'edicion'      => $edicion,
            'bits'         => publicar_bits((int) $edicion['id']),
            'base'         => $base,
            'alta_abierta' => $alta,
            // Firma los enlaces contados. Si falta, los enlaces salen
            // directos a la fuente y simplemente no se cuentan.
            'secreto'      => (string) ($config['secretos']['secreto_hmac'] ?? ''),
        ];

        $html = publicar_plantilla('edicion', $datos);

        $ficheros += publicar_escribir($publico . '/' . web_ruta_edicion($edicion['slug']), $html) ? 1 : 0;
        $hechas++;

        // La edicion mas reciente es tambien la portada.
        if ($indice === 0) {
            $ficheros += publicar_escribir($publico . '/index.html', $html) ? 1 : 0;
        }
    }

    $ficheros += publicar_escribir(
        $publico . '/archivo.html',
        publicar_plantilla('archivo', ['ediciones' => $ediciones, 'base' => $base, 'alta_abierta' => $alta])
    ) ? 1 : 0;

    // Fichas de proveedor: solo las de los que tienen algo publicado.
    $proveedores = publicar_proveedores();

    foreach ($proveedores as $proveedor) {
        $ficheros += publicar_escribir(
            $publico . '/' . web_ruta_proveedor((string) $proveedor['slug']),
            publicar_plantilla('proveedor', [
                'proveedor'    => $proveedor,
                'bits'         => publicar_bits_proveedor((int) $proveedor['id']),
                'base'         => $base,
                'alta_abierta' => $alta,
            ])
        ) ? 1 : 0;
    }

    // Indice de busqueda y el buscador, que corre entero en el navegador.
    $indice = publicar_indice();

    $ficheros += publicar_escribir(
        $publico . '/indice.json',
        (string) json_encode($indice, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    ) ? 1 : 0;

    $ficheros += publicar_escribir(
        $publico . '/buscar.js',
        publicar_plantilla('buscarjs', [])
    ) ? 1 : 0;

    $ficheros += publicar_escribir(
        $publico . '/buscar.html',
        publicar_plantilla('buscar', [
            'base'         => $base,
            'total'        => count($indice),
            'alta_abierta' => $alta,
        ])
    ) ? 1 : 0;

    $ficheros += publicar_escribir(
        $publico . '/proveedores.html',
        publicar_plantilla('proveedores', [
            'proveedores'  => $proveedores,
            'base'         => $base,
            'alta_abierta' => $alta,
        ])
    ) ? 1 : 0;

    $ficheros += publicar_escribir(
        $publico . '/sobre.html',
        publicar_plantilla('sobre', ['base' => $base, 'alta_abierta' => $alta])
    ) ? 1 : 0;

    $ficheros += publicar_escribir(
        $publico . '/feed.xml',
        publicar_plantilla('feed', ['ediciones' => $ediciones, 'base' => $base])
    ) ? 1 : 0;

    ajuste_guardar('publicar_firma', $firma);

    return ['ediciones' => $hechas, 'ficheros' => $ficheros, 'estado' => 'publicado'];
}

/**
 * Las ediciones publicables, de la mas reciente a la mas antigua.
 */
function publicar_ediciones(): array
{
    return bd()->query(
        "SELECT id, numero, slug, titulo, intro, fecha_prevista, fecha_envio, estado
           FROM ediciones
          WHERE estado IN ('cerrada', 'enviada')
          ORDER BY numero DESC"
    )->fetchAll();
}

/**
 * Los bits de una edicion, en orden, con el enlace a la fuente original.
 *
 * El enlace es el del mejor item del racimo: el que mas puntua es el que
 * mejor cuenta la noticia, y es el que se ofrece al lector.
 */
function publicar_bits(int $edicion_id): array
{
    $sql = "SELECT b.id, b.titular, b.cuerpo, b.por_que, b.categoria, b.madurez, b.tipo,
                   (SELECT i.url FROM items i
                     WHERE i.racimo_id = b.racimo_id AND i.estado <> 'descartado'
                     ORDER BY i.puntuacion DESC, i.id ASC LIMIT 1) AS url,
                   (SELECT f.nombre FROM items i
                      JOIN fuentes f ON f.id = i.fuente_id
                     WHERE i.racimo_id = b.racimo_id AND i.estado <> 'descartado'
                     ORDER BY i.puntuacion DESC, i.id ASC LIMIT 1) AS fuente,
                   -- Los proveedores del racimo, para enlazar sus fichas.
                   -- slug y nombre en la misma cadena para no hacer una
                   -- consulta por bit.
                   (SELECT GROUP_CONCAT(DISTINCT CONCAT(p.slug, '|', p.nombre) SEPARATOR ';;')
                      FROM items i
                      JOIN item_proveedor ip ON ip.item_id = i.id
                      JOIN proveedores p     ON p.id = ip.proveedor_id
                     WHERE i.racimo_id = b.racimo_id AND i.estado <> 'descartado') AS proveedores
              FROM bits b
             WHERE b.edicion_id = ? AND b.estado = 'publicado'
             ORDER BY b.orden ASC, b.id ASC";

    $st = bd()->prepare($sql);
    $st->execute([$edicion_id]);

    return $st->fetchAll();
}

/**
 * El indice de busqueda: todos los bits publicados, ya normalizados.
 *
 * Se descarga entero en el navegador, asi que se queda en lo justo. Con mil
 * bits ronda los doscientos kilobytes; el dia que se acerque al megabyte
 * habra que partirlo por anos, pero a veinte bits por semana eso son cuatro
 * anos de boletin.
 */
function publicar_indice(): array
{
    $sql = "SELECT b.id, b.titular, b.por_que, b.cuerpo, b.categoria,
                   e.numero, e.slug, e.fecha_prevista,
                   (SELECT GROUP_CONCAT(DISTINCT CONCAT(p.slug, '|', p.nombre) SEPARATOR ';;')
                      FROM items i
                      JOIN item_proveedor ip ON ip.item_id = i.id
                      JOIN proveedores p     ON p.id = ip.proveedor_id
                     WHERE i.racimo_id = b.racimo_id AND i.estado <> 'descartado') AS proveedores
              FROM bits b
              JOIN ediciones e ON e.id = b.edicion_id
             WHERE b.estado = 'publicado'
             ORDER BY e.numero DESC, b.orden ASC";

    $filas = [];

    foreach (bd()->query($sql) as $bit) {
        $filas[] = web_fila_indice($bit);
    }

    return $filas;
}

/**
 * Proveedores con al menos un bit publicado, con su recuento.
 *
 * La relacion no es directa: un bit cuelga de un racimo, y los proveedores se
 * detectan en los items de ese racimo. Por eso el salto de tres tablas.
 */
function publicar_proveedores(): array
{
    $sql = "SELECT p.id, p.nombre, p.slug, p.categoria,
                   COUNT(DISTINCT b.id) AS bits
              FROM proveedores p
              JOIN item_proveedor ip ON ip.proveedor_id = p.id
              JOIN items i           ON i.id = ip.item_id
              JOIN bits b            ON b.racimo_id = i.racimo_id
             WHERE b.estado = 'publicado'
             GROUP BY p.id, p.nombre, p.slug, p.categoria
             ORDER BY p.nombre";

    return bd()->query($sql)->fetchAll();
}

/**
 * Los bits publicados que mencionan a un proveedor, del mas reciente al mas
 * antiguo, con la edicion en la que salieron.
 */
function publicar_bits_proveedor(int $proveedor_id): array
{
    $sql = "SELECT DISTINCT b.id, b.titular, b.por_que,
                   e.numero, e.slug, e.fecha_prevista
              FROM bits b
              JOIN ediciones e       ON e.id = b.edicion_id
              JOIN items i           ON i.racimo_id = b.racimo_id
              JOIN item_proveedor ip ON ip.item_id = i.id
             WHERE ip.proveedor_id = ?
               AND b.estado = 'publicado'
             ORDER BY e.numero DESC, b.orden ASC";

    $st = bd()->prepare($sql);
    $st->execute([$proveedor_id]);

    return $st->fetchAll();
}

/**
 * Firma de todo lo publicable: ediciones, bits y su ultima modificacion.
 *
 * Si un bit se corrige despues de publicar, cambia su 'modificado' y con el
 * la firma, asi que la correccion sale sola en la siguiente pasada del cron.
 */
function publicar_firma(array $ediciones): string
{
    if (!$ediciones) {
        return '';
    }

    $st = bd()->query(
        "SELECT COUNT(*) AS bits, COALESCE(MAX(modificado), '') AS ultimo
           FROM bits WHERE estado = 'publicado'"
    );
    $bits = $st->fetch();

    $partes = [
        // Un cambio en las plantillas tiene que regenerar la web aunque no
        // haya cambiado ni una edicion. Si no, se despliega un arreglo de
        // diseno y no se ve hasta la semana siguiente.
        publicar_firma_plantillas(),
        // Sin esto, configurar el proveedor de correo no cambiaria nada
        // visible hasta que se publicara una edicion nueva: las paginas ya
        // generadas seguirian diciendo que el alta no esta abierta.
        correo_configurado() ? 'alta' : 'sin-alta',
        count($ediciones),
        (string) ($ediciones[0]['slug'] ?? ''),
        (int) ($bits['bits'] ?? 0),
        (string) ($bits['ultimo'] ?? ''),
    ];

    foreach ($ediciones as $edicion) {
        $partes[] = $edicion['id'] . ':' . $edicion['estado'] . ':' . $edicion['titulo'];
    }

    return sha1(implode('|', $partes));
}

/**
 * Firma de las plantillas: nombre, fecha y tamano de cada una.
 *
 * Con la fecha basta, porque un despliegue por Git reescribe el fichero y la
 * cambia. Se anade el tamano por si dos escrituras caen en el mismo segundo.
 */
function publicar_firma_plantillas(): string
{
    $ficheros = glob(dirname(__DIR__) . '/plantillas/web/*.php') ?: [];
    sort($ficheros);

    $partes = [];

    foreach ($ficheros as $fichero) {
        $partes[] = basename($fichero) . ':' . (int) @filemtime($fichero) . ':' . (int) @filesize($fichero);
    }

    return sha1(implode('|', $partes));
}

/**
 * Renderiza una plantilla de plantillas/web/ y devuelve su salida.
 */
function publicar_plantilla(string $nombre, array $datos): string
{
    $ruta = dirname(__DIR__) . '/plantillas/web/' . $nombre . '.php';

    if (!is_readable($ruta)) {
        throw new RuntimeException('Falta la plantilla ' . $nombre . '.php');
    }

    extract($datos, EXTR_SKIP);

    ob_start();
    require $ruta;

    return (string) ob_get_clean();
}

/**
 * Escribe un fichero solo si su contenido ha cambiado. Devuelve true si lo
 * ha escrito.
 *
 * La escritura es atomica: primero un temporal en la misma carpeta y despues
 * un rename, que en el mismo sistema de ficheros no se puede quedar a medias.
 * Sin eso, un visitante puede llegar justo cuando el fichero esta escrito por
 * la mitad y llevarse una pagina rota.
 */
function publicar_escribir(string $ruta, string $contenido): bool
{
    if (is_file($ruta) && file_get_contents($ruta) === $contenido) {
        return false;
    }

    $carpeta = dirname($ruta);

    if (!is_dir($carpeta) && !@mkdir($carpeta, 0755, true) && !is_dir($carpeta)) {
        throw new RuntimeException('No se ha podido crear ' . $carpeta);
    }

    $temporal = $carpeta . '/.' . basename($ruta) . '.' . getmypid();

    if (@file_put_contents($temporal, $contenido) === false) {
        throw new RuntimeException('No se ha podido escribir ' . $ruta);
    }

    if (!@rename($temporal, $ruta)) {
        @unlink($temporal);
        throw new RuntimeException('No se ha podido reemplazar ' . $ruta);
    }

    return true;
}

// Ejecucion directa por linea de comandos, para poder probar sin esperar al
// cron. El mismo bloque que cierra cron/ingesta.php.
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {
    date_default_timezone_set('UTC');

    $resumen = publicar_pendiente(microtime(true) + (float) (config('presupuesto_cron') ?? 25));

    printf(
        "publicar: %s, %d ediciones, %d ficheros escritos\n",
        $resumen['estado'],
        $resumen['ediciones'],
        $resumen['ficheros']
    );
}
