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

$resumen_web = publicar_pendiente(microtime(true) + 30);

comprobar('el generador publica un dia', 1, $resumen_web['dias']);

$dia = substr((string) $edicion['fecha_prevista'], 0, 10);

comprobar('escribe la portada', true, is_file($publico . '/index.html'));
comprobar('escribe el dia en su carpeta', true, is_file($publico . '/' . web_ruta_dia($dia)));
comprobar('escribe el archivo', true, is_file($publico . '/archivo.html'));
comprobar('escribe la pagina de que es esto', true, is_file($publico . '/sobre.html'));
comprobar('escribe el indice de temas', true, is_file($publico . '/temas.html'));
comprobar('y el de medios', true, is_file($publico . '/medios.html'));
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

comprobar(
    'la portada no cuela etiquetas que vengan del panel',
    false,
    str_contains($portada, '<script')
);

// Las cifras de la cabecera: van en todas las paginas, no solo en la portada,
// porque quien llega por un enlace a una ficha tambien quiere saber si esto
// esta vivo.
comprobar('la cabecera lleva el panel de cifras', true, str_contains($portada, 'class="panel"'));

// Cada noticia enlaza a su articulo original, y lo enlaza dos veces: desde el
// titular, que es donde todo el mundo pincha, y al pie con todas las letras.
// Un agregador que no lleva a la fuente no es un agregador.
comprobar(
    'el titular lleva al original',
    true,
    (bool) preg_match('~<h2 id="titular-\d+">\s*<a href="[^"]+"~', $portada)
);

comprobar(
    'y el pie lo dice con todas las letras',
    true,
    str_contains($portada, 'Leer el original en')
);
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

resumen_pruebas('Prueba de humo: esquema, semillas, procesado, curacion, automatico y web');
