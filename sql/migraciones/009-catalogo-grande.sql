-- =============================================================================
--  009 - el catalogo, mucho mas grande
--
--  Las treinta se han comprobado una a una el 17/09/2026, con el mismo agente
--  con el que rastrea el sitio: robots.txt permite la ruta, HTTP 200, feed
--  valido y con entradas dentro. Las que no pasaron la prueba no estan aqui
--  -entre ellas el Instituto Tecnologico Hotelero, que prohibe /*feed/ en su
--  robots.txt, y Preferente, que solo abre la puerta a Google y a Facebook-.
--
--  El peso no es la calidad del medio: es cuanta confianza merece para
--  publicar sin que nadie lo mire. La prensa generalista entra con peso bajo a
--  proposito -tres- porque publica mucho y de hoteleria casi nada: sirve para
--  cazar la multa, la brecha o la compra que los medios del sector tardan un
--  dia en contar, y para poco mas. Las puertas del modo automatico se encargan
--  del resto.
--
--  Los blogs de fabricante -tipo 'changelog'- son la otra mitad de esto. No
--  son prensa y no pretenden serlo, pero son donde primero se cuenta que un
--  PMS ha cambiado algo, y eso es exactamente lo que este sitio agrega.
-- =============================================================================

INSERT INTO fuentes
  (nombre, url_feed, url_sitio, tipo, idioma, region, categoria_defecto, peso, activa, notas)
VALUES

  -- --- En espanol ----------------------------------------------------------
  ('Dingus', 'https://www.dingus.es/feed/', 'https://www.dingus.es/',
   'changelog', 'es', 'es', 'pms-crs', 5, 1,
   'Tecnologia hotelera espanola: motor, channel y PMS'),
  ('Mirai', 'https://www.mirai.com/feed/', 'https://www.mirai.com/',
   'changelog', 'es', 'es', 'distribucion-otas', 6, 1,
   'Venta directa; escriben largo y con datos'),
  ('CEHAT', 'https://cehat.com/feed/', 'https://cehat.com/',
   'general', 'es', 'es', 'inversion-mercado', 6, 1,
   'Patronal hotelera espanola'),
  ('Agenttravel', 'https://www.agenttravel.es/modulos/agenttravel_rss.php', 'https://www.agenttravel.es/',
   'prensa', 'es', 'es', 'distribucion-otas', 4, 1,
   'Diario de turismo profesional'),
  ('INCIBE', 'https://www.incibe.es/rss.xml', 'https://www.incibe.es/',
   'estado', 'es', 'es', 'ciberseguridad-cumplimiento', 5, 1,
   'Ciberseguridad institucional en espanol'),
  ('Xataka', 'https://www.xataka.com/atom.xml', 'https://www.xataka.com/',
   'prensa', 'es', 'es', 'tecnologia-general', 3, 1,
   'Tecnologia generalista; peso bajo a proposito'),
  ('Genbeta', 'https://www.genbeta.com/atom.xml', 'https://www.genbeta.com/',
   'prensa', 'es', 'es', 'tecnologia-general', 3, 1,
   'Software y servicios; peso bajo a proposito'),
  ('Cinco Dias', 'https://feeds.elpais.com/mrss-s/pages/ep/site/cincodias.elpais.com/portada', 'https://cincodias.elpais.com/',
   'prensa', 'es', 'es', 'inversion-mercado', 3, 1,
   'Economia; caza compras y multas antes que el sector'),
  ('Europa Press Economia', 'https://www.europapress.es/rss/rss.aspx?ch=00269', 'https://www.europapress.es/',
   'prensa', 'es', 'es', 'inversion-mercado', 3, 1,
   'Agencia; volumen alto y senal baja, pero rapida'),
  ('Expansion', 'https://e01-expansion.uecdn.es/rss/economia.xml', 'https://www.expansion.com/',
   'prensa', 'es', 'es', 'inversion-mercado', 3, 1,
   'Economia; util para operaciones y resultados'),

  -- --- Fabricantes y plataformas -------------------------------------------
  ('Amadeus Hospitality', 'https://www.amadeus-hospitality.com/feed/', 'https://www.amadeus-hospitality.com/',
   'changelog', 'en', 'global', 'pms-crs', 6, 1,
   'Uno de los grandes del CRS'),
  ('IDeaS', 'https://ideas.com/feed/', 'https://ideas.com/',
   'changelog', 'en', 'global', 'revenue-rms', 6, 1,
   'Revenue management; referencia del sector'),
  ('D-EDGE', 'https://www.d-edge.com/feed/', 'https://www.d-edge.com/',
   'changelog', 'en', 'eu', 'distribucion-otas', 5, 1,
   'Distribucion hotelera europea'),
  ('Operto', 'https://operto.com/feed/', 'https://operto.com/',
   'changelog', 'en', 'global', 'operaciones-iot', 5, 1,
   'Operacion y acceso sin contacto'),
  ('TrustYou', 'https://www.trustyou.com/feed/', 'https://www.trustyou.com/',
   'changelog', 'en', 'global', 'experiencia-huesped', 5, 1,
   'Reputacion y encuestas'),
  ('RoomRaccoon', 'https://roomraccoon.com/feed/', 'https://roomraccoon.com/',
   'changelog', 'en', 'eu', 'pms-crs', 4, 1,
   'PMS para hoteles pequenos'),
  ('Little Hotelier', 'https://www.littlehotelier.com/feed/', 'https://www.littlehotelier.com/',
   'changelog', 'en', 'global', 'pms-crs', 4, 1,
   'PMS de SiteMinder para alojamientos pequenos'),
  ('Asksuite', 'https://www.asksuite.com/feed/', 'https://www.asksuite.com/',
   'changelog', 'en', 'global', 'ia-aplicada', 4, 1,
   'Agentes conversacionales para hoteles'),

  -- --- Prensa del sector ---------------------------------------------------
  ('Hospitality Technology', 'https://hospitalitytech.com/rss', 'https://hospitalitytech.com/',
   'prensa', 'en', 'global', 'tecnologia-general', 7, 1,
   'Monotematica de tecnologia en hosteleria'),
  ('Hotel Online', 'https://www.hotel-online.com/news/feed', 'https://www.hotel-online.com/',
   'prensa', 'en', 'global', 'tecnologia-general', 5, 1,
   'Agregador veterano del sector'),
  ('Rental Scale-Up', 'https://www.rentalscaleup.com/feed/', 'https://www.rentalscaleup.com/',
   'prensa', 'en', 'global', 'distribucion-otas', 4, 1,
   'Alquiler vacacional y canales'),

  -- --- Seguridad -----------------------------------------------------------
  ('The Hacker News', 'https://thehackernews.com/rss.xml', 'https://thehackernews.com/',
   'prensa', 'en', 'global', 'ciberseguridad-cumplimiento', 4, 1,
   'Volumen alto; lo filtra la puerta del sector'),
  ('Dark Reading', 'https://www.darkreading.com/rss.xml', 'https://www.darkreading.com/',
   'prensa', 'en', 'global', 'ciberseguridad-cumplimiento', 4, 1,
   'Seguridad corporativa'),
  ('SecurityWeek', 'https://www.securityweek.com/feed/', 'https://www.securityweek.com/',
   'prensa', 'en', 'global', 'ciberseguridad-cumplimiento', 4, 1,
   'Brechas y vulnerabilidades'),
  ('Help Net Security', 'https://www.helpnetsecurity.com/feed/', 'https://www.helpnetsecurity.com/',
   'prensa', 'en', 'global', 'ciberseguridad-cumplimiento', 4, 1,
   'Seguridad, con mucho producto'),
  ('Infosecurity Magazine', 'https://www.infosecurity-magazine.com/rss/news/', 'https://www.infosecurity-magazine.com/',
   'prensa', 'en', 'global', 'ciberseguridad-cumplimiento', 4, 1,
   'Seguridad con mirada europea'),
  ('NCSC Reino Unido', 'https://www.ncsc.gov.uk/api/1/services/v1/news-rss-feed.xml', 'https://www.ncsc.gov.uk/',
   'estado', 'en', 'eu', 'ciberseguridad-cumplimiento', 5, 1,
   'Avisos oficiales britanicos'),

  -- --- Tecnologia generalista ----------------------------------------------
  ('WIRED', 'https://www.wired.com/feed/rss', 'https://www.wired.com/',
   'prensa', 'en', 'global', 'tecnologia-general', 3, 1,
   'Peso bajo: casi nada es hotelero'),
  ('ZDNET', 'https://www.zdnet.com/news/rss.xml', 'https://www.zdnet.com/',
   'prensa', 'en', 'global', 'tecnologia-general', 3, 1,
   'Peso bajo: casi nada es hotelero'),
  ('Ars Technica', 'https://feeds.arstechnica.com/arstechnica/technology-lab', 'https://arstechnica.com/',
   'prensa', 'en', 'global', 'tecnologia-general', 3, 1,
   'Seccion de sistemas; peso bajo a proposito')
ON DUPLICATE KEY UPDATE activa = 1;
