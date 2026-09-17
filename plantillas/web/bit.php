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
 */

declare(strict_types=1);

$indice   = $indice ?? 0;
$fuentes  = $fuentes ?? [];
$secreto  = $secreto ?? '';

?>
  <?php $tema = bits_categoria_canonica((string) $bit['categoria']) ?: 'tecnologia-general'; ?>
  <section class="bit<?= $indice === 0 ? ' bit-lead' : '' ?>" id="bit-<?= (int) $bit['id'] ?>"
           data-tema="<?= web_e($tema) ?>"
           aria-labelledby="titular-<?= (int) $bit['id'] ?>">
    <div class="bit-carril" aria-hidden="true">
      <p class="numero"><?= str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) ?></p>
      <span class="bit-icono"><?= web_icono($tema) ?></span>
    </div>

    <div class="bit-cuerpo">
      <h2 id="titular-<?= (int) $bit['id'] ?>"><?= web_e($bit['titular']) ?></h2>

      <p class="etiquetas">
        <a class="etiqueta etiqueta-categoria" href="<?= web_e(web_url_tema($base, $tema)) ?>"><?= web_e($categorias[$tema] ?? $bit['categoria']) ?></a>
        <?php // El tipo y la madurez se quedan fuera de la portada: en un
              // titular no anaden nada y convierten la linea de arriba en
              // una fila de tres cosas iguales. Siguen en el dato del bit
              // para el explorador y el correo. ?>
        <?php if (($bit['idioma'] ?? 'es') !== 'es'): ?>
          <?php // El titular es el que publico el medio. Decir en que idioma
                // esta evita que parezca un descuido: es la noticia tal cual
                // la conto su fuente, sin traducir, que es lo que promete
                // este radar. ?>
          <span class="etiqueta etiqueta-idioma">Titular en <?= web_e(mb_strtolower((string) ($idiomas[$bit['idioma']] ?? $bit['idioma']), 'UTF-8')) ?></span>
        <?php endif; ?>
      </p>

      <div class="texto"><?= web_parrafos((string) $bit['cuerpo']) ?></div>

      <?php if (trim((string) $bit['por_que']) !== ''): ?>
        <p class="por-que"><strong>Por qué importa.</strong> <?= web_e($bit['por_que']) ?></p>
      <?php endif; ?>

      <?php $menciona = web_proveedores($bit['proveedores'] ?? null); ?>
      <?php if ($menciona): ?>
        <?php // Los proveedores ya no tienen ficha propia -este radar habla de
              // temas, no de marcas-, pero seguir nombrandolos si sirve: el
              // enlace lleva al explorador buscando ese nombre. ?>
        <p class="menciona">Menciona:
          <?php foreach ($menciona as $indice_p => $proveedor): ?><?= $indice_p > 0 ? ', ' : '' ?><a href="<?= web_e($base) ?>/buscar.html?q=<?= web_e(rawurlencode((string) $proveedor['nombre'])) ?>"><?= web_e($proveedor['nombre']) ?></a><?php endforeach; ?>
        </p>
      <?php endif; ?>

      <?php
        // Todas las fuentes que cuentan la noticia, no solo la mejor. Que
        // cuatro medios independientes la cuenten es la mitad de la
        // informacion, y esconderla detras de un solo enlace la tiraba.
        $suyas = $fuentes[(int) ($bit['racimo_id'] ?? 0)] ?? [];
        $unica = count($suyas) === 1 ? $suyas[0] : null;
      ?>

      <p class="pie-bit">
        <?php if (!empty($bit['url'])): ?>
          <a class="fuente" href="<?= web_e(web_url_clic($base, (int) $bit['id'], $secreto, (string) $bit['url'])) ?>" rel="nofollow noopener">
            <?= web_e($bit['fuente'] ?? 'Leer la fuente') ?> →
          </a>
          <?php if (!empty($bit['fuente'])): ?>
            <a class="ficha-medio" href="<?= web_e(web_url_medio($base, web_slug_medio((string) $bit['fuente']))) ?>">ficha</a>
          <?php endif; ?>
        <?php endif; ?>

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
              <a href="<?= web_e($fuente['url']) ?>" rel="nofollow noopener"><?= web_e($fuente['fuente']) ?></a>
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
