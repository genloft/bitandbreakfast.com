<?php
/**
 * Procesado: indexa, agrupa y puntua los items que ha dejado la ingesta.
 *
 * Se ejecuta desde cron/tareas.php, o directamente por linea de comandos:
 *   php cron/procesar.php
 *
 * La cola son los items con estado 'nuevo'. Cada uno pasa por cuatro pasos y
 * sale como 'agrupado', siempre dentro de una transaccion: un item a medio
 * procesar seria peor que uno sin procesar, porque el lote siguiente ya no
 * volveria a mirarlo.
 *
 *   1. Indice invertido: sus tokens raros van a item_token.
 *   2. Proveedores: los del catalogo que menciona, a item_proveedor.
 *   3. Racimo: se busca uno que cuente lo mismo; si no lo hay, se abre.
 *   4. Puntuacion del item y del racimo.
 *
 * Como la ingesta, trabaja por lotes con presupuesto de tiempo. Aqui el
 * puntero no hace falta: el propio estado del item es la cola, y lo que no
 * entre en esta pasada sigue en 'nuevo' esperando a la siguiente.
 */

require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/texto.php';
require_once dirname(__DIR__) . '/lib/agrupar.php';
require_once dirname(__DIR__) . '/lib/puntuar.php';

/**
 * Ejecuta un lote de procesado.
 *
 * @param float $limite Marca de tiempo (microtime) a partir de la cual no se
 *                      empiezan items nuevos.
 * @return array Resumen para el registro.
 */
function procesar_lote(float $limite): array
{
    $conf     = procesar_conf();
    // Nunca por debajo de uno: un ajuste puesto a cero daria LIMIT 0 y la
    // cola dejaria de vaciarse sin una sola linea en el registro.
    $tamano   = max(1, (int) $conf['procesar_lote']);
    $alias    = procesar_alias();
    $terminos = procesar_terminos();

    $resumen = [
        'items'          => 0,
        'racimos_nuevos' => 0,
        'agrupados'      => 0,
        'fusionados'     => 0,
        'errores'        => 0,
    ];

    foreach (procesar_pendientes($tamano) as $item) {
        if (microtime(true) >= $limite) {
            break;
        }

        try {
            $hecho = procesar_item($item, $conf, $alias, $terminos);

            $resumen['items']++;
            $resumen[$hecho['nuevo'] ? 'racimos_nuevos' : 'agrupados']++;
            $resumen['fusionados'] += $hecho['fusionados'];
        } catch (Throwable $e) {
            $resumen['errores']++;
            error_log('Bit & Breakfast, procesar item ' . $item['id'] . ': ' . $e->getMessage());

            // Un item que revienta no puede parar el lote. Pero descartarlo
            // siempre tampoco vale: un interbloqueo de InnoDB o una conexion
            // caida no dicen nada de la noticia, y marcarla 'descartado' la
            // perderia para siempre sin dejar rastro distinguible de un
            // descarte editorial. Lo transitorio se queda en la cola.
            if (!procesar_error_transitorio($e)) {
                procesar_descartar((int) $item['id']);
            }
        }
    }

    // Lo unico que dice si la cola crece mas rapido de lo que se vacia, y va
    // al registro del cron en cada pasada.
    $resumen['pendientes'] = procesar_pendientes_total();

    return $resumen;
}

/**
 * Ajustes de agrupacion y puntuacion, leidos de la base sobre los valores por
 * defecto de puntuar_defectos(). La tabla de defectos vive en lib/puntuar.php
 * para que las pruebas usen exactamente los mismos numeros.
 */
function procesar_conf(): array
{
    $conf = [];

    foreach (puntuar_defectos() as $clave => $defecto) {
        $conf[$clave] = ajuste($clave, $defecto);
    }

    return $conf;
}

/**
 * Distingue el fallo que dice algo del item del que solo dice que la base
 * estaba ocupada.
 *
 * 40001 es interbloqueo o fallo de serializacion, y HY000 cubre la conexion
 * perdida y el tiempo de espera de bloqueo agotado. En los dos casos la
 * noticia es perfectamente buena y lo unico que hay que hacer es volver a
 * intentarlo en la siguiente pasada.
 */
function procesar_error_transitorio(Throwable $e): bool
{
    return $e instanceof PDOException
        && in_array((string) $e->getCode(), ['40001', 'HY000'], true);
}

/**
 * Catalogo de alias de proveedor, ya normalizados: 'alias' => proveedor_id.
 *
 * Se carga entero una vez por lote. Son unos cientos de filas como mucho, y
 * la alternativa es un LIKE por item contra una tabla que no se puede
 * indexar por el lado que hace falta.
 */
function procesar_alias(): array
{
    $alias = [];

    // Se normalizan aqui, una vez por lote. La semilla los guarda ya
    // normalizados, pero agrupar_proveedores_en() vuelve a normalizar lo que
    // recibe, y hacerlo por cada item y cada alias son miles de pasadas
    // inutiles. La normalizacion es idempotente, asi que esto no cambia nada
    // salvo el tiempo que tarda.
    foreach (bd()->query('SELECT alias_norm, proveedor_id FROM proveedor_alias') as $fila) {
        $alias[texto_normalizar((string) $fila['alias_norm'])] = (int) $fila['proveedor_id'];
    }

    return $alias;
}

/**
 * Terminos activos del diccionario.
 */
function procesar_terminos(): array
{
    $terminos = [];

    foreach (bd()->query('SELECT termino, peso FROM diccionario WHERE activo = 1') as $fila) {
        $terminos[] = [
            'termino' => texto_normalizar((string) $fila['termino']),
            'peso'    => (int) $fila['peso'],
        ];
    }

    return $terminos;
}

/**
 * Los siguientes items sin procesar, del mas antiguo al mas nuevo.
 *
 * Por id y no por fecha de publicacion: interesa que un item se compare con
 * los que ya estaban, y el orden de llegada es el unico que garantiza que el
 * racimo lo abre el primero que conto la noticia.
 */
function procesar_pendientes(int $tamano): array
{
    $sql = "SELECT i.id, i.titulo, i.resumen_origen, i.publicado, i.capturado,
                   i.fuente_id, f.peso AS peso_fuente, f.tipo AS tipo_fuente
              FROM items i
              JOIN fuentes f ON f.id = i.fuente_id
             WHERE i.estado = 'nuevo'
             ORDER BY i.id
             LIMIT ?";

    $st = bd()->prepare($sql);
    $st->bindValue(1, $tamano, PDO::PARAM_INT);
    $st->execute();

    return $st->fetchAll();
}

/**
 * Procesa un item de principio a fin.
 *
 * @return array ['nuevo' => bool, 'fusionados' => int]
 */
function procesar_item(array $item, array $conf, array $alias, array $terminos): array
{
    $id      = (int) $item['id'];
    $momento = (string) ($item['publicado'] ?? $item['capturado']);

    bd()->beginTransaction();

    try {
        $tokens = texto_tokens_clave((string) $item['titulo']);
        procesar_guardar_tokens($id, $tokens);

        $proveedores = agrupar_proveedores_en(
            (string) $item['titulo'],
            $item['resumen_origen'],
            $alias
        );
        procesar_guardar_proveedores($id, $proveedores);

        $candidatos = procesar_candidatos($id, $item['titulo'], $tokens, $proveedores, $conf);
        $elegido    = agrupar_elegir($candidatos, $conf);
        $nuevo      = $elegido === null;

        $racimo_id = $nuevo
            ? procesar_abrir_racimo((string) $item['titulo'], $momento)
            : (int) $elegido['racimo_id'];

        // Si el item se parece mucho a varios racimos a la vez, es que esos
        // racimos cuentan lo mismo y nacieron separados. Este es el unico
        // momento en que se sabe, asi que es aqui donde se cosen.
        $fusionados = $nuevo
            ? 0
            : procesar_fusionar($racimo_id, agrupar_fusionables($candidatos, $racimo_id, $conf));

        $diccionario = puntuar_diccionario(
            (string) $item['titulo'],
            $item['resumen_origen'],
            $terminos,
            (int) $conf['punt_tope_diccionario']
        );

        $puntos = puntuar_item([
            'peso_fuente'        => (int) $item['peso_fuente'],
            'tipo_fuente'        => (string) $item['tipo_fuente'],
            'publicado'          => $momento,
            'puntos_diccionario' => $diccionario['puntos'],
            'publirreportaje'    => $diccionario['publirreportaje'],
            'proveedores'        => count($proveedores),
        ], $conf);

        $sql = "UPDATE items SET racimo_id = ?, puntuacion = ?, estado = 'agrupado' WHERE id = ?";
        bd()->prepare($sql)->execute([$racimo_id, $puntos, $id]);

        procesar_recalcular_racimo($racimo_id, $conf);

        bd()->commit();

        return ['nuevo' => $nuevo, 'fusionados' => $fusionados];
    } catch (Throwable $e) {
        if (bd()->inTransaction()) {
            bd()->rollBack();
        }
        throw $e;
    }
}

/**
 * Guarda los tokens del titular en el indice invertido.
 */
function procesar_guardar_tokens(int $item_id, array $tokens): void
{
    if (!$tokens) {
        return;
    }

    $st = bd()->prepare('INSERT IGNORE INTO item_token (token, item_id) VALUES (?, ?)');

    foreach ($tokens as $token) {
        $st->execute([substr($token, 0, 40), $item_id]);
    }
}

function procesar_guardar_proveedores(int $item_id, array $proveedores): void
{
    if (!$proveedores) {
        return;
    }

    $st = bd()->prepare('INSERT IGNORE INTO item_proveedor (item_id, proveedor_id) VALUES (?, ?)');

    foreach ($proveedores as $proveedor_id) {
        $st->execute([$item_id, $proveedor_id]);
    }
}

/**
 * Busca items ya agrupados que puedan estar contando la misma noticia.
 *
 * El indice invertido es lo que hace esto viable: en vez de comparar contra
 * los cientos de items de la ventana, se compara contra los pocos que
 * comparten alguna palabra rara. Solo se miran racimos en estado candidato:
 * uno ya publicado no puede cambiar por una noticia que llega despues.
 */
function procesar_candidatos(
    int $item_id,
    string $titulo,
    array $tokens,
    array $proveedores,
    array $conf
): array {
    if (!$tokens) {
        return [];
    }

    $desde  = gmdate('Y-m-d H:i:s', time() - (int) $conf['agrupar_ventana_horas'] * 3600);
    $marcas = implode(',', array_fill(0, count($tokens), '?'));

    $sql = "SELECT i.id, i.titulo, i.racimo_id, COUNT(*) AS comunes
              FROM item_token t
              JOIN items i   ON i.id = t.item_id
              JOIN racimos r ON r.id = i.racimo_id
             WHERE t.token IN ($marcas)
               AND i.id <> ?
               AND i.racimo_id IS NOT NULL
               AND i.estado IN ('agrupado', 'usado')
               AND r.estado = 'candidato'
               AND COALESCE(i.publicado, i.capturado) >= ?
             GROUP BY i.id, i.titulo, i.racimo_id
             ORDER BY comunes DESC, i.id DESC
             LIMIT 25";

    $st        = bd()->prepare($sql);
    $posicion  = 1;

    foreach ($tokens as $token) {
        $st->bindValue($posicion++, substr($token, 0, 40));
    }
    $st->bindValue($posicion++, $item_id, PDO::PARAM_INT);
    $st->bindValue($posicion, $desde);
    $st->execute();

    $filas = $st->fetchAll();

    if (!$filas) {
        return [];
    }

    $por_item   = procesar_proveedores_de(array_column($filas, 'id'));
    $candidatos = [];

    foreach ($filas as $fila) {
        $candidatos[] = [
            'racimo_id'           => (int) $fila['racimo_id'],
            'similitud'           => texto_similitud($titulo, (string) $fila['titulo']),
            'proveedores_comunes' => agrupar_proveedores_comunes(
                $proveedores,
                $por_item[(int) $fila['id']] ?? []
            ),
        ];
    }

    return $candidatos;
}

/**
 * Proveedores de varios items de una sola consulta: [item_id => [ids]].
 */
function procesar_proveedores_de(array $item_ids): array
{
    if (!$item_ids) {
        return [];
    }

    $marcas = implode(',', array_fill(0, count($item_ids), '?'));
    $st     = bd()->prepare(
        "SELECT item_id, proveedor_id FROM item_proveedor WHERE item_id IN ($marcas)"
    );
    $st->execute(array_map('intval', $item_ids));

    $mapa = [];

    foreach ($st->fetchAll() as $fila) {
        $mapa[(int) $fila['item_id']][] = (int) $fila['proveedor_id'];
    }

    return $mapa;
}

/**
 * Abre un racimo con un solo item dentro.
 */
function procesar_abrir_racimo(string $titulo, string $momento): int
{
    $sql = "INSERT INTO racimos
              (titulo_representativo, primer_visto, ultimo_visto, n_items, puntuacion, estado)
            VALUES (?, ?, ?, 1, 0, 'candidato')";

    bd()->prepare($sql)->execute([texto_recortar($titulo, 480), $momento, $momento]);

    return (int) bd()->lastInsertId();
}

/**
 * Recalcula el racimo entero a partir de sus items.
 *
 * Se recalcula todo en lugar de ir sumando: es una consulta mas, pero el
 * racimo nunca se desincroniza de sus items, y con racimos de cinco o seis
 * items el coste no se nota.
 */
function procesar_recalcular_racimo(int $racimo_id, array $conf): void
{
    // Los items descartados por el panel no cuentan: si contaran, el racimo
    // diria "cinco medios lo cuentan" cuando ya solo quedan tres, y el
    // titular representativo podria ser el de una noticia rechazada.
    $sql = "SELECT COUNT(*) AS n,
                   COUNT(DISTINCT fuente_id) AS fuentes,
                   MIN(COALESCE(publicado, capturado)) AS primero,
                   MAX(COALESCE(publicado, capturado)) AS ultimo,
                   MAX(puntuacion) AS mejor
              FROM items
             WHERE racimo_id = ?
               AND estado <> 'descartado'";

    $st = bd()->prepare($sql);
    $st->execute([$racimo_id]);
    $agregado = $st->fetch();

    if (!$agregado || (int) $agregado['n'] === 0) {
        return;
    }

    // El titular que representa al racimo es el del mejor item, no el del
    // primero: si una nota de prensa llega antes que el analisis, el que
    // aparece en la cola del panel tiene que ser el analisis.
    //
    // Y a igualdad, el que este en espanol. El boletin se lee en espanol, y
    // publicar el titular ingles de una noticia que tambien cuenta Hosteltur
    // es regalarle al lector una traduccion que no ha pedido.
    $st = bd()->prepare(
        "SELECT titulo FROM items
          WHERE racimo_id = ? AND estado <> 'descartado'
          ORDER BY (idioma = 'es') DESC, puntuacion DESC, id ASC LIMIT 1"
    );
    $st->execute([$racimo_id]);
    $titulo = (string) $st->fetchColumn();

    $puntuacion = puntuar_racimo(
        (int) $agregado['mejor'],
        (int) $agregado['fuentes'],
        $conf
    );

    $sql = 'UPDATE racimos
               SET titulo_representativo = ?,
                   primer_visto = ?,
                   ultimo_visto = ?,
                   n_items      = ?,
                   puntuacion   = ?
             WHERE id = ?';

    bd()->prepare($sql)->execute([
        texto_recortar($titulo, 480),
        $agregado['primero'],
        $agregado['ultimo'],
        (int) $agregado['n'],
        $puntuacion,
        $racimo_id,
    ]);
}

/**
 * Cuantos items esperan todavia en la cola.
 */
function procesar_pendientes_total(): int
{
    return (int) bd()->query("SELECT COUNT(*) FROM items WHERE estado = 'nuevo'")->fetchColumn();
}

/**
 * Cose varios racimos en uno y borra los que quedan vacios.
 *
 * Solo se fusionan racimos en estado candidato: uno publicado o descartado ya
 * ha pasado por manos humanas y el agrupador no tiene nada que decir sobre el.
 * Los items se mueven, no se copian, asi que ninguno se queda sin racimo.
 *
 * @return int Cuantos racimos se han absorbido.
 */
function procesar_fusionar(int $destino, array $origenes): int
{
    if (!$origenes) {
        return 0;
    }

    $marcas = implode(',', array_fill(0, count($origenes), '?'));

    // Se vuelve a filtrar por estado aqui y no solo al buscar candidatos:
    // entre una cosa y la otra cabe que el panel haya publicado el racimo.
    $st = bd()->prepare(
        "SELECT id FROM racimos WHERE id IN ($marcas) AND estado = 'candidato'"
    );
    $st->execute(array_map('intval', $origenes));

    $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));

    if (!$ids) {
        return 0;
    }

    $marcas = implode(',', array_fill(0, count($ids), '?'));

    $st = bd()->prepare("UPDATE items SET racimo_id = ? WHERE racimo_id IN ($marcas)");
    $st->execute(array_merge([$destino], $ids));

    $st = bd()->prepare("DELETE FROM racimos WHERE id IN ($marcas)");
    $st->execute($ids);

    return count($ids);
}

/**
 * Saca de la cola un item que no se ha podido procesar.
 *
 * Sin esto, un item con un titular que rompa algo volveria a intentarse en
 * cada pasada del cron y se comeria el presupuesto del lote para siempre.
 */
function procesar_descartar(int $item_id): void
{
    try {
        bd()->prepare("UPDATE items SET estado = 'descartado' WHERE id = ?")->execute([$item_id]);
    } catch (Throwable $e) {
        error_log('Bit & Breakfast, no se ha podido descartar el item ' . $item_id . ': ' . $e->getMessage());
    }
}

// Ejecucion directa por linea de comandos, para poder probar sin esperar al
// cron. El mismo bloque que cierra cron/ingesta.php.
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {
    date_default_timezone_set('UTC');

    $resumen = procesar_lote(microtime(true) + (float) (config('presupuesto_cron') ?? 25));

    printf(
        "procesar: %d items, %d racimos nuevos, %d agrupados, %d fusionados, %d errores, %d pendientes\n",
        $resumen['items'],
        $resumen['racimos_nuevos'],
        $resumen['agrupados'],
        $resumen['fusionados'],
        $resumen['errores'],
        $resumen['pendientes']
    );
}
