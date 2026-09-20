<?php
/**
 * Las siglas del sector, en una frase cada una.
 *
 * Vive aquí y no en plantillas/web/glosario.php -a diferencia de $grupos en
 * estadisticas.php antes de esta misma revisión- porque plantillas/web/tema.php
 * necesita poder preguntar qué siglas pertenecen a un tema sin ejecutar la
 * plantilla del glosario entera. Mismo patrón que lib/cumplimiento.php.
 *
 * Como el glosario, no caducan -un PMS sigue siendo un PMS el año que
 * viene- y por eso no llevan fecha de revisión ni aviso de mantenimiento.
 */

declare(strict_types=1);

/**
 * Todos los términos del glosario, alfabético por sigla.
 *
 * Cada uno puede enlazar a un tema (el mismo catálogo de bits_categorias())
 * para quien quiera ver qué se ha publicado sobre eso, no solo qué
 * significa.
 */
function glosario_terminos(): array
{
    return [
        [
            'sigla'   => 'ADR',
            'nombre'  => 'Average Daily Rate',
            'tema'    => 'revenue-rms',
            'definicion' => 'La tarifa media diaria por habitación vendida. Sube o baja con el precio, no con la ocupación: un hotel puede llenar más habitaciones y tener un ADR más bajo si las vende más baratas.',
        ],
        [
            'sigla'   => 'API',
            'nombre'  => 'Application Programming Interface',
            'tema'    => null,
            'definicion' => 'La puerta por la que dos programas se hablan sin intervención humana. Cuando un PMS "se integra" con un channel manager, casi siempre es una API la que hace el trabajo.',
        ],
        [
            'sigla'   => 'BAR',
            'nombre'  => 'Best Available Rate',
            'tema'    => 'revenue-rms',
            'definicion' => 'La tarifa más baja que un hotel ofrece sin restricciones -sin mínimo de noches, sin no reembolsable-. Es el precio de referencia contra el que se comparan casi todas las demás tarifas.',
        ],
        [
            'sigla'   => 'Channel manager',
            'nombre'  => 'gestor de canales',
            'tema'    => 'distribucion-otas',
            'definicion' => 'El programa que reparte precio y disponibilidad a la vez a todas las OTAs, la web propia y el GDS, para que no haya que actualizar cada canal a mano ni se venda dos veces la misma habitación.',
        ],
        [
            'sigla'   => 'Chargeback',
            'nombre'  => 'contracargo',
            'tema'    => 'pagos-fraude',
            'definicion' => 'Cuando el banco del cliente devuelve un cargo a la fuerza -por fraude, por disputa o por error- después de que el hotel ya había cobrado. El hotel pierde el dinero y, a veces, una comisión encima.',
        ],
        [
            'sigla'   => 'Chatbot',
            'nombre'  => 'chatbot',
            'tema'    => 'ia-aplicada',
            'definicion' => 'Un programa que contesta al huésped por texto -web, WhatsApp, la propia app del hotel- sin que haya una persona detrás en cada mensaje. Los hay con guion fijo y los hay con IA generativa detrás.',
        ],
        [
            'sigla'   => 'CRS',
            'nombre'  => 'Central Reservation System',
            'tema'    => 'pms-crs',
            'definicion' => 'El sistema que centraliza las reservas de una cadena o de un hotel con varios canales de venta, para que la disponibilidad sea una sola y no una por canal.',
        ],
        [
            'sigla'   => 'GDS',
            'nombre'  => 'Global Distribution System',
            'tema'    => 'distribucion-otas',
            'definicion' => 'La red por la que las agencias de viajes tradicionales y los departamentos de viajes de empresa buscan y reservan hotel, herencia de los sistemas de reserva de vuelos de los años setenta y todavía viva en el segmento corporativo.',
        ],
        [
            'sigla'   => 'IA generativa',
            'nombre'  => 'inteligencia artificial generativa',
            'tema'    => 'ia-aplicada',
            'definicion' => 'La IA que redacta, resume o conversa a partir de lo que se le pide, en vez de solo clasificar o predecir. Es la que hay detrás de casi todos los chatbots y asistentes de escritura del sector desde 2023.',
        ],
        [
            'sigla'   => 'IoT',
            'nombre'  => 'Internet of Things',
            'tema'    => 'operaciones-iot',
            'definicion' => 'Sensores y aparatos conectados a internet que antes eran mecánicos: cerraduras, termostatos, contadores de energía. Un hotel con IoT sabe si una habitación está ocupada sin que nadie llame a la puerta.',
        ],
        [
            'sigla'   => 'KPI',
            'nombre'  => 'Key Performance Indicator',
            'tema'    => null,
            'definicion' => 'Un indicador clave de rendimiento: el número que un equipo ha decidido mirar para saber si algo va bien. RevPAR, ADR y ocupación son los tres KPI clásicos de un hotel.',
        ],
        [
            'sigla'   => 'NDC',
            'nombre'  => 'New Distribution Capability',
            'tema'    => 'distribucion-otas',
            'definicion' => 'Un estándar -nacido en aerolíneas, adoptado también en hotel- para vender con más detalle y menos intermediarios de los que permite el GDS clásico. Se habla de él como el sucesor lento del GDS, no como su sustituto inmediato.',
        ],
        [
            'sigla'   => 'OTA',
            'nombre'  => 'Online Travel Agency',
            'tema'    => 'distribucion-otas',
            'definicion' => 'Una agencia de viajes online -Booking, Expedia y similares-. Cobra comisión por cada reserva que trae, y para muchos hoteles independientes es el canal por el que llega la mayoría de sus huéspedes.',
        ],
        [
            'sigla'   => 'PCI DSS',
            'nombre'  => 'Payment Card Industry Data Security Standard',
            'tema'    => 'pagos-fraude',
            'definicion' => 'El estándar de seguridad que exige Visa, Mastercard y el resto de marcas de tarjeta a quien procesa pagos. Cumplirlo no es opcional: sin él, ningún banco deja procesar tarjetas al hotel.',
        ],
        [
            'sigla'   => 'PMS',
            'nombre'  => 'Property Management System',
            'tema'    => 'pms-crs',
            'definicion' => 'El programa que lleva las reservas, el check-in, la asignación de habitaciones y la facturación de un hotel. Es el sistema del que cuelgan casi todos los demás -channel manager, RMS, cerraduras- por eso un cambio de PMS es la migración más temida del sector.',
        ],
        [
            'sigla'   => 'Ransomware',
            'nombre'  => 'ransomware',
            'tema'    => 'ciberseguridad',
            'definicion' => 'Un programa que cifra los datos de una empresa y pide un pago para devolverlos. Un hotel con el PMS cifrado no puede ni hacer check-in a mano: es de los sectores donde más duele porque no hay un "modo sin sistemas" al que volver.',
        ],
        [
            'sigla'   => 'RevPAR',
            'nombre'  => 'Revenue per Available Room',
            'tema'    => 'revenue-rms',
            'definicion' => 'Los ingresos por habitación disponible, ocupada o no. Es ADR multiplicado por ocupación, y por eso es el KPI que de verdad importa: un ADR alto con pocas habitaciones vendidas puede dar un RevPAR peor que uno bajo con el hotel lleno.',
        ],
        [
            'sigla'   => 'RGPD',
            'nombre'  => 'Reglamento General de Protección de Datos',
            'tema'    => 'cumplimiento',
            'definicion' => 'La norma europea sobre qué datos personales se pueden guardar, para qué y durante cuánto tiempo. Un hotel guarda pasaportes, tarjetas y preferencias de huésped, así que le afecta de lleno.',
        ],
        [
            'sigla'   => 'RMS',
            'nombre'  => 'Revenue Management System',
            'tema'    => 'revenue-rms',
            'definicion' => 'El programa que sugiere o ajusta precios solo, mirando ocupación, fechas y competencia, para vender cada habitación al precio que el mercado aguanta ese día.',
        ],
        [
            'sigla'   => 'Upselling',
            'nombre'  => 'venta adicional',
            'tema'    => 'experiencia-huesped',
            'definicion' => 'Ofrecer al huésped una mejora sobre lo que ya ha reservado -una habitación mejor, un desayuno, una hora de salida más tardía- normalmente de forma automática, antes de que llegue al hotel.',
        ],
    ];
}

/**
 * Los términos del glosario que enlazan a un tema concreto, en el mismo
 * orden alfabético que glosario_terminos().
 */
function glosario_por_tema(string $categoria): array
{
    return array_values(array_filter(
        glosario_terminos(),
        static fn (array $termino): bool => $termino['tema'] === $categoria
    ));
}
