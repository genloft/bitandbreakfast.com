<?php
/**
 * Cabecera comun de la web publicada.
 *
 * Dos piezas, como en un periodico: una barra de navegacion con el sello a la
 * izquierda, y debajo el nombre ocupando el ancho entero. Todo dentro de
 * filetes, que es como esta maquetado el resto del sitio.
 *
 * El sello es un circulo negro con la inicial. Hubo un dibujo -una taza con
 * vapor de wifi- y se quito: un icono ilustrativo baja de categoria una
 * cabecera que quiere parecer la de una publicacion. Una letra dentro de un
 * circulo no ilustra nada, y por eso funciona.
 *
 * A la derecha del nombre van las cifras del radar. Estaban antes en una tira
 * negra encima de todo, y se han traido aqui: decian lo mismo -cuando se
 * actualizo esto y que ha entrado- pero lo decian de pasada, como un aviso, y
 * es de las primeras cosas que se preguntan al volver a un agregador. Al lado
 * del nombre son parte de la cabecera, no un mensaje.
 *
 * Cifras, Cumplimiento, Tendencias, Glosario, Reserva agéntica y Calendario
 * -paginas de consulta, no de lectura diaria- van como iconos, no como texto:
 * puestas al mismo nivel que Portada o Temas la barra crecio a diez enlaces,
 * y un desplegable de texto ("Recursos ▾") escondia el destino hasta abrirlo.
 * Cada icono lleva su propio aria-label -el nombre de la pagina, nada mas
 * largo- que hace de nombre accesible y, con el ::after de .icono-boton en
 * estilo.php, de tooltip al pasar el raton o llegar por teclado: se ve donde
 * lleva sin tener que entrar. Mismo patron que los iconos de compartir del
 * pie de cada ficha, no uno nuevo que aprender.
 *
 * Van en su propia fila -.cabecera-recursos-, no dentro del <nav> de arriba:
 * debajo del filete que separa la barra de navegacion, y encima del nombre
 * grande. Mezclados con "Portada" o "Buscar" quedaban aplastados por el
 * padding de esos enlaces de texto y no se distinguian de un adorno mas del
 * menu; en su propia fila son mas grandes y leen como lo que son, una
 * segunda forma de moverse por el sitio.
 *
 * Por eso viven dentro de .cabecera-marca, delante del logo, y no como
 * hermano suelto delante de el: en escritorio .cabecera-marca es una
 * reticula de dos columnas y dos filas, los iconos ocupan la fila 1 de la
 * columna del nombre y el panel de cifras ocupa las dos filas de la
 * columna derecha, asi que "Actualizado" -la primera linea del panel- cae
 * a la misma altura que los iconos en vez de a la altura del nombre
 * grande. El nombre baja a la fila 2, debajo de los iconos. Se ahorra la
 * fila entera que antes ocupaban los iconos por su cuenta, con su propio
 * relleno arriba y abajo: ahora comparten alto con el panel en vez de
 * sumar el suyo aparte.
 *
 * Lleva microdatos de schema.org (WebSite, con su SearchAction hacia
 * /buscar.html), no JSON-LD -mismo motivo que en bit.php: un <script> en
 * linea cae bajo el script-src 'self' que el resto del sitio no rompe a
 * proposito-. Va aqui y no en cada plantilla de pagina porque esta cabecera
 * ya es lo unico que todas comparten. Nada de "logo": no hay ninguna imagen
 * en todo el sitio, y un schema que apunte a un fichero que no existe es
 * peor que no llevar ese campo.
 *
 * A la derecha del menu va un buscador en vivo: un campo que consulta el
 * mismo indice.json del explorador (dinamicojs.php, publico/dinamico.js) y
 * ensena hasta ocho titulares segun se escribe, sin esperar al envio del
 * formulario. El formulario en si sigue funcionando sin JavaScript -manda a
 * /buscar.html como siempre-, asi que quien tenga el script desactivado no
 * pierde nada.
 *
 * Recibe $base, $panel y, opcionalmente, $enlace_activo.
 *
 * Justo antes del <header> va el aviso de cookies -.aviso-cookies-,
 * empieza oculto y cookiesjs.php lo destapa al cargar, salvo que ya se
 * hubiera cerrado en una visita anterior. Vive aqui, delante de todo,
 * por la misma razon que el resto de esta cabecera: es lo unico que
 * comparten todas las paginas. No es un position:fixed que tape nada:
 * es un bloque normal que empuja el resto hacia abajo mientras esta
 * visible, y desaparece del todo -sin dejar hueco- en cuanto se cierra.
 *
 * Es informativo, no de consentimiento: Google Analytics -la unica
 * pieza de este sitio que instala una cookie de analitica- se activa
 * para toda visita, sin esperar a este aviso ni a ningun clic. Unos
 * botones de "Aceptar" o "Rechazar" que no controlaran nada de verdad
 * serian peor que no llevarlos, asi que no los lleva: solo avisa, con
 * un enlace a /legal.html#cookies para el detalle y para la unica
 * forma real de no ser medido -la extension de Google-.
 */

declare(strict_types=1);

require_once __DIR__ . '/iconos.php';

$enlace_activo = $enlace_activo ?? '';

?>
<div class="aviso-cookies" id="aviso-cookies" role="region" aria-label="Aviso de cookies" hidden>
  <div class="aviso-cookies-caja">
    <p>Usamos Google Analytics para saber cuánta gente lee esto y qué se
    lee más: instala una cookie de analítica en tu navegador.
    <a href="<?= web_e($base) ?>/legal.html#cookies">Más detalles</a>.</p>
    <button type="button" class="aviso-cookies-cerrar" aria-label="Cerrar este aviso">&times;</button>
  </div>
</div>

<header class="cabecera" itemscope itemtype="https://schema.org/WebSite">
  <meta itemprop="name" content="Bit &amp; Breakfast">
  <meta itemprop="url" content="<?= web_e($base) ?>/">
  <meta itemprop="inLanguage" content="es">
  <span itemprop="publisher" itemscope itemtype="https://schema.org/Organization" hidden>
    <meta itemprop="name" content="Bit &amp; Breakfast">
    <meta itemprop="url" content="<?= web_e($base) ?>/">
  </span>
  <div itemprop="potentialAction" itemscope itemtype="https://schema.org/SearchAction" hidden>
    <meta itemprop="target" content="<?= web_e($base) ?>/buscar.html?q={search_term_string}">
    <meta itemprop="query-input" content="required name=search_term_string">
  </div>

  <div class="cabecera-barra">
    <a class="sello-marca" href="<?= web_e($base) ?>/" aria-label="Bit &amp; Breakfast, portada">B</a>

    <nav class="menu" aria-label="Principal">
      <a href="<?= web_e($base) ?>/"<?= $enlace_activo === 'portada' ? ' aria-current="page"' : '' ?>>Portada</a>
      <a href="<?= web_e($base) ?>/mapa.html"<?= $enlace_activo === 'mapa' ? ' aria-current="page"' : '' ?>>Mapa</a>
      <a href="<?= web_e($base) ?>/temas.html"<?= $enlace_activo === 'temas' ? ' aria-current="page"' : '' ?>>Temas</a>
      <a href="<?= web_e($base) ?>/medios.html"<?= $enlace_activo === 'medios' ? ' aria-current="page"' : '' ?>>Medios</a>
      <a href="<?= web_e($base) ?>/archivo.html"<?= $enlace_activo === 'archivo' ? ' aria-current="page"' : '' ?>>Archivo</a>
      <a href="<?= web_e($base) ?>/sobre.html"<?= $enlace_activo === 'sobre' ? ' aria-current="page"' : '' ?>>Qué es</a>
      <span class="menu-fin"></span>
      <a href="<?= web_e($base) ?>/buscar.html"<?= $enlace_activo === 'buscar' ? ' aria-current="page"' : '' ?>>Buscar</a>
      <a href="<?= web_e($base) ?>/feed.xml">RSS</a>
    </nav>
    <div class="cabecera-buscar">
      <form class="cabecera-buscar-forma" role="search" action="<?= web_e($base) ?>/buscar.html" method="get">
        <input id="q-dinamico" name="q" type="search" placeholder="Buscar…" aria-label="Buscar noticias" autocomplete="off">
        <button type="submit" aria-label="Buscar"><?= web_icono_ui('lupa', 'icono icono-mini') ?></button>
      </form>
      <div id="resultados-dinamicos" class="cabecera-resultados" hidden aria-live="polite"></div>
    </div>
  </div>

  <div class="cabecera-marca">
    <div class="cabecera-recursos" role="group" aria-label="Recursos">
      <a class="icono-boton" href="<?= web_e($base) ?>/estadisticas.html" aria-label="Cifras"<?= $enlace_activo === 'cifras' ? ' aria-current="page"' : '' ?>><?= web_icono_ui('cifras') ?></a>
      <a class="icono-boton" href="<?= web_e($base) ?>/cumplimiento.html" aria-label="Cumplimiento"<?= $enlace_activo === 'cumplimiento' ? ' aria-current="page"' : '' ?>><?= web_icono('cumplimiento') ?></a>
      <a class="icono-boton" href="<?= web_e($base) ?>/tendencias.html" aria-label="Tendencias"<?= $enlace_activo === 'tendencias' ? ' aria-current="page"' : '' ?>><?= web_icono_ui('tendencias') ?></a>
      <a class="icono-boton" href="<?= web_e($base) ?>/glosario.html" aria-label="Glosario"<?= $enlace_activo === 'glosario' ? ' aria-current="page"' : '' ?>><?= web_icono_ui('glosario') ?></a>
      <a class="icono-boton" href="<?= web_e($base) ?>/agentica.html" aria-label="Reserva agéntica"<?= $enlace_activo === 'agentica' ? ' aria-current="page"' : '' ?>><?= web_icono_ui('agentica') ?></a>
      <a class="icono-boton" href="<?= web_e($base) ?>/calendario.html" aria-label="Calendario"<?= $enlace_activo === 'calendario' ? ' aria-current="page"' : '' ?>><?= web_icono_ui('calendario') ?></a>
    </div>

    <a class="logo" href="<?= web_e($base) ?>/" aria-label="Bit &amp; Breakfast, portada">
      <span class="logo-bloque">Bit<span class="logo-amp">&amp;</span>Breakfast</span>
    </a>

    <?php require __DIR__ . '/panel.php'; ?>
  </div>

  <div class="cabecera-pie">
    <p class="promesa">Agregador de tecnología hotelera <span class="promesa-punto">·</span> en español</p>
    <p class="promesa">Lo que aparece cada día</p>
  </div>
</header>
