<?php
/**
 * Generador de la web estatica.
 *
 * Se ejecuta desde cron/tareas.php, o directamente por linea de comandos:
 *   php cron/publicar.php
 *
 * Convierte lo publicado en HTML dentro de publico/. A partir de ahi el sitio
 * lo sirve Apache sin tocar PHP ni la base de datos, que es lo unico que
 * aguanta una portada compartida el dia que algo se comparta en LinkedIn.
 *
 * La web esta ordenada por el dia en que este radar descubrio cada noticia, no
 * por ediciones. Una edicion solo existia al cerrarse, asi que lo de hoy no se
 * veia hasta mañana; un dia existe en cuanto cae en el la primera noticia.
 *
 * Lo que escribe:
 *
 *   index.html          la portada: el rio de los ultimos dias
 *   d/<AAAA-MM-DD>/     cada dia, entero
 *   archivo.html        la lista de dias
 *   t/<tema>/           una ficha por tema, con su propio feed.xml
 *   m/<medio>/          una ficha por medio
 *   feed.xml            RSS de lo ultimo
 *   buscar.html         el explorador, mas indice.json
 *   estilo.css          la hoja del sitio
 *   favicon.svg         el sello, en un fichero aparte
 *   fuentes/            la tipografia de --titular, copiada tal cual
 *   og-generica.png      la tarjeta que se comparte, para todo lo que no es un dia
 *   d/<AAAA-MM-DD>/og.png  la tarjeta de ese dia, con su titular de cabeza
 *   robots.txt
 *   sitemap.xml         direcciones para Google, con lastmod por dia
 *   estadisticas.html   cuadro de mandos con cifras externas -no de esta base-
 *   cumplimiento.html   calendario normativo, con fecha y fuente en cada norma
 *   tendencias.html     que tema sube y cual baja, con los datos propios
 *   glosario.html       las siglas del sector, explicadas una vez
 *   legal.html          aviso legal, cookies y datos personales
 *   agentica.html        reserva agentica: MCP, ACP, UCP y AP2, con fecha y fuente
 *   cifras.json          las mismas cifras de estadisticas.html, en JSON
 *
 * No regenera en cada pasada: calcula una firma de lo publicable y solo
 * trabaja si ha cambiado. Asi el cron -que pasa cada cinco minutos- no
 * reescribe la web entera para nada, y a la vez cualquier correccion de un bit
 * ya publicado se recoge sola en la siguiente vuelta.
 */

require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/texto.php';
require_once dirname(__DIR__) . '/lib/web.php';
require_once dirname(__DIR__) . '/lib/bits.php';
require_once dirname(__DIR__) . '/lib/correo.php';
require_once dirname(__DIR__) . '/lib/cifras.php';
require_once dirname(__DIR__) . '/lib/cumplimiento.php';
require_once dirname(__DIR__) . '/lib/glosario.php';
require_once dirname(__DIR__) . '/lib/agentica.php';
require_once dirname(__DIR__) . '/lib/imagen_social.php';

/**
 * Publica lo que haya pendiente.
 *
 * @param float $limite Marca de tiempo a partir de la cual no se empiezan
 *                      paginas nuevas.
 */
function publicar_pendiente(float $limite): array
{
    // Subido de 80: la portada dejo de partirse por dias -es un solo flujo
    // continuo- y ochenta se notaba enseguida, sobre todo justo despues de
    // aprobar un lote grande desde el panel. Sin fotos ni JS, trescientas
    // fichas de texto siguen pesando poco: lo caro de una pagina de noticias
    // de verdad son las imagenes, y esta no tiene ninguna.
    $tope_portada = max(10, (int) ajuste('web_bits_portada', '300'));
    $tope_archivo = max(10, (int) ajuste('web_dias_archivo', '180'));

    $dias  = publicar_dias($tope_archivo);
    $firma = publicar_firma($dias);

    if ($firma === (string) ajuste('publicar_firma', '')) {
        return ['dias' => 0, 'ficheros' => 0, 'estado' => 'sin cambios'];
    }

    $config  = config();
    $base    = rtrim((string) ($config['sitio']['url'] ?? ''), '/');
    $publico = (string) ($config['rutas']['publico'] ?? dirname(__DIR__) . '/publico');

    $ficheros = 0;
    $hechas   = 0;

    // El rio: los ultimos bits, ya partidos por dias. Es la portada, y de aqui
    // sale tambien el RSS, asi que se pide una vez.
    $rio = publicar_rio($tope_portada);

    // Si no hay proveedor de correo configurado, el bloque de alta se pinta
    // como "todavia no". Mejor eso que un formulario que no lleva a ningun
    // sitio.
    $alta = correo_configurado();

    // Los temas y los medios que tienen algo publicado. Se piden una vez para
    // todas las paginas: son los mismos en todas y son dos consultas.
    $temas  = publicar_temas();
    $medios = publicar_medios();
    $radar  = publicar_radar_total();
    $resumen_dias = publicar_resumen_dias();
    $mas_leidos   = publicar_mas_leidos();
    $tendencias   = publicar_tendencias();

    $publicados = (int) bd()->query("SELECT COUNT(*) FROM bits WHERE estado = 'publicado'")->fetchColumn();
    $aviso      = publicar_aviso($publicados, $tope_portada);

    // Las cifras de la cabecera. Se calculan antes de escribir nada porque van
    // en todas las paginas, no solo en la portada: quien llega por un enlace a
    // la ficha de un tema tambien quiere saber si esto esta vivo.
    $panel = publicar_panel($publicados, count($medios), count($temas), (string) $aviso['cuando']);

    // La hoja de estilo, el guion y robots.txt no dependen de lo publicado,
    // pero se escriben aqui: son parte de la salida y no tienen otro sitio
    // donde vivir. Van los primeros porque de su contenido sale la version que
    // cuelga de sus URLs.
    $ficheros += publicar_escribir($publico . '/estilo.css', publicar_plantilla('estilo', [])) ? 1 : 0;
    $ficheros += publicar_escribir($publico . '/buscar.js', publicar_plantilla('buscarjs', [])) ? 1 : 0;
    $ficheros += publicar_escribir($publico . '/robots.txt', publicar_plantilla('robots', ['base' => $base])) ? 1 : 0;
    $ficheros += publicar_escribir($publico . '/favicon.svg', publicar_plantilla('favicon', [])) ? 1 : 0;

    // La tipografia de --titular: un fichero binario, no una plantilla, asi
    // que se copia tal cual en vez de pasar por publicar_plantilla(). Vive
    // junto al resto de plantillas/web/ y no en publico/ para que un
    // despliegue limpio -publico/ no esta en el repositorio- la traiga sola,
    // igual que trae iconos.php o cabecera.php.
    $ficheros += publicar_escribir(
        $publico . '/fuentes/BigShoulders-Titular-Bold.woff2',
        (string) file_get_contents(__DIR__ . '/../plantillas/web/fuentes/BigShoulders-Titular-Bold.woff2')
    ) ? 1 : 0;

    // La tarjeta generica que se comparte desde cualquier pagina que no sea
    // un dia -og-image tiene que ser PNG de verdad, ninguna red social
    // rasteriza SVG en la vista previa, asi que este fichero no es un primo
    // del favicon aunque se escriba igual-.
    $ficheros += publicar_escribir(
        $publico . '/og-generica.png',
        imagen_social_png('El radar de tecnología hotelera')
    ) ? 1 : 0;

    // El .htaccess le pone un mes de cache a los dos ficheros, y se reescriben
    // siempre en el mismo sitio. Sin colgar el hash de su contenido de la URL,
    // quien ya hubiera visitado el sitio seguiria viendo la version vieja
    // durante treinta dias aunque el servidor tuviera la nueva.
    $comunes = [
        'base'         => $base,
        'version'      => web_version($publico . '/estilo.css'),
        'version_js'   => web_version($publico . '/buscar.js'),
        'alta_abierta' => $alta,
        'temas'        => $temas,
        'medios'       => $medios,
        'radar'        => $radar,
        'resumen'      => $resumen_dias,
        'mas_leidos'   => $mas_leidos,
        'tendencias'   => $tendencias,
        'panel'        => $panel,
        'secreto'      => (string) ($config['secretos']['secreto_hmac'] ?? ''),
    ];

    // La portada: el rio entero, con sus dias por dentro. Una sola pagina y
    // una sola consulta de fuentes para los ochenta bits.
    if ($rio) {
        $racimos = [];

        foreach ($rio as $tramo) {
            foreach ($tramo['bits'] as $bit) {
                $racimos[] = (int) ($bit['racimo_id'] ?? 0);
            }
        }

        $ficheros += publicar_escribir(
            $publico . '/index.html',
            publicar_plantilla('portada', $comunes + [
                'rio'     => $rio,
                'fuentes' => publicar_fuentes($racimos),
                'dias'    => $dias,
            ])
        ) ? 1 : 0;
    } else {
        // Sin nada publicado, la portada es la pagina provisional. El
        // generador es el unico que puede mantenerla al dia: el instalador la
        // escribe una vez y despues desaparece.
        $ficheros += publicar_escribir(
            $publico . '/index.html',
            publicar_plantilla('provisional', $comunes)
        ) ? 1 : 0;
    }

    // Una pagina por dia. Se rehacen los dias que estan en la portada -que son
    // los que pueden haber cambiado- y se escriben los antiguos solo si les
    // falta la suya: rehacer ciento ochenta paginas cada cinco minutos para
    // cambiar una seria gastar el presupuesto entero en no mover nada.
    $recientes = array_column($rio, 'dia');

    foreach ($dias as $dia) {
        if (microtime(true) >= $limite) {
            // Sin firma guardada, la proxima pasada vuelve a empezar. Se
            // reescriben ficheros ya escritos, pero nunca queda un dia sin
            // generar por haberse quedado sin tiempo.
            return ['dias' => $hechas, 'ficheros' => $ficheros, 'estado' => 'a medias'];
        }

        $ruta = $publico . '/' . web_ruta_dia((string) $dia['dia']);

        if (!in_array($dia['dia'], $recientes, true) && is_file($ruta)) {
            continue;
        }

        $bits    = publicar_bits((string) $dia['dia'], 500);
        $racimos = array_map(static fn (array $b): int => (int) ($b['racimo_id'] ?? 0), $bits);

        $ficheros += publicar_escribir($ruta, publicar_plantilla('dia', $comunes + [
            'dia'     => $dia,
            'bits'    => $bits,
            'fuentes' => publicar_fuentes($racimos),
        ])) ? 1 : 0;

        // La tarjeta de ese dia, con su titular de cabeza: es la que ve
        // quien recibe un enlace a /d/<fecha>/ por WhatsApp o LinkedIn.
        if ($bits) {
            $ficheros += publicar_escribir(
                dirname($ruta) . '/og.png',
                imagen_social_png((string) $bits[0]['titular'], web_fecha_larga(substr((string) $dia['dia'], 0, 10)))
            ) ? 1 : 0;
        }

        $hechas++;
    }

    $ficheros += publicar_escribir(
        $publico . '/archivo.html',
        publicar_plantilla('archivo', $comunes + ['dias' => $dias])
    ) ? 1 : 0;

    // Fichas de tema. Sustituyen a las de proveedor, y el cambio no es
    // cosmetico: una ficha de proveedor contesta "que se ha dicho de Mews",
    // que es la pregunta de Mews; una de tema contesta "que esta pasando con
    // los pagos", que es la de un hotel.
    foreach ($temas as $tema) {
        if (microtime(true) >= $limite) {
            // Como con los dias: sin firma guardada, la proxima pasada vuelve
            // a empezar y no queda ninguna ficha a medias.
            return ['dias' => $hechas, 'ficheros' => $ficheros, 'estado' => 'a medias'];
        }

        $bits_tema = publicar_bits_tema((string) $tema['slug']);

        $ficheros += publicar_escribir(
            $publico . '/' . web_ruta_tema((string) $tema['slug']),
            publicar_plantilla('tema', $comunes + [
                'tema'  => $tema,
                'bits'  => $bits_tema,
                'otros' => array_values(array_filter(
                    $temas,
                    static fn (array $otro): bool => $otro['slug'] !== $tema['slug']
                )),
            ])
        ) ? 1 : 0;

        $ficheros += publicar_escribir(
            $publico . '/t/' . web_slug_seguro((string) $tema['slug']) . '/feed.xml',
            publicar_plantilla('feed_tema', [
                'tema' => $tema,
                'bits' => $bits_tema,
                'base' => $base,
            ])
        ) ? 1 : 0;
    }

    // Fichas de medio: a quien estamos leyendo de verdad.
    foreach ($medios as $medio) {
        if (microtime(true) >= $limite) {
            return ['dias' => $hechas, 'ficheros' => $ficheros, 'estado' => 'a medias'];
        }

        $ficheros += publicar_escribir(
            $publico . '/' . web_ruta_medio((string) $medio['slug']),
            publicar_plantilla('medio', $comunes + [
                'medio' => $medio,
                'bits'  => publicar_bits_medio((string) $medio['nombre']),
                'otros' => array_values(array_filter(
                    $medios,
                    static fn (array $otro): bool => $otro['slug'] !== $medio['slug']
                )),
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
        $publico . '/buscar.html',
        publicar_plantilla('buscar', $comunes + ['total' => count($indice['bits'])])
    ) ? 1 : 0;

    $ficheros += publicar_escribir(
        $publico . '/temas.html',
        publicar_plantilla('temas', $comunes)
    ) ? 1 : 0;

    $ficheros += publicar_escribir(
        $publico . '/medios.html',
        publicar_plantilla('medios', $comunes)
    ) ? 1 : 0;

    $ficheros += publicar_escribir(
        $publico . '/sobre.html',
        publicar_plantilla('sobre', $comunes)
    ) ? 1 : 0;

    $ficheros += publicar_escribir(
        $publico . '/estadisticas.html',
        publicar_plantilla('estadisticas', $comunes)
    ) ? 1 : 0;

    $ficheros += publicar_escribir(
        $publico . '/cumplimiento.html',
        publicar_plantilla('cumplimiento', $comunes)
    ) ? 1 : 0;

    $ficheros += publicar_escribir(
        $publico . '/tendencias.html',
        publicar_plantilla('tendencias', $comunes)
    ) ? 1 : 0;

    $ficheros += publicar_escribir(
        $publico . '/glosario.html',
        publicar_plantilla('glosario', $comunes)
    ) ? 1 : 0;

    $ficheros += publicar_escribir(
        $publico . '/legal.html',
        publicar_plantilla('legal', $comunes)
    ) ? 1 : 0;

    $ficheros += publicar_escribir(
        $publico . '/agentica.html',
        publicar_plantilla('agentica', $comunes)
    ) ? 1 : 0;

    $ficheros += publicar_escribir(
        $publico . '/feed.xml',
        publicar_plantilla('feed', [
            'rio'  => $rio,
            'base' => $base,
        ])
    ) ? 1 : 0;

    $ficheros += publicar_escribir(
        $publico . '/sitemap.xml',
        publicar_plantilla('sitemap', [
            'base'   => $base,
            'dias'   => $dias,
            'temas'  => $temas,
            'medios' => $medios,
        ])
    ) ? 1 : 0;

    // Las mismas cifras de /estadisticas.html, en JSON: es lo que hace que
    // otros citen estos datos en vez de rehacerlos.
    $ficheros += publicar_escribir(
        $publico . '/cifras.json',
        (string) json_encode(
            cifras_exportar(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        )
    ) ? 1 : 0;

    // Y se barre lo que ya no le corresponde a nada: un dia caido del archivo
    // no puede seguir servido en su direccion de siempre. Las ediciones se
    // barren enteras: ya no existen como concepto y sus paginas enlazaban a un
    // sitio que ya no tiene sentido.
    $barridos  = publicar_barrer($publico . '/d', array_column($dias, 'dia'));
    $barridos += publicar_barrer($publico . '/e', [], true);
    $barridos += publicar_barrer($publico . '/t', array_column($temas, 'slug'));
    $barridos += publicar_barrer($publico . '/m', array_column($medios, 'slug'));

    // Las fichas de proveedor ya no se escriben: se barren enteras. Aqui si se
    // permite vaciar la carpeta, porque el vacio es la intencion y no el
    // sintoma de que algo ha ido mal.
    $barridos += publicar_barrer($publico . '/p', [], true);

    publicar_recordar($publicados, count($medios), count($temas), $aviso);

    ajuste_guardar('publicar_firma', $firma);

    return [
        'dias'      => $hechas,
        'bits'      => $publicados,
        'ficheros'  => $ficheros,
        'barridos'  => $barridos,
        // Lo que ha cambiado viaja en el resumen: el despachador lo usa para
        // el aviso por correo sin tener que volver a preguntarselo a la base.
        'nuevos'      => (int) $aviso['nuevos'],
        'archivados'  => (int) $aviso['archivados'],
        'estado'    => 'publicado',
    ];
}

/**
 * Borra las carpetas generadas que ya no le corresponden a nada.
 *
 * Solo entra en carpetas de un nivel escritas por el propio generador y solo
 * borra dentro de ellas los ficheros que el mismo escribe. No es paranoia: de
 * aqui sale un rmdir, y un rmdir con una ruta mal calculada se lleva por
 * delante lo que no debe.
 *
 * @return int Cuantas carpetas se han borrado.
 */
function publicar_barrer(string $carpeta, array $vivos, bool $vaciar = false): int
{
    if (!is_dir($carpeta)) {
        return 0;
    }

    $hijas = array_values(array_filter(
        scandir($carpeta) ?: [],
        static fn (string $nombre): bool => is_dir($carpeta . '/' . $nombre)
            && $nombre !== '.'
            && $nombre !== '..'
    ));

    // Un barrido que se lo lleva todo no es un barrido: es un sintoma. Pasa si
    // alguien reabre las ediciones a mano, o si una revision las deja vacias, y
    // el precio seria borrar el archivo entero de una web que no esta en el
    // repositorio y solo vuelve con una regeneracion completa.
    if (!$vivos && $hijas && !$vaciar) {
        error_log('Bit & Breakfast, barrido abortado en ' . $carpeta . ': no queda ningun slug vivo');

        return 0;
    }

    $borradas = 0;

    foreach (web_sobran($hijas, $vivos) as $sobra) {
        $ruta = $carpeta . '/' . $sobra;

        // La carpeta tiene que llamarse como la escribio el generador. Si no,
        // no es suya y no se toca.
        if ($sobra !== web_slug_seguro($sobra)) {
            continue;
        }

        @unlink($ruta . '/index.html');
        // Las fichas de tema llevan tambien su feed.xml: sin borrarlo, rmdir()
        // se niega a vaciar la carpeta -no esta vacia- y la carpeta retirada
        // se queda huerfana para siempre.
        @unlink($ruta . '/feed.xml');

        if (@rmdir($ruta)) {
            $borradas++;
        }
    }

    return $borradas;
}

/**
 * Los temas que tienen algo publicado, con cuantos bits cada uno.
 *
 * Solo los que tienen algo: una lista de once temas de los que ocho estan
 * vacios no invita a explorar, informa de lo que falta.
 *
 * @return array Filas con 'slug', 'nombre' y 'bits'.
 */
function publicar_temas(): array
{
    $sql = "SELECT b.categoria, COUNT(*) AS bits
              FROM bits b
             WHERE b.estado = 'publicado'
             GROUP BY b.categoria";

    $catalogo = bits_categorias();
    $cuenta   = [];

    foreach (bd()->query($sql) ?: [] as $fila) {
        // Los bits viejos llevan el nombre viejo de su categoria: se suman al
        // que corresponde en vez de aparecer como un tema aparte sin nombre.
        $slug = bits_categoria_canonica((string) $fila['categoria']);

        if ($slug === '') {
            continue;
        }

        $cuenta[$slug] = ($cuenta[$slug] ?? 0) + (int) $fila['bits'];
    }

    arsort($cuenta);

    $temas = [];

    foreach ($cuenta as $slug => $bits) {
        $temas[] = ['slug' => $slug, 'nombre' => $catalogo[$slug] ?? $slug, 'bits' => $bits];
    }

    return $temas;
}

/**
 * Que ha cambiado desde la ultima vez que se genero la web.
 *
 * Contesta tres cosas y nada mas: cuando fue, cuantas noticias han entrado y
 * cuantas han pasado al archivo. Es lo que se pinta arriba de la web y lo que
 * va en el aviso del cron.
 *
 * No hay historico ni tabla de cambios: hacen falta tres numeros, no un diario
 * de operaciones. La memoria son dos ajustes -cuando fue y cuantos bits habia-
 * y el delta sale de compararlos con lo de ahora.
 *
 * @param int $publicados Cuantos bits hay publicados ahora mismo.
 * @param int $tope       Cuantos caben en la portada.
 *
 * @return array ['cuando' => string, 'nuevos' => int, 'archivados' => int]
 */
function publicar_aviso(int $publicados, int $tope): array
{
    $antes = (int) ajuste('web_bits_frente', '0');
    $ahora = gmdate('Y-m-d H:i:s');

    // Primera vez: todo lo que hay es nuevo y no ha salido nada todavia.
    if ($antes === 0) {
        return ['cuando' => $ahora, 'nuevos' => $publicados, 'archivados' => 0];
    }

    $nuevos = max(0, $publicados - $antes);

    // Lo archivado ya no es "la edicion anterior entera": en un rio, lo que se
    // va es lo que los nuevos empujan fuera de la portada. Si todavia cabe
    // todo, no se ha ido nada.
    $archivados = max(0, min($nuevos, $publicados - $tope));

    return ['cuando' => $ahora, 'nuevos' => $nuevos, 'archivados' => $archivados];
}

/**
 * Las cifras de la cabecera: cuando fue esto, cuando sera lo siguiente y
 * cuanto ha crecido cada cosa desde la vez anterior.
 *
 * Las tres parejas -noticias, medios, temas- se cuentan igual: lo que hay
 * ahora, y lo que hay ahora menos lo que habia cuando se genero la web la vez
 * anterior. No hay historico: tres numeros guardados contestan la pregunta, y
 * un diario de operaciones seria otra tabla que mantener.
 *
 * Lo de "cuando sera la siguiente" no es una promesa, es una estimacion: sale
 * de la cadencia que el propio cron mide al pasar. Si alguien cambia la
 * frecuencia en el panel del alojamiento, esto se entera solo a la siguiente
 * pasada, sin que nadie toque nada.
 */
function publicar_panel(int $bits, int $medios, int $temas, string $cuando): array
{
    $cadencia = max(1, (int) ajuste('cron_cada_minutos', '60'));
    $momento  = strtotime($cuando . ' UTC') ?: time();

    return [
        'cuando'    => $cuando,
        'siguiente' => gmdate('Y-m-d H:i:s', $momento + $cadencia * 60),
        'cadencia'  => $cadencia,
        'noticias'  => [
            'total'  => $bits,
            'nuevas' => max(0, $bits - (int) ajuste('web_bits_frente', '0')),
        ],
        'medios'    => [
            'total'  => $medios,
            'nuevas' => max(0, $medios - (int) ajuste('web_medios_frente', '0')),
        ],
        'temas'     => [
            'total'  => $temas,
            'nuevas' => max(0, $temas - (int) ajuste('web_temas_frente', '0')),
        ],
    ];
}

/**
 * Guarda el estado con el que se compara la proxima vez.
 *
 * Se llama al final y solo si la generacion ha terminado: si se guardara antes
 * y el proceso muriera a la mitad, el cambio se habria dado por contado sin
 * que nadie lo hubiera visto publicado.
 */
function publicar_recordar(int $publicados, int $medios, int $temas, array $aviso): void
{
    ajuste_guardar('web_actualizada_en', (string) $aviso['cuando']);
    ajuste_guardar('web_bits_frente', (string) $publicados);
    ajuste_guardar('web_medios_frente', (string) $medios);
    ajuste_guardar('web_temas_frente', (string) $temas);
}

/**
 * Cuantos bits y de que temas trae cada dia.
 *
 * Es lo que convierte el archivo en algo que se puede ojear: una lista de
 * fechas no dice nada, y con esto se ve de un vistazo que dia fue el de
 * ciberseguridad y cual el de distribucion.
 *
 * @return array [dia => ['bits' => int, 'temas' => string[]]]
 */
function publicar_resumen_dias(): array
{
    $sql = "SELECT b.dia, b.categoria, COUNT(*) AS bits
              FROM bits b
             WHERE b.estado = 'publicado' AND b.dia IS NOT NULL
             GROUP BY b.dia, b.categoria";

    $crudo = [];

    foreach (bd()->query($sql) ?: [] as $fila) {
        $id   = substr((string) $fila['dia'], 0, 10);
        $tema = bits_categoria_canonica((string) $fila['categoria']);

        $crudo[$id]['bits'] = ($crudo[$id]['bits'] ?? 0) + (int) $fila['bits'];

        if ($tema !== '') {
            $crudo[$id]['temas'][$tema] = ($crudo[$id]['temas'][$tema] ?? 0) + (int) $fila['bits'];
        }
    }

    $resumen = [];

    foreach ($crudo as $id => $datos) {
        $temas = $datos['temas'] ?? [];
        arsort($temas);

        $resumen[$id] = [
            'bits' => (int) ($datos['bits'] ?? 0),
            // Cinco como mucho: a partir de ahi es una fila de iconos y deja
            // de decir de que iba el dia.
            'temas' => array_slice(array_keys($temas), 0, 5),
        ];
    }

    return $resumen;
}

/**
 * Los bits mas leidos de los ultimos siete dias, contando solo clics que no
 * parecen de un rastreador de correo (api/ir.php ya los marca al guardarlos).
 *
 * Es una foto, no un dato en vivo, igual que el resto de la web: se calcula
 * aqui y se congela hasta la proxima vez que haya algo nuevo que publicar, no
 * en cada clic. Meterla en publicar_firma() habria significado regenerar el
 * sitio entero cada vez que alguien pulsara un enlace, que es exactamente lo
 * que esa firma existe para evitar.
 *
 * Por debajo de tres bits con al menos dos clics cada uno no hay ranking, hay
 * ruido: hasta que el sitio tenga trafico de sobra, la caja simplemente no se
 * pinta ese dia. Es preferible no enseñar nada a enseñar "lo mas leido" con
 * una sola entrada.
 *
 * @return array Filas con 'id', 'titular', 'categoria', 'dia', 'fuente', 'url' y 'clics'.
 */
function publicar_mas_leidos(int $dias = 7, int $tope = 6): array
{
    $sql = "SELECT b.id, b.titular, b.categoria, b.dia, COUNT(*) AS clics,
                   (SELECT f.nombre FROM items i
                      JOIN fuentes f ON f.id = i.fuente_id
                     WHERE i.racimo_id = b.racimo_id AND i.estado <> 'descartado'
                     ORDER BY (i.idioma = 'es') DESC, i.puntuacion DESC, i.id ASC LIMIT 1) AS fuente,
                   (SELECT i.url FROM items i
                     WHERE i.racimo_id = b.racimo_id AND i.estado <> 'descartado'
                     ORDER BY (i.idioma = 'es') DESC, i.puntuacion DESC, i.id ASC LIMIT 1) AS url
              FROM clics c
              JOIN bits b ON b.id = c.bit_id
             WHERE c.sospechoso = 0
               AND c.creado >= (NOW() - INTERVAL ? DAY)
               AND b.estado = 'publicado'
             GROUP BY b.id
            HAVING clics >= 2
             ORDER BY clics DESC, b.id DESC
             LIMIT ?";

    $st = bd()->prepare($sql);
    $st->bindValue(1, max(1, $dias), PDO::PARAM_INT);
    $st->bindValue(2, max(1, $tope), PDO::PARAM_INT);
    $st->execute();

    $filas = $st->fetchAll();

    return count($filas) >= 3 ? $filas : [];
}

/**
 * Que tema sube y cual baja: los ultimos 90 dias frente a los 90 anteriores.
 *
 * Trimestre natural no, a proposito: comparar el trimestre en curso -a medio
 * llenar- contra uno cerrado siempre da una caida falsa, porque el que
 * empieza tiene menos dias para acumular bits que el que ya termino. Dos
 * ventanas iguales de 90 dias, las dos completas, es la unica comparacion
 * que no miente por el calendario. Es la misma logica que ya usa
 * publicar_mas_leidos() con sus siete dias: una ventana que rueda, no una
 * casilla del calendario.
 *
 * Con menos de cuatro bits entre los dos periodos, el porcentaje es ruido: un
 * tema que pasa de un bit a tres "sube un 200 %" y no ha pasado nada digno de
 * ese numero. Esos temas no aparecen, igual que "lo mas leido" no aparece por
 * debajo de tres bits.
 *
 * No dice si subir es bueno o bajar es malo -eso es un juicio, y el modo
 * automático no inventa juicios-, solo cuenta. Ordenado por el movimiento
 * mas grande primero, suba o baje, porque eso es lo que hace que esta pagina
 * merezca una segunda mirada.
 *
 * @return array Filas con 'slug', 'nombre', 'actual', 'anterior', 'delta',
 *               'porcentaje' (null si el periodo anterior estaba a cero) y
 *               'nuevo' (bool: no habia nada de este tema hace un trimestre).
 */
function publicar_tendencias(int $dias_periodo = 90): array
{
    $hoy            = gmdate('Y-m-d');
    $actual_desde   = gmdate('Y-m-d', strtotime($hoy . " -$dias_periodo days"));
    $anterior_desde = gmdate('Y-m-d', strtotime($hoy . ' -' . ($dias_periodo * 2) . ' days'));

    $sql = "SELECT categoria,
                   SUM(dia >= ?) AS actual,
                   SUM(dia >= ? AND dia < ?) AS anterior
              FROM bits
             WHERE estado = 'publicado' AND dia >= ?
             GROUP BY categoria";

    $st = bd()->prepare($sql);
    $st->execute([$actual_desde, $anterior_desde, $actual_desde, $anterior_desde]);

    $catalogo = bits_categorias();
    $cuenta   = [];

    foreach ($st->fetchAll() as $fila) {
        // Los bits viejos llevan el nombre viejo de su categoria: se suman al
        // que corresponde, como en publicar_temas().
        $slug = bits_categoria_canonica((string) $fila['categoria']);

        if ($slug === '') {
            continue;
        }

        $cuenta[$slug]['actual']   = ($cuenta[$slug]['actual']   ?? 0) + (int) $fila['actual'];
        $cuenta[$slug]['anterior'] = ($cuenta[$slug]['anterior'] ?? 0) + (int) $fila['anterior'];
    }

    $tendencias = [];

    foreach ($cuenta as $slug => $datos) {
        $actual   = $datos['actual'];
        $anterior = $datos['anterior'];

        if ($actual + $anterior < 4) {
            continue;
        }

        $tendencias[] = [
            'slug'       => $slug,
            'nombre'     => $catalogo[$slug] ?? $slug,
            'actual'     => $actual,
            'anterior'   => $anterior,
            'delta'      => $actual - $anterior,
            'porcentaje' => $anterior > 0 ? (int) round((($actual - $anterior) / $anterior) * 100) : null,
            'nuevo'      => $anterior === 0 && $actual > 0,
        ];
    }

    usort(
        $tendencias,
        static fn (array $a, array $b): int => abs($b['delta']) <=> abs($a['delta'])
    );

    return $tendencias;
}

/**
 * Los bits publicados de un tema, del mas reciente al mas antiguo.
 *
 * Lleva el cuerpo -tema.php no lo pinta, pero t/<tema>/feed.xml si- para no
 * repetir esta consulta con una columna mas cuando el feed la necesita.
 */
function publicar_bits_tema(string $tema): array
{
    // Se comparan las dos formas del slug -la de ahora y la vieja- porque los
    // bits guardan la categoria con la que se escribieron.
    $viejas = array_keys(array_filter(
        ['pms-gestion' => 'pms-crs', 'distribucion-revenue' => 'distribucion-otas', 'operaciones-personal' => 'operaciones-iot'],
        static fn (string $nueva): bool => $nueva === $tema
    ));

    $formas = array_merge([$tema], $viejas);
    $marcas = implode(',', array_fill(0, count($formas), '?'));

    $sql = "SELECT b.id, b.titular, b.cuerpo, b.por_que, b.categoria, b.dia,
                   (SELECT f.nombre FROM items i
                      JOIN fuentes f ON f.id = i.fuente_id
                     WHERE i.racimo_id = b.racimo_id AND i.estado <> 'descartado'
                     ORDER BY (i.idioma = 'es') DESC, i.puntuacion DESC, i.id ASC LIMIT 1) AS fuente
              FROM bits b
             WHERE b.categoria IN ($marcas)
               AND b.estado = 'publicado'
             ORDER BY b.dia DESC, b.id DESC";

    $st = bd()->prepare($sql);
    $st->execute($formas);

    return $st->fetchAll();
}

/**
 * Los bits publicados que salieron de un medio, del mas reciente al mas
 * antiguo.
 *
 * "Salieron de un medio" quiere decir que es el medio al que apunta el enlace
 * del bit, no cualquiera de los que contaban la noticia: es el que el lector
 * ha leido.
 */
function publicar_bits_medio(string $nombre): array
{
    $sql = "SELECT b.id, b.titular, b.por_que, b.categoria, b.dia
              FROM bits b
             WHERE b.estado = 'publicado'
               AND (SELECT f.nombre FROM items i
                      JOIN fuentes f ON f.id = i.fuente_id
                     WHERE i.racimo_id = b.racimo_id AND i.estado <> 'descartado'
                     ORDER BY (i.idioma = 'es') DESC, i.puntuacion DESC, i.id ASC LIMIT 1) = ?
             ORDER BY b.dia DESC, b.id DESC";

    $st = bd()->prepare($sql);
    $st->execute([$nombre]);

    return $st->fetchAll();
}

/**
 * Los medios de los que ha salido algo publicado, con cuantos bits cada uno.
 *
 * Se cuenta el medio de la fuente principal de cada bit, que es el que lleva
 * el enlace: contar todos los del racimo daria numeros mas altos y menos
 * ciertos, porque el lector no ha leido a los demas.
 *
 * @return array Filas con 'nombre' y 'bits'.
 */
function publicar_medios(): array
{
    $sql = "SELECT f.nombre, f.url_sitio, COUNT(DISTINCT b.id) AS bits
              FROM bits b
              JOIN items i     ON i.id = (
                    SELECT i2.id FROM items i2
                     WHERE i2.racimo_id = b.racimo_id AND i2.estado <> 'descartado'
                     ORDER BY (i2.idioma = 'es') DESC, i2.puntuacion DESC, i2.id ASC
                     LIMIT 1)
              JOIN fuentes f   ON f.id = i.fuente_id
             WHERE b.estado = 'publicado'
             GROUP BY f.id, f.nombre, f.url_sitio
             ORDER BY bits DESC, f.nombre ASC";

    $medios = bd()->query($sql)->fetchAll();

    // El slug se calcula aqui y no en la plantilla: lo usan la ficha, el
    // enlace y el barrido de carpetas, y tienen que coincidir los tres.
    foreach ($medios as $indice => $medio) {
        $medios[$indice]['slug'] = web_slug_medio((string) $medio['nombre']);
        $medios[$indice]['url']  = (string) ($medio['url_sitio'] ?? '');
    }

    return $medios;
}

/**
 * Cuantas fuentes vigila el radar en total, activas o no, publiquen o no.
 *
 * `/medios.html` solo lista las que ya han aportado un bit, y eso deja una
 * impresion de catalogo pequeño que no es real: la mayoria de fuentes
 * activas simplemente no han tenido todavia una noticia que pase el filtro
 * de puntuacion, no es que no se vigilen. Esta cifra es la que hace visible
 * esa diferencia, con el mismo desglose por ambito que usa `web_ambitos()`.
 *
 * @return array{total: int, por_region: array<string, int>}
 */
function publicar_radar_total(): array
{
    $filas = bd()->query("SELECT region, COUNT(*) AS n FROM fuentes WHERE activa = 1 GROUP BY region")->fetchAll();

    $por_region = ['es' => 0, 'eu' => 0, 'global' => 0];

    foreach ($filas as $fila) {
        $region = (string) $fila['region'];

        if (array_key_exists($region, $por_region)) {
            $por_region[$region] = (int) $fila['n'];
        }
    }

    return [
        'total'      => array_sum($por_region),
        'por_region' => $por_region,
    ];
}

/**
 * Los dias con algo publicado, del mas reciente al mas antiguo.
 *
 * Esto sustituye a la lista de ediciones, y el cambio es el corazon de todo
 * lo demas: una edicion solo existia cuando se cerraba, asi que lo que el
 * radar encontraba hoy no se veia hasta mañana. Un dia existe en cuanto cae
 * en el la primera noticia.
 *
 * @return array Filas con 'dia' y 'bits'.
 */
function publicar_dias(int $limite = 180): array
{
    $sql = "SELECT b.dia, COUNT(*) AS bits
              FROM bits b
             WHERE b.estado = 'publicado' AND b.dia IS NOT NULL
             GROUP BY b.dia
             ORDER BY b.dia DESC
             LIMIT ?";

    $st = bd()->prepare($sql);
    $st->bindValue(1, max(1, $limite), PDO::PARAM_INT);
    $st->execute();

    $dias = [];

    foreach ($st->fetchAll() as $fila) {
        $dias[] = [
            'dia'  => substr((string) $fila['dia'], 0, 10),
            'bits' => (int) $fila['bits'],
        ];
    }

    return $dias;
}

/**
 * Los bits publicados, del ultimo descubierto al primero.
 *
 * Con $dia devuelve los de ese dia; sin el, el rio entero hasta $tope. Es la
 * misma consulta porque es la misma lista mirada por dos ventanas distintas,
 * y tener dos habria sido tener dos sitios donde equivocarse con el orden.
 *
 * El enlace es el del mejor item del racimo: el que mas puntua es el que
 * mejor cuenta la noticia, y es el que se ofrece al lector.
 */
function publicar_bits(?string $dia = null, int $tope = 80): array
{
    // La etiqueta de traduccion solo si la columna existe ya: entre que el
    // despliegue trae el codigo y el cron aplica la migracion hay una ventana,
    // y en esa ventana esta consulta es la que genera la web entera.
    $campo_traducido = bd_columna('bits', 'traducido_de') ? 'b.traducido_de' : "NULL AS traducido_de";

    $sql = "SELECT b.id, b.racimo_id, b.titular, b.cuerpo, b.por_que, b.categoria, b.madurez, b.tipo,
                   b.dia, $campo_traducido,
                   -- El enlace y el medio, con el mismo orden con el que se
                   -- eligio el titular: primero el que lo cuenta en espanol.
                   -- Con otro orden, el bit llevaria titular de un sitio y
                   -- enlace a otro, que es la peor forma de citar una fuente.
                   (SELECT i.url FROM items i
                     WHERE i.racimo_id = b.racimo_id AND i.estado <> 'descartado'
                     ORDER BY (i.idioma = 'es') DESC, i.puntuacion DESC, i.id ASC LIMIT 1) AS url,
                   (SELECT f.nombre FROM items i
                      JOIN fuentes f ON f.id = i.fuente_id
                     WHERE i.racimo_id = b.racimo_id AND i.estado <> 'descartado'
                     ORDER BY (i.idioma = 'es') DESC, i.puntuacion DESC, i.id ASC LIMIT 1) AS fuente,
                   -- El idioma del titular, con el mismo orden con el que
                   -- cron/procesar.php elige el titular representativo: si no
                   -- coincidiera, la etiqueta diria una cosa y el titular otra.
                   (SELECT i.idioma FROM items i
                     WHERE i.racimo_id = b.racimo_id AND i.estado <> 'descartado'
                     ORDER BY (i.idioma = 'es') DESC, i.puntuacion DESC, i.id ASC LIMIT 1) AS idioma,
                   -- Los proveedores del racimo, para enlazar sus fichas.
                   -- slug y nombre en la misma cadena para no hacer una
                   -- consulta por bit.
                   (SELECT GROUP_CONCAT(DISTINCT CONCAT(p.slug, '|', p.nombre) SEPARATOR ';;')
                      FROM items i
                      JOIN item_proveedor ip ON ip.item_id = i.id
                      JOIN proveedores p     ON p.id = ip.proveedor_id
                     WHERE i.racimo_id = b.racimo_id AND i.estado <> 'descartado') AS proveedores
              FROM bits b
             WHERE b.estado = 'publicado'
               AND b.dia IS NOT NULL"
         . ($dia !== null ? " AND b.dia = ?" : '')
         . " ORDER BY b.dia DESC, b.id DESC
             LIMIT ?";

    $st = bd()->prepare($sql);

    if ($dia !== null) {
        $st->bindValue(1, substr($dia, 0, 10));
        $st->bindValue(2, max(1, $tope), PDO::PARAM_INT);
    } else {
        $st->bindValue(1, max(1, $tope), PDO::PARAM_INT);
    }

    $st->execute();

    return $st->fetchAll();
}

/**
 * El rio, partido en dias: [['dia' => '2026-09-17', 'bits' => [...]], ...].
 *
 * La portada es esto. Se pide de una vez y se agrupa aqui, en vez de una
 * consulta por dia, porque la portada son ochenta bits y eran quince
 * consultas para enseñar una pagina.
 */
function publicar_rio(int $tope = 80): array
{
    $dias = [];

    foreach (publicar_bits(null, $tope) as $bit) {
        $dias[substr((string) $bit['dia'], 0, 10)][] = $bit;
    }

    $salida = [];

    foreach ($dias as $dia => $bits) {
        $salida[] = ['dia' => $dia, 'bits' => $bits];
    }

    return $salida;
}

/**
 * Todas las fuentes que cuentan cada una de esas noticias.
 *
 * Una sola consulta para toda la pagina, agrupada despues en PHP: la
 * alternativa era una consulta por bit, y la portada trae ochenta.
 *
 * @return array [racimo_id => filas con fuente, url, publicado, region, idioma]
 */
function publicar_fuentes(array $racimos): array
{
    $racimos = array_values(array_unique(array_filter(array_map('intval', $racimos))));

    if (!$racimos) {
        return [];
    }

    $marcas = implode(',', array_fill(0, count($racimos), '?'));

    $sql = "SELECT i.racimo_id, i.url, i.titulo, i.publicado, i.idioma,
                   f.nombre AS fuente, f.region, f.tipo
              FROM items i
              JOIN fuentes f ON f.id = i.fuente_id
             WHERE i.racimo_id IN ($marcas)
               AND i.estado <> 'descartado'
             ORDER BY i.puntuacion DESC, i.id ASC";

    $st = bd()->prepare($sql);
    $st->execute($racimos);

    $por_racimo = [];

    foreach ($st->fetchAll() as $fila) {
        $por_racimo[(int) $fila['racimo_id']][] = $fila;
    }

    return $por_racimo;
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
    $sql = "SELECT b.id, b.titular, b.por_que, b.cuerpo, b.categoria, b.dia,
                   (SELECT f.nombre FROM items i
                      JOIN fuentes f ON f.id = i.fuente_id
                     WHERE i.racimo_id = b.racimo_id AND i.estado <> 'descartado'
                     ORDER BY (i.idioma = 'es') DESC, i.puntuacion DESC, i.id ASC LIMIT 1) AS fuente,
                   -- El ambito mas concreto del racimo: si alguna fuente es
                   -- espanola, cuenta como espanola; si no, europea.
                   (SELECT f.region FROM items i
                      JOIN fuentes f ON f.id = i.fuente_id
                     WHERE i.racimo_id = b.racimo_id AND i.estado <> 'descartado'
                     ORDER BY (f.region = 'es') DESC, (f.region = 'eu') DESC LIMIT 1) AS ambito,
                   (SELECT i.idioma FROM items i
                     WHERE i.racimo_id = b.racimo_id AND i.estado <> 'descartado'
                     ORDER BY (i.idioma = 'es') DESC, i.puntuacion DESC, i.id ASC LIMIT 1) AS idioma,
                   (SELECT GROUP_CONCAT(DISTINCT CONCAT(p.slug, '|', p.nombre) SEPARATOR ';;')
                      FROM items i
                      JOIN item_proveedor ip ON ip.item_id = i.id
                      JOIN proveedores p     ON p.id = ip.proveedor_id
                     WHERE i.racimo_id = b.racimo_id AND i.estado <> 'descartado') AS proveedores
              FROM bits b
             WHERE b.estado = 'publicado'
             ORDER BY b.dia DESC, b.id DESC";

    $filas = [];

    foreach (bd()->query($sql) as $bit) {
        $filas[] = web_fila_indice($bit);
    }

    // Las etiquetas viajan con el indice para que el buscador no tenga que
    // llevar una copia de los catalogos en JavaScript.
    return [
        'etiquetas' => [
            'c' => bits_categorias(),
            'a' => web_ambitos(),
            'l' => web_idiomas(),
        ],
        'bits' => $filas,
    ];
}

/**
 * Firma de todo lo publicable: cuantos bits hay, cual es el ultimo y cuando
 * se toco algo por ultima vez.
 *
 * Si un bit se corrige despues de publicar, cambia su 'modificado' y con el
 * la firma, asi que la correccion sale sola en la siguiente pasada del cron.
 */
function publicar_firma(array $dias): string
{
    // Sin ediciones tambien hay firma. Devolver cadena vacia aqui hacia que
    // coincidiera con el ajuste vacio de una instalacion recien hecha, y
    // publicar_pendiente() se marchaba sin escribir ni la hoja de estilo: la
    // portada se quedaba con lo que hubiera dejado el instalador, para
    // siempre, porque publico/ no esta en el repositorio y un despliegue no lo
    // toca.
    $st = bd()->query(
        "SELECT COUNT(*) AS bits, COALESCE(MAX(modificado), '') AS ultimo,
                COALESCE(MAX(id), 0) AS ultimo_id
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
        count($dias),
        (string) ($dias[0]['dia'] ?? ''),
        (int) ($bits['bits'] ?? 0),
        (int) ($bits['ultimo_id'] ?? 0),
        (string) ($bits['ultimo'] ?? ''),
    ];

    foreach ($dias as $dia) {
        $partes[] = $dia['dia'] . ':' . $dia['bits'];
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
        "publicar: %s, %d dias, %d ficheros escritos\n",
        $resumen['estado'],
        $resumen['dias'] ?? 0,
        $resumen['ficheros']
    );
}
