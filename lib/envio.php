<?php
/**
 * El correo de una edicion: como se escribe y a quien se le manda.
 *
 * Lo que se manda es la edicion, no un resumen de la edicion con un "sigue
 * leyendo". Quien se suscribe a un boletin de cinco minutos quiere leerlo en
 * el correo; obligarle a pulsar para leer lo que ya prometia el asunto es
 * pedirle un favor a cambio de nada.
 *
 * El HTML es de 1998 a proposito: tablas no, pero si estilos en linea, un
 * ancho fijo y ni una imagen. Los clientes de correo no tienen hoja de estilo
 * externa, la mitad no entienden variables CSS y Gmail recorta lo que pasa de
 * 102 KB. Todo lo que aqui parece antiguo esta puesto para que se lea igual en
 * Outlook que en el movil.
 *
 * Las funciones son puras -reciben la edicion y sus bits, devuelven texto-,
 * asi que el correo entero se puede comprobar en las pruebas sin mandar nada
 * a nadie.
 */

declare(strict_types=1);

require_once __DIR__ . '/web.php';
require_once __DIR__ . '/bits.php';

/**
 * El asunto: el titulo del dia, si alguien se lo ha puesto, o su titular.
 *
 * Sin emojis, sin "no te pierdas" y sin el nombre del boletin repetido: el
 * remitente ya lo dice, y un asunto que empieza por la marca desperdicia los
 * cuarenta caracteres que se ven en el movil.
 */
function envio_asunto(array $edicion, array $bits): string
{
    $titulo = trim((string) ($edicion['titulo'] ?? ''));

    if ($titulo !== '') {
        return $titulo;
    }

    if (!$bits) {
        return 'Bit & Breakfast, ' . web_fecha_larga((string) $edicion['fecha_prevista']);
    }

    // Sin titulo escrito a mano, manda el primer titular: es lo que de verdad
    // trae la edicion, y es lo que hace que se abra.
    return texto_recortar((string) $bits[0]['titular'], 90);
}

/**
 * La edicion en texto plano.
 *
 * No es el premio de consolacion para quien no ve HTML: es lo que leen los
 * filtros antispam para decidir si esto es un correo o un folleto, y lo que
 * se ve en la vista previa de muchos clientes.
 *
 * $votos_urls, opcional, lleva un par ['si' => url, 'no' => url] por cada id
 * de bit que pueda votarse. Sin el -el valor por defecto-, la edicion se
 * manda igual, solo que sin la pregunta: asi las pruebas y cualquier llamada
 * antigua siguen valiendo sin tocarlas.
 */
function envio_texto(array $edicion, array $bits, string $base, string $url_baja, array $votos_urls = []): string
{
    $categorias = bits_categorias();
    $lineas     = [];

    $lineas[] = 'BIT & BREAKFAST';
    $lineas[] = web_fecha_larga((string) $edicion['fecha_prevista'])
              . ' · ' . count($bits) . ' noticia' . (count($bits) === 1 ? '' : 's');
    $lineas[] = '';

    if (trim((string) ($edicion['intro'] ?? '')) !== '') {
        $lineas[] = trim((string) $edicion['intro']);
        $lineas[] = '';
    }

    foreach ($bits as $indice => $bit) {
        $tema = bits_categoria_canonica((string) $bit['categoria']);
        $voto = $votos_urls[(int) $bit['id']] ?? null;

        $lineas[] = str_repeat('-', 60);
        $lineas[] = ($indice + 1) . '. ' . (string) $bit['titular'];
        $lineas[] = '[' . ($categorias[$tema] ?? $bit['categoria']) . ']';
        $lineas[] = '';
        $lineas[] = trim((string) $bit['cuerpo']);

        if (trim((string) ($bit['por_que'] ?? '')) !== '') {
            $lineas[] = '';
            $lineas[] = 'Por qué importa: ' . trim((string) $bit['por_que']);
        }

        if (!empty($bit['url'])) {
            $lineas[] = '';
            $lineas[] = trim((string) ($bit['fuente'] ?? 'Fuente')) . ': ' . (string) $bit['url'];
        }

        if ($voto !== null) {
            $lineas[] = '';
            $lineas[] = '¿Te ha servido esta noticia? Sí: ' . $voto['si'] . ' · No: ' . $voto['no'];
        }

        $lineas[] = '';
    }

    $lineas[] = str_repeat('-', 60);
    $lineas[] = 'Leer en la web: ' . web_url_dia($base, (string) $edicion['fecha_prevista']);
    $lineas[] = 'Darte de baja: ' . $url_baja;

    return implode("\n", $lineas) . "\n";
}

/**
 * La edicion en HTML.
 *
 * Mismo orden y mismo contenido que el texto. Los colores van escritos a mano
 * y no con variables: en el correo no hay hoja de estilo que las declare.
 *
 * $votos_urls, igual que en envio_texto(): un par ['si' => url, 'no' => url]
 * por id de bit, opcional.
 */
function envio_html(array $edicion, array $bits, string $base, string $url_baja, array $votos_urls = []): string
{
    $categorias = bits_categorias();
    $e = static fn (string $t): string => htmlspecialchars($t, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    // Los mismos que la web: papel, tinta negra y un rojo. El correo llevaba
    // todavia los del diseno oscuro de antes, asi que quien recibia el boletin
    // y despues entraba en la web veia dos sitios distintos.
    $fondo  = '#f4f2ee';
    $papel  = '#faf9f7';
    $tinta  = '#0d0d0d';
    $suave  = '#5f5c57';
    $laton  = '#d02b1f';
    $borde  = '#cfcac2';

    $serif = "Georgia,'Times New Roman',serif";
    $sans  = "-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif";

    $h = '<!doctype html><html lang="es"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>' . $e(envio_asunto($edicion, $bits)) . '</title></head>'
       . '<body style="margin:0;padding:0;background:' . $fondo . ';">'
       . '<div style="max-width:600px;margin:0 auto;padding:28px 22px 40px;'
       . 'background:' . $fondo . ';color:' . $tinta . ';font-family:' . $serif . ';'
       . 'font-size:17px;line-height:1.65;">';

    // Cabecera: el nombre y poco mas. En un correo, la marca no necesita
    // ocupar media pantalla; ya se sabe de quien es porque lo han pedido.
    $h .= '<p style="margin:0 0 4px;font-size:30px;line-height:1.1;">Bit'
        . '<span style="color:' . $laton . ';font-style:italic;">&amp;</span>Breakfast</p>'
        . '<p style="margin:0 0 26px;font-family:' . $sans . ';font-size:12px;'
        . 'letter-spacing:.12em;text-transform:uppercase;color:' . $suave . ';">'
        . $e(web_fecha_larga((string) $edicion['fecha_prevista'])) . ' &middot; '
        . count($bits) . ' noticia' . (count($bits) === 1 ? '' : 's') . '</p>';

    if (trim((string) ($edicion['intro'] ?? '')) !== '') {
        $h .= '<div style="margin:0 0 28px;color:' . $suave . ';font-style:italic;">'
            . web_parrafos((string) $edicion['intro']) . '</div>';
    }

    foreach ($bits as $indice => $bit) {
        $tema = bits_categoria_canonica((string) $bit['categoria']);
        $voto = $votos_urls[(int) $bit['id']] ?? null;

        $h .= '<div style="border-top:1px solid ' . $borde . ';padding:24px 0 4px;">'
            . '<p style="margin:0 0 8px;font-family:' . $sans . ';font-size:11px;'
            . 'letter-spacing:.12em;text-transform:uppercase;color:' . $laton . ';">'
            . str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) . ' &middot; '
            . $e($categorias[$tema] ?? (string) $bit['categoria']) . '</p>'
            . '<h2 style="margin:0 0 10px;font-size:21px;line-height:1.28;'
            . 'font-weight:400;">' . $e((string) $bit['titular']) . '</h2>'
            . '<div style="margin:0 0 10px;">' . web_parrafos((string) $bit['cuerpo']) . '</div>';

        if (trim((string) ($bit['por_que'] ?? '')) !== '') {
            $h .= '<p style="margin:0 0 10px;padding-left:12px;border-left:2px solid ' . $laton . ';'
                . 'color:' . $suave . ';font-size:16px;"><strong style="color:' . $tinta . ';">'
                . 'Por qué importa.</strong> ' . $e((string) $bit['por_que']) . '</p>';
        }

        if (!empty($bit['url'])) {
            $h .= '<p style="margin:0 0 6px;font-family:' . $sans . ';font-size:12px;'
                . 'letter-spacing:.06em;text-transform:uppercase;">'
                . '<a href="' . $e((string) $bit['url']) . '" style="color:' . $laton . ';'
                . 'text-decoration:none;">' . $e(trim((string) ($bit['fuente'] ?? 'Leer la fuente')))
                . ' &rarr;</a></p>';
        }

        if ($voto !== null) {
            $h .= '<p style="margin:6px 0 0;font-family:' . $sans . ';font-size:12px;color:' . $suave . ';">'
                . '¿Te ha servido esta noticia? '
                . '<a href="' . $e((string) $voto['si']) . '" style="color:' . $suave . ';">Sí</a>'
                . ' &middot; '
                . '<a href="' . $e((string) $voto['no']) . '" style="color:' . $suave . ';">No</a></p>';
        }

        $h .= '</div>';
    }

    $h .= '<div style="border-top:1px solid ' . $borde . ';margin-top:8px;padding-top:20px;'
        . 'font-family:' . $sans . ';font-size:12px;line-height:1.7;color:' . $suave . ';">'
        . '<p style="margin:0 0 6px;"><a href="' . $e(web_url_dia($base, (string) $edicion['fecha_prevista']))
        . '" style="color:' . $suave . ';">Leer este día en la web</a></p>'
        . '<p style="margin:0;"><a href="' . $e($url_baja) . '" style="color:' . $suave . ';">'
        . 'Darte de baja</a> &middot; un clic, sin preguntas.</p>'
        . '</div></div></body></html>';

    return $h;
}

/**
 * El asunto del aviso del cron.
 *
 * Lo importante primero, porque en el movil se ven cuarenta caracteres: si ha
 * entrado algo, cuanto; si no, que no. Asi se puede saber si hace falta abrirlo
 * sin abrirlo, que es lo que se le pide a un aviso que llega cada hora.
 */
function aviso_asunto(array $datos): string
{
    $nuevos     = (int) ($datos['nuevos'] ?? 0);
    $archivados = (int) ($datos['archivados'] ?? 0);
    $errores    = (int) ($datos['errores'] ?? 0);

    $partes = [];

    if ($nuevos > 0) {
        $partes[] = $nuevos . ' nueva' . ($nuevos === 1 ? '' : 's');
    }

    if ($archivados > 0) {
        $partes[] = $archivados . ' al archivo';
    }

    if ($errores > 0) {
        $partes[] = $errores . ' error' . ($errores === 1 ? '' : 'es');
    }

    if (!$partes) {
        $partes[] = 'sin novedades';
    }

    return 'Radar · ' . implode(' · ', $partes);
}

/**
 * El cuerpo del aviso: lo que ha hecho la pasada, en texto plano.
 *
 * Sin HTML a proposito. Esto no es un boletin, es un parte: se lee en diez
 * segundos, se archiva y no se ensena a nadie.
 *
 * @param array $datos  nuevos, archivados, errores, cuando
 * @param array $lineas El registro de la pasada, una linea por tarea.
 */
function aviso_cuerpo(array $datos, array $lineas, string $base): string
{
    $nuevos     = (int) ($datos['nuevos'] ?? 0);
    $archivados = (int) ($datos['archivados'] ?? 0);

    $texto = [];

    $texto[] = 'Pasada del ' . gmdate('d/m/Y H:i') . ' UTC.';
    $texto[] = '';

    $texto[] = $nuevos > 0
        ? '- Han entrado ' . $nuevos . ' noticia' . ($nuevos === 1 ? '' : 's') . ' nueva' . ($nuevos === 1 ? '' : 's') . '.'
        : '- No ha entrado ninguna noticia nueva.';

    if ($archivados > 0) {
        $texto[] = '- ' . $archivados . ($archivados === 1 ? ' ha pasado' : ' han pasado')
                 . ' al archivo al cerrarse la edicion anterior.';
    }

    $texto[] = '';
    $texto[] = 'Lo que ha hecho cada tarea:';
    $texto[] = '';

    foreach ($lineas as $linea) {
        $texto[] = '  ' . trim((string) $linea);
    }

    $texto[] = '';
    $texto[] = 'La web: ' . $base . '/';
    $texto[] = 'El estado: ' . $base . '/salud.php';
    $texto[] = '';
    $texto[] = 'Este aviso sale en cada pasada del cron. Para recibirlo solo';
    $texto[] = 'cuando haya cambios, pon el ajuste cron_aviso a "cambios";';
    $texto[] = 'para no recibirlo, a "no".';

    return implode(PHP_EOL, $texto) . PHP_EOL;
}
