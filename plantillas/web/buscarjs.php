<?php
/**
 * El buscador, en el navegador. Se escribe como publico/buscar.js.
 *
 * Vanilla y sin dependencias, como el resto del proyecto. Descarga
 * indice.json una sola vez, la primera que se teclea, y busca en memoria.
 *
 * La normalizacion tiene que dar el mismo resultado que texto_normalizar() de
 * lib/texto.php, porque el campo 'b' del indice viene normalizado desde PHP:
 * minusculas, sin acentos y con cualquier signo convertido en espacio.
 */

declare(strict_types=1);

?>
(function () {
  'use strict';

  var campo = document.getElementById('q');
  var lista = document.getElementById('resultados');
  var estado = document.getElementById('estado');

  if (!campo || !lista || !estado) {
    return;
  }

  var indice = null;
  var cargando = false;
  var MAXIMO = 40;

  // Mismas reglas que texto_normalizar() en PHP.
  function normalizar(texto) {
    return texto
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

  /**
   * Puntua una fila contra los terminos. Todos los terminos tienen que
   * aparecer: una busqueda de dos palabras no puede devolver lo que solo
   * tiene una.
   */
  function puntuar(fila, terminos) {
    var total = 0;
    var titular = normalizar(fila.t);
    var proveedores = normalizar(fila.v || '');

    for (var i = 0; i < terminos.length; i++) {
      var termino = terminos[i];

      if (fila.b.indexOf(termino) === -1) {
        return 0;
      }

      // El titular pesa mas que el cuerpo, y el proveedor casi tanto: quien
      // busca "mews" quiere las noticias de Mews, no las que lo nombran de
      // pasada en la tercera linea.
      total += 1;
      if (titular.indexOf(termino) !== -1) { total += 6; }
      if (proveedores.indexOf(termino) !== -1) { total += 4; }
    }

    // A igualdad, lo mas reciente primero.
    return total * 1000 + Math.min(fila.n, 999);
  }

  function pintar(resultados, consulta) {
    if (!consulta) {
      lista.innerHTML = '';
      estado.textContent = 'Escribe para buscar.';
      return;
    }

    if (!resultados.length) {
      lista.innerHTML = '';
      estado.textContent = 'Nada sobre «' + consulta + '». Prueba con menos palabras.';
      return;
    }

    estado.textContent = resultados.length === 1
      ? '1 resultado'
      : resultados.length + ' resultados' + (resultados.length >= MAXIMO ? ' (se muestran los primeros)' : '');

    var html = '';

    for (var i = 0; i < resultados.length; i++) {
      var f = resultados[i];
      var url = '/e/' + encodeURIComponent(f.s) + '/#bit-' + f.i;

      html += '<li>';
      html += '<h2><a href="' + escapar(url) + '">' + escapar(f.t) + '</a></h2>';
      html += '<p class="datos">Edición ' + f.n + '<span class="punto">·</span>';
      html += '<time datetime="' + escapar(f.f) + '">' + escapar(f.d || f.f) + '</time></p>';

      if (f.q) {
        html += '<p class="resumen">' + escapar(f.q) + '</p>';
      }

      html += '</li>';
    }

    lista.innerHTML = html;
  }

  function buscar() {
    var consulta = campo.value.trim();
    var terminos = normalizar(consulta).split(' ').filter(function (t) { return t.length > 1; });

    if (!terminos.length) {
      pintar([], '');
      return;
    }

    var encontrados = [];

    for (var i = 0; i < indice.length; i++) {
      var puntos = puntuar(indice[i], terminos);

      if (puntos > 0) {
        encontrados.push({ p: puntos, f: indice[i] });
      }
    }

    encontrados.sort(function (a, b) { return b.p - a.p; });

    pintar(encontrados.slice(0, MAXIMO).map(function (e) { return e.f; }), consulta);
  }

  /**
   * El indice se descarga la primera vez que hace falta, no al abrir la
   * pagina: quien llega y no busca nada no tiene por que pagar la descarga.
   */
  function asegurarIndice(despues) {
    if (indice) { despues(); return; }
    if (cargando) { return; }

    cargando = true;
    estado.textContent = 'Cargando el índice…';

    fetch('/indice.json', { credentials: 'omit' })
      .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
      .then(function (datos) {
        indice = datos;
        cargando = false;
        despues();
      })
      .catch(function () {
        cargando = false;
        estado.textContent = 'No se ha podido cargar el índice. Recarga la página.';
      });
  }

  var temporizador = null;

  campo.addEventListener('input', function () {
    clearTimeout(temporizador);
    temporizador = setTimeout(function () {
      asegurarIndice(buscar);
    }, 120);
  });

  // El formulario no envia a ningun sitio: la busqueda es local.
  campo.form.addEventListener('submit', function (evento) {
    evento.preventDefault();
    asegurarIndice(buscar);
  });

  // Permite enlazar una busqueda: buscar.html?q=mews
  var inicial = new URLSearchParams(window.location.search).get('q');

  if (inicial) {
    campo.value = inicial;
    asegurarIndice(buscar);
  }
})();
