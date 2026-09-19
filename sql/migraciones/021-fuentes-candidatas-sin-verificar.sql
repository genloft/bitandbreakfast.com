-- =============================================================================
--  021 - fuentes candidatas, sin verificar a mano
--
--  Distinto criterio del de las migraciones 009 y 014: aquellas se escribieron
--  tras comprobar cada feed uno a uno -robots.txt, HTTP 200, entradas de
--  verdad-. Esta vez el entorno donde se prepara el cambio no tiene salida de
--  red a sitios externos -ni siquiera a fuentes ya verificadas del catalogo-,
--  asi que ninguna de estas se ha podido comprobar de la misma forma.
--
--  Entran igual, activas, porque el propio sistema ya sabe distinguir una
--  fuente que no responde de una que si: si el feed no existe o el dominio ha
--  cambiado, se duerme sola tras los primeros fallos (dormida_hasta, migracion
--  006) y aparece marcada en panel/index.php?p=fuentes con el motivo exacto.
--  Limpiarlas -desactivar o corregir la URL- es cosa de revisar esa pantalla
--  dentro de unos dias, no de creer a ciegas esta lista.
--
--  El hueco que cubren: hasta ahora el catalogo era casi todo prensa
--  anglosajona (EEUU, Reino Unido, Irlanda, Australia) mas cinco fuentes en
--  espanol. Aqui entran: la prensa internacional de referencia que faltaba
--  (PhocusWire, el gran rival de Skift, no estaba), changelogs de proveedores
--  grandes que el catalogo no citaba (Mews, Amadeus, Sabre, RateGain, Apaleo),
--  Asia-Pacifico (un solo hueco hasta ahora) y una primera entrada en
--  portugues -Brasil-, que exige que web_idiomas() en lib/web.php reconozca
--  'pt' ademas de 'es' y 'en'; sin eso, un bit traducido de aqui hubiera
--  enseñado "Titular en pt" en vez de "Titular en portugués".
-- =============================================================================

INSERT INTO fuentes
  (nombre, url_feed, url_sitio, tipo, idioma, region, categoria_defecto, peso, activa, notas, fecha_alta)
VALUES

  -- --- Prensa internacional de referencia, hueco grande -------------------
  ('PhocusWire', 'https://www.phocuswire.com/feed', 'https://www.phocuswire.com/',
   'prensa', 'en', 'global', 'tecnologia-general', 8, 1,
   'Sin verificar: la principal referencia de tecnologia de viajes junto a Skift, y no estaba en el catalogo. URL de feed sin confirmar.', UTC_DATE()),
  ('Hospitality Technology', 'https://hospitalitytech.com/feed', 'https://hospitalitytech.com/',
   'prensa', 'en', 'global', 'tecnologia-general', 7, 1,
   'Sin verificar. Revista dedicada a tecnologia de hoteles y restaurantes.', UTC_DATE()),
  ('Hospitality Upgrade', 'https://www.hospitalityupgrade.com/feed', 'https://www.hospitalityupgrade.com/',
   'prensa', 'en', 'global', 'tecnologia-general', 6, 1,
   'Sin verificar. Revista veterana centrada en directores de sistemas de hotel.', UTC_DATE()),

  -- --- Changelogs de proveedores grandes que faltaban ----------------------
  ('Mews', 'https://www.mews.com/en/blog/rss.xml', 'https://www.mews.com/',
   'changelog', 'en', 'global', 'pms-crs', 7, 1,
   'Sin verificar de primera mano, pero la URL del feed viene confirmada por la propia documentacion publica de Mews. PMS cloud de referencia, citado a menudo en el sector y ausente del catalogo hasta ahora.', UTC_DATE()),
  ('RateGain', 'https://rategain.com/feed', 'https://rategain.com/',
   'changelog', 'en', 'global', 'distribucion-otas', 6, 1,
   'Sin verificar de primera mano, URL de feed confirmada por su propia documentacion. Ya citado como fuente en /estadisticas.html; no estaba en el catalogo de noticias.', UTC_DATE()),
  ('Amadeus Hospitality', 'https://www.amadeus-hospitality.com/feed', 'https://www.amadeus-hospitality.com/',
   'changelog', 'en', 'global', 'distribucion-otas', 5, 1,
   'Sin verificar. Distribucion y tecnologia para cadenas grandes.', UTC_DATE()),
  ('Sabre Hospitality', 'https://www.sabrehospitality.com/feed', 'https://www.sabrehospitality.com/',
   'changelog', 'en', 'global', 'distribucion-otas', 5, 1,
   'Sin verificar. GDS y tecnologia de reservas.', UTC_DATE()),
  ('Apaleo', 'https://apaleo.com/blog/feed', 'https://apaleo.com/',
   'changelog', 'en', 'eu', 'pms-crs', 4, 1,
   'Sin verificar. PMS europeo con API abierta, en expansion por Francia y DACH.', UTC_DATE()),

  -- --- Asia-Pacifico: un solo hueco hasta ahora -----------------------------
  ('Travel News Asia', 'https://www.travelnewsasia.com/travelnews.xml', 'https://www.travelnewsasia.com/',
   'prensa', 'en', 'global', 'tecnologia-general', 4, 1,
   'Sin verificar de primera mano, URL de feed confirmada por la propia pagina de ayuda del sitio. Cubre Singapur, Japon e India, region con un solo hueco (Hotel Management Australia).', UTC_DATE()),

  -- --- Primera fuente en portugues (Brasil) --------------------------------
  ('Revista Hoteis', 'https://revistahoteis.com.br/feed', 'https://revistahoteis.com.br/',
   'prensa', 'pt', 'global', 'tecnologia-general', 4, 1,
   'Sin verificar. Primera fuente en portugues del catalogo -requiere que web_idiomas() reconozca "pt"-. Mercado hotelero brasileno, sin cobertura hasta ahora.', UTC_DATE()),

  -- --- Latinoamerica, en ingles pero centrada en la region -----------------
  ('Latinvex', 'https://latinvex.com/latinvex-rss', 'https://latinvex.com/',
   'general', 'en', 'global', 'inversion-mercado', 3, 1,
   'Sin verificar de primera mano, URL de feed confirmada por su propia pagina. En ingles pero centrada en negocio latinoamericano; peso bajo porque no es especializada en hoteles.', UTC_DATE())

ON DUPLICATE KEY UPDATE activa = 1;
