<?php
/**
 * Lo que necesitan tanto /estadisticas.html como el mantenimiento diario
 * para saber si esa pagina esta al dia.
 *
 * Vive fuera de la plantilla a proposito: cron/mantenimiento.php necesita
 * leer cuando se revisaron las cifras sin ejecutar la pagina entera, y asi
 * solo hay un sitio que tocar el dia que se actualicen los datos -este
 * fichero- ademas del array de plantillas/web/estadisticas.php.
 */

declare(strict_types=1);

/**
 * Pasados estos dias sin que una persona revise /estadisticas.html, el
 * mantenimiento diario avisa por correo.
 *
 * Ciento veinte son unos cuatro meses: ni tan corto que avise por una fuente
 * que todavia no ha publicado su informe anual, ni tan largo que una cifra
 * pueda llevar casi un año sin que nadie lo note -que es justo lo que esta
 * pagina promete que no pasa.
 */
const CIFRAS_CADUCIDAD_DIAS = 120;

/**
 * La fecha en que una persona reviso por ultima vez /estadisticas.html.
 *
 * Fija, escrita a mano, nunca gmdate(): esta pagina no sale de la base
 * propia y no se pone al dia sola. Se actualiza junto con el array $grupos
 * de plantillas/web/estadisticas.php, a la vez y por la misma persona.
 */
function cifras_revisado(): string
{
    return '2026-09-18';
}

/**
 * Hasta cuando "revisado el $revisado" sigue siendo una promesa vigente.
 *
 * Mismo umbral que decide si el mantenimiento avisa por correo
 * (CIFRAS_CADUCIDAD_DIAS), pero contado hacia delante: no es "cuanto lleva
 * caducado", es "cuando caduca". /estadisticas.html lo ensena para que quien
 * lee la pagina no tenga que confiar a ciegas en una fecha de revision sin
 * saber cuanto dura esa promesa.
 */
function cifras_limite_revision(string $revisado): string
{
    $revisado_ts = strtotime($revisado . ' UTC');

    if ($revisado_ts === false) {
        return $revisado;
    }

    return gmdate('Y-m-d', $revisado_ts + CIFRAS_CADUCIDAD_DIAS * 86400);
}

/**
 * Si toca avisar de que /estadisticas.html lleva demasiado sin revisarse.
 *
 * Pura -ni correo ni base de datos-, para poder probarla sin montar nada.
 *
 * $ultimo_aviso es la fecha de revision por la que ya se mando un aviso la
 * ultima vez. Si coincide con $revisado, el aviso de esa revision ya salio
 * y no hay que repetirlo cada dia mientras nadie actualice la pagina: sin
 * este freno, el mantenimiento -que corre una vez al dia- mandaria el mismo
 * correo a diario para siempre, y un aviso que se repite es indistinguible
 * de ruido.
 */
function cifras_caducadas(string $revisado, string $hoy, string $ultimo_aviso): bool
{
    $revisado_ts = strtotime($revisado . ' UTC');
    $hoy_ts      = strtotime($hoy . ' UTC');

    if ($revisado_ts === false || $hoy_ts === false) {
        return false;
    }

    $dias = (int) floor(($hoy_ts - $revisado_ts) / 86400);

    if ($dias < CIFRAS_CADUCIDAD_DIAS) {
        return false;
    }

    return $ultimo_aviso !== $revisado;
}
