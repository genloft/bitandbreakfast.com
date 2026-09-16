<?php
/**
 * Decision de agrupacion: cuando dos noticias cuentan lo mismo.
 *
 * Como en lib/puntuar.php, aqui no hay base de datos: la consulta de
 * candidatos vive en cron/procesar.php y lo que se decide con ellos vive
 * aqui, que es la parte que se puede equivocar de verdad y la que tiene
 * pruebas.
 *
 * La regla tiene dos puertas:
 *
 *   umbral alto  - la similitud de titulares basta por si sola.
 *   umbral bajo  - la similitud es floja, pero las dos noticias mencionan los
 *                  mismos proveedores. Esta es la puerta que salva el caso
 *                  que la similitud no ve: la misma noticia en ingles y en
 *                  espanol da un Jaccard cercano a cero, porque los shingles
 *                  de tres caracteres no cruzan idiomas. Lo que si cruza es
 *                  que las dos hablen de Mews y de Cloudbeds.
 *
 * Y una tercera funcion, que no es una puerta sino una costura: un item puede
 * parecerse mucho a dos racimos distintos a la vez. Eso significa que esos dos
 * racimos cuentan lo mismo y nacieron separados porque, cuando llegaron, no
 * habia todavia un item que los uniera. agrupar_fusionables() los identifica y
 * cron/procesar.php los cose. Sin esto, la misma noticia acabaria dos veces en
 * la misma edicion, que es exactamente lo que este proyecto promete no hacer.
 */

declare(strict_types=1);

require_once __DIR__ . '/texto.php';

/** Por debajo de esto, dos similitudes se consideran la misma. */
const AGRUPAR_EPSILON = 0.0001;

/**
 * Proveedores del catalogo mencionados en un texto.
 *
 * @param array $alias Mapa 'alias ya normalizado' => id de proveedor.
 * @return array Ids de proveedor, sin repetir.
 */
function agrupar_proveedores_en(string $titulo, ?string $resumen, array $alias): array
{
    $aguja = ' ' . texto_normalizar($titulo . ' ' . (string) $resumen) . ' ';
    $ids   = [];

    foreach ($alias as $texto => $proveedor_id) {
        $texto = texto_normalizar((string) $texto);

        if ($texto === '') {
            continue;
        }

        if (str_contains($aguja, ' ' . $texto . ' ')) {
            $ids[(int) $proveedor_id] = true;
        }
    }

    $ids = array_keys($ids);
    sort($ids);

    return $ids;
}

/**
 * Decide a que racimo se engancha un item, si es que se engancha a alguno.
 *
 * @param array $candidatos Filas con 'racimo_id', 'similitud' y
 *                          'proveedores_comunes'.
 * @return array|null El candidato ganador, o null si hay que abrir racimo.
 */
function agrupar_elegir(array $candidatos, array $conf): ?array
{
    $alto           = (float) $conf['agrupar_umbral_alto'];
    $bajo           = (float) $conf['agrupar_umbral_bajo'];
    $min_proveedores = (int) $conf['agrupar_min_proveedores'];

    $ganador = null;

    foreach ($candidatos as $candidato) {
        $similitud  = (float) $candidato['similitud'];
        $comunes    = (int) ($candidato['proveedores_comunes'] ?? 0);

        $pasa = $similitud >= $alto
            || ($similitud >= $bajo && $comunes >= $min_proveedores);

        if (!$pasa) {
            continue;
        }

        if ($ganador === null || agrupar_mejor_que($candidato, $ganador)) {
            $ganador = $candidato;
        }
    }

    return $ganador;
}

/**
 * Desempata dos candidatos que ya han pasado el filtro.
 *
 * Primero la similitud; con la misma similitud, el que comparta mas
 * proveedores; y si tambien empatan, el racimo mas antiguo, para que la
 * noticia se acumule donde ya estaba en lugar de repartirse.
 */
function agrupar_mejor_que(array $candidato, array $actual): bool
{
    $sa = (float) $candidato['similitud'];
    $sb = (float) $actual['similitud'];

    // Nunca se comparan dos flotantes por identidad: texto_similitud redondea
    // a cuatro decimales, asi que dos cocientes iguales dan bits iguales hoy,
    // pero de eso no puede depender a que racimo va una noticia.
    if (abs($sa - $sb) > AGRUPAR_EPSILON) {
        return $sa > $sb;
    }

    $pa = (int) ($candidato['proveedores_comunes'] ?? 0);
    $pb = (int) ($actual['proveedores_comunes'] ?? 0);

    if ($pa !== $pb) {
        return $pa > $pb;
    }

    return (int) $candidato['racimo_id'] < (int) $actual['racimo_id'];
}

/**
 * Cuantos proveedores comparten dos listas de ids.
 */
function agrupar_proveedores_comunes(array $unos, array $otros): int
{
    return count(array_intersect($unos, $otros));
}

/**
 * Racimos que hay que fusionar con el elegido.
 *
 * Solo entran los que superan el umbral ALTO: la puerta de los proveedores
 * vale para enganchar un item a un racimo, pero no para declarar que dos
 * racimos enteros son la misma noticia. Ahi hace falta que los titulares se
 * parezcan de verdad, porque el error se paga caro: fusionar dos noticias
 * distintas las hace desaparecer a las dos de la cola.
 *
 * @param array $candidatos Los mismos que recibio agrupar_elegir().
 * @param int   $destino    Racimo ganador, al que se fusionan los demas.
 * @return array Ids de racimo a fusionar, sin repetir y sin el destino.
 */
function agrupar_fusionables(array $candidatos, int $destino, array $conf): array
{
    $alto = (float) $conf['agrupar_umbral_alto'];
    $ids  = [];

    foreach ($candidatos as $candidato) {
        $racimo_id = (int) $candidato['racimo_id'];

        if ($racimo_id === $destino || (float) $candidato['similitud'] < $alto) {
            continue;
        }

        $ids[$racimo_id] = true;
    }

    $ids = array_keys($ids);
    sort($ids);

    return $ids;
}
