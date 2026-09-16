<?php
/**
 * Pruebas de lib/instalacion.php.  Ejecutar:  php pruebas/instalacion.php
 *
 * Solo se prueba lo que no toca ni la base de datos ni el estado del
 * servidor: el troceado del SQL, la validacion del formulario y la
 * configuracion generada. El cerrojo y la autodestruccion no se prueban aqui
 * a proposito, porque escriben y borran ficheros reales del despliegue.
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/instalacion.php';

// --- Troceado de ficheros .sql ----------------------------------------------

comprobar(
    'separa dos sentencias',
    ['CREATE TABLE a (id INT)', 'INSERT INTO a VALUES (1)'],
    inst_sentencias('CREATE TABLE a (id INT); INSERT INTO a VALUES (1);')
);

comprobar(
    'no parte por un punto y coma que va dentro de una cadena',
    ["INSERT INTO f (nombre) VALUES ('Skift; Hospitality')"],
    inst_sentencias("INSERT INTO f (nombre) VALUES ('Skift; Hospitality');")
);

comprobar(
    'descarta los comentarios de linea',
    ['SELECT 1'],
    inst_sentencias("-- semilla de fuentes\nSELECT 1;\n-- fin\n")
);

comprobar(
    'respeta la comilla escapada dentro de una cadena',
    ["INSERT INTO f (nombre) VALUES ('Hotel\\'s Tech Report')"],
    inst_sentencias("INSERT INTO f (nombre) VALUES ('Hotel\\'s Tech Report');")
);

// El salto de linea que cierra el comentario se conserva: sin el, "1" y
// "FROM" acabarian pegados en la misma palabra.
comprobar(
    'el salto que cierra un comentario sigue separando palabras',
    ["SELECT 1 \nFROM ajustes"],
    inst_sentencias("SELECT 1 -- de donde\nFROM ajustes;")
);

comprobar(
    'una sentencia sin punto y coma final tambien cuenta',
    ['SELECT 1'],
    inst_sentencias('SELECT 1')
);

comprobar(
    'un fichero en blanco no da ninguna sentencia',
    [],
    inst_sentencias("\n\n-- nada\n")
);

// --- Validacion del formulario ----------------------------------------------

$validos = [
    'host'          => 'localhost',
    'nombre'        => 'u123456789_bitb',
    'usuario'       => 'u123456789_bitb',
    'puerto'        => 3306,
    'dominio'       => 'bitandbreakfast.com',
    'panel_usuario' => '',
    'panel_clave'   => '',
];

comprobar(
    'un formulario correcto no da errores',
    [],
    inst_validar($validos)
);

comprobar(
    'el dominio con esquema se rechaza',
    1,
    count(inst_validar(['dominio' => 'https://bitandbreakfast.com'] + $validos))
);

comprobar(
    'el dominio con barra final se rechaza',
    1,
    count(inst_validar(['dominio' => 'bitandbreakfast.com/'] + $validos))
);

comprobar(
    'falta el nombre de la base',
    1,
    count(inst_validar(['nombre' => ''] + $validos))
);

comprobar(
    'el servidor con punto y coma se rechaza, que acaba en el DSN',
    1,
    count(inst_validar(['host' => 'localhost;charset=latin1'] + $validos))
);

comprobar(
    'el servidor con puerto pegado se rechaza',
    1,
    count(inst_validar(['host' => 'localhost:3306'] + $validos))
);

comprobar(
    'el nombre de base con caracteres raros se rechaza',
    1,
    count(inst_validar(['nombre' => 'bitb;DROP'] + $validos))
);

comprobar(
    'el puerto fuera de rango se rechaza',
    1,
    count(inst_validar(['puerto' => 0] + $validos))
);

comprobar(
    'con usuario de panel, la clave corta se rechaza',
    1,
    count(inst_validar(['panel_usuario' => 'juan', 'panel_clave' => 'corta'] + $validos))
);

comprobar(
    'con usuario de panel y clave larga, no hay error',
    [],
    inst_validar(['panel_usuario' => 'juan', 'panel_clave' => 'doce-caracteres-y-mas'] + $validos)
);

// --- Configuracion generada -------------------------------------------------
//
// Lo que se comprueba aqui es que una contrasena con comillas, barras
// invertidas y signos de dolar sobrevive entera y no rompe el fichero: si
// var_export se cambiara por una interpolacion, esta prueba lo caza.

$clave_retorcida = 'a\'b"c\\d$e{f}';

$php = inst_plantilla_config([
    'host'         => 'localhost',
    'nombre'       => 'u123456789_bitb',
    'usuario'      => 'u123456789_bitb',
    'clave'        => $clave_retorcida,
    'puerto'       => 3306,
    'dominio'      => 'bitandbreakfast.com',
    'secreto_hmac' => str_repeat('a', 64),
    'token_api'    => str_repeat('b', 64),
    'sal_hash'     => str_repeat('c', 64),
]);

$temporal = sys_get_temp_dir() . '/bitb_config_prueba_' . getmypid() . '.php';
file_put_contents($temporal, $php);
$config = require $temporal;

comprobar(
    'el token se relee de la configuracion escrita',
    str_repeat('b', 64),
    inst_token_api($temporal)
);

comprobar(
    'sin fichero de configuracion, el token sale vacio',
    '',
    inst_token_api($temporal . '.noexiste')
);

unlink($temporal);

comprobar(
    'la configuracion generada es PHP valido y devuelve un array',
    true,
    is_array($config)
);

comprobar(
    'la contrasena viaja intacta, comillas y barras incluidas',
    $clave_retorcida,
    $config['bd']['clave']
);

comprobar(
    'el puerto se guarda como entero',
    3306,
    $config['bd']['puerto']
);

comprobar(
    'la url del sitio se arma con https',
    'https://bitandbreakfast.com',
    $config['sitio']['url']
);

comprobar(
    'el user-agent lleva el prefijo que evita los 403',
    0,
    strpos($config['rastreador']['user_agent'], 'Mozilla/5.0 (compatible;')
);

comprobar(
    'el remitente cuelga del dominio configurado',
    'hola@bitandbreakfast.com',
    $config['correo']['remitente']
);

comprobar(
    'los tres secretos llegan enteros',
    [64, 64, 64],
    [
        strlen($config['secretos']['secreto_hmac']),
        strlen($config['secretos']['token_api']),
        strlen($config['secretos']['sal_hash']),
    ]
);

// --- Tabla de requisitos ----------------------------------------------------

comprobar(
    'una tabla de requisitos con todo en verde pasa',
    true,
    inst_requisitos_ok([['a', true, ''], ['b', true, '']])
);

comprobar(
    'un solo requisito en rojo tumba la tabla',
    false,
    inst_requisitos_ok([['a', true, ''], ['b', false, ''], ['c', true, '']])
);

resumen_pruebas('Pruebas de lib/instalacion.php');
