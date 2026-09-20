<?php
/**
 * Bit & Breakfast - plantilla de configuracion.
 *
 * Copia este fichero como config/config.php y rellenalo en el servidor.
 * config/config.php NO se sube al repositorio: esta en .gitignore.
 *
 * Los tres secretos se generan una sola vez y no se cambian despues: si
 * cambias secreto_hmac, todos los enlaces de voto de los correos ya enviados
 * dejan de validar.
 */

return [

    'bd' => [
        'host'   => 'localhost',
        'nombre' => 'uXXXXXXXXX_bitb',
        'usuario'=> 'uXXXXXXXXX_bitb',
        'clave'  => '',
        'puerto' => 3306,
    ],

    'sitio' => [
        'nombre'  => 'Bit & Breakfast',
        'dominio' => 'bitandbreakfast.com',
        'url'     => 'https://bitandbreakfast.com',
        'zona_horaria' => 'Europe/Madrid',
    ],

    // Para /legal.html. Los tres son opcionales: sin ellos, la pagina se
    // publica igual, con el contacto por correo como unico dato -mejor eso
    // que inventar una identidad que no se puede comprobar-, pero la LSSI
    // exige identificar a quien responde del sitio, asi que conviene
    // rellenarlos antes de publicar de cara al publico.
    'legal' => [
        'titular'        => '',  // persona o empresa responsable del sitio
        'identificacion' => '',  // NIF o CIF
        'domicilio'      => '',  // domicilio a efectos de notificaciones
    ],

    'rastreador' => [
        // Prefijo Mozilla/5.0 (compatible; ...) porque es el formato que
        // reconocen los filtros antibot de varias fuentes buenas. El nombre
        // del bot y la URL de contacto siguen siendo visibles, que es lo que
        // exige la buena ciudadania.
        'user_agent' => 'Mozilla/5.0 (compatible; BitAndBreakfastBot/1.0; +https://bitandbreakfast.com/bot)',
        'timeout'    => 10,   // segundos por peticion
        'pausa_dominio' => 1, // segundos minimos entre peticiones al mismo dominio
        'max_bytes'  => 5242880,
    ],

    // Presupuesto de segundos por ejecucion del cron. Cada tarea para cuando
    // lo agota y la siguiente ejecucion continua por donde iba, asi que
    // quedarse corto nunca pierde trabajo: solo lo reparte en mas pasadas.
    // Segundos por tarea del cron, y techo de la ejecucion entera.
    'presupuesto_cron'       => 25,
    'presupuesto_cron_total' => 75,

    'rutas' => [
        // Rutas absolutas en el servidor. __DIR__ apunta a config/.
        'raiz'    => dirname(__DIR__),
        'cache'   => dirname(__DIR__) . '/cache',
        'publico' => dirname(__DIR__) . '/publico',
    ],

    'secretos' => [
        // Genera cada uno con 64 caracteres hexadecimales distintos.
        'secreto_hmac' => '',  // firma los enlaces de voto
        'token_api'    => '',  // cabecera de api/candidatos.php y api/bits.php
        'sal_hash'     => '',  // sal del hash de IP y de user-agent
    ],

    // El alta del boletin. Ver docs/INSTALACION.md, apartado 9.
    'correo' => [
        'proveedor'     => 'mailerlite',   // mailerlite | brevo
        'api_key'       => '',
        // Grupo de MailerLite o lista de Brevo donde entra el suscriptor.
        'lista'         => '',
        // Solo Brevo: identificador de la plantilla de doble confirmacion.
        'doi_plantilla' => 0,
        'remitente'     => 'hola@bitandbreakfast.com',
        'nombre_remitente' => 'Bit & Breakfast',
    ],

    // true muestra los errores por pantalla. En produccion, siempre false.
    'depuracion' => false,
];
