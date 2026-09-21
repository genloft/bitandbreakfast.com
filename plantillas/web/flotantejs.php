<?php
/**
 * El aviso flotante de alta. Se escribe como publico/flotante.js.
 *
 * Solo dos trabajos: decidir cuando aparece, y recordar que alguien lo
 * cerro para no volver a preguntarle en esa misma visita. El formulario en
 * si no necesita nada de esto -manda a /api/suscribir.php como el de
 * siempre, y funciona igual sin JavaScript, solo que entonces se queda
 * escondido con el atributo hidden en vez de aparecer solo-.
 *
 * "Tras un poco de scroll o unos segundos, lo que llegue antes": alguien
 * que solo pasa a mirar el titular y se va no llega a verlo -no hay nada
 * que recordarle-, y alguien que se queda leyendo lo ve sin que tenga que
 * bajar hasta el pie para encontrar el alta de siempre.
 */
declare(strict_types=1);
?>
(function () {
  'use strict';

  var CLAVE = 'bb-alta-cerrada';
  var caja = document.getElementById('flotante-alta');

  if (!caja) { return; }

  try {
    if (window.localStorage.getItem(CLAVE) === '1') { return; }
  } catch (e) {
    // Sin localStorage -modo privado, cookies bloqueadas- se pinta igual:
    // peor volver a preguntar en cada visita que no ofrecer nunca el alta.
  }

  var mostrado = false;

  function mostrar() {
    if (mostrado) { return; }
    mostrado = true;
    caja.hidden = false;
    window.removeEventListener('scroll', comprobarScroll);
  }

  function comprobarScroll() {
    if (window.scrollY > 400) { mostrar(); }
  }

  window.addEventListener('scroll', comprobarScroll, { passive: true });
  window.setTimeout(mostrar, 15000);

  var cerrar = caja.querySelector('.flotante-alta-cerrar');

  if (cerrar) {
    cerrar.addEventListener('click', function () {
      caja.hidden = true;
      window.removeEventListener('scroll', comprobarScroll);
      try {
        window.localStorage.setItem(CLAVE, '1');
      } catch (e) {
        // Sin localStorage no se puede recordar el cierre; se cierra igual
        // para esta visita, y ya esta.
      }
    });
  }
})();
