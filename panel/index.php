<?php
/**
 * Panel de curacion.
 *
 * Un solo punto de entrada: todo el panel cuelga de index.php?p=... Asi hay
 * un unico sitio donde se comprueba la sesion y un unico sitio donde se
 * comprueba el testigo del formulario, en lugar de repetirlo en cada pagina y
 * olvidarlo en la septima.
 *
 * El flujo del curador es este y no tiene mas:
 *
 *   cola    - los racimos candidatos ordenados por puntuacion. Se descartan
 *             o se convierten en bit.
 *   bit     - se escribe el bit y se aprueba.
 *   edicion - los bits aprobados se ordenan y la edicion se cierra.
 *
 * Toda accion es un POST con testigo y termina en una redireccion, para que
 * recargar no repita nada.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/panel.php';
require_once dirname(__DIR__) . '/lib/bits.php';
require_once dirname(__DIR__) . '/lib/correo.php';
require_once dirname(__DIR__) . '/lib/traducir.php';
require_once __DIR__ . '/datos.php';

date_default_timezone_set('UTC');

panel_sesion();

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

$pagina  = (string) ($_GET['p'] ?? 'cola');
$usuario = panel_usuario();
$errores = [];

// -----------------------------------------------------------------------------
// Entrada y salida
// -----------------------------------------------------------------------------

if ($pagina === 'salir') {
    panel_salir();
    panel_ir('entrar');
}

if ($usuario === null) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pagina === 'entrar') {
        panel_penalizacion();

        if (!panel_csrf_valido()) {
            $errores[] = 'La sesión ha caducado. Vuelve a intentarlo.';
        } elseif (panel_entrar(trim((string) ($_POST['usuario'] ?? '')), (string) ($_POST['clave'] ?? ''))) {
            panel_ir('cola');
        } else {
            $errores[] = 'Usuario o contraseña incorrectos.';
        }
    }

    $vista = 'entrar';
    require dirname(__DIR__) . '/plantillas/panel/marco.php';
    exit;
}

// -----------------------------------------------------------------------------
// Acciones
// -----------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!panel_csrf_valido()) {
        panel_avisar('La sesión ha caducado y la acción no se ha ejecutado. Vuelve a intentarlo.', 'error');
        panel_ir($pagina === 'entrar' ? 'cola' : $pagina);
    }

    $accion = (string) ($_POST['accion'] ?? '');

    switch ($accion) {
        case 'descartar':
            $racimo_id = (int) ($_POST['racimo_id'] ?? 0);
            datos_descartar_racimo($racimo_id, (string) ($_POST['motivo'] ?? 'sin interés'));
            panel_avisar('Racimo descartado.');
            panel_ir('cola');
            // no continua

        case 'crear_bit':
            $racimo = datos_racimo((int) ($_POST['racimo_id'] ?? 0));

            if ($racimo === null) {
                panel_avisar('Ese racimo ya no existe.', 'error');
                panel_ir('cola');
            }

            $bit_id = datos_crear_bit($racimo, datos_items_racimo((int) $racimo['id']));
            panel_avisar('Bit creado en borrador. Escríbelo y apruébalo.');
            panel_ir('bit', ['id' => $bit_id]);
            // no continua

        case 'guardar_bit':
        case 'aprobar_bit':
            $bit_id = (int) ($_POST['id'] ?? 0);
            $bit    = datos_bit($bit_id);

            if ($bit === null) {
                panel_avisar('Ese bit ya no existe.', 'error');
                panel_ir('cola');
            }

            $nuevo = [
                'titular'   => trim((string) ($_POST['titular'] ?? '')),
                'cuerpo'    => trim((string) ($_POST['cuerpo'] ?? '')),
                'por_que'   => trim((string) ($_POST['por_que'] ?? '')),
                'categoria' => (string) ($_POST['categoria'] ?? ''),
                'madurez'   => (string) ($_POST['madurez'] ?? ''),
                'tipo'      => (string) ($_POST['tipo'] ?? ''),
            ];

            $fallos = bits_validar($nuevo);

            // Un borrador se guarda como este: lo que no se puede es aprobar
            // algo que no cumple el formato.
            if ($accion === 'aprobar_bit' && $fallos) {
                foreach ($fallos as $fallo) {
                    panel_avisar($fallo, 'error');
                }
                panel_avisar('El bit se ha guardado como borrador.', 'aviso');
                $nuevo['estado'] = 'borrador';
            } else {
                $nuevo['estado'] = $accion === 'aprobar_bit' ? 'aprobado' : (string) $bit['estado'];

                if ($accion === 'aprobar_bit') {
                    panel_avisar('Bit aprobado.');
                } else {
                    panel_avisar('Bit guardado.');
                }
            }

            datos_guardar_bit($bit_id, $nuevo);

            // Un bit aprobado entra solo en la edicion abierta: es lo unico
            // que se puede querer hacer con el.
            if ($nuevo['estado'] === 'aprobado' && $bit['edicion_id'] === null) {
                datos_asignar_bit($bit_id, (int) datos_edicion_abierta()['id']);
            }

            panel_ir('bit', ['id' => $bit_id]);
            // no continua

        case 'borrar_bit':
            datos_borrar_bit((int) ($_POST['id'] ?? 0));
            panel_avisar('Bit borrado. El racimo vuelve a la cola.');
            panel_ir('cola');
            // no continua

        case 'mover_bit':
            $edicion = datos_edicion_abierta();
            datos_mover_bit(
                (int) ($_POST['id'] ?? 0),
                (int) $edicion['id'],
                (string) ($_POST['direccion'] ?? '') === 'subir' ? 'subir' : 'bajar'
            );
            panel_ir('edicion');
            // no continua

        case 'sacar_bit':
            datos_asignar_bit((int) ($_POST['id'] ?? 0), null);
            panel_avisar('Bit fuera de la edición.');
            panel_ir('edicion');
            // no continua

        case 'meter_bit':
            datos_asignar_bit((int) ($_POST['id'] ?? 0), (int) datos_edicion_abierta()['id']);
            panel_avisar('Bit añadido a la edición.');
            panel_ir('edicion');
            // no continua

        case 'guardar_edicion':
            $edicion = datos_edicion_abierta();

            bd()->prepare('UPDATE ediciones SET titulo = ?, intro = ?, fecha_prevista = ? WHERE id = ?')
                ->execute([
                    texto_recortar(trim((string) ($_POST['titulo'] ?? '')), 190),
                    trim((string) ($_POST['intro'] ?? '')),
                    (string) ($_POST['fecha_prevista'] ?? $edicion['fecha_prevista']),
                    (int) $edicion['id'],
                ]);

            panel_avisar('Edición guardada.');
            panel_ir('edicion');
            // no continua

        case 'cerrar_edicion':
            $edicion = datos_edicion_abierta();
            $revision = bits_revisar_edicion(
                datos_bits_edicion((int) $edicion['id']),
                datos_conf_edicion()
            );

            if ($revision['errores']) {
                foreach ($revision['errores'] as $fallo) {
                    panel_avisar($fallo, 'error');
                }
                panel_ir('edicion');
            }

            datos_cerrar_edicion((int) $edicion['id']);
            panel_avisar('Edición cerrada. La siguiente se abre sola con el primer bit aprobado.');
            panel_ir('edicion');
            // no continua

        case 'guardar_correo':
            // La contrasena no pasa por aqui mas que de camino al fichero: ni
            // se registra, ni se guarda en sesion, ni se devuelve al formulario.
            $guardado = correo_guardar_buzon([
                'host'      => (string) ($_POST['host'] ?? ''),
                'puerto'    => (int) ($_POST['puerto'] ?? 465),
                'usuario'   => (string) ($_POST['usuario'] ?? ''),
                'clave'     => (string) ($_POST['clave'] ?? ''),
                'remitente' => (string) ($_POST['remitente'] ?? ''),
            ]);

            if ($guardado['ok']) {
                // La web se regenera para que aparezca el formulario de alta,
                // que hasta ahora decia que no estaba abierta.
                ajuste_guardar('publicar_firma', '');
                panel_avisar('Buzón guardado. Manda una prueba antes de fiarte.');
            } else {
                panel_avisar($guardado['mensaje'], 'error');
            }

            panel_ir('correo');
            // no continua

        case 'guardar_traductor':
            // La clave no pasa por aqui mas que de camino al fichero: ni se
            // registra, ni se guarda en sesion, ni vuelve al formulario.
            $guardado = traducir_guardar([
                'clave'      => (string) ($_POST['clave'] ?? ''),
                'limite_mes' => (int) ($_POST['limite_mes'] ?? 500000),
            ]);

            if ($guardado['ok']) {
                // Con traductor, lo que no esta en espanol deja de descartarse.
                // Sin el, vuelve a descartarse: es la misma decision al reves.
                ajuste_guardar('auto_solo_espanol', traducir_configurado() ? '0' : '1');
                panel_avisar($guardado['mensaje']);
            } else {
                panel_avisar($guardado['mensaje'], 'error');
            }

            panel_ir('correo');
            // no continua

        case 'guardar_aviso':
            $modo = (string) ($_POST['cron_aviso'] ?? 'siempre');

            // Lista blanca: de aqui sale el comportamiento del cron, y un valor
            // inventado lo dejaria en un estado que no contempla nadie.
            ajuste_guardar('cron_aviso', in_array($modo, ['siempre', 'cambios', 'no'], true) ? $modo : 'siempre');

            $correo_aviso = correo_normalizar(trim((string) ($_POST['cron_aviso_correo'] ?? '')));

            if ($correo_aviso !== '' && !correo_valido($correo_aviso)) {
                panel_avisar('Esa dirección para el aviso no es válida.', 'error');
                panel_ir('correo');
            }

            ajuste_guardar('cron_aviso_correo', $correo_aviso);
            panel_avisar('Aviso del cron guardado.');
            panel_ir('correo');
            // no continua

        case 'enviar_tanda':
            require_once dirname(__DIR__) . '/cron/enviar.php';

            $tanda = enviar_lote(microtime(true) + 45);

            panel_avisar(sprintf(
                'Edición %d: %d enviados, %d fallos (%s).',
                $tanda['edicion'],
                $tanda['enviados'],
                $tanda['fallos'],
                $tanda['estado']
            ), $tanda['fallos'] > 0 ? 'aviso' : 'bien');

            panel_ir('correo');
            // no continua

        case 'probar_correo':
            $prueba = correo_probar(trim((string) ($_POST['destino'] ?? '')));

            if ($prueba['ok']) {
                panel_avisar('Prueba enviada. Si no llega en un minuto, mira la carpeta de no deseados.');
            } else {
                panel_avisar($prueba['mensaje'], 'error');
            }

            panel_ir('correo');
            // no continua

        default:
            panel_avisar('Acción desconocida.', 'error');
            panel_ir('cola');
    }
}

// -----------------------------------------------------------------------------
// Paginas
// -----------------------------------------------------------------------------

switch ($pagina) {
    case 'bit':
        $bit = datos_bit((int) ($_GET['id'] ?? 0));

        if ($bit === null) {
            panel_avisar('Ese bit no existe.', 'error');
            panel_ir('cola');
        }

        $racimo = $bit['racimo_id'] !== null ? datos_racimo((int) $bit['racimo_id']) : null;
        $items  = $bit['racimo_id'] !== null ? datos_items_racimo((int) $bit['racimo_id']) : [];
        $vista  = 'bit';
        break;

    case 'edicion':
        $edicion  = datos_edicion_abierta();
        $bits     = datos_bits_edicion((int) $edicion['id']);
        $sueltos  = datos_bits_sueltos();
        $revision = bits_revisar_edicion($bits, datos_conf_edicion());
        $vista    = 'edicion';
        break;

    case 'correo':
        $buzon = correo_buzon();

        // La clave no llega a la plantilla: lo que no se pinta no se puede
        // filtrar en una captura de pantalla ni en el HTML de una cache.
        unset($buzon['clave']);

        $configurado = correo_configurado();
        $cuentas     = lista_cuentas();
        $envio       = panel_estado_envio();
        // Del traductor tampoco sale la clave a la plantilla. Lo que se
        // enseña es si esta puesto y cuanta cuota queda del mes.
        $traductor      = traducir_configurado();
        $traductor_plan = (string) traducir_conf()['plan'];
        $traductor_tope = (int) traducir_conf()['limite_mes'];
        $traductor_cuota = traducir_cuota();

        $aviso_modo   = (string) ajuste('cron_aviso', 'cambios');
        $aviso_correo = (string) ajuste('cron_aviso_correo', '');
        $vista       = 'correo';
        break;

    case 'entrar':
        // Ya hay sesion: no tiene sentido volver a pedirla.
        panel_ir('cola');
        // no continua

    default:
        $cola  = datos_cola();
        $vista = 'cola';
}

require dirname(__DIR__) . '/plantillas/panel/marco.php';
