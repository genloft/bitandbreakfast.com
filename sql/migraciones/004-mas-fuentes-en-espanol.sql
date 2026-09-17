-- =============================================================================
--  004 - mas fuentes en espanol
--
--  El radar publica solo lo que alguien cuenta en espanol, asi que la cantidad
--  de contenido depende de cuantos medios en espanol se rastrean. Con cinco,
--  una edicion se quedaba en siete bits.
--
--  Estas seis se han comprobado una a una: HTTP 200, RSS valido y entradas
--  dentro. Se anaden con ON DUPLICATE KEY para que la migracion se pueda
--  repetir sin duplicar nada -url_feed es unica-.
--
--  MuyComputerPro no es hotelera: es tecnologia B2B en espanol. Entra con peso
--  bajo a proposito, porque la puerta del sector ya se encarga de que solo
--  pase lo que hable de hoteles, y cuando pasa suele ser lo que a un director
--  de sistemas le importa de verdad -una brecha, una multa, un cambio de
--  reglamento-.
-- =============================================================================

INSERT INTO fuentes
  (nombre, url_feed, url_sitio, tipo, idioma, region, categoria_defecto, peso, activa, notas)
VALUES
  ('Instituto Tecnologico Hotelero', 'https://www.ithotelero.com/feed/', 'https://www.ithotelero.com/',
   'investigacion', 'es', 'es', 'tecnologia-general', 8, 1,
   'ITH: innovacion hotelera espanola, informes y proyectos'),

  ('Expreso', 'https://www.expreso.info/rss.xml', 'https://www.expreso.info/',
   'prensa', 'es', 'es', 'inversion-mercado', 6, 1,
   'Negocio turistico espanol, volumen alto'),

  ('Revista Gran Hotel', 'https://www.revistagranhotel.com/feed/', 'https://www.revistagranhotel.com/',
   'prensa', 'es', 'es', 'operaciones-iot', 6, 1,
   'Gestion y equipamiento hotelero en espanol'),

  ('Arecoa', 'https://www.arecoa.com/feed/', 'https://www.arecoa.com/',
   'prensa', 'es', 'es', 'inversion-mercado', 5, 1,
   'Hoteleria y turismo en Espana y Caribe'),

  ('Grupo Via', 'https://www.grupovia.net/feed/', 'https://www.grupovia.net/',
   'prensa', 'es', 'es', 'experiencia-huesped', 5, 1,
   'Arquitectura, interiorismo y negocio hotelero'),

  ('MuyComputerPro', 'https://www.muycomputerpro.com/feed/', 'https://www.muycomputerpro.com/',
   'prensa', 'es', 'es', 'ciberseguridad-cumplimiento', 4, 1,
   'Tecnologia B2B en espanol; la puerta del sector filtra lo que no es hotelero')
ON DUPLICATE KEY UPDATE activa = 1;

-- Y la edicion deja de tener veinte bits de tope: con mas fuentes en espanol
-- entra mas, y un radar que corta a los veinte tira lo que sobra en lugar de
-- publicarlo.
UPDATE ajustes SET valor = '32' WHERE clave = 'edicion_max_bits';
