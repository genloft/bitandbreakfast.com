<?php
/**
 * El guion del mapa: /mapa.js
 *
 * Tres trabajos, y los tres son adorno en el sentido literal: sin este fichero
 * la pagina se lee entera igual.
 *
 *   1. **Navegar el dibujo.** Arrastrar con el raton para moverse, la rueda
 *      para acercar, y tres botones para quien no tenga rueda. Todo se hace
 *      moviendo el viewBox del SVG, no con transform ni con scroll: un SVG
 *      redibuja el trazo a la escala nueva, asi que se puede ampliar cuatro
 *      veces y los rotulos siguen siendo tipografia y no pixeles estirados.
 *   2. **Decir por que.** Al pasar por un nodo sale su "por que importa", que
 *      es lo unico que este sitio sabe que no sabe la noticia. Sin JavaScript
 *      lo hace el navegador solo, con el <title> que la plantilla mete en cada
 *      nodo; cuando hay JavaScript ese <title> se retira -si no, saldrian los
 *      dos- y se pinta uno legible.
 *   3. **Plegar la banda de la portada.** A los cinco segundos de cargar la
 *      pagina entera, el mapa se recoge y deja su resumen y un boton. Es la
 *      forma de que el mapa se vea -que para eso esta arriba- sin quedarse con
 *      la pantalla de quien venia a leer titulares.
 *   4. **El panel lateral.** Al pulsar un nodo, coge su seccion -que ya esta
 *      escrita en la pagina- y la mete en el panel. Al cerrar, la devuelve.
 *      Mueve la seccion en vez de copiarla: con una copia habria dos elementos
 *      con el mismo id -HTML invalido, y el lector de pantalla leeria el
 *      detalle dos veces- y ademas el panel tendria que saber pintar un brief,
 *      que es justo la logica que este sitio no quiere tener en el navegador.
 *
 * Sin JavaScript el mapa se ve entero y quieto, con su tooltip nativo y sus
 * enlaces a anclas que existen. Es un estado perfectamente util, y por eso no
 * hay ni un try/catch defensivo: si algo falla, lo que queda es ese.
 *
 * Sobre la rueda: en /mapa.html amplia directamente, porque alli el mapa es la
 * pagina y nadie llega para pasar de largo. En la banda de la portada no, hasta
 * que se pulsa el dibujo: quien esta bajando a leer noticias y pasa el raton
 * por encima espera que la pagina siga bajando, y un mapa que se come la rueda
 * a la primera es un mapa que atrapa. Lo dice la plantilla con data-rueda, que
 * sabe en cual de las dos esta.
 *
 * La direccion lleva el nodo abierto (#/nodo/pms) porque el gesto que
 * convierte esto en herramienta de equipo es mandarle a alguien el enlace de
 * su parte del stack, no el del mapa entero.
 */

declare(strict_types=1);

?>
(function () {
  'use strict';

  // Cuanto se puede acercar. Mas de cuatro veces se pierde el mapa de vista sin
  // ganar nada: los rotulos ya se leian a dos.
  var MAXIMO = 4;

  // Lo que hay que moverse para que deje de ser un clic y pase a ser un
  // arrastre. Sin este margen, un pulso de mano abre el panel del nodo
  // equivocado o no abre ninguno.
  var UMBRAL = 4;

  // Por debajo de esta escala -pixeles de pantalla por unidad de dibujo- el
  // rotulo de un nodo, que mide 19 unidades, baja de los doce pixeles y deja de
  // leerse de un vistazo. Es lo que decide si el mapa arranca entero o acercado.
  var LEGIBLE = 0.62;

  function montarMapa(marco) {
    var svg = marco.querySelector('.mapa-svg');

    if (!svg) {
      return;
    }

    var caja = svg.getAttribute('viewBox').split(/\s+/).map(Number);
    var ANCHO = caja[2];
    var ALTO = caja[3];
    var vista = { x: 0, y: 0, ancho: ANCHO, alto: ALTO };

    var arrastre = null;
    var recorrido = 0;

    // En cuanto alguien arrastra o amplia, el encuadre es suyo y el automatico
    // no vuelve a tocarlo. Sin esta marca, ensanchar la ventana -o que cargue
    // la tipografia y cambie el ancho- le quitaba de delante lo que estaba
    // mirando.
    var tocado = false;

    function pintar() {
      svg.setAttribute('viewBox', vista.x + ' ' + vista.y + ' ' + vista.ancho + ' ' + vista.alto);
    }

    // La proporcion del hueco, que la manda la hoja de estilo. El viewBox se
    // adapta a ella y no al reves: si el dibujo impusiera su proporcion de 2,7
    // a 1, en un telefono la banda serian 139 pixeles de alto y el mapa un
    // pasillo. Asi, el hueco es mas cuadrado en pantalla estrecha y el encuadre
    // enseña un trozo mas alto del dibujo.
    function aspecto() {
      var r = svg.getBoundingClientRect();

      return r.height > 0 ? r.width / r.height : ANCHO / ALTO;
    }

    // El encuadre mas amplio posible: todo el dibujo que quepa sin deformarlo.
    // En un hueco apaisado lo limita el ancho; en uno alto, el alto.
    function tope() {
      return Math.min(ANCHO, ALTO * aspecto());
    }

    // El encuadre no puede salirse del dibujo por ningun lado: si se pudiera,
    // un arrastre largo deja la pantalla en blanco y no hay forma de saber
    // hacia donde estaba el mapa.
    function encajar() {
      var maximo = tope();

      vista.ancho = Math.min(maximo, Math.max(maximo / MAXIMO, vista.ancho));
      vista.alto = vista.ancho / aspecto();
      vista.x = Math.min(ANCHO - vista.ancho, Math.max(0, vista.x));
      vista.y = Math.min(ALTO - vista.alto, Math.max(0, vista.y));
    }

    // De pixeles de pantalla a unidades del dibujo.
    function unidades(evento) {
      var r = svg.getBoundingClientRect();

      return {
        x: vista.x + (evento.clientX - r.left) / r.width * vista.ancho,
        y: vista.y + (evento.clientY - r.top) / r.height * vista.alto,
        escala: vista.ancho / (r.width || 1)
      };
    }

    // Ampliar dejando quieto el punto que hay debajo del raton. Es la
    // diferencia entre acercarse a lo que estas mirando y que el dibujo se te
    // escape mientras lo haces.
    function ampliar(factor, hacia) {
      var antes = vista.ancho;

      tocado = true;

      vista.ancho = vista.ancho / factor;
      encajar();

      var real = antes / vista.ancho;

      vista.x = hacia.x - (hacia.x - vista.x) / real;
      vista.y = hacia.y - (hacia.y - vista.y) / real;

      encajar();
      pintar();
    }

    function centro() {
      return { x: vista.x + vista.ancho / 2, y: vista.y + vista.alto / 2 };
    }

    // Todo el dibujo que quepa, centrado. En un hueco mas alto que el dibujo
    // sobra sitio arriba y abajo, y se reparte.
    function entero() {
      vista.ancho = tope();
      vista.alto = vista.ancho / aspecto();
      vista.x = (ANCHO - vista.ancho) / 2;
      vista.y = (ALTO - vista.alto) / 2;
      encajar();
      pintar();
    }

    // --- Arrastrar ------------------------------------------------------------

    svg.addEventListener('pointerdown', function (evento) {
      if (evento.button !== 0) {
        return;
      }

      arrastre = unidades(evento);
      recorrido = 0;
      marco.classList.add('mapa-marco--agarrado');
    });

    svg.addEventListener('pointermove', function (evento) {
      if (!arrastre) {
        return;
      }

      var ahora = unidades(evento);

      recorrido += (Math.abs(ahora.x - arrastre.x) + Math.abs(ahora.y - arrastre.y)) / arrastre.escala;

      if (recorrido < UMBRAL) {
        return;
      }

      // El puntero se captura solo cuando ya es un arrastre de verdad: antes,
      // capturarlo se comeria el clic sobre un nodo.
      if (svg.setPointerCapture && !svg.hasPointerCapture(evento.pointerId)) {
        svg.setPointerCapture(evento.pointerId);
      }

      evento.preventDefault();
      esconderPista();
      tocado = true;

      vista.x -= ahora.x - arrastre.x;
      vista.y -= ahora.y - arrastre.y;
      encajar();
      pintar();

      // Despues de mover el encuadre, el mismo pixel de pantalla cae en otro
      // punto del dibujo: el origen se vuelve a leer, si no la imagen se
      // acelera sola mientras arrastras.
      arrastre = unidades(evento);
    });

    function soltar() {
      arrastre = null;
      marco.classList.remove('mapa-marco--agarrado');
    }

    svg.addEventListener('pointerup', soltar);
    svg.addEventListener('pointercancel', soltar);

    // Un clic que ha sido arrastre no es un clic: se traga en captura, antes de
    // que llegue al <a> del nodo y abra un panel que nadie ha pedido.
    svg.addEventListener('click', function (evento) {
      if (recorrido >= UMBRAL) {
        evento.preventDefault();
        evento.stopPropagation();
        recorrido = 0;
      }
    }, true);

    // --- La rueda -------------------------------------------------------------

    var directa = marco.getAttribute('data-rueda') === 'directa';
    var activo = directa;

    if (!directa) {
      // Pulsar el fondo del dibujo -no un nodo- le da permiso a la rueda.
      svg.addEventListener('click', function (evento) {
        if (!evento.target.closest('a.mapa-nodo-enlace')) {
          activo = true;
          marco.classList.add('mapa-marco--activo');
        }
      });

      marco.addEventListener('pointerleave', function () {
        activo = false;
        marco.classList.remove('mapa-marco--activo');
      });
    }

    svg.addEventListener('wheel', function (evento) {
      if (!activo) {
        return;
      }

      evento.preventDefault();
      esconderPista();
      ampliar(evento.deltaY < 0 ? 1.18 : 1 / 1.18, unidades(evento));
    }, { passive: false });

    // --- El "por que" al pasar por encima -------------------------------------
    //
    // La plantilla deja el texto en data-porque y ademas en un <title>, que es
    // el tooltip que enseña el navegador cuando aqui no llega nadie. Ahora que
    // hemos llegado, el <title> estorba: saldrian los dos, el nativo tarde y
    // encima del nuestro.

    var pista = document.createElement('div');

    pista.className = 'mapa-pista';
    pista.hidden = true;
    marco.appendChild(pista);

    function esconderPista() {
      pista.hidden = true;
    }

    function mostrarPista(nodo) {
      var texto = nodo.getAttribute('data-porque');

      if (!texto) {
        return;
      }

      pista.textContent = '';

      var titulo = document.createElement('strong');

      titulo.textContent = nodo.getAttribute('data-titulo') || '';
      pista.appendChild(titulo);
      pista.appendChild(document.createTextNode(texto));
      pista.hidden = false;

      // Encima del nodo, centrada, y dentro del marco: una pista que se sale
      // por el canto se lee peor que no tenerla.
      var r = nodo.getBoundingClientRect();
      var m = marco.getBoundingClientRect();
      var ancho = pista.offsetWidth;
      var alto = pista.offsetHeight;

      var x = r.left - m.left + r.width / 2 - ancho / 2;
      var y = r.top - m.top - alto - 10;

      pista.classList.toggle('mapa-pista--debajo', y < 0);

      if (y < 0) {
        y = r.bottom - m.top + 10;
      }

      pista.style.left = Math.min(m.width - ancho - 6, Math.max(6, x)) + 'px';
      pista.style.top = Math.min(m.height - alto - 6, Math.max(6, y)) + 'px';
    }

    Array.prototype.forEach.call(svg.querySelectorAll('a.mapa-nodo-enlace'), function (nodo) {
      var nativo = nodo.querySelector('title');

      if (nativo) {
        nativo.parentNode.removeChild(nativo);
      }

      nodo.addEventListener('pointerenter', function () {
        if (!arrastre) {
          mostrarPista(nodo);
        }
      });

      nodo.addEventListener('pointerleave', esconderPista);
      nodo.addEventListener('focus', function () { mostrarPista(nodo); });
      nodo.addEventListener('blur', esconderPista);
    });

    // --- Los botones ----------------------------------------------------------
    //
    // Se crean aqui y no en la plantilla a proposito: sin JavaScript no harian
    // nada, y un boton que no hace nada es peor que no tenerlo. Son ademas la
    // unica forma de ampliar para quien no tiene rueda.

    var mandos = document.createElement('div');

    mandos.className = 'mapa-mandos';

    [
      ['+', 'Acercar el mapa', function () { ampliar(1.5, centro()); }],
      ['−', 'Alejar el mapa', function () { ampliar(1 / 1.5, centro()); }],
      ['⟲', 'Ver el mapa entero', function () { tocado = true; entero(); }]
    ].forEach(function (mando) {
      var boton = document.createElement('button');

      boton.type = 'button';
      boton.textContent = mando[0];
      boton.setAttribute('aria-label', mando[1]);
      boton.addEventListener('click', mando[2]);
      mandos.appendChild(boton);
    });

    marco.appendChild(mandos);

    // --- Teclado --------------------------------------------------------------
    //
    // Tabular por los nodos ya funciona sin nada de esto, pero un nodo que
    // recibe el foco fuera del encuadre no se ve: el foco esta en un sitio y la
    // vista en otro. Se trae el encuadre a donde esta el foco.

    svg.addEventListener('focusin', function (evento) {
      var nodo = evento.target.closest ? evento.target.closest('a.mapa-nodo-enlace') : null;
      var punto = nodo && nodo.querySelector('.mapa-punto');

      if (!punto || vista.ancho >= ANCHO) {
        return;
      }

      var px = Number(punto.getAttribute('cx'));
      var py = Number(punto.getAttribute('cy'));

      if (px < vista.x || px > vista.x + vista.ancho || py < vista.y || py > vista.y + vista.alto) {
        vista.x = px - vista.ancho / 2;
        vista.y = py - vista.alto / 2;
        encajar();
        pintar();
      }
    });

    // --- El encuadre de partida -----------------------------------------------
    //
    // En una pantalla ancha, el mapa entero. En una estrecha el mapa entero son
    // rotulos de cinco pixeles, asi que arranca acercado a la zona mas caliente
    // y desde ahi se arrastra. Es lo que sustituye al scroll horizontal que
    // habia antes: el mapa ya no se sale de su sitio, se navega.

    function encuadrar() {
      var r = svg.getBoundingClientRect();

      if (!r.width) {
        return;
      }

      entero();

      if (r.width / vista.ancho >= LEGIBLE) {
        return;
      }

      vista.ancho = r.width / LEGIBLE;
      encajar();

      var caliente = svg.querySelector('.mapa-nodo-enlace--alto .mapa-punto')
                  || svg.querySelector('.mapa-punto');

      var foco = caliente
        ? { x: Number(caliente.getAttribute('cx')), y: Number(caliente.getAttribute('cy')) }
        : { x: ANCHO / 2, y: ALTO / 2 };

      vista.x = foco.x - vista.ancho / 2;
      vista.y = foco.y - vista.alto / 2;
      encajar();
      pintar();
    }

    encuadrar();

    // El encuadre automatico sigue al ancho del dibujo mientras nadie lo haya
    // tocado: al girar el telefono, al ensanchar la ventana y tambien cuando
    // acaba de cargar la tipografia y la maquetacion se mueve sola, que es lo
    // que dejaba el mapa acercado en una pantalla en la que cabia entero.
    //
    // Un ResizeObserver sobre el propio SVG y no un 'resize' de ventana: mide
    // lo que hay que medir -el hueco real del dibujo, que tiene tope de ancho
    // propio- y se entera tambien de los cambios que no vienen de la ventana.
    if (typeof ResizeObserver === 'function') {
      var medida = svg.getBoundingClientRect();
      var ancho = medida.width;
      var alto = medida.height;

      new ResizeObserver(function () {
        var nueva = svg.getBoundingClientRect();

        if (Math.abs(nueva.width - ancho) < 1 && Math.abs(nueva.height - alto) < 1) {
          return;
        }

        ancho = nueva.width;
        alto = nueva.height;
        esconderPista();

        if (tocado) {
          // Su encuadre es suyo, pero la proporcion no: sin reencajar, el
          // viewBox conserva la de antes y el dibujo sale estirado.
          encajar();
          pintar();
          return;
        }

        encuadrar();
      }).observe(svg);
    }
  }

  Array.prototype.forEach.call(document.querySelectorAll('.mapa-marco'), montarMapa);

  // --- Plegar la banda de la portada -------------------------------------------
  //
  // Cinco segundos desde que la pagina esta entera, no desde que empieza a
  // cargar: en una conexion lenta, contar desde el principio pliega el mapa
  // antes de que se haya llegado a ver.
  //
  // La decision de quien lo abre se recuerda durante la visita. Volver a la
  // portada y que se te cierre otra vez en la cara lo convierte de comodidad en
  // pelea, y a la tercera vez ya nadie lo abre.

  function montarPlegado(banda) {
    var segundos = Number(banda.getAttribute('data-plegar')) || 5;
    var cabeza = banda.querySelector('.mapa-banda-cabeza');

    if (!cabeza) {
      return;
    }

    // Navegacion privada o almacenamiento bloqueado: se pliega igual, solo que
    // sin memoria. Es una comodidad, no un requisito, asi que el fallo se traga.
    function guardadoDice() {
      try {
        return sessionStorage.getItem('mapa-abierto');
      } catch (e) {
        return null;
      }
    }

    var abierto = true;
    var boton = document.createElement('button');

    boton.type = 'button';
    boton.className = 'mapa-banda-plegar';
    banda.appendChild(boton);

    function pintar() {
      banda.classList.toggle('mapa-banda--plegada', !abierto);
      boton.textContent = abierto ? 'Ocultar el mapa' : 'Ver el mapa del stack';
      boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
      boton.setAttribute('aria-controls', 'mapa-banda');
    }

    boton.addEventListener('click', function () {
      abierto = !abierto;
      pintar();

      try {
        sessionStorage.setItem('mapa-abierto', abierto ? 'si' : 'no');
      } catch (e) {
        // Igual que arriba: sin memoria, pero funcionando.
      }
    });

    // Arranca abierto -es lo que hay en el HTML- y se pliega luego. Al reves
    // habria un parpadeo: el mapa aparece plegado y se abre, que es justo el
    // gesto contrario al que se quiere.
    pintar();

    // Quien ya lo cerro en esta visita no tiene que volver a verlo cinco
    // segundos en cada pagina: se le pliega en el acto.
    if (guardadoDice() === 'no') {
      abierto = false;
      pintar();
      return;
    }

    // Y quien lo dejo abierto, abierto se queda.
    if (guardadoDice() === 'si') {
      return;
    }

    setTimeout(function () {
      if (guardadoDice() !== null) {
        return;
      }

      abierto = false;
      pintar();
    }, segundos * 1000);
  }

  Array.prototype.forEach.call(document.querySelectorAll('[data-plegar]'), function (banda) {
    if (document.readyState === 'complete') {
      montarPlegado(banda);
      return;
    }

    window.addEventListener('load', function () { montarPlegado(banda); });
  });

  // --- El panel lateral -------------------------------------------------------

  var panel  = document.getElementById('mapa-panel');
  var cuerpo = document.getElementById('mapa-panel-cuerpo');

  // Sin <dialog> modal no hay panel que valga: los nodos se quedan siendo
  // enlaces a un ancla, que es exactamente lo que ya hacen solos.
  if (!panel || !cuerpo || typeof panel.showModal !== 'function') {
    return;
  }

  // Donde estaba la seccion antes de traerla. Un comentario vacio marca el
  // hueco: sobrevive a cualquier cambio de maquetacion y no pinta nada.
  var marca  = document.createComment('nodo');
  var puesto = null;

  function nodoDeHash(hash) {
    var trozos = /^#\/nodo\/([a-z0-9-]+)$/.exec(hash || '');

    return trozos ? trozos[1] : '';
  }

  function devolver() {
    if (puesto && marca.parentNode) {
      marca.parentNode.replaceChild(puesto, marca);
    }

    puesto = null;
  }

  function abrir(id) {
    var seccion = document.getElementById('nodo-' + id);

    if (!seccion) {
      return false;
    }

    // Si ya hay uno dentro, primero vuelve a su sitio: el panel enseña un nodo,
    // no una pila.
    devolver();

    seccion.parentNode.replaceChild(marca, seccion);
    cuerpo.appendChild(seccion);
    puesto = seccion;

    if (!panel.open) {
      panel.showModal();
    }

    seccion.focus();

    return true;
  }

  function cerrar() {
    if (panel.open) {
      panel.close();
    }
  }

  // El <dialog> nativo ya cierra con Escape y con el boton del formulario, asi
  // que solo hay que recoger lo que deja: devolver la seccion y limpiar la
  // direccion para que recargar no vuelva a abrir lo que se acaba de cerrar.
  panel.addEventListener('close', function () {
    devolver();

    if (nodoDeHash(location.hash) !== '') {
      history.replaceState(null, '', location.pathname + location.search);
    }
  });

  // Pulsar fuera del panel lo cierra. Se compara contra el propio <dialog>
  // porque el fondo oscuro es su ::backdrop y los clics en el llegan con target
  // igual al dialogo.
  panel.addEventListener('click', function (evento) {
    if (evento.target === panel) {
      cerrar();
    }
  });

  document.addEventListener('click', function (evento) {
    var nodo = evento.target.closest ? evento.target.closest('a.mapa-nodo-enlace') : null;

    if (!nodo) {
      return;
    }

    // Un <a> dentro de un SVG no es un HTMLAnchorElement: no tiene .hash ni
    // .pathname, y su .href es un SVGAnimatedString. Se lee el atributo y se
    // resuelve con URL, que ademas funciona igual si el enlace fuera relativo.
    var destino = new URL(nodo.getAttribute('href'), location.href);
    var id = nodo.getAttribute('data-nodo') || nodoDeHash(destino.hash);

    // Un nodo de la portada apunta a otra pagina: que el navegador haga lo suyo
    // y se la lleve.
    if (id === '' || destino.pathname !== location.pathname) {
      return;
    }

    if (abrir(id)) {
      evento.preventDefault();
      history.pushState(null, '', destino.hash);
    }
  });

  // Llegar con el enlace ya puesto -alguien lo ha compartido- y moverse con las
  // flechas del navegador tienen que hacer lo mismo que pulsar.
  window.addEventListener('hashchange', function () {
    var id = nodoDeHash(location.hash);

    if (id === '') {
      cerrar();
      return;
    }

    abrir(id);
  });

  var inicial = nodoDeHash(location.hash);

  if (inicial !== '') {
    abrir(inicial);
  }
})();
