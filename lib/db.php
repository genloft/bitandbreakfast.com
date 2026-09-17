<?php
/**
 * Conexion unica a la base de datos y acceso a la configuracion.
 *
 * No hay capa de abstraccion: se devuelve el PDO pelado y las consultas se
 * escriben a la vista en cada fichero. Lo unico que se centraliza es la
 * conexion, el modo de errores y el juego de caracteres.
 */

/**
 * Devuelve la configuracion completa, leida una sola vez.
 */
function config(?string $clave = null)
{
    static $config = null;

    if ($config === null) {
        $ruta = dirname(__DIR__) . '/config/config.php';
        if (!is_readable($ruta)) {
            throw new RuntimeException(
                'No existe config/config.php. Copia config/config.ejemplo.php y rellenalo.'
            );
        }
        $config = require $ruta;
    }

    if ($clave === null) {
        return $config;
    }

    // Admite "bd.host" para no tener que encadenar corchetes por todas partes.
    $valor = $config;
    foreach (explode('.', $clave) as $parte) {
        if (!is_array($valor) || !array_key_exists($parte, $valor)) {
            return null;
        }
        $valor = $valor[$parte];
    }
    return $valor;
}

/**
 * Un ajuste de configuracion que puede no estar.
 *
 * config() revienta cuando no hay config/config.php, y hace bien: en
 * produccion, un sitio sin configuracion tiene que parar en seco y no seguir a
 * medias. Pero hay codigo que se ejecuta tambien donde no hay configuracion
 * -las pruebas, sin ir mas lejos- y que solo necesita un valor por defecto.
 */
function config_opcional(string $clave, $defecto = null)
{
    try {
        $valor = config($clave);
    } catch (Throwable $e) {
        return $defecto;
    }

    return $valor ?? $defecto;
}

/**
 * Conexion PDO unica. Lanza excepciones: cualquier fallo de SQL debe romper
 * ruidosamente en el cron y quedar en el registro, no pasar desapercibido.
 */
function bd(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $bd  = config('bd');
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $bd['host'],
        $bd['puerto'] ?? 3306,
        $bd['nombre']
    );

    try {
        $pdo = new PDO($dsn, $bd['usuario'], $bd['clave'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Consultas realmente preparadas en el servidor, no emuladas:
            // asi los enteros viajan como enteros y LIMIT ? funciona.
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // El mensaje original lleva usuario y host; no se propaga hacia fuera.
        error_log('Fallo de conexion a la base de datos: ' . $e->getMessage());
        throw new RuntimeException('No se ha podido conectar con la base de datos.');
    }

    // Todo el sistema trabaja en UTC y solo se convierte al mostrar.
    $pdo->exec("SET time_zone = '+00:00'");

    return $pdo;
}

/**
 * Cache de la tabla ajustes, compartida por ajuste() y ajuste_guardar().
 *
 * Se lee entera una vez por ejecucion porque el motor de puntuacion la
 * consulta en bucle. Vive en una funcion propia, y no en un static dentro de
 * ajuste(), para que al escribir se pueda refrescar: si no, quien guarda un
 * ajuste y lo vuelve a leer en la misma pasada recibe el valor viejo, y ese
 * fallo no se ve hasta que algo se ejecuta dos veces seguidas.
 *
 * @param array|null $reemplazo Si se pasa, sustituye la cache entera.
 */
/**
 * ¿Existe esa columna?
 *
 * Nace de una tarde entera perdida. El despliegue trae el codigo nuevo y el
 * cron aplica la migracion despues, asi que hay una ventana -minutos, o dias
 * si la migracion falla- en la que el codigo pide una columna que todavia no
 * esta. Una consulta con una columna que no existe no devuelve nada: revienta.
 * Y si esa consulta es la que genera la web, el sitio deja de actualizarse
 * entero por una etiqueta de idioma.
 *
 * Con esto, lo accesorio se puede pedir solo si esta. Una consulta a
 * information_schema por ejecucion, cacheada, y a cambio el sitio sobrevive a
 * sus propios despliegues.
 */
function bd_columna(string $tabla, string $columna): bool
{
    static $vistas = [];

    $llave = $tabla . '.' . $columna;

    if (isset($vistas[$llave])) {
        return $vistas[$llave];
    }

    try {
        $st = bd()->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $st->execute([$tabla, $columna]);

        $vistas[$llave] = (int) $st->fetchColumn() > 0;
    } catch (Throwable $e) {
        // Ante la duda, que no esta: lo accesorio se queda fuera y la consulta
        // principal sigue funcionando, que es de lo que se trata.
        $vistas[$llave] = false;
    }

    return $vistas[$llave];
}

function ajustes_cache(?array $reemplazo = null): array
{
    static $cache = null;

    if ($reemplazo !== null) {
        return $cache = $reemplazo;
    }

    if ($cache === null) {
        $cache = [];
        foreach (bd()->query('SELECT clave, valor FROM ajustes') as $fila) {
            $cache[$fila['clave']] = $fila['valor'];
        }
    }

    return $cache;
}

/**
 * Lee un ajuste de la tabla ajustes.
 */
function ajuste(string $clave, $defecto = null)
{
    $cache = ajustes_cache();

    return array_key_exists($clave, $cache) ? $cache[$clave] : $defecto;
}

/**
 * Escribe un ajuste. Se usa sobre todo para los punteros de lote y para las
 * firmas de lo ya generado.
 */
function ajuste_guardar(string $clave, string $valor): void
{
    $sql = 'INSERT INTO ajustes (clave, valor) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE valor = VALUES(valor)';
    bd()->prepare($sql)->execute([$clave, $valor]);

    $cache = ajustes_cache();
    $cache[$clave] = $valor;
    ajustes_cache($cache);
}
