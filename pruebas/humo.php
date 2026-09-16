<?php
/**
 * Prueba de humo: esquema, semillas y procesado contra una base de verdad.
 *
 * Es la unica prueba del proyecto que toca la base de datos de punta a punta.
 * Las demas comprueban funciones; esta comprueba que las piezas encajan:
 * importa el esquema y las tres semillas con el mismo codigo que usa el
 * instalador, mete unos cuantos items a mano y ejecuta procesar_lote() para
 * ver si agrupa, detecta proveedores y puntua.
 *
 * DESTRUCTIVA. Empieza por sql/esquema.sql, que abre con DROP TABLE, asi que
 * borra todo lo que haya en la base con la que se ejecute. Por eso no se
 * ejecuta jamas por las buenas:
 *
 *   1. Exige la variable de entorno BITB_HUMO=1.
 *   2. Exige que las credenciales lleguen tambien por entorno: nunca usa la
 *      configuracion del servidor, que es la que apunta a los datos buenos.
 *   3. Se niega a arrancar si ya existe config/config.php.
 *
 * En la practica esto solo corre en GitHub Actions, contra un MariaDB de
 * usar y tirar. Ver .github/workflows/pruebas.yml.
 *
 *   BITB_HUMO=1 BITB_BD_NOMBRE=bitb_prueba BITB_BD_USUARIO=root \
 *   BITB_BD_CLAVE=raiz php pruebas/humo.php
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/instalacion.php';

if (getenv('BITB_HUMO') !== '1') {
    echo "Prueba de humo omitida: falta BITB_HUMO=1.\n";
    echo "Es destructiva y solo debe correr contra una base de usar y tirar.\n";
    exit(0);
}

if (inst_instalado()) {
    fwrite(STDERR, "Existe config/config.php: esto parece una instalacion real y no se toca.\n");
    exit(1);
}

$bd = [
    'host'    => getenv('BITB_BD_HOST') ?: '127.0.0.1',
    'nombre'  => getenv('BITB_BD_NOMBRE') ?: '',
    'usuario' => getenv('BITB_BD_USUARIO') ?: '',
    'clave'   => (string) getenv('BITB_BD_CLAVE'),
    'puerto'  => (int) (getenv('BITB_BD_PUERTO') ?: 3306),
];

if ($bd['nombre'] === '' || $bd['usuario'] === '') {
    fwrite(STDERR, "Faltan BITB_BD_NOMBRE o BITB_BD_USUARIO.\n");
    exit(1);
}

// La configuracion que se escribe es de usar y tirar, pero mientras exista es
// un fichero con credenciales en el arbol del proyecto. Se borra pase lo que
// pase, tambien si una comprobacion falla y resumen_pruebas() corta la
// ejecucion.
register_shutdown_function(static function (): void {
    @unlink(inst_fichero_config());
});

// -----------------------------------------------------------------------------
// Montar la base con el mismo codigo que usa el instalador
// -----------------------------------------------------------------------------

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $bd['host'], $bd['puerto'], $bd['nombre']),
    $bd['usuario'],
    $bd['clave'],
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
);

$raiz = dirname(__DIR__);

$sentencias  = inst_ejecutar_sql($pdo, $raiz . '/sql/esquema.sql');
$sentencias += inst_ejecutar_sql($pdo, $raiz . '/sql/semilla_fuentes.sql');
$sentencias += inst_ejecutar_sql($pdo, $raiz . '/sql/semilla_diccionario.sql');
$sentencias += inst_ejecutar_sql($pdo, $raiz . '/sql/semilla_proveedores.sql');

comprobar('el esquema y las semillas se importan enteros', true, $sentencias > 3);

$config = inst_plantilla_config($bd + [
    'dominio'      => 'ejemplo.test',
    'secreto_hmac' => str_repeat('a', 64),
    'token_api'    => str_repeat('b', 64),
    'sal_hash'     => str_repeat('c', 64),
]);

file_put_contents(inst_fichero_config(), $config);

// A partir de aqui ya se puede usar lib/db.php, que lee esa configuracion.
require_once $raiz . '/cron/procesar.php';

comprobar(
    'las semillas dejan el catalogo de fuentes cargado',
    true,
    (int) bd()->query('SELECT COUNT(*) FROM fuentes')->fetchColumn() > 50
);

comprobar(
    'las semillas dejan los proveedores cargados',
    true,
    (int) bd()->query('SELECT COUNT(*) FROM proveedor_alias')->fetchColumn() > 40
);

// -----------------------------------------------------------------------------
// Items de prueba
//
// Dos titulares cuentan la misma caida y vienen de fuentes distintas: tienen
// que acabar en el mismo racimo. El tercero no tiene nada que ver y abre el
// suyo. Los tres mencionan proveedores del catalogo.
// -----------------------------------------------------------------------------

function humo_fuente(string $nombre, string $tipo, int $peso, string $idioma): int
{
    $sql = 'INSERT INTO fuentes (nombre, url_feed, url_sitio, tipo, idioma, region, peso, activa)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)';

    bd()->prepare($sql)->execute([
        $nombre,
        'https://humo.test/' . md5($nombre) . '/feed.xml',
        'https://humo.test/',
        $tipo,
        $idioma,
        'global',
        $peso,
    ]);

    return (int) bd()->lastInsertId();
}

function humo_item(int $fuente_id, string $titulo, string $resumen, string $idioma): int
{
    $url = 'https://humo.test/' . md5($titulo);

    $sql = 'INSERT INTO items
              (fuente_id, guid, url, url_canonica, hash_url, titulo, titulo_norm,
               resumen_origen, autor, publicado, capturado, idioma, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP(), ?, ?)';

    bd()->prepare($sql)->execute([
        $fuente_id,
        $url,
        $url,
        $url,
        sha1($url),
        $titulo,
        texto_titulo_norm($titulo),
        $resumen,
        '',
        $idioma,
        'nuevo',
    ]);

    return (int) bd()->lastInsertId();
}

$fuente_a = humo_fuente('Humo Uno', 'prensa', 5, 'es');
$fuente_b = humo_fuente('Humo Dos', 'prensa', 4, 'es');
$fuente_c = humo_fuente('Humo Estado', 'estado', 6, 'en');

$titulo_a = 'Oracle OPERA Cloud sufre una caida global de cuatro horas';
$titulo_b = 'Caida global de Oracle OPERA Cloud durante cuatro horas';
$titulo_c = 'Mews compra un motor de reservas europeo';

$item_a = humo_item($fuente_a, $titulo_a, 'El PMS quedo fuera de servicio en toda Europa.', 'es');
$item_b = humo_item($fuente_b, $titulo_b, 'La incidencia afecto a los hoteles de media Europa.', 'es');
$item_c = humo_item($fuente_c, $titulo_c, 'La compra refuerza su posicion en el mercado europeo.', 'en');

// -----------------------------------------------------------------------------
// Procesado
// -----------------------------------------------------------------------------

$resumen = procesar_lote(microtime(true) + 30);

comprobar('el lote procesa los tres items', 3, $resumen['items']);
comprobar('no hay errores en el lote', 0, $resumen['errores']);
comprobar('la cola queda vacia', 0, $resumen['pendientes']);

function humo_item_leer(int $id): array
{
    $st = bd()->prepare('SELECT racimo_id, puntuacion, estado FROM items WHERE id = ?');
    $st->execute([$id]);

    return $st->fetch() ?: [];
}

$a = humo_item_leer($item_a);
$b = humo_item_leer($item_b);
$c = humo_item_leer($item_c);

comprobar('los items quedan agrupados', 'agrupado', $a['estado']);

comprobar(
    'las dos versiones de la misma caida caen en el mismo racimo',
    true,
    (int) $a['racimo_id'] === (int) $b['racimo_id']
);

comprobar(
    'una noticia distinta abre racimo propio',
    true,
    (int) $c['racimo_id'] !== (int) $a['racimo_id']
);

comprobar('se abren dos racimos y no tres', 2, (int) bd()->query('SELECT COUNT(*) FROM racimos')->fetchColumn());

$st = bd()->prepare('SELECT n_items, puntuacion, titulo_representativo FROM racimos WHERE id = ?');
$st->execute([(int) $a['racimo_id']]);
$racimo = $st->fetch();

comprobar('el racimo compartido cuenta sus dos items', 2, (int) $racimo['n_items']);

comprobar(
    'el titular representativo sale de los items del racimo',
    true,
    in_array($racimo['titulo_representativo'], [$titulo_a, $titulo_b], true)
);

// El racimo vale lo que su mejor item mas seis puntos por cada fuente
// distinta: dos fuentes, doce puntos.
comprobar(
    'el racimo suma la corroboracion de sus dos fuentes',
    max((int) $a['puntuacion'], (int) $b['puntuacion']) + 12,
    (int) $racimo['puntuacion']
);

comprobar(
    'los items puntuan por encima de cero',
    true,
    (int) $a['puntuacion'] > 0 && (int) $c['puntuacion'] > 0
);

// Oracle OPERA en los dos primeros, Mews en el tercero.
comprobar(
    'se detectan los proveedores del catalogo',
    3,
    (int) bd()->query('SELECT COUNT(*) FROM item_proveedor')->fetchColumn()
);

comprobar(
    'el indice invertido queda poblado',
    true,
    (int) bd()->query('SELECT COUNT(*) FROM item_token')->fetchColumn() > 5
);

// La cola es el propio estado del item: si el procesado dejara alguno sin
// marcar, la siguiente pasada volveria a cogerlo y lo duplicaria todo.
comprobar(
    'una segunda pasada no vuelve a tocar nada',
    0,
    procesar_lote(microtime(true) + 10)['items']
);

// -----------------------------------------------------------------------------
// Curacion y publicacion
//
// El resto del camino: un racimo se convierte en bit, el bit entra en la
// edicion, la edicion se cierra y el generador escribe la web. Es lo unico
// que comprueba que las cuatro fases encajan entre si.
// -----------------------------------------------------------------------------

require_once $raiz . '/panel/datos.php';
require_once $raiz . '/cron/publicar.php';

$bit_id = datos_crear_bit(
    datos_racimo((int) $a['racimo_id']),
    datos_items_racimo((int) $a['racimo_id'])
);

comprobar('el bit nace del racimo en borrador', 'borrador', datos_bit($bit_id)['estado']);

$contenido = [
    'titular'   => 'Oracle OPERA Cloud se cae durante cuatro horas en toda Europa',
    'cuerpo'    => trim(str_repeat('palabra ', 40)),
    'por_que'   => 'Si tu PMS es OPERA Cloud, esto explica por que el jueves no pudiste hacer check-in.',
    'categoria' => 'pms-gestion',
    'madurez'   => 'anuncio',
    'tipo'      => 'incidente',
    'estado'    => 'aprobado',
];

comprobar('el bit cumple el formato', [], bits_validar($contenido));

datos_guardar_bit($bit_id, $contenido);

$edicion = datos_edicion_abierta();
datos_asignar_bit($bit_id, (int) $edicion['id']);

comprobar('la edicion abierta recoge el bit', 1, count(datos_bits_edicion((int) $edicion['id'])));

$revision = bits_revisar_edicion(datos_bits_edicion((int) $edicion['id']), datos_conf_edicion());
comprobar('la edicion se puede cerrar', [], $revision['errores']);

// Antes de cerrar nada no hay ediciones publicables. Aun asi el generador
// tiene que dejar la portada en pie: saltarselo es lo que dejo el sitio real
// con la pagina del instalador durante semanas, porque publico/ no esta en el
// repositorio y ningun despliegue lo toca.
$publico = $raiz . '/publico';
$previa  = publicar_pendiente(microtime(true) + 20);

comprobar('sin ediciones cerradas, el generador publica igualmente', 'publicado', $previa['estado']);
comprobar('escribe la portada provisional', true, is_file($publico . '/index.html'));
comprobar('y la hoja de estilo', true, is_file($publico . '/estilo.css'));

comprobar(
    'la portada provisional dice que la edicion esta en camino',
    true,
    str_contains((string) file_get_contents($publico . '/index.html'), 'primera edición está en camino')
);

datos_cerrar_edicion((int) $edicion['id']);

comprobar(
    'al cerrar, el racimo sale de la cola',
    'publicado',
    datos_racimo((int) $a['racimo_id'])['estado']
);

comprobar('y la cola de candidatos se queda con el otro racimo', 1, count(datos_cola()));

$resumen_web = publicar_pendiente(microtime(true) + 30);

comprobar('el generador publica una edicion', 1, $resumen_web['ediciones']);

$slug = (string) $edicion['slug'];

comprobar('escribe la portada', true, is_file($publico . '/index.html'));
comprobar('escribe la edicion en su carpeta', true, is_file($publico . '/' . web_ruta_edicion($slug)));
comprobar('escribe el archivo', true, is_file($publico . '/archivo.html'));
comprobar('escribe la pagina de que es esto', true, is_file($publico . '/sobre.html'));
comprobar('escribe el indice de proveedores', true, is_file($publico . '/proveedores.html'));
comprobar('escribe el buscador', true, is_file($publico . '/buscar.html'));
comprobar('escribe el guion del buscador', true, is_file($publico . '/buscar.js'));
comprobar('escribe el indice de busqueda', true, is_file($publico . '/indice.json'));

$indice = json_decode((string) file_get_contents($publico . '/indice.json'), true);
$bits_indice = $indice['bits'] ?? [];

comprobar('el indice lleva el bit publicado', 1, count($bits_indice));

comprobar(
    'y su texto buscable esta normalizado',
    true,
    isset($bits_indice[0]['b']) && str_contains((string) $bits_indice[0]['b'], 'oracle opera cloud se cae')
);

// Las facetas por las que se filtra tienen que llegar con cada bit.
comprobar('el bit del indice trae su fuente', 'Humo Uno', $bits_indice[0]['fu'] ?? '');
comprobar('y su ambito', 'global', $bits_indice[0]['a'] ?? '');
comprobar('y su idioma', 'es', $bits_indice[0]['l'] ?? '');

// Y las etiquetas, para que el buscador no lleve una copia de los catalogos.
comprobar(
    'el indice trae las etiquetas de las facetas',
    true,
    isset($indice['etiquetas']['c']['pms-gestion'], $indice['etiquetas']['a']['es'], $indice['etiquetas']['l']['en'])
);

// El bit habla de Oracle OPERA, asi que su ficha tiene que existir y llevarlo.
comprobar(
    'escribe la ficha del proveedor mencionado',
    true,
    is_file($publico . '/' . web_ruta_proveedor('oracle-hospitality'))
);

comprobar(
    'la ficha del proveedor lleva el bit',
    true,
    str_contains(
        (string) file_get_contents($publico . '/' . web_ruta_proveedor('oracle-hospitality')),
        'Oracle OPERA Cloud se cae durante cuatro horas'
    )
);

comprobar('escribe el feed', true, is_file($publico . '/feed.xml'));
comprobar('escribe la hoja de estilo', true, is_file($publico . '/estilo.css'));

// Sin version en la URL, quien ya haya visitado el sitio se queda con la hoja
// vieja hasta treinta dias: el .htaccess le pone un mes de cache y el fichero
// se reescribe siempre en el mismo sitio.
comprobar(
    'la portada enlaza la hoja con su version',
    1,
    preg_match('~/estilo\.css\?v=[0-9a-f]{8}~', (string) file_get_contents($publico . '/index.html'))
);
comprobar('escribe el robots.txt', true, is_file($publico . '/robots.txt'));

$portada = (string) file_get_contents($publico . '/index.html');

comprobar(
    'la portada lleva el titular del bit',
    true,
    str_contains($portada, 'Oracle OPERA Cloud se cae durante cuatro horas')
);

comprobar(
    'y el por que importa',
    true,
    str_contains($portada, 'Por qu&eacute; importa') || str_contains($portada, 'Por qué importa')
);

// El cuerpo llega de un textarea: si alguna vez saliera sin escapar, esto lo
// caza antes que un lector.
comprobar(
    'la portada enlaza la ficha del proveedor desde el bit',
    true,
    str_contains($portada, '/p/oracle-hospitality/')
);

comprobar(
    'la portada no cuela etiquetas que vengan del panel',
    false,
    str_contains($portada, '<script')
);

$feed = (string) file_get_contents($publico . '/feed.xml');

comprobar('el feed declara el canal', true, str_contains($feed, '<rss version="2.0"'));
comprobar('y enlaza la edicion', true, str_contains($feed, web_url_edicion('https://ejemplo.test', $slug)));

// La firma evita que el cron reescriba seis ficheros cada hora para nada.
comprobar(
    'una segunda pasada no reescribe nada',
    'sin cambios',
    publicar_pendiente(microtime(true) + 10)['estado']
);

resumen_pruebas('Prueba de humo: esquema, semillas, procesado, curacion y web');
