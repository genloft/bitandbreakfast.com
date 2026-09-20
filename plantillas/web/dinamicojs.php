<?php
/**
 * Buscador en vivo de la cabecera. Se escribe como publico/dinamico.js.
 *
 * Mismo indice.json que el explorador (buscarjs.php), pero sin facetas ni
 * puntuacion: aqui basta con las primeras ocho coincidencias mientras se
 * escribe. La normalizacion tiene que dar el mismo resultado que
 * texto_normalizar() de lib/texto.php, porque el campo 'b' del indice viene
 * normalizado desde PHP -misma regla copiada de buscarjs.php-.
 *
 * El indice llega como {etiquetas, bits} desde la version con facetas; se
 * acepta tambien un array plano por si queda una copia vieja en la cache del
 * navegador.
 *
 * La direccion de cada resultado es la misma que usa el explorador:
 * /d/<fecha>/#bit-<id>. No hay pagina "bit.html" en el sitio -cada bit vive
 * dentro de la portada del dia que le toco-, asi que un enlace con otra
 * forma llevaria a un 404.
 */
declare(strict_types=1);
?>
(function () {
  'use strict';

  var input = document.getElementById('q-dinamico');
  var caja = document.getElementById('resultados-dinamicos');

  if (!input || !caja) { return; }

  var indice = null;
  var cargando = false;

  function normalizar(texto) {
    return String(texto)
      .toLowerCase()
      .normalize('NFD')
      .replace(/[̀-ͯ]/g, '')
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

  function pintar(coincidencias) {
    if (!coincidencias.length) {
      caja.innerHTML = '<p class="cabecera-resultados-vacio">Sin resultados.</p>';
    } else {
      caja.innerHTML = coincidencias.map(function (bit) {
        var url = '/d/' + encodeURIComponent(bit.w) + '/#bit-' + bit.i;
        return '<a href="' + escapar(url) + '">' + escapar(bit.t) + '</a>';
      }).join('');
    }
    caja.hidden = false;
  }

  function buscar(query) {
    var terminos = normalizar(query).split(' ').filter(function (t) { return t.length > 1; });
    var coincidencias = [];

    for (var i = 0; i < indice.length && coincidencias.length < 8; i++) {
      var bit = indice[i];
      var coincide = terminos.every(function (termino) {
        return bit.b.indexOf(termino) !== -1;
      });

      if (coincide) { coincidencias.push(bit); }
    }

    pintar(coincidencias);
  }

  input.addEventListener('input', function () {
    var query = input.value.trim();

    if (query.length < 3) {
      caja.hidden = true;
      return;
    }

    if (indice) {
      buscar(query);
      return;
    }

    if (cargando) { return; }
    cargando = true;

    fetch('/indice.json', { credentials: 'omit', cache: 'no-cache' })
      .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
      .then(function (datos) {
        indice = Array.isArray(datos) ? datos : (datos.bits || []);
        cargando = false;
        buscar(query);
      })
      .catch(function () {
        cargando = false;
      });
  });

  input.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { caja.hidden = true; }
  });

  document.addEventListener('click', function (e) {
    if (!input.contains(e.target) && !caja.contains(e.target)) {
      caja.hidden = true;
    }
  });
})();
