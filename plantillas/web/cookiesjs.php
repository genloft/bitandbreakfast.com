<?php
/**
 * Google Analytics y el aviso de cookies. Se escribe como
 * publico/cookies.js.
 *
 * A peticion expresa, confirmada dos veces tras explicar exactamente lo
 * que implicaba: Google Analytics se activa para toda visita, sin
 * esperar a ningun clic. No hay ya una puerta de consentimiento real
 * que abrir o cerrar, asi que el aviso deja de tener botones de
 * "Aceptar" o "Rechazar" -unos botones que no controlaran nada de
 * verdad serian peor que no llevar aviso, el mismo gesto vacio que este
 * sitio ya descarto una vez para un banner de "aceptar cookies"-.
 * Vuelve a ser lo que fue antes de esa puerta: informativo, con un
 * boton de cerrar que se recuerda en localStorage para no repetirse en
 * cada visita, y un enlace a como desactivarlo de verdad -la extension
 * oficial de Google, la unica forma real de no ser medido por este
 * guion concreto-.
 *
 * El guion de gtag.js no se pega como <script> en linea en ninguna
 * plantilla: el Content-Security-Policy de este sitio no lleva
 * 'unsafe-inline' en script-src -nunca lo ha llevado, ver los
 * comentarios de migas.php y bit.php- y no se va a debilitar esa
 * politica para esto. Se inyecta desde aqui, un fichero .js propio que
 * si esta permitido, con exactamente el mismo efecto que el trozo de
 * codigo de Google: mismo dataLayer, mismo gtag(), mismo 'js' y mismo
 * 'config' con el id de medicion.
 */
declare(strict_types=1);
?>
(function () {
  'use strict';

  // No es un secreto: un id de medicion de Analytics es visible en el
  // codigo fuente de cualquier pagina que lo cargue, por diseño de
  // Google. No hace falta esconderlo ni sacarlo a config().
  var ID_MEDICION = 'G-EFKDVWY725';
  var CLAVE_VISTO = 'bb-cookies-vista';

  window.dataLayer = window.dataLayer || [];
  function gtag() { window.dataLayer.push(arguments); }
  window.gtag = gtag;
  gtag('js', new Date());
  gtag('config', ID_MEDICION);

  var guion = document.createElement('script');
  guion.async = true;
  guion.src = 'https://www.googletagmanager.com/gtag/js?id=' + ID_MEDICION;
  document.head.appendChild(guion);

  // El aviso, solo informativo: no depende de nada de lo anterior, que
  // ya ha pasado se muestre o no.
  var aviso = document.getElementById('aviso-cookies');

  if (!aviso) { return; }

  try {
    if (window.localStorage.getItem(CLAVE_VISTO) === '1') { return; }
  } catch (e) {
    // Sin localStorage -modo privado, almacenamiento bloqueado- se
    // pinta igual: peor repetir el aviso en cada visita que no
    // mostrarlo nunca.
  }

  aviso.hidden = false;

  var cerrar = aviso.querySelector('.aviso-cookies-cerrar');

  if (cerrar) {
    cerrar.addEventListener('click', function () {
      aviso.hidden = true;
      try {
        window.localStorage.setItem(CLAVE_VISTO, '1');
      } catch (e) {
        // Sin localStorage no se puede recordar el cierre; se cierra
        // igual para esta visita, y ya esta.
      }
    });
  }
})();
