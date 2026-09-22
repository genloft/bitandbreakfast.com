<?php
/**
 * El mapa del stack, dibujado: nodos, conexiones y calor.
 *
 * Un SVG en linea, generado aqui desde la geometria de lib/stack_grafo.php. No
 * hay libreria de grafos ni layout automatico, y no es por ahorrar: la
 * politica de seguridad de este sitio no deja cargar nada de fuera -ni
 * cytoscape ni d3 ni un CDN-, y un layout automatico coloca los nodos donde le
 * conviene al algoritmo, no donde estan en un hotel. Las coordenadas a mano
 * son mas trabajo una vez y dicen algo cada vez.
 *
 * Como se lee el calor, en tres capas que se refuerzan:
 *
 *   1. **El tamano.** Un nodo encendido crece; el que pide una decision es el
 *      doble de gordo que uno en calma. Esto funciona en blanco y negro y a
 *      cualquier escala, que es lo que le pedimos a la primera lectura.
 *   2. **El halo.** Dos circulos concentricos muy transparentes alrededor del
 *      punto. Es lo que hace que el mapa parezca caliente y no una lista de
 *      puntos: el calor se derrama, no se recorta.
 *   3. **El simbolo dentro.** Triangulo de aviso para riesgo, flecha para
 *      oportunidad. Es la unica de las tres que distingue las dos senales sin
 *      depender del color, y por eso no se puede quitar.
 *
 * Las aristas que tocan un nodo encendido se tinen de su color. Ahi esta la
 * mitad del valor del dibujo: no dice solo "los pagos estan en rojo", dice
 * "los pagos estan en rojo y de ahi salen lineas al motor de reservas, al ERP
 * y al PMS". El contagio es la pregunta cara y un reticulo de casillas no la
 * contesta.
 *
 * El dibujo no lleva marco ni caja: se funde con el papel por los cuatro
 * cantos -la mascara esta en la hoja de estilo- porque un recuadro lo convierte
 * en una figura pegada en la pagina, y esto no es una ilustracion del articulo,
 * es la portada.
 *
 * Tampoco se desplaza: se navega. Arrastrando con el raton y ampliando con la
 * rueda, que es trabajo de mapa.js. Sin JavaScript se queda entero y quieto, y
 * entero y quieto es perfectamente util.
 *
 * Cada nodo lleva su "por que importa" en tres sitios, y no es redundancia:
 * en un <title>, que es el tooltip que enseña el navegador cuando no hay
 * JavaScript; en data-porque, de donde lo saca mapa.js para pintar uno legible
 * -y entonces retira el <title>, porque si no saldrian los dos-; y dentro del
 * aria-label, que es lo unico que oye quien no ve el dibujo.
 *
 * Accesible sin ver el dibujo: cada nodo es un <a> con aria-label completo
 * -nombre, area, senal, nivel, cuantas noticias y el por que-, asi que el mapa
 * se recorre entero con el tabulador y se escucha entero con un lector de
 * pantalla. No hace falta JavaScript para nada de esto.
 *
 * Recibe $mapa, $base y $mapa_variante ('banda' o 'completo').
 */

declare(strict_types=1);

$mapa          = $mapa ?? ['nodes' => []];
$mapa_variante = $mapa_variante ?? 'banda';
$estados       = $mapa['nodes'] ?? [];

$semana     = (string) ($mapa['semana'] ?? '');
$lienzo     = grafo_lienzo();
$posiciones = grafo_posiciones();
$areas      = stack_areas();

$titulo_svg = 'mapa-titulo-' . $mapa_variante;

?>
<?php // data-rueda: en su propia pagina la rueda amplia desde el primer giro;
      // en la banda de la portada, solo despues de pulsar el dibujo. Quien esta
      // bajando a leer noticias espera que la rueda siga bajando. ?>
<div class="mapa-marco" data-rueda="<?= $mapa_variante === 'completo' ? 'directa' : 'al-activar' ?>">
 <div class="mapa-lienzo">
  <svg class="mapa-svg"
       viewBox="0 0 <?= (int) $lienzo['ancho'] ?> <?= (int) $lienzo['alto'] ?>"
       xmlns="http://www.w3.org/2000/svg"
       aria-labelledby="<?= web_e($titulo_svg) ?>">
    <title id="<?= web_e($titulo_svg) ?>">El stack tecnológico de un hotel: veinticuatro sistemas y las integraciones que los unen, con los que tienen novedades encendidos.</title>

    <?php // Las manchas de area van las primeras, debajo de todo. ?>
    <g class="mapa-regiones" aria-hidden="true">
      <?php foreach (grafo_regiones() as $area_id => $caja): ?>
        <rect class="mapa-region" rx="14"
              x="<?= (int) $caja['x'] ?>" y="<?= (int) $caja['y'] ?>"
              width="<?= (int) $caja['ancho'] ?>" height="<?= (int) $caja['alto'] ?>"/>
        <text class="mapa-region-nombre"
              x="<?= (int) $caja['x'] + 14 ?>" y="<?= (int) $caja['y'] + 22 ?>"><?= web_e(mb_strtoupper($areas[$area_id] ?? $area_id, 'UTF-8')) ?></text>
      <?php endforeach; ?>
    </g>

    <?php // Despues las conexiones, para que los nodos las tapen y no al reves. ?>
    <g class="mapa-aristas" aria-hidden="true" fill="none">
      <?php foreach (grafo_aristas($mapa) as $arista): ?>
        <path class="<?= web_e($arista['clase']) ?>" d="<?= web_e($arista['d']) ?>"/>
      <?php endforeach; ?>
    </g>

    <g class="mapa-nodos">
      <?php foreach ($posiciones as $nodo_id => $punto): ?>
        <?php
          $estado = $estados[$nodo_id] ?? null;
          // La larga para lo que se lee -el aria-label, que es el nombre
          // de verdad del nodo- y la corta para lo que se dibuja, que compite
          // por el hueco con las lineas de debajo.
          $nombre = stack_etiqueta($nodo_id);
          $rotulo_corto = stack_etiqueta_corta($nodo_id);
          $area   = $areas[stack_area_de($nodo_id)] ?? '';
          $radio  = grafo_radio($estado);

          if ($estado === null) {
              $clase  = 'mapa-nodo-enlace mapa-nodo-enlace--apagado';
              $rotulo = sprintf('%s, en %s: sin novedades este mes.', $nombre, $area);
              $nivel  = '';
              $porque = 'Sin novedades este mes.';
          } else {
              $clase = 'mapa-nodo-enlace mapa-nodo-enlace--' . $estado['signal']
                     . ' mapa-nodo-enlace--' . $estado['level'];

              $cuantas = count($estado['brief_ids']);
              $palabra = $estado['signal'] === 'riesgo' ? 'riesgo' : 'oportunidad';

              $rotulo = sprintf(
                  '%s, en %s: %s de nivel %s, %d noticia%s.',
                  $nombre,
                  $area,
                  $palabra,
                  $estado['level'],
                  $cuantas,
                  $cuantas === 1 ? '' : 's'
              );

              $nivel = ucfirst($palabra) . ' · ' . $estado['level'];

              // El "por que importa" de la noticia que mas pesa en este nodo.
              // Es lo unico que este sitio sabe que no sabe ya la fuente, asi
              // que es lo que merece salir al pasar por encima. Cuando el bit
              // no lo lleva -el modo automatico lo deja en blanco a proposito,
              // porque es un juicio editorial y ahi no hay nadie- se enseña su
              // disparador, que son las palabras del propio bit y no un relleno.
              $brief  = heatmap_briefs_de($mapa, $nodo_id)[0] ?? null;
              $porque = $brief === null
                  ? ''
                  : ($brief['por_que'] !== '' ? $brief['por_que'] : $brief['trigger']);

              if ($brief !== null) {
                  $rotulo .= ' ' . $brief['headline'] . '. ' . $porque;
              }
          }

          // El simbolo de dentro, escalado al punto. Se dibuja en coordenadas
          // absolutas y no con un <use> trasladado porque son cuatro numeros
          // y asi el SVG no necesita <defs>, que es una pieza menos que puede
          // quedarse sin resolver si alguien copia el dibujo a otro sitio.
          $g = $radio * 0.42;
          $x = $punto['x'];
          $y = $punto['y'];

          $glifo = $estado === null ? '' : ($estado['signal'] === 'riesgo'
              ? sprintf(
                  'M %.1f %.1f L %.1f %.1f L %.1f %.1f Z',
                  $x, $y - $g, $x + $g * 1.1, $y + $g * 0.8, $x - $g * 1.1, $y + $g * 0.8
              )
              : sprintf(
                  'M %.1f %.1f L %.1f %.1f L %.1f %.1f L %.1f %.1f L %.1f %.1f L %.1f %.1f L %.1f %.1f Z',
                  $x, $y - $g,
                  $x + $g, $y + $g * 0.05,
                  $x + $g * 0.4, $y + $g * 0.05,
                  $x + $g * 0.4, $y + $g * 0.9,
                  $x - $g * 0.4, $y + $g * 0.9,
                  $x - $g * 0.4, $y + $g * 0.05,
                  $x - $g, $y + $g * 0.05
              ));
        ?>
        <a class="<?= web_e($clase) ?>"
           href="<?= web_e($base) ?>/mapa.html#/nodo/<?= web_e($nodo_id) ?>"
           data-nodo="<?= web_e($nodo_id) ?>"
           data-titulo="<?= web_e($nombre) ?>"
           data-porque="<?= web_e($porque) ?>"
           aria-label="<?= web_e($rotulo) ?>">

          <?php // El tooltip de quien no tiene JavaScript. aria-label manda
                // sobre <title> para el nombre accesible, asi que esto solo se
                // ve, no se oye dos veces. ?>
          <title><?= web_e($nombre . ' — ' . $porque) ?></title>

          <?php if ($estado !== null): ?>
            <circle class="mapa-halo" cx="<?= $x ?>" cy="<?= $y ?>" r="<?= (int) round($radio * 2.5) ?>"/>
            <circle class="mapa-aura" cx="<?= $x ?>" cy="<?= $y ?>" r="<?= (int) round($radio * 1.6) ?>"/>
          <?php endif; ?>

          <circle class="mapa-punto" cx="<?= $x ?>" cy="<?= $y ?>" r="<?= $radio ?>"/>

          <?php if ($glifo !== ''): ?>
            <path class="mapa-glifo" d="<?= web_e($glifo) ?>"/>
          <?php endif; ?>

          <text class="mapa-etiqueta" x="<?= $x ?>" y="<?= $y + $radio + 16 ?>"><?= web_e($rotulo_corto) ?></text>

          <?php if ($nivel !== ''): ?>
            <text class="mapa-etiqueta-nivel" x="<?= $x ?>" y="<?= $y + $radio + 30 ?>"><?= web_e($nivel) ?></text>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </g>
  </svg>
 </div>

  <?php if ($semana !== ''): ?>
    <?php // La fecha va en una esquina del dibujo y fuera del SVG, para que no
          // se vaya de la pantalla al arrastrar ni crezca al ampliar: es un
          // dato del mapa, no una pieza del mapa.
          //
          // Y va aqui aunque la cabecera de la banda ya diga cuantos nodos hay
          // encendidos: el mapa se comparte en capturas, y una captura sin
          // fecha de un mapa que se congela por semanas es exactamente la forma
          // de que alguien enseñe en una reunion el estado de hace un mes
          // creyendo que es el de hoy. ?>
    <p class="mapa-fecha">
      <span class="mapa-fecha-rotulo">Actualizado</span>
      <time datetime="<?= web_e($semana) ?>"><?= web_e(web_fecha_larga($semana)) ?></time>
      <?php if (!empty($mapa['stale'])): ?>
        <span class="mapa-fecha-vieja">· sin novedades desde entonces</span>
      <?php endif; ?>
    </p>
  <?php endif; ?>
</div>
