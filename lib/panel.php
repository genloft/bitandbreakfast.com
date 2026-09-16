<?php
/**
 * Sesion, autenticacion y avisos del panel de curacion.
 *
 * El panel es la unica zona privada del sitio y la unica que escribe en la
 * base desde el navegador. Todo lo que tiene que ver con quien eres y con si
 * puedes estar aqui vive en este fichero; lo que se hace una vez dentro, en
 * panel/.
 *
 * No hay roles ni permisos: quien entra, cura. Para un boletin que escribe
 * una persona, cualquier cosa mas seria un adorno con mantenimiento.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

/** Segundos de inactividad tras los que la sesion caduca. */
const PANEL_VIDA_SESION = 28800;      // ocho horas

/** Intentos fallidos seguidos antes de empezar a cansar al que lo intenta. */
const PANEL_INTENTOS_LIBRES = 3;

/**
 * Arranca la sesion del panel. Cookie propia, distinta de la del instalador.
 */
function panel_sesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('bitb_panel');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => panel_es_https(),
        'path'     => '/',
    ]);
    session_start();

    // Sesion vieja: se cierra sola. Una pestaña olvidada en un portatil no
    // puede seguir abierta una semana despues.
    if (isset($_SESSION['visto']) && time() - (int) $_SESSION['visto'] > PANEL_VIDA_SESION) {
        panel_salir();
        session_start();
    }

    $_SESSION['visto'] = time();
}

/**
 * Detras del proxy de Hostinger la peticion llega en claro aunque el visitante
 * este en HTTPS, asi que hay que mirar tambien la cabecera reenviada.
 */
function panel_es_https(): bool
{
    return !empty($_SERVER['HTTPS'])
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

/**
 * El usuario de la sesion, o null si no ha entrado nadie.
 */
function panel_usuario(): ?array
{
    return isset($_SESSION['usuario_id'])
        ? ['id' => (int) $_SESSION['usuario_id'], 'usuario' => (string) $_SESSION['usuario']]
        : null;
}

/**
 * Comprueba usuario y contrasena y abre la sesion.
 *
 * Devuelve false sin decir por que: si distinguiera "ese usuario no existe"
 * de "esa contrasena no es", estaria regalando la mitad del trabajo a quien
 * prueba nombres.
 */
function panel_entrar(string $usuario, string $clave): bool
{
    $st = bd()->prepare('SELECT id, usuario, hash_clave FROM usuarios WHERE usuario = ? AND activo = 1');
    $st->execute([$usuario]);
    $fila = $st->fetch();

    // Se comprueba el hash aunque el usuario no exista, contra un hash de
    // mentira: si no, el tiempo de respuesta delataria que usuarios existen.
    $hash = $fila['hash_clave'] ?? '$2y$12$invalidoinvalidoinvalidoinvalidoinvalidoinvalidoinvalidoinvalido';

    if (!password_verify($clave, $hash) || !$fila) {
        $_SESSION['intentos'] = (int) ($_SESSION['intentos'] ?? 0) + 1;
        return false;
    }

    // Sesion nueva al entrar: el identificador con el que llego el visitante
    // no puede seguir siendo valido despues de autenticarse.
    session_regenerate_id(true);

    $_SESSION['usuario_id'] = (int) $fila['id'];
    $_SESSION['usuario']    = (string) $fila['usuario'];
    $_SESSION['intentos']   = 0;
    $_SESSION['visto']      = time();

    if (password_needs_rehash($fila['hash_clave'], PASSWORD_DEFAULT)) {
        bd()->prepare('UPDATE usuarios SET hash_clave = ? WHERE id = ?')
            ->execute([password_hash($clave, PASSWORD_DEFAULT), (int) $fila['id']]);
    }

    bd()->prepare('UPDATE usuarios SET ultimo_acceso = UTC_TIMESTAMP() WHERE id = ?')
        ->execute([(int) $fila['id']]);

    return true;
}

/**
 * Espera creciente tras varios fallos seguidos.
 *
 * No es un cerrojo, es un peaje: con un solo usuario y sin tabla de intentos,
 * hacer que el cuarto intento cueste un segundo y el decimo siete convierte
 * un ataque por diccionario en algo que no cabe en una tarde.
 */
function panel_penalizacion(): void
{
    $intentos = (int) ($_SESSION['intentos'] ?? 0);

    if ($intentos > PANEL_INTENTOS_LIBRES) {
        sleep(min(7, $intentos - PANEL_INTENTOS_LIBRES));
    }
}

function panel_salir(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }

    session_destroy();
}

// -----------------------------------------------------------------------------
// Testigo de formulario
// -----------------------------------------------------------------------------

function panel_csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf'];
}

function panel_csrf_valido(): bool
{
    return hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''));
}

// -----------------------------------------------------------------------------
// Avisos entre peticiones
//
// El panel trabaja con POST y redireccion, asi que el resultado de una accion
// tiene que sobrevivir a un salto: se guarda en la sesion y se consume al
// pintarlo.
// -----------------------------------------------------------------------------

function panel_avisar(string $texto, string $tipo = 'bien'): void
{
    $_SESSION['avisos'][] = ['texto' => $texto, 'tipo' => $tipo];
}

function panel_avisos(): array
{
    $avisos = $_SESSION['avisos'] ?? [];
    unset($_SESSION['avisos']);

    return $avisos;
}

/**
 * Redirige dentro del panel y corta la ejecucion.
 *
 * El destino se arma aqui y nunca sale de una variable de la peticion: una
 * redireccion que acepte destino de fuera es una redireccion abierta.
 */
function panel_ir(string $pagina, array $parametros = []): void
{
    $url = 'index.php?p=' . rawurlencode($pagina);

    foreach ($parametros as $clave => $valor) {
        $url .= '&' . rawurlencode((string) $clave) . '=' . rawurlencode((string) $valor);
    }

    header('Location: ' . $url, true, 303);
    exit;
}

function panel_e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
