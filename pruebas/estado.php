<?php
/**
 * Pruebas de lib/estado.php.  Ejecutar:  php pruebas/estado.php
 *
 * De estas cuentas depende algo que no se ve: si el cron esta callado, la
 * portada se pone a trabajar en cada visita. Equivocarse por exceso convierte
 * una portada compartida en un martillo contra la base de datos; por defecto,
 * deja el radar congelado sin que nadie se entere.
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/estado.php';
require_once dirname(__DIR__) . '/lib/feed.php';

$ahora    = 1_800_000_000;
$silencio = 7200;

// --- ¿Esta callado el cron? -------------------------------------------------

comprobar(
    'sin marca, se da por callado',
    true,
    estado_cron_callado(0, $ahora, $silencio)
);

comprobar(
    'recien pasado, no',
    false,
    estado_cron_callado($ahora - 60, $ahora, $silencio)
);

comprobar(
    'justo antes del limite, todavia no',
    false,
    estado_cron_callado($ahora - 7199, $ahora, $silencio)
);

comprobar(
    'cumplido el limite, si',
    true,
    estado_cron_callado($ahora - 7200, $ahora, $silencio)
);

comprobar(
    'dos dias sin pasar, si',
    true,
    estado_cron_callado($ahora - 172800, $ahora, $silencio)
);

// Una marca con fecha futura no es un cron vivo: es un reloj mal puesto, y
// creersela dejaria el sitio parado hasta que llegase esa fecha.
comprobar(
    'una marca del futuro no cuenta como senal de vida',
    true,
    estado_cron_callado($ahora + 90000, $ahora, $silencio)
);

// Un desfase pequeno entre el reloj del cron y el de la web es normal y no
// debe interpretarse como avería.
comprobar(
    'un desfase de unos minutos se tolera',
    false,
    estado_cron_callado($ahora + 120, $ahora, $silencio)
);

// --- Antiguedad -------------------------------------------------------------

comprobar('sin marca, no hay edad', null, estado_edad(0, $ahora));
comprobar('edad en segundos', 300, estado_edad($ahora - 300, $ahora));

// El reloj del servidor puede irse hacia atras; una edad negativa no significa
// nada para quien lee el informe.
comprobar('nunca una edad negativa', 0, estado_edad($ahora + 50, $ahora));

// --- Antiguedad en palabras -------------------------------------------------

comprobar('sin marca', 'nunca', estado_edad_texto(null));
comprobar('segundos', 'hace 45 s', estado_edad_texto(45));
comprobar('minutos', 'hace 30 min', estado_edad_texto(1800));
comprobar('horas', 'hace 5 h', estado_edad_texto(18000));
comprobar('dias', 'hace 3 días', estado_edad_texto(259200));

// --- Como se presenta el rastreador -----------------------------------------
//
// Hay cortafuegos que devuelven 403 a cualquier agente con la palabra "bot"
// dentro, aunque el feed sea publico. En el reintento se quita esa palabra,
// pero el nombre y la direccion del sitio siguen ahi: no se finge ser otro.

comprobar(
    'por defecto se presenta como lo que es',
    true,
    str_contains(strtolower(feed_agente()), 'bot')
);

comprobar(
    'en el reintento se quita la palabra, no el nombre',
    true,
    !str_contains(strtolower(feed_agente(false)), 'bot')
        && str_contains(feed_agente(false), 'BitAndBreakfast')
);

// --- Por que falla una fuente -----------------------------------------------
//
// Esta etiqueta es lo unico que se ve desde fuera cuando un feed deja de
// contestar, asi que tiene que bastar para decidir que hacer con el.

comprobar('un 403 se dice con su numero', 'http 403', estado_motivo('HTTP 403 - Forbidden'));
comprobar('y un 500 tambien', 'http 500', estado_motivo('Error HTTP 500 del servidor'));
comprobar('agotar el tiempo tiene nombre', 'tarda demasiado', estado_motivo('cURL error 28: Operation timed out'));
comprobar('el certificado, tambien', 'certificado', estado_motivo('SSL certificate problem'));
comprobar('y el dominio que no resuelve', 'no resuelve el dominio', estado_motivo('Could not resolve host'));
comprobar('lo que no es un feed', 'no devuelve un feed', estado_motivo('El XML no se puede leer'));

// Lo que no encaja en ninguna familia se enseña tal cual. Decir "otro" es no
// decir nada, y justo entonces es cuando hace falta saber que ha pasado.

comprobar(
    'un motivo desconocido se cuenta con sus palabras',
    'Conexion cerrada por el otro extremo',
    estado_motivo('Conexion cerrada por el otro extremo')
);

comprobar(
    'y en una sola linea',
    'dos lineas en una',
    estado_motivo("dos lineas\n   en una")
);

// La pagina es publica: el error de una descarga no tiene por que contar donde
// vive el codigo.

comprobar(
    'las rutas del servidor no salen',
    true,
    !str_contains(estado_motivo('Fallo al abrir /home/u123456/domains/ejemplo.com/lib/feed.php'), 'home')
);

comprobar(
    'pero la direccion del feed si, que es la que hay que arreglar',
    true,
    str_contains(estado_motivo('Respuesta rara de https://ejemplo.com/feed/'), 'https://ejemplo.com/feed/')
);

comprobar(
    'un mensaje kilometrico se recorta',
    90,
    mb_strlen(estado_motivo(str_repeat('a', 300)))
);

comprobar('sin mensaje, otro', 'otro', estado_motivo('   '));

// --- Cuando se duerme una fuente --------------------------------------------
//
// Lo que se protege aqui es el catalogo: antes, cinco fallos seguidos apagaban
// una fuente para siempre, y en una IP compartida eso se lo lleva todo por
// delante la primera tarde que un cortafuegos se pone tonto.

comprobar('un fallo suelto no se castiga', 0, feed_sueno(1));
comprobar('ni cuatro', 0, feed_sueno(4));
comprobar('al quinto, seis horas', 6, feed_sueno(5));
comprobar('siete fallos, un dia', 24, feed_sueno(7));
comprobar('diez, tres dias', 72, feed_sueno(10));
comprobar('veinte, una semana', 168, feed_sueno(20));

comprobar(
    'y de ahi no pasa: nunca se da por perdida',
    168,
    feed_sueno(500)
);

comprobar(
    'el plazo nunca se acorta al encadenar fallos',
    true,
    (function (): bool {
        $anterior = 0;

        for ($fallos = 1; $fallos <= 60; $fallos++) {
            $horas = feed_sueno($fallos);

            if ($horas < $anterior) {
                return false;
            }

            $anterior = $horas;
        }

        return true;
    })()
);

// --- Cada cuanto pasa el cron -----------------------------------------------
//
// Nadie se lo dice a este sitio: la frecuencia vive en el panel del
// alojamiento. Se mide, y por eso hay que descartar las mediciones tomadas en
// mal momento, que es justo cuando mas ganas dan de creerselas.

comprobar('cinco minutos se miden bien', 5, estado_cadencia($ahora - 300, $ahora, 60));
comprobar('y una hora tambien', 60, estado_cadencia($ahora - 3600, $ahora, 5));

// La primera pasada despues de un paron daria horas: se conserva lo sabido.
comprobar('un paron no cuenta como cadencia', 5, estado_cadencia($ahora - 86400, $ahora, 5));

// Y dos pasadas pisandose darian cero.
comprobar('ni dos pasadas seguidas', 15, estado_cadencia($ahora - 20, $ahora, 15));

// Sin marca anterior no hay nada que medir.
comprobar('sin anterior, lo que ya se sabia', 60, estado_cadencia(0, $ahora, 60));

// Y lo sabido tampoco puede ser cualquier cosa.
comprobar('lo sabido se acota por arriba', 180, estado_cadencia(0, $ahora, 99999));
comprobar('y por abajo', 1, estado_cadencia(0, $ahora, 0));

resumen_pruebas('Pruebas del estado del sitio');
