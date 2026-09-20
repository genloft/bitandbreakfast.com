<?php
/**
 * Lo que necesitan tanto /cumplimiento.html como el mantenimiento diario
 * para saber si esa página está al día.
 *
 * Mismo patrón que lib/cifras.php -la fecha de revisión vive fuera de la
 * plantilla para que cron/mantenimiento.php pueda leerla sin ejecutar la
 * página entera-, con una diferencia: allí la caducidad es un plazo fijo
 * (ciento veinte días) porque no hay ninguna fecha propia de la que
 * colgarse. Aquí sí la hay -es una tabla de fechas normativas-, así que la
 * caducidad es la fecha pendiente más próxima de la propia tabla, no un
 * número inventado: el día que venza un plazo es, por definición, el día
 * que esta página necesita que alguien la mire.
 */

declare(strict_types=1);

/**
 * Sin ninguna fecha pendiente en la tabla -pasará el día que se resuelvan
 * todos los trámites en curso y no quede ningún plazo futuro-, cuántos
 * días de margen se dan antes de pedir una revisión de todos modos. Mismo
 * orden de magnitud que CIFRAS_CADUCIDAD_DIAS.
 */
const CUMPLIMIENTO_CADUCIDAD_RESPALDO_DIAS = 180;

/**
 * La fecha en que una persona revisó por última vez /cumplimiento.html.
 * Igual que cifras_revisado(): fija, a mano, nunca gmdate().
 */
function cumplimiento_revisado(): string
{
    return '2026-09-20';
}

/**
 * El calendario normativo entero, cada norma con su fuente.
 *
 * Vive aquí y no en la plantilla -a diferencia de $grupos en
 * estadisticas.php, que sí vive en la plantilla- porque cron/mantenimiento.php
 * necesita las fechas de cada norma para calcular cuándo caduca la revisión
 * de la página, no solo una fecha de revisión suelta: aquí la caducidad es
 * la fecha pendiente más próxima de esta misma tabla, así que la tabla
 * tiene que poder leerse sin ejecutar la plantilla entera.
 *
 * El orden es cronológico por 'fecha' -el mismo criterio con el que se
 * cuenta la historia de una regulación: cuándo empezó a aplicarse de
 * verdad, no cuándo se aprobó sobre el papel-, no por lo importante que sea
 * para un hotel.
 *
 * Cada norma lleva un 'estado' -no todas cuentan atrás de la misma forma-:
 *
 *   'plazo'    hay una fecha futura concreta: cuenta atrás (en
 *              'fecha_cuenta_atras', que puede no coincidir con 'fecha' si
 *              esta última ya es histórica -el caso del AI Act, ya vigente
 *              en su parte de transparencia-).
 *   'vigente'  la fecha ya pasó, ya se aplica.
 *   'tramite'  no hay una fecha única a la que colgarse -está en proceso-.
 *   'contexto' no aplica a hoteles, se incluye por el efecto que tiene
 *              sobre su competencia.
 */
function cumplimiento_normas(): array
{
    return [
        [
            'norma'   => 'NIS2',
            'ambito'  => 'Directiva (UE) 2022/2555',
            'fecha'   => '2024-10-17',
            'estado'  => 'tramite',
            'texto'   => 'España debía transponerla el 17 de octubre de 2024 y, casi dos años después, sigue sin hacerlo.',
            'aplica'  => 'Sin decidir todavía: turismo y hostelería no están en la lista de sectores de la directiva europea. España podría ampliar el ámbito al aprobar su propia ley -algo que, a día de hoy, tampoco ha hecho-, y solo entonces se sabrá si una cadena hotelera con sistemas de reserva propios entra como proveedor digital.',
            'detalle' => 'El 8 de julio de 2026 la Comisión Europea llevó a España, junto con Irlanda, Francia y los Países Bajos, ante el Tribunal de Justicia de la UE por el retraso, pidiendo una sanción a tanto alzado y una multa diaria.',
            'fuente'  => 'Comisión Europea',
            'url'     => 'https://ec.europa.eu/commission/presscorner/detail/en/ip_26_1499',
        ],
        [
            'norma'   => 'SES.Hospedajes',
            'ambito'  => 'Real Decreto 933/2021',
            'fecha'   => '2024-12-02',
            'estado'  => 'vigente',
            'texto'   => 'Obligatorio y sancionable desde el 2 de diciembre de 2024.',
            'aplica'  => 'La recepción de cualquier alojamiento turístico: hoteles, apartamentos, pensiones, campings.',
            'detalle' => 'Comunicar los datos de cada huésped al Ministerio del Interior en las 24 horas siguientes al check-in. Multas de 100 a 600 € por infracción leve y de 601 a 30.000 € por infracción grave.',
            'fuente'  => 'Ministerio del Interior',
            'url'     => 'https://www.interior.gob.es/opencms/es/servicios-al-ciudadano/hospedajes-y-alquiler-de-vehiculos/index.html',
        ],
        [
            'norma'   => 'Accesibilidad digital',
            'ambito'  => 'European Accessibility Act, Directiva (UE) 2019/882',
            'fecha'   => '2025-06-28',
            'estado'  => 'vigente',
            'texto'   => 'En vigor desde el 28 de junio de 2025.',
            'aplica'  => 'El motor de reservas: vender una habitación por web o app es comercio electrónico.',
            'detalle' => 'Hay una exención para microempresas -menos de 10 empleados y 2 M€ de facturación o balance-, pero no protege al proveedor del motor de reservas si ese sí es grande. Ya hay demandas por incumplimiento en Francia.',
            'fuente'  => 'EUR-Lex',
            'url'     => 'https://eur-lex.europa.eu/legal-content/ES/TXT/HTML/?uri=CELEX:32019L0882',
        ],
        [
            'norma'   => 'Reglamento de alquiler de corta duración',
            'ambito'  => 'Reglamento (UE) 2024/1028',
            'fecha'   => '2026-05-20',
            'estado'  => 'contexto',
            'texto'   => 'Aplicable desde el 20 de mayo de 2026.',
            'aplica'  => 'No a hoteles: obliga a plataformas y anfitriones de alquiler turístico de corta duración a compartir datos de registro con las autoridades.',
            'detalle' => 'Entra en esta tabla por contexto competitivo, no porque afecte a un hotel: es la primera pieza regulatoria que somete a los pisos turísticos al mismo tipo de escrutinio de datos que ya tiene, desde hace años, la recepción de cualquier hotel.',
            'fuente'  => 'EUR-Lex',
            'url'     => 'https://eur-lex.europa.eu/eli/reg/2024/1028/oj/spa',
        ],
        [
            'norma'   => 'AI Act',
            'ambito'  => 'Reglamento (UE) 2024/1689',
            'fecha'   => '2026-08-02',
            'fecha_cuenta_atras' => '2027-12-02',
            'estado'  => 'plazo',
            'texto'   => 'La transparencia -avisar de que se habla con un chatbot- ya es obligatoria desde el 2 de agosto de 2026. Lo que todavía no ha llegado es el régimen de alto riesgo.',
            'aplica'  => 'Chatbots de atención al cliente y sistemas de fijación de precios con IA.',
            'detalle' => 'El régimen de alto riesgo -evaluación de conformidad, registro, gestión de riesgos- se aplazó al 2 de diciembre de 2027 para sistemas independientes y al 2 de agosto de 2028 para los integrados en un producto.',
            'fuente'  => 'Comisión Europea',
            'url'     => 'https://digital-strategy.ec.europa.eu/en/policies/regulatory-framework-ai',
        ],
        [
            'norma'   => 'Verifactu',
            'ambito'  => 'RD 1007/2023 + RD 254/2025, aplazado por el RDL 15/2025',
            'fecha'   => '2027-01-01',
            'fecha_cuenta_atras' => '2027-01-01',
            'estado'  => 'plazo',
            'texto'   => '1 de enero de 2027 para el Impuesto de Sociedades · 1 de julio de 2027 para el resto.',
            'aplica'  => 'Todo hotel que emite facturas.',
            'detalle' => 'Aplazado dos veces -el plazo original era 2026-: el Real Decreto-ley 15/2025 movió la fecha por segunda vez en diciembre de 2025. Exige que las facturas no se puedan alterar sin dejar rastro y, en la modalidad Veri*Factu, que se envíen a la Agencia Tributaria nada más emitirse.',
            'fuente'  => 'Agencia Tributaria',
            'url'     => 'https://sede.agenciatributaria.gob.es/Sede/iva/sistemas-informaticos-facturacion-verifactu/nota-informativa-ampliacion-plazo-adaptacion-facturacion.html',
        ],
    ];
}

/**
 * Todas las fechas de la tabla que importan para calcular la caducidad de
 * la revisión: la de cada norma y, cuando exista y sea distinta, la de su
 * cuenta atrás. Pura -solo lee cumplimiento_normas()-, para que
 * cron/mantenimiento.php y la plantilla usen exactamente la misma lista.
 */
function cumplimiento_fechas(): array
{
    $fechas = [];

    foreach (cumplimiento_normas() as $norma) {
        $fechas[] = $norma['fecha'];

        if (!empty($norma['fecha_cuenta_atras'])) {
            $fechas[] = $norma['fecha_cuenta_atras'];
        }
    }

    return $fechas;
}

/**
 * Cuántos días quedan hasta una fecha, o cuántos han pasado ya -negativo-.
 * Pura, la usa la plantilla para pintar la cuenta atrás de cada norma.
 */
function cumplimiento_dias_hasta(string $fecha, string $hoy): ?int
{
    $fecha_ts = strtotime($fecha . ' UTC');
    $hoy_ts   = strtotime($hoy . ' UTC');

    if ($fecha_ts === false || $hoy_ts === false) {
        return null;
    }

    return (int) floor(($fecha_ts - $hoy_ts) / 86400);
}

/**
 * La fecha límite hasta la que sigue vigente la revisión de la página: la
 * más próxima de las fechas todavía pendientes en la tabla -filtradas aquí
 * mismo contra $hoy, no se confía en que quien llama ya las haya filtrado-,
 * o -si no queda ninguna- un plazo de respaldo desde la última revisión.
 *
 * @param string[] $fechas Todas las fechas ISO (Y-m-d) de la tabla, pasadas
 *                         o futuras.
 */
function cumplimiento_limite_revision(array $fechas, string $hoy, string $revisado): string
{
    $hoy_ts  = strtotime($hoy . ' UTC');
    $futuras = [];

    foreach ($fechas as $fecha) {
        $fecha_ts = strtotime($fecha . ' UTC');

        if ($fecha_ts !== false && $hoy_ts !== false && $fecha_ts > $hoy_ts) {
            $futuras[] = $fecha;
        }
    }

    if ($futuras) {
        sort($futuras);

        return $futuras[0];
    }

    $revisado_ts = strtotime($revisado . ' UTC');

    if ($revisado_ts === false) {
        return $revisado;
    }

    return gmdate('Y-m-d', $revisado_ts + CUMPLIMIENTO_CADUCIDAD_RESPALDO_DIAS * 86400);
}

/**
 * Si toca avisar de que /cumplimiento.html lleva vencido su plazo de
 * revisión. Mismo freno que cifras_caducadas(): un aviso por revisión, no
 * uno por día mientras nadie actualice la página.
 */
function cumplimiento_caducadas(string $limite, string $hoy, string $ultimo_aviso, string $revisado): bool
{
    $limite_ts = strtotime($limite . ' UTC');
    $hoy_ts    = strtotime($hoy . ' UTC');

    if ($limite_ts === false || $hoy_ts === false || $hoy_ts < $limite_ts) {
        return false;
    }

    return $ultimo_aviso !== $revisado;
}
