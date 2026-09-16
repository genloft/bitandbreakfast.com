<?php
/**
 * Latidos: quien ha pasado por aqui y cuando.
 *
 * El sitio esta pensado para que el trabajo pesado lo haga el cron. Pero el
 * cron vive en el panel del alojamiento, fuera del repositorio, y puede estar
 * mal puesto sin que nadie se entere: la portada se queda congelada y desde
 * fuera no hay forma de distinguir "no hay noticias" de "no corre nada".
 *
 * Aqui esta lo que hace falta para notarlo: una marca en disco que el cron
 * toca cada vez que se despierta, y las cuentas para decidir si lleva
 * demasiado tiempo callado. Son funciones puras sobre marcas de tiempo,
 * porque quien decide -index.php- no puede permitirse ni una consulta.
 */

declare(strict_types=1);

/** Marca que deja el cron al pasar. */
const ESTADO_MARCA_CRON = '/cache/.cron';

/**
 * Deja constancia de que el cron ha corrido.
 *
 * Con arroba y sin devolver nada: que no se pueda escribir la marca es un
 * problema, pero jamas uno que deba tumbar la tarea que la escribe.
 */
function estado_latir(string $raiz): void
{
    $marca = $raiz . ESTADO_MARCA_CRON;

    if (!is_file($marca)) {
        @file_put_contents($marca, "El cron toca este fichero al pasar.\n");
        return;
    }

    @touch($marca);
}

/**
 * Cuando paso el cron por ultima vez, en segundos desde epoch. 0 si nunca.
 */
function estado_ultimo_cron(string $raiz): int
{
    return estado_marca($raiz . ESTADO_MARCA_CRON);
}

/**
 * Fecha de modificacion de una marca, o 0 si no existe.
 */
function estado_marca(string $ruta): int
{
    return is_file($ruta) ? (int) @filemtime($ruta) : 0;
}

/**
 * ¿Lleva el cron demasiado tiempo sin dar senales?
 *
 * Que no haya marca cuenta como silencio: o el cron no se ha configurado
 * nunca, o esta configurado y no llega a ejecutarse. Para lo que se decide
 * con esto -si la web tira del carro ella sola- las dos cosas son iguales.
 */
function estado_cron_callado(int $ultimo, int $ahora, int $silencio): bool
{
    if ($ultimo <= 0) {
        return true;
    }

    // Una marca con fecha futura es un reloj mal puesto, no un cron vivo.
    return $ahora - $ultimo >= $silencio || $ultimo > $ahora + $silencio;
}

/**
 * Antiguedad en segundos de una marca, o null si no existe.
 */
function estado_edad(int $marca, int $ahora): ?int
{
    return $marca > 0 ? max(0, $ahora - $marca) : null;
}

/**
 * La antiguedad en palabras, para que un vistazo baste.
 */
function estado_edad_texto(?int $segundos): string
{
    if ($segundos === null) {
        return 'nunca';
    }

    if ($segundos < 120) {
        return 'hace ' . $segundos . ' s';
    }

    if ($segundos < 7200) {
        return 'hace ' . intdiv($segundos, 60) . ' min';
    }

    if ($segundos < 172800) {
        return 'hace ' . intdiv($segundos, 3600) . ' h';
    }

    return 'hace ' . intdiv($segundos, 86400) . ' días';
}
