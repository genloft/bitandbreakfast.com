<?php
/**
 * El aviso de cookies y el consentimiento de Google Analytics. Se
 * escribe como publico/cookies.js.
 *
 * Google Analytics es la unica pieza de este sitio que instala una
 * cookie de analitica, asi que su guion -gtag.js, servido desde
 * googletagmanager.com- solo se inyecta despues de un "Aceptar"
 * explicito, nunca antes: ni en el HTML servido por el servidor -ese
 * guion no aparece en ninguna plantilla-, ni aqui hasta que alguien
 * pulsa el boton. "Rechazar" no vuelve a preguntar en esa visita, ni en
 * las siguientes, hasta que se borre la marca del navegador.
 *
 * La decision -aceptar o rechazar- se guarda en localStorage
 * (CLAVE_CONSENTIMIENTO), nunca en una cookie: seria raro usar una
 * cookie para recordar una decision sobre cookies. Si ya hay una
 * decision guardada de una visita anterior, el aviso ni se muestra: se
 * aplica sola -Analytics se activa sin preguntar de nuevo si ya se
 * habia aceptado-.
 *
 * /legal.html#cookies lleva sus propios botones -mismos id, distinto
 * sitio- para cambiar la decision despues: este mismo guion los conecta
 * si los encuentra en la pagina, para no duplicar la logica.
 */
declare(strict_types=1);
?>
(function () {
  'use strict';

  // No es un secreto: un id de medicion de Analytics es visible en el
  // codigo fuente de cualquier pagina que lo cargue, por diseño de
  // Google. No hace falta esconderlo ni sacarlo a config().
  var ID_MEDICION = 'G-EFKDVWY725';
  var CLAVE_CONSENTIMIENTO = 'bb-cookies-consentimiento';

  function leerConsentimiento() {
    try {
      return window.localStorage.getItem(CLAVE_CONSENTIMIENTO);
    } catch (e) {
      // Sin localStorage -modo privado, almacenamiento bloqueado- no se
      // puede recordar nada: se trata como si no hubiera decision.
      return null;
    }
  }

  function guardarConsentimiento(valor) {
    try {
      window.localStorage.setItem(CLAVE_CONSENTIMIENTO, valor);
    } catch (e) {
      // No se puede recordar la decision; se aplica igual para esta
      // visita, y ya esta.
    }
  }

  var activado = false;

  function activarAnalytics() {
    if (activado) { return; }
    activado = true;

    window.dataLayer = window.dataLayer || [];
    function gtag() { window.dataLayer.push(arguments); }
    window.gtag = gtag;
    gtag('js', new Date());
    gtag('config', ID_MEDICION);

    var guion = document.createElement('script');
    guion.async = true;
    guion.src = 'https://www.googletagmanager.com/gtag/js?id=' + ID_MEDICION;
    document.head.appendChild(guion);
  }

  function actualizarEstado(texto) {
    var estado = document.getElementById('cookies-estado');
    if (estado) {
      estado.hidden = false;
      estado.textContent = texto;
    }
  }

  function aceptar() {
    guardarConsentimiento('aceptado');
    activarAnalytics();
    var aviso = document.getElementById('aviso-cookies');
    if (aviso) { aviso.hidden = true; }
    actualizarEstado('Google Analytics: activado.');
  }

  function borrarCookiesAnalytics() {
    // Por si se habia aceptado antes y ahora se rechaza -desde
    // /legal.html#cookies, tipicamente-: Google Analytics ya habra
    // dejado sus cookies (_ga, _ga_<identificador>), y rechazar deberia
    // quitarlas, no solo dejar de instalar mas. Se buscan por prefijo
    // en vez de por nombre exacto porque _ga_<identificador> lleva un
    // sufijo que no se conoce de antemano.
    try {
      document.cookie.split(';').forEach(function (galleta) {
        var nombre = galleta.split('=')[0].trim();
        if (nombre.indexOf('_ga') === 0) {
          document.cookie = nombre + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/';
        }
      });
    } catch (e) {
      // Sin acceso a document.cookie no se puede hacer nada mas: la
      // decision de no volver a instalarlas ya queda guardada.
    }
  }

  function rechazar() {
    guardarConsentimiento('rechazado');
    borrarCookiesAnalytics();
    var aviso = document.getElementById('aviso-cookies');
    if (aviso) { aviso.hidden = true; }
    actualizarEstado('Google Analytics: desactivado.');
  }

  // El aviso de la cabecera: solo se muestra si todavia no hay
  // decision. Si ya la hay, se aplica sola sin preguntar de nuevo.
  var aviso = document.getElementById('aviso-cookies');
  var decision = leerConsentimiento();

  if (decision === 'aceptado') {
    activarAnalytics();
  } else if (decision !== 'rechazado' && aviso) {
    aviso.hidden = false;
  }

  var botonAceptar = document.getElementById('aviso-cookies-aceptar');
  var botonRechazar = document.getElementById('aviso-cookies-rechazar');

  if (botonAceptar) { botonAceptar.addEventListener('click', aceptar); }
  if (botonRechazar) { botonRechazar.addEventListener('click', rechazar); }

  // Los mismos botones, pero en /legal.html#cookies, para cambiar la
  // decision despues de la primera visita.
  var gestionarAceptar = document.getElementById('cookies-gestionar-aceptar');
  var gestionarRechazar = document.getElementById('cookies-gestionar-rechazar');

  if (gestionarAceptar) { gestionarAceptar.addEventListener('click', aceptar); }
  if (gestionarRechazar) { gestionarRechazar.addEventListener('click', rechazar); }

  if (gestionarAceptar || gestionarRechazar) {
    if (decision === 'aceptado') {
      actualizarEstado('Google Analytics: activado.');
    } else if (decision === 'rechazado') {
      actualizarEstado('Google Analytics: desactivado.');
    } else {
      actualizarEstado('Todavía no has decidido: Google Analytics sigue desactivado.');
    }
  }
})();
