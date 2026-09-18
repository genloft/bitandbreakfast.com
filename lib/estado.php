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
 *
 * Y, del mismo palo, poner nombre a un fallo de ingesta: son cadenas de
 * entrada y cadenas de salida, sin base de datos ni red, asi que viven aqui
 * -donde se pueden probar- y no dentro de la pagina que las enseña.
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

/**
 * Cada cuantos minutos pasa el cron, deducido de lo que tarda en volver.
 *
 * Nadie se lo dice: la frecuencia vive en el panel del alojamiento, fuera del
 * repositorio y fuera de la base de datos. Pero la web quiere contarle al
 * lector cuando sera la proxima actualizacion, y para eso hay que saberlo.
 *
 * Se mide, entonces. Cada pasada mira cuanto hace de la anterior y se queda
 * con ese numero si es creible. Lo de "creible" no es pereza: la primera
 * pasada despues de un rato parado daria horas, y un reintento pisando a otro
 * daria cero. Fuera de ese rango se conserva lo que ya se sabia, que es mejor
 * dato que una medicion tomada en un mal momento.
 *
 * @param int $anterior Marca de la pasada anterior, o 0 si no habia.
 * @param int $sabido   Lo que se creia hasta ahora, en minutos.
 *
 * @return int Minutos, siempre entre 1 y 180.
 */
function estado_cadencia(int $anterior, int $ahora, int $sabido = 60): int
{
    $sabido = max(1, min(180, $sabido));

    if ($anterior <= 0 || $ahora <= $anterior) {
        return $sabido;
    }

    $minutos = (int) round(($ahora - $anterior) / 60);

    // Menos de un minuto no es una cadencia, es un reintento. Y mas de tres
    // horas no es una cadencia, es que el cron estuvo parado.
    if ($minutos < 1 || $minutos > 180) {
        return $sabido;
    }

    return $minutos;
}

/**
 * El motivo de un fallo de ingesta, en cuatro palabras.
 *
 * Casi siempre es una de cinco cosas -contesta 403, tarda demasiado, el
 * certificado, el dominio no resuelve, lo que devuelve no es un feed-, y con
 * esa etiqueta ya se sabe si hay que cambiar la URL, bajar la frecuencia o
 * quitar la fuente. Lo que no encaje en ninguna baja a estado_motivo_crudo().
 */
function estado_motivo(string $mensaje): string
{
    $m = mb_strtolower($mensaje);

    if (preg_match('/\b([45]\d{2})\b/', $m, $coincide)) {
        return 'http ' . $coincide[1];
    }

    return match (true) {
        str_contains($m, 'timed out'), str_contains($m, 'timeout')  => 'tarda demasiado',
        str_contains($m, 'ssl'), str_contains($m, 'certificate')    => 'certificado',
        str_contains($m, 'resolve'), str_contains($m, 'dns')        => 'no resuelve el dominio',
        str_contains($m, 'xml'), str_contains($m, 'entradas')       => 'no devuelve un feed',
        str_contains($m, 'vac')                                     => 'contesta vacio',
        default                                                     => estado_motivo_crudo($mensaje),
    };
}

/**
 * Cuando el motivo no encaja en ninguna familia, el mensaje tal cual.
 *
 * Decir "otro" es no decir nada: la unica vez que hace falta esta pagina es
 * justo cuando algo falla por una razon que no estaba prevista, y esa es
 * precisamente la que se quedaba sin nombre. Va recortado y en una linea
 * porque esto se lee de un vistazo, y sin rutas del servidor: la pagina es
 * publica y el error de una descarga no tiene por que contar donde vive el
 * codigo.
 */
function estado_motivo_crudo(string $mensaje): string
{
    $limpio = (string) preg_replace('~(?<![:\w/])/(?:[\w.-]+/)+[\w.-]*~u', '…', $mensaje);
    $limpio = trim((string) preg_replace('/\s+/', ' ', $limpio));

    if ($limpio === '') {
        return 'otro';
    }

    return mb_substr($limpio, 0, 90);
}
