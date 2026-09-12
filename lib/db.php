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
 * Lee un ajuste de la tabla ajustes. Los ajustes se cachean por ejecucion
 * porque el motor de puntuacion los consulta en bucle.
 */
function ajuste(string $clave, $defecto = null)
{
    static $cache = null;

    if ($cache === null) {
        $cache = [];
        foreach (bd()->query('SELECT clave, valor FROM ajustes') as $fila) {
            $cache[$fila['clave']] = $fila['valor'];
        }
    }

    return array_key_exists($clave, $cache) ? $cache[$clave] : $defecto;
}

/**
 * Escribe un ajuste. Se usa sobre todo para los punteros de lote.
 */
function ajuste_guardar(string $clave, string $valor): void
{
    $sql = 'INSERT INTO ajustes (clave, valor) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE valor = VALUES(valor)';
    bd()->prepare($sql)->execute([$clave, $valor]);
}
