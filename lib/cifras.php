<?php
/**
 * Lo que necesitan /estadisticas.html, plantillas/web/tema.php y el
 * mantenimiento diario para leer las cifras sin tener que ejecutar la
 * página del cuadro de mandos entera.
 *
 * El array $grupos vivía antes directamente en plantillas/web/estadisticas.php;
 * se trasladó aquí -como cifras_grupos()- cuando plantillas/web/tema.php
 * necesitó las mismas cifras para la ficha de cada tema, mismo motivo por
 * el que lib/cumplimiento.php no vive en su plantilla. Mismo dato, un solo
 * sitio que tocar cuando se actualice.
 */

declare(strict_types=1);

/**
 * Pasados estos dias sin que una persona revise /estadisticas.html, el
 * mantenimiento diario avisa por correo.
 *
 * Ciento veinte son unos cuatro meses: ni tan corto que avise por una fuente
 * que todavia no ha publicado su informe anual, ni tan largo que una cifra
 * pueda llevar casi un año sin que nadie lo note -que es justo lo que esta
 * pagina promete que no pasa.
 */
const CIFRAS_CADUCIDAD_DIAS = 120;

/**
 * La fecha en que una persona reviso por ultima vez /estadisticas.html.
 *
 * Fija, escrita a mano, nunca gmdate(): esta pagina no sale de la base
 * propia y no se pone al dia sola. Se actualiza junto con cifras_grupos(),
 * a la vez y por la misma persona.
 */
function cifras_revisado(): string
{
    return '2026-09-18';
}

/**
 * El porcentaje de empresas españolas que usa IA -la primera cifra de
 * cifras_grupos(), la de "España supera la media europea"- que
 * portada.php repite en el banner de Cifras.
 *
 * Vive aqui, no como dos literales sueltos en dos plantillas: antes de esto
 * portada.php citaba el numero a mano, sin nada que avisara si alguien
 * actualizaba estadisticas.php sin tocar el banner, y el titular de portada
 * se habria quedado citando un dato que la propia pagina de Cifras ya no
 * dice.
 */
function cifras_valor_ia_espana(): string
{
    return '21,1';
}

/**
 * Cada grupo comparativo del cuadro de mandos, con su categoría del
 * catálogo de proveedores -bits_categorias()- cuando el grupo es
 * específico del sector hotelero. Los tres primeros grupos -adopción de
 * IA, cloud y comercio electrónico en la empresa española en general- no
 * lo son a propósito: son la vara de medir de fondo, no un dato de hotel,
 * y llevan 'categoria' => null para dejarlo explícito en vez de omitir el
 * campo.
 *
 * Vive aquí y no en la plantilla -a diferencia de como estaba antes de que
 * plantillas/web/tema.php necesitara reutilizar estos mismos grupos- por el
 * mismo motivo que ya tenía $revisado: solo un sitio que tocar cuando se
 * actualicen los datos.
 */
function cifras_grupos(): array
{
    return [
        [
            'tema' => 'IA en la empresa, en general',
            'categoria' => null,
            'nota' => 'Esta y las dos siguientes -cloud y comercio electrónico- no son datos del sector hotelero: son la vara de medir de fondo, la misma para cualquier sector.',
            'cifras' => [
                [
                    'ambito'  => 'España',
                    'valor'   => cifras_valor_ia_espana() . '%',
                    'detalle' => 'de las empresas de 10 o más empleados usa inteligencia artificial',
                    'fuente'  => 'INE, Encuesta sobre el uso de TIC y comercio electrónico en las empresas',
                    'fecha'   => 'dato de 2025 (1T) · publicado en octubre de 2025',
                    'url'     => 'https://www.ine.es/dyngs/Prensa/ETICCE20241T2025.htm',
                ],
                [
                    'ambito'  => 'Unión Europea',
                    'valor'   => '20,0%',
                    'detalle' => 'de las empresas de 10 o más empleados usa inteligencia artificial (media UE)',
                    'fuente'  => 'Eurostat',
                    'fecha'   => '2025 · publicado en diciembre de 2025',
                    'url'     => 'https://ec.europa.eu/eurostat/web/products-eurostat-news/w/ddn-20251211-2',
                ],
            ],
            'destacado' => 'Por primera vez, España supera la media europea.',
        ],
        [
            'tema' => 'Cloud computing en la empresa',
            'categoria' => null,
            'cifras' => [
                [
                    'ambito'  => 'España',
                    'valor'   => '44,3%',
                    'detalle' => 'de las empresas usa servicios de computación en la nube de pago',
                    'fuente'  => 'INE, Encuesta sobre el uso de TIC y comercio electrónico en las empresas',
                    'fecha'   => 'dato de 2024/2025 · publicado en octubre de 2025',
                    'url'     => 'https://www.ine.es/dyngs/Prensa/ETICCE20241T2025.htm',
                ],
                [
                    'ambito'  => 'Unión Europea',
                    'valor'   => '52,7%',
                    'detalle' => 'de las empresas usa servicios de computación en la nube de pago (media UE)',
                    'fuente'  => 'Eurostat',
                    'fecha'   => '2025 · publicado en febrero de 2026',
                    'url'     => 'https://ec.europa.eu/eurostat/web/products-eurostat-news/w/ddn-20260203-1',
                ],
            ],
            'destacado' => 'Aquí España va por detrás: casi ocho puntos por debajo de la media europea.',
        ],
        [
            'tema' => 'Comercio electrónico',
            'categoria' => null,
            'cifras' => [
                [
                    'ambito'  => 'España',
                    'valor'   => '26,6%',
                    'detalle' => 'de las empresas vendió por comercio electrónico en 2024',
                    'fuente'  => 'INE, Encuesta sobre el uso de TIC y comercio electrónico en las empresas',
                    'fecha'   => 'dato de 2024 · publicado en octubre de 2025',
                    'url'     => 'https://www.ine.es/dyngs/Prensa/ETICCE20241T2025.htm',
                ],
                [
                    'ambito'  => 'Unión Europea',
                    'valor'   => '23,6%',
                    'detalle' => 'de las empresas vendió por comercio electrónico en 2024 (media UE)',
                    'fuente'  => 'Eurostat',
                    'fecha'   => 'dato de 2024 · publicado en junio de 2026',
                    'url'     => 'https://ec.europa.eu/eurostat/statistics-explained/index.php?title=E-commerce_statistics',
                ],
            ],
            'destacado' => 'Y aquí al revés: España supera la media europea en comercio electrónico.',
        ],
        [
            'tema' => 'IA en los hoteles',
            'categoria' => 'ia-aplicada',
            'cifras' => [
                [
                    'ambito'  => 'Global',
                    'valor'   => '82%',
                    'detalle' => 'de los hoteles ampliará su uso de IA en 2026',
                    'fuente'  => 'RateGain, NYU SPS y HEDNA — «State of Distribution 2026» (270+ cadenas, 58.000+ hoteles, 53 países)',
                    'fecha'   => 'encuesta dic. 2024–nov. 2025 · publicado en 2026',
                    'url'     => 'https://rategain.com/press-release/state-of-distribution-2026-launch/',
                ],
                [
                    'ambito'  => 'Global',
                    'valor'   => '< 1 de cada 10',
                    'detalle' => 'hoteles ve un impacto real medible, aunque más de la mitad ya usa o adquiere IA generativa',
                    'fuente'  => 'mismo informe',
                    'fecha'   => '2026',
                    'url'     => 'https://rategain.com/press-release/state-of-distribution-2026-launch/',
                ],
            ],
            'destacado' => 'La adopción va muy por delante del resultado: casi nadie mide todavía si de verdad funciona.',
        ],
        [
            'tema' => 'IA en el viajero',
            'categoria' => 'ia-aplicada',
            'cifras' => [
                [
                    'ambito'  => 'España',
                    'valor'   => '35–45%',
                    'detalle' => 'de los viajeros ya usa IA para planificar sus vacaciones, según la encuesta; supera el 50% entre los 18 y los 34 años',
                    'fuente'  => 'Simon-Kucher (Travel Trends 2026) y Allianz Partners',
                    'fecha'   => '2026',
                    'url'     => 'https://www.allianz-partners.com/es_ES/sala-de-prensa/notas-de-prensa/noticias-2026/el-45-de-los-vajeros-recurre-a-la-ia-para-planificar-sus-vacaciones.html',
                ],
                [
                    'ambito'  => 'Global',
                    'valor'   => '40–54%',
                    'detalle' => 'de los viajeros ha usado ya una IA para planificar un viaje, según la encuesta',
                    'fuente'  => 'Statista (~40%) y Skyscanner Travel Trends (54% en 2025)',
                    'fecha'   => '2025–2026',
                    'url'     => 'https://www.statista.com/topics/10887/artificial-intelligence-ai-use-in-travel-and-tourism/',
                ],
            ],
            'destacado' => 'España aparece, según varias encuestas, entre los países líderes de Europa en este uso.',
        ],
        [
            'tema' => 'Mix de canal directo y OTA',
            'categoria' => 'distribucion-otas',
            'cifras' => [
                [
                    'ambito'  => 'Global',
                    'valor'   => '≈21%',
                    'detalle' => 'de las reservas hoteleras llegan por el canal directo del propio hotel, según el informe -su segunda edición-, igualando por primera vez a la cuota de las OTAs',
                    'fuente'  => 'NYU SPS, HEDNA y RateGain — «State of Distribution 2025» (700+ cadenas, 21.000+ hoteles, 310 ciudades)',
                    'fecha'   => 'informe de 2025 (2.ª edición) · publicado en junio de 2025',
                    'url'     => 'https://rategain.com/press-release/state-of-distribution-2025-launch/',
                ],
            ],
            'destacado' => 'Llevaba años perdiendo terreno frente a las OTAs; es la primera vez que el canal directo empata con ellas.',
        ],
        [
            'tema' => 'Turismo internacional',
            'categoria' => 'inversion-mercado',
            'nota' => 'Esta y las dos siguientes ya no son sobre tecnología: son el tamaño real del sector en el que esa tecnología se usa.',
            'cifras' => [
                [
                    'ambito'  => 'España',
                    'valor'   => '96,8 M',
                    'detalle' => 'turistas internacionales recibidos en 2025, máximo histórico (+3,2% sobre 2024)',
                    'fuente'  => 'INE, Estadística de Movimientos Turísticos en Frontera (Frontur)',
                    'fecha'   => 'año 2025 · publicado en enero de 2026',
                    'url'     => 'https://www.ine.es/dyngs/Prensa/FRONTUR1225.htm',
                ],
                [
                    'ambito'  => 'Global',
                    'valor'   => '1.520 M',
                    'detalle' => 'turistas internacionales en todo el mundo en 2025, un nuevo récord (+4% sobre 2024)',
                    'fuente'  => 'UN Tourism, World Tourism Barometer',
                    'fecha'   => 'año 2025 · publicado en enero de 2026',
                    'url'     => 'https://www.untourism.int/news/international-tourist-arrivals-up-4-in-2025-reflecting-strong-travel-demand-around-the-world',
                ],
            ],
            'destacado' => 'España sola concentra más del 6% de todo el turismo internacional del planeta.',
        ],
        [
            'tema' => 'Contribución económica del turismo',
            'categoria' => 'inversion-mercado',
            'cifras' => [
                [
                    'ambito'  => 'España',
                    'valor'   => '16%',
                    'detalle' => 'del PIB español lo aporta el sector de viajes y turismo, con más de 3,2 millones de empleos',
                    'fuente'  => 'WTTC — World Travel & Tourism Council',
                    'fecha'   => 'año 2025 · publicado en mayo de 2025',
                    'url'     => 'https://wttc.org/news/el-sector-turistico-de-espana-podria-superar-los-260000-millones-de-euros-en-2025',
                ],
                [
                    'ambito'  => 'Global',
                    'valor'   => '9,9%',
                    'detalle' => 'del PIB mundial (12 billones de dólares) lo aporta el sector, con 376 millones de empleos —uno de cada nueve del planeta—',
                    'fuente'  => 'WTTC — World Travel & Tourism Council',
                    'fecha'   => 'previsión 2026 · publicado en mayo de 2026',
                    'url'     => 'https://wttc.org/news/global-travel-tourism-growth-to-outpace-wider-economy-by-1-5-times-over-the-next-decade',
                ],
            ],
            'destacado' => 'El turismo pesa en España mucho más que en el resto del mundo: un 16% del PIB frente a un 9,9% global.',
        ],
        [
            'tema' => 'Rendimiento hotelero',
            'categoria' => 'revenue-rms',
            'cifras' => [
                [
                    'ambito'  => 'España',
                    'valor'   => '61,4%',
                    'detalle' => 'de ocupación media en 2025, con un ADR de 127,7 € y un RevPAR de 89,7 €; récord histórico de pernoctaciones',
                    'fuente'  => 'INE, Coyuntura Turística Hotelera (EOH/IPH/IRSH)',
                    'fecha'   => 'año 2025 · publicado en enero de 2026',
                    'url'     => 'https://ine.es/dyngs/Prensa/CTH1225.htm',
                ],
                [
                    'ambito'  => 'Estados Unidos',
                    'valor'   => '62,3%',
                    'detalle' => 'de ocupación media en 2025, con un ADR de 160,54 $ y un RevPAR de 100,02 $ —el primer retroceso anual en ocupación y RevPAR desde 2020—',
                    'fuente'  => 'STR / CoStar',
                    'fecha'   => 'año 2025 · publicado en enero de 2026',
                    'url'     => 'https://www.costar.com/products/str-benchmark/resources/press-releases/us-hotels-report-first-full-year-occupancy-revpar',
                ],
            ],
            'destacado' => 'España ganó terreno en 2025; el mercado hotelero más grande del mundo, por primera vez desde 2020, lo perdió.',
        ],
        [
            'tema' => 'Inversión hotelera',
            'categoria' => 'inversion-mercado',
            'cifras' => [
                [
                    'ambito'  => 'España',
                    'valor'   => '4.275 M€',
                    'detalle' => 'invertidos en hoteles en España en 2025 -194 operaciones-, el segundo mejor registro histórico',
                    'fuente'  => 'Colliers, Informe de Inversión Hotelera en España 2025',
                    'fecha'   => 'año 2025 · publicado en enero de 2026',
                    'url'     => 'https://www.colliers.com/es-es/research/informe-inversion-hotelera-en-espana-2025',
                ],
                [
                    'ambito'  => 'España',
                    'valor'   => '+30%',
                    'detalle' => 'creció la inversión en hoteles ya en funcionamiento sobre 2024 -de 3.064 M€ a 3.986 M€-',
                    'fuente'  => 'mismo informe',
                    'fecha'   => '2025',
                    'url'     => 'https://www.colliers.com/es-es/research/informe-inversion-hotelera-en-espana-2025',
                ],
            ],
            'destacado' => 'El segmento vacacional concentra ya el 55% de toda la inversión hotelera, y recupera el liderazgo frente al urbano.',
        ],
        [
            'tema' => 'Empleo en alojamiento',
            'categoria' => null,
            'cifras' => [
                [
                    'ambito'  => 'España',
                    'valor'   => '473.450',
                    'detalle' => 'personas ocupadas en alojamiento en 2025 -media anual-, un 1,8% más que en 2024',
                    'fuente'  => 'INE, Encuesta de Población Activa, vía Hostelería Digital',
                    'fecha'   => 'año 2025 · publicado en enero de 2026',
                    'url'     => 'https://www.hosteleriadigital.es/2026/01/28/epa-2025-32-000-trabajadores-menos-en-restauracion-y-8-000-mas-en-alojamiento/',
                ],
                [
                    'ambito'  => 'España',
                    'valor'   => '−2,3%',
                    'detalle' => 'cayó el empleo en restauración en el mismo año -32.375 personas menos-, el otro lado del sector hostelero',
                    'fuente'  => 'mismo informe',
                    'fecha'   => '2025',
                    'url'     => 'https://www.hosteleriadigital.es/2026/01/28/epa-2025-32-000-trabajadores-menos-en-restauracion-y-8-000-mas-en-alojamiento/',
                ],
            ],
            'destacado' => 'El alojamiento crece en empleo mientras la restauración lo pierde: "hostelería" no es una sola foto.',
        ],
        [
            'tema' => 'Gasto turístico',
            'categoria' => 'inversion-mercado',
            'cifras' => [
                [
                    'ambito'  => 'España',
                    'valor'   => '195 €',
                    'detalle' => 'gasto medio diario de un turista internacional en 2025 -media anual-, un 4,9% más que en 2024',
                    'fuente'  => 'INE, Encuesta de Gasto Turístico (Egatur)',
                    'fecha'   => 'año 2025 · publicado en febrero de 2026',
                    'url'     => 'https://www.ine.es/dyngs/Prensa/EGATUR1225.htm',
                ],
                [
                    'ambito'  => 'España',
                    'valor'   => '134.712 M€',
                    'detalle' => 'gasto total de los turistas internacionales en España en 2025, un 6,8% más que en 2024',
                    'fuente'  => 'mismo informe',
                    'fecha'   => '2025',
                    'url'     => 'https://www.ine.es/dyngs/Prensa/EGATUR1225.htm',
                ],
            ],
            'destacado' => 'El turista de hoy no solo es más numeroso: cada uno gasta más cada día que el del año pasado.',
        ],
        [
            'tema' => 'Ciberseguridad hotelera',
            'categoria' => 'ciberseguridad',
            'cifras' => [
                [
                    'ambito'  => 'Global',
                    'valor'   => '4,03 M$',
                    'detalle' => 'coste medio de una brecha de datos en hostelería en 2025 — sube, mientras la media de todos los sectores bajó a 4,44 M$',
                    'fuente'  => 'IBM, Cost of a Data Breach Report',
                    'fecha'   => '2025',
                    'url'     => 'https://www.ibm.com/reports/data-breach',
                ],
                [
                    'ambito'  => 'España',
                    'valor'   => '919',
                    'detalle' => 'notificaciones de brechas de datos personales a la AEPD en julio de 2026 — la cifra mensual más alta en un año, más del triple que en julio de 2025 (282); la propia AEPD señaló a los hoteles y a las empresas de gestión de reservas como uno de los focos del repunte',
                    'fuente'  => 'AEPD, vía Gobierno de España',
                    'fecha'   => 'julio de 2026',
                    'url'     => 'https://www.moncloa.com/2026/08/20/aepd-ciberataques-hoteles-espana-verano-3418190',
                ],
            ],
            'destacado' => 'El coste sube donde nadie mira: la hostelería no es de los sectores más caros, pero es de los pocos que van a peor.',
        ],
    ];
}

/**
 * Las tres cifras que salen en la portada.
 *
 * La pagina de Cifras tiene trece grupos y treinta y tantos numeros, y eso es
 * una pagina de consulta: nadie la abre desde la portada porque nadie sabe que
 * hay dentro. Tres numeros en la portada no sustituyen a esa pagina, la
 * anuncian -y de paso le dan el orden de magnitud del sector a quien llega por
 * un enlace suelto y no vuelve-.
 *
 * Se eligen por el nombre del grupo y no por su posicion, para que reordenar
 * la pagina de Cifras no cambie en silencio lo que sale en portada. Y el valor
 * no se copia: se lee del mismo sitio que lo publica, asi que no puede
 * quedarse atras cuando alguien actualice el dato.
 *
 * Los tres estan elegidos a proposito: uno dice de que tamano es el sector,
 * otro como le fue el ano, y el tercero por que existe este sitio.
 *
 * @return array<int, array{valor: string, rotulo: string, tema: string}>
 */
function cifras_destacadas(): array
{
    $quiero = [
        'Contribución económica del turismo' => 'del PIB español',
        'Rendimiento hotelero'               => 'de ocupación media',
        'Ciberseguridad hotelera'            => 'brechas notificadas a la AEPD',
    ];

    $salida = [];

    foreach (cifras_grupos() as $grupo) {
        $rotulo = $quiero[$grupo['tema']] ?? null;

        if ($rotulo === null) {
            continue;
        }

        foreach ($grupo['cifras'] as $cifra) {
            if (($cifra['ambito'] ?? '') !== 'España') {
                continue;
            }

            $salida[] = [
                'valor'  => (string) $cifra['valor'],
                'rotulo' => $rotulo,
                'tema'   => (string) $grupo['tema'],
            ];

            break;
        }
    }

    return $salida;
}

/**
 * Los grupos del cuadro de mandos que tocan a un tema concreto.
 */
function cifras_por_tema(string $categoria): array
{
    return array_values(array_filter(
        cifras_grupos(),
        static fn (array $grupo): bool => ($grupo['categoria'] ?? null) === $categoria
    ));
}

/**
 * Todo lo que ensena /estadisticas.html, en forma de datos: para /cifras.json,
 * que es lo que hace que otros citen estas cifras en vez de rehacerlas -tal
 * cual pide docs/MEJORAS.md-. Mismos grupos y el mismo criterio de revision
 * que la pagina, sin nada que la pagina no diga ya.
 */
function cifras_exportar(): array
{
    $revisado = cifras_revisado();

    return [
        'revisado'        => $revisado,
        'limite_revision' => cifras_limite_revision($revisado),
        'grupos'          => cifras_grupos(),
    ];
}

/**
 * Hasta cuando "revisado el $revisado" sigue siendo una promesa vigente.
 *
 * Mismo umbral que decide si el mantenimiento avisa por correo
 * (CIFRAS_CADUCIDAD_DIAS), pero contado hacia delante: no es "cuanto lleva
 * caducado", es "cuando caduca". /estadisticas.html lo ensena para que quien
 * lee la pagina no tenga que confiar a ciegas en una fecha de revision sin
 * saber cuanto dura esa promesa.
 */
function cifras_limite_revision(string $revisado): string
{
    $revisado_ts = strtotime($revisado . ' UTC');

    if ($revisado_ts === false) {
        return $revisado;
    }

    return gmdate('Y-m-d', $revisado_ts + CIFRAS_CADUCIDAD_DIAS * 86400);
}

/**
 * Si toca avisar de que /estadisticas.html lleva demasiado sin revisarse.
 *
 * Pura -ni correo ni base de datos-, para poder probarla sin montar nada.
 *
 * $ultimo_aviso es la fecha de revision por la que ya se mando un aviso la
 * ultima vez. Si coincide con $revisado, el aviso de esa revision ya salio
 * y no hay que repetirlo cada dia mientras nadie actualice la pagina: sin
 * este freno, el mantenimiento -que corre una vez al dia- mandaria el mismo
 * correo a diario para siempre, y un aviso que se repite es indistinguible
 * de ruido.
 */
function cifras_caducadas(string $revisado, string $hoy, string $ultimo_aviso): bool
{
    $revisado_ts = strtotime($revisado . ' UTC');
    $hoy_ts      = strtotime($hoy . ' UTC');

    if ($revisado_ts === false || $hoy_ts === false) {
        return false;
    }

    $dias = (int) floor(($hoy_ts - $revisado_ts) / 86400);

    if ($dias < CIFRAS_CADUCIDAD_DIAS) {
        return false;
    }

    return $ultimo_aviso !== $revisado;
}
