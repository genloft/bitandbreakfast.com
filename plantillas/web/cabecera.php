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
 * El menu agrupa Cifras, Cumplimiento, Tendencias, Glosario, Reserva
 * agéntica y Calendario bajo un
 * desplegable ("Recursos"): son paginas de consulta, no de lectura diaria, y
 * puestas al mismo nivel que Portada o Temas la barra crecio a diez enlaces. Un
 * <details> nativo -sin una linea de JS- porque no hay nada que un script
 * haga aqui mejor que el navegador solo.
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
 */

declare(strict_types=1);

require_once __DIR__ . '/iconos.php';

$enlace_activo = $enlace_activo ?? '';
$en_recursos   = in_array($enlace_activo, ['cifras', 'cumplimiento', 'tendencias', 'glosario', 'agentica', 'calendario'], true);

?>
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
      <a href="<?= web_e($base) ?>/temas.html"<?= $enlace_activo === 'temas' ? ' aria-current="page"' : '' ?>>Temas</a>
      <a href="<?= web_e($base) ?>/medios.html"<?= $enlace_activo === 'medios' ? ' aria-current="page"' : '' ?>>Medios</a>
      <a href="<?= web_e($base) ?>/archivo.html"<?= $enlace_activo === 'archivo' ? ' aria-current="page"' : '' ?>>Archivo</a>
      <a href="<?= web_e($base) ?>/sobre.html"<?= $enlace_activo === 'sobre' ? ' aria-current="page"' : '' ?>>Qué es</a>
      <details class="menu-recursos">
        <summary<?= $en_recursos ? ' aria-current="page"' : '' ?>>Recursos</summary>
        <div class="menu-recursos-lista">
          <a href="<?= web_e($base) ?>/estadisticas.html"<?= $enlace_activo === 'cifras' ? ' aria-current="page"' : '' ?>>Cifras</a>
          <a href="<?= web_e($base) ?>/cumplimiento.html"<?= $enlace_activo === 'cumplimiento' ? ' aria-current="page"' : '' ?>>Cumplimiento</a>
          <a href="<?= web_e($base) ?>/tendencias.html"<?= $enlace_activo === 'tendencias' ? ' aria-current="page"' : '' ?>>Tendencias</a>
          <a href="<?= web_e($base) ?>/glosario.html"<?= $enlace_activo === 'glosario' ? ' aria-current="page"' : '' ?>>Glosario</a>
          <a href="<?= web_e($base) ?>/agentica.html"<?= $enlace_activo === 'agentica' ? ' aria-current="page"' : '' ?>>Reserva agéntica</a>
          <a href="<?= web_e($base) ?>/calendario.html"<?= $enlace_activo === 'calendario' ? ' aria-current="page"' : '' ?>>Calendario</a>
        </div>
      </details>
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
