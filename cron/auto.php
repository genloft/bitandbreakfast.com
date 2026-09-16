<?php
/**
 * Publicacion automatica: llena la edicion abierta y la cierra cuando toca.
 *
 * Se ejecuta desde cron/tareas.php, o directamente por linea de comandos:
 *   php cron/auto.php
 *
 * Coge los racimos candidatos mejor puntuados que todavia no tienen bit, les
 * escribe uno con el titular del racimo y el resumen de la propia fuente, y
 * los mete en la edicion abierta. Cuando la edicion se llena o le llega la
 * fecha, la cierra, y a partir de ahi el generador la publica.
 *
 * Se apaga con el ajuste auto_publicar a 0, y entonces el panel vuelve a ser
 * el unico camino. Los dos modos conviven sin pisarse: esto solo toca racimos
 * que no tienen bit, y nunca reescribe uno existente.
 *
 * El acceso a datos se reutiliza de panel/datos.php a proposito. Son las
 * mismas operaciones que hace el curador a mano -crear el bit, asignarlo,
 * cerrar la edicion- y duplicar ese SQL seria garantizar que un dia los dos
 * caminos dejan de comportarse igual.
 */

require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/texto.php';
require_once dirname(__DIR__) . '/lib/bits.php';
require_once dirname(__DIR__) . '/lib/auto.php';
require_once dirname(__DIR__) . '/panel/datos.php';

/**
 * Una pasada de publicacion automatica.
 *
 * @param float $limite Marca de tiempo a partir de la cual no se empiezan
 *                      racimos nuevos.
 */
function auto_publicar_lote(float $limite): array
{
    $resumen = ['estado' => 'nada que hacer', 'bits' => 0, 'cerrada' => 0];

    // Por defecto encendido: es lo que pidio el dueno del sitio. Se apaga
    // poniendo el ajuste auto_publicar a 0 desde la base de datos.
    if ((string) ajuste('auto_publicar', '1') !== '1') {
        $resumen['estado'] = 'desactivado';

        return $resumen;
    }

    $tope    = max(1, (int) ajuste('edicion_max_bits', '20'));
    $umbral  = (int) ajuste('auto_umbral', '30');
    $edicion = datos_edicion_abierta();
    $dentro  = count(datos_bits_edicion((int) $edicion['id']));

    foreach (auto_elegir(datos_cola($tope * 2), $umbral, $tope - $dentro) as $candidato) {
        if (microtime(true) >= $limite) {
            break;
        }

        try {
            auto_escribir_bit((int) $candidato['id'], (int) $edicion['id']);
            $resumen['bits']++;
            $dentro++;
        } catch (Throwable $e) {
            error_log('Bit & Breakfast, auto racimo ' . $candidato['id'] . ': ' . $e->getMessage());
        }
    }

    if (auto_toca_cerrar($dentro, $tope, (string) $edicion['fecha_prevista'], gmdate('Y-m-d'))) {
        datos_cerrar_edicion((int) $edicion['id']);
        $resumen['cerrada'] = (int) $edicion['numero'];
    }

    $resumen['estado'] = $resumen['bits'] > 0 || $resumen['cerrada'] > 0 ? 'publicado' : 'nada que hacer';

    return $resumen;
}

/**
 * Escribe el bit de un racimo y lo mete en la edicion.
 *
 * Todo dentro de una transaccion: un bit a medias en una edicion es peor que
 * un racimo que se queda esperando a la siguiente pasada.
 */
function auto_escribir_bit(int $racimo_id, int $edicion_id): void
{
    $racimo = datos_racimo($racimo_id);

    if ($racimo === null) {
        return;
    }

    $items = auto_items($racimo_id);

    bd()->beginTransaction();

    try {
        $bit_id = datos_crear_bit($racimo, $items);

        datos_guardar_bit($bit_id, [
            'titular'   => texto_recortar((string) $racimo['titulo_representativo'], BITS_TITULAR_MAX),
            'cuerpo'    => auto_cuerpo($items),
            // El "por que importa" se queda vacio a proposito: es un juicio
            // editorial y aqui no hay nadie para hacerlo. Inventarlo seria
            // exactamente lo que este proyecto dice no hacer.
            'por_que'   => '',
            'categoria' => auto_categoria($items),
            'madurez'   => 'anuncio',
            'tipo'      => auto_tipo($items),
            'estado'    => 'aprobado',
        ]);

        bd()->prepare("UPDATE bits SET redactado_por = 'ia', revisado = 0 WHERE id = ?")
            ->execute([$bit_id]);

        datos_asignar_bit($bit_id, $edicion_id);

        bd()->commit();
    } catch (Throwable $e) {
        if (bd()->inTransaction()) {
            bd()->rollBack();
        }

        throw $e;
    }
}

/**
 * Los items del racimo con lo que necesita el modo automatico: el nombre de
 * la fuente, su tipo y su categoria por defecto.
 */
function auto_items(int $racimo_id): array
{
    $sql = "SELECT i.id, i.titulo, i.url, i.publicado, i.puntuacion, i.resumen_origen,
                   f.nombre AS fuente, f.tipo, f.categoria_defecto, f.region, f.idioma
              FROM items i
              JOIN fuentes f ON f.id = i.fuente_id
             WHERE i.racimo_id = ? AND i.estado <> 'descartado'
             ORDER BY i.puntuacion DESC, i.id ASC";

    $st = bd()->prepare($sql);
    $st->execute([$racimo_id]);

    return $st->fetchAll();
}

// Ejecucion directa por linea de comandos, para poder probar sin esperar al
// cron. El mismo bloque que cierra cron/ingesta.php.
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {
    date_default_timezone_set('UTC');

    $resumen = auto_publicar_lote(microtime(true) + (float) (config('presupuesto_cron') ?? 25));

    printf(
        "auto: %s, %d bits escritos, edicion cerrada: %s\n",
        $resumen['estado'],
        $resumen['bits'],
        $resumen['cerrada'] > 0 ? (string) $resumen['cerrada'] : 'no'
    );
}
