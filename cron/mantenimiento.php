<?php
/**
 * Mantenimiento diario: lo que no hace falta mirar cada cinco minutos.
 *
 * Tres avisos, el mismo mecanismo para los tres: si /estadisticas.html o
 * /agentica.html llevan mas de su plazo fijo sin que una persona los
 * revise, o si /cumplimiento.html ha llegado a la fecha pendiente mas
 * proxima de su propia tabla, manda un aviso por el mismo buzon que ya usa
 * el cron para su propio parte.
 *
 * No intenta traer las cifras solas. La mayoria de las fuentes que cita esa
 * pagina -RateGain, IBM, AEPD, las encuestas de viajeros- son informes y
 * notas de prensa, no una API estable que se pueda leer sin vigilancia: un
 * cron que las raspara se romperia con el primer cambio de maquetacion de
 * cualquiera de ellas, y lo haria en silencio. INE y Eurostat si tienen una
 * API abierta y documentada -servicios.ine.es/wstempus y el API de
 * dissemination de Eurostat-, y ahi si cabria traer el numero solo; pero
 * antes hay que probar la integracion contra el servicio de verdad, y eso
 * no se puede hacer sin salida a esos dominios. Hasta entonces, lo unico
 * honesto que un cron puede automatizar aqui es recordarle a una persona que
 * mire, no fabricar el dato el mismo: mejor un aviso que se comprueba a mano
 * que un numero que se trae solo y nadie revisa.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/correo.php';
require_once dirname(__DIR__) . '/lib/smtp.php';
require_once dirname(__DIR__) . '/lib/cifras.php';
require_once dirname(__DIR__) . '/lib/cumplimiento.php';
require_once dirname(__DIR__) . '/lib/agentica.php';

/**
 * Punto de entrada que llama cron/tareas.php cada dia a partir de las 05:00
 * UTC. El $limite lo reciben todas las tareas por la misma firma, pero esta
 * no lo necesita: es una comprobacion de fechas, no un lote con puntero.
 *
 * @return array ['cifras_aviso' => bool, 'cumplimiento_aviso' => bool] Si se
 *               ha mandado cada aviso ahora.
 */
function mantenimiento_diario(float $limite): array
{
    return [
        'cifras_aviso'       => mantenimiento_avisar_cifras_caducadas(),
        'cumplimiento_aviso' => mantenimiento_avisar_cumplimiento_caducado(),
        'agentica_aviso'     => mantenimiento_avisar_agentica_caducada(),
    ];
}

/**
 * Manda el aviso de caducidad de Cifras si toca, y deja constancia de para
 * que revision se ha mandado.
 *
 * Sigue el mismo patron que tareas_avisar(): mismo buzon, mismo ajuste de
 * destino, y si algo falla se registra y el sitio sigue funcionando igual.
 * Que este aviso no salga no es un error del cron, es -como mucho- un aviso
 * que llega tarde.
 */
function mantenimiento_avisar_cifras_caducadas(): bool
{
    $revisado     = cifras_revisado();
    $ultimo_aviso = (string) ajuste('cifras_aviso_revisado', '');

    if (!cifras_caducadas($revisado, gmdate('Y-m-d'), $ultimo_aviso)) {
        return false;
    }

    $conf = correo_conf();

    if (!correo_configurado($conf) || $conf['proveedor'] !== 'propio') {
        // Sin buzon propio no hay a donde mandarlo. No es un error: el sitio
        // sigue funcionando igual sin este aviso, igual que sin el del cron.
        return false;
    }

    $destino = trim((string) ajuste('cron_aviso_correo', ''));
    $destino = $destino !== '' ? $destino : (string) $conf['usuario'];

    if (!correo_valido($destino)) {
        return false;
    }

    $dias = (int) floor((time() - (strtotime($revisado . ' UTC') ?: time())) / 86400);

    $envio = smtp_enviar($conf, [
        'para'   => $destino,
        'asunto' => 'Bit & Breakfast: las Cifras llevan ' . $dias . ' días sin revisar',
        'texto'  => "La página /estadisticas.html se revisó por última vez el $revisado.\n\n"
            . "Han pasado $dias días. Toca comprobar si INE, Eurostat, IBM, AEPD, RateGain "
            . "y el resto de fuentes citadas han publicado datos más recientes, y actualizar "
            . "\$grupos en plantillas/web/estadisticas.php -y la fecha en lib/cifras.php-.\n",
        'cabeceras' => [
            'Auto-Submitted: auto-generated',
            'Precedence: bulk',
        ],
    ]);

    if (!$envio['ok']) {
        error_log('Bit & Breakfast, aviso de Cifras caducadas: ' . $envio['mensaje']);

        return false;
    }

    ajuste_guardar('cifras_aviso_revisado', $revisado);

    return true;
}

/**
 * Manda el aviso de caducidad de /cumplimiento.html si toca, y deja
 * constancia de para que revision se ha mandado.
 *
 * Mismo patron exacto que mantenimiento_avisar_cifras_caducadas(), con una
 * diferencia: el limite no es un plazo fijo, es la fecha pendiente mas
 * proxima de cumplimiento_fechas() -calculada en cumplimiento_limite_revision(),
 * la misma funcion que usa la propia pagina para enseñar esa fecha-.
 */
function mantenimiento_avisar_cumplimiento_caducado(): bool
{
    $revisado = cumplimiento_revisado();
    $hoy      = gmdate('Y-m-d');
    $limite   = cumplimiento_limite_revision(cumplimiento_fechas(), $hoy, $revisado);

    $ultimo_aviso = (string) ajuste('cumplimiento_aviso_revisado', '');

    if (!cumplimiento_caducadas($limite, $hoy, $ultimo_aviso, $revisado)) {
        return false;
    }

    $conf = correo_conf();

    if (!correo_configurado($conf) || $conf['proveedor'] !== 'propio') {
        return false;
    }

    $destino = trim((string) ajuste('cron_aviso_correo', ''));
    $destino = $destino !== '' ? $destino : (string) $conf['usuario'];

    if (!correo_valido($destino)) {
        return false;
    }

    $envio = smtp_enviar($conf, [
        'para'   => $destino,
        'asunto' => 'Bit & Breakfast: /cumplimiento.html ha llegado a su fecha de revisión',
        'texto'  => "La página /cumplimiento.html se revisó por última vez el $revisado.\n\n"
            . "Su fecha de revisión era el $limite -la fecha pendiente más próxima de su propia "
            . "tabla-. Toca comprobar si esa norma ha cambiado de plazo, si ya se ha resuelto un "
            . "trámite en curso o si ha aparecido una norma nueva que añadir, y actualizar "
            . "cumplimiento_normas() y la fecha de revisión en lib/cumplimiento.php.\n",
        'cabeceras' => [
            'Auto-Submitted: auto-generated',
            'Precedence: bulk',
        ],
    ]);

    if (!$envio['ok']) {
        error_log('Bit & Breakfast, aviso de Cumplimiento caducado: ' . $envio['mensaje']);

        return false;
    }

    ajuste_guardar('cumplimiento_aviso_revisado', $revisado);

    return true;
}

/**
 * Manda el aviso de caducidad de /agentica.html si toca, y deja constancia
 * de para que revision se ha mandado.
 *
 * Mismo patron exacto que mantenimiento_avisar_cifras_caducadas(), con el
 * plazo mas corto de AGENTICA_CADUCIDAD_DIAS: los protocolos de reserva
 * agentica cambian de mes en mes.
 */
function mantenimiento_avisar_agentica_caducada(): bool
{
    $revisado     = agentica_revisado();
    $ultimo_aviso = (string) ajuste('agentica_aviso_revisado', '');

    if (!agentica_caducadas($revisado, gmdate('Y-m-d'), $ultimo_aviso)) {
        return false;
    }

    $conf = correo_conf();

    if (!correo_configurado($conf) || $conf['proveedor'] !== 'propio') {
        return false;
    }

    $destino = trim((string) ajuste('cron_aviso_correo', ''));
    $destino = $destino !== '' ? $destino : (string) $conf['usuario'];

    if (!correo_valido($destino)) {
        return false;
    }

    $dias = (int) floor((time() - (strtotime($revisado . ' UTC') ?: time())) / 86400);

    $envio = smtp_enviar($conf, [
        'para'   => $destino,
        'asunto' => 'Bit & Breakfast: /agentica.html lleva ' . $dias . ' días sin revisar',
        'texto'  => "La página /agentica.html se revisó por última vez el $revisado.\n\n"
            . "Han pasado $dias días. Toca comprobar si MCP, ACP, UCP o AP2 han cambiado de "
            . "alcance, si ha entrado un protocolo nuevo o si algún dato citado -la retirada de "
            . "Instant Checkout, el lanzamiento de UCP for Lodging- ha quedado desfasado, y "
            . "actualizar plantillas/web/agentica.php -y la fecha en lib/agentica.php-.\n",
        'cabeceras' => [
            'Auto-Submitted: auto-generated',
            'Precedence: bulk',
        ],
    ]);

    if (!$envio['ok']) {
        error_log('Bit & Breakfast, aviso de Agentica caducada: ' . $envio['mensaje']);

        return false;
    }

    ajuste_guardar('agentica_aviso_revisado', $revisado);

    return true;
}
