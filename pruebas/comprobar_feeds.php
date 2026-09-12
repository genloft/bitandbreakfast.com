<?php
/**
 * Comprueba que las fuentes del catalogo siguen vivas.
 *
 *   php pruebas/comprobar_feeds.php            todas las fuentes activas
 *   php pruebas/comprobar_feeds.php todas      incluidas las desactivadas
 *
 * No escribe nada en la base de datos: solo informa. Los feeds se mueren o
 * cambian de URL cada pocos meses, y esto es lo que lo detecta antes de que
 * el radar se quede ciego por un lado.
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/feed.php';

$incluir_inactivas = ($argv[1] ?? '') === 'todas';

$sql = 'SELECT id, nombre, url_feed, activa, fallos_consecutivos
          FROM fuentes'
     . ($incluir_inactivas ? '' : ' WHERE activa = 1')
     . ' ORDER BY id';

$fuentes = bd()->query($sql)->fetchAll();

printf("Comprobando %d fuentes con %s\n\n", count($fuentes), config('rastreador.user_agent'));

$vivas = 0;
$rotas = [];

foreach ($fuentes as $fuente) {
    // Sin cabeceras condicionales: aqui queremos el feed entero para contar
    // sus entradas, no un 304 barato.
    $respuesta = feed_descargar($fuente['url_feed']);
    $entradas  = $respuesta['codigo'] === 200 ? feed_parsear($respuesta['cuerpo'], $fuente['url_feed']) : [];

    if ($respuesta['codigo'] === 200 && $entradas) {
        $vivas++;
        printf("  OK   %3d entradas  %-34s\n", count($entradas), $fuente['nombre']);
        continue;
    }

    $motivo = $respuesta['codigo'] === 200
        ? 'responde pero no se le sacan entradas'
        : 'HTTP ' . $respuesta['codigo'] . ($respuesta['error'] !== '' ? ' - ' . $respuesta['error'] : '');

    $rotas[] = sprintf("  #%-3d %-34s %s\n       %s", $fuente['id'], $fuente['nombre'], $motivo, $fuente['url_feed']);
    printf("  MAL  %-38s %s\n", $fuente['nombre'], $motivo);
}

printf("\n%d vivas, %d con problemas.\n", $vivas, count($rotas));

if ($rotas) {
    echo "\nFuentes a revisar:\n\n" . implode("\n", $rotas) . "\n";
    echo "\nPara desactivar una:  UPDATE fuentes SET activa = 0 WHERE id = ?;\n";
    exit(1);
}

exit(0);
