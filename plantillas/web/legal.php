<?php
/**
 * Aviso legal: identificación, cookies, datos personales y de dónde sale
 * lo que se publica.
 *
 * No vive en /sobre.html a propósito: esa página explica el criterio
 * editorial -qué entra y por qué-, y esta contesta las preguntas legales
 * que no tienen nada que ver con el criterio: quién responde del sitio,
 * qué guarda de cada visita y por qué puede publicar un fragmento de lo
 * que cuenta otro medio sin pedirle permiso caso por caso.
 *
 * La identificación (titular, NIF, domicilio) sale de config('legal'), no
 * de un dato inventado aquí: sin configurar, la página se publica igual,
 * con el contacto por correo como único dato -mejor eso que una identidad
 * que no se puede comprobar-.
 *
 * Recibe $base y $alta_abierta.
 */

declare(strict_types=1);

$version    = $version ?? '0';
$version_js = $version_js ?? '0';

$enlace_activo = 'legal';
$alta_abierta  = $alta_abierta ?? false;

$titular        = trim((string) config_opcional('legal.titular', ''));
$identificacion = trim((string) config_opcional('legal.identificacion', ''));
$domicilio      = trim((string) config_opcional('legal.domicilio', ''));
$contacto       = trim((string) config_opcional('correo.remitente', ''));
$proveedor_correo = (string) config_opcional('correo.proveedor', '');
$nombre_proveedor_correo = match ($proveedor_correo) {
    'mailerlite' => 'MailerLite',
    'brevo'      => 'Brevo',
    default      => 'el proveedor de correo configurado',
};

$descripcion = 'Aviso legal de Bit & Breakfast: identificación del responsable, cookies, datos personales y de dónde sale el contenido que se publica.';

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Aviso legal · Bit &amp; Breakfast</title>
<meta name="description" content="<?= web_e($descripcion) ?>">
<link rel="canonical" href="<?= web_e($base) ?>/legal.html">
<link rel="alternate" type="application/rss+xml" title="Bit &amp; Breakfast" href="<?= web_e($base) ?>/feed.xml">
<link rel="icon" href="<?= web_e($base) ?>/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= web_e($base) ?>/estilo.css?v=<?= web_e($version) ?>">
<meta name="theme-color" content="#12100d">
<meta name="robots" content="noindex, follow">
<meta property="og:site_name" content="Bit &amp; Breakfast">
<meta property="og:title" content="Aviso legal · Bit &amp; Breakfast">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= web_e($base) ?>/legal.html">
<meta property="og:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="<?= web_e(web_url_imagen_generica($base)) ?>">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<?php require __DIR__ . '/cabecera.php'; ?>

<main id="contenido">
  <header class="edicion-cabecera">
    <p class="sello">Aviso legal</p>
    <h1>Quién responde de esto, y de dónde sale</h1>
    <p class="datos">Identificación, cookies, datos personales y el origen del contenido</p>
  </header>

  <div class="texto pagina">

    <h2>Identificación</h2>

    <?php if ($titular !== ''): ?>
      <p><?= web_e($titular) ?><?php if ($identificacion !== ''): ?>, <?= web_e($identificacion) ?><?php endif; ?><?php if ($domicilio !== ''): ?>, con domicilio en <?= web_e($domicilio) ?><?php endif; ?>, es quien edita y responde de <?= web_e($base) ?>.</p>
    <?php else: ?>
      <p>Este sitio todavía no tiene rellenos los datos de identificación del
      responsable en su configuración. Mientras tanto, la vía de contacto de
      abajo es la forma de llegar a quien lo edita.</p>
    <?php endif; ?>

    <?php if ($contacto !== ''): ?>
      <p>Contacto: <a href="mailto:<?= web_e($contacto) ?>"><?= web_e($contacto) ?></a>.</p>
    <?php endif; ?>

    <h2>De dónde sale lo que se publica</h2>

    <p>Cada bit lleva el titular tal cual lo redactó el medio original y un
    resumen breve -nunca el artículo completo-, con un enlace a la fuente
    para leerla entera. Es exactamente la explicación que da
    <a href="<?= web_e($base) ?>/sobre.html">Qué es</a>: no se inventa nada,
    no se reescribe el titular y todo lo que aparece aquí se puede
    comprobar pulsando el enlace a su origen.</p>

    <p>El artículo 32.2 del Texto Refundido de la Ley de Propiedad
    Intelectual permite a un servicio de estas características poner a
    disposición del público fragmentos no significativos de contenido
    publicado en páginas de actualización periódica con fines informativos,
    sin necesidad de autorización previa de cada medio, a cambio de una
    remuneración equitativa e irrenunciable a favor de los editores. Esa
    remuneración se gestiona a través de una entidad de gestión de derechos
    y es independiente de que cada bit enlace y nombre a su fuente.</p>

    <p>Las imágenes y fotografías no entran en esa excepción -por eso este
    sitio no publica ninguna que no sea propia-, y la traducción de un
    titular o un resumen se marca siempre como tal, con enlace al original
    en su idioma.</p>

    <h2>Cookies</h2>

    <p>La parte pública de este sitio -lo que se lee sin iniciar sesión- no
    instala ninguna cookie: ni de analítica, ni de publicidad, ni de
    terceros. El <code>Content-Security-Policy</code> del servidor ya
    bloquea cargar nada que no sea del propio dominio, así que no hay donde
    esconder un rastreador aunque se quisiera.</p>

    <p>El único dato que este sitio guarda en tu navegador sin ser una
    cookie es que cerraste el aviso de suscripción flotante, si lo cerraste:
    una marca en el <code>localStorage</code> de tu navegador, solo para no
    volver a preguntarte en la misma visita. No sale de tu navegador, no
    identifica a nadie y no sirve para nada más que eso; puedes borrarla en
    cualquier momento desde los ajustes de privacidad de tu navegador,
    igual que una cookie.</p>

    <p>La única cookie de todo el sitio es la de la sesión del panel de
    administración, de uso interno y estrictamente necesaria para que quien
    edita pueda iniciar sesión. No la ve, ni la recibe, quien solo lee la
    web pública.</p>

    <h2>Datos personales</h2>

    <p>Al suscribirte al boletín, tu correo se envía directamente a
    <?= web_e($nombre_proveedor_correo) ?>, que es quien manda los envíos y
    gestiona la baja; este sitio no guarda tu dirección en su propia base de
    datos. Antes de entrar en la lista tienes que confirmar desde un correo
    aparte -doble confirmación-, y puedes darte de baja desde cualquier
    envío.</p>

    <p>Para evitar altas automáticas, el formulario cuenta los intentos por
    dirección de origen durante la última hora a partir de una huella -un
    HMAC de esa dirección, no la dirección en sí-, que no sirve para
    identificar a nadie y se descarta pasado ese tiempo.</p>

    <p>Los votos de «lo más útil» de cada bit guardan la misma clase de
    huella -nunca la dirección en sí- solo para impedir que se vote dos
    veces el mismo bit desde el mismo sitio. Los clics que cuentan qué se
    lee de verdad no guardan ni dirección ni huella de ningún tipo: solo un
    HMAC del navegador, y únicamente para descartar los rastreos
    automáticos de los propios gestores de correo.</p>

    <p>Puedes pedir en cualquier momento, escribiendo a la dirección de
    contacto de arriba, qué se sabe de ti o que se borre: dado lo anterior,
    lo habitual es que la respuesta sea que no hay nada que buscar más allá
    de tu alta en <?= web_e($nombre_proveedor_correo) ?>.</p>

    <h2>Enlaces externos</h2>

    <p>Cada bit, cada cifra y cada norma de este sitio enlaza a una fuente
    ajena. Este sitio no controla ni responde por el contenido de esas
    páginas externas, que pueden cambiar o desaparecer después de haberse
    citado aquí.</p>

  </div>

  <?php require __DIR__ . '/suscribir.php'; ?>
</main>

<?php require __DIR__ . '/pie.php'; ?>

</body>
</html>
