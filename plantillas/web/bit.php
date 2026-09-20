<?php
/**
 * Una noticia. La pieza que se repite por toda la web.
 *
 * Vivia dentro de la plantilla de la edicion. Salio de ahi cuando la portada
 * dejo de ser "la ultima edicion" para ser un rio de dias: la misma noticia se
 * pinta ahora en la portada, en la pagina de su dia y manana en donde haga
 * falta, y tres copias del mismo marcado son tres sitios donde arreglar el
 * mismo fallo.
 *
 * Recibe $bit, $indice -su posicion, que decide cual va destacada-, $base,
 * $fuentes, $categorias, $idiomas, $ambitos y $secreto.
 *
 * Lleva microdatos de schema.org (NewsArticle), no JSON-LD: un <script> en
 * linea -aunque sea de puros datos- cae bajo el mismo script-src 'self' que
 * el resto de la pagina, y esta pagina no lleva ni uno a proposito. Los
 * atributos itemprop no son <script> y no necesitan ese permiso.
 *
 * Los enlaces de "compartir" son intents por URL -wa.me, linkedin.com/
 * sharing-, no un widget de terceros: ni script, ni pixel, ni peticion
 * hasta que alguien pulsa. Apuntan siempre al permalink del bit, nunca a la
 * fuente, porque es lo unico de los dos que este sitio puede prometer que
 * sigue existiendo.
 *
 * El pie es una fila de iconos -leer el original, ficha del medio,
 * compartir- con su aria-label como nombre accesible y como texto del
 * globo que aparece al pasar el raton o al llegar por teclado (estilo.php,
 * .icono-boton). El nombre del medio sigue visible al lado, sin enlace:
 * la mitad de "cuanto fiarse de esto" no puede depender de pasar el raton
 * por un icono.
 *
 * Una noticia que cuentan varios medios a la vez sale en negativo -fondo
 * negro, letra blanca- (.bit-multifuente): es la señal mas fuerte que
 * tiene esta ficha de "esto es importante de verdad", y una fila mas en el
 * pie no se veia lo bastante.
 *
 * "Por qué importa" (.por-que) es el unico campo que el modo automatico deja
 * en blanco a proposito -es un juicio editorial, y ahi no hay nadie que lo
 * haga-, pero vivia solo dentro del cuerpo: un bit con criterio humano
 * anadido y uno sin el se distinguian solo leyendo la ficha entera. La
 * etiqueta "Con analisis" (.etiqueta-analisis), junto a la categoria,
 * enlaza a ese mismo parrafo -no anade texto nuevo, solo lo hace visible
 * donde ya se mira primero-.
 */

declare(strict_types=1);

$indice   = $indice ?? 0;
$fuentes  = $fuentes ?? [];
$secreto  = $secreto ?? '';

?>
  <?php
    $tema      = bits_categoria_canonica((string) $bit['categoria']) ?: 'tecnologia-general';
    $permalink = web_url_dia($base, (string) ($bit['dia'] ?? '')) . '#bit-' . (int) $bit['id'];

    // Compartir apunta siempre al permalink del bit, nunca a la fuente: es lo
    // que este sitio puede prometer que sigue existiendo -la fuente puede
    // mover o borrar el articulo- y es donde vive el contexto: las demas
    // fuentes que lo cuentan, el tema, el "por que importa". Vale con
    // rawurlencode y sin secreto: no hace falta contar cuantas veces se
    // comparte, solo que el enlace funcione.
    $compartir_whatsapp = 'https://wa.me/?text=' . rawurlencode($bit['titular'] . ' — ' . $permalink);
    $compartir_linkedin = 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($permalink);

    // Todas las fuentes que cuentan la noticia, no solo la mejor. Que cuatro
    // medios independientes la cuenten es la mitad de la informacion, y
    // esconderla detras de un solo enlace la tiraba. Se calcula aqui arriba
    // -y no junto al resto del pie, donde vivia antes- porque tambien decide
    // el aspecto de toda la ficha: lo que varios medios confirman a la vez
    // se destaca fuerte, no con un detalle mas al fondo.
    $suyas = $fuentes[(int) ($bit['racimo_id'] ?? 0)] ?? [];
    $unica = count($suyas) === 1 ? $suyas[0] : null;
  ?>
  <section class="bit<?= $indice === 0 ? ' bit-lead' : '' ?><?= count($suyas) > 1 ? ' bit-multifuente' : '' ?>" id="bit-<?= (int) $bit['id'] ?>"
           data-tema="<?= web_e($tema) ?>"
           aria-labelledby="titular-<?= (int) $bit['id'] ?>"
           itemscope itemtype="https://schema.org/NewsArticle">
    <link itemprop="mainEntityOfPage" href="<?= web_e($permalink) ?>">
    <meta itemprop="datePublished" content="<?= web_e(substr((string) ($bit['dia'] ?? ''), 0, 10)) ?>">
    <?php if (!empty($bit['url'])): ?>
      <link itemprop="isBasedOn" href="<?= web_e((string) $bit['url']) ?>">
    <?php endif; ?>
    <span itemprop="author" itemscope itemtype="https://schema.org/Organization" hidden>
      <meta itemprop="name" content="Bit &amp; Breakfast">
    </span>
    <span itemprop="publisher" itemscope itemtype="https://schema.org/Organization" hidden>
      <meta itemprop="name" content="Bit &amp; Breakfast">
      <meta itemprop="url" content="<?= web_e($base) ?>">
    </span>
    <div class="bit-carril" aria-hidden="true">
      <p class="numero"><?= str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) ?></p>
      <span class="bit-icono"><?= web_icono($tema) ?></span>
    </div>

    <div class="bit-cuerpo">
      <div class="bit-resumen">
        <?php
          // El titular lleva al articulo original. Es lo que hace un
          // agregador: lo que hay aqui es una ficha -de que va, quien lo
          // cuenta, cuantos lo cuentan- y el articulo es del medio. Obligar a
          // bajar la vista hasta el pie para encontrar el enlace es poner un
          // peaje al gesto que todo el mundo va a hacer de todas formas.
          //
          // Pasa por el contador propio, que redirige al original: asi se
          // sabe que se lee de verdad sin mandar a nadie a un tercero por el
          // camino. Sin secreto configurado, el enlace es directo.
          //
          // Y target="_blank" en los tres enlaces que llevan al original -aqui,
          // el boton "externo" del pie y cada fuente del desplegable "N fuentes
          // lo cuentan"-: abrirlo en una pestana nueva es lo que hace que esta
          // ficha se quede abierta de fondo en vez de perderse en cuanto se
          // pulsa. Para un lector que va abriendo varias, es la diferencia
          // entre "atras" veinte veces y no tocar el boton nunca; para el
          // sitio, cada pestana que sigue abierta es una sesion mas larga.
          $enlace = !empty($bit['url'])
              ? web_url_clic($base, (int) $bit['id'], $secreto, (string) $bit['url'])
              : '';
        ?>
        <h2 id="titular-<?= (int) $bit['id'] ?>" itemprop="headline">
          <?php if ($enlace !== ''): ?>
            <a href="<?= web_e($enlace) ?>" rel="nofollow noopener" target="_blank"><?= web_e($bit['titular']) ?></a>
          <?php else: ?>
            <?= web_e($bit['titular']) ?>
          <?php endif; ?>
        </h2>

        <p class="etiquetas">
          <?php
            // Ya no hay una cabecera de dia que lo diga una vez por grupo: el
            // rio es un solo flujo, asi que cada ficha lleva su fecha encima.
            // "Hoy" y "Ayer" son las mismas palabras que usaba esa cabecera.
            $fecha_bit = substr((string) ($bit['dia'] ?? ''), 0, 10);
          ?>
          <?php if ($fecha_bit !== ''): ?>
            <a class="etiqueta etiqueta-fecha" href="<?= web_e(web_url_dia($base, $fecha_bit)) ?>">
              <time datetime="<?= web_e($fecha_bit) ?>"><?= web_e(web_dia_titulo($fecha_bit)) ?></time>
            </a>
          <?php endif; ?>
          <a class="etiqueta etiqueta-categoria" href="<?= web_e(web_url_tema($base, $tema)) ?>"><?= web_e($categorias[$tema] ?? $bit['categoria']) ?></a>
          <?php if (trim((string) $bit['por_que']) !== ''): ?>
            <?php // La unica senal de que aqui hay un juicio humano -el modo
                  // automatico deja este campo vacio a proposito- vivia solo
                  // dentro del cuerpo, asi que un bit con "por que importa" y
                  // uno sin el se distinguian solo leyendo la ficha entera.
                  // Esta etiqueta lo hace visible donde ya se mira primero, sin
                  // escribir ningun analisis nuevo: solo senala el que ya
                  // existe. ?>
            <a class="etiqueta etiqueta-analisis" href="#por-que-<?= (int) $bit['id'] ?>">Con análisis</a>
          <?php endif; ?>
          <?php // El tipo y la madurez se quedan fuera de la portada: en un
                // titular no anaden nada y convierten la linea de arriba en
                // una fila de tres cosas iguales. Siguen en el dato del bit
                // para el explorador y el correo. ?>
          <?php if (!empty($bit['traducido_de'])): ?>
            <?php // Estas palabras no son las que escribio el periodista, y
                  // eso hay que decirlo donde se leen, no en una pagina de
                  // avisos. El enlace de abajo sigue llevando al original. ?>
            <span class="etiqueta etiqueta-idioma">Traducido del <?= web_e(mb_strtolower((string) ($idiomas[$bit['traducido_de']] ?? $bit['traducido_de']), 'UTF-8')) ?></span>
          <?php elseif (($bit['idioma'] ?? 'es') !== 'es'): ?>
            <?php // Sin traducir: el titular es tal cual lo publico el medio,
                  // y decir en que idioma esta evita que parezca un descuido. ?>
            <span class="etiqueta etiqueta-idioma">Titular en <?= web_e(mb_strtolower((string) ($idiomas[$bit['idioma']] ?? $bit['idioma']), 'UTF-8')) ?></span>
          <?php endif; ?>
        </p>

        <div class="texto" itemprop="articleBody"><?= web_parrafos((string) $bit['cuerpo']) ?></div>

        <?php if (trim((string) $bit['por_que']) !== ''): ?>
          <p class="por-que" id="por-que-<?= (int) $bit['id'] ?>"><strong>Por qué importa.</strong> <?= web_e($bit['por_que']) ?></p>
        <?php endif; ?>

        <?php $menciona = web_proveedores($bit['proveedores'] ?? null); ?>
        <?php if ($menciona): ?>
          <?php // Los proveedores ya no tienen ficha propia -este radar habla
                // de temas, no de marcas-, pero seguir nombrandolos si sirve:
                // el enlace lleva al explorador buscando ese nombre. ?>
          <p class="menciona">Menciona:
            <?php foreach ($menciona as $indice_p => $proveedor): ?><?= $indice_p > 0 ? ', ' : '' ?><a href="<?= web_e($base) ?>/buscar.html?q=<?= web_e(rawurlencode((string) $proveedor['nombre'])) ?>"><?= web_e($proveedor['nombre']) ?></a><?php endforeach; ?>
          </p>
        <?php endif; ?>
      </div>

      <?php
        // El pie es la unica fila de acciones de la ficha, y todas se leen
        // igual: un icono con su etiqueta accesible, sin texto suelto al
        // lado. El nombre del medio no desaparece -sigue siendo la mitad de
        // "cuanto fiarse de esto"-, solo deja de ir dentro del enlace.
      ?>
      <p class="pie-bit">
        <?php if (!empty($bit['fuente'])): ?>
          <span class="pie-fuente"><?= web_e($bit['fuente']) ?></span>
        <?php endif; ?>

        <span class="pie-acciones">
          <?php if ($enlace !== ''): ?>
            <a class="icono-boton" href="<?= web_e($enlace) ?>" rel="nofollow noopener" target="_blank"
               aria-label="Leer el original en <?= web_e($bit['fuente'] ?? 'la fuente') ?>">
              <?= web_icono_ui('externo') ?>
            </a>
          <?php endif; ?>
          <?php if (!empty($bit['fuente'])): ?>
            <a class="icono-boton" href="<?= web_e(web_url_medio($base, web_slug_medio((string) $bit['fuente']))) ?>"
               aria-label="Ficha de <?= web_e($bit['fuente']) ?>">
              <?= web_icono_ui('medio') ?>
            </a>
          <?php endif; ?>
          <a class="icono-boton" href="<?= web_e($compartir_whatsapp) ?>" rel="nofollow noopener" target="_blank"
             aria-label="Compartir en WhatsApp">
            <?= web_icono_ui('whatsapp') ?>
          </a>
          <a class="icono-boton" href="<?= web_e($compartir_linkedin) ?>" rel="nofollow noopener" target="_blank"
             aria-label="Compartir en LinkedIn">
            <?= web_icono_ui('linkedin') ?>
          </a>
        </span>

        <?php if ($unica !== null): ?>
          <?php // Con una sola fuente no hay desplegable que abrir, asi que
                // sus datos van aqui: de donde es, en que idioma publica y
                // cuando lo conto. Es la mitad de lo que hace falta para
                // saber cuanto fiarse de una noticia. ?>
          <span class="datos">
            <?= web_e($ambitos[$unica['region']] ?? $unica['region']) ?>
            <span class="punto">·</span>
            <?= web_e($idiomas[$unica['idioma']] ?? $unica['idioma']) ?>
            <span class="punto">·</span>
            <time datetime="<?= web_e(substr((string) $unica['publicado'], 0, 10)) ?>"><?= web_e(web_fecha_larga(substr((string) $unica['publicado'], 0, 10))) ?></time>
          </span>
        <?php endif; ?>
      </p>

      <?php if (count($suyas) > 1): ?>
        <details class="fuentes-bit">
          <summary><?= count($suyas) ?> fuentes lo cuentan</summary>

          <ul>
          <?php foreach ($suyas as $fuente): ?>
            <li>
              <a href="<?= web_e($fuente['url']) ?>" rel="nofollow noopener" target="_blank"><?= web_e($fuente['fuente']) ?></a>
              <span class="datos">
                <?= web_e($ambitos[$fuente['region']] ?? $fuente['region']) ?>
                <span class="punto">·</span>
                <?= web_e($idiomas[$fuente['idioma']] ?? $fuente['idioma']) ?>
                <span class="punto">·</span>
                <time datetime="<?= web_e(substr((string) $fuente['publicado'], 0, 10)) ?>"><?= web_e(web_fecha_larga(substr((string) $fuente['publicado'], 0, 10))) ?></time>
              </span>
              <span class="titular-fuente"><?= web_e($fuente['titulo']) ?></span>
            </li>
          <?php endforeach; ?>
          </ul>
        </details>
      <?php endif; ?>
    </div>
  </section>
