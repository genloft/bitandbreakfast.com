<?php
/**
 * Lo que necesita tanto /agentica.html como el mantenimiento diario para
 * saber si esa pagina esta al dia.
 *
 * Mismo patron que lib/cifras.php -la fecha de revision vive fuera de la
 * plantilla para que cron/mantenimiento.php pueda leerla sin ejecutar la
 * pagina entera-, con una diferencia: el plazo es mas corto. Los protocolos
 * de reserva agentica -MCP, ACP, UCP, AP2- cambian de mes en mes, no de
 * trimestre en trimestre como una encuesta del INE, asi que noventa dias es
 * el limite, no ciento veinte.
 */

declare(strict_types=1);

/**
 * Pasados estos dias sin que una persona revise /agentica.html, el
 * mantenimiento diario avisa por correo.
 */
const AGENTICA_CADUCIDAD_DIAS = 90;

/**
 * La fecha en que una persona reviso por ultima vez /agentica.html.
 *
 * Fija, escrita a mano, nunca gmdate(): igual que cifras_revisado(), esta
 * pagina no se pone al dia sola.
 */
function agentica_revisado(): string
{
    return '2026-09-20';
}

/**
 * Hasta cuando "revisado el $revisado" sigue siendo una promesa vigente.
 * Misma logica que cifras_limite_revision(), con AGENTICA_CADUCIDAD_DIAS.
 */
function agentica_limite_revision(string $revisado): string
{
    $revisado_ts = strtotime($revisado . ' UTC');

    if ($revisado_ts === false) {
        return $revisado;
    }

    return gmdate('Y-m-d', $revisado_ts + AGENTICA_CADUCIDAD_DIAS * 86400);
}

/**
 * Si toca avisar de que /agentica.html lleva demasiado sin revisarse.
 * Misma logica que cifras_caducadas(), con AGENTICA_CADUCIDAD_DIAS.
 */
function agentica_caducadas(string $revisado, string $hoy, string $ultimo_aviso): bool
{
    $revisado_ts = strtotime($revisado . ' UTC');
    $hoy_ts      = strtotime($hoy . ' UTC');

    if ($revisado_ts === false || $hoy_ts === false) {
        return false;
    }

    $dias = (int) floor(($hoy_ts - $revisado_ts) / 86400);

    if ($dias < AGENTICA_CADUCIDAD_DIAS) {
        return false;
    }

    return $ultimo_aviso !== $revisado;
}
