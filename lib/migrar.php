<?php
/**
 * Migraciones: cambios de esquema que se aplican solos.
 *
 * Hasta ahora, cualquier tabla nueva exigia abrir phpMyAdmin y pegar el SQL a
 * mano. Eso convierte cada cambio de esquema en una tarea del dueno del sitio,
 * que es justo lo que este proyecto intenta evitar, y en la practica significa
 * que el cambio no se hace y el codigo nuevo convive con la base vieja.
 *
 * El mecanismo es el mas simple que funciona: un fichero .sql por cambio, en
 * sql/migraciones/, con nombre que ordena -001-, -002-...-, y un ajuste que
 * guarda cual fue el ultimo aplicado. Se aplican en orden, una vez, y si una
 * falla se para ahi: media migracion aplicada y las siguientes encima es peor
 * que no haber empezado.
 *
 * Sin transacciones a proposito: MySQL hace commit implicito en cada DDL, asi
 * que envolverlas daria una sensacion de seguridad que no existe. Por eso las
 * migraciones se escriben para poder repetirse -CREATE TABLE IF NOT EXISTS,
 * ADD COLUMN comprobando antes- y no para deshacerse.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

/** Clave del ajuste donde se guarda la ultima migracion aplicada. */
const MIGRAR_AJUSTE = 'migracion_ultima';

/**
 * Aplica las migraciones que falten.
 *
 * @param string $raiz Raiz del proyecto.
 *
 * @return array ['aplicadas' => string[], 'error' => string]
 */
function migrar_pendientes(string $raiz): array
{
    $resultado = ['aplicadas' => [], 'error' => ''];
    $ultima    = (string) ajuste(MIGRAR_AJUSTE, '');

    foreach (migrar_ficheros($raiz, $ultima) as $ruta) {
        $nombre = basename($ruta);
        $sql    = (string) @file_get_contents($ruta);

        if (trim($sql) === '') {
            continue;
        }

        try {
            foreach (migrar_sentencias($sql) as $sentencia) {
                bd()->exec($sentencia);
            }
        } catch (Throwable $e) {
            // Se para en la primera que falla y no se guarda como aplicada:
            // las siguientes pueden depender de esta.
            $resultado['error'] = $nombre . ': ' . $e->getMessage();
            error_log('Bit & Breakfast, migracion ' . $nombre . ': ' . $e->getMessage());

            // Y queda escrito donde se pueda leer sin entrar al servidor. Esto
            // se aprendio por las malas: una migracion fallaba, la cola entera
            // se quedaba parada detras, el codigo nuevo esperaba una columna
            // que no existia y desde fuera lo unico que se veia era que el
            // sitio habia dejado de publicar. El error estaba en el registro
            // del cron, al que no se llega sin SSH.
            @ajuste_guardar('migracion_error', texto_error_corto($resultado['error']));

            return $resultado;
        }

        ajuste_guardar('migracion_error', '');

        ajuste_guardar(MIGRAR_AJUSTE, $nombre);
        $resultado['aplicadas'][] = $nombre;
    }

    return $resultado;
}

/**
 * El error, en una linea y sin rutas del servidor.
 *
 * Va a /salud.php, que es publica: el mensaje de una excepcion de PDO puede
 * llevar dentro la consulta entera y, con ella, nombres de tablas y rutas.
 */
function texto_error_corto(string $mensaje): string
{
    $limpio = (string) preg_replace('~(?<![:\w/])/(?:[\w.-]+/)+[\w.-]*~u', '…', $mensaje);
    $limpio = trim((string) preg_replace('/\s+/', ' ', $limpio));

    return mb_substr($limpio, 0, 200);
}

/**
 * Los ficheros de migracion posteriores al ultimo aplicado, en orden.
 *
 * Se comparan por nombre, que por eso empieza por un numero con ceros: el
 * orden alfabetico y el cronologico tienen que ser el mismo.
 *
 * @return string[] Rutas absolutas.
 */
function migrar_ficheros(string $raiz, string $ultima): array
{
    $todas = glob($raiz . '/sql/migraciones/*.sql') ?: [];

    sort($todas, SORT_STRING);

    if ($ultima === '') {
        return $todas;
    }

    $pendientes = [];

    foreach ($todas as $ruta) {
        if (strcmp(basename($ruta), $ultima) > 0) {
            $pendientes[] = $ruta;
        }
    }

    return $pendientes;
}

/**
 * Parte un fichero SQL en sentencias.
 *
 * Nada de analizar SQL de verdad: se parte por el punto y coma al final de
 * linea, que es como estan escritas las migraciones de este proyecto, y se
 * quitan los comentarios de linea. Si algun dia hace falta un procedimiento
 * almacenado con puntos y comas dentro, esto habra que cambiarlo; mientras
 * tanto, mas vale que se entienda de un vistazo.
 *
 * @return string[]
 */
function migrar_sentencias(string $sql): array
{
    $limpio = (string) preg_replace('/^\s*--.*$/m', '', $sql);
    $trozos = preg_split('/;\s*[\r\n]+/', $limpio) ?: [];
    $salida = [];

    foreach ($trozos as $trozo) {
        $trozo = trim(rtrim(trim($trozo), ';'));

        if ($trozo !== '') {
            $salida[] = $trozo;
        }
    }

    return $salida;
}
