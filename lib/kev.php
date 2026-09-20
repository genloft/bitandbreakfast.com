<?php
/**
 * Boletin de vulnerabilidades: cruzar el catalogo KEV de CISA con el
 * catalogo de proveedores.
 *
 * Calculo puro, sin base de datos ni red -lo que descarga y escribe esta en
 * cron/kev.php-, igual que la fase 2 reparte las decisiones entre
 * lib/agrupar.php y cron/procesar.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/texto.php';
require_once __DIR__ . '/agrupar.php';

/**
 * Las entradas del catalogo que no se han visto todavia, de mas antigua a
 * mas nueva.
 *
 * El catalogo de CISA llega ordenado por dateAdded descendente -la mas
 * reciente primero-, y aqui se recorre en ese mismo orden, cortando en
 * cuanto se llega a una entrada ya vista: todo lo que viene despues es mas
 * antiguo todavia, y no hace falta seguir mirando.
 *
 * El resultado se devuelve invertido -de mas antigua a mas nueva- para que,
 * si el presupuesto de tiempo corta el lote a medias, lo procesado sea
 * siempre un tramo continuo desde donde se quedo la pasada anterior. Al
 * reves -de mas nueva a mas antigua- un corte a medias dejaria un hueco de
 * entradas antiguas sin procesar que la siguiente pasada daria por vistas
 * sin estarlo.
 */
function kev_pendientes(array $vulnerabilidades, string $puntero_fecha, array $puntero_vistos): array
{
    $nuevas = [];

    foreach ($vulnerabilidades as $v) {
        $fecha = (string) ($v['dateAdded'] ?? '');
        $cve   = (string) ($v['cveID'] ?? '');

        if ($fecha === '' || $cve === '') {
            continue;
        }

        if ($fecha > $puntero_fecha) {
            $nuevas[] = $v;
            continue;
        }

        if ($fecha === $puntero_fecha && !in_array($cve, $puntero_vistos, true)) {
            $nuevas[] = $v;
            continue;
        }

        break;
    }

    return array_reverse($nuevas);
}

/**
 * El puntero que le toca a la siguiente pasada, a partir de cuantas
 * entradas de kev_pendientes() se ha llegado a procesar en esta.
 *
 * @param array $pendientes Lo que devolvio kev_pendientes(), en el mismo orden.
 * @param int   $procesadas Cuantas de esas entradas -desde el principio- se
 *                          llegaron a procesar en este lote.
 */
function kev_puntero_siguiente(array $pendientes, int $procesadas, string $puntero_fecha, array $puntero_vistos): array
{
    if ($procesadas <= 0) {
        return ['fecha' => $puntero_fecha, 'vistos' => $puntero_vistos];
    }

    $hechas       = array_slice($pendientes, 0, $procesadas);
    $ultima       = end($hechas);
    $fecha_ultima = (string) $ultima['dateAdded'];

    // Si seguimos en el mismo dia que ya teniamos a medias, se amplia la
    // lista de vistas. Si se ha cruzado a un dia mas nuevo, el de antes
    // queda cerrado del todo y no hace falta recordar sus cveID: con
    // "dateAdded > fecha" ya basta para no volver a mirarlas.
    $vistos = $fecha_ultima === $puntero_fecha ? $puntero_vistos : [];

    foreach ($hechas as $v) {
        if ((string) $v['dateAdded'] === $fecha_ultima) {
            $vistos[] = (string) $v['cveID'];
        }
    }

    return ['fecha' => $fecha_ultima, 'vistos' => array_values(array_unique($vistos))];
}

/**
 * El primer puntero, la primera vez que corre esta tarea.
 *
 * No tiene sentido cruzar contra el catalogo entero de CISA -mas de mil
 * quinientas entradas desde 2021- el dia que se activa esta tarea: casi
 * todas son de hace anos, ya parcheadas, y convertirlas en avisos de golpe
 * enterraria las noticias de ese dia bajo un vertedero de CVEs viejas. La
 * primera pasada se pone al dia en silencio -el puntero salta directo a la
 * fecha mas reciente del catalogo- y de ahi en adelante solo entra lo que
 * CISA anada de nuevo.
 */
function kev_puntero_inicial(array $vulnerabilidades): array
{
    if (!$vulnerabilidades) {
        return ['fecha' => '', 'vistos' => []];
    }

    $fecha  = (string) ($vulnerabilidades[0]['dateAdded'] ?? '');
    $vistos = [];

    foreach ($vulnerabilidades as $v) {
        if ((string) ($v['dateAdded'] ?? '') !== $fecha) {
            break;
        }

        $vistos[] = (string) ($v['cveID'] ?? '');
    }

    return ['fecha' => $fecha, 'vistos' => $vistos];
}

/**
 * Los proveedores del catalogo que menciona una entrada del KEV, cruzando
 * vendorProject, product y vulnerabilityName contra el alias normalizado -el
 * mismo mecanismo que agrupar_proveedores_en() usa para los items normales,
 * asi que "Sabre" o "Adyen" sueltos casan igual de bien -o igual de mal- que
 * en cualquier otro texto del sitio.
 *
 * @param array $alias 'alias_norm' => proveedor_id, como construye
 *                      procesar_alias().
 */
function kev_proveedores_de(array $vulnerabilidad, array $alias): array
{
    $texto = trim(
        (string) ($vulnerabilidad['vendorProject'] ?? '') . ' ' .
        (string) ($vulnerabilidad['product'] ?? '') . ' ' .
        (string) ($vulnerabilidad['vulnerabilityName'] ?? '')
    );

    return agrupar_proveedores_en($texto, null, $alias);
}

/**
 * Como se nombra en ingles cada categoria del catalogo de proveedores,
 * siempre con la palabra "hotel" o "hotels" dentro.
 *
 * No es un adorno: el texto de CISA no dice "hotel" en ningun sitio -habla
 * de "Oracle" o de "SiteMinder", no de para que sirven-, y la puerta del
 * sector de auto.php (auto_es_del_sector(), deliberadamente barata: busca
 * la palabra en el texto) descartaria esto igual que descarta una
 * vulnerabilidad de Chrome. Esta frase no inventa nada sobre la
 * vulnerabilidad -sigue siendo la de CISA, palabra por palabra-: solo hace
 * explicito un dato que este sitio ya tiene comprobado en su propio
 * catalogo, la categoria del proveedor.
 *
 * En ingles y no en espanol porque el resto del titular tambien lo esta -es
 * el texto de CISA, sin traducir todavia-, y así el bit entero pasa entero
 * por el mismo traductor que cualquier otra fuente en ingles, en vez de
 * dejar una frase suelta a medio traducir.
 */
function kev_categoria_legible(string $categoria): string
{
    return match ($categoria) {
        'pms'                  => 'hotel property-management systems (PMS)',
        'distribucion'         => 'hotel distribution',
        'channel-manager'      => 'hotel channel managers',
        'motor-reserva'        => 'hotel booking engines',
        'rms'                  => 'hotel revenue-management systems (RMS)',
        'inteligencia-mercado' => 'hotel market intelligence',
        'crm-marketing'        => 'hotel guest CRM and marketing',
        'mensajeria'           => 'hotel guest messaging',
        'operaciones'          => 'hotel operations software',
        'accesos'              => 'hotel access-control systems',
        'pagos'                => 'hotel payment systems',
        default                => 'hotel technology',
    };
}

/**
 * El item sintetico a partir de una entrada del KEV que ya se sabe que
 * menciona un proveedor del catalogo.
 *
 * No inventa nada que CISA no diga: el titular cita el producto y el
 * proveedor tal cual los da el catalogo, y el resumen es su descripcion
 * oficial mas la accion y el plazo que impone la agencia. La unica pieza
 * que anade este sitio es kev_categoria_legible(), y esa no es una
 * suposicion sobre esta vulnerabilidad -es un dato ya verificado en
 * sql/semilla_proveedores.sql, no algo que se decida aqui-.
 *
 * El resultado tiene la misma forma que feed_entrada() en lib/feed.php
 * -guid, url, titulo, resumen, autor, publicado-, para poder guardarse con
 * la misma funcion que usa la ingesta normal.
 */
function kev_entrada_a_item(array $vulnerabilidad, string $categoria_proveedor): array
{
    $cve      = (string) ($vulnerabilidad['cveID'] ?? '');
    $vendedor = trim((string) ($vulnerabilidad['vendorProject'] ?? ''));
    $producto = trim((string) ($vulnerabilidad['product'] ?? ''));
    $nombre   = trim((string) ($vulnerabilidad['vulnerabilityName'] ?? ''));

    $titulo = sprintf(
        'CISA confirms active exploitation of a vulnerability in %s (%s), used in %s',
        $producto !== '' ? $producto : 'an undisclosed product',
        $vendedor !== '' ? $vendedor : 'an undisclosed vendor',
        kev_categoria_legible($categoria_proveedor)
    );

    $partes = [];

    if ($nombre !== '') {
        $partes[] = rtrim($nombre, '.') . '.';
    }

    if (trim((string) ($vulnerabilidad['shortDescription'] ?? '')) !== '') {
        $partes[] = trim((string) $vulnerabilidad['shortDescription']);
    }

    if (trim((string) ($vulnerabilidad['requiredAction'] ?? '')) !== '') {
        $partes[] = 'CISA-required action: ' . trim((string) $vulnerabilidad['requiredAction']);
    }

    if (trim((string) ($vulnerabilidad['dueDate'] ?? '')) !== '') {
        $partes[] = 'Federal remediation deadline: ' . trim((string) $vulnerabilidad['dueDate']) . '.';
    }

    if ((string) ($vulnerabilidad['knownRansomwareCampaignUse'] ?? '') === 'Known') {
        $partes[] = 'CISA confirms this vulnerability has been used in ransomware campaigns.';
    }

    $fecha = (string) ($vulnerabilidad['dateAdded'] ?? '');

    return [
        'guid'      => 'kev-' . $cve,
        // El NVD tiene ficha propia de cada CVE, publica y estable: es donde
        // comprobar el aviso sin depender de que el catalogo de CISA siga
        // teniendo esta entrada en su primera pagina.
        'url'       => $cve !== '' ? 'https://nvd.nist.gov/vuln/detail/' . $cve : '',
        'titulo'    => $titulo,
        'resumen'   => trim(implode(' ', $partes)),
        'autor'     => 'CISA',
        'publicado' => $fecha !== '' ? $fecha . ' 00:00:00' : null,
    ];
}
