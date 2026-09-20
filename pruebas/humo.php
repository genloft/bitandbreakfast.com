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
// Las migraciones, encima del esquema recien montado
// -----------------------------------------------------------------------------
//
// El esquema ya trae todo lo que anaden, asi que aqui no cambian nada: lo que
// se comprueba es que el SQL es valido y que se puede pasar dos veces sin
// romperse. Hasta ahora una migracion con una errata no se descubria hasta que
// el cron la ejecutaba en produccion, y al fallar una se paran las siguientes.

require_once $raiz . '/lib/migrar.php';

$migradas = migrar_pendientes($raiz);

comprobar('las migraciones se aplican sin error', '', $migradas['error']);
comprobar('y se aplican todas', true, count($migradas['aplicadas']) > 0);

// Segunda pasada desde cero: el ajuste dice que ya estan, asi que no deberia
// tocar ninguna. Se fuerza a que las repita para ver que aguantan.
ajuste_guardar('migracion_ultima', '');
$otra_vez = migrar_pendientes($raiz);

comprobar('y se pueden repetir sin romperse', '', $otra_vez['error']);

// El diccionario es lo que decide si una noticia "habla del tema", y estaba
// escrito mirando a la prensa internacional: una noticia espanola pertinente
// podia no llegar al minimo. Se comprueba con titulares de verdad, en espanol,
// porque es el idioma en el que este sitio publica siempre.

require_once $raiz . '/lib/puntuar.php';

$terminos = bd()->query('SELECT termino, peso FROM diccionario WHERE activo = 1')->fetchAll();

comprobar('el diccionario pasa de ciento ochenta terminos', true, count($terminos) > 180);

foreach ([
    'Una brecha de datos deja al descubierto el PMS de una cadena hotelera',
    'Los hoteles espanoles apuestan por la venta directa frente a las OTAs',
    'El nuevo reconocimiento facial acelera el check-in digital en recepcion',
    'La sostenibilidad y el consumo energetico entran en el cuadro de mando del hotel',
] as $titular) {
    comprobar(
        'suma senal tematica: ' . mb_substr($titular, 0, 38),
        true,
        puntuar_diccionario($titular, '', $terminos, 100)['puntos'] >= 8
    );
}

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
$item_c = humo_item(
    $fuente_c,
    $titulo_c,
    'La compra refuerza su posicion en el mercado europeo y le da acceso directo al '
    . 'canal de reserva de mas de dos mil hoteles independientes, segun la nota que ha '
    . 'publicado la compania esta manana sin detallar el importe de la operacion.',
    'en'
);

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
    'categoria' => 'pms-crs',
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

// Antes de cerrar nada no hay ningun bit publicado. Aun asi el generador
// tiene que dejar la portada en pie: saltarselo es lo que dejo el sitio real
// con la pagina del instalador durante semanas, porque publico/ no esta en el
// repositorio y ningun despliegue lo toca.
$publico = $raiz . '/publico';
$previa  = publicar_pendiente(microtime(true) + 20);

comprobar('sin nada publicado, el generador publica igualmente', 'publicado', $previa['estado']);
comprobar('escribe la portada provisional', true, is_file($publico . '/index.html'));
comprobar('y la hoja de estilo', true, is_file($publico . '/estilo.css'));

comprobar(
    'la portada provisional dice que lo primero esta en camino',
    true,
    str_contains((string) file_get_contents($publico . '/index.html'), 'están en camino')
);

datos_cerrar_edicion((int) $edicion['id']);

comprobar(
    'al cerrar, el racimo sale de la cola',
    'publicado',
    datos_racimo((int) $a['racimo_id'])['estado']
);

comprobar('y la cola de candidatos se queda con el otro racimo', 1, count(datos_cola()));

// El mismo camino que recorre api/candidatos.php: la cola de verdad, con
// items de verdad, dando la forma que se manda por la API.
$cola_candidatos = datos_cola();
$candidato_api   = datos_formatear_candidato(
    $cola_candidatos[0],
    datos_items_racimo((int) $cola_candidatos[0]['id'])
);

comprobar('el candidato de la API lleva items de verdad', true, count($candidato_api['items']) > 0);
comprobar(
    'y el titulo del item viene de la base, no inventado',
    $titulo_c,
    $candidato_api['items'][0]['titulo']
);

$resumen_web = publicar_pendiente(microtime(true) + 30);

comprobar('el generador publica un dia', 1, $resumen_web['dias']);

$dia = substr((string) $edicion['fecha_prevista'], 0, 10);

comprobar('escribe la portada', true, is_file($publico . '/index.html'));
comprobar('escribe el dia en su carpeta', true, is_file($publico . '/' . web_ruta_dia($dia)));

comprobar(
    'y lleva sus migas de pan en schema.org',
    true,
    str_contains(
        (string) file_get_contents($publico . '/' . web_ruta_dia($dia)),
        'itemtype="https://schema.org/BreadcrumbList"'
    )
);

comprobar('escribe el archivo', true, is_file($publico . '/archivo.html'));
comprobar('escribe la pagina de que es esto', true, is_file($publico . '/sobre.html'));

$sobre = (string) file_get_contents($publico . '/sobre.html');

comprobar('y enlaza a Cifras', true, str_contains($sobre, '/estadisticas.html'));
comprobar('y a Cumplimiento', true, str_contains($sobre, '/cumplimiento.html'));
comprobar('y a Tendencias', true, str_contains($sobre, '/tendencias.html'));
comprobar('y a Glosario', true, str_contains($sobre, '/glosario.html'));
comprobar('y a Medios', true, str_contains($sobre, '/medios.html'));
comprobar('escribe el cuadro de cifras', true, is_file($publico . '/estadisticas.html'));
comprobar(
    'y dice hasta cuando vale esa revision',
    true,
    str_contains((string) file_get_contents($publico . '/estadisticas.html'), 'con revisión antes del')
);
comprobar('escribe el calendario de cumplimiento', true, is_file($publico . '/cumplimiento.html'));

$cumplimiento_html = (string) file_get_contents($publico . '/cumplimiento.html');

comprobar(
    'y tambien dice hasta cuando vale su revision',
    true,
    str_contains($cumplimiento_html, 'con revisión antes del')
);
comprobar(
    'con al menos una norma en cuenta atras',
    true,
    str_contains($cumplimiento_html, 'Cuenta atrás')
);
comprobar('escribe el sitemap', true, is_file($publico . '/sitemap.xml'));
comprobar(
    'y el sitemap lista cumplimiento.html',
    true,
    str_contains((string) file_get_contents($publico . '/sitemap.xml'), '/cumplimiento.html')
);

// cifras.json: las mismas cifras de la pagina, en JSON valido.
comprobar('escribe cifras.json', true, is_file($publico . '/cifras.json'));

$cifras_json = json_decode((string) file_get_contents($publico . '/cifras.json'), true);

comprobar('cifras.json es JSON valido', true, is_array($cifras_json));
comprobar('con la misma fecha de revision que la pagina', cifras_revisado(), $cifras_json['revisado'] ?? null);
comprobar('y al menos diez grupos', true, count($cifras_json['grupos'] ?? []) >= 10);
comprobar(
    'incluyendo el mix de canal directo y OTA',
    true,
    in_array('Mix de canal directo y OTA', array_column($cifras_json['grupos'] ?? [], 'tema'), true)
);

comprobar('escribe el glosario', true, is_file($publico . '/glosario.html'));
comprobar(
    'y enlaza un termino con el tema que le corresponde',
    true,
    str_contains((string) file_get_contents($publico . '/glosario.html'), web_url_tema('https://ejemplo.test', 'pms-crs'))
);
comprobar('escribe el indice de temas', true, is_file($publico . '/temas.html'));
comprobar('y el de medios', true, is_file($publico . '/medios.html'));

// El aviso legal: sin identidad configurada en la prueba, tiene que caer en
// el aviso de contacto por correo en vez de inventar un titular.
comprobar('escribe el aviso legal', true, is_file($publico . '/legal.html'));

$legal = (string) file_get_contents($publico . '/legal.html');

comprobar('sin identidad configurada, lo dice en vez de inventarla', true, str_contains($legal, 'todavía no tiene rellenos'));
comprobar('y aun asi deja un contacto', true, str_contains($legal, 'mailto:'));
comprobar('explica la base legal de citar fragmentos', true, str_contains($legal, 'artículo 32.2'));
comprobar('declara que la web publica no usa cookies de rastreo', true, str_contains($legal, 'instala ninguna cookie'));
comprobar('el pie enlaza al aviso legal', true, str_contains($sobre, '/legal.html'));

// Las dos paginas de indice eran las unicas de todo el sitio sin enlace de
// autodescubrimiento al RSS general: un lector de feeds que las visitara no
// tenia forma de encontrarlo desde ahi.
comprobar(
    'temas.html enlaza el feed general para autodescubrimiento',
    true,
    str_contains((string) file_get_contents($publico . '/temas.html'), 'rel="alternate"')
);
comprobar(
    'y medios.html tambien',
    true,
    str_contains((string) file_get_contents($publico . '/medios.html'), 'rel="alternate"')
);
comprobar('escribe el buscador', true, is_file($publico . '/buscar.html'));
comprobar('escribe el guion del buscador', true, is_file($publico . '/buscar.js'));
comprobar('escribe el indice de busqueda', true, is_file($publico . '/indice.json'));

// -----------------------------------------------------------------------------
// Votos: "te ha servido esta noticia?", desde el correo
//
// Lo que no se puede probar sin base de datos: que el mismo enlace pulsado
// dos veces cuente un solo voto, y que dos destinatarios distintos puedan
// votar el mismo bit sin chocar entre ellos.
// -----------------------------------------------------------------------------

require_once $raiz . '/lib/votos.php';

$votos_secreto = (string) config('secretos.secreto_hmac');
$voto_firma    = votos_firma($bit_id, 501, $votos_secreto);

comprobar(
    'un voto bien firmado se registra',
    'ok',
    votos_registrar($bit_id, 501, 1, $voto_firma, $votos_secreto, 'hash-de-prueba')
);

comprobar(
    'pulsar el mismo enlace otra vez no cuenta un segundo voto',
    'repetido',
    votos_registrar($bit_id, 501, 1, $voto_firma, $votos_secreto, 'hash-de-prueba')
);

comprobar(
    'otro destinatario puede votar el mismo bit sin chocar con el primero',
    'ok',
    votos_registrar($bit_id, 502, -1, votos_firma($bit_id, 502, $votos_secreto), $votos_secreto, 'hash-de-prueba')
);

comprobar(
    'una firma que no cuadra no llega a escribirse',
    'invalido',
    votos_registrar($bit_id, 503, 1, 'firma-inventada', $votos_secreto, 'hash-de-prueba')
);

comprobar(
    'quedan exactamente los dos votos validos, no el invalido',
    2,
    (int) bd()->query('SELECT COUNT(*) FROM votos WHERE bit_id = ' . (int) $bit_id)->fetchColumn()
);

// Las mismas cuentas que ensena /salud.php, con el unico bit que se vota en
// esta prueba: si esa pagina cambiara la condicion (por ejemplo, de "valor >
// 0" a "valor = 1"), esto lo notaria sin tener que leer salud.php a mano.
comprobar('y /salud.php contaria el total igual', 2, (int) bd()->query('SELECT COUNT(*) FROM votos')->fetchColumn());
comprobar('y los positivos', 1, (int) bd()->query('SELECT COUNT(*) FROM votos WHERE valor > 0')->fetchColumn());
comprobar('y los negativos', 1, (int) bd()->query('SELECT COUNT(*) FROM votos WHERE valor < 0')->fetchColumn());

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
comprobar('y la lista de proveedores que menciona, para poder filtrar por uno', ['Oracle Hospitality'], $bits_indice[0]['pv'] ?? null);

// Y las etiquetas, para que el buscador no lleve una copia de los catalogos.
comprobar(
    'el indice trae las etiquetas de las facetas',
    true,
    isset($indice['etiquetas']['c']['pms-crs'], $indice['etiquetas']['a']['es'], $indice['etiquetas']['l']['en'])
);

// El bit es de PMS y CRS, asi que la ficha de ese tema tiene que existir y
// llevarlo dentro.
comprobar(
    'escribe la ficha del tema',
    true,
    is_file($publico . '/' . web_ruta_tema('pms-crs'))
);

comprobar(
    'y la ficha lleva el bit',
    true,
    str_contains(
        (string) file_get_contents($publico . '/' . web_ruta_tema('pms-crs')),
        'Oracle OPERA Cloud se cae durante cuatro horas'
    )
);

comprobar(
    'y lleva sus migas de pan en schema.org',
    true,
    str_contains(
        (string) file_get_contents($publico . '/' . web_ruta_tema('pms-crs')),
        'itemtype="https://schema.org/BreadcrumbList"'
    )
);

comprobar(
    'y escribe el feed de ese tema',
    true,
    is_file($publico . '/t/pms-crs/feed.xml')
);

comprobar(
    'con el bit dentro',
    true,
    str_contains(
        (string) file_get_contents($publico . '/t/pms-crs/feed.xml'),
        'Oracle OPERA Cloud se cae durante cuatro horas'
    )
);

comprobar(
    'y la ficha del tema enlaza a su propio feed, no al general',
    true,
    str_contains(
        (string) file_get_contents($publico . '/' . web_ruta_tema('pms-crs')),
        '/t/pms-crs/feed.xml'
    )
);

// La ficha de tema enriquecida: descripcion evergreen, siglas del glosario
// que enlazan de vuelta -CRS y PMS son justo los dos terminos de pms-crs-,
// y ningun bloque vacio cuando el tema no tiene ni normas ni cifras propias.
$ficha_pms_crs = (string) file_get_contents($publico . '/' . web_ruta_tema('pms-crs'));

comprobar('la ficha trae la descripcion evergreen del tema', true, str_contains($ficha_pms_crs, 'channel manager'));
comprobar('y enlaza de vuelta al glosario', true, str_contains($ficha_pms_crs, '/glosario.html#crs'));
comprobar('con las dos siglas que le tocan', true, str_contains($ficha_pms_crs, '/glosario.html#pms'));
comprobar(
    'pero no pinta un bloque de cumplimiento vacio -pms-crs no tiene ninguna norma asociada-',
    false,
    str_contains($ficha_pms_crs, '/cumplimiento.html#')
);
comprobar(
    'ni uno de cifras vacio -pms-crs tampoco tiene ningun grupo asociado-',
    false,
    str_contains($ficha_pms_crs, '/estadisticas.html#')
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

comprobar('escribe el favicon', true, is_file($publico . '/favicon.svg'));
comprobar(
    'y la portada lo enlaza',
    true,
    str_contains((string) file_get_contents($publico . '/index.html'), '/favicon.svg')
);

// La tarjeta generica: og:image tiene que ser PNG de verdad, no SVG como el
// favicon -ninguna red social que enseña vista previa rasteriza SVG-.
comprobar('escribe la tarjeta generica', true, is_file($publico . '/og-generica.png'));
comprobar(
    'y es un PNG de verdad, no el mismo SVG que el favicon',
    'image/png',
    getimagesizefromstring((string) file_get_contents($publico . '/og-generica.png'))['mime'] ?? null
);
comprobar(
    'la portada enlaza la tarjeta generica en og:image',
    true,
    str_contains((string) file_get_contents($publico . '/index.html'), '/og-generica.png')
);

// La tarjeta de un dia concreto, con el titular del bit que lleva.
$ruta_dia_og = $publico . '/' . dirname(web_ruta_dia($dia)) . '/og.png';
comprobar('escribe la tarjeta del dia', true, is_file($ruta_dia_og));
comprobar(
    'y el dia la enlaza en og:image',
    true,
    str_contains(
        (string) file_get_contents($publico . '/' . web_ruta_dia($dia)),
        '/d/' . $dia . '/og.png'
    )
);

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
// Los proveedores ya no tienen ficha: se nombran y se enlazan al buscador.
comprobar(
    'la portada nombra al proveedor y lleva al buscador',
    true,
    str_contains($portada, 'buscar.html?q=Oracle')
);

// Y el medio del que sale el bit si tiene ficha propia.
comprobar(
    'la portada enlaza la ficha del medio',
    true,
    str_contains($portada, '/m/humo-uno/') || str_contains($portada, '/m/humo-dos/')
);

$medio_ficha = is_file($publico . '/' . web_ruta_medio('humo-uno'))
    ? $publico . '/' . web_ruta_medio('humo-uno')
    : $publico . '/' . web_ruta_medio('humo-dos');

comprobar(
    'y la ficha del medio lleva sus migas de pan en schema.org',
    true,
    str_contains((string) file_get_contents($medio_ficha), 'itemtype="https://schema.org/BreadcrumbList"')
);

comprobar(
    'la portada no cuela etiquetas que vengan del panel',
    false,
    str_contains($portada, '<script')
);

// Las cifras de la cabecera: van en todas las paginas, no solo en la portada,
// porque quien llega por un enlace a una ficha tambien quiere saber si esto
// esta vivo.
comprobar('la cabecera lleva el panel de cifras', true, str_contains($portada, 'class="panel"'));

// Microdatos de sitio: WebSite con su SearchAction hacia /buscar.html, para
// que un buscador pueda ofrecer la caja de busqueda propia en sus resultados
// y para que quien lea la pagina con una IA sepa que sitio es este.
comprobar('la cabecera lleva el WebSite de schema.org', true, str_contains($portada, 'itemtype="https://schema.org/WebSite"'));
comprobar('con su SearchAction hacia el buscador', true, str_contains($portada, 'buscar.html?q={search_term_string}'));

// Cada noticia enlaza a su articulo original, y lo enlaza dos veces: desde el
// titular, que es donde todo el mundo pincha, y al pie con todas las letras.
// Un agregador que no lleva a la fuente no es un agregador.
comprobar(
    'el titular lleva al original',
    true,
    // [^>]* porque el h2 puede llevar mas atributos -itemprop="headline" de
    // los microdatos, por ejemplo- sin que eso cambie lo que aqui importa:
    // que justo detras venga un enlace.
    (bool) preg_match('~<h2 id="titular-\d+"[^>]*>\s*<a href="[^"]+"~', $portada)
);

comprobar(
    'y el pie lo dice, aunque sea en el aria-label del icono',
    true,
    str_contains($portada, 'Leer el original en')
);

comprobar(
    'el enlace al original abre en pestana nueva, sin perder la propia pagina',
    true,
    (bool) preg_match('~<h2 id="titular-\d+"[^>]*>\s*<a href="[^"]+" rel="nofollow noopener" target="_blank"~', $portada)
);

comprobar('cada bit se puede compartir por WhatsApp', true, str_contains($portada, 'https://wa.me/?text='));
comprobar('y por LinkedIn', true, str_contains($portada, 'https://www.linkedin.com/sharing/share-offsite/?url='));

// Ya no hay una cabecera de dia por tramo: el rio es un flujo continuo y
// cada ficha lleva su propia fecha.
comprobar('la portada ya no separa por dias con su propia caja', false, str_contains($portada, 'dia-cabecera'));
comprobar('cada bit lleva su fecha encima', true, str_contains($portada, 'etiqueta-fecha'));

// $fuente_a y $fuente_b cuentan la misma noticia: su bit tiene que salir
// resaltado en negativo.
comprobar('lo que cuentan varios medios sale en negativo', true, str_contains($portada, 'bit-multifuente'));

comprobar('con las tres cuentas', 3, substr_count($portada, 'class="panel-cifra"'));
comprobar('y los dos relojes', 2, substr_count($portada, 'class="panel-reloj"'));

comprobar(
    'y tambien lo lleva una ficha de tema',
    true,
    str_contains((string) file_get_contents($publico . '/' . web_ruta_tema('pms-crs')), 'class="panel"')
);

$feed = (string) file_get_contents($publico . '/feed.xml');

comprobar('el feed declara el canal', true, str_contains($feed, '<rss version="2.0"'));
comprobar('y enlaza el dia', true, str_contains($feed, web_url_dia('https://ejemplo.test', $dia)));

// La firma evita que el cron reescriba seis ficheros cada hora para nada.
comprobar(
    'una segunda pasada no reescribe nada',
    'sin cambios',
    publicar_pendiente(microtime(true) + 10)['estado']
);

// -----------------------------------------------------------------------------
// Publicacion automatica
//
// El otro racimo -el de Mews- sigue en la cola sin que nadie lo haya tocado.
// En modo automatico tiene que convertirse en bit el solo, entrar en la
// edicion abierta y acabar publicado cuando le llegue la fecha.
// -----------------------------------------------------------------------------

require_once $raiz . '/cron/auto.php';

ajuste_guardar('auto_publicar', '1');
ajuste_guardar('auto_umbral', '10');

// El filtro tematico se prueba aparte, con sus propias funciones: aqui lo que
// se comprueba es que la cadena entera funciona.
ajuste_guardar('auto_min_diccionario', '0');

// El traductor de respaldo, apagado para toda la prueba. No es un detalle de
// la prueba: es que traducir significa salir a la red de un tercero, y una
// prueba que sale a internet no comprueba este codigo, comprueba el wifi. Lo
// que se mira aqui son las puertas, y para eso hace falta que no haya
// traductor ninguno.
ajuste_guardar('traductor_respaldo', '0');

// Con la puerta del idioma puesta, este racimo -que solo lo cuenta un medio en
// ingles- no puede entrar. Se comprueba antes de apagarla, porque es la regla
// que decide que se publica en un radar que se lee en espanol.
ajuste_guardar('auto_solo_espanol', '1');

$soloes = auto_publicar_lote(microtime(true) + 20);

comprobar('lo que no cuenta nadie en espanol no se publica', 0, $soloes['bits']);

$st = bd()->prepare('SELECT r.motivo_descarte FROM racimos r JOIN items i ON i.racimo_id = r.id WHERE i.id = ?');
$st->execute([$item_c]);

comprobar(
    'y queda dicho por que',
    true,
    str_contains((string) ($st->fetchColumn() ?: ''), 'espanol')
);

// Y apagar el ajuste no basta: sin traductor configurado, la puerta sigue
// cerrada. El ajuste dice "publica tambien lo extranjero", y sin traductor eso
// no significa publicarlo en espanol, significa llenar la portada de titulares
// en ingles. Se cumple la intencion, no la letra.
ajuste_guardar('auto_solo_espanol', '0');
bd()->prepare("UPDATE items SET estado = 'agrupado' WHERE estado = 'descartado' AND racimo_id IN (SELECT id FROM racimos WHERE estado = 'descartado')")->execute();
bd()->prepare("UPDATE racimos SET estado = 'candidato', motivo_descarte = '' WHERE estado = 'descartado'")->execute();

$sin_traductor = auto_publicar_lote(microtime(true) + 20);

comprobar('sin traductor, apagar la puerta no la abre', 0, $sin_traductor['bits']);

// Y encender el respaldo si la abre: es lo que decide si este sitio publica
// lo que pasa en el mundo o solo lo que pasa en Espana.
ajuste_guardar('traductor_respaldo', '1');
comprobar('con respaldo, hay traductor', true, traducir_configurado());
ajuste_guardar('traductor_respaldo', '0');
comprobar('y sin el, no', false, traducir_configurado());

// El resto de la cadena se prueba con el racimo ya en espanol: lo que se mira
// aqui es que el engranaje gira, no la politica editorial.
bd()->prepare("UPDATE items SET estado = 'agrupado' WHERE estado = 'descartado' AND racimo_id IN (SELECT id FROM racimos WHERE estado = 'descartado')")->execute();
bd()->prepare("UPDATE racimos SET estado = 'candidato', motivo_descarte = '' WHERE estado = 'descartado'")->execute();
bd()->prepare("UPDATE items SET idioma = 'es' WHERE id = ?")->execute([$item_c]);

$auto = auto_publicar_lote(microtime(true) + 20);

comprobar('el modo automatico escribe el bit que quedaba', 1, $auto['bits']);

$abierta = datos_edicion_abierta();
$suyos   = datos_bits_edicion((int) $abierta['id']);

comprobar('y lo mete en el cajon del dia', 1, count($suyos));

// Publicado al escribirse, no al cerrar nada: es la diferencia entre un
// agregador y una revista, y es lo que hace que lo de hoy se vea hoy.
comprobar('publicado, no esperando a nada', 'publicado', $suyos[0]['estado']);
comprobar('con el dia en que se descubrio', gmdate('Y-m-d'), substr((string) $suyos[0]['dia'], 0, 10));
comprobar('y marcado como no escrito por una persona', 'ia', $suyos[0]['redactado_por']);

// Y por eso sale en la web sin que nadie cierre nada.
$resumen_vivo = publicar_pendiente(microtime(true) + 30);

comprobar(
    'y aparece en la portada el mismo dia',
    true,
    str_contains((string) file_get_contents($publico . '/index.html'), 'bit-' . (int) $suyos[0]['id'])
);

// El cuerpo sale del resumen de la fuente o de la lista de medios, nunca de
// la nada.
comprobar(
    'el bit automatico tiene cuerpo',
    true,
    trim((string) $suyos[0]['cuerpo']) !== ''
);

// El cajon es de hoy y hoy no ha terminado: no toca cerrar todavia.
comprobar('mientras dura el dia, el cajon sigue abierto', 0, $auto['cerrada']);

// Con el dia terminado, se cierra solo.
bd()->prepare('UPDATE ediciones SET fecha_prevista = ? WHERE id = ?')
    ->execute([gmdate('Y-m-d', time() - 86400), (int) $abierta['id']]);

$auto = auto_publicar_lote(microtime(true) + 20);

comprobar('pasado el dia, el cajon se cierra solo', (int) $abierta['numero'], $auto['cerrada']);

publicar_pendiente(microtime(true) + 30);

// Las dos noticias son del mismo dia -se han escrito hoy-, asi que lo que se
// comprueba es que el dia de hoy las lleva las dos. Contar paginas no diria
// nada: la de hoy ya estaba escrita de la pasada anterior.
$hoy_html = (string) file_get_contents($publico . '/' . web_ruta_dia(gmdate('Y-m-d')));

comprobar('el dia de hoy lleva las dos noticias', 2, substr_count($hoy_html, 'id="bit-'));

comprobar(
    'y la portada tambien',
    2,
    substr_count((string) file_get_contents($publico . '/index.html'), 'id="bit-')
);

$indice = json_decode((string) file_get_contents($publico . '/indice.json'), true);

comprobar('el indice recoge los dos bits', 2, count($indice['bits'] ?? []));

// Un racimo sin resumen utilizable no se publica: un bit que solo dice quien
// lo cuenta no le ahorra el clic a nadie. Se saca de la cola con el motivo
// escrito para no reevaluarlo en cada pasada.
$fuente_d = humo_fuente('Humo Flojo', 'prensa', 5, 'es');
$item_d   = humo_item($fuente_d, 'Una nota de prensa cualquiera del sector', 'Sin resumen.', 'es');

procesar_lote(microtime(true) + 20);

$flojo = auto_publicar_lote(microtime(true) + 20);

comprobar('el racimo sin cuerpo no se publica', 0, $flojo['bits']);
comprobar('y se descarta, no se reintenta', 1, $flojo['descartados']);

$st = bd()->prepare('SELECT r.estado, r.motivo_descarte FROM racimos r JOIN items i ON i.racimo_id = r.id WHERE i.id = ?');
$st->execute([$item_d]);
$descartado = $st->fetch();

comprobar('queda marcado como descartado', 'descartado', $descartado['estado'] ?? '');

comprobar(
    'con el motivo escrito',
    true,
    str_contains((string) ($descartado['motivo_descarte'] ?? ''), 'automatico')
);

// --- Revision de lo ya publicado --------------------------------------------
//
// Cuando suben los criterios, lo que se publico sin mirar se vuelve a pasar
// por el filtro una sola vez. Es lo que arregla una edicion publicada con
// reglas flojas en lugar de dejarla ahi para siempre.

ajuste_guardar('auto_criterios', '0');
ajuste_guardar('auto_min_diccionario', '500');   // nada puede pasar este filtro

$antes = (int) bd()->query("SELECT COUNT(*) FROM bits WHERE redactado_por = 'ia'")->fetchColumn();

comprobar('hay algun bit automatico publicado', true, $antes > 0);

$revision = auto_publicar_lote(microtime(true) + 20);

comprobar(
    'con criterios imposibles, lo automatico se retira',
    0,
    (int) bd()->query("SELECT COUNT(*) FROM bits WHERE redactado_por = 'ia'")->fetchColumn()
);

// Lo escrito por una persona no se toca nunca, pase lo que pase.
comprobar(
    'el bit escrito a mano sigue donde estaba',
    1,
    (int) bd()->query("SELECT COUNT(*) FROM bits WHERE redactado_por = 'humano'")->fetchColumn()
);

// Y la revision no se repite en la pasada siguiente.
comprobar(
    'la revision corre una sola vez',
    0,
    auto_publicar_lote(microtime(true) + 10)['revisados']
);

ajuste_guardar('auto_min_diccionario', '0');

// Apagar el modo automatico tiene que bastar para que no vuelva a tocar nada.
ajuste_guardar('auto_publicar', '0');

comprobar(
    'apagado, el modo automatico no hace nada',
    'desactivado',
    auto_publicar_lote(microtime(true) + 5)['estado']
);

// -----------------------------------------------------------------------------
// Fuentes que fallan: se duermen, no se mueren
// -----------------------------------------------------------------------------

require_once $raiz . '/cron/ingesta.php';

$dormilona = humo_fuente('Fuente que falla', 'prensa', 5, 'es');

function humo_fuente_fila(int $id): array
{
    $st = bd()->prepare('SELECT * FROM fuentes WHERE id = ?');
    $st->execute([$id]);

    return (array) $st->fetch();
}

// Cuatro fallos seguidos no duermen a nadie: un feed se cae un rato y vuelve.
for ($i = 0; $i < 4; $i++) {
    ingesta_marcar_fallo(humo_fuente_fila($dormilona), 'HTTP 502');
}

$fila = humo_fuente_fila($dormilona);

comprobar('cuatro fallos se cuentan', 4, (int) $fila['fallos_consecutivos']);
comprobar('pero no duermen la fuente', null, $fila['dormida_hasta']);
comprobar('y desde luego no la apagan', 1, (int) $fila['activa']);

// El quinto si.
ingesta_marcar_fallo($fila, 'HTTP 502');
$fila = humo_fuente_fila($dormilona);

comprobar('al quinto se duerme', true, $fila['dormida_hasta'] !== null);
comprobar('sin apagarse', 1, (int) $fila['activa']);

comprobar(
    'y mientras duerme no se le pide nada',
    false,
    in_array($dormilona, array_map(
        static fn (array $f): int => (int) $f['id'],
        ingesta_siguientes(0, 200)
    ), true)
);

// Un intento que sale bien la despierta del todo.
ingesta_marcar_ok($dormilona, ['etag' => null, 'last_modified' => null]);
$fila = humo_fuente_fila($dormilona);

comprobar('un acierto la despierta', null, $fila['dormida_hasta']);
comprobar('y borra la cuenta de fallos', 0, (int) $fila['fallos_consecutivos']);

comprobar(
    'y vuelve al lote',
    true,
    in_array($dormilona, array_map(
        static fn (array $f): int => (int) $f['id'],
        ingesta_siguientes(0, 200)
    ), true)
);

// Un "no" de robots.txt duerme una semana a la primera: no es una averia que
// se vaya a arreglar sola dentro de un rato.
ingesta_marcar_fallo(humo_fuente_fila($dormilona), 'robots.txt prohibe', INGESTA_SUENO_ROBOTS);

$horas = (int) bd()->query(
    'SELECT TIMESTAMPDIFF(HOUR, UTC_TIMESTAMP(), dormida_hasta) FROM fuentes WHERE id = ' . $dormilona
)->fetchColumn();

comprobar('robots.txt duerme una semana a la primera', true, $horas >= 167 && $horas <= 168);

// -----------------------------------------------------------------------------
// La fuente del boletin KEV: la trae la semilla, y el rastreador de RSS
// tiene que dejarla en paz -no sabe leer JSON, y si la cogiera en su turno
// la marcaria como una fuente rota para siempre-.
// -----------------------------------------------------------------------------

require_once $raiz . '/cron/kev.php';

$kev_fila = bd()->query("SELECT id, activa, gestion FROM fuentes WHERE nombre = 'CISA KEV'")->fetch();

comprobar('la fuente del KEV existe y esta gestionada aparte', 'manual', $kev_fila['gestion'] ?? null);
comprobar('y sigue activa -asi el panel puede apagarla de verdad-', 1, (int) ($kev_fila['activa'] ?? 0));

comprobar(
    'el rastreador de RSS nunca la coge, aunque este activa',
    false,
    in_array((int) $kev_fila['id'], array_map(
        static fn (array $f): int => (int) $f['id'],
        ingesta_siguientes(0, 200)
    ), true)
);

comprobar('kev_fuente() la encuentra para poder anclar sus items', true, kev_fuente() !== null);

// -----------------------------------------------------------------------------
// Tendencias
//
// publicar_tendencias() solo lee categoria, dia y estado de bits, asi que no
// hace falta un racimo de verdad para probarla: basta con filas propias, con
// racimo_id a NULL. La categoria es una que ningun otro fixture de este
// fichero usa, para que el recuento no se mezcle con nada de lo de arriba.
// -----------------------------------------------------------------------------

function humo_bit_tendencia(string $categoria, string $dia): void
{
    bd()->prepare(
        "INSERT INTO bits (titular, cuerpo, por_que, categoria, estado, dia)
         VALUES ('Bit de prueba de tendencia', 'Cuerpo de prueba con palabras de sobra.', '', ?, 'publicado', ?)"
    )->execute([$categoria, $dia]);
}

$humo_tema_tendencia = 'sostenibilidad-energia';
$humo_hoy            = gmdate('Y-m-d');
$humo_hace_45        = gmdate('Y-m-d', strtotime('-45 days'));
$humo_hace_120       = gmdate('Y-m-d', strtotime('-120 days'));

// Dos en el periodo anterior (91-180 dias) y cuatro en el actual (0-90 dias):
// tiene que verse como una subida del cien por cien, no como tema nuevo.
humo_bit_tendencia($humo_tema_tendencia, $humo_hace_120);
humo_bit_tendencia($humo_tema_tendencia, $humo_hace_120);
humo_bit_tendencia($humo_tema_tendencia, $humo_hace_45);
humo_bit_tendencia($humo_tema_tendencia, $humo_hace_45);
humo_bit_tendencia($humo_tema_tendencia, $humo_hoy);
humo_bit_tendencia($humo_tema_tendencia, $humo_hoy);

$tendencias    = publicar_tendencias();
$suya          = null;

foreach ($tendencias as $t) {
    if ($t['slug'] === $humo_tema_tendencia) {
        $suya = $t;
        break;
    }
}

comprobar('la tendencia aparece en el recuento', true, $suya !== null);
comprobar('cuenta bien el periodo actual', 4, $suya['actual'] ?? null);
comprobar('cuenta bien el periodo anterior', 2, $suya['anterior'] ?? null);
comprobar('calcula el porcentaje de subida', 100, $suya['porcentaje'] ?? null);
comprobar('no la marca como tema nuevo', false, $suya['nuevo'] ?? null);

// Y que todo eso llegue de verdad a la web generada, no solo a la funcion.
publicar_pendiente(microtime(true) + 10);

comprobar('escribe la pagina de tendencias', true, is_file($publico . '/tendencias.html'));
comprobar(
    'y habla del tema que ha subido',
    true,
    str_contains((string) file_get_contents($publico . '/tendencias.html'), 'Sostenibilidad y energía')
);

$portada_final = (string) file_get_contents($publico . '/index.html');

comprobar('la portada destaca las cifras del sector', true, str_contains($portada_final, '/estadisticas.html'));
comprobar('y las tendencias', true, str_contains($portada_final, '/tendencias.html'));

// -----------------------------------------------------------------------------
// Mantenimiento diario
//
// Sin buzon propio configurado en esta base de prueba, tiene que salir sin
// tocar nada ni intentar conectar a ningun SMTP: eso ya lo prueba
// pruebas/cifras.php contra la logica pura. Aqui solo se comprueba que el
// fichero existe, engancha con cron/tareas.php y corre de principio a fin
// contra la base real sin lanzar nada.
// -----------------------------------------------------------------------------

require_once $raiz . '/cron/mantenimiento.php';

$mantenimiento = mantenimiento_diario(microtime(true) + 5);

comprobar('el mantenimiento corre y devuelve su resumen', true, is_array($mantenimiento));
comprobar(
    'y no manda nada sin un buzon propio configurado',
    false,
    $mantenimiento['cifras_aviso'] ?? null
);
comprobar(
    'ni el aviso de cumplimiento tampoco',
    false,
    $mantenimiento['cumplimiento_aviso'] ?? null
);

// -----------------------------------------------------------------------------
// El catalogo de fuentes, tal como lo ensena panel/index.php?p=fuentes
//
// Para cuando se llega aqui, el racimo de $fuente_a/$fuente_b ya se publico a
// mano desde el panel, y el de $fuente_c -que abrio racimo propio- acabo
// publicandose el solo mas adelante, en las pruebas del modo automatico: los
// tres items estan en 'usado'. $fuente_d, en cambio, es la de mas abajo -sin
// resumen utilizable- y se queda descartada para siempre.
// -----------------------------------------------------------------------------

$catalogo = datos_fuentes();

comprobar('el catalogo trae las fuentes de la prueba', true, count($catalogo) >= 4);

$por_id = [];
foreach ($catalogo as $fila) {
    $por_id[(int) $fila['id']] = $fila;
}

comprobar('la fuente de un item publicado a mano cuenta un publicado', 1, $por_id[$fuente_a]['diagnostico']['publicados']);
comprobar('y la que se publico sola por el modo automatico tambien', 1, $por_id[$fuente_c]['diagnostico']['publicados']);
comprobar('la fuente sin resumen utilizable cuenta una descartada', 1, $por_id[$fuente_d]['diagnostico']['descartados']);
comprobar('ninguna de las cuatro esta fallando en esta base limpia', null, $por_id[$fuente_a]['ultimo_error']);

$fuente_nueva_id = datos_fuente_crear([
    'nombre' => 'Prueba de alta', 'url_feed' => 'https://humo.test/nueva/feed/',
    'url_sitio' => 'https://humo.test/nueva/', 'tipo' => 'prensa', 'idioma' => 'es',
    'region' => 'es', 'categoria_defecto' => 'tecnologia-general', 'peso' => 5, 'notas' => '',
]);

$st = bd()->prepare('SELECT activa, fecha_alta FROM fuentes WHERE id = ?');
$st->execute([$fuente_nueva_id]);
$fuente_nueva = $st->fetch();

comprobar('una fuente nueva desde el panel nace activa', 1, (int) $fuente_nueva['activa']);
comprobar('y con fecha de hoy', gmdate('Y-m-d'), (string) $fuente_nueva['fecha_alta']);

datos_fuente_activar($fuente_nueva_id, false);
comprobar(
    'desactivarla a mano la apaga',
    0,
    (int) bd()->query('SELECT activa FROM fuentes WHERE id = ' . (int) $fuente_nueva_id)->fetchColumn()
);

datos_fuente_activar($fuente_nueva_id, true);
comprobar(
    'y reactivarla la enciende otra vez',
    1,
    (int) bd()->query('SELECT activa FROM fuentes WHERE id = ' . (int) $fuente_nueva_id)->fetchColumn()
);

// -----------------------------------------------------------------------------
// Vaciar la cola de golpe, y el motivo de lo descartado
//
// Lo que importa comprobar no es solo que vacia: es que no toca lo que ya
// tiene un bit. Un WHERE estado='candidato' a secas se llevaria por delante
// bits en borrador o aprobados que siguen esperando su edicion, porque
// datos_crear_bit() no cambia el estado del racimo. Por eso el racimo de
// $fuente_a -publicado hace rato- es la comprobacion que importa de verdad
// aqui, no un detalle de relleno.
// -----------------------------------------------------------------------------

$fuente_e = humo_fuente('Humo Lote Uno', 'prensa', 5, 'es');
$fuente_f = humo_fuente('Humo Lote Dos', 'prensa', 5, 'es');

$item_e = humo_item(
    $fuente_e,
    'Un proveedor de channel manager lanza una nueva integracion con PMS',
    'Resumen suficiente para pasar el filtro de palabras minimas exigido en esta prueba.',
    'es'
);
$item_f = humo_item(
    $fuente_f,
    'Una cadena hotelera anuncia un acuerdo de franquicia en el Caribe',
    'Otro resumen distinto, tambien suficiente para pasar el mismo filtro de palabras.',
    'es'
);

procesar_lote(microtime(true) + 20);

$racimo_e = (int) humo_item_leer($item_e)['racimo_id'];
$racimo_f = (int) humo_item_leer($item_f)['racimo_id'];

comprobar('los dos titulares sin relacion abren racimo propio', true, $racimo_e !== $racimo_f);

$ids_en_cola = array_column(datos_cola(), 'id');

comprobar(
    'los dos racimos nuevos entran en la cola',
    true,
    in_array($racimo_e, $ids_en_cola, true) && in_array($racimo_f, $ids_en_cola, true)
);

$antes_de_vaciar = count(datos_cola());
$vaciados        = datos_descartar_cola('vaciado de prueba');

comprobar('vaciar la cola descarta exactamente lo que habia en ella', $antes_de_vaciar, $vaciados);
comprobar('la cola queda vacia despues de vaciarla', 0, count(datos_cola()));

comprobar(
    'el racimo ya publicado no se toca al vaciar la cola',
    'publicado',
    datos_racimo((int) $a['racimo_id'])['estado']
);

$racimo_vaciado = datos_racimo($racimo_e);

comprobar('el racimo vaciado queda descartado', 'descartado', $racimo_vaciado['estado'] ?? '');
comprobar('con el motivo que se escribio en el formulario', 'vaciado de prueba', $racimo_vaciado['motivo_descarte'] ?? '');

comprobar(
    'sus items tambien quedan descartados',
    'descartado',
    (string) bd()->query('SELECT estado FROM items WHERE id = ' . (int) $item_e)->fetchColumn()
);

$descartados_recientes = array_column(datos_descartados(), 'id');

comprobar(
    'lo recien vaciado aparece en los descartados recientes',
    true,
    in_array($racimo_e, $descartados_recientes, true) && in_array($racimo_f, $descartados_recientes, true)
);

comprobar('vaciar una cola ya vacia no descarta nada', 0, datos_descartar_cola('sin nada que vaciar'));

resumen_pruebas('Prueba de humo: esquema, semillas, procesado, curacion, automatico y web');
