<?php
/**
 * Confirmacion del alta: el clic que de verdad apunta a alguien.
 *
 * Hasta aqui, el alta era una peticion: alguien escribio una direccion en un
 * formulario, y esa direccion podia no ser suya. Este enlace llega al buzon, y
 * solo lo puede pulsar quien lo abre. Es el unico momento en que alguien entra
 * en la lista.
 *
 * Se atiende por GET a proposito, porque un enlace en un correo es un GET, y
 * por eso el testigo es de un solo uso y caduca a los tres dias.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/lista.php';
require_once __DIR__ . '/respuesta.php';

date_default_timezone_set('UTC');

$base = rtrim((string) (config('sitio.url') ?? ''), '/');

$id      = (int) ($_GET['s'] ?? 0);
$testigo = (string) ($_GET['t'] ?? '');

if (lista_confirmar($id, $testigo)) {
    api_responder(
        $base,
        'Ya estás dentro',
        'Confirmado. El próximo martes te llega la edición, y en cada envío tienes un enlace para darte de baja en un clic.'
    );
}

// No se distingue un testigo caducado de uno inventado: al que llega con algo
// que no vale se le dice lo mismo y se le ofrece volver a intentarlo.
api_responder(
    $base,
    'Ese enlace ya no vale',
    'O ha caducado —duran tres días— o ya se había usado. Puedes volver a pedir el alta desde la portada y te mandamos otro.',
    410,
    'Volver a la portada'
);
