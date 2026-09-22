<?php
/**
 * La forma del mapa: donde cae cada nodo y con quien esta conectado.
 *
 * `lib/stack.php` dice QUE piezas hay; esto dice como se dibujan. Son dos
 * ficheros y no uno porque cambian por motivos distintos: un alias nuevo es
 * trabajo editorial y una coordenada es trabajo de diseno, y mezclarlos
 * obligaria a revisar el dibujo cada vez que alguien anade la palabra
 * "siteminder" a una lista.
 *
 * Las coordenadas viven en `lib/` y no en la plantilla, aunque sean
 * presentacion pura, por una razon concreta: un nodo sin coordenada no da
 * error, simplemente **desaparece del mapa**, y nadie lo nota hasta que un
 * dia alguien pregunta por que su area no sale nunca. Aqui abajo se puede
 * probar que los veinticuatro estan puestos y que ningun enlace apunta a un
 * nodo que no existe. En una plantilla, no.
 *
 * El dibujo no es decorativo: es como circula el trabajo en un hotel, de
 * izquierda a derecha. La demanda entra por Distribucion, pasa por
 * Operaciones -con el PMS de eje, que por eso esta en el centro-, sigue al
 * huesped en Experiencia y acaba liquidandose en Back-Office. Debajo, como
 * cimiento, la Infraestructura, que sostiene a todos. Encima, los Datos, que
 * se alimentan de todos. Quien conozca el sector reconoce su casa en este
 * dibujo antes de leer una sola etiqueta, y esa es la mitad del trabajo de
 * un mapa.
 *
 * El lienzo es 1400 x 520 y se escala solo: son unidades de dibujo, no
 * pixeles. Deliberadamente apaisado -casi 3:1-, porque este mapa vive en una
 * banda de la portada y una franja ancha y baja no le quita el sitio a las
 * noticias; un lienzo cuadrado, si.
 */

declare(strict_types=1);

require_once __DIR__ . '/stack.php';

/**
 * El tamano del lienzo, en unidades de dibujo.
 *
 * @return array{ancho: int, alto: int}
 */
function grafo_lienzo(): array
{
    return ['ancho' => 1400, 'alto' => 520];
}

/**
 * Donde esta cada nodo.
 *
 * @return array<string, array{x: int, y: int}>
 */
function grafo_posiciones(): array
{
    return [
        // Datos e IA: la banda de arriba. Se alimenta de todo lo de abajo, y
        // por eso cruza el mapa entero en vez de ocupar una esquina.
        'cdp-datos'        => ['x' => 300,  'y' => 70],
        'bi-reporting'     => ['x' => 560,  'y' => 92],
        'integraciones'    => ['x' => 850,  'y' => 66],
        'gobierno-ia'      => ['x' => 1120, 'y' => 94],

        // Distribucion: por aqui entra la demanda, asi que entra por la
        // izquierda.
        'ota-metas'        => ['x' => 85,   'y' => 175],
        'channel-manager'  => ['x' => 175,  'y' => 245],
        'crs-motor'        => ['x' => 90,   'y' => 315],
        'rms'              => ['x' => 185,  'y' => 372],

        // Operaciones: el PMS en el centro geometrico del mapa porque lo es
        // tambien del hotel. Es el nodo con mas aristas de los veinticuatro,
        // y eso no es una opinion de diseno: es lo que se rompe cuando se
        // rompe.
        'mice'             => ['x' => 420,  'y' => 160],
        'housekeeping'     => ['x' => 545,  'y' => 205],
        'pms'              => ['x' => 465,  'y' => 285],
        'pos-fb'           => ['x' => 400,  'y' => 365],

        // Experiencia de cliente: lo que el huesped toca.
        'checkin-digital'  => ['x' => 770,  'y' => 170],
        'app-huesped'      => ['x' => 895,  'y' => 230],
        'mensajeria-ia'    => ['x' => 780,  'y' => 305],
        'crm-fidelizacion' => ['x' => 890,  'y' => 370],

        // Back-Office: donde acaba liquidandose todo.
        'rrhh'             => ['x' => 1130, 'y' => 165],
        'pagos'            => ['x' => 1100, 'y' => 255],
        'erp-finanzas'     => ['x' => 1255, 'y' => 310],
        'compras'          => ['x' => 1140, 'y' => 372],

        // Infraestructura: el cimiento, debajo y cruzando el ancho entero.
        'red-wifi'         => ['x' => 350,  'y' => 440],
        'ciberseguridad'   => ['x' => 620,  'y' => 460],
        'cloud-hosting'    => ['x' => 880,  'y' => 438],
        'accesos-iot'      => ['x' => 1140, 'y' => 460],
    ];
}

/**
 * Las manchas de fondo que agrupan cada area.
 *
 * Son rectangulos redondeados muy tenues, sin borde. Un borde marcado
 * convierte el mapa en seis cajas con cosas dentro -que es exactamente el
 * reticulo del que este dibujo viene huyendo-; una mancha apenas mas oscura
 * que el papel agrupa sin encerrar, y deja que las lineas la crucen.
 *
 * @return array<string, array{x: int, y: int, ancho: int, alto: int}>
 */
function grafo_regiones(): array
{
    return [
        'data'  => ['x' => 245,  'y' => 10,  'ancho' => 940, 'alto' => 140],
        'dist'  => ['x' => 25,   'y' => 130, 'ancho' => 225, 'alto' => 270],
        'ops'   => ['x' => 345,  'y' => 105, 'ancho' => 265, 'alto' => 295],
        'cx'    => ['x' => 700,  'y' => 115, 'ancho' => 262, 'alto' => 285],
        'bo'    => ['x' => 1040, 'y' => 110, 'ancho' => 292, 'alto' => 290],
        'infra' => ['x' => 280,  'y' => 410, 'ancho' => 930, 'alto' => 105],
    ];
}

/**
 * Quien habla con quien.
 *
 * No son "temas relacionados": son integraciones que existen de verdad en un
 * hotel. Sirven para leer el contagio, que es la pregunta cara. Si el PMS se
 * cae, se ve en el dibujo que arrastra al check-in, al TPV, a housekeeping y
 * al motor de reservas; un mapa de casillas sueltas obliga a saberselo de
 * memoria.
 *
 * Por eso la lista es corta y conservadora. Con todas las conexiones
 * imaginables -y casi todo se integra con casi todo- saldrian doscientas
 * aristas y el dibujo dejaria de decir nada: un grafo completo es tan
 * informativo como uno vacio.
 *
 * @return array<int, array{0: string, 1: string}>
 */
function grafo_enlaces(): array
{
    return [
        // La cadena de la demanda.
        ['ota-metas', 'channel-manager'],
        ['channel-manager', 'crs-motor'],
        ['channel-manager', 'rms'],
        ['crs-motor', 'rms'],
        ['crs-motor', 'pms'],
        ['crs-motor', 'pagos'],

        // El PMS como eje.
        ['pms', 'housekeeping'],
        ['pms', 'pos-fb'],
        ['pms', 'mice'],
        ['pms', 'checkin-digital'],
        ['pms', 'crm-fidelizacion'],
        ['pms', 'pagos'],
        ['pms', 'integraciones'],
        ['pms', 'cloud-hosting'],
        ['pms', 'ciberseguridad'],
        ['pms', 'bi-reporting'],

        // El viaje del huesped.
        ['checkin-digital', 'app-huesped'],
        ['checkin-digital', 'accesos-iot'],
        ['app-huesped', 'mensajeria-ia'],
        ['mensajeria-ia', 'crm-fidelizacion'],
        ['mensajeria-ia', 'gobierno-ia'],
        ['mice', 'crm-fidelizacion'],

        // El dato, que sube desde todas partes.
        ['crm-fidelizacion', 'cdp-datos'],
        ['cdp-datos', 'bi-reporting'],
        ['cdp-datos', 'gobierno-ia'],
        ['integraciones', 'crs-motor'],
        ['integraciones', 'channel-manager'],
        ['integraciones', 'cdp-datos'],
        ['rms', 'bi-reporting'],

        // El dinero y la casa.
        ['pagos', 'erp-finanzas'],
        ['pagos', 'ciberseguridad'],
        ['erp-finanzas', 'compras'],
        ['erp-finanzas', 'rrhh'],
        ['compras', 'pos-fb'],
        ['rrhh', 'housekeeping'],

        // El cimiento.
        ['red-wifi', 'accesos-iot'],
        ['red-wifi', 'pos-fb'],
        ['cloud-hosting', 'crs-motor'],
        ['cloud-hosting', 'red-wifi'],
        ['ciberseguridad', 'accesos-iot'],
    ];
}

/**
 * El radio de un nodo segun lo caliente que este.
 *
 * El tamano es la segunda lectura del calor, despues del color y antes que la
 * etiqueta: un mapa impreso en blanco y negro sigue enseñando donde mirar
 * porque los puntos gordos siguen siendo gordos.
 */
function grafo_radio(?array $estado): int
{
    if ($estado === null) {
        return 8;
    }

    return [3 => 15, 4 => 19, 5 => 23][(int) $estado['score']] ?? 15;
}

/**
 * La curva entre dos nodos.
 *
 * Curva y no recta por una razon practica: con cuarenta aristas rectas sobre
 * veinticuatro puntos, unas cuantas se superponen exactamente y dejan de
 * verse como dos. Una curvatura pequena, siempre hacia el mismo lado, las
 * separa lo justo para poder seguirlas con la vista.
 *
 * Con un octavo de la distancia -lo primero que se probo- las aristas largas
 * del PMS salian disparadas por el centro del dibujo y lo llenaban de arcos.
 * Un catorceavo, y ademas con tope: a partir de cierta separacion la curva
 * deja de crecer, porque lo que hay que evitar es que dos lineas coincidan,
 * y eso ya se consigue con veinte unidades de desvio.
 *
 * @param array{x: int, y: int} $a
 * @param array{x: int, y: int} $b
 */
function grafo_curva(array $a, array $b): string
{
    $dx = $b['x'] - $a['x'];
    $dy = $b['y'] - $a['y'];

    $medio_x = $a['x'] + $dx / 2;
    $medio_y = $a['y'] + $dy / 2;

    // Perpendicular al segmento, normalizada y escalada.
    $largo = sqrt($dx * $dx + $dy * $dy);

    if ($largo < 1.0) {
        return sprintf('M %d %d L %d %d', $a['x'], $a['y'], $b['x'], $b['y']);
    }

    $desvio = min(20.0, $largo / 14);
    $cx = $medio_x + (-$dy / $largo) * $desvio;
    $cy = $medio_y + ($dx / $largo) * $desvio;

    return sprintf(
        'M %d %d Q %.1f %.1f %d %d',
        $a['x'],
        $a['y'],
        $cx,
        $cy,
        $b['x'],
        $b['y']
    );
}

/**
 * Los enlaces, con la pinta que les toca segun a quien tocan.
 *
 * Es lo que convierte el dibujo en una respuesta y no en un adorno: cuando el
 * PMS esta en rojo, las lineas que salen de el tambien se tinen, y se ve de un
 * golpe a que arrastra.
 *
 * Solo se tinen las que salen de un nodo de **nivel alto**, y el motivo salio
 * de mirar el dibujo: con nueve nodos encendidos, teñir todo lo que tocara
 * cualquiera de ellos pintaba veintinueve de las cuarenta aristas. Un mapa en
 * el que casi todo es rojo no dice que casi todo esta mal, dice que el color
 * ha dejado de significar algo. Las de nivel medio se marcan solo un poco mas
 * oscuras: se ve que hay algo, sin gritar.
 *
 * Un enlace entre dos nodos encendidos se queda con el mas fuerte de los dos.
 * Si mandara el mas flojo, una linea que sale de una alarma se pintaria del
 * color de la calma.
 *
 * @return array<int, array{d: string, clase: string}>
 */
function grafo_aristas(array $mapa): array
{
    $posiciones = grafo_posiciones();
    $estados    = $mapa['nodes'] ?? [];
    $aristas    = [];

    foreach (grafo_enlaces() as [$desde, $hasta]) {
        if (!isset($posiciones[$desde], $posiciones[$hasta])) {
            continue;
        }

        $a = $estados[$desde] ?? null;
        $b = $estados[$hasta] ?? null;

        // El mas caliente de los dos extremos manda. Si ninguno lo esta, la
        // arista es una linea de fondo.
        $fuerte = null;

        foreach ([$a, $b] as $estado) {
            if ($estado !== null && ($fuerte === null || $estado['score'] > $fuerte['score'])) {
                $fuerte = $estado;
            }
        }

        if ($fuerte === null) {
            $clase = 'mapa-arista';
        } elseif ($fuerte['level'] === 'alto') {
            $clase = 'mapa-arista mapa-arista--viva mapa-arista--' . $fuerte['signal'];
        } else {
            $clase = 'mapa-arista mapa-arista--tibia';
        }

        $aristas[] = [
            'd'     => grafo_curva($posiciones[$desde], $posiciones[$hasta]),
            'clase' => $clase,
        ];
    }

    return $aristas;
}
