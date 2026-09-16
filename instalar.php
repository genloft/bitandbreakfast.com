<?php
/**
 * Instalador web de Bit & Breakfast.
 *
 * Se arma solo. Mientras no exista config/config.php el sitio no esta
 * instalado, el .htaccess manda cualquier peticion aqui y esta pagina pide los
 * datos de la base, importa el esquema y las semillas, genera los secretos,
 * escribe la configuracion y crea el usuario del panel.
 *
 * SEGURIDAD. Este fichero vive en el repositorio, asi que cada despliegue por
 * Git lo vuelve a dejar en el servidor. Tres cerrojos evitan que eso sea una
 * puerta abierta:
 *
 *   1. Con config/config.php escrito, el instalador no hace nada. Ni conecta,
 *      ni lee formularios, ni cuenta que corre el servidor: se borra solo la
 *      primera vez que alguien lo abre.
 *   2. Mientras no lo hay, quien envia el formulario primero se queda con un
 *      testigo aleatorio. Otro navegador que llegue despues no puede instalar
 *      encima.
 *   3. Al terminar, el instalador se borra a si mismo.
 *
 * Lo que esos cerrojos NO cubren es la ventana entre el despliegue y la
 * instalacion: durante esos minutos, cualquiera que abra el dominio ve el
 * formulario. Instala inmediatamente despues de desplegar, o cierra el sitio
 * por IP con el bloque comentado que hay en .htaccess.
 *
 * El flujo, en tres pantallas: comprobaciones y formulario, resumen con la
 * linea de cron, y despedida despues de borrarse.
 */

declare(strict_types=1);

require_once __DIR__ . '/lib/instalacion.php';

session_name('bitb_instalador');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    // Tras el proxy de Hostinger la peticion llega en claro al PHP aunque el
    // visitante este en HTTPS; sin mirar la cabecera reenviada, la cookie de
    // sesion saldria sin Secure en un sitio que si lo es.
    'secure'   => !empty($_SERVER['HTTPS'])
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
]);
session_start();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// -----------------------------------------------------------------------------
// Utilidades
// -----------------------------------------------------------------------------

/**
 * Escapa para HTML. Lleva prefijo como el resto del instalador: una funcion
 * global llamada e() colisionaria con las plantillas de la web.
 */
function inst_e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function inst_campo(string $nombre, string $defecto = ''): string
{
    return trim((string) ($_POST[$nombre] ?? $defecto));
}

function inst_csrf_valido(): bool
{
    return hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''));
}

/**
 * Dominio sugerido a partir de la cabecera Host, sin puerto y sin caracteres
 * raros. Solo rellena la casilla: al enviar se valida igual.
 */
function inst_dominio_sugerido(): string
{
    $host = explode(':', (string) ($_SERVER['HTTP_HOST'] ?? ''))[0];

    return (string) preg_replace('/[^a-z0-9.-]/', '', strtolower($host));
}

// -----------------------------------------------------------------------------
// Estado de la peticion
// -----------------------------------------------------------------------------

$errores   = [];
$avisos    = [];
$resultado = $_SESSION['inst_resultado'] ?? null;
$ingesta   = $_SESSION['inst_ingesta']   ?? null;
$testigo   = (string) ($_SESSION['inst_testigo'] ?? '');
$bloqueo   = inst_bloqueo_estado($testigo);
$borrado   = false;
$token     = '';

// Valores del formulario, resueltos aqui para que la vista no tenga que
// mirar $_POST. La contrasena nunca vuelve a la pagina.
$valores = [
    'host'          => inst_campo('host', 'localhost'),
    'nombre'        => inst_campo('nombre'),
    'usuario'       => inst_campo('usuario'),
    'clave'         => (string) ($_POST['clave'] ?? ''),
    'puerto'        => (int) (inst_campo('puerto', '3306') ?: 3306),
    'dominio'       => strtolower(inst_campo('dominio', inst_dominio_sugerido())),
    'panel_usuario' => inst_campo('panel_usuario'),
    'panel_clave'   => (string) ($_POST['panel_clave'] ?? ''),
];

$accion = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string) ($_POST['accion'] ?? '') : '';

if ($accion !== '' && !inst_csrf_valido()) {
    $errores[] = 'La sesión ha caducado. Vuelve a cargar la página e inténtalo otra vez.';
    $accion    = '';
}

// -----------------------------------------------------------------------------
// Flujo
// -----------------------------------------------------------------------------

if ($resultado !== null) {

    // Ya instalado en esta misma sesion: quedan la puesta en marcha y el
    // borrado del instalador.
    $estado = 'listo';
    $token  = inst_token_api();

    // Renovar el cerrojo mientras esta pantalla siga abierta: asi nadie mas
    // puede llegar y borrar el instalador por debajo.
    if ($testigo !== '') {
        inst_bloqueo_tomar($testigo);
    }

    if ($accion === 'ingesta') {
        @set_time_limit(INST_PRESUPUESTO_INGESTA * 5);
        try {
            $ingesta = inst_primera_ingesta();
            $_SESSION['inst_ingesta'] = $ingesta;
        } catch (Throwable $ex) {
            $errores[] = 'La primera ingesta ha fallado: ' . $ex->getMessage();
        }
    }

    if ($accion === 'terminar') {
        $borrado = inst_autodestruir();
        $estado  = 'terminado';

        // La sesion ya no pinta nada: la configuracion esta en su sitio.
        $dominio  = (string) $resultado['dominio'];
        $_SESSION = [];
        session_destroy();
    }

} elseif (inst_instalado()) {

    // Hay configuracion y esta no es la sesion que la escribio: el instalador
    // sobra. Se borra ahora, que es justo lo que hay que hacer cuando un
    // despliegue por Git lo devuelve al servidor.
    //
    // Con un cerrojo ajeno todavia vivo no se toca nada: significa que otra
    // pestaña acaba de instalar y sigue en la pantalla de puesta en marcha.
    // Se limpiara cuando ese cerrojo caduque.
    $estado  = 'ya_instalado';
    $borrado = ($bloqueo === 'ajeno') ? false : inst_autodestruir();

} elseif ($bloqueo === 'ajeno') {

    $estado = 'ocupado';

} elseif ($accion === 'instalar') {

    $estado  = 'formulario';
    $errores = array_merge($errores, inst_validar($valores));

    if (!$errores) {
        // El cerrojo se toma al enviar, no al pintar el formulario: si se
        // tomara en cada GET, el primer rastreador que pasara por aqui
        // dejaria al dueño del sitio fuera media hora.
        if ($testigo === '') {
            $testigo = bin2hex(random_bytes(16));
            $_SESSION['inst_testigo'] = $testigo;
        }
        inst_bloqueo_tomar($testigo);

        try {
            $_SESSION['inst_resultado'] = inst_instalar($valores);

            // Patron POST-redirect-GET: recargar la pantalla de resumen no
            // puede volver a lanzar un esquema que empieza con DROP TABLE.
            // SCRIPT_NAME lo pone el servidor; PHP_SELF arrastra lo que venga
            // detras en la URL y no se puede devolver sin mirar.
            header('Location: ' . (string) ($_SERVER['SCRIPT_NAME'] ?? '/instalar.php'), true, 303);
            exit;
        } catch (PDOException $ex) {
            $errores[] = 'Error de base de datos: ' . $ex->getMessage();
            inst_deshacer_config();
        } catch (Throwable $ex) {
            $errores[] = $ex->getMessage();
            inst_deshacer_config();
        }
    }

} else {

    $estado = 'formulario';
}

// -----------------------------------------------------------------------------
// Datos para la vista
// -----------------------------------------------------------------------------

$requisitos = $estado === 'formulario' ? inst_requisitos() : [];
$minutos    = $estado === 'ocupado'    ? inst_bloqueo_minutos() : 0;
$dominio    = $dominio ?? (string) ($resultado['dominio'] ?? '');
$csrf       = (string) ($_SESSION['csrf'] ?? '');

if ($estado === 'listo') {
    if (!is_dir((string) ($resultado['logs'] ?? ''))) {
        $avisos[] = 'No he podido crear la carpeta ' . $resultado['logs']
            . '. Créala desde el Administrador de archivos antes de guardar la tarea cron, '
            . 'o el registro no se escribirá.';
    }

    if (!($resultado['portada'] ?? true)) {
        $avisos[] = 'No he podido escribir publico/index.html. La raíz del dominio no '
            . 'contestará hasta que se publique la primera edición o arregles los permisos '
            . 'de la carpeta publico/.';
    }

    if ($token === '') {
        $avisos[] = 'No he podido releer config/config.php para enseñarte el token de la API. '
            . 'Está dentro de ese fichero, en la clave secretos.token_api.';
    }
}

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

require __DIR__ . '/plantillas/instalador.php';
