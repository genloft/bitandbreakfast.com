<?php
/**
 * Consultas del panel de curacion.
 *
 * Todo el SQL del panel esta aqui, a la vista. panel/index.php decide que
 * pasa y plantillas/panel/ lo pinta; este fichero es el unico que habla con
 * la base.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/texto.php';
require_once dirname(__DIR__) . '/lib/bits.php';

/**
 * La cola: racimos candidatos, el de mas puntuacion primero.
 *
 * Se excluyen los que ya tienen un bit escrito, porque ya han pasado por aqui
 * y lo que queda de ellos esta en la edicion, no en la cola.
 */
function datos_cola(int $limite = 60): array
{
    $sql = "SELECT r.id, r.titulo_representativo, r.puntuacion, r.n_items,
                   r.primer_visto, r.ultimo_visto,
                   (SELECT COUNT(DISTINCT i.fuente_id)
                      FROM items i
                     WHERE i.racimo_id = r.id AND i.estado <> 'descartado') AS fuentes
              FROM racimos r
              LEFT JOIN bits b ON b.racimo_id = r.id
             WHERE r.estado = 'candidato'
               AND b.id IS NULL
             ORDER BY r.puntuacion DESC, r.ultimo_visto DESC
             LIMIT ?";

    $st = bd()->prepare($sql);
    $st->bindValue(1, $limite, PDO::PARAM_INT);
    $st->execute();

    return $st->fetchAll();
}

/**
 * Los items de un racimo, con su fuente. El mejor primero.
 */
function datos_items_racimo(int $racimo_id): array
{
    $sql = "SELECT i.id, i.titulo, i.url, i.publicado, i.puntuacion, i.idioma,
                   i.resumen_origen, f.nombre AS fuente, f.region, f.tipo
              FROM items i
              JOIN fuentes f ON f.id = i.fuente_id
             WHERE i.racimo_id = ? AND i.estado <> 'descartado'
             ORDER BY i.puntuacion DESC, i.id ASC";

    $st = bd()->prepare($sql);
    $st->execute([$racimo_id]);

    return $st->fetchAll();
}

function datos_racimo(int $id): ?array
{
    $st = bd()->prepare('SELECT * FROM racimos WHERE id = ?');
    $st->execute([$id]);

    return $st->fetch() ?: null;
}

/**
 * Descarta un racimo y sus items. El motivo se guarda: dentro de un mes,
 * "por que no salio esto" es una pregunta que se hace sola.
 */
function datos_descartar_racimo(int $id, string $motivo): void
{
    bd()->prepare("UPDATE racimos SET estado = 'descartado', motivo_descarte = ? WHERE id = ?")
        ->execute([texto_recortar($motivo, 110), $id]);

    bd()->prepare("UPDATE items SET estado = 'descartado' WHERE racimo_id = ?")
        ->execute([$id]);
}

/**
 * Crea un bit en borrador a partir de un racimo.
 *
 * El titular y el cuerpo se rellenan con lo que hay: el titular del racimo y
 * el resumen del mejor item, limpios de HTML. No es redaccion, es un punto de
 * partida para no mirar una caja vacia. Todo bit nace en borrador y ningun
 * borrador se publica.
 */
function datos_crear_bit(array $racimo, array $items): int
{
    $mejor  = $items[0] ?? [];
    $cuerpo = texto_recortar(
        trim(strip_tags(texto_limpiar_html((string) ($mejor['resumen_origen'] ?? '')))),
        600
    );

    $sql = "INSERT INTO bits (racimo_id, titular, cuerpo, por_que, categoria, estado, redactado_por)
            VALUES (?, ?, ?, '', 'tecnologia-general', 'borrador', 'humano')";

    bd()->prepare($sql)->execute([
        (int) $racimo['id'],
        texto_recortar((string) $racimo['titulo_representativo'], BITS_TITULAR_MAX),
        $cuerpo,
    ]);

    return (int) bd()->lastInsertId();
}

function datos_bit(int $id): ?array
{
    $st = bd()->prepare('SELECT * FROM bits WHERE id = ?');
    $st->execute([$id]);

    return $st->fetch() ?: null;
}

function datos_guardar_bit(int $id, array $bit): void
{
    $sql = 'UPDATE bits
               SET titular = ?, cuerpo = ?, por_que = ?, categoria = ?,
                   madurez = ?, tipo = ?, estado = ?
             WHERE id = ?';

    bd()->prepare($sql)->execute([
        $bit['titular'],
        $bit['cuerpo'],
        $bit['por_que'],
        $bit['categoria'],
        $bit['madurez'],
        $bit['tipo'],
        $bit['estado'],
        $id,
    ]);
}

function datos_borrar_bit(int $id): void
{
    bd()->prepare('DELETE FROM bits WHERE id = ?')->execute([$id]);
}

// -----------------------------------------------------------------------------
// Edicion semanal
// -----------------------------------------------------------------------------

/**
 * La edicion abierta. Si no hay ninguna, se abre una.
 *
 * Siempre hay exactamente una edicion abierta: es el cajon donde caen los
 * bits aprobados, y no tener ninguna obligaria a preguntar al curador algo
 * que se puede decidir solo.
 */
function datos_edicion_abierta(): array
{
    $abierta = bd()->query("SELECT * FROM ediciones WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1")->fetch();

    if ($abierta) {
        return $abierta;
    }

    $numero = (int) bd()->query('SELECT COALESCE(MAX(numero), 0) + 1 FROM ediciones')->fetchColumn();

    // El proximo martes, que es el dia de envio.
    $fecha = gmdate('Y-m-d', strtotime('next tuesday'));

    $sql = "INSERT INTO ediciones (numero, slug, titulo, fecha_prevista, estado)
            VALUES (?, ?, '', ?, 'abierta')";

    bd()->prepare($sql)->execute([$numero, bits_slug_edicion($numero, $fecha), $fecha]);

    $id = (int) bd()->lastInsertId();
    $st = bd()->prepare('SELECT * FROM ediciones WHERE id = ?');
    $st->execute([$id]);

    return $st->fetch();
}

/**
 * Los bits de una edicion, en orden, con la region de su racimo.
 *
 * La region sale de las fuentes que cuentan la noticia: si alguna es
 * espanola, cuenta como espanola; si no, europea; y si no, global. Es lo que
 * mide la cuota al cerrar.
 */
function datos_bits_edicion(int $edicion_id): array
{
    $sql = "SELECT b.*,
                   (SELECT f.region
                      FROM items i
                      JOIN fuentes f ON f.id = i.fuente_id
                     WHERE i.racimo_id = b.racimo_id
                     ORDER BY (f.region = 'es') DESC, (f.region = 'eu') DESC
                     LIMIT 1) AS region
              FROM bits b
             WHERE b.edicion_id = ?
             ORDER BY b.orden ASC, b.id ASC";

    $st = bd()->prepare($sql);
    $st->execute([$edicion_id]);

    return $st->fetchAll();
}

/**
 * Bits aprobados o en borrador que todavia no estan en ninguna edicion.
 */
function datos_bits_sueltos(): array
{
    return bd()->query(
        "SELECT id, titular, estado FROM bits
          WHERE edicion_id IS NULL AND estado <> 'publicado'
          ORDER BY creado DESC"
    )->fetchAll();
}

function datos_asignar_bit(int $bit_id, ?int $edicion_id): void
{
    if ($edicion_id === null) {
        bd()->prepare('UPDATE bits SET edicion_id = NULL, orden = 0 WHERE id = ?')->execute([$bit_id]);
        return;
    }

    $st = bd()->prepare('SELECT COALESCE(MAX(orden), 0) + 1 FROM bits WHERE edicion_id = ?');
    $st->execute([$edicion_id]);

    bd()->prepare('UPDATE bits SET edicion_id = ?, orden = ? WHERE id = ?')
        ->execute([$edicion_id, (int) $st->fetchColumn(), $bit_id]);
}

/**
 * Mueve un bit una posicion arriba o abajo intercambiando ordenes.
 */
function datos_mover_bit(int $bit_id, int $edicion_id, string $direccion): void
{
    $bits = datos_bits_edicion($edicion_id);
    $pos  = null;

    foreach ($bits as $i => $bit) {
        if ((int) $bit['id'] === $bit_id) {
            $pos = $i;
            break;
        }
    }

    if ($pos === null) {
        return;
    }

    $otro = $direccion === 'subir' ? $pos - 1 : $pos + 1;

    if ($otro < 0 || $otro >= count($bits)) {
        return;
    }

    // Se reescribe el orden entero de la edicion: con veinte bits cuesta lo
    // mismo que intercambiar dos, y no deja huecos ni empates arrastrados.
    $ids = array_column($bits, 'id');
    [$ids[$pos], $ids[$otro]] = [$ids[$otro], $ids[$pos]];

    $st = bd()->prepare('UPDATE bits SET orden = ? WHERE id = ?');

    foreach ($ids as $i => $id) {
        $st->execute([$i + 1, (int) $id]);
    }
}

/**
 * Cierra la edicion: los bits pasan a publicados y sus racimos salen de la
 * cola. A partir de aqui la edicion es materia del generador de la fase 4.
 */
function datos_cerrar_edicion(int $edicion_id): void
{
    bd()->beginTransaction();

    try {
        bd()->prepare("UPDATE ediciones SET estado = 'cerrada' WHERE id = ?")->execute([$edicion_id]);
        bd()->prepare("UPDATE bits SET estado = 'publicado' WHERE edicion_id = ?")->execute([$edicion_id]);

        $sql = "UPDATE racimos SET estado = 'publicado'
                 WHERE id IN (SELECT racimo_id FROM bits WHERE edicion_id = ? AND racimo_id IS NOT NULL)";
        bd()->prepare($sql)->execute([$edicion_id]);

        $sql = "UPDATE items SET estado = 'usado'
                 WHERE racimo_id IN (SELECT racimo_id FROM bits WHERE edicion_id = ? AND racimo_id IS NOT NULL)
                   AND estado = 'agrupado'";
        bd()->prepare($sql)->execute([$edicion_id]);

        bd()->commit();
    } catch (Throwable $e) {
        if (bd()->inTransaction()) {
            bd()->rollBack();
        }
        throw $e;
    }
}

/**
 * Ajustes de la edicion, con los mismos defectos que la semilla.
 */
function datos_conf_edicion(): array
{
    return [
        'edicion_max_bits'    => (int) ajuste('edicion_max_bits', '20'),
        'edicion_cuota_es_eu' => (int) ajuste('edicion_cuota_es_eu', '30'),
    ];
}
