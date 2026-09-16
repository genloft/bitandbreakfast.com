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

resumen_pruebas('Pruebas de la fase 5: alta con doble confirmacion');
