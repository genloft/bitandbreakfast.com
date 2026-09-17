<?php
/**
 * Baja en un clic.
 *
 * Sin preguntar por que, sin pedir que confirme dos veces y sin iniciar
 * sesion. Quien quiere irse se va: cualquier friccion aqui solo consigue que
 * la siguiente vez marquen el correo como spam, que es mucho peor para el
 * boletin que perder a un lector.
 *
 * El enlace va firmado con HMAC y no caduca nunca: tiene que seguir
 * funcionando en un correo de hace dos anos.
 *
 * La direccion no se borra, se marca. Guardar la baja es lo unico que impide
 * volver a escribir a quien ya dijo que no, incluso si esa direccion se vuelve
 * a colar en el formulario.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/lista.php';
require_once __DIR__ . '/respuesta.php';

date_default_timezone_set('UTC');

$base = rtrim((string) (config('sitio.url') ?? ''), '/');

$id    = (int) ($_GET['s'] ?? 0);
$firma = (string) ($_GET['t'] ?? '');

if (lista_baja($id, $firma)) {
    api_responder(
        $base,
        'Dado de baja',
        'No te escribiremos más. Si algún día cambias de idea, el alta sigue en la portada; y el RSS lleva lo mismo sin dejar tu correo en ningún sitio.',
        200,
        'Ver la última edición'
    );
}

api_responder(
    $base,
    'Ese enlace no vale',
    'No hemos podido dar de baja esa dirección con este enlace. Escríbenos y lo hacemos a mano.',
    400,
    'Volver a la portada'
);
