<?php
/**
 * Motor del instalador web.
 *
 * Aqui vive todo lo que el instalador hace de verdad: comprobar el servidor,
 * tomar el cerrojo, importar el SQL, escribir la configuracion y borrarse.
 * instalar.php se queda con el flujo, y plantillas/instalador.php con la cara.
 *
 * Este fichero no imprime nada ni lee $_POST, y no se puede abrir desde el
 * navegador: lib/ devuelve 403.
 */

declare(strict_types=1);

/** Segundos que dura el cerrojo de instalacion antes de liberarse solo. */
const INST_VIDA_BLOQUEO = 1800;          // 30 minutos

/**
 * Segundos de rastreo en la primera ingesta lanzada desde el instalador.
 *
 * Corto a proposito: el presupuesto solo impide empezar fuentes nuevas, asi
 * que una descarga lenta puede anadir otros diez segundos por encima, y el
 * limite de tiempo de una peticion web en alojamiento compartido no perdona.
 */
const INST_PRESUPUESTO_INGESTA = 12;

function inst_raiz(): string
{
    return dirname(__DIR__);
}

function inst_fichero_config(): string
{
    return inst_raiz() . '/config/config.php';
}

function inst_fichero_bloqueo(): string
{
    return inst_raiz() . '/config/.instalacion';
}

function inst_instalado(): bool
{
    return is_file(inst_fichero_config());
}

// -----------------------------------------------------------------------------
// Cerrojo de instalacion
//
// El instalador se arma solo mientras no exista config/config.php, de manera
// que cualquiera que abra el dominio recien desplegado cae en el. Para que esa
// ventana no quede abierta a dos manos a la vez, quien envia el formulario
// primero se queda con un testigo aleatorio y solo ese navegador puede
// instalar. El cerrojo se toma al enviar, nunca al pintar la pagina: si se
// tomara en cada visita, el primer rastreador que pasara dejaria fuera al
// dueño del sitio. Caduca solo a la media hora.
// -----------------------------------------------------------------------------

/**
 * Lee el cerrojo. Uno caducado o ilegible cuenta como inexistente.
 */
function inst_bloqueo_leer(): ?array
{
    $ruta = inst_fichero_bloqueo();

    if (!is_file($ruta)) {
        return null;
    }

    $datos = json_decode((string) @file_get_contents($ruta), true);

    if (!is_array($datos) || !isset($datos['testigo'], $datos['creado'])) {
        return null;
    }

    if (time() - (int) $datos['creado'] > INST_VIDA_BLOQUEO) {
        return null;
    }

    return ['testigo' => (string) $datos['testigo'], 'creado' => (int) $datos['creado']];
}

/**
 * Devuelve 'libre', 'mio' o 'ajeno' comparando el cerrojo con el testigo que
 * lleva la sesion del visitante.
 */
function inst_bloqueo_estado(string $testigo_sesion): string
{
    $datos = inst_bloqueo_leer();

    if ($datos === null) {
        return 'libre';
    }

    if ($testigo_sesion !== '' && hash_equals($datos['testigo'], $testigo_sesion)) {
        return 'mio';
    }

    return 'ajeno';
}

/**
 * Escribe el cerrojo con el testigo dado y la hora actual. Sirve para tomarlo
 * la primera vez y para renovarlo en cada paso.
 */
function inst_bloqueo_tomar(string $testigo): void
{
    @file_put_contents(
        inst_fichero_bloqueo(),
        json_encode(['testigo' => $testigo, 'creado' => time()])
    );
    @chmod(inst_fichero_bloqueo(), 0600);
}

function inst_bloqueo_soltar(): void
{
    @unlink(inst_fichero_bloqueo());
}

/**
 * Minutos que le quedan a un cerrojo ajeno antes de liberarse solo.
 */
function inst_bloqueo_minutos(): int
{
    $datos = inst_bloqueo_leer();

    if ($datos === null) {
        return 0;
    }

    return (int) max(1, ceil((INST_VIDA_BLOQUEO - (time() - $datos['creado'])) / 60));
}

// -----------------------------------------------------------------------------
// Comprobaciones del entorno
// -----------------------------------------------------------------------------

/**
 * Tabla de requisitos, cada fila [etiqueta, se cumple, detalle].
 */
function inst_requisitos(): array
{
    $raiz       = inst_raiz();
    $requisitos = [];

    $requisitos[] = [
        'PHP 8.1 o superior',
        version_compare(PHP_VERSION, '8.1.0', '>='),
        'versión detectada: ' . PHP_VERSION,
    ];

    foreach (['pdo_mysql', 'curl', 'mbstring', 'simplexml', 'json'] as $extension) {
        $requisitos[] = [
            'Extensión ' . $extension,
            extension_loaded($extension),
            extension_loaded($extension) ? 'disponible' : 'actívala en hPanel, Configuración PHP',
        ];
    }

    foreach (['config', 'cache', 'publico'] as $directorio) {
        $ruta = $raiz . '/' . $directorio;
        $requisitos[] = [
            'Carpeta ' . $directorio . '/ con permiso de escritura',
            is_dir($ruta) && is_writable($ruta),
            is_dir($ruta) ? 'permisos actuales: ' . substr(sprintf('%o', fileperms($ruta)), -3) : 'no existe',
        ];
    }

    foreach (['esquema.sql', 'semilla_fuentes.sql', 'semilla_diccionario.sql', 'semilla_proveedores.sql'] as $fichero) {
        $requisitos[] = [
            'sql/' . $fichero,
            is_readable($raiz . '/sql/' . $fichero),
            is_readable($raiz . '/sql/' . $fichero) ? 'encontrado' : 'falta',
        ];
    }

    $requisitos[] = [
        'El instalador puede borrarse solo al terminar',
        is_writable($raiz),
        is_writable($raiz) ? 'la raíz admite escritura' : 'tendrás que borrar instalar.php a mano',
    ];

    return $requisitos;
}

function inst_requisitos_ok(array $requisitos): bool
{
    foreach ($requisitos as $requisito) {
        if (!$requisito[1]) {
            return false;
        }
    }

    return true;
}

// -----------------------------------------------------------------------------
// Importacion del SQL
// -----------------------------------------------------------------------------

/**
 * Parte un fichero .sql en sentencias sueltas.
 *
 * No vale con explode(';'): hay valores que llevan punto y coma dentro de las
 * comillas. Esto recorre el texto respetando cadenas y comentarios.
 *
 * Entiende comillas simples con escapes y comentarios de linea con --. No
 * entiende comillas dobles, comentarios de bloque, # ni DELIMITER. Los
 * ficheros de sql/ se escriben dentro de ese subconjunto a proposito: si
 * algun dia hace falta un trigger o un procedimiento, hay que ampliar esto
 * antes, no despues.
 */
function inst_sentencias(string $sql): array
{
    $sentencias = [];
    $actual     = '';
    $en_cadena  = false;
    $largo      = strlen($sql);

    for ($i = 0; $i < $largo; $i++) {
        $c = $sql[$i];

        if ($en_cadena) {
            $actual .= $c;
            if ($c === '\\' && $i + 1 < $largo) {
                $actual .= $sql[++$i];      // caracter escapado
            } elseif ($c === "'") {
                $en_cadena = false;
            }
            continue;
        }

        // Comentario de linea: se descarta hasta el salto, pero el salto se
        // conserva. Sin el, un comentario al final de una linea pegaria la
        // palabra anterior con la primera de la linea siguiente.
        if ($c === '-' && substr($sql, $i, 2) === '--') {
            $fin = strpos($sql, "\n", $i);

            if ($fin === false) {
                $i = $largo;
                continue;
            }

            $actual .= "\n";
            $i       = $fin;
            continue;
        }

        if ($c === "'") {
            $en_cadena = true;
            $actual   .= $c;
            continue;
        }

        if ($c === ';') {
            $sentencias[] = trim($actual);
            $actual       = '';
            continue;
        }

        $actual .= $c;
    }

    if (trim($actual) !== '') {
        $sentencias[] = trim($actual);
    }

    return array_values(array_filter($sentencias, static fn($s) => $s !== ''));
}

/**
 * Ejecuta un fichero .sql completo y devuelve cuantas sentencias corrieron.
 */
function inst_ejecutar_sql(PDO $pdo, string $ruta): int
{
    if (!is_readable($ruta)) {
        throw new RuntimeException(
            'No se encuentra ' . basename($ruta) . '. ¿Se ha desplegado la carpeta sql/?'
        );
    }

    $sentencias = inst_sentencias((string) file_get_contents($ruta));

    foreach ($sentencias as $sentencia) {
        $pdo->exec($sentencia);
    }

    return count($sentencias);
}

// -----------------------------------------------------------------------------
// Escritura de la configuracion
// -----------------------------------------------------------------------------

/**
 * Genera el contenido de config/config.php.
 *
 * Los valores pasan por var_export, asi que una contrasena con comillas o
 * barras invertidas ni rompe el fichero ni permite colar codigo.
 */
function inst_plantilla_config(array $datos): string
{
    $fecha     = gmdate('Y-m-d H:i') . ' UTC';
    $host      = var_export($datos['host'], true);
    $nombre    = var_export($datos['nombre'], true);
    $usuario   = var_export($datos['usuario'], true);
    $clave     = var_export($datos['clave'], true);
    $puerto    = var_export((int) $datos['puerto'], true);
    $dominio   = var_export($datos['dominio'], true);
    $url       = var_export('https://' . $datos['dominio'], true);
    $agente    = var_export(
        'Mozilla/5.0 (compatible; BitAndBreakfastBot/1.0; +https://' . $datos['dominio'] . '/bot)',
        true
    );
    $remitente = var_export('hola@' . $datos['dominio'], true);
    $hmac      = var_export($datos['secreto_hmac'], true);
    $token     = var_export($datos['token_api'], true);
    $sal       = var_export($datos['sal_hash'], true);

    return <<<PHP
    <?php
    /**
     * Bit & Breakfast - configuracion del servidor.
     * Generado por el instalador web el {$fecha}.
     *
     * Este fichero NO esta en el repositorio y no se sobrescribe al desplegar.
     * Mientras exista, el instalador se niega a ejecutarse.
     */

    return [

        'bd' => [
            'host'    => {$host},
            'nombre'  => {$nombre},
            'usuario' => {$usuario},
            'clave'   => {$clave},
            'puerto'  => {$puerto},
        ],

        'sitio' => [
            'nombre'       => 'Bit & Breakfast',
            'dominio'      => {$dominio},
            'url'          => {$url},
            'zona_horaria' => 'Europe/Madrid',
        ],

        'rastreador' => [
            'user_agent'    => {$agente},
            'timeout'       => 10,
            'pausa_dominio' => 1,
            'max_bytes'     => 5242880,
        ],

        // Segundos por tarea del cron, y techo de la ejecucion entera.
        'presupuesto_cron'       => 25,
        'presupuesto_cron_total' => 75,

        'rutas' => [
            'raiz'    => dirname(__DIR__),
            'cache'   => dirname(__DIR__) . '/cache',
            'publico' => dirname(__DIR__) . '/publico',
        ],

        'secretos' => [
            'secreto_hmac' => {$hmac},
            'token_api'    => {$token},
            'sal_hash'     => {$sal},
        ],

        'correo' => [
            'proveedor'        => 'mailerlite',
            'api_key'          => '',
            'remitente'        => {$remitente},
            'nombre_remitente' => 'Bit & Breakfast',
        ],

        'depuracion' => false,
    ];

    PHP;
}

/**
 * Escribe una pagina provisional en publico/ si no hay ninguna.
 *
 * La raiz del dominio reescribe hacia publico/, y un directorio sin index y
 * sin listado de ficheros devuelve 403. Esto evita que el sitio parezca roto
 * entre la instalacion y la primera edicion publicada.
 *
 * Los estilos van en un fichero aparte, no en atributos style: la politica de
 * seguridad de contenido del sitio es 'self' y el estilo en linea no se
 * aplicaria. El generador de la fase 4 sobrescribira los dos ficheros.
 */
function inst_pagina_provisional(): bool
{
    $publico = inst_raiz() . '/publico';

    if (!is_file($publico . '/estilo.css')) {
        $css = <<<CSS
        :root { color-scheme: light; }
        body { margin: 0; padding: 4rem 1.5rem; background: #f6f5f2; color: #1b1b1a;
               font: 16px/1.6 system-ui, -apple-system, "Segoe UI", sans-serif; }
        main { max-width: 32rem; margin: 0 auto; }
        h1 { font-size: 1.6rem; margin: 0 0 .5rem; }
        p { color: #6b675e; margin: 0; }
        p + p { margin-top: 2rem; }

        CSS;

        @file_put_contents($publico . '/estilo.css', $css);
    }

    if (is_file($publico . '/index.html')) {
        return true;   // ya hay algo publicado; no se toca
    }

    $html = <<<HTML
    <!doctype html>
    <html lang="es">
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bit &amp; Breakfast</title>
    <link rel="stylesheet" href="/estilo.css">
    </head>
    <body>
    <main>
    <h1>Bit &amp; Breakfast</h1>
    <p>Radar de tecnolog&iacute;a hotelera. Cinco minutos de lectura a la semana.</p>
    <p>Pronto, la primera edici&oacute;n.</p>
    </main>
    </body>
    </html>

    HTML;

    return @file_put_contents($publico . '/index.html', $html) !== false;
}

// -----------------------------------------------------------------------------
// Instalacion
// -----------------------------------------------------------------------------

/**
 * Valida el formulario. Devuelve la lista de errores, vacia si todo va bien.
 */
function inst_validar(array $datos): array
{
    $errores = [];

    if ($datos['nombre'] === '' || $datos['usuario'] === '') {
        $errores[] = 'El nombre de la base de datos y el usuario son obligatorios.';
    }

    // Servidor y nombre se concatenan en el DSN. Un punto y coma ahi colaria
    // parametros extra en la cadena de conexion (unix_socket=, charset=...),
    // asi que se validan por lista blanca. De paso, esto caza el error tipico
    // de escribir "localhost:3306" en la casilla del servidor.
    if (!preg_match('/^[a-zA-Z0-9._-]{1,255}$/', $datos['host'])) {
        $errores[] = 'El servidor admite letras, números, punto, guion y guion bajo. El puerto va en su propia casilla.';
    }

    if ($datos['nombre'] !== '' && !preg_match('/^[a-zA-Z0-9_$]{1,64}$/', $datos['nombre'])) {
        $errores[] = 'El nombre de la base de datos solo admite letras, números y guion bajo.';
    }

    if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $datos['dominio'])) {
        $errores[] = 'Escribe el dominio sin https:// y sin barra final, por ejemplo bitandbreakfast.com';
    }

    if ($datos['puerto'] < 1 || $datos['puerto'] > 65535) {
        $errores[] = 'El puerto tiene que ser un número entre 1 y 65535.';
    }

    if ($datos['panel_usuario'] !== '' && strlen($datos['panel_clave']) < 12) {
        $errores[] = 'La contraseña del panel debe tener al menos 12 caracteres.';
    }

    if ($datos['panel_usuario'] !== '' && !preg_match('/^[a-zA-Z0-9._-]{3,60}$/', $datos['panel_usuario'])) {
        $errores[] = 'El usuario del panel admite letras, números, punto, guion y guion bajo, entre 3 y 60 caracteres.';
    }

    return $errores;
}

/**
 * Instala de principio a fin: conecta, importa, escribe la configuracion y
 * crea el usuario del panel. Devuelve el resumen que ve el usuario.
 *
 * Cualquier fallo sale como excepcion; quien llama decide como contarlo.
 */
function inst_instalar(array $datos): array
{
    $raiz = inst_raiz();

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $datos['host'],
        $datos['puerto'],
        $datos['nombre']
    );

    $pdo = new PDO($dsn, $datos['usuario'], $datos['clave'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $sentencias  = inst_ejecutar_sql($pdo, $raiz . '/sql/esquema.sql');
    $sentencias += inst_ejecutar_sql($pdo, $raiz . '/sql/semilla_fuentes.sql');
    $sentencias += inst_ejecutar_sql($pdo, $raiz . '/sql/semilla_diccionario.sql');
    $sentencias += inst_ejecutar_sql($pdo, $raiz . '/sql/semilla_proveedores.sql');

    if ($datos['panel_usuario'] !== '') {
        $sql = 'INSERT INTO usuarios (usuario, hash_clave, nombre, activo) VALUES (?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE hash_clave = VALUES(hash_clave), activo = 1';
        $pdo->prepare($sql)->execute([
            $datos['panel_usuario'],
            password_hash($datos['panel_clave'], PASSWORD_DEFAULT),
            $datos['panel_usuario'],
        ]);
    }

    // Hasta la fase 4 no hay web generada, y publico/ vacio hace que Apache
    // responda 403 en la raiz del dominio.
    $portada = inst_pagina_provisional();

    $resumen = [
        'sentencias' => $sentencias,
        'fuentes'    => (int) $pdo->query('SELECT COUNT(*) FROM fuentes')->fetchColumn(),
        'terminos'   => (int) $pdo->query('SELECT COUNT(*) FROM diccionario')->fetchColumn(),
        'proveedores' => (int) $pdo->query('SELECT COUNT(*) FROM proveedores')->fetchColumn(),
        'ajustes'    => (int) $pdo->query('SELECT COUNT(*) FROM ajustes')->fetchColumn(),
        'usuario'    => $datos['panel_usuario'],
        'dominio'    => $datos['dominio'],
        'portada'    => $portada,
        'cron'       => inst_linea_cron(),
        'logs'       => inst_carpeta_logs(),
    ];

    // Secretos: 64 caracteres hexadecimales de origen criptografico.
    $datos['secreto_hmac'] = bin2hex(random_bytes(32));
    $datos['token_api']    = bin2hex(random_bytes(32));
    $datos['sal_hash']     = bin2hex(random_bytes(32));

    // La configuracion se escribe la ultima, y por eso va aqui abajo. En
    // cuanto existe, el sitio cuenta como instalado y el instalador se
    // desarma: si algo revienta despues de escribirla, quedaria un sitio a
    // medias sin forma de reintentar desde el navegador.
    if (@file_put_contents(inst_fichero_config(), inst_plantilla_config($datos)) === false) {
        throw new RuntimeException(
            'No se ha podido escribir config/config.php. Revisa los permisos de la carpeta config/.'
        );
    }
    @chmod(inst_fichero_config(), 0600);

    return $resumen;
}

/**
 * Lee el token de la API de la configuracion recien escrita.
 *
 * Se consulta al pintar la pantalla, en lugar de arrastrarlo en la sesion:
 * un secreto de 64 caracteres no tiene por que quedarse esperando en el
 * fichero de sesion del servidor hasta que pase el recolector.
 */
function inst_token_api(?string $ruta = null): string
{
    $ruta = $ruta ?? inst_fichero_config();

    if (!is_readable($ruta)) {
        return '';
    }

    $config = @include $ruta;

    return is_array($config) ? (string) ($config['secretos']['token_api'] ?? '') : '';
}

/**
 * Borra la configuracion a medio escribir.
 *
 * Solo se llama cuando la instalacion revienta despues de haber escrito
 * config/config.php: antes de esa peticion el fichero no existia, asi que el
 * unico que puede haberlo dejado ahi es el intento que acaba de fallar. Sin
 * esto quedaria un sitio a medias que ademas deja el instalador inerte, y no
 * habria forma de reintentar desde el navegador.
 */
function inst_deshacer_config(): void
{
    if (is_file(inst_fichero_config())) {
        @unlink(inst_fichero_config());
    }
}

/**
 * Carpeta de registro del cron, fuera de public_html para que no se lea desde
 * la web.
 *
 * En Hostinger el proyecto vive en /home/uXXX/domains/dominio/public_html y
 * la carpeta natural es /home/uXXX/logs, tres niveles por encima. Pero no
 * todos los planes tienen esa forma, asi que se prueban los ancestros de
 * arriba abajo y se elige el primero donde se pueda escribir. Si ninguno
 * sirve, se cae a cache/, que esta dentro del proyecto pero cerrado por
 * .htaccess: peor sitio, pero el registro no se pierde.
 */
function inst_carpeta_logs(): string
{
    // Se resuelve una sola vez por peticion: la llaman el resumen y la linea
    // de cron, y una funcion que crea directorios no debe hacerlo dos veces.
    static $elegida = null;

    if ($elegida !== null) {
        return $elegida;
    }

    $raiz = inst_raiz();

    foreach ([3, 2, 1] as $niveles) {
        $padre = dirname($raiz, $niveles);

        // dirname() acaba topando con la raiz del sistema; ahi no se escribe.
        if ($padre === '/' || $padre === '' || $padre === $raiz) {
            continue;
        }

        $carpeta = $padre . '/logs';

        if (is_dir($carpeta) && is_writable($carpeta)) {
            return $elegida = $carpeta;
        }

        if (is_dir($padre) && is_writable($padre) && @mkdir($carpeta, 0755)) {
            return $elegida = $carpeta;
        }
    }

    return $elegida = $raiz . '/cache';
}

/**
 * Ruta del interprete de consola.
 *
 * PHP_BINARY no sirve: bajo Apache o php-fpm apunta al modulo, no al binario
 * de linea de comandos. PHP_BINDIR si apunta al directorio de la version de
 * PHP elegida en hPanel, que es donde vive el ejecutable que quiere el cron.
 */
function inst_php_cli(): string
{
    $candidato = PHP_BINDIR . '/php';

    return (PHP_BINDIR !== '' && @is_file($candidato)) ? $candidato : '/usr/bin/php';
}

/**
 * Linea de cron ya con las rutas reales de este servidor.
 */
function inst_linea_cron(): string
{
    return sprintf(
        '%s %s/cron/tareas.php >> %s/bitb-cron.log 2>&1',
        inst_php_cli(),
        inst_raiz(),
        inst_carpeta_logs()
    );
}

/**
 * Primera pasada de ingesta lanzada a mano desde el instalador, para no tener
 * que esperar a que el cron se despierte. Presupuesto corto: lo que no entre
 * en esta pasada lo recoge la siguiente, que para eso hay puntero.
 */
function inst_primera_ingesta(): array
{
    require_once inst_raiz() . '/cron/ingesta.php';

    if (!function_exists('ingesta_lote')) {
        throw new RuntimeException('cron/ingesta.php no define ingesta_lote().');
    }

    return ingesta_lote(microtime(true) + INST_PRESUPUESTO_INGESTA);
}

/**
 * El instalador se borra a si mismo.
 *
 * Si el borrado falla por permisos, quien llama tiene que decirlo: un
 * instalador que sigue en el servidor no es un detalle menor, aunque este
 * inerte mientras exista config/config.php.
 */
function inst_autodestruir(): bool
{
    inst_bloqueo_soltar();

    $instalador = inst_raiz() . '/instalar.php';

    if (!is_file($instalador)) {
        return true;
    }

    return @unlink($instalador);
}
