<?php
/**
 * Envio del boletin: la edicion cerrada llega a quien la pidio.
 *
 * Se ejecuta desde cron/tareas.php o por linea de comandos:
 *   php cron/enviar.php
 *
 * Por lotes y con puntero, como todo lo demas del proyecto, pero aqui el
 * motivo es distinto y mas serio: un buzon de alojamiento compartido tiene un
 * limite de correos por hora, y pasarselo no devuelve un error amable, hace
 * que el proveedor bloquee el buzon. Asi que se manda un punado por pasada y
 * se retoma en la siguiente.
 *
 * Lo que no se hace, a proposito:
 *
 *   - No se abre con cada envio una conexion nueva por destinatario ni se
 *     manda uno con todos en copia oculta. Lo primero es lento y lo segundo
 *     convierte un boletin en una filtracion de la lista entera.
 *   - No se cuenta quien abre ni quien pulsa. No hace falta para escribir un
 *     radar semanal, y un pixel de seguimiento es exactamente lo que este
 *     proyecto dice no hacer.
 *   - Y no se reintenta un fallo permanente: se marca el rebote y se sigue.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/correo.php';
require_once dirname(__DIR__) . '/lib/lista.php';
require_once dirname(__DIR__) . '/lib/smtp.php';
require_once dirname(__DIR__) . '/lib/envio.php';
require_once dirname(__DIR__) . '/cron/publicar.php';

/**
 * Una pasada de envio.
 *
 * @param float $limite Marca de tiempo a partir de la cual no se empieza otro.
 */
function enviar_lote(float $limite): array
{
    $resumen = ['estado' => 'nada que enviar', 'edicion' => 0, 'enviados' => 0, 'fallos' => 0];

    if ((string) ajuste('envio_automatico', '1') !== '1') {
        $resumen['estado'] = 'desactivado';

        return $resumen;
    }

    $conf = correo_conf();

    if (!correo_configurado($conf) || $conf['proveedor'] !== 'propio') {
        $resumen['estado'] = 'sin buzon';

        return $resumen;
    }

    $edicion = enviar_edicion_pendiente();

    if ($edicion === null) {
        return $resumen;
    }

    $resumen['edicion'] = (int) $edicion['numero'];
    $bits    = publicar_bits((int) $edicion['id']);

    if (!$bits) {
        // Una edicion sin bits no se manda: se da por enviada para que no
        // bloquee a la siguiente.
        enviar_marcar_enviada((int) $edicion['id']);
        $resumen['estado'] = 'edicion vacia';

        return $resumen;
    }

    $base   = rtrim((string) config_opcional('sitio.url', ''), '/');
    $tanda  = max(1, (int) ajuste('envio_por_pasada', '25'));
    $asunto = envio_asunto($edicion, $bits);

    foreach (enviar_pendientes((int) $edicion['id'], $tanda) as $quien) {
        if (microtime(true) >= $limite) {
            break;
        }

        $baja = $base . '/api/baja.php?s=' . (int) $quien['id'] . '&t=' . lista_firma_baja((int) $quien['id']);

        $envio = smtp_enviar($conf, [
            'para'   => (string) $quien['email'],
            'asunto' => $asunto,
            'texto'  => envio_texto($edicion, $bits, $base, $baja),
            'html'   => envio_html($edicion, $bits, $base, $baja),
            'cabeceras' => [
                // Con esto, el cliente de correo ensena su propio boton de baja
                // arriba del mensaje. Quien se quiere ir lo usa en vez de
                // marcar el correo como spam, que es lo que hunde un dominio.
                'List-Unsubscribe: <' . $baja . '>',
                'List-Unsubscribe-Post: List-Unsubscribe=One-Click',
                'List-Id: Bit & Breakfast <boletin.' . parse_url($base, PHP_URL_HOST) . '>',
                'Precedence: bulk',
            ],
        ]);

        if ($envio['ok']) {
            enviar_anotar((int) $edicion['id'], (int) $quien['id'], true);
            $resumen['enviados']++;
        } else {
            enviar_anotar((int) $edicion['id'], (int) $quien['id'], false);
            $resumen['fallos']++;
            error_log('Bit & Breakfast, envio a suscriptor ' . $quien['id'] . ': ' . $envio['mensaje']);
        }
    }

    // La edicion se da por enviada cuando no queda nadie a quien mandarsela.
    if (!enviar_pendientes((int) $edicion['id'], 1)) {
        enviar_marcar_enviada((int) $edicion['id']);
        $resumen['estado'] = 'edicion enviada';
    } else {
        $resumen['estado'] = $resumen['enviados'] > 0 ? 'enviando' : 'sin avanzar';
    }

    return $resumen;
}

/**
 * La edicion cerrada mas antigua que todavia no se ha mandado.
 *
 * La mas antigua y no la mas reciente: si se acumulan dos, el lector las
 * recibe en el orden en que se escribieron.
 */
function enviar_edicion_pendiente(): ?array
{
    $sql = "SELECT id, numero, slug, titulo, intro, fecha_prevista, fecha_envio, estado
              FROM ediciones
             WHERE estado = 'cerrada' AND fecha_envio IS NULL
             ORDER BY numero ASC
             LIMIT 1";

    $fila = bd()->query($sql)->fetch();

    return $fila === false ? null : $fila;
}

/**
 * A quien le falta esta edicion.
 *
 * Confirmados, sin baja, sin demasiados rebotes y sin fila en envios para esta
 * edicion. Esa tabla es la que permite mandar por tandas: sin ella, la pasada
 * siguiente volveria a empezar por el primero de la lista.
 */
function enviar_pendientes(int $edicion_id, int $cuantos): array
{
    $sql = "SELECT s.id, s.email
              FROM suscriptores s
              LEFT JOIN envios en ON en.suscriptor_id = s.id AND en.edicion_id = ?
             WHERE s.estado = 'confirmado'
               AND s.rebotes < 3
               AND en.suscriptor_id IS NULL
             ORDER BY s.id ASC
             LIMIT ?";

    $st = bd()->prepare($sql);
    $st->bindValue(1, $edicion_id, PDO::PARAM_INT);
    $st->bindValue(2, $cuantos, PDO::PARAM_INT);
    $st->execute();

    return $st->fetchAll();
}

/**
 * Anota el resultado de un envio.
 *
 * La fila en envios se escribe tanto si salio como si no: si no, un fallo
 * permanente -una direccion que ya no existe- se reintentaria en cada pasada y
 * el envio de esa edicion no terminaria nunca.
 *
 * El rebote se cuenta pero no descarta a la primera: un buzon lleno un martes
 * no significa que la direccion este muerta. A los tres, se deja de intentar.
 */
function enviar_anotar(int $edicion_id, int $suscriptor_id, bool $ok): void
{
    $sql = 'INSERT INTO envios (edicion_id, suscriptor_id, resultado)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE resultado = VALUES(resultado), enviado = UTC_TIMESTAMP()';

    bd()->prepare($sql)->execute([$edicion_id, $suscriptor_id, $ok ? 'ok' : 'fallo']);

    $sql = $ok
        ? 'UPDATE suscriptores SET ultimo_envio = UTC_TIMESTAMP(), rebotes = 0 WHERE id = ?'
        : 'UPDATE suscriptores SET ultimo_envio = UTC_TIMESTAMP(), rebotes = rebotes + 1 WHERE id = ?';

    bd()->prepare($sql)->execute([$suscriptor_id]);
}

/**
 * Da la edicion por enviada.
 */
function enviar_marcar_enviada(int $edicion_id): void
{
    bd()->prepare("UPDATE ediciones SET estado = 'enviada', fecha_envio = UTC_TIMESTAMP() WHERE id = ?")
        ->execute([$edicion_id]);

    // La web tiene que reflejarlo: el archivo distingue una edicion enviada de
    // una que solo esta publicada.
    ajuste_guardar('publicar_firma', '');
}

// Por linea de comandos se ejecuta directamente.
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {
    date_default_timezone_set('UTC');

    $resultado = enviar_lote(microtime(true) + 60);

    echo json_encode($resultado, JSON_UNESCAPED_UNICODE), "\n";
}
