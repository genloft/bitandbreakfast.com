<?php
/**
 * Instalador web de Bit & Breakfast.
 *
 * Pide los datos de la base de datos, importa el esquema y las semillas,
 * genera los secretos, escribe config/config.php y crea el usuario del panel.
 *
 * SEGURIDAD. Este fichero vive en el repositorio, asi que cada despliegue por
 * Git lo vuelve a dejar en el servidor. Para que eso no sea una puerta
 * abierta, no se ejecuta nunca por las buenas:
 *
 *   1. Exige que exista el fichero  config/INSTALAR_PERMITIDO
 *      Lo creas tu a mano en el Administrador de archivos cuando vas a
 *      instalar, y el propio instalador lo borra al terminar con exito.
 *   2. Si ya existe config/config.php, se niega a seguir.
 *
 * Sin ese fichero, la pagina no hace absolutamente nada: ni conecta, ni lee
 * formularios, ni cuenta que version de PHP corre el servidor.
 */

declare(strict_types=1);

const RAIZ            = __DIR__;
const FICHERO_PERMISO = RAIZ . '/config/INSTALAR_PERMITIDO';
const FICHERO_CONFIG  = RAIZ . '/config/config.php';

session_start();

// -----------------------------------------------------------------------------
// Cerrojos
// -----------------------------------------------------------------------------

$bloqueo = null;

if (!is_file(FICHERO_PERMISO)) {
    $bloqueo = 'permiso';
} elseif (is_file(FICHERO_CONFIG)) {
    $bloqueo = 'ya_instalado';
}

// -----------------------------------------------------------------------------
// Utilidades
// -----------------------------------------------------------------------------

function e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function entrada(string $campo, string $defecto = ''): string
{
    return trim((string) ($_POST[$campo] ?? $defecto));
}

/**
 * Parte un fichero .sql en sentencias sueltas.
 *
 * No vale con explode(';'): hay valores que llevan punto y coma dentro de las
 * comillas. Esto recorre el texto respetando cadenas y comentarios.
 */
function instalar_sentencias(string $sql): array
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

        // Comentario de linea: se descarta hasta el salto.
        if ($c === '-' && substr($sql, $i, 2) === '--') {
            $fin = strpos($sql, "\n", $i);
            $i   = ($fin === false) ? $largo : $fin;
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
function instalar_fichero_sql(PDO $pdo, string $ruta): int
{
    if (!is_readable($ruta)) {
        throw new RuntimeException('No se encuentra ' . basename($ruta) . '. ¿Se ha subido la carpeta sql/?');
    }

    $sentencias = instalar_sentencias((string) file_get_contents($ruta));

    foreach ($sentencias as $sentencia) {
        $pdo->exec($sentencia);
    }

    return count($sentencias);
}

/**
 * Genera el contenido de config/config.php.
 *
 * Los valores pasan por var_export, asi que una contrasena con comillas o
 * barras invertidas no rompe el fichero ni permite colar codigo.
 */
function instalar_config(array $datos): string
{
    // Cada valor se exporta con var_export antes de entrar en la plantilla:
    // asi una contrasena con comillas o barras invertidas ni rompe el fichero
    // ni permite colar codigo dentro de la configuracion.
    $fecha    = $datos['fecha'];
    $host     = var_export($datos['host'], true);
    $nombre   = var_export($datos['nombre'], true);
    $usuario  = var_export($datos['usuario'], true);
    $clave    = var_export($datos['clave'], true);
    $puerto   = var_export((int) $datos['puerto'], true);
    $dominio  = var_export($datos['dominio'], true);
    $url      = var_export('https://' . $datos['dominio'], true);
    $agente   = var_export(
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
 * Generado por instalar.php el {$fecha}.
 *
 * Este fichero NO esta en el repositorio y no se sobrescribe al desplegar.
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

    'presupuesto_cron' => 25,

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

// -----------------------------------------------------------------------------
// Comprobaciones del entorno
// -----------------------------------------------------------------------------

function instalar_requisitos(): array
{
    $requisitos = [];

    $requisitos[] = [
        'PHP 8.1 o superior',
        version_compare(PHP_VERSION, '8.1.0', '>='),
        'Version detectada: ' . PHP_VERSION,
    ];

    foreach (['pdo_mysql', 'curl', 'mbstring', 'simplexml', 'json'] as $extension) {
        $requisitos[] = [
            'Extension ' . $extension,
            extension_loaded($extension),
            extension_loaded($extension) ? 'disponible' : 'actívala en hPanel, Configuración PHP',
        ];
    }

    foreach (['config', 'cache', 'publico'] as $directorio) {
        $ruta = RAIZ . '/' . $directorio;
        $requisitos[] = [
            'Carpeta ' . $directorio . '/ con permiso de escritura',
            is_dir($ruta) && is_writable($ruta),
            is_dir($ruta) ? 'permisos actuales: ' . substr(sprintf('%o', fileperms($ruta)), -3) : 'no existe',
        ];
    }

    foreach (['esquema.sql', 'semilla_fuentes.sql', 'semilla_diccionario.sql'] as $fichero) {
        $requisitos[] = [
            'sql/' . $fichero,
            is_readable(RAIZ . '/sql/' . $fichero),
            is_readable(RAIZ . '/sql/' . $fichero) ? 'encontrado' : 'falta',
        ];
    }

    return $requisitos;
}

// -----------------------------------------------------------------------------
// Proceso de instalacion
// -----------------------------------------------------------------------------

$errores  = [];
$resultado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $bloqueo === null) {

    if (!hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''))) {
        $errores[] = 'La sesión ha caducado. Vuelve a cargar la página e inténtalo otra vez.';
    }

    $datos = [
        'host'     => entrada('host', 'localhost'),
        'nombre'   => entrada('nombre'),
        'usuario'  => entrada('usuario'),
        'clave'    => (string) ($_POST['clave'] ?? ''),
        'puerto'   => (int) (entrada('puerto', '3306') ?: 3306),
        'dominio'  => strtolower(entrada('dominio')),
        'panel_usuario' => entrada('panel_usuario'),
        'panel_clave'   => (string) ($_POST['panel_clave'] ?? ''),
        'fecha'    => gmdate('Y-m-d H:i') . ' UTC',
    ];

    if ($datos['nombre'] === '' || $datos['usuario'] === '') {
        $errores[] = 'El nombre de la base de datos y el usuario son obligatorios.';
    }
    if ($datos['dominio'] === '' || !preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $datos['dominio'])) {
        $errores[] = 'Escribe el dominio sin https:// y sin barra final, por ejemplo bitandbreakfast.com';
    }
    if ($datos['panel_usuario'] !== '' && strlen($datos['panel_clave']) < 12) {
        $errores[] = 'La contraseña del panel debe tener al menos 12 caracteres.';
    }

    if (!$errores) {
        try {
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

            $ejecutadas  = instalar_fichero_sql($pdo, RAIZ . '/sql/esquema.sql');
            $ejecutadas += instalar_fichero_sql($pdo, RAIZ . '/sql/semilla_fuentes.sql');
            $ejecutadas += instalar_fichero_sql($pdo, RAIZ . '/sql/semilla_diccionario.sql');

            // Secretos: 64 caracteres hexadecimales de origen criptografico.
            $datos['secreto_hmac'] = bin2hex(random_bytes(32));
            $datos['token_api']    = bin2hex(random_bytes(32));
            $datos['sal_hash']     = bin2hex(random_bytes(32));

            if (@file_put_contents(FICHERO_CONFIG, instalar_config($datos)) === false) {
                throw new RuntimeException('No se ha podido escribir config/config.php. Revisa los permisos de la carpeta config/.');
            }
            @chmod(FICHERO_CONFIG, 0600);

            if ($datos['panel_usuario'] !== '') {
                $sql = 'INSERT INTO usuarios (usuario, hash_clave, nombre, activo) VALUES (?, ?, ?, 1)
                        ON DUPLICATE KEY UPDATE hash_clave = VALUES(hash_clave), activo = 1';
                $pdo->prepare($sql)->execute([
                    $datos['panel_usuario'],
                    password_hash($datos['panel_clave'], PASSWORD_DEFAULT),
                    $datos['panel_usuario'],
                ]);
            }

            $resultado = [
                'sentencias'  => $ejecutadas,
                'fuentes'     => (int) $pdo->query('SELECT COUNT(*) FROM fuentes')->fetchColumn(),
                'terminos'    => (int) $pdo->query('SELECT COUNT(*) FROM diccionario')->fetchColumn(),
                'ajustes'     => (int) $pdo->query('SELECT COUNT(*) FROM ajustes')->fetchColumn(),
                'usuario'     => $datos['panel_usuario'],
                'token_api'   => $datos['token_api'],
                'cron'        => '/usr/bin/php ' . RAIZ . '/cron/tareas.php >> ' . dirname(RAIZ, 3) . '/logs/bitb-cron.log 2>&1',
            ];

            // El instalador se desarma solo: sin este fichero no vuelve a
            // ejecutarse aunque siga en el servidor tras el proximo despliegue.
            @unlink(FICHERO_PERMISO);

        } catch (PDOException $ex) {
            $errores[] = 'Error de base de datos: ' . $ex->getMessage();
        } catch (Throwable $ex) {
            $errores[] = $ex->getMessage();
        }
    }
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

header('X-Robots-Tag: noindex, nofollow');
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Instalador de Bit &amp; Breakfast</title>
<style>
  :root { color-scheme: light; }
  body { margin: 0; padding: 2rem 1rem; background: #f6f5f2; color: #1b1b1a;
         font: 16px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif; }
  main { max-width: 44rem; margin: 0 auto; background: #fff; padding: 2rem;
         border: 1px solid #e0ddd6; border-radius: 6px; }
  h1 { margin: 0 0 .25rem; font-size: 1.5rem; }
  h2 { font-size: 1.1rem; margin: 2rem 0 .75rem; }
  p.sub { margin: 0 0 2rem; color: #6b675e; }
  label { display: block; margin: 1rem 0 .25rem; font-weight: 600; font-size: .9rem; }
  input { width: 100%; padding: .55rem .7rem; border: 1px solid #c9c5ba; border-radius: 4px;
          font: inherit; box-sizing: border-box; }
  small { color: #6b675e; display: block; margin-top: .25rem; }
  button { margin-top: 1.75rem; padding: .7rem 1.4rem; font: inherit; font-weight: 600;
           background: #1b1b1a; color: #fff; border: 0; border-radius: 4px; cursor: pointer; }
  table { width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: .9rem; }
  td { padding: .4rem .5rem; border-bottom: 1px solid #eeebe4; vertical-align: top; }
  td:first-child { width: 1.5rem; }
  .ok { color: #1a7f37; } .mal { color: #b42318; }
  .aviso { background: #fff6e5; border-left: 3px solid #d98a00; padding: 1rem; margin: 1rem 0; }
  .error { background: #fdeceb; border-left: 3px solid #b42318; padding: 1rem; margin: 1rem 0; }
  .bien  { background: #eaf6ed; border-left: 3px solid #1a7f37; padding: 1rem; margin: 1rem 0; }
  code, pre { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .85rem; }
  pre { background: #f6f5f2; padding: .8rem; border-radius: 4px; overflow-x: auto; }
</style>
</head>
<body>
<main>

<h1>Instalador de Bit &amp; Breakfast</h1>
<p class="sub">Crea las tablas, carga las 59 fuentes y el diccionario, y escribe la configuración.</p>

<?php if ($bloqueo === 'permiso'): ?>

  <div class="aviso">
    <strong>El instalador está desarmado.</strong>
    <p>Para poder ejecutarlo, crea un fichero vacío llamado
    <code>INSTALAR_PERMITIDO</code> dentro de la carpeta <code>config/</code>
    (Administrador de archivos de hPanel → carpeta <code>config</code> →
    Nuevo archivo). Después recarga esta página.</p>
    <p>El instalador borra ese fichero solo, en cuanto termina. Así, aunque el
    despliegue por Git vuelva a dejar <code>instalar.php</code> en el servidor,
    nadie puede ejecutarlo.</p>
  </div>

<?php elseif ($bloqueo === 'ya_instalado'): ?>

  <div class="aviso">
    <strong>Ya está instalado.</strong>
    <p>Existe <code>config/config.php</code>, así que el instalador no hace nada
    para no machacar la configuración ni borrar datos.</p>
    <p>Si de verdad quieres reinstalar desde cero, borra a mano
    <code>config/config.php</code> y vuelve a crear
    <code>config/INSTALAR_PERMITIDO</code>. Ten en cuenta que el esquema empieza
    con <code>DROP TABLE</code>: perderás todo lo que haya en la base.</p>
  </div>

<?php elseif ($resultado !== null): ?>

  <div class="bien">
    <strong>Instalación terminada.</strong>
    <p><?= (int) $resultado['sentencias'] ?> sentencias ejecutadas,
       <?= (int) $resultado['fuentes'] ?> fuentes,
       <?= (int) $resultado['terminos'] ?> términos y
       <?= (int) $resultado['ajustes'] ?> ajustes.</p>
    <?php if ($resultado['usuario'] !== ''): ?>
      <p>Usuario del panel creado: <code><?= e($resultado['usuario']) ?></code></p>
    <?php endif; ?>
  </div>

  <h2>Lo que falta por hacer</h2>

  <p><strong>1. Crea la tarea programada.</strong> hPanel → Avanzado → Trabajos
  cron, cada hora, comando personalizado:</p>
  <pre><?= e($resultado['cron']) ?></pre>
  <small>Crea antes la carpeta <code>logs/</code> en la ruta que aparece en el
  comando. Si <code>/usr/bin/php</code> no fuese PHP 8.1, usa la ruta que
  ofrezca el desplegable de esa misma pantalla.</small>

  <p style="margin-top:1.5rem"><strong>2. Comprueba que los directorios
  privados están cerrados.</strong> Abre
  <code>/config/config.ejemplo.php</code> en el navegador: tiene que devolver
  403.</p>

  <p><strong>3. Token de la API</strong> (lo necesitarás en la fase 6 para la
  redacción asistida). Está guardado en <code>config/config.php</code>:</p>
  <pre><?= e($resultado['token_api']) ?></pre>

  <div class="aviso" style="margin-top:1.5rem">
    El fichero <code>config/INSTALAR_PERMITIDO</code> ya se ha borrado solo.
    El instalador queda inerte hasta que vuelvas a crearlo a mano.
  </div>

<?php else: ?>

  <?php foreach ($errores as $error): ?>
    <div class="error"><?= e($error) ?></div>
  <?php endforeach; ?>

  <h2>Comprobaciones del servidor</h2>
  <table>
    <?php $todo_ok = true; foreach (instalar_requisitos() as [$etiqueta, $cumple, $detalle]): ?>
      <?php $todo_ok = $todo_ok && $cumple; ?>
      <tr>
        <td class="<?= $cumple ? 'ok' : 'mal' ?>"><?= $cumple ? '✓' : '✗' ?></td>
        <td><?= e($etiqueta) ?></td>
        <td><small><?= e($detalle) ?></small></td>
      </tr>
    <?php endforeach; ?>
  </table>

  <?php if (!$todo_ok): ?>
    <div class="aviso">Arregla lo marcado en rojo antes de continuar. La
    instalación fallará a medias si falta una extensión o si
    <code>config/</code> no tiene permiso de escritura.</div>
  <?php endif; ?>

  <h2>Datos de la base de datos</h2>
  <p><small>Los que te dio hPanel al crear la base en Bases de datos → MySQL.
  El nombre y el usuario llevan el prefijo de tu cuenta, algo como
  <code>u123456789_bitb</code>.</small></p>

  <form method="post" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

    <label for="host">Servidor</label>
    <input id="host" name="host" value="<?= e(entrada('host', 'localhost')) ?>" required>
    <small>En Hostinger casi siempre es <code>localhost</code>.</small>

    <label for="nombre">Nombre de la base de datos</label>
    <input id="nombre" name="nombre" value="<?= e(entrada('nombre')) ?>" required>

    <label for="usuario">Usuario</label>
    <input id="usuario" name="usuario" value="<?= e(entrada('usuario')) ?>" required>

    <label for="clave">Contraseña</label>
    <input id="clave" name="clave" type="password">

    <label for="puerto">Puerto</label>
    <input id="puerto" name="puerto" value="<?= e(entrada('puerto', '3306')) ?>">

    <h2>Sitio</h2>

    <label for="dominio">Dominio</label>
    <input id="dominio" name="dominio" value="<?= e(entrada('dominio')) ?>"
           placeholder="bitandbreakfast.com" required>
    <small>Sin <code>https://</code> y sin barra final.</small>

    <h2>Usuario del panel <small style="display:inline;font-weight:400">(opcional, hace falta en la fase 3)</small></h2>

    <label for="panel_usuario">Usuario</label>
    <input id="panel_usuario" name="panel_usuario" value="<?= e(entrada('panel_usuario')) ?>">

    <label for="panel_clave">Contraseña</label>
    <input id="panel_clave" name="panel_clave" type="password">
    <small>Mínimo 12 caracteres. Se guarda con <code>password_hash</code>, nunca en claro.</small>

    <div class="aviso" style="margin-top:1.5rem">
      <strong>Ojo:</strong> el esquema empieza con <code>DROP TABLE IF EXISTS</code>.
      Si la base ya tiene datos de Bit &amp; Breakfast, <strong>se borran</strong>.
    </div>

    <button type="submit">Instalar</button>
  </form>

<?php endif; ?>

</main>
</body>
</html>
