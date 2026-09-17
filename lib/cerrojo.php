<?php
/**
 * Un cerrojo de fichero para que dos pasadas del cron no se pisen.
 *
 * Con el cron cada hora esto no hacia falta: ninguna pasada duraba sesenta
 * minutos. Con el cron cada cinco, si: basta una fuente lenta y un lote gordo
 * para que una pasada siga viva cuando arranca la siguiente, y entonces las
 * dos leen el mismo puntero, descargan las mismas fuentes y escriben los
 * mismos items. El trabajo no se duplica -los indices unicos lo impiden-,
 * pero el tiempo de CPU si, y en un alojamiento compartido eso se paga.
 *
 * Es un flock() sobre un fichero en cache/. Se eligio flock y no una marca de
 * tiempo en la base de datos porque el sistema operativo suelta el cerrojo
 * solo cuando el proceso muere, sea como sea que muera. Una marca en disco
 * escrita a mano se queda puesta para siempre si el proceso se cae en medio, y
 * entonces el cron deja de funcionar hasta que alguien lo note.
 */

declare(strict_types=1);

/**
 * Coge el cerrojo. Devuelve el recurso, o null si ya lo tiene otro.
 *
 * El recurso hay que guardarlo en una variable que viva toda la ejecucion: si
 * se recoge la basura, PHP cierra el fichero y suelta el cerrojo.
 *
 * @return resource|null
 */
function cerrojo_coger(string $raiz, string $nombre = 'cron')
{
    $ruta = $raiz . '/cache/.' . $nombre . '.lock';
    $fh   = @fopen($ruta, 'c');

    if ($fh === false) {
        // Sin poder abrir el fichero no hay cerrojo posible. Se deja pasar: es
        // peor no rastrear nunca que rastrear dos veces.
        return null;
    }

    if (!flock($fh, LOCK_EX | LOCK_NB)) {
        fclose($fh);

        return null;
    }

    @ftruncate($fh, 0);
    @fwrite($fh, (string) getmypid() . ' ' . gmdate('Y-m-d H:i:s') . PHP_EOL);
    @fflush($fh);

    return $fh;
}

/**
 * Suelta el cerrojo. No es imprescindible llamarla -el sistema lo suelta al
 * morir el proceso-, pero deja el fichero limpio y la intencion escrita.
 *
 * @param resource|null $fh
 */
function cerrojo_soltar($fh): void
{
    if (!is_resource($fh)) {
        return;
    }

    @flock($fh, LOCK_UN);
    @fclose($fh);
}
