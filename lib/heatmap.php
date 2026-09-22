<?php
/**
 * El mapa de calor del stack: que parte del sistema de un hotel esta caliente
 * esta semana y por que.
 *
 * Coge lo que ya hay publicado -bits, con su tipo, su madurez, su categoria y
 * los medios que lo cuentan- y lo convierte en un estado por nodo. No vuelve a
 * salir a la red, no llama a ningun modelo y no guarda nada en la base: es una
 * funcion de lo publicado, y por eso se puede recalcular entero en cada pasada
 * del generador sin que cueste nada.
 *
 * Las cuatro reglas que sostienen esto, y que son las que lo separan de un
 * contador de noticias por categoria:
 *
 *   1. **El estado de un nodo es el maximo de sus briefs, nunca la suma.**
 *      Cinco noticias menores no pueden encender un nodo en rojo. Sumar es
 *      exactamente el muro de ruido que un mapa viene a sustituir.
 *   2. **La puntuacion mide cuanto obliga a actuar, no cuanto llama la
 *      atencion.** Un fin de soporte anunciado vale mas que un anuncio
 *      espectacular de producto, aunque el segundo se lea mejor.
 *   3. **Todo caduca.** El mapa es un estado, no un historico: cada brief
 *      pierde un punto por cada media vida cumplida y desaparece al llegar a
 *      cero. Sin esto, a los tres meses el mapa entero esta rojo y deja de
 *      querer decir nada.
 *   4. **Como mucho tres nodos en nivel alto.** Un mapa con media docena de
 *      alarmas ensena a ignorarlo, y ese es el fallo que mata a este tipo de
 *      herramientas en el segundo mes.
 *
 * Lo que este fichero NO hace, a proposito: redactar. La skill del mapa
 * describe un "executive brief" con trigger, sistemas afectados, impacto de
 * negocio y next best action, redactado por un modelo. Aqui no hay modelo, asi
 * que cada casilla enseña lo que el bit ya dice con sus propias palabras -su
 * cuerpo y su "por que importa"- y enlaza al bit entero. Un impacto de negocio
 * inventado por una plantilla es justo lo que haria que nadie volviera a
 * fiarse de las casillas rojas.
 */

declare(strict_types=1);

require_once __DIR__ . '/stack.php';
require_once __DIR__ . '/texto.php';

/**
 * Riesgo u oportunidad.
 *
 * No es un juicio sobre la noticia, es la direccion en la que empuja al que
 * la lee: una regulacion nueva puede ser estupenda para el sector y sigue
 * siendo trabajo obligatorio para el que la cumple.
 */
function heatmap_senal(array $bit): string
{
    $tipo      = (string) ($bit['tipo'] ?? 'producto');
    $categoria = (string) ($bit['categoria'] ?? '');

    if ($tipo === 'incidente' || $tipo === 'regulacion') {
        return 'riesgo';
    }

    // Un producto nuevo de un fabricante de antivirus sigue siendo una
    // oportunidad; una noticia de la tematica "ciberseguridad" que no sea un
    // producto ni una inversion, casi nunca.
    if (in_array($categoria, ['ciberseguridad', 'cumplimiento'], true)
        && !in_array($tipo, ['producto', 'inversion'], true)) {
        return 'riesgo';
    }

    return 'oportunidad';
}

/**
 * La puntuacion de partida, de 1 a 5, antes de que el tiempo la gaste.
 *
 * La rubrica, de arriba abajo:
 *
 *   5  Accion en semanas: vulnerabilidad de un producto de uso comun,
 *      norma con fecha de entrada en vigor.
 *   4  Decision este trimestre: cambio de precio o de licencia, fin de
 *      soporte, compra de un proveedor con el que hay contrato.
 *   3  A vigilar y planificar: capacidad nueva de un proveedor del stack,
 *      tendencia con casos publicados.
 *   2  Contexto util, sin decision asociada.
 *   1  Ruido informativo.
 *
 * Los ajustes no son decoracion. Que tres medios independientes cuenten lo
 * mismo es informacion de verdad -es la diferencia entre un comunicado y un
 * hecho-, y un producto que todavia es rumor no obliga a nada por mucho que
 * el titular prometa.
 *
 * @param int $medios Cuantos medios distintos cuentan la noticia.
 */
function heatmap_puntuar(array $bit, int $medios): int
{
    $tipo      = (string) ($bit['tipo'] ?? 'producto');
    $madurez   = (string) ($bit['madurez'] ?? 'anuncio');
    $categoria = (string) ($bit['categoria'] ?? '');

    $base = [
        'incidente'  => 4,
        'regulacion' => 4,
        'producto'   => 3,
        'caso_real'  => 3,
        'inversion'  => 2,
    ][$tipo] ?? 2;

    // Una vulnerabilidad en software del sector es lo unico que se mide en
    // semanas y no en trimestres.
    if ($tipo === 'incidente' && $categoria === 'ciberseguridad') {
        $base++;
    }

    // Un producto que ya se puede comprar o que ya esta instalado en alguna
    // parte es una decision; el mismo producto anunciado para el ano que viene
    // es una nota al margen.
    if ($tipo === 'producto' && in_array($madurez, ['disponible', 'implantado'], true)) {
        $base++;
    }

    if ($madurez === 'rumor') {
        $base--;
    }

    if ($medios >= 3) {
        $base++;
    }

    return max(1, min(5, $base));
}

/**
 * Cuanto dura viva una noticia, segun de que sea.
 *
 * 'vida' es la media vida: cada vez que se cumple, el brief pierde un punto.
 * 'caducidad' es el tope duro, por si la puntuacion de partida era tan alta
 * que el decay solo no lo sacaria del mapa en un trimestre.
 *
 * Una brecha envejece en una semana -o ya la has parcheado o ya es tarde-;
 * una norma tarda un mes en dejar de ser noticia porque el plazo de
 * cumplimiento sigue corriendo.
 *
 * @return array{vida: int, caducidad: int} en dias
 */
function heatmap_caducidad(string $tipo): array
{
    return [
        'incidente'  => ['vida' => 7,  'caducidad' => 30],
        'regulacion' => ['vida' => 30, 'caducidad' => 90],
        'inversion'  => ['vida' => 14, 'caducidad' => 60],
        'producto'   => ['vida' => 10, 'caducidad' => 45],
        'caso_real'  => ['vida' => 10, 'caducidad' => 45],
    ][$tipo] ?? ['vida' => 10, 'caducidad' => 45];
}

/**
 * Los dias entre dos fechas AAAA-MM-DD. Negativo si la segunda es anterior.
 *
 * A mano y no con DateTime::diff() porque las fechas llegan de la base como
 * cadenas y una ilegible tiene que devolver algo, no lanzar. El mapa del dia
 * no se puede caer porque un bit tenga el dia a NULL.
 */
function heatmap_dias(string $desde, string $hasta): int
{
    $a = strtotime(substr($desde, 0, 10) . ' 00:00:00 UTC');
    $b = strtotime(substr($hasta, 0, 10) . ' 00:00:00 UTC');

    if ($a === false || $b === false) {
        return 0;
    }

    return (int) round(($b - $a) / 86400);
}

/**
 * El lunes de la semana de una fecha, que es cuando se congela el mapa.
 *
 * El mapa no es el rio. El rio publica una noticia en cuanto esta escrita
 * -esa es la promesa del sitio- y el mapa es un estado, y un estado que se
 * mueve todos los dias no se puede mirar: quien lo vio el martes y vuelve el
 * jueves no sabe si lo que ha cambiado es el sector o el decay. Congelado por
 * semanas, "el mapa de esta semana" es una cosa concreta que se puede recordar,
 * comparar y mandar por correo.
 *
 * El corte es el lunes a las 00:00 UTC. Lo que se publica de lunes a domingo
 * sale en el rio ese mismo dia y entra en el mapa el lunes siguiente, todo de
 * golpe. Es la unica incoherencia que este diseño acepta a proposito, y por eso
 * el dibujo lleva la fecha escrita en una esquina: un mapa de la semana pasada
 * bien fechado es honesto; el mismo mapa disfrazado de hoy, no.
 *
 * Con 'N', que va de 1 el lunes a 7 el domingo. Una fecha ilegible devuelve la
 * semana actual: el mapa del dia no se puede caer porque alguien pase una
 * cadena rara.
 */
function heatmap_semana(?string $dia = null): string
{
    $instante = strtotime(substr((string) ($dia ?? gmdate('Y-m-d')), 0, 10) . ' 00:00:00 UTC');

    if ($instante === false) {
        $instante = time();
    }

    return gmdate('Y-m-d', $instante - ((int) gmdate('N', $instante) - 1) * 86400);
}

/**
 * La puntuacion que le queda a un brief hoy. Cero quiere decir que ya no va
 * al mapa.
 *
 * El decay se aplica siempre antes de agregar nada: primero se apaga lo viejo
 * y despues se enciende lo nuevo. Al reves, un nodo podria quedarse en rojo
 * por una noticia que ya no deberia contar.
 */
function heatmap_vigente(int $base, string $tipo, string $dia, string $hoy): int
{
    $edad = heatmap_dias($dia, $hoy);

    // Un bit con fecha de manana es un reloj mal puesto, no una noticia del
    // futuro: se trata como recien publicado.
    if ($edad < 0) {
        $edad = 0;
    }

    $plazos = heatmap_caducidad($tipo);

    if ($edad >= $plazos['caducidad']) {
        return 0;
    }

    return max(0, $base - intdiv($edad, $plazos['vida']));
}

/**
 * La etiqueta legible de una puntuacion.
 *
 * Existe para que la plantilla no tenga que saber de rangos, y sobre todo
 * para que el color nunca vaya solo: una parte real de los directivos no
 * distingue el rojo del verde, y el mapa se mira tambien en capturas en
 * blanco y negro.
 */
function heatmap_nivel(int $score): string
{
    if ($score >= 4) {
        return 'alto';
    }

    return $score >= 3 ? 'medio' : 'bajo';
}

/**
 * Cuanto se puede uno fiar de que esta noticia va donde el mapa dice.
 *
 * Mide la clasificacion, no la veracidad de la noticia: 'baja' quiere decir
 * "esta casilla puede no ser la suya", no "esto puede ser mentira". Se enseña
 * siempre, nunca se esconde.
 */
function heatmap_confianza(string $origen, int $alias, int $medios): string
{
    if ($origen !== 'alias') {
        return 'baja';
    }

    if ($alias >= 2 && $medios >= 2) {
        return 'alta';
    }

    return $alias >= 2 || $medios >= 2 ? 'media' : 'baja';
}

/**
 * El arranque del brief: lo que ha pasado, con las palabras del propio bit.
 *
 * Se corta por frases y no por caracteres sueltos para no dejar el texto a
 * mitad de una cifra. Si el cuerpo esta vacio -no deberia, el formato lo
 * exige antes de aprobar- se devuelve el titular, que siempre esta.
 */
function heatmap_disparador(array $bit, int $maximo = 180): string
{
    // strip_tags() y no texto_limpiar_html(): esa deja pasar <p> y <strong>
    // -correcto para el cuerpo de un bit, que se pinta con formato- y aqui el
    // disparador va dentro de web_e(), asi que una etiqueta superviviente se
    // veria escrita tal cual en medio de la frase.
    $cuerpo = trim(strip_tags((string) ($bit['cuerpo'] ?? '')));

    if ($cuerpo === '') {
        return trim((string) ($bit['titular'] ?? ''));
    }

    $frases = preg_split('/(?<=[.!?])\s+/u', $cuerpo) ?: [$cuerpo];
    $salida = '';

    foreach ($frases as $frase) {
        $candidato = $salida === '' ? $frase : $salida . ' ' . $frase;

        if (mb_strlen($candidato, 'UTF-8') > $maximo && $salida !== '') {
            break;
        }

        $salida = $candidato;

        if (mb_strlen($salida, 'UTF-8') >= $maximo) {
            break;
        }
    }

    return texto_recortar(trim($salida), $maximo);
}

/**
 * El umbral de publicacion: por debajo de esto, la noticia no va al mapa.
 *
 * Tres. El valor esta aqui y no repartido por el fichero porque es la unica
 * palanca que decide cuanto habla el mapa, y cambiarlo tiene que ser una
 * decision, no un descuido.
 */
function heatmap_umbral(): int
{
    return 3;
}

/**
 * Cuantos nodos pueden estar en rojo a la vez.
 */
function heatmap_tope_alto(): int
{
    return 3;
}

/**
 * El mapa entero: nodos encendidos y los briefs que los encienden.
 *
 * Es lo que se publica en data/heatmap.json y lo que pinta la portada. Recibe
 * ya los bits -no los pide, para poder probarse sin base de datos- y devuelve
 * la estructura completa, lista para serializar.
 *
 * @param array  $bits    Filas de publicar_bits(): titular, cuerpo, por_que,
 *                        categoria, tipo, madurez, dia, url, fuente, racimo_id.
 * @param array  $fuentes racimo_id => lista de medios, tal y como la devuelve
 *                        publicar_fuentes(). Solo se usa para contarlos.
 * @param string $corte   El lunes de la semana que se publica, de
 *                        heatmap_semana(). Se mide el decay desde ahi y se
 *                        deja fuera lo publicado a partir de ese dia: si
 *                        entrara, el mapa volveria a moverse a diario y dejaria
 *                        de ser el de la semana.
 * @param string $base    URL del sitio, para que los enlaces del JSON sirvan
 *                        fuera de el.
 */
function heatmap_componer(array $bits, array $fuentes, string $corte, string $base = ''): array
{
    $briefs = [];

    foreach ($bits as $bit) {
        $dia = substr((string) ($bit['dia'] ?? ''), 0, 10);

        // Vacio, o publicado ya dentro de la semana en curso: fuera. Lo
        // segundo no es un descarte, es una espera: entra en el mapa el lunes
        // que viene.
        if ($dia === '' || $dia >= $corte) {
            continue;
        }

        $detalle = stack_clasificar_detalle($bit);

        if (!$detalle['nodos']) {
            continue;
        }

        $medios  = max(1, count($fuentes[(int) ($bit['racimo_id'] ?? 0)] ?? []));
        $tipo    = (string) ($bit['tipo'] ?? 'producto');
        $partida = heatmap_puntuar($bit, $medios);
        $score   = heatmap_vigente($partida, $tipo, $dia, $corte);

        if ($score < heatmap_umbral()) {
            continue;
        }

        $id = (int) ($bit['id'] ?? 0);

        $briefs[] = [
            'id'          => 'bit-' . $id,
            'bit_id'      => $id,
            'headline'    => (string) ($bit['titular'] ?? ''),
            'node_ids'    => $detalle['nodos'],
            'signal'      => heatmap_senal($bit),
            'score'       => $score,
            'score_base'  => $partida,
            'level'       => heatmap_nivel($score),
            'confidence'  => heatmap_confianza($detalle['origen'], $detalle['alias'], $medios),
            'trigger'     => heatmap_disparador($bit),
            'por_que'     => trim((string) ($bit['por_que'] ?? '')),
            'tipo'        => $tipo,
            'madurez'     => (string) ($bit['madurez'] ?? ''),
            'medios'      => $medios,
            'sources'     => [[
                'title'     => (string) ($bit['titular'] ?? ''),
                'publisher' => (string) ($bit['fuente'] ?? ''),
                'url'       => (string) ($bit['url'] ?? ''),
                'date'      => $dia,
            ]],
            'published_at' => $dia,
            'url'          => $base === '' ? '' : $base . '/d/' . $dia . '/#bit-' . $id,
        ];
    }

    // De mas urgente a menos, y a igual urgencia lo mas reciente primero. El
    // orden manda dentro de cada casilla y en la lista sin JavaScript, asi que
    // se fija una vez aqui y nadie mas vuelve a ordenar.
    usort($briefs, static function (array $a, array $b): int {
        return [$b['score'], $b['published_at']] <=> [$a['score'], $a['published_at']];
    });

    $nodos = heatmap_agregar($briefs);

    return [
        'generated_at'     => gmdate('c'),
        // La semana que se esta enseñando. Va al JSON publico y a la esquina
        // del dibujo: es la fecha de la foto, no la de la ultima vez que el
        // generador paso por aqui, que no le importa a nadie.
        'semana'           => $corte,
        'stale'            => heatmap_rancio($briefs, $corte),
        'taxonomy_version' => stack_version(),
        'umbral'           => heatmap_umbral(),
        'nodes'            => $nodos,
        'briefs'           => $briefs,
    ];
}

/**
 * El estado de cada nodo a partir de sus briefs: el maximo, nunca la suma.
 *
 * Y despues la unica regla que recorta: si quedan mas de tres nodos en rojo,
 * solo los tres de mas puntuacion se quedan ahi y el resto baja a nivel medio.
 *
 * La skill lo describe como "subir el umbral hasta dejar los tres mas
 * relevantes", pero subir el umbral tira primero los briefs de puntuacion 3,
 * que no son los que pintan rojo: no arregla lo que se quiere arreglar.
 * Bajarlos de nivel si. Los briefs siguen todos publicados y con su
 * puntuacion intacta -no se esconde nada-, lo que cambia es el color de la
 * casilla, que es lo que estaba gritando de mas.
 */
function heatmap_agregar(array $briefs): array
{
    $catalogo = stack_nodos();
    $nodos    = [];

    foreach ($briefs as $brief) {
        foreach ($brief['node_ids'] as $nodo) {
            // Un brief guardado con un catalogo anterior puede apuntar a un
            // nodo que ya no existe. Se ignora en silencio: encender una
            // casilla que no esta seria peor, y avisar no ayuda a nadie que
            // pueda hacer algo al respecto.
            if (!isset($catalogo[$nodo])) {
                continue;
            }

            $actual = $nodos[$nodo] ?? null;

            if ($actual === null || $brief['score'] > $actual['score']) {
                $nodos[$nodo] = [
                    'signal'    => $brief['signal'],
                    'score'     => $brief['score'],
                    'level'     => $brief['level'],
                    'brief_ids' => [],
                ];
            }
        }
    }

    foreach ($briefs as $brief) {
        foreach ($brief['node_ids'] as $nodo) {
            if (isset($nodos[$nodo])) {
                $nodos[$nodo]['brief_ids'][] = $brief['id'];
            }
        }
    }

    // Los altos, de mayor a menor. A igualdad de puntuacion decide el orden
    // del catalogo, que es estable: sin un desempate fijo, dos nodos empatados
    // se turnarian el rojo en cada regeneracion sin que hubiera pasado nada.
    $altos = [];

    foreach (array_keys($catalogo) as $nodo) {
        if (isset($nodos[$nodo]) && $nodos[$nodo]['score'] >= 4) {
            $altos[$nodo] = $nodos[$nodo]['score'];
        }
    }

    arsort($altos);

    $sobran = array_slice(array_keys($altos), heatmap_tope_alto());

    foreach ($sobran as $nodo) {
        $nodos[$nodo]['level']    = 'medio';
        $nodos[$nodo]['atenuado'] = true;
    }

    // Ordenados como el catalogo: asi el JSON se lee igual que se pinta.
    $ordenados = [];

    foreach (array_keys($catalogo) as $nodo) {
        if (isset($nodos[$nodo])) {
            $ordenados[$nodo] = $nodos[$nodo];
        }
    }

    return $ordenados;
}

/**
 * Si el mapa esta contando algo viejo.
 *
 * No mide si el cron corrio -eso lo dice /salud.php- sino si lo que se enseña
 * sigue teniendo algo que ver con el presente. Un mapa viejo bien fechado es
 * honesto; el mismo mapa disfrazado de hoy es el peor resultado posible.
 *
 * Catorce dias y no siete: el mapa se congela por semanas, asi que una semana
 * sin noticias nuevas es el funcionamiento normal y avisar de ella seria una
 * alarma que suena sola. Dos semanas sin nada ya es que pasa algo.
 */
function heatmap_rancio(array $briefs, string $hoy, int $plazo = 14): bool
{
    if (!$briefs) {
        return true;
    }

    $ultimo = '';

    foreach ($briefs as $brief) {
        if ($brief['published_at'] > $ultimo) {
            $ultimo = $brief['published_at'];
        }
    }

    return heatmap_dias($ultimo, $hoy) > $plazo;
}

/**
 * Los briefs de un nodo, en el orden en que ya vienen.
 *
 * @return array[] los briefs enteros, no sus ids
 */
function heatmap_briefs_de(array $mapa, string $nodo): array
{
    $ids = array_flip($mapa['nodes'][$nodo]['brief_ids'] ?? []);

    return array_values(array_filter(
        $mapa['briefs'] ?? [],
        static fn(array $brief): bool => isset($ids[$brief['id']])
    ));
}

/**
 * Cuantos nodos estan encendidos y cuantos en rojo. Para la linea de resumen
 * de la portada, que es lo que se lee antes que el mapa.
 *
 * @return array{encendidos: int, altos: int, riesgos: int, oportunidades: int}
 */
function heatmap_resumen(array $mapa): array
{
    $altos = 0;
    $riesgos = 0;
    $oportunidades = 0;

    foreach ($mapa['nodes'] ?? [] as $nodo) {
        if ($nodo['level'] === 'alto') {
            $altos++;
        }

        if ($nodo['signal'] === 'riesgo') {
            $riesgos++;
        } else {
            $oportunidades++;
        }
    }

    return [
        'encendidos'    => count($mapa['nodes'] ?? []),
        'altos'         => $altos,
        'riesgos'       => $riesgos,
        'oportunidades' => $oportunidades,
    ];
}
