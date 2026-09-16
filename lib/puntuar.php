<?php
/**
 * Puntuacion de items y racimos.
 *
 * Todo lo de aqui es calculo puro: recibe datos, devuelve numeros, no toca la
 * base ni el reloj salvo cuando se le pasa la hora. Asi la puntuacion se
 * puede probar entera sin servidor, que es justo lo que hace
 * pruebas/procesar.php.
 *
 * La puntuacion tiene dos niveles y conviene no mezclarlos:
 *
 *   item   - lo que vale una noticia por si sola: peso de la fuente,
 *            diccionario, frescura, proveedores y bonus por tipo de fuente.
 *   racimo - lo que vale la noticia contada por varios: el mejor de sus items
 *            mas un extra por cada fuente distinta que la cuenta.
 *
 * Que varias fuentes cuenten lo mismo es la mejor senal de que importa, y por
 * eso el extra vive en el racimo y no en el item.
 */

declare(strict_types=1);

require_once __DIR__ . '/texto.php';

/**
 * Valores por defecto de todos los ajustes de agrupacion y puntuacion.
 *
 * Viven aqui, y no en cron/procesar.php, para que las pruebas puedan usar
 * exactamente los mismos numeros que produccion. Tenerlos copiados en tres
 * sitios -esquema, cron y pruebas- garantizaba que antes o despues uno se
 * quedara atras.
 *
 * Que existan estos defectos permite ademas desplegar una version que use un
 * ajuste nuevo sin tener que tocar la base de datos antes.
 */
function puntuar_defectos(): array
{
    return [
        'procesar_lote'           => '40',
        'agrupar_ventana_horas'   => '72',
        'agrupar_umbral_alto'     => '0.45',
        'agrupar_umbral_bajo'     => '0.30',
        'agrupar_min_proveedores' => '2',
        'punt_peso_fuente'        => '3',
        'punt_tope_diccionario'   => '30',
        'punt_por_fuente_racimo'  => '6',
        'punt_frescura_24h'       => '10',
        'punt_frescura_72h'       => '5',
        'punt_frescura_7d'        => '2',
        'punt_proveedor'          => '8',
        'punt_bonus_incidente'    => '12',
        'punt_bonus_regulacion'   => '10',
        'punt_bonus_changelog'    => '6',
        'punt_bonus_empleo'       => '3',
        'punt_publirreportaje'    => '12',
    ];
}

/**
 * Suma los pesos de los terminos del diccionario presentes en un texto.
 *
 * Los dos lados pasan por texto_normalizar: los terminos de la semilla vienen
 * con guiones ("zero-day", "game-changer") y la normalizacion convierte
 * cualquier signo en espacio. Si solo se normalizara el texto, esos terminos
 * no encontrarian nunca su pareja.
 *
 * Quien carga el diccionario deberia normalizar los terminos una sola vez, al
 * leerlos: aqui se vuelve a normalizar porque la funcion tiene que ser
 * correcta por si sola, y la normalizacion es idempotente, pero repetirla por
 * cada item y cada termino son miles de pasadas inutiles por lote.
 *
 * @param array $terminos Filas con 'termino' y 'peso'.
 * @return array ['positivo' => int, 'negativo' => int]
 */
function puntuar_terminos_en(string $texto, array $terminos): array
{
    $aguja = ' ' . texto_normalizar($texto) . ' ';
    $suma  = ['positivo' => 0, 'negativo' => 0];

    foreach ($terminos as $fila) {
        $termino = texto_normalizar((string) $fila['termino']);

        if ($termino === '') {
            continue;
        }

        // Los espacios de los extremos hacen que "cve" no case dentro de
        // "cveinfo" ni "ia" dentro de "ialgo".
        if (!str_contains($aguja, ' ' . $termino . ' ')) {
            continue;
        }

        $peso = (int) $fila['peso'];

        if ($peso >= 0) {
            $suma['positivo'] += $peso;
        } else {
            $suma['negativo'] += $peso;
        }
    }

    return $suma;
}

/**
 * Puntos que aporta el diccionario a un item.
 *
 * El titular cuenta doble y el resumen simple, como dice la cabecera de
 * sql/semilla_diccionario.sql: un termino en el titular es de lo que va la
 * noticia; en el resumen puede ser de pasada.
 *
 * Lo positivo se limita con un tope para que un titular cargado de palabras
 * de moda no se coma la escala. Lo negativo no tiene tope: es el filtro
 * anti-publirreportaje y ahi cuanto mas hunda, mejor.
 *
 * @return array ['puntos' => int, 'publirreportaje' => bool]
 */
function puntuar_diccionario(
    string $titulo,
    ?string $resumen,
    array $terminos,
    int $tope,
    int $umbral_publirreportaje = -5
): array {
    $en_titulo = puntuar_terminos_en($titulo, $terminos);
    $en_resumen = puntuar_terminos_en((string) $resumen, $terminos);

    $positivo = min($tope, 2 * $en_titulo['positivo'] + $en_resumen['positivo']);
    $negativo = 2 * $en_titulo['negativo'] + $en_resumen['negativo'];

    return [
        'puntos'          => $positivo + $negativo,
        // Un solo marcador fuerte (-5) o varios flojos encienden la bandera.
        // Un "socio estrategico" suelto resta, pero no condena la noticia.
        'publirreportaje' => $negativo <= $umbral_publirreportaje,
    ];
}

/**
 * Puntos por lo reciente que es la noticia.
 *
 * Los tramos son escalones y no una curva: un radar semanal no necesita
 * distinguir entre seis y ocho horas, y los escalones se explican solos
 * cuando hay que justificar por que un candidato esta donde esta.
 */
function puntuar_frescura(string $publicado, array $conf, ?int $ahora = null): int
{
    $ahora    = $ahora ?? time();
    $instante = strtotime($publicado . ' UTC');

    if ($instante === false) {
        return 0;
    }

    $horas = ($ahora - $instante) / 3600;

    // Una fecha en el futuro es un feed con el reloj mal puesto, no una
    // primicia: se trata como recien publicada, no se premia de mas.
    if ($horas < 0) {
        $horas = 0;
    }

    if ($horas < 24) {
        return (int) $conf['punt_frescura_24h'];
    }
    if ($horas < 72) {
        return (int) $conf['punt_frescura_72h'];
    }
    if ($horas < 24 * 7) {
        return (int) $conf['punt_frescura_7d'];
    }

    return 0;
}

/**
 * Bonus segun el tipo de fuente.
 *
 * El tipo de la fuente hace de proxy del tipo de noticia: una pagina de
 * estado solo publica incidentes, y un boletin oficial solo normativa. No es
 * exacto, pero no exige clasificar el texto y acierta casi siempre.
 */
function puntuar_bonus_fuente(string $tipo, array $conf): int
{
    return match ($tipo) {
        'estado'    => (int) $conf['punt_bonus_incidente'],
        'normativa' => (int) $conf['punt_bonus_regulacion'],
        'changelog' => (int) $conf['punt_bonus_changelog'],
        'empleo'    => (int) $conf['punt_bonus_empleo'],
        default     => 0,
    };
}

/**
 * Puntuacion de un item suelto.
 *
 * @param array $datos peso_fuente, tipo_fuente, publicado, puntos_diccionario,
 *                     publirreportaje, proveedores
 */
function puntuar_item(array $datos, array $conf, ?int $ahora = null): int
{
    $puntos = (int) $datos['peso_fuente'] * (int) $conf['punt_peso_fuente'];

    $puntos += (int) $datos['puntos_diccionario'];
    $puntos += puntuar_frescura((string) $datos['publicado'], $conf, $ahora);
    $puntos += puntuar_bonus_fuente((string) $datos['tipo_fuente'], $conf);

    if ((int) $datos['proveedores'] > 0) {
        $puntos += (int) $conf['punt_proveedor'];
    }

    if (!empty($datos['publirreportaje'])) {
        $puntos -= (int) $conf['punt_publirreportaje'];
    }

    return $puntos;
}

/**
 * Puntuacion de un racimo: su mejor item mas la corroboracion.
 *
 * El extra por fuentes distintas se corta en cinco. Mas alla de cinco medios
 * contando lo mismo, la sexta confirmacion ya no dice nada nuevo y sin tope
 * cualquier nota de prensa muy distribuida ganaria a una exclusiva buena.
 */
function puntuar_racimo(int $mejor_item, int $fuentes_distintas, array $conf): int
{
    $fuentes = max(0, min(5, $fuentes_distintas));

    return $mejor_item + $fuentes * (int) $conf['punt_por_fuente_racimo'];
}
