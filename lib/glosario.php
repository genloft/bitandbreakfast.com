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
            'sigla'   => '3DS',
            'nombre'  => '3D Secure',
            'tema'    => 'pagos-fraude',
            'definicion' => 'El protocolo que aplica la autenticación reforzada (SCA) a un pago con tarjeta sin que la tarjeta esté físicamente presente. Es la pantalla del banco que aparece al confirmar el pago de una reserva online.',
        ],
        [
            'sigla'   => 'ACP',
            'nombre'  => 'Agentic Commerce Protocol',
            'tema'    => 'distribucion-otas',
            'definicion' => 'El estándar abierto de OpenAI y Stripe para que un agente de IA complete una compra -o una reserva- en nombre de alguien. Instant Checkout, su primera aplicación en viajes, se retiró en marzo de 2026, pero el protocolo sigue vivo para otros comercios.',
        ],
        [
            'sigla'   => 'ADR',
            'nombre'  => 'Average Daily Rate',
            'tema'    => 'revenue-rms',
            'definicion' => 'La tarifa media diaria por habitación vendida. Sube o baja con el precio, no con la ocupación: un hotel puede llenar más habitaciones y tener un ADR más bajo si las vende más baratas.',
        ],
        [
            'sigla'   => 'Agentic booking',
            'nombre'  => 'reserva agéntica',
            'tema'    => 'distribucion-otas',
            'definicion' => 'Que una IA complete la reserva entera por el viajero, sin que este pase por la web ni la app del hotel. Todavía más previsión que práctica extendida, pero es el paso que protocolos como MCP y ACP están preparando.',
        ],
        [
            'sigla'   => 'AI Act',
            'nombre'  => 'AI Act',
            'tema'    => 'cumplimiento',
            'definicion' => 'El reglamento europeo que regula la inteligencia artificial por nivel de riesgo. Ya obliga a avisar cuando se habla con un chatbot; el régimen de alto riesgo llega en 2027 y 2028.',
        ],
        [
            'sigla'   => 'ALOS',
            'nombre'  => 'Average Length of Stay',
            'tema'    => 'revenue-rms',
            'definicion' => 'Cuántas noches se queda de media un huésped. Sube con el turismo vacacional y baja con el urbano y el de negocios, así que es una de las formas más rápidas de saber qué tipo de demanda tiene un hotel.',
        ],
        [
            'sigla'   => 'API',
            'nombre'  => 'Application Programming Interface',
            'tema'    => null,
            'definicion' => 'La puerta por la que dos programas se hablan sin intervención humana. Cuando un PMS "se integra" con un channel manager, casi siempre es una API la que hace el trabajo.',
        ],
        [
            'sigla'   => 'Attribute-based selling',
            'nombre'  => 'venta por atributos',
            'tema'    => 'revenue-rms',
            'definicion' => 'Vender atributos de la habitación por separado -vistas, planta alta, cama extra- en vez de solo categorías cerradas (estándar, superior, suite). Convierte unos pocos precios fijos en decenas de combinaciones posibles.',
        ],
        [
            'sigla'   => 'BAR',
            'nombre'  => 'Best Available Rate',
            'tema'    => 'revenue-rms',
            'definicion' => 'La tarifa más baja que un hotel ofrece sin restricciones -sin mínimo de noches, sin no reembolsable-. Es el precio de referencia contra el que se comparan casi todas las demás tarifas.',
        ],
        [
            'sigla'   => 'BEMS',
            'nombre'  => 'Building Energy Management System',
            'tema'    => 'sostenibilidad-energia',
            'definicion' => 'Como el BMS, pero centrado en el consumo energético: mide, compara y ajusta el gasto de luz y climatización habitación por habitación o zona por zona.',
        ],
        [
            'sigla'   => 'BMS',
            'nombre'  => 'Building Management System',
            'tema'    => 'operaciones-iot',
            'definicion' => 'El sistema que centraliza el control del edificio -climatización, iluminación, ascensores, alarmas- desde un solo panel, en vez de uno por instalación.',
        ],
        [
            'sigla'   => 'CDP',
            'nombre'  => 'Customer Data Platform',
            'tema'    => null,
            'definicion' => 'Un sistema que junta en un solo perfil por huésped los datos que hoy están repartidos entre el PMS, el motor de reservas y el CRM, para poder personalizar sin perseguir el dato de sistema en sistema.',
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
            'sigla'   => 'CSRD',
            'nombre'  => 'Corporate Sustainability Reporting Directive',
            'tema'    => 'cumplimiento',
            'definicion' => 'La directiva europea de información de sostenibilidad corporativa. Tras la simplificación aprobada en 2026 (el "Omnibus I"), solo obliga a partir de 1.000 empleados y 450 millones de euros de facturación, así que deja fuera a la inmensa mayoría de cadenas hoteleras.',
        ],
        [
            'sigla'   => 'Dynamic packaging',
            'nombre'  => 'empaquetado dinámico',
            'tema'    => 'distribucion-otas',
            'definicion' => 'Combinar vuelo, hotel y extras en un paquete cuyo precio se calcula al momento, en vez de ofrecer paquetes cerrados ya armados de antemano.',
        ],
        [
            'sigla'   => 'EAA',
            'nombre'  => 'European Accessibility Act',
            'tema'    => 'cumplimiento',
            'definicion' => 'La directiva europea de accesibilidad digital. Obliga a que un motor de reservas se pueda usar con lector de pantalla, entre otros requisitos, desde junio de 2025.',
        ],
        [
            'sigla'   => 'GDS',
            'nombre'  => 'Global Distribution System',
            'tema'    => 'distribucion-otas',
            'definicion' => 'La red por la que las agencias de viajes tradicionales y los departamentos de viajes de empresa buscan y reservan hotel, herencia de los sistemas de reserva de vuelos de los años setenta y todavía viva en el segmento corporativo.',
        ],
        [
            'sigla'   => 'GOPPAR',
            'nombre'  => 'Gross Operating Profit per Available Room',
            'tema'    => 'revenue-rms',
            'definicion' => 'El beneficio operativo bruto por habitación disponible, descontados ya los costes de operar. Es la cifra que de verdad interesa a un propietario o un fondo de inversión: RevPAR y TRevPAR miden ingresos, GOPPAR mide lo que queda.',
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
            'sigla'   => 'iPaaS',
            'nombre'  => 'Integration Platform as a Service',
            'tema'    => null,
            'definicion' => 'Un middleware en la nube, de pago por uso, para conectar el PMS con el channel manager, el CRM o el motor de pagos sin escribir la integración a medida cada vez.',
        ],
        [
            'sigla'   => 'KPI',
            'nombre'  => 'Key Performance Indicator',
            'tema'    => null,
            'definicion' => 'Un indicador clave de rendimiento: el número que un equipo ha decidido mirar para saber si algo va bien. RevPAR, ADR y ocupación son los tres KPI clásicos de un hotel.',
        ],
        [
            'sigla'   => 'MCP',
            'nombre'  => 'Model Context Protocol',
            'tema'    => 'distribucion-otas',
            'definicion' => 'El protocolo abierto que deja que una IA -un chatbot, un agente- consulte los sistemas de un hotel: tarifas, disponibilidad, tipos de habitación. Es la capa que permite que un hotel aparezca, con datos reales, dentro de una búsqueda o una reserva hecha por IA.',
        ],
        [
            'sigla'   => 'Metabuscador',
            'nombre'  => 'metabuscador',
            'tema'    => 'distribucion-otas',
            'definicion' => 'Un buscador -Google Hotel Ads, Trivago, Kayak- que compara el precio de la misma habitación entre varias OTAs y la web del hotel, y cobra por cada clic que envía, no por cada reserva.',
        ],
        [
            'sigla'   => 'Middleware',
            'nombre'  => 'middleware',
            'tema'    => null,
            'definicion' => 'El software que traduce entre dos sistemas que no se entienden directamente -distinto formato, distinto protocolo-. Vive en medio, de ahí el nombre, y no lo ve nunca el huésped.',
        ],
        [
            'sigla'   => 'NDC',
            'nombre'  => 'New Distribution Capability',
            'tema'    => 'distribucion-otas',
            'definicion' => 'Un estándar -nacido en aerolíneas, adoptado también en hotel- para vender con más detalle y menos intermediarios de los que permite el GDS clásico. Se habla de él como el sucesor lento del GDS, no como su sustituto inmediato.',
        ],
        [
            'sigla'   => 'NIS2',
            'nombre'  => 'NIS2',
            'tema'    => 'cumplimiento',
            'definicion' => 'La directiva europea de ciberseguridad para sectores esenciales y proveedores digitales críticos. España lleva años de retraso en transponerla, y si el turismo entrará en su ámbito sigue sin decidirse.',
        ],
        [
            'sigla'   => 'OCC',
            'nombre'  => 'ocupación',
            'tema'    => 'revenue-rms',
            'definicion' => 'El porcentaje de habitaciones vendidas sobre las disponibles. El más básico de los indicadores del sector, y el primero que se cita en cualquier informe de rendimiento hotelero.',
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
            'sigla'   => 'Rate parity y disparidad',
            'nombre'  => 'paridad de tarifas',
            'tema'    => 'distribucion-otas',
            'definicion' => 'Que el precio de una misma habitación sea igual en todos los canales de venta -web propia, OTAs, GDS-. Cuando no lo es, hay disparidad, y una OTA que descubre una tarifa más barata en otro canal puede penalizar al hotel en su ranking de búsqueda.',
        ],
        [
            'sigla'   => 'RevPAG',
            'nombre'  => 'Revenue per Available Guest',
            'tema'    => 'revenue-rms',
            'definicion' => 'Los ingresos totales por huésped, no por habitación: cuenta también el gasto en restaurante, spa o extras. Útil sobre todo en hoteles y resorts con mucha venta ajena a la habitación.',
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
            'sigla'   => 'SCA',
            'nombre'  => 'Strong Customer Authentication',
            'tema'    => 'pagos-fraude',
            'definicion' => 'La autenticación reforzada que exige la normativa europea de pagos (PSD2) para las compras con tarjeta: al menos dos de tres factores -algo que se sabe, se tiene o se es-. Es lo que obliga a confirmar un pago desde el móvil del banco.',
        ],
        [
            'sigla'   => 'SES.Hospedajes',
            'nombre'  => 'SES.Hospedajes',
            'tema'    => 'cumplimiento',
            'definicion' => 'El sistema por el que todo alojamiento turístico en España comunica los datos de cada huésped al Ministerio del Interior en las 24 horas siguientes al check-in. Obligatorio y sancionable desde diciembre de 2024.',
        ],
        [
            'sigla'   => 'SSO',
            'nombre'  => 'Single Sign-On',
            'tema'    => null,
            'definicion' => 'Iniciar sesión una sola vez y quedar identificado en varios sistemas a la vez, sin volver a escribir la contraseña en cada uno.',
        ],
        [
            'sigla'   => 'Tokenización',
            'nombre'  => 'tokenización',
            'tema'    => 'pagos-fraude',
            'definicion' => 'Sustituir el número de una tarjeta por un código -un token- que solo sirve para ese comercio y esa operación. Si se filtra, el token no vale para nada fuera de donde se generó, al contrario que el número real de la tarjeta.',
        ],
        [
            'sigla'   => 'TRevPAR',
            'nombre'  => 'Total Revenue per Available Room',
            'tema'    => 'revenue-rms',
            'definicion' => 'Los ingresos totales de un hotel -habitaciones, restaurante, spa, todo- por habitación disponible. RevPAR solo cuenta la habitación; TRevPAR cuenta el hotel entero.',
        ],
        [
            'sigla'   => 'Upselling',
            'nombre'  => 'venta adicional',
            'tema'    => 'experiencia-huesped',
            'definicion' => 'Ofrecer al huésped una mejora sobre lo que ya ha reservado -una habitación mejor, un desayuno, una hora de salida más tardía- normalmente de forma automática, antes de que llegue al hotel.',
        ],
        [
            'sigla'   => 'Verifactu',
            'nombre'  => 'Verifactu',
            'tema'    => 'cumplimiento',
            'definicion' => 'El sistema español de facturación electrónica verificable, que exige que las facturas no se puedan alterar sin dejar rastro. Aplazado dos veces, entra en vigor en 2027.',
        ],
        [
            'sigla'   => 'Webhook',
            'nombre'  => 'webhook',
            'tema'    => null,
            'definicion' => 'Un aviso automático que un sistema manda a otro en cuanto pasa algo -una reserva nueva, un pago confirmado- en vez de que el segundo tenga que preguntar cada rato si ha cambiado algo.',
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
