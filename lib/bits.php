<?php
/**
 * Reglas de los bits y de la edicion semanal.
 *
 * Calculo puro, como lib/puntuar.php: ni base de datos ni sesion. Aqui esta
 * lo que hace que una edicion sea publicable o no, que es justo lo que hay
 * que poder probar sin montar medio sistema.
 *
 * El formato del bit no es negociable y por eso vive en constantes: la
 * promesa al lector son cinco minutos de lectura a la semana, y esa promesa
 * se cumple o se rompe aqui, no en el generador.
 */

declare(strict_types=1);

require_once __DIR__ . '/texto.php';

/** Un titular que no cabe en una linea de correo deja de ser un titular. */
const BITS_TITULAR_MAX = 120;

/** Palabras del cuerpo. Veinte bits por cien palabras son cinco minutos. */
const BITS_CUERPO_MIN = 25;
const BITS_CUERPO_MAX = 110;

/** El "por que importa" es una frase, no un parrafo. */
const BITS_PORQUE_MAX = 220;

/**
 * Categorias tematicas. Las mismas que usan las fuentes y el diccionario.
 *
 * "Las mismas" llevaba tiempo siendo mentira: el catalogo decia 'pms-gestion'
 * y 'distribucion-revenue', y las 48 fuentes y los 130 terminos del
 * diccionario decian 'pms-crs', 'distribucion-otas', 'revenue-rms',
 * 'pagos-fraude', 'operaciones-iot' e 'ia-aplicada'. Como una categoria que no
 * esta en el catalogo se ignora -y bien ignorada esta, porque luego no
 * filtra-, casi todo acababa en "Tecnologia general". Una edicion entera con
 * la misma etiqueta, que es como no tener etiquetas.
 *
 * Manda el vocabulario de las semillas: son cuarenta y ocho fuentes y ciento
 * treinta terminos contra ocho lineas, y ademas distingue mejor -pagos no es
 * lo mismo que ciberseguridad, y revenue no es lo mismo que distribucion-.
 */
function bits_categorias(): array
{
    return [
        'tecnologia-general'          => 'Tecnología general',
        'pms-crs'                     => 'PMS y CRS',
        'distribucion-otas'           => 'Distribución y OTAs',
        'revenue-rms'                 => 'Revenue y RMS',
        'pagos-fraude'                => 'Pagos y fraude',
        'ciberseguridad'              => 'Ciberseguridad',
        'cumplimiento'                => 'Cumplimiento normativo',
        'operaciones-iot'             => 'Operaciones e IoT',
        'experiencia-huesped'         => 'Experiencia del huésped',
        'ia-aplicada'                 => 'IA aplicada',
        'sostenibilidad-energia'      => 'Sostenibilidad y energía',
        'inversion-mercado'           => 'Inversión y mercado',
    ];
}

/**
 * La categoria buena a partir de cualquiera de sus nombres.
 *
 * Los bits escritos con el catalogo viejo siguen en la base de datos con la
 * categoria de entonces. Cambiar el catalogo sin esto los dejaria con una
 * etiqueta que ya no existe: sin nombre que ensenar y sin filtro que los
 * encuentre.
 *
 * @return string La categoria del catalogo, o '' si no se reconoce.
 */
function bits_categoria_canonica(string $categoria): string
{
    $categoria = trim($categoria);

    if (array_key_exists($categoria, bits_categorias())) {
        return $categoria;
    }

    $viejas = [
        'pms-gestion'                 => 'pms-crs',
        'distribucion-revenue'        => 'distribucion-otas',
        'operaciones-personal'        => 'operaciones-iot',
        // Un incidente y una multa son noticias distintas para un director de
        // sistemas: la primera la resuelve TI esta noche, la segunda la lee
        // legal. El combinado se parte en dos catalogo arriba; lo publicado
        // antes del reparto cae en ciberseguridad, el lado con mas volumen.
        'ciberseguridad-cumplimiento' => 'ciberseguridad',
    ];

    return $viejas[$categoria] ?? '';
}

/**
 * Qué es cada tema, en un párrafo evergreen: no cuenta lo que ha pasado
 * -eso ya lo hace la lista de bits de la propia ficha-, cuenta qué es y por
 * qué le importa a un hotel. No caduca -un PMS sigue siendo un PMS el año
 * que viene-, así que no lleva fecha ni fuente, igual que el glosario.
 *
 * @return string El párrafo, o '' si la categoría no se reconoce.
 */
function bits_categoria_descripcion(string $categoria): string
{
    $textos = [
        'tecnologia-general' => 'La categoría de lo que no encaja en ninguna otra: movimientos corporativos, tendencias de fondo y anuncios de producto que tocan a varios sistemas del hotel a la vez, no a uno solo. Sirve de cajón de sastre a propósito, porque forzar cada noticia a encajar en una categoría más estrecha habría sido peor que admitir que algunas cruzan varias.',
        'pms-crs' => 'El sistema del que cuelgan casi todos los demás: el PMS (Property Management System) lleva las reservas, el check-in, la asignación de habitaciones y la facturación de un hotel, y el CRS (Central Reservation System) centraliza esas reservas cuando hay varios canales de venta o varios hoteles de una misma cadena. Un cambio de PMS es la migración más temida del sector porque channel manager, RMS y cerraduras dependen de que hable con todos ellos.',
        'distribucion-otas' => 'Cómo llega la reserva hasta el hotel: agencias online como Booking o Expedia, el motor de reservas propio, el GDS heredado de las aerolíneas y, desde hace poco, la reserva agéntica -Booking y Expedia dentro de ChatGPT, protocolos como el Agentic Commerce Protocol-. La tensión de fondo es siempre la misma: cuánta comisión se paga por traer al huésped frente a cuánto cuesta traerlo por cuenta propia.',
        'revenue-rms' => 'La ciencia de poner precio a una habitación que caduca cada noche que no se vende. Un RMS (Revenue Management System) ajusta tarifas solo, mirando ocupación, fechas y competencia, y los indicadores del oficio -ADR, RevPAR, ocupación- son el idioma en el que un director de revenue mide si le va bien o mal.',
        'pagos-fraude' => 'Cobrar sin que el dinero se pierda por el camino: pasarelas de pago, cumplimiento de PCI DSS -obligatorio para poder procesar tarjetas-, contracargos cuando un banco devuelve un cargo a la fuerza, y el fraude que aparece en cuanto una reserva se puede hacer sin pisar el mostrador.',
        'ciberseguridad' => 'Incidentes técnicos que se resuelven esta noche, no en un juzgado: ransomware, brechas de datos, vulnerabilidades explotadas en el software que usa un hotel. Un PMS cifrado no permite ni hacer check-in a mano, y es uno de los pocos sectores sin un "modo sin sistemas" al que volver, lo que explica por qué el coste medio de una brecha en hostelería sube mientras la media de otros sectores baja.',
        'cumplimiento' => 'Lo que exige la ley y no el mercado: Verifactu y la facturación electrónica, SES.Hospedajes y el registro de viajeros, el RGPD y los datos personales que guarda cualquier recepción, la accesibilidad digital del motor de reservas. A diferencia de la ciberseguridad, aquí el plazo lo pone un boletín oficial, no un atacante, y por eso tiene su propio calendario en /cumplimiento.html.',
        'operaciones-iot' => 'Lo que pasa puertas adentro, fuera de la recepción: cerraduras conectadas, termostatos y contadores de energía, aplicaciones de housekeeping, mantenimiento predictivo. Es la categoría que menos titulares genera y más horas de trabajo ahorra cuando funciona bien.',
        'experiencia-huesped' => 'Todo lo que el huésped toca directamente: chatbots de atención, check-in sin mostrador, mensajería durante la estancia, venta adicional -upselling- automatizada. La frontera con Operaciones es que aquí el sistema habla con el huésped, no solo con el hotel.',
        'ia-aplicada' => 'La inteligencia artificial generativa aplicada a un caso concreto del sector: chatbots, fijación de precios, planificación de viaje del lado del huésped. La adopción va casi siempre por delante de la medición -la mayoría de hoteles usa o adquiere IA generativa, pero muy pocos miden si de verdad cambia algo-, y esa brecha entre adoptar y medir es, en sí misma, la noticia recurrente de esta categoría.',
        'sostenibilidad-energia' => 'El consumo energético y la huella ambiental de un hotel, y la tecnología que los mide o los reduce: sistemas de gestión energética (BMS/BEMS), certificaciones, informes de sostenibilidad que empiezan a pedir tanto viajeros como reguladores.',
        'inversion-mercado' => 'El negocio detrás de la tecnología: rondas de financiación, adquisiciones, movimientos de capital en el sector hotelero y en sus proveedores. No cualquier ronda -las de empresas que un hotel nunca va a contratar quedan fuera-, solo la que mueve algo que un hotel pueda llegar a usar.',
    ];

    return $textos[$categoria] ?? '';
}

function bits_madureces(): array
{
    return [
        'rumor'      => 'Rumor',
        'anuncio'    => 'Anuncio',
        'disponible' => 'Disponible',
        'implantado' => 'Implantado',
    ];
}

function bits_tipos(): array
{
    return [
        'producto'   => 'Producto',
        'inversion'  => 'Inversión',
        'regulacion' => 'Regulación',
        'caso_real'  => 'Caso real',
        'incidente'  => 'Incidente',
    ];
}

/**
 * Valida un bit antes de guardarlo. Devuelve la lista de errores.
 *
 * Los limites de longitud se comprueban en caracteres para el titular, que es
 * lo que ocupa sitio en la pantalla, y en palabras para el cuerpo, que es lo
 * que cuesta tiempo de lectura.
 */
function bits_validar(array $bit): array
{
    $errores = [];

    $titular = trim((string) ($bit['titular'] ?? ''));
    $cuerpo  = trim((string) ($bit['cuerpo'] ?? ''));
    $porque  = trim((string) ($bit['por_que'] ?? ''));

    if ($titular === '') {
        $errores[] = 'El titular no puede quedarse vacío.';
    } elseif (mb_strlen($titular) > BITS_TITULAR_MAX) {
        $errores[] = sprintf(
            'El titular tiene %d caracteres y el máximo son %d.',
            mb_strlen($titular),
            BITS_TITULAR_MAX
        );
    }

    $palabras = texto_contar_palabras($cuerpo);

    if ($palabras < BITS_CUERPO_MIN) {
        $errores[] = sprintf(
            'El cuerpo tiene %d palabras y hacen falta al menos %d.',
            $palabras,
            BITS_CUERPO_MIN
        );
    } elseif ($palabras > BITS_CUERPO_MAX) {
        $errores[] = sprintf(
            'El cuerpo tiene %d palabras y el máximo son %d.',
            $palabras,
            BITS_CUERPO_MAX
        );
    }

    if ($porque === '') {
        $errores[] = 'Falta el "por qué importa": es lo que distingue un radar de un agregador.';
    } elseif (mb_strlen($porque) > BITS_PORQUE_MAX) {
        $errores[] = sprintf(
            'El "por qué importa" tiene %d caracteres y el máximo son %d.',
            mb_strlen($porque),
            BITS_PORQUE_MAX
        );
    }

    if (!array_key_exists((string) ($bit['categoria'] ?? ''), bits_categorias())) {
        $errores[] = 'La categoría no es una de las del catálogo.';
    }

    if (!array_key_exists((string) ($bit['madurez'] ?? ''), bits_madureces())) {
        $errores[] = 'La madurez no es una de las del catálogo.';
    }

    if (!array_key_exists((string) ($bit['tipo'] ?? ''), bits_tipos())) {
        $errores[] = 'El tipo no es uno de los del catálogo.';
    }

    return $errores;
}

/**
 * Revisa una edicion entera antes de cerrarla.
 *
 * Devuelve ['errores' => [...], 'avisos' => [...], 'palabras' => int]. Los
 * errores impiden cerrar; los avisos solo se enseñan. La distincion importa:
 * la cuota de fuentes en espanol es una intencion editorial, no una regla, y
 * una semana floja en Europa no puede bloquear el envio.
 *
 * @param array $bits  Filas con 'estado', 'cuerpo' y 'region'.
 * @param array $conf  edicion_max_bits y edicion_cuota_es_eu.
 */
function bits_revisar_edicion(array $bits, array $conf): array
{
    $errores   = [];
    $avisos    = [];
    $palabras  = 0;
    $es_eu     = 0;
    $borradores = 0;

    // El recorrido no se corta en el primer borrador: los recuentos de abajo
    // tienen que mirar la edicion entera para que el aviso de cuota no salga
    // calculado a medias.
    foreach ($bits as $bit) {
        $palabras += texto_contar_palabras((string) ($bit['cuerpo'] ?? ''));

        if (in_array((string) ($bit['region'] ?? ''), ['es', 'eu'], true)) {
            $es_eu++;
        }

        if ((string) ($bit['estado'] ?? '') === 'borrador') {
            $borradores++;
        }
    }

    if ($borradores > 0) {
        $errores[] = sprintf(
            'Quedan %d bits en borrador: apruébalos todos antes de cerrar.',
            $borradores
        );
    }

    $total = count($bits);
    $tope  = (int) ($conf['edicion_max_bits'] ?? 20);
    $cuota = (int) ($conf['edicion_cuota_es_eu'] ?? 30);

    if ($total === 0) {
        $errores[] = 'La edición no tiene ni un bit.';
    }

    if ($total > $tope) {
        $errores[] = sprintf('La edición tiene %d bits y el tope son %d.', $total, $tope);
    }

    if ($total > 0 && $cuota > 0 && ($es_eu * 100) < ($cuota * $total)) {
        $avisos[] = sprintf(
            'Solo %d de %d bits vienen de fuentes españolas o europeas; la intención es al menos el %d%%.',
            $es_eu,
            $total,
            $cuota
        );
    }

    // Cinco minutos a 200 palabras por minuto son mil palabras largas.
    if ($palabras > 1200) {
        $avisos[] = sprintf(
            'La edición son %d palabras, más de los cinco minutos que promete la cabecera.',
            $palabras
        );
    }

    return ['errores' => $errores, 'avisos' => $avisos, 'palabras' => $palabras];
}

/**
 * Slug de una edicion a partir de su numero y su fecha: 2026-w38-bits.
 */
function bits_slug_edicion(int $numero, string $fecha): string
{
    // La fecha se interpreta en UTC y se formatea en UTC. Mezclar strtotime,
    // que usa la zona de PHP, con gmdate, que no, hace que en Madrid una
    // fecha a medianoche caiga en el dia anterior y cambie de semana.
    $tiempo = strtotime($fecha . ' UTC');
    $tiempo = $tiempo === false ? time() : $tiempo;

    // 'o' y no 'Y': el año de la semana ISO, que en fin de año no
    // coincide con el del calendario y produciria slugs como 2027-w53.
    return sprintf('%s-w%s-%03d', gmdate('o', $tiempo), gmdate('W', $tiempo), $numero);
}
