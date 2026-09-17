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
require_once dirname(__DIR__) . '/lib/traducir.php';
require_once dirname(__DIR__) . '/lib/texto.php';
require_once dirname(__DIR__) . '/lib/bits.php';
require_once dirname(__DIR__) . '/lib/auto.php';
require_once dirname(__DIR__) . '/lib/puntuar.php';
require_once dirname(__DIR__) . '/panel/datos.php';

/**
 * Una pasada de publicacion automatica.
 *
 * @param float $limite Marca de tiempo a partir de la cual no se empiezan
 *                      racimos nuevos.
 */
function auto_publicar_lote(float $limite): array
{
    $resumen = ['estado' => 'nada que hacer', 'bits' => 0, 'descartados' => 0, 'revisados' => 0, 'cerrada' => 0];

    // Por defecto encendido: es lo que pidio el dueno del sitio. Se apaga
    // poniendo el ajuste auto_publicar a 0 desde la base de datos.
    if ((string) ajuste('auto_publicar', '1') !== '1') {
        $resumen['estado'] = 'desactivado';

        return $resumen;
    }

    // Antes de escribir nada, se revisa lo que ya se publico sin mirar. Se le
    // da la mitad del presupuesto: es trabajo de mantenimiento y no puede
    // comerse la pasada entera.
    $resumen['revisados'] = auto_revisar_publicados(
        microtime(true) + max(1.0, ($limite - microtime(true)) / 2)
    );

    // Ya no es un limite editorial -la portada es un rio y cabe todo lo que
    // pase las puertas-, sino un seguro: si un dia las puertas fallan, que no
    // se publiquen mil entradas de una agencia antes de que nadie lo note.
    $tope     = max(1, (int) ajuste('edicion_max_bits', '200'));
    $umbral   = (int) ajuste('auto_umbral', '30');
    $minimo   = (int) ajuste('auto_min_diccionario', '8');
    $terminos = auto_terminos();
    $edicion  = datos_edicion_abierta();
    $dentro   = count(datos_bits_edicion((int) $edicion['id']));

    // Se miran mas candidatos que huecos: muchos se caen por no hablar de
    // tecnologia hotelera, y si solo se pidieran los justos la edicion se
    // quedaria a medias.
    $resumen['descartados'] = 0;

    foreach (auto_elegir(datos_cola($tope * 5), $umbral, ($tope - $dentro) * 4) as $candidato) {
        if (microtime(true) >= $limite || $dentro >= $tope) {
            break;
        }

        try {
            $hecho = auto_escribir_bit((int) $candidato['id'], (int) $edicion['id'], $terminos, $minimo);

            if ($hecho) {
                $resumen['bits']++;
                $dentro++;
            } else {
                $resumen['descartados']++;
            }
        } catch (Throwable $e) {
            error_log('Bit & Breakfast, auto racimo ' . $candidato['id'] . ': ' . $e->getMessage());
        }
    }

    // Si no hay ninguna edicion publicada, la portada esta vacia ahora mismo y
    // esperar al martes son seis dias de nada.
    $publicadas = (int) bd()->query("SELECT COUNT(*) FROM ediciones WHERE estado <> 'abierta'")->fetchColumn();
    $suelo      = max(1, (int) ajuste('edicion_min_bits', '6'));

    if (auto_toca_cerrar(
        $dentro,
        $tope,
        (string) $edicion['fecha_prevista'],
        gmdate('Y-m-d'),
        $publicadas > 0,
        $suelo
    )) {
        datos_cerrar_edicion((int) $edicion['id']);
        $resumen['cerrada'] = (int) $edicion['numero'];
    }

    $resumen['estado'] = $resumen['bits'] > 0 || $resumen['cerrada'] > 0 || $resumen['revisados'] > 0
        ? 'publicado'
        : 'nada que hacer';

    return $resumen;
}

/**
 * Vuelve a pasar por el filtro lo que se publico solo con criterios viejos.
 *
 * Corre una sola vez por cada version de criterios. Lo que ya no cumple se
 * retira y su racimo se descarta; lo que sigue valiendo se reescribe con las
 * reglas de ahora, que limpian la coletilla del feed, clasifican por el
 * diccionario y prefieren el titular en espanol.
 *
 * Solo toca bits sin revisar escritos en automatico: lo que haya pasado por
 * manos humanas no se toca jamas.
 *
 * @return int Cuantos bits se han revisado.
 */
function auto_revisar_publicados(float $limite): int
{
    if ((int) ajuste('auto_criterios', '0') >= AUTO_CRITERIOS) {
        return 0;
    }

    $terminos = auto_terminos();
    $minimo   = (int) ajuste('auto_min_diccionario', '8');

    // Con puntero y por lotes, como todo lo demas del sistema. Esta es la
    // unica tarea que BORRA en vez de anadir, y antes se cargaba todos los
    // bits de golpe sin mirar el reloj: si el proceso moria a la mitad -y en
    // alojamiento compartido muere-, la version de criterios no llegaba a
    // guardarse y la pasada siguiente volvia a empezar desde el principio,
    // borrando otro trozo cada vez. Un bucle destructivo sin final.
    $desde     = (int) ajuste('auto_criterios_puntero', '0');
    $ediciones = [];
    $tocados   = 0;
    $quedan    = true;

    while (microtime(true) < $limite) {
        $st = bd()->prepare(
            "SELECT b.id, b.racimo_id, b.edicion_id, b.estado,
                    (e.fecha_envio IS NULL) AS sin_enviar
               FROM bits b
               LEFT JOIN ediciones e ON e.id = b.edicion_id
              WHERE b.redactado_por = 'ia' AND b.revisado = 0 AND b.racimo_id IS NOT NULL
                AND b.id > ?
              ORDER BY b.id
              LIMIT 25"
        );
        $st->execute([$desde]);
        $bits = $st->fetchAll();

        if (!$bits) {
            $quedan = false;
            break;
        }

        foreach ($bits as $bit) {
            $ediciones[(int) $bit['edicion_id']] = true;
            $desde = (int) $bit['id'];

            if (auto_revisar_bit($bit, $terminos, $minimo)) {
                $tocados++;
            }

            // El puntero se guarda por bit: lo revisado no se vuelve a tocar
            // aunque el proceso se muera en la linea siguiente.
            ajuste_guardar('auto_criterios_puntero', (string) $desde);

            if (microtime(true) >= $limite) {
                break 2;
            }
        }
    }

    // Una edicion que se ha quedado sin nada no puede seguir publicada: seria
    // una portada vacia. Se borra y el modo automatico abrira otra.
    foreach (array_keys($ediciones) as $edicion_id) {
        if ($edicion_id <= 0) {
            continue;
        }

        $st = bd()->prepare('SELECT COUNT(*) FROM bits WHERE edicion_id = ?');
        $st->execute([$edicion_id]);

        if ((int) $st->fetchColumn() === 0) {
            bd()->prepare('DELETE FROM ediciones WHERE id = ?')->execute([$edicion_id]);
        }
    }

    // La web se regenera: los bits revisados han cambiado de titular, de
    // cuerpo y de categoria. Tambien si la revision se ha quedado a medias,
    // porque lo que ya se ha tocado hay que ensenarlo.
    if ($tocados > 0 || !$quedan) {
        ajuste_guardar('publicar_firma', '');
    }

    // Y solo cuando no queda ninguno por mirar se da la version por aplicada.
    if (!$quedan) {
        auto_fechar_ediciones();
        ajuste_guardar('auto_criterios', (string) AUTO_CRITERIOS);
        ajuste_guardar('auto_criterios_puntero', '0');
    }

    return $tocados;
}

/**
 * Pone a las ediciones ya cerradas la fecha del dia en que salieron.
 *
 * Una edicion que se cerro antes de tiempo se quedo con la fecha del martes
 * que tenia prevista, asi que la portada lleva una fecha futura. No parece una
 * primicia: parece un reloj mal puesto. Como el dia exacto del cierre no se
 * guarda, se usa el del ultimo bit que entro, que es el mismo dia salvo que
 * alguien lo cerrara a mano dias despues.
 */
function auto_fechar_ediciones(): void
{
    $sql = "UPDATE ediciones e
               SET e.fecha_prevista = COALESCE(
                       (SELECT DATE(MAX(b.creado)) FROM bits b WHERE b.edicion_id = e.id),
                       e.fecha_prevista
                   )
             WHERE e.estado <> 'abierta'
               AND e.fecha_prevista > UTC_DATE()";

    bd()->exec($sql);
}

/**
 * Revisa un bit automatico: lo reescribe si sigue valiendo, lo retira si no.
 *
 * @return bool true si se ha reescrito, false si se ha retirado.
 */
function auto_revisar_bit(array $bit, array $terminos, int $minimo): bool
{
    $racimo = datos_racimo((int) $bit['racimo_id']);
    $items  = auto_espanol_primero(auto_items((int) $bit['racimo_id']));
    $cuerpo = auto_cuerpo($items);

    $titular = $racimo !== null
        ? auto_titular((string) $racimo['titulo_representativo'], auto_medios($items))
        : '';
    $cuerpo  = auto_sin_titular($cuerpo, $titular);
    $senal   = puntuar_diccionario($titular, $cuerpo, $terminos, 100);

    // Los filtros de forma -recopilatorio, promocional, guia- son heuristicas:
    // aciertan lo justo para descartar un candidato, que no cuesta nada, y no
    // lo bastante para retirar algo que ya ha salido por correo. Una edicion
    // enviada es un hecho consumado y no se toca; una que todavia no se ha
    // mandado a nadie si se puede corregir, y mas vale corregirla.
    $forma = (bool) ($bit['sin_enviar'] ?? false)
        && ((auto_solo_espanol() && !auto_hay_espanol($items))
            || auto_es_recopilatorio($titular)
            || auto_es_promocional($titular . ' ' . $cuerpo)
            || auto_es_didactico($titular)
            || auto_es_entrevista($titular)
            || !auto_es_del_sector($titular . ' ' . $cuerpo));

    if ($racimo === null
        || $senal['puntos'] < $minimo
        || texto_contar_palabras($cuerpo) < BITS_CUERPO_MIN
        || $forma
    ) {
        datos_borrar_bit((int) $bit['id']);

        if ($racimo !== null) {
            bd()->prepare("UPDATE racimos SET estado = 'descartado', motivo_descarte = ? WHERE id = ?")
                ->execute(['automatico: no pasa los criterios nuevos', (int) $racimo['id']]);
        }

        return false;
    }

    $categoria = auto_categoria_diccionario($titular . ' ' . $cuerpo, $terminos);

    datos_guardar_bit((int) $bit['id'], [
        'titular'   => texto_recortar($titular, BITS_TITULAR_MAX),
        'cuerpo'    => $cuerpo,
        'por_que'   => '',
        'categoria' => $categoria !== '' ? $categoria : auto_categoria($items),
        'madurez'   => 'anuncio',
        'tipo'      => auto_tipo($items),
        // Se revisa el contenido, no el calendario: un bit que espera en la
        // edicion abierta sigue esperando. Ponerlos todos en 'publicado' los
        // daba por salidos sin que su edicion se hubiera cerrado.
        'estado'    => (string) ($bit['estado'] ?? 'aprobado'),
    ]);

    return true;
}

/**
 * Escribe el bit de un racimo y lo mete en la edicion.
 *
 * Todo dentro de una transaccion: un bit a medias en una edicion es peor que
 * un racimo que se queda esperando a la siguiente pasada.
 */
function auto_escribir_bit(int $racimo_id, int $edicion_id, array $terminos, int $minimo): bool
{
    $racimo = datos_racimo($racimo_id);

    if ($racimo === null) {
        return false;
    }

    $items   = auto_espanol_primero(auto_items($racimo_id));
    $cuerpo  = auto_cuerpo($items);
    $solo_es = auto_solo_espanol();
    $titular = auto_titular((string) $racimo['titulo_representativo'], auto_medios($items));
    $cuerpo  = auto_sin_titular($cuerpo, $titular);
    $texto   = $titular . ' ' . $cuerpo;

    // Dos filtros que el umbral de puntuacion no cubre, y que son la
    // diferencia entre un radar y un tablon de novedades del sector:
    //
    //   1. Tiene que hablar de tecnologia hotelera. La puntuacion se la puede
    //      ganar un medio con peso publicando una entrevista o un congreso;
    //      si el diccionario no reconoce nada, no es para este boletin.
    //   2. Tiene que tener cuerpo. Un bit que solo dice quien lo publica no
    //      le ahorra el clic a nadie.
    //   3. Tiene que contar una sola noticia. Hay boletines que meten cinco en
    //      una entrada del feed, y ese titular no es un bit: es un indice.
    //   4. Tiene que ser una noticia y no un libro blanco, un seminario o una
    //      guia descargable. El diccionario no los distingue porque hablan de
    //      lo mismo; para el lector, detras hay un formulario, no una noticia.
    //   5. Ni un tutorial ni una columna. Media tecnologia hotelera publica su
    //      marketing en el mismo feed que sus notas, y una guia no caduca: si
    //      entra una vez entra siempre, desplazando a lo que si ha pasado.
    //   6. Ni una entrevista: no es un hecho, es la opinion de alguien que
    //      vende algo, y el bit no la puede resumir sin volverse su titular.
    //   7. Y tiene que hablar de hoteles. El diccionario puntua palabras, no
    //      contextos: una vulnerabilidad de Chrome puntua igual en una noticia
    //      sobre un PMS que en una sobre Outlook.
    //   8. Y tiene que poder leerse en espanol. Si nadie la cuenta en espanol
    //      se traduce, y el bit lo dice en su cara. Sin traductor configurado
    //      esto vuelve a ser una puerta cerrada, que es como estaba antes.
    $senal = puntuar_diccionario($titular, $cuerpo, $terminos, 100);

    // El idioma ya no descarta si hay traductor: se traduce y se dice. La
    // puerta sigue ahi para cuando no lo hay, que es como estaba el sitio
    // hasta ahora -y como vuelve a estar si se borra la clave-.
    $traducible = !auto_hay_espanol($items) && traducir_configurado();

    $motivo = match (true) {
        $solo_es && !$traducible && !auto_hay_espanol($items)
                                                         => 'automatico: no lo cuenta nadie en espanol',
        $senal['puntos'] < $minimo                        => 'automatico: sin senal tematica',
        texto_contar_palabras($cuerpo) < BITS_CUERPO_MIN  => 'automatico: sin resumen utilizable',
        auto_es_recopilatorio($titular)                   => 'automatico: recopilatorio, no una noticia',
        auto_es_promocional($titular . ' ' . $cuerpo)     => 'automatico: material promocional',
        auto_es_didactico($titular)                       => 'automatico: guia, no noticia',
        auto_es_entrevista($titular)                      => 'automatico: entrevista',
        !auto_es_del_sector($texto)                       => 'automatico: no habla de hoteles',
        default                                           => '',
    };

    if ($motivo !== '') {
        // Se saca de la cola con el motivo escrito: si no, se volveria a
        // evaluar en cada pasada y taparia a los que si valen.
        bd()->prepare("UPDATE racimos SET estado = 'descartado', motivo_descarte = ? WHERE id = ?")
            ->execute([$motivo, $racimo_id]);

        return false;
    }

    bd()->beginTransaction();

    try {
        $bit_id = datos_crear_bit($racimo, $items);

        $categoria = auto_categoria_diccionario($texto, $terminos);

        // Traducir es lo ultimo que se hace, cuando ya ha pasado todas las
        // puertas: hacerlo antes seria gastar cuota en las novecientas
        // noticias que se van a descartar de todas formas.
        $origen    = $traducible ? auto_idioma_principal($items) : '';
        $traducido = $traducible
            ? auto_traducir($titular, $cuerpo, $origen)
            : ['ok' => false];

        if ($traducido['ok']) {
            $titular = $traducido['titular'];
            $cuerpo  = $traducido['cuerpo'];
        }

        datos_guardar_bit($bit_id, [
            'titular'   => texto_recortar($titular, BITS_TITULAR_MAX),
            'cuerpo'    => $cuerpo,
            // El "por que importa" se queda vacio a proposito: es un juicio
            // editorial y aqui no hay nadie para hacerlo. Inventarlo seria
            // exactamente lo que este proyecto dice no hacer.
            'por_que'   => '',
            // El diccionario sabe de que va la noticia; la fuente solo sabe de
            // que suele ir. Se prefiere el primero y se cae al segundo.
            'categoria' => $categoria !== '' ? $categoria : auto_categoria($items),
            'madurez'   => 'anuncio',
            'tipo'      => auto_tipo($items),
            // Publicado al escribirse, no al cerrar nada. Antes se quedaba en
            // 'aprobado' esperando a que su edicion cerrara, asi que todo lo
            // que el radar encontraba hoy no se veia hasta mañana. Un
            // agregador que esconde lo de hoy no es un agregador.
            'estado'    => 'publicado',
        ]);

        // El dia en que este sitio se entero. Es lo que ordena la web entera.
        // Y de que idioma viene, si viene de otro: la web lo dice en la cara
        // del bit, porque esas palabras no son las que escribio el periodista.
        bd()->prepare(
            "UPDATE bits
                SET redactado_por = 'ia',
                    revisado = 0,
                    dia = UTC_DATE(),
                    traducido_de = ?
              WHERE id = ?"
        )->execute([$traducido['ok'] ? $origen : null, $bit_id]);

        // Y el racimo deja de estar en la cola: ya tiene quien lo cuente.
        bd()->prepare("UPDATE racimos SET estado = 'publicado' WHERE id = ?")
            ->execute([$racimo_id]);

        // El cajon del dia. La web no lo nombra en ninguna parte; existe
        // porque el boletin necesita saber que mando ayer y a quien.
        datos_asignar_bit($bit_id, $edicion_id);

        bd()->commit();

        return true;
    } catch (Throwable $e) {
        if (bd()->inTransaction()) {
            bd()->rollBack();
        }

        throw $e;
    }
}

/**
 * El diccionario entero, con su categoria, para clasificar y para medir si la
 * noticia habla de lo que tiene que hablar.
 */
function auto_terminos(): array
{
    return bd()->query(
        'SELECT termino, peso, categoria FROM diccionario WHERE activo = 1'
    )->fetchAll();
}

/**
 * ¿Solo se publica lo que alguien cuente en espanol?
 *
 * Encendido por defecto, que es lo que se pidio. Se apaga con el ajuste
 * auto_solo_espanol a 0, y entonces vuelven a entrar los titulares en ingles
 * con su etiqueta.
 */
function auto_solo_espanol(): bool
{
    return (string) ajuste('auto_solo_espanol', '1') === '1';
}

/**
 * El idioma del que habria que traducir: el del item que manda.
 *
 * El mismo orden con el que se elige el titular, porque es de ese item de
 * donde sale el texto. Con otro orden se le pediria al traductor que tradujera
 * del aleman un titular que esta en ingles.
 */
function auto_idioma_principal(array $items): string
{
    foreach ($items as $item) {
        $idioma = trim((string) ($item['idioma'] ?? ''));

        if ($idioma !== '') {
            return substr($idioma, 0, 2);
        }
    }

    return '';
}

/**
 * Traduce titular y cuerpo de una vez.
 *
 * Los dos en la misma peticion: DeepL cobra por caracter pero cuesta por
 * viaje. Si algo falla -la cuota, la red, una respuesta rara- se devuelve que
 * no y el bit se publica en su idioma: mejor una noticia en ingles y
 * etiquetada que ninguna noticia.
 *
 * @return array ['ok' => bool, 'titular' => string, 'cuerpo' => string]
 */
function auto_traducir(string $titular, string $cuerpo, string $origen): array
{
    $traduccion = traducir_textos([$titular, $cuerpo], $origen);

    if (!$traduccion['ok']) {
        if ($traduccion['mensaje'] !== '') {
            error_log('Bit & Breakfast, traductor: ' . $traduccion['mensaje']);
        }

        return ['ok' => false, 'titular' => $titular, 'cuerpo' => $cuerpo];
    }

    $nuevo_titular = trim((string) ($traduccion['textos'][0] ?? ''));
    $nuevo_cuerpo  = trim((string) ($traduccion['textos'][1] ?? ''));

    // Una traduccion vacia es peor que no traducir: se queda el original.
    if ($nuevo_titular === '' || $nuevo_cuerpo === '') {
        return ['ok' => false, 'titular' => $titular, 'cuerpo' => $cuerpo];
    }

    return ['ok' => true, 'titular' => $nuevo_titular, 'cuerpo' => $nuevo_cuerpo];
}

/**
 * Los items del racimo con lo que necesita el modo automatico: el nombre de
 * la fuente, su tipo y su categoria por defecto.
 */
function auto_items(int $racimo_id): array
{
    // El idioma del item y no el de la fuente: hay medios que publican en dos, y
    // lo que decide si el lector puede leer el bit es el idioma de la noticia.
    $sql = "SELECT i.id, i.titulo, i.url, i.publicado, i.puntuacion, i.resumen_origen,
                   f.nombre AS fuente, f.tipo, f.categoria_defecto, f.region,
                   COALESCE(NULLIF(i.idioma, ''), f.idioma) AS idioma
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
