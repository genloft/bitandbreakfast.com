<?php
/**
 * Ingesta: lee un lote de fuentes y guarda las entradas nuevas.
 *
 * Se ejecuta desde cron/tareas.php, o directamente por linea de comandos:
 *   php cron/ingesta.php
 *
 * Diseno por lotes con puntero circular: cada pasada procesa 15 fuentes
 * (ajuste ingesta_lote) empezando por donde lo dejo la anterior. Con 59
 * fuentes y una ejecucion por hora, todas se rastrean cada cuatro horas.
 * Nunca se intenta recorrer el catalogo entero de una vez: eso es lo que
 * revienta el limite de tiempo del alojamiento compartido.
 */

require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/feed.php';
require_once dirname(__DIR__) . '/lib/robots.php';
require_once dirname(__DIR__) . '/lib/canonica.php';
require_once dirname(__DIR__) . '/lib/texto.php';

/**
 * Las fuentes que hoy se pueden pedir: encendidas y no dormidas.
 *
 * Esta a medias a proposito -le falta la condicion del puntero y el orden-,
 * porque las dos consultas que recorren el catalogo solo se diferencian en
 * eso, y el dia que cambie lo que significa "se puede pedir" no puede
 * cambiarse en una y olvidarse en la otra.
 */
/**
 * Lo que duerme una fuente cuyo robots.txt nos cierra la puerta: una semana.
 *
 * No es un castigo, es cortesia. Volver cada hora a leer el mismo "no" es
 * gastar la banda de otro para nada.
 */
const INGESTA_SUENO_ROBOTS = 168;

const INGESTA_DESPIERTAS = "SELECT * FROM fuentes
                             WHERE activa = 1
                               AND gestion = 'rss'
                               AND (dormida_hasta IS NULL OR dormida_hasta <= UTC_TIMESTAMP())";

/**
 * Ejecuta un lote de ingesta.
 *
 * @param float $limite Marca de tiempo (microtime) a partir de la cual se
 *                      deja de empezar fuentes nuevas.
 * @return array Resumen para el registro.
 */
function ingesta_lote(float $limite): array
{
    $tamano  = (int) ajuste('ingesta_lote', 15);
    $puntero = (int) ajuste('ingesta_puntero', 0);

    $fuentes = ingesta_siguientes($puntero, $tamano);

    $resumen = ['fuentes' => 0, 'nuevos' => 0, 'errores' => 0, 'sin_cambios' => 0];

    $resultados_multi = feed_descargar_multi($fuentes);

    foreach ($fuentes as $fuente) {
        $id = $fuente['id'];
        $respuesta = $resultados_multi[$id] ?? null;

        if (!$respuesta) continue;

        $r = ingesta_fuente_multi($fuente, $respuesta);

        $resumen['fuentes']++;
        $resumen['nuevos'] += $r['nuevos'];
        if ($r['resultado'] === 'error') {
            $resumen['errores']++;
        } elseif ($r['resultado'] === 'sin_cambios') {
            $resumen['sin_cambios']++;
        }

        ajuste_guardar('ingesta_puntero', (string) $fuente['id']);
    }

    // Si el lote ha llegado al final del catalogo, el puntero vuelve a cero.
    if ($fuentes && count($fuentes) < $tamano) {
        ajuste_guardar('ingesta_puntero', '0');
    }

    return $resumen;
}

/**
 * Devuelve las siguientes fuentes despiertas a partir del puntero, dando la
 * vuelta al catalogo cuando se llega al final.
 *
 * Se saltan las dormidas -las que encadenaron fallos y tienen plazo hasta
 * dentro de un rato-, pero no se apagan: vuelven solas cuando les toque.
 */
function ingesta_siguientes(int $puntero, int $tamano): array
{
    // LIMIT exige entero de verdad: con consultas preparadas no emuladas,
    // pasarlo como cadena hace que MySQL rechace la sentencia.
    $st = bd()->prepare(INGESTA_DESPIERTAS . ' AND id > ? ORDER BY id LIMIT ?');
    $st->bindValue(1, $puntero, PDO::PARAM_INT);
    $st->bindValue(2, $tamano, PDO::PARAM_INT);
    $st->execute();
    $fuentes = $st->fetchAll();

    if (count($fuentes) >= $tamano || $puntero === 0) {
        return $fuentes;
    }

    // Faltan fuentes para completar el lote: se completa desde el principio.
    $st2 = bd()->prepare(INGESTA_DESPIERTAS . ' AND id <= ? ORDER BY id LIMIT ?');
    $st2->bindValue(1, $puntero, PDO::PARAM_INT);
    $st2->bindValue(2, $tamano - count($fuentes), PDO::PARAM_INT);
    $st2->execute();

    return array_merge($fuentes, $st2->fetchAll());
}

/**
 * Procesa una sola fuente de principio a fin y deja constancia en log_ingesta.
 */
function ingesta_fuente(array $fuente): array
{
    $inicio    = gmdate('Y-m-d H:i:s');
    $nuevos    = 0;
    $resultado = 'ok';
    $mensaje   = '';

    bd()->prepare('UPDATE fuentes SET ultimo_intento = UTC_TIMESTAMP() WHERE id = ?')
        ->execute([$fuente['id']]);

    try {
        if (!robots_permite($fuente['url_feed'])) {
            throw new RobotsProhibido('robots.txt prohibe la descarga de este feed');
        }

        $respuesta = feed_descargar($fuente['url_feed'], $fuente['etag'], $fuente['last_modified']);

        if ($respuesta['codigo'] === 304) {
            $resultado = 'sin_cambios';
            ingesta_marcar_ok($fuente['id'], $respuesta);
        } elseif ($respuesta['codigo'] !== 200) {
            throw new RuntimeException(
                'HTTP ' . $respuesta['codigo'] . ($respuesta['error'] !== '' ? ' - ' . $respuesta['error'] : '')
            );
        } else {
            $entradas = feed_parsear($respuesta['cuerpo'], $fuente['url_feed']);

            if (!$entradas) {
                throw new RuntimeException('el feed responde pero no contiene entradas legibles');
            }

            foreach ($entradas as $entrada) {
                $nuevos += ingesta_guardar_item($fuente, $entrada) ? 1 : 0;
            }

            ingesta_marcar_ok($fuente['id'], $respuesta);
        }
    } catch (Throwable $e) {
        $resultado = 'error';
        $mensaje   = texto_recortar($e->getMessage(), 480);

        // Un "no" en robots.txt no es una averia que se arregle sola: se
        // duerme la semana entera de golpe en vez de volver cada hora a que
        // nos repitan lo mismo. Si el medio cambia de idea, en siete dias se
        // entera este radar; mientras tanto, ni una peticion de mas.
        ingesta_marcar_fallo(
            $fuente,
            $mensaje,
            $e instanceof RobotsProhibido ? INGESTA_SUENO_ROBOTS : 0
        );
    }

    $sql = 'INSERT INTO log_ingesta (fuente_id, inicio, fin, nuevos, resultado, mensaje)
            VALUES (?, ?, UTC_TIMESTAMP(), ?, ?, ?)';
    bd()->prepare($sql)->execute([$fuente['id'], $inicio, $nuevos, $resultado, $mensaje]);

    return ['resultado' => $resultado, 'nuevos' => $nuevos, 'mensaje' => $mensaje];
}

/**
 * Procesa una fuente usando una respuesta ya descargada por curl_multi.
 */
function ingesta_fuente_multi(array $fuente, array $respuesta): array
{
    $inicio    = gmdate('Y-m-d H:i:s');
    $nuevos    = 0;
    $resultado = 'ok';
    $mensaje   = '';

    bd()->prepare('UPDATE fuentes SET ultimo_intento = UTC_TIMESTAMP() WHERE id = ?')
        ->execute([$fuente['id']]);

    try {
        if (!robots_permite($fuente['url_feed'])) {
            throw new RobotsProhibido('robots.txt prohibe la descarga de este feed');
        }

        if ($respuesta['codigo'] === 304 || ($respuesta['codigo'] >= 200 && $respuesta['codigo'] < 300 && empty(trim($respuesta['cuerpo'])))) {
            $resultado = 'sin_cambios';
            ingesta_marcar_ok($fuente['id'], $respuesta);
        } elseif ($respuesta['codigo'] < 200 || $respuesta['codigo'] >= 400) {
            throw new RuntimeException(
                'HTTP ' . $respuesta['codigo'] . ($respuesta['error'] !== '' ? ' - ' . $respuesta['error'] : '')
            );
        } else {
            $entradas = feed_parsear($respuesta['cuerpo'], $fuente['url_feed']);

            if (!$entradas) {
                throw new RuntimeException('el feed responde pero no contiene entradas legibles');
            }

            foreach ($entradas as $entrada) {
                $nuevos += ingesta_guardar_item($fuente, $entrada) ? 1 : 0;
            }

            ingesta_marcar_ok($fuente['id'], $respuesta);
        }
    } catch (Throwable $e) {
        $resultado = 'error';
        $mensaje   = texto_recortar($e->getMessage(), 480);
        ingesta_marcar_fallo(
            $fuente,
            $mensaje,
            $e instanceof RobotsProhibido ? INGESTA_SUENO_ROBOTS : 0
        );
    }

    $sql = 'INSERT INTO log_ingesta (fuente_id, inicio, fin, nuevos, resultado, mensaje)
            VALUES (?, ?, UTC_TIMESTAMP(), ?, ?, ?)';
    bd()->prepare($sql)->execute([$fuente['id'], $inicio, $nuevos, $resultado, $mensaje]);

    return ['resultado' => $resultado, 'nuevos' => $nuevos, 'mensaje' => $mensaje];
}

/**
 * Guarda una entrada si no la teniamos. Devuelve true si era nueva.
 *
 * La barrera contra duplicados es el indice unico sobre hash_url, no una
 * consulta previa: entre el SELECT y el INSERT cabe otra ejecucion del cron.
 */
function ingesta_guardar_item(array $fuente, array $entrada): bool
{
    $canonica = canonica_normalizar($entrada['url']);
    if ($canonica === '') {
        return false;
    }

    $sql = 'INSERT IGNORE INTO items
              (fuente_id, guid, url, url_canonica, hash_url, titulo, titulo_norm,
               resumen_origen, autor, publicado, capturado, idioma, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), ?, ?)';

    $st = bd()->prepare($sql);
    $st->execute([
        $fuente['id'],
        $entrada['guid'],
        texto_recortar($entrada['url'], 780),
        texto_recortar($canonica, 780),
        canonica_hash($canonica),
        // Recortado tambien aqui: titulo es VARCHAR(500) y con
        // STRICT_TRANS_TABLES un titular mas largo aborta el INSERT entero.
        texto_recortar($entrada['titulo'], 480),
        texto_recortar(texto_titulo_norm($entrada['titulo']), 480),
        $entrada['resumen'],
        $entrada['autor'],
        // Sin fecha en el feed se usa la de captura: es mentira piadosa, pero
        // dejarla a null sacaria la noticia de la ventana de agrupacion.
        $entrada['publicado'] ?? gmdate('Y-m-d H:i:s'),
        $fuente['idioma'],
        'nuevo',
    ]);

    return $st->rowCount() > 0;
}

/**
 * Marca la fuente como sana y guarda las cabeceras condicionales.
 */
function ingesta_marcar_ok(int $fuente_id, array $respuesta): void
{
    $sql = 'UPDATE fuentes
               SET ultimo_ok = UTC_TIMESTAMP(),
                   fallos_consecutivos = 0,
                   dormida_hasta = NULL,
                   etag = ?,
                   last_modified = ?
             WHERE id = ?';

    bd()->prepare($sql)->execute([
        $respuesta['etag'] !== null ? substr($respuesta['etag'], 0, 255) : null,
        $respuesta['last_modified'] !== null ? substr($respuesta['last_modified'], 0, 120) : null,
        $fuente_id,
    ]);
}

/**
 * Suma un fallo y, si ya van muchos, duerme la fuente una temporada.
 *
 * Antes se apagaba -activa = 0- al quinto fallo seguido, y ya no volvia a
 * encenderse jamas. En un alojamiento compartido eso es una trampa: hay
 * cortafuegos que contestan 403 a la IP del vecino durante unas horas, y la
 * regla vieja convertia esa tarde mala en una fuente perdida para siempre.
 *
 * Ahora se duerme y despierta sola. Apagarla del todo -activa = 0- sigue
 * estando ahi, pero como lo que es: una decision de una persona.
 *
 * @param int $minimo_horas Suelo para el plazo, cuando quien llama sabe algo
 *                          que la cuenta de fallos no dice.
 */
function ingesta_marcar_fallo(array $fuente, string $mensaje, int $minimo_horas = 0): void
{
    $fallos = (int) $fuente['fallos_consecutivos'] + 1;
    $horas  = max(feed_sueno($fallos), $minimo_horas);

    if ($horas === 0) {
        bd()->prepare('UPDATE fuentes SET fallos_consecutivos = ? WHERE id = ?')
            ->execute([$fallos, $fuente['id']]);

        return;
    }

    // La nota queda en la tabla para cuando alguien se pregunte por que esta
    // fuente no trae nada: dice desde cuando, cuanto duerme y por que. El
    // recuento de dormidas sale ademas en /salud.php.
    $nota = texto_recortar(
        'Dormida ' . $horas . ' h el ' . gmdate('Y-m-d H:i') . ' tras ' . $fallos
            . ' fallos seguidos: ' . $mensaje,
        490
    );

    // Las horas van pegadas a la consulta y no como parametro: INTERVAL ? HOUR
    // no lo aceptan todos los servidores, y aqui fallar significa tumbar la
    // pasada entera desde el manejador de errores. El valor sale de
    // feed_sueno(), que devuelve uno de cinco enteros, y ademas va forzado.
    $sql = 'UPDATE fuentes
               SET fallos_consecutivos = ?,
                   dormida_hasta = DATE_ADD(UTC_TIMESTAMP(), INTERVAL ' . (int) $horas . ' HOUR),
                   notas = ?
             WHERE id = ?';

    bd()->prepare($sql)->execute([$fallos, $nota, $fuente['id']]);
}

// Ejecucion directa por linea de comandos, para poder probar sin esperar al cron.
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {
    $limite  = microtime(true) + (float) (config('presupuesto_cron') ?? 25);
    $resumen = ingesta_lote($limite);

    printf(
        "ingesta: %d fuentes, %d items nuevos, %d sin cambios, %d errores\n",
        $resumen['fuentes'],
        $resumen['nuevos'],
        $resumen['sin_cambios'],
        $resumen['errores']
    );
}
