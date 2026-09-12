<?php
/**
 * Arnes de pruebas minimo. Sin framework: compara esperado contra real,
 * cuenta aciertos y devuelve codigo de salida 1 si algo falla, para que se
 * pueda encadenar en un script.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Las pruebas solo se ejecutan por linea de comandos.\n");
}

$GLOBALS['pruebas_ok']     = 0;
$GLOBALS['pruebas_fallos'] = [];

function comprobar(string $descripcion, $esperado, $real): void
{
    if ($esperado === $real) {
        $GLOBALS['pruebas_ok']++;
        return;
    }

    $GLOBALS['pruebas_fallos'][] = sprintf(
        "  %s\n    esperado: %s\n    real:     %s",
        $descripcion,
        var_export($esperado, true),
        var_export($real, true)
    );
}

/**
 * Para valores que no son deterministas, como la similitud: se comprueba que
 * caen dentro de un rango en lugar de exigir un numero exacto.
 */
function comprobar_rango(string $descripcion, float $minimo, float $maximo, float $real): void
{
    if ($real >= $minimo && $real <= $maximo) {
        $GLOBALS['pruebas_ok']++;
        return;
    }

    $GLOBALS['pruebas_fallos'][] = sprintf(
        "  %s\n    esperado: entre %.2f y %.2f\n    real:     %.4f",
        $descripcion,
        $minimo,
        $maximo,
        $real
    );
}

function resumen_pruebas(string $titulo): void
{
    $fallos = $GLOBALS['pruebas_fallos'];

    echo "\n$titulo\n";
    echo str_repeat('-', strlen($titulo)) . "\n";

    if (!$fallos) {
        printf("%d comprobaciones, todas correctas.\n", $GLOBALS['pruebas_ok']);
        exit(0);
    }

    printf("%d correctas, %d FALLIDAS:\n\n", $GLOBALS['pruebas_ok'], count($fallos));
    echo implode("\n\n", $fallos) . "\n";
    exit(1);
}
