<?php
/**
 * El aviso de cookies. Se escribe como publico/cookies.js.
 *
 * No pide "aceptar" nada -no hay ninguna cookie no esencial que aceptar,
 * como explica el apartado de Cookies del aviso legal-, asi que no lleva
 * botones de aceptar o rechazar: solo informa y se puede cerrar. Un
 * banner con un boton de "aceptar" que no activa ni desactiva nada real
 * seria un gesto vacio, lo mismo que este sitio ya descarto para un
 * banner clasico -ver la decision en docs/README.md-.
 *
 * Aparece nada mas cargar, sin esperar a un scroll ni a un tiempo como el
 * aviso flotante de alta: no es un reclamo publicitario que convenga
 * demorar, es una nota de transparencia. Recuerda el cierre en
 * localStorage para no repetirla en cada visita -mismo mecanismo que el
 * aviso de alta, nunca una cookie: seria irónico usar una cookie para
 * recordar que este sitio no usa cookies-.
 */
declare(strict_types=1);
?>
(function () {
  'use strict';

  var CLAVE = 'bb-cookies-vista';
  var aviso = document.getElementById('aviso-cookies');

  if (!aviso) { return; }

  try {
    if (window.localStorage.getItem(CLAVE) === '1') { return; }
  } catch (e) {
    // Sin localStorage -modo privado, almacenamiento bloqueado- se pinta
    // igual: peor repetir el aviso en cada visita que no mostrarlo nunca.
  }

  aviso.hidden = false;

  var cerrar = aviso.querySelector('.aviso-cookies-cerrar');

  if (cerrar) {
    cerrar.addEventListener('click', function () {
      aviso.hidden = true;
      try {
        window.localStorage.setItem(CLAVE, '1');
      } catch (e) {
        // Sin localStorage no se puede recordar el cierre; se cierra
        // igual para esta visita, y ya esta.
      }
    });
  }
})();
