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

    foreach ($fuentes as $fuente) {
        // Si no queda presupuesto, se corta aqui: el puntero ya apunta a la
        // ultima fuente terminada y la proxima pasada sigue desde ahi.
        if (microtime(true) >= $limite) {
            break;
        }

        $r = ingesta_fuente($fuente);

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
 * Devuelve las siguientes fuentes activas a partir del puntero, dando la
 * vuelta al catalogo cuando se llega al final.
 */
function ingesta_siguientes(int $puntero, int $tamano): array
{
    // LIMIT exige entero de verdad: con consultas preparadas no emuladas,
    // pasarlo como cadena hace que MySQL rechace la sentencia.
    $st = bd()->prepare('SELECT * FROM fuentes WHERE activa = 1 AND id > ? ORDER BY id LIMIT ?');
    $st->bindValue(1, $puntero, PDO::PARAM_INT);
    $st->bindValue(2, $tamano, PDO::PARAM_INT);
    $st->execute();
    $fuentes = $st->fetchAll();

    if (count($fuentes) >= $tamano || $puntero === 0) {
        return $fuentes;
    }

    // Faltan fuentes para completar el lote: se completa desde el principio.
    $st2 = bd()->prepare('SELECT * FROM fuentes WHERE activa = 1 AND id <= ? ORDER BY id LIMIT ?');
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
            throw new RuntimeException('robots.txt prohibe la descarga de este feed');
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
        ingesta_marcar_fallo($fuente, $mensaje);
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
 * Suma un fallo y, al quinto seguido, desactiva la fuente y lo deja anotado
 * para que se vea en el panel. No se borra nada: solo se apaga.
 */
function ingesta_marcar_fallo(array $fuente, string $mensaje): void
{
    $fallos = (int) $fuente['fallos_consecutivos'] + 1;

    if ($fallos >= 5) {
        $nota = texto_recortar(
            'Desactivada automaticamente el ' . gmdate('Y-m-d') . ' tras 5 fallos: ' . $mensaje,
            490
        );
        $sql = 'UPDATE fuentes SET fallos_consecutivos = ?, activa = 0, notas = ? WHERE id = ?';
        bd()->prepare($sql)->execute([$fallos, $nota, $fuente['id']]);
        return;
    }

    bd()->prepare('UPDATE fuentes SET fallos_consecutivos = ? WHERE id = ?')
        ->execute([$fallos, $fuente['id']]);
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
