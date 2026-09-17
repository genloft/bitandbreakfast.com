<?php
/**
 * El explorador, en el navegador. Se escribe como publico/buscar.js.
 *
 * Vanilla y sin dependencias, como el resto del proyecto. Descarga
 * indice.json una sola vez y busca y filtra en memoria.
 *
 * Como funcionan los filtros: dentro de un grupo se suman (temática A o B),
 * entre grupos se restan (temática A y ademas idioma español). Es lo que
 * espera cualquiera que haya usado una tienda, y evita el callejon de elegir
 * dos idiomas y no obtener nada.
 *
 * Los recuentos de cada opcion se calculan con el resto de filtros aplicados
 * pero sin el propio grupo, que es lo que hace que nunca te lleven a cero.
 *
 * La normalizacion tiene que dar el mismo resultado que texto_normalizar() de
 * lib/texto.php, porque el campo 'b' del indice viene normalizado desde PHP.
 */

declare(strict_types=1);

require_once __DIR__ . '/iconos.php';

?>
(function () {
  'use strict';

  // Los dibujos de cada tematica, escritos aqui por PHP desde la misma lista
  // que usa la web. Si se copiaran a mano, el dia que cambie un icono habria
  // dos versiones y solo se notaria en esta pagina.
  var ICONOS = <?= json_encode(web_iconos(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

  var campo = document.getElementById('q');
  var lista = document.getElementById('resultados');
  var estado = document.getElementById('estado');
  var cajaFacetas = document.getElementById('facetas');
  var botonLimpiar = document.getElementById('limpiar');

  if (!campo || !lista || !estado || !cajaFacetas) {
    return;
  }

  var GRUPOS = ['c', 'a', 'l', 'fu'];
  var MAXIMO = 60;

  var bits = null;
  var etiquetas = {};
  var cargando = false;
  var seleccion = { c: [], a: [], l: [], fu: [] };

  // Mismas reglas que texto_normalizar() en PHP.
  function normalizar(texto) {
    return String(texto)
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9]+/g, ' ')
      .trim();
  }

  function escapar(texto) {
    return String(texto)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function etiqueta(grupo, valor) {
    return (etiquetas[grupo] && etiquetas[grupo][valor]) || valor;
  }

  function terminos() {
    return normalizar(campo.value).split(' ').filter(function (t) { return t.length > 1; });
  }

  /**
   * ¿Pasa este bit los filtros? Se puede pedir ignorar un grupo, que es como
   * se calculan los recuentos de ese mismo grupo.
   */
  function pasa(bit, salvo) {
    for (var i = 0; i < GRUPOS.length; i++) {
      var grupo = GRUPOS[i];

      if (grupo === salvo || !seleccion[grupo].length) {
        continue;
      }

      if (seleccion[grupo].indexOf(bit[grupo]) === -1) {
        return false;
      }
    }

    return true;
  }

  function puntuar(bit, palabras) {
    if (!palabras.length) {
      return 1;
    }

    var total = 0;
    var titular = normalizar(bit.t);
    var proveedores = normalizar(bit.v || '');

    for (var i = 0; i < palabras.length; i++) {
      var palabra = palabras[i];

      if (bit.b.indexOf(palabra) === -1) {
        return 0;
      }

      // El titular pesa mas que el cuerpo, y el proveedor casi tanto: quien
      // busca "mews" quiere las noticias de Mews, no las que lo nombran de
      // pasada en la tercera linea.
      total += 1;
      if (titular.indexOf(palabra) !== -1) { total += 6; }
      if (proveedores.indexOf(palabra) !== -1) { total += 4; }
    }

    return total;
  }

  function filtrados() {
    var palabras = terminos();
    var salida = [];

    for (var i = 0; i < bits.length; i++) {
      if (!pasa(bits[i], null)) {
        continue;
      }

      var puntos = puntuar(bits[i], palabras);

      if (puntos > 0) {
        // A igualdad de puntos, lo mas reciente primero.
        salida.push({ p: puntos * 1000 + Math.min(bits[i].n, 999), b: bits[i] });
      }
    }

    salida.sort(function (a, b) { return b.p - a.p; });

    return salida.map(function (e) { return e.b; });
  }

  /**
   * Pinta las opciones de cada grupo con su recuento, ordenadas por uso.
   */
  function pintarFacetas() {
    var palabras = terminos();

    GRUPOS.forEach(function (grupo) {
      var caja = cajaFacetas.querySelector('[data-faceta="' + grupo + '"] .opciones');

      if (!caja) { return; }

      var cuentas = {};

      for (var i = 0; i < bits.length; i++) {
        var bit = bits[i];

        if (!bit[grupo] || !pasa(bit, grupo) || puntuar(bit, palabras) === 0) {
          continue;
        }

        cuentas[bit[grupo]] = (cuentas[bit[grupo]] || 0) + 1;
      }

      // Las seleccionadas siguen apareciendo aunque el recuento sea cero: si
      // no, al filtrar desapareceria el propio boton que hay que volver a
      // tocar para deshacerlo.
      seleccion[grupo].forEach(function (valor) {
        if (!(valor in cuentas)) { cuentas[valor] = 0; }
      });

      var valores = Object.keys(cuentas).sort(function (a, b) {
        if (cuentas[b] !== cuentas[a]) { return cuentas[b] - cuentas[a]; }
        return etiqueta(grupo, a).localeCompare(etiqueta(grupo, b), 'es');
      });

      var html = '';

      valores.forEach(function (valor) {
        var activa = seleccion[grupo].indexOf(valor) !== -1;

        html += '<button type="button" class="opcion" aria-pressed="' + activa + '"';

        // El color y el icono solo en las tematicas: en fuente, ambito e
        // idioma no significarian nada y serian ruido de colores.
        if (grupo === 'c') {
          html += ' data-tema="' + escapar(valor) + '"';
        }

        html += ' data-grupo="' + escapar(grupo) + '" data-valor="' + escapar(valor) + '">';

        if (grupo === 'c' && ICONOS[valor]) {
          html += '<svg class="icono icono-mini" viewBox="0 0 24 24" fill="none" '
                + 'stroke="currentColor" stroke-width="1.5" stroke-linecap="round" '
                + 'stroke-linejoin="round" aria-hidden="true">' + ICONOS[valor] + '</svg>';
        }

        html += '<span class="opcion-nombre">' + escapar(etiqueta(grupo, valor)) + '</span>';
        html += '<span class="cuenta-opcion">' + cuentas[valor] + '</span>';
        html += '</button>';
      });

      caja.innerHTML = html;
      caja.parentNode.hidden = valores.length < 2 && !seleccion[grupo].length;
    });

    var hayFiltros = GRUPOS.some(function (g) { return seleccion[g].length > 0; });
    botonLimpiar.hidden = !hayFiltros;
  }

  function pintarResultados(resultados) {
    var hayFiltros = GRUPOS.some(function (g) { return seleccion[g].length > 0; });
    var hayTexto = terminos().length > 0;

    // Sin filtros se ensena todo lo publicado, lo mas reciente primero. Antes
    // esto era una pagina en blanco con un "escribe algo": el lector llega a
    // "En todo lo publicado" y se encuentra con que no hay nada publicado.

    if (!resultados.length) {
      lista.innerHTML = '';
      estado.textContent = hayFiltros || hayTexto
        ? 'Nada con esos criterios. Prueba a quitar un filtro.'
        : 'Todavía no hay nada publicado.';
      return;
    }

    var recorte = resultados.length > MAXIMO ? ' (se muestran los ' + MAXIMO + ' primeros)' : '';

    if (!hayFiltros && !hayTexto) {
      estado.textContent = resultados.length === 1
        ? 'Un bit publicado'
        : resultados.length + ' bits publicados, del más reciente al más antiguo' + recorte;
    } else {
      estado.textContent = resultados.length === 1
        ? '1 resultado'
        : resultados.length + ' resultados' + recorte;
    }

    var html = '';

    resultados.slice(0, MAXIMO).forEach(function (f) {
      var url = '/e/' + encodeURIComponent(f.s) + '/#bit-' + f.i;

      html += '<li>';
      html += '<h2><a href="' + escapar(url) + '">' + escapar(f.t) + '</a></h2>';
      html += '<p class="datos">Edición ' + f.n + '<span class="punto">·</span>';
      html += '<time datetime="' + escapar(f.f) + '">' + escapar(f.d || f.f) + '</time>';

      if (f.fu) {
        html += '<span class="punto">·</span>' + escapar(f.fu);
      }

      html += '</p>';

      if (f.q) {
        html += '<p class="resumen">' + escapar(f.q) + '</p>';
      }

      html += '</li>';
    });

    lista.innerHTML = html;
  }

  function refrescar() {
    if (!bits) { return; }

    pintarFacetas();
    pintarResultados(filtrados());
    guardarEnUrl();
  }

  /**
   * Deja el estado en la barra de direcciones para que una busqueda se pueda
   * compartir o guardar. replaceState y no pushState: al volver atras se sale
   * de la pagina, no se deshace filtro a filtro.
   */
  function guardarEnUrl() {
    var params = new URLSearchParams();

    if (campo.value.trim()) { params.set('q', campo.value.trim()); }

    GRUPOS.forEach(function (grupo) {
      if (seleccion[grupo].length) { params.set(grupo, seleccion[grupo].join('|')); }
    });

    var cadena = params.toString();

    history.replaceState(null, '', cadena ? '?' + cadena : location.pathname);
  }

  function leerDeUrl() {
    var params = new URLSearchParams(location.search);

    if (params.get('q')) { campo.value = params.get('q'); }

    GRUPOS.forEach(function (grupo) {
      var valor = params.get(grupo);

      if (valor) { seleccion[grupo] = valor.split('|').filter(Boolean); }
    });
  }

  function cargar(despues) {
    if (bits) { despues(); return; }
    if (cargando) { return; }

    cargando = true;
    estado.textContent = 'Cargando el índice…';

    // cache: 'no-cache' no significa "no guardes": significa "pregunta antes de
    // usar lo guardado". El indice cambia cada vez que se publica y no lleva
    // version en la URL, asi que sin esto el explorador puede ensenar la
    // edicion de la semana pasada con toda la confianza del mundo.
    fetch('/indice.json', { credentials: 'omit', cache: 'no-cache' })
      .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
      .then(function (datos) {
        // Formato nuevo con etiquetas; se acepta el antiguo por si queda una
        // copia en la cache del navegador.
        bits = Array.isArray(datos) ? datos : (datos.bits || []);
        etiquetas = (datos && datos.etiquetas) || {};
        cargando = false;
        despues();
      })
      .catch(function () {
        cargando = false;
        estado.textContent = 'No se ha podido cargar el índice. Recarga la página.';
      });
  }

  // --- Sucesos --------------------------------------------------------------

  var temporizador = null;

  campo.addEventListener('input', function () {
    clearTimeout(temporizador);
    temporizador = setTimeout(function () { cargar(refrescar); }, 120);
  });

  campo.form.addEventListener('submit', function (evento) {
    evento.preventDefault();
    cargar(refrescar);
  });

  // Delegado: los botones se repintan en cada refresco, asi que escuchar en
  // el contenedor evita volver a engancharlos uno a uno.
  cajaFacetas.addEventListener('click', function (evento) {
    var boton = evento.target.closest('.opcion');

    if (!boton) { return; }

    var grupo = boton.getAttribute('data-grupo');
    var valor = boton.getAttribute('data-valor');
    var donde = seleccion[grupo].indexOf(valor);

    if (donde === -1) {
      seleccion[grupo].push(valor);
    } else {
      seleccion[grupo].splice(donde, 1);
    }

    refrescar();
  });

  botonLimpiar.addEventListener('click', function () {
    GRUPOS.forEach(function (grupo) { seleccion[grupo] = []; });
    campo.value = '';
    refrescar();
  });

  // --- Arranque -------------------------------------------------------------

  leerDeUrl();

  var hayEstado = campo.value.trim() || GRUPOS.some(function (g) { return seleccion[g].length > 0; });

  // El indice se descarga siempre en esta pagina: quien llega aqui viene a
  // explorar, y los filtros no se pueden pintar sin datos.
  cargar(refrescar);

  if (!hayEstado) {
    // Mientras baja el indice; en cuanto llega, se pinta la lista entera.
    estado.textContent = 'Cargando lo publicado…';
  }
})();
