<?php
/**
 * Pruebas de lib/correo.php.  Ejecutar:  php pruebas/correo.php
 *
 * No sale a la red: lo que se comprueba es la validacion de la direccion y la
 * carga util que se le manda a cada proveedor. Un cambio de nombre de campo
 * ahi no se ve hasta que alguien intenta suscribirse de verdad, y para
 * entonces ya se ha perdido el lector.
 *
 * Los dos formatos se han contrastado con la documentacion de cada proveedor:
 *
 *   MailerLite  POST https://connect.mailerlite.com/api/subscribers
 *               Authorization: Bearer <clave>, status "unconfirmed"
 *   Brevo       POST https://api.brevo.com/v3/contacts/doubleOptinConfirmation
 *               api-key: <clave>, includeListIds, templateId, redirectionUrl
 */

require_once __DIR__ . '/ayuda.php';
require_once dirname(__DIR__) . '/lib/correo.php';
require_once dirname(__DIR__) . '/lib/smtp.php';
require_once dirname(__DIR__) . '/lib/migrar.php';
require_once dirname(__DIR__) . '/lib/envio.php';

// --- Normalizacion ----------------------------------------------------------

comprobar(
    'el dominio pasa a minusculas',
    'juan@hotel.com',
    correo_normalizar('juan@HOTEL.COM')
);

// La parte local distingue mayusculas segun el estandar: no es cosa nuestra
// decidir que Juan@ y juan@ son la misma persona.
comprobar(
    'la parte local se respeta tal cual',
    'Juan.Garcia@hotel.com',
    correo_normalizar('  Juan.Garcia@HOTEL.com  ')
);

comprobar(
    'una cadena sin arroba se devuelve limpia pero sin tocar',
    'esto no es un correo',
    correo_normalizar('  esto no es un correo  ')
);

// --- Validacion -------------------------------------------------------------

comprobar('una direccion normal vale', true, correo_valido('juan@hotel.com'));
comprobar('una direccion con subdominio vale', true, correo_valido('juan@mail.hotel.co.uk'));
comprobar('sin arroba no vale', false, correo_valido('juan.hotel.com'));
comprobar('sin dominio no vale', false, correo_valido('juan@'));
comprobar('vacia no vale', false, correo_valido(''));

// Un salto de linea dentro de la direccion es un intento de colar cabeceras.
comprobar(
    'una direccion con salto de linea no vale',
    false,
    correo_valido("juan@hotel.com\nBcc: otro@sitio.com")
);

comprobar(
    'una direccion absurdamente larga no vale',
    false,
    correo_valido(str_repeat('a', 250) . '@hotel.com')
);

// --- Configuracion ----------------------------------------------------------

$mailerlite = [
    'proveedor'     => 'mailerlite',
    'api_key'       => 'clave-secreta',
    'lista'         => '123456789',
    'doi_plantilla' => 0,
    'remitente'     => 'hola@bitandbreakfast.com',
];

$brevo = [
    'proveedor'     => 'brevo',
    'api_key'       => 'clave-secreta',
    'lista'         => '7',
    'doi_plantilla' => 12,
    'remitente'     => 'hola@bitandbreakfast.com',
];

comprobar('con clave y lista, mailerlite esta listo', true, correo_configurado($mailerlite));
comprobar('sin clave no hay alta', false, correo_configurado(['api_key' => ''] + $mailerlite));

// Dar de alta en ninguna lista es perder el correo del lector sin decirselo.
comprobar('sin lista tampoco', false, correo_configurado(['lista' => ''] + $mailerlite));

comprobar('con plantilla de confirmacion, brevo esta listo', true, correo_configurado($brevo));

// Brevo manda el correo de confirmacion con una plantilla suya: sin ella el
// alta se queda a medias y el lector nunca confirma.
comprobar(
    'brevo sin plantilla de confirmacion no esta listo',
    false,
    correo_configurado(['doi_plantilla' => 0] + $brevo)
);

// --- Carga util de MailerLite -----------------------------------------------

$carga = correo_carga_alta('juan@hotel.com', $mailerlite, 'https://bitandbreakfast.com/');
$cuerpo = json_decode($carga['cuerpo'], true);

comprobar(
    'mailerlite recibe la peticion en su endpoint',
    'https://connect.mailerlite.com/api/subscribers',
    $carga['url']
);

comprobar(
    'y la clave como testigo Bearer',
    true,
    in_array('Authorization: Bearer clave-secreta', $carga['cabeceras'], true)
);

comprobar('el cuerpo lleva la direccion', 'juan@hotel.com', $cuerpo['email']);

// El estado es lo que dispara la doble confirmacion.
comprobar('y el estado sin confirmar', 'unconfirmed', $cuerpo['status']);

comprobar('y el grupo de destino', ['123456789'], $cuerpo['groups']);

// --- Carga util de Brevo ----------------------------------------------------

$carga = correo_carga_alta('juan@hotel.com', $brevo, 'https://bitandbreakfast.com/');
$cuerpo = json_decode($carga['cuerpo'], true);

comprobar(
    'brevo usa su endpoint de doble confirmacion',
    'https://api.brevo.com/v3/contacts/doubleOptinConfirmation',
    $carga['url']
);

comprobar(
    'y la clave en su cabecera propia',
    true,
    in_array('api-key: clave-secreta', $carga['cabeceras'], true)
);

comprobar('la lista viaja como entero en un array', [7], $cuerpo['includeListIds']);
comprobar('la plantilla de confirmacion viaja como entero', 12, $cuerpo['templateId']);
comprobar('y la url de vuelta', 'https://bitandbreakfast.com/', $cuerpo['redirectionUrl']);

// Las barras no se escapan: una URL con \/ dentro del JSON es valida pero
// ilegible en el registro del proveedor cuando algo falla.
comprobar(
    'la url no sale con las barras escapadas',
    true,
    str_contains($carga['cuerpo'], 'https://bitandbreakfast.com/')
);

// --- Interpretacion de la respuesta -----------------------------------------

comprobar('201 es un alta buena', true, correo_interpretar(201, '{}')['ok']);

// Al que ya estaba hay que decirle lo mismo que al que no: lo contrario
// permite averiguar quien esta en la lista probando direcciones.
comprobar('200 tambien, aunque ya estuviera', true, correo_interpretar(200, '{}')['ok']);

comprobar('422 no es un alta', false, correo_interpretar(422, '{}')['ok']);
comprobar('401 no es un alta', false, correo_interpretar(401, '{}')['ok']);

comprobar(
    'un 401 no le cuenta al lector que pasa con la clave',
    false,
    str_contains(correo_interpretar(401, 'invalid api key')['mensaje'], 'api')
);

comprobar('429 pide paciencia', false, correo_interpretar(429, '{}')['ok']);

comprobar(
    'un codigo raro se cuenta tal cual',
    true,
    str_contains(correo_interpretar(503, 'nope')['mensaje'], '503')
);

// --- El mensaje que se manda ------------------------------------------------
//
// El cuerpo del correo se construye sin tocar la red, asi que se puede
// comprobar entero. Es donde estan los accidentes clasicos: un asunto con
// acentos sin codificar, una cabecera inyectada con un salto de linea.

$buzon = [
    'host'      => 'smtp.ejemplo.com',
    'puerto'    => 465,
    'usuario'   => 'conserje@ejemplo.com',
    'clave'     => 'no-se-usa-aqui',
    'remitente' => 'conserje@ejemplo.com',
    'nombre'    => 'Bit & Breakfast',
];

$mensaje = smtp_cuerpo($buzon, [
    'para'      => 'lector@ejemplo.com',
    'asunto'    => 'Edicion numero 3',
    'texto'     => 'Hola',
    'html'      => '<p>Hola</p>',
    'cabeceras' => ['List-Unsubscribe: <https://ejemplo.com/baja>'],
]);

comprobar('el mensaje dice de quien viene', true, str_contains($mensaje, 'From: Bit & Breakfast <conserje@ejemplo.com>'));
comprobar('y para quien es', true, str_contains($mensaje, 'To: lector@ejemplo.com'));
comprobar('lleva la baja en una cabecera', true, str_contains($mensaje, 'List-Unsubscribe: <https://ejemplo.com/baja>'));
comprobar('y va en dos partes, texto y html', true, str_contains($mensaje, 'multipart/alternative'));

// Un asunto con acentos sin codificar se ve roto en medio mundo.
comprobar(
    'un asunto con acentos va codificado',
    true,
    str_starts_with(smtp_cabecera_codificada('Edición número 3'), '=?UTF-8?B?')
);

comprobar(
    'y uno sin acentos se deja tal cual',
    'Edicion numero 3',
    smtp_cabecera_codificada('Edicion numero 3')
);

// Una cabecera solo puede ocupar una linea: un salto dentro es una cabecera
// ajena metida en el mensaje.
comprobar(
    'un salto de linea en el asunto no sobrevive',
    true,
    !str_contains(smtp_cabecera_codificada("Hola
Bcc: otro@ejemplo.com"), "
")
);

comprobar('una direccion normal vale', true, smtp_direccion_limpia('lector@ejemplo.com'));
comprobar('una con salto de linea no', false, smtp_direccion_limpia("lector@ejemplo.com
Bcc: otro@ejemplo.com"));
comprobar('ni una con un angulo dentro', false, smtp_direccion_limpia('<lector@ejemplo.com>'));
comprobar('ni una que no es direccion', false, smtp_direccion_limpia('lector'));

// --- El enlace de baja ------------------------------------------------------
//
// Tiene que seguir funcionando en un correo de hace dos anos, asi que la
// firma no puede depender de nada que cambie.

comprobar('la firma de baja es estable', lista_firma_baja(42), lista_firma_baja(42));
comprobar('y distinta para cada suscriptor', true, lista_firma_baja(42) !== lista_firma_baja(43));

// --- El correo de confirmacion ----------------------------------------------

$enlace = 'https://ejemplo.com/api/confirmar.php?s=7&t=abc';

comprobar(
    'el texto lleva el enlace',
    true,
    str_contains(lista_texto_confirmacion($enlace, 'https://ejemplo.com'), $enlace)
);

// Lo mas importante del correo: decir que si no se pulsa, no pasa nada.
comprobar(
    'y dice que sin el clic no se apunta a nadie',
    true,
    str_contains(lista_texto_confirmacion($enlace, 'https://ejemplo.com'), 'no te apuntamos')
);

comprobar(
    'el html escapa lo que pinta',
    true,
    str_contains(lista_html_confirmacion('https://ejemplo.com/?a=1&b=2', 'https://ejemplo.com'), '&amp;b=2')
);

// --- Migraciones ------------------------------------------------------------

comprobar(
    'un fichero con dos sentencias da dos sentencias',
    2,
    count(migrar_sentencias("CREATE TABLE a (id INT);
INSERT INTO b VALUES (1);
"))
);

comprobar(
    'los comentarios no cuentan como sentencia',
    1,
    count(migrar_sentencias("-- esto es un comentario
CREATE TABLE a (id INT);
"))
);

comprobar('un fichero vacio no da ninguna', 0, count(migrar_sentencias("-- nada

")));

// --- El correo de la edicion ------------------------------------------------
//
// Se manda la edicion entera, no un resumen con un "sigue leyendo": quien se
// suscribe a un boletin de cinco minutos quiere leerlo en el correo.

$edicion_correo = [
    'id'             => 7,
    'numero'         => 12,
    'slug'           => '2026-w39-012',
    'titulo'         => '',
    'intro'          => '',
    'fecha_prevista' => '2026-09-22',
];

$bits_correo = [
    [
        'id'        => 1,
        'titular'   => 'Airbnb pone precio a la reserva directa',
        'cuerpo'    => 'La compania empieza a cobrar menos cuando el anfitrion trae al cliente.',
        'por_que'   => 'Cambia la cuenta de la distribucion directa.',
        'categoria' => 'distribucion-otas',
        'url'       => 'https://ejemplo.com/noticia',
        'fuente'    => 'Smart Travel News',
    ],
];

$baja_url = 'https://ejemplo.com/api/baja.php?s=3&t=abc';
$texto_ed = envio_texto($edicion_correo, $bits_correo, 'https://ejemplo.com', $baja_url);
$html_ed  = envio_html($edicion_correo, $bits_correo, 'https://ejemplo.com', $baja_url);

// Sin titulo escrito a mano manda el primer titular: es lo que trae la
// edicion y es lo que hace que se abra.
comprobar(
    'el asunto es el primer titular cuando no hay titulo',
    'Airbnb pone precio a la reserva directa',
    envio_asunto($edicion_correo, $bits_correo)
);

comprobar(
    'y el titulo cuando lo hay',
    'La semana del ransomware',
    envio_asunto(['numero' => 12, 'titulo' => 'La semana del ransomware'], $bits_correo)
);

comprobar('el texto lleva el titular', true, str_contains($texto_ed, 'Airbnb pone precio'));
comprobar('y el cuerpo', true, str_contains($texto_ed, 'anfitrion trae al cliente'));
comprobar('y el enlace a la fuente', true, str_contains($texto_ed, 'https://ejemplo.com/noticia'));

// Lo que no puede faltar en ningun envio, ni por error ni por prisa.
comprobar('el texto lleva la baja', true, str_contains($texto_ed, $baja_url));
comprobar('y el html tambien', true, str_contains($html_ed, $baja_url));

comprobar('el html escapa lo que pinta', true, str_contains(
    envio_html($edicion_correo, [[
        'id' => 1, 'titular' => 'Mews & Atomize', 'cuerpo' => 'x', 'por_que' => '',
        'categoria' => 'pms-crs', 'url' => '', 'fuente' => '',
    ]], 'https://ejemplo.com', $baja_url),
    'Mews &amp; Atomize'
));

// El correo no carga nada de fuera: ni imagenes, ni tipografias, ni pixeles.
comprobar('el html no trae imagenes', false, str_contains($html_ed, '<img'));
comprobar('ni hojas de estilo externas', false, str_contains($html_ed, '<link'));

resumen_pruebas('Pruebas de la fase 5: alta con doble confirmacion');
