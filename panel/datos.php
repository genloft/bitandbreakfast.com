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

/**
 * Un candidato de la cola, listo para mandarse tal cual por api/candidatos.php.
 *
 * Pura -recibe lo que ya devuelven datos_cola() y datos_items_racimo(), no
 * hace ninguna consulta propia-, para poder probarla sin base de datos.
 */
function datos_formatear_candidato(array $racimo, array $items): array
{
    return [
        'id'           => (int) $racimo['id'],
        'titulo'       => (string) $racimo['titulo_representativo'],
        'puntuacion'   => (int) $racimo['puntuacion'],
        'fuentes'      => (int) $racimo['fuentes'],
        'primer_visto' => (string) $racimo['primer_visto'],
        'ultimo_visto' => (string) $racimo['ultimo_visto'],
        'items'        => array_map(
            static fn (array $item): array => [
                'titulo'         => (string) $item['titulo'],
                'url'            => (string) $item['url'],
                'fuente'         => (string) $item['fuente'],
                'idioma'         => (string) $item['idioma'],
                'resumen_origen' => (string) $item['resumen_origen'],
                'publicado'      => (string) $item['publicado'],
            ],
            $items
        ),
    ];
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

    // Hoy. Esto ya no es una edicion que sale los martes: es el cajon del dia,
    // donde se van dejando los bits segun se descubren para que el boletin de
    // mañana sepa que mandar. La web no lo nombra en ninguna parte.
    $fecha = gmdate('Y-m-d');

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
        // La fecha de la edicion es el dia que sale, no el que estaba previsto.
        // Una edicion que se cierra antes de tiempo -porque se ha llenado, o
        // porque el sitio no tenia nada publicado- se quedaba con la fecha del
        // martes siguiente, asi que la portada llevaba una fecha futura. Eso no
        // parece una primicia: parece un reloj mal puesto.
        $sql = "UPDATE ediciones
                   SET estado = 'cerrada',
                       fecha_prevista = LEAST(fecha_prevista, UTC_DATE())
                 WHERE id = ?";
        bd()->prepare($sql)->execute([$edicion_id]);
        // Con su dia puesto si no lo tenia. Los bits escritos a mano desde el
        // panel no pasan por el modo automatico, que es quien lo rellena, y un
        // bit sin dia no sale en ninguna parte: la web entera se ordena por el.
        bd()->prepare(
            "UPDATE bits
                SET estado = 'publicado',
                    dia = COALESCE(dia, DATE(creado))
              WHERE edicion_id = ?"
        )->execute([$edicion_id]);

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

/**
 * Como va el envio de la edicion que toca.
 *
 * Tres numeros, que son los que hacen falta para saber si hay que preocuparse:
 * cual va en camino, cuantos han salido y cuantos quedan.
 *
 * @return array ['edicion' => int, 'enviados' => int, 'pendientes' => int]
 */
function panel_estado_envio(): array
{
    $vacio = ['edicion' => 0, 'enviados' => 0, 'pendientes' => 0];

    try {
        $edicion = bd()->query(
            "SELECT id, numero FROM ediciones
              WHERE estado = 'cerrada' AND fecha_envio IS NULL
              ORDER BY numero ASC LIMIT 1"
        )->fetch();

        if ($edicion === false) {
            return $vacio;
        }

        $st = bd()->prepare('SELECT COUNT(*) FROM envios WHERE edicion_id = ?');
        $st->execute([(int) $edicion['id']]);
        $enviados = (int) $st->fetchColumn();

        $st = bd()->prepare(
            "SELECT COUNT(*) FROM suscriptores s
              LEFT JOIN envios en ON en.suscriptor_id = s.id AND en.edicion_id = ?
              WHERE s.estado = 'confirmado' AND s.rebotes < 3 AND en.suscriptor_id IS NULL"
        );
        $st->execute([(int) $edicion['id']]);

        return [
            'edicion'    => (int) $edicion['numero'],
            'enviados'   => $enviados,
            'pendientes' => (int) $st->fetchColumn(),
        ];
    } catch (Throwable $e) {
        // Sin las tablas del boletin todavia, no hay envio del que informar.
        return $vacio;
    }
}
