<?php
/**
 * La taxonomia del stack hotelero: seis areas y veinticuatro nodos.
 *
 * Es el mapa de la casa de un hotel vista desde el departamento de sistemas.
 * El radar publica noticias sueltas; esto es lo que dice a que parte del
 * negocio le tocan. Un director no lee ciento veinte bits al mes, pero mira
 * un mapa de veinticuatro casillas y sabe en diez segundos si esta semana le
 * ha pasado algo al PMS o a la pasarela de pagos.
 *
 * Decisiones que conviene no deshacer sin querer:
 *
 *   - Los 'id' son contrato. Salen publicados en data/taxonomy.json, cuelgan
 *     de las URL compartidas (/mapa.html#/nodo/pms) y quedan en el historico.
 *     Renombrar uno rompe los enlaces que alguien ya ha mandado a su equipo.
 *   - La distribucion es area propia, no un rincon de operaciones. En
 *     hoteleria el ingreso se mueve ahi y es donde se concentra la dependencia
 *     de terceros; fundirla con operaciones deja el mapa ciego justo donde mas
 *     duele.
 *   - 'integraciones' vive en datos y no en infraestructura. Que un gran
 *     proveedor cambie su politica de APIs es un problema de contrato y de
 *     flujo de datos antes que de maquinas.
 *
 * Los alias son lo que hace de clasificador. No hay LLM aqui a proposito: el
 * modo automatico de este sitio no inventa, y un modelo que adivina a que
 * nodo va una noticia encendera casillas que nadie puede comprobar. Un alias
 * es una afirmacion verificable -esta palabra esta en el texto o no esta-, y
 * es lo unico que se puede defender cuando alguien pregunte por que su area
 * aparece en rojo.
 *
 * Los alias se comparan ya normalizados por texto_normalizar(): en
 * minusculas, sin acentos y con todo lo que no sea letra o digito convertido
 * en espacio. Escribirlos aqui con mayusculas o acentos no estorba.
 */

declare(strict_types=1);

require_once __DIR__ . '/texto.php';

/**
 * Las seis areas, en el orden en que se pintan.
 */
function stack_areas(): array
{
    return [
        'ops'   => 'Operaciones',
        'dist'  => 'Distribución y Revenue',
        'cx'    => 'Experiencia de Cliente',
        'bo'    => 'Back-Office',
        'infra' => 'Infraestructura y Ciberseguridad',
        'data'  => 'Datos e IA',
    ];
}

/**
 * Los veinticuatro nodos: etiqueta, area y alias que los delatan en un texto.
 *
 * Los alias mezclan castellano e ingles a proposito: la mitad de lo que
 * rastrea este radar viene en ingles, y aunque el bit se publique traducido,
 * los nombres propios del sector -channel manager, revenue management- no se
 * traducen ni en espanol.
 *
 * @return array<string, array{label: string, corto?: string, area: string, alias: string[]}>
 */
function stack_nodos(): array
{
    // Memorizado: stack_menciones() la pide una vez por noticia y el generador
    // clasifica varios cientos en cada pasada. Rehacer el literal cada vez son
    // trescientas cadenas construidas de nuevo para nada, y esto corre en un
    // alojamiento compartido con presupuesto de segundos.
    static $nodos = null;

    if ($nodos !== null) {
        return $nodos;
    }

    return $nodos = [
        // --- Operaciones -----------------------------------------------------
        'pms' => [
            'label' => 'PMS',
            'area'  => 'ops',
            'alias' => [
                'pms', 'property management system', 'opera cloud', 'oracle hospitality',
                'cloudbeds', 'mews', 'apaleo', 'protel', 'guestline', 'stayntouch',
                'roomraccoon', 'ulyses', 'tesipro', 'noray', 'gestion hotelera',
                'sistema de gestion hotelera',
            ],
        ],
        'housekeeping' => [
            'label' => 'Housekeeping',
            'area'  => 'ops',
            'alias' => [
                'housekeeping', 'limpieza de habitaciones', 'camareras de piso',
                'gobernanta', 'mantenimiento preventivo', 'ordenes de trabajo',
                'gestion de tareas', 'flexkeeping', 'optii',
            ],
        ],
        'pos-fb' => [
            'label' => 'TPV y F&B',
            'area'  => 'ops',
            'alias' => [
                'tpv', 'punto de venta', 'pos', 'restauracion', 'f b', 'food and beverage',
                'room service', 'carta digital', 'comanda', 'lightspeed', 'micros',
                'simphony', 'agora', 'glop', 'cocina', 'restaurante del hotel',
            ],
        ],
        'mice' => [
            'label' => 'Grupos y MICE',
            'corto' => 'MICE',
            'area'  => 'ops',
            'alias' => [
                'mice', 'grupos y eventos', 'salas de reuniones', 'espacios de reunion',
                'congresos', 'convenciones', 'banquetes', 'cvent', 'event temple',
                'reserva de grupos',
            ],
        ],

        // --- Distribucion y Revenue ------------------------------------------
        'crs-motor' => [
            'label' => 'CRS y motor',
            'corto' => 'CRS',
            'area'  => 'dist',
            'alias' => [
                'crs', 'central reservation system', 'motor de reservas', 'booking engine',
                'reserva directa', 'venta directa', 'web del hotel', 'mirai', 'paraty',
                'witbooking', 'roiback', 'neobookings', 'synxis', 'travelclick',
            ],
        ],
        'channel-manager' => [
            'label' => 'Channel manager',
            'area'  => 'dist',
            'alias' => [
                'channel manager', 'gestor de canales', 'conectividad', 'siteminder',
                'd edge', 'derbysoft', 'hotelrunner', 'yieldplanet', 'paridad de precios',
                'rate parity', 'distribucion hotelera',
            ],
        ],
        'rms' => [
            'label' => 'Revenue y RMS',
            'corto' => 'Revenue',
            'area'  => 'dist',
            'alias' => [
                'rms', 'revenue management', 'revenue manager', 'pricing dinamico',
                'precios dinamicos', 'tarificacion', 'yield', 'revpar', 'adr',
                'ideas g3', 'duetto', 'atomize', 'pace revenue', 'beonx', 'lybra',
                'previsión de demanda', 'forecast de demanda',
            ],
        ],
        'ota-metas' => [
            'label' => 'OTAs y metas',
            'corto' => 'OTAs',
            'area'  => 'dist',
            'alias' => [
                'ota', 'otas', 'booking com', 'expedia', 'airbnb', 'agoda', 'hotelbeds',
                'metabuscador', 'metabuscadores', 'google hotel ads', 'trivago', 'tripadvisor',
                'kayak', 'gds', 'amadeus', 'sabre', 'travelport', 'comision', 'comisiones',
                'dma', 'ley de mercados digitales',
            ],
        ],

        // --- Experiencia de Cliente ------------------------------------------
        'crm-fidelizacion' => [
            'label' => 'CRM y fidelización',
            'corto' => 'CRM',
            'area'  => 'cx',
            'alias' => [
                'crm', 'fidelizacion', 'programa de fidelizacion', 'loyalty', 'puntos',
                'email marketing', 'marketing automation', 'salesforce', 'hubspot',
                'revinate', 'cendyn', 'for s hotelware', 'bookboost', 'club de cliente',
            ],
        ],
        'checkin-digital' => [
            'label' => 'Check-in digital',
            'corto' => 'Check-in',
            'area'  => 'cx',
            'alias' => [
                'check in online', 'checkin online', 'check in digital', 'check out express',
                'kiosco', 'kioscos', 'quiosco de autocheckin', 'llave movil', 'mobile key',
                'registro de viajeros', 'ses hospedajes', 'parte de entrada',
            ],
        ],
        'app-huesped' => [
            'label' => 'App del huésped',
            'corto' => 'App huésped',
            'area'  => 'cx',
            'alias' => [
                'app del huesped', 'guest app', 'aplicacion para huespedes', 'upselling',
                'upsell', 'conserjeria digital', 'directorio digital', 'tv del hotel',
                'oaky', 'nonius', 'hotelkit', 'guest experience',
            ],
        ],
        'mensajeria-ia' => [
            'label' => 'Mensajería e IA',
            'corto' => 'Mensajería IA',
            'area'  => 'cx',
            'alias' => [
                'chatbot', 'chatbots', 'asistente virtual', 'agente de voz', 'voicebot',
                'atencion al cliente automatizada', 'whatsapp business', 'mensajeria',
                'agente conversacional', 'recepcion virtual', 'contact center',
            ],
        ],

        // --- Back-Office -----------------------------------------------------
        'erp-finanzas' => [
            'label' => 'ERP y finanzas',
            'corto' => 'ERP',
            'area'  => 'bo',
            'alias' => [
                'erp', 'contabilidad', 'consolidacion', 'cierre contable', 'facturacion',
                'sap', 'navision', 'business central', 'sage', 'a3', 'tesoreria',
                'cuenta de resultados', 'uniform system of accounts',
            ],
        ],
        'rrhh' => [
            'label' => 'RRHH y turnos',
            'corto' => 'RRHH',
            'area'  => 'bo',
            'alias' => [
                'rrhh', 'recursos humanos', 'nominas', 'nomina', 'turnos', 'cuadrante',
                'planificacion de turnos', 'convenio colectivo', 'contratacion',
                'personal de hotel', 'plantilla', 'rotacion de personal', 'workforce',
            ],
        ],
        'compras' => [
            'label' => 'Compras',
            'area'  => 'bo',
            'alias' => [
                'compras', 'aprovisionamiento', 'proveedores de suministro', 'inventario',
                'almacen', 'escandallo', 'central de compras', 'procurement',
                'cadena de suministro', 'supply chain',
            ],
        ],
        'pagos' => [
            'label' => 'Pagos',
            'area'  => 'bo',
            'alias' => [
                'pasarela de pago', 'pasarela de pagos', 'psp', 'tokenizacion', 'adyen',
                'stripe', 'redsys', 'planet payment', 'shift4', 'pci dss', 'psd2', 'psd3',
                'sca', 'chargeback', 'contracargo', 'fraude con tarjeta', 'tarjeta virtual',
                'factura electronica', 'verifactu', 'tickbim',
            ],
        ],

        // --- Infraestructura y Ciberseguridad ---------------------------------
        'red-wifi' => [
            'label' => 'Red y wifi',
            'area'  => 'infra',
            'alias' => [
                'wifi', 'wi fi', 'red del hotel', 'router', 'routers', 'sd wan', 'switch',
                'cableado', 'fibra', 'ancho de banda', 'cobertura movil', 'ruckus',
                'cambium', 'aruba', 'ubiquiti',
            ],
        ],
        'ciberseguridad' => [
            'label' => 'Ciberseguridad',
            'area'  => 'infra',
            'alias' => [
                'ciberseguridad', 'ciberataque', 'ransomware', 'malware', 'phishing',
                'brecha de datos', 'filtracion de datos', 'vulnerabilidad', 'cve',
                'parche', 'edr', 'zero day', 'dia cero', 'nis2', 'incidente de seguridad',
                'robo de credenciales', 'suplantacion', 'incibe', 'cisa', 'kev',
            ],
        ],
        'cloud-hosting' => [
            'label' => 'Cloud y hosting',
            'corto' => 'Cloud',
            'area'  => 'infra',
            'alias' => [
                'cloud', 'nube', 'hosting', 'centro de datos', 'data center', 'aws',
                'azure', 'google cloud', 'virtualizacion', 'vmware', 'continuidad de negocio',
                'plan de contingencia', 'caida del servicio', 'interrupcion del servicio',
                'migracion a la nube', 'saas',
            ],
        ],
        'accesos-iot' => [
            'label' => 'Accesos e IoT',
            'corto' => 'Accesos',
            'area'  => 'infra',
            'alias' => [
                'cerradura', 'cerraduras', 'control de accesos', 'llave de habitacion',
                'cctv', 'videovigilancia', 'iot', 'domotica', 'termostato', 'salto systems',
                'assa abloy', 'dormakaba', 'onity', 'sensores de habitacion',
            ],
        ],

        // --- Datos e IA -------------------------------------------------------
        'bi-reporting' => [
            'label' => 'BI y reporting',
            'corto' => 'BI',
            'area'  => 'data',
            'alias' => [
                'business intelligence', 'cuadro de mando', 'cuadros de mando', 'dashboard',
                'reporting', 'power bi', 'tableau', 'looker', 'kpi', 'kpis', 'benchmarking',
                'str', 'informe de mercado',
            ],
        ],
        'cdp-datos' => [
            'label' => 'CDP y dato del huésped',
            'corto' => 'CDP',
            'area'  => 'data',
            'alias' => [
                'cdp', 'customer data platform', 'data warehouse', 'data lake',
                'calidad del dato', 'perfil unico', 'ficha unica del cliente',
                'golden record', 'segmentacion', 'primeras partes', 'first party data',
            ],
        ],
        'integraciones' => [
            'label' => 'Integraciones y APIs',
            'corto' => 'Integraciones',
            'area'  => 'data',
            'alias' => [
                'api', 'apis', 'integracion', 'integraciones', 'middleware', 'ipaas',
                'webhook', 'conector', 'conectores', 'interoperabilidad', 'htng',
                'open api', 'marketplace de integraciones', 'mcp', 'model context protocol',
            ],
        ],
        'gobierno-ia' => [
            'label' => 'Gobierno del dato e IA',
            'corto' => 'Gobierno IA',
            'area'  => 'data',
            'alias' => [
                'ia act', 'ai act', 'reglamento de ia', 'rgpd', 'gdpr', 'proteccion de datos',
                'aepd', 'gobierno del dato', 'data governance', 'sesgo algoritmico',
                'transparencia algoritmica', 'cumplimiento normativo', 'auditoria de modelos',
                'dora', 'data act',
            ],
        ],
    ];
}

/**
 * La etiqueta corta, para el dibujo del mapa.
 *
 * En el mapa cada nombre vive dentro de un hueco de unas cien unidades y
 * compite con las lineas que le pasan por debajo: "CDP y dato del huesped" se
 * parte en tres y se come al vecino. Fuera del dibujo -en la ficha del nodo,
 * en el JSON publico- manda la etiqueta larga, que es la que de verdad dice
 * que es esa pieza.
 *
 * Solo la tienen los nodos que la necesitan. El resto devuelven la suya de
 * siempre, para no mantener veinticuatro nombres por duplicado.
 */
function stack_etiqueta_corta(string $nodo): string
{
    $registro = stack_nodos()[$nodo] ?? null;

    if ($registro === null) {
        return $nodo;
    }

    return $registro['corto'] ?? $registro['label'];
}

/**
 * La etiqueta de un nodo, o su propio id si no existe.
 *
 * Un bit clasificado con el catalogo de hace seis meses puede apuntar a un
 * nodo que ya no esta. Devolver el id es feo pero legible; devolver vacio
 * deja una casilla sin nombre, que es peor.
 */
function stack_etiqueta(string $nodo): string
{
    return stack_nodos()[$nodo]['label'] ?? $nodo;
}

/**
 * El area de un nodo, o '' si no se reconoce.
 */
function stack_area_de(string $nodo): string
{
    return stack_nodos()[$nodo]['area'] ?? '';
}

/**
 * Los nodos de un area, en el orden del catalogo.
 *
 * @return string[]
 */
function stack_nodos_de(string $area): array
{
    $ids = [];

    foreach (stack_nodos() as $id => $nodo) {
        if ($nodo['area'] === $area) {
            $ids[] = $id;
        }
    }

    return $ids;
}

/**
 * La taxonomia entera, tal y como se publica en data/taxonomy.json.
 *
 * Es la unica forma de que otro pueda leer data/heatmap.json sin adivinar que
 * significa 'crs-motor'. Los alias viajan tambien: quien quiera entender por
 * que una noticia cayo donde cayo tiene derecho a ver la regla.
 */
function stack_taxonomia(): array
{
    $areas = [];

    foreach (stack_areas() as $id => $label) {
        $areas[] = ['id' => $id, 'label' => $label, 'nodes' => stack_nodos_de($id)];
    }

    $nodos = [];

    foreach (stack_nodos() as $id => $nodo) {
        $nodos[] = [
            'id'      => $id,
            'label'   => $nodo['label'],
            'area'    => $nodo['area'],
            'aliases' => $nodo['alias'],
        ];
    }

    return [
        'version' => stack_version(),
        'areas'   => $areas,
        'nodes'   => $nodos,
    ];
}

/**
 * La version de la taxonomia.
 *
 * No es un numero escrito a mano -se olvida de subir- sino la huella del
 * catalogo. Si alguien anade un nodo o cambia un alias, cambia sola, y
 * heatmap.json dice contra que catalogo se clasifico lo que lleva dentro.
 */
function stack_version(): string
{
    return substr(sha1((string) json_encode([stack_areas(), stack_nodos()])), 0, 12);
}

/**
 * Alias que no encienden un nodo por si solos.
 *
 * Son palabras que el nodo usa de verdad pero que el castellano usa tambien
 * para otra cosa, y cada una de estas cuatro se cazo mirando lo publicado:
 *
 *   - "conectividad aerea" mandaba un destino turistico al channel manager;
 *   - "una vulnerabilidad que los destinos tardan en reconocer" mandaba un
 *     analisis de demanda a ciberseguridad;
 *   - "compras" aparece en cualquier noticia que hable de comprar algo;
 *   - "cocina" sale en sostenibilidad tanto como en restauracion.
 *
 * Siguen contando -si el nodo ya se ha encendido por otra cosa, suman a su
 * confianza- pero no abren la puerta. Es la diferencia entre una palabra que
 * identifica una pieza del stack y una que solo la roza.
 *
 * @return array<string, true>
 */
function stack_alias_debiles(): array
{
    return [
        'conectividad'     => true,
        'vulnerabilidad'   => true,
        'vulnerabilidades' => true,
        'compras'          => true,
        'cocina'           => true,
        'integracion'      => true,
        'integraciones'    => true,
    ];
}

/**
 * De que nodos habla un texto, del que mas lo menciona al que menos.
 *
 * Se cuenta cuantos alias distintos del nodo aparecen, no cuantas veces
 * aparece cada uno: un titular que repite "API" seis veces no sabe mas de
 * integraciones que uno que dice "API" y "middleware". Repetir una palabra es
 * estilo; usar dos del mismo campo es tema.
 *
 * El texto se compara con espacios a los lados para que 'pos' no se coma
 * "posible" y 'ota' no se coma "otan". Es la diferencia entre un clasificador
 * y un buscador de subcadenas.
 *
 * @return array<string, int> nodo => alias distintos encontrados
 */
function stack_menciones(string $texto): array
{
    $normal = ' ' . texto_normalizar($texto) . ' ';
    $cuenta = [];

    $debiles = stack_alias_debiles();

    foreach (stack_nodos() as $id => $nodo) {
        $encontrados = 0;
        $fuertes     = 0;

        foreach ($nodo['alias'] as $alias) {
            $aguja = texto_normalizar($alias);

            if ($aguja === '' || !str_contains($normal, ' ' . $aguja . ' ')) {
                continue;
            }

            $encontrados++;

            if (!isset($debiles[$aguja])) {
                $fuertes++;
            }
        }

        // Un nodo se enciende con un alias que lo identifique, o con dos que
        // lo rocen. Con uno que lo roce, no: ahi es donde se colaban los
        // falsos positivos.
        if ($fuertes > 0 || $encontrados >= 2) {
            $cuenta[$id] = $encontrados;
        }
    }

    arsort($cuenta);

    return $cuenta;
}

/**
 * El nodo al que cae una categoria del catalogo editorial cuando el texto no
 * menciona ningun alias.
 *
 * Es la red de seguridad, no el camino principal: la categoria de un bit la
 * pone el diccionario de puntuacion, que sabe de palabras y no de contextos.
 * Sirve para que una noticia claramente del sector no se quede fuera del mapa
 * por estar escrita sin una sola palabra tecnica, y por eso lo que entra por
 * aqui queda marcado con confianza baja.
 *
 * Las categorias que no apuntan a un nodo concreto -tecnologia general,
 * inversion y mercado, sostenibilidad- no estan: encender un nodo al azar es
 * peor que no encender ninguno.
 */
function stack_nodo_de_categoria(string $categoria): string
{
    return [
        'pms-crs'             => 'pms',
        'distribucion-otas'   => 'ota-metas',
        'revenue-rms'         => 'rms',
        'pagos-fraude'        => 'pagos',
        'ciberseguridad'      => 'ciberseguridad',
        'cumplimiento'        => 'gobierno-ia',
        'operaciones-iot'     => 'accesos-iot',
        'experiencia-huesped' => 'app-huesped',
        // 'ia-aplicada' no esta, y estuvo. Mandaba a "mensajeria e IA" -que es
        // el nodo de los chatbots y los agentes de voz- once noticias de las
        // cincuenta y tres publicadas: ferias, columnas de opinion y notas
        // sobre la adopcion de la IA en el sector. Ninguna hablaba de una
        // pieza del stack. Un respaldo que acierta una de cada tres no es una
        // red de seguridad, es un vertedero con nombre de nodo.
    ][$categoria] ?? '';
}

/**
 * Los nodos de un bit: como mucho tres, el primero el principal.
 *
 * Tres y no mas porque un mapa donde cada noticia enciende ocho casillas no
 * dice donde mirar, que es lo unico que se le pide.
 *
 * @param array $bit Fila de bits con titular, cuerpo, por_que y categoria.
 * @return string[]
 */
function stack_clasificar(array $bit): array
{
    return stack_clasificar_detalle($bit)['nodos'];
}

/**
 * Lo mismo, contando ademas por que salio asi.
 *
 * Quien pinta el mapa necesita los nodos; quien decide cuanta confianza
 * merece la casilla necesita saber si el nodo lo dijo el texto o lo dijo la
 * red de seguridad. Devolverlo por separado evita que heatmap.php repita la
 * clasificacion entera solo para averiguarlo.
 *
 * @return array{nodos: string[], origen: string, alias: int}
 */
function stack_clasificar_detalle(array $bit): array
{
    $texto = implode(' ', [
        (string) ($bit['titular'] ?? ''),
        (string) ($bit['cuerpo'] ?? ''),
        (string) ($bit['por_que'] ?? ''),
    ]);

    $menciones = stack_menciones($texto);

    if ($menciones) {
        $nodos = array_slice(array_keys($menciones), 0, 3);

        return [
            'nodos'  => $nodos,
            'origen' => 'alias',
            // Los alias del nodo principal: es el que decide el color de la
            // casilla, asi que es el unico cuya solidez importa.
            'alias'  => (int) $menciones[$nodos[0]],
        ];
    }

    $respaldo = stack_nodo_de_categoria((string) ($bit['categoria'] ?? ''));

    if ($respaldo === '') {
        return ['nodos' => [], 'origen' => 'ninguno', 'alias' => 0];
    }

    return ['nodos' => [$respaldo], 'origen' => 'categoria', 'alias' => 0];
}
