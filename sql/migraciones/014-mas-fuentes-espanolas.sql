-- =============================================================================
--  014 - trece fuentes mas, y siete de ellas en espanol
--
--  Tercera tanda, con el mismo baremo que las anteriores: robots.txt permite la
--  ruta, HTTP 200, feed valido con entradas dentro y comprobado el 17/09/2026.
--  Se quedan fuera Reportur -su robots.txt no deja leer a nadie- y las de
--  gastronomia, que no son de esto.
--
--  La mitad son espanolas a proposito. Mientras no haya traductor configurado,
--  lo que este en espanol es lo unico que puede llegar a la portada, y ahi el
--  catalogo seguia siendo corto: cuatro medios generalistas del sector y poco
--  mas. Estas anaden lo que faltaba, que son las casas de tecnologia espanolas
--  -Noray, MasterYield, BEONx, Avantio- y las instituciones que publican
--  informes y no notas de prensa: Segittur, Thinktur y Exceltur.
--
--  Las cuatro internacionales son de producto: PMS y RMS pequenos, que es
--  donde primero se cuenta que algo ha cambiado.
-- =============================================================================

INSERT INTO fuentes
  (nombre, url_feed, url_sitio, tipo, idioma, region, categoria_defecto, peso, activa, notas)
VALUES

  -- --- Espana: instituciones y casas de tecnologia -------------------------
  ('Segittur', 'https://www.segittur.es/feed/', 'https://www.segittur.es/',
   'estado', 'es', 'es', 'tecnologia-general', 7, 1,
   'Sociedad estatal de innovacion turistica; destinos inteligentes'),
  ('Thinktur', 'https://www.thinktur.org/feed/', 'https://www.thinktur.org/',
   'general', 'es', 'es', 'tecnologia-general', 7, 1,
   'Plataforma tecnologica del turismo espanol'),
  ('Exceltur', 'https://www.exceltur.org/feed/', 'https://www.exceltur.org/',
   'general', 'es', 'es', 'inversion-mercado', 6, 1,
   'Alianza para la excelencia turistica; informes de referencia'),
  ('Noray', 'https://www.noray.com/feed/', 'https://www.noray.com/',
   'changelog', 'es', 'es', 'pms-crs', 5, 1,
   'PMS espanol veterano'),
  ('MasterYield', 'https://www.masteryield.com/feed/', 'https://www.masteryield.com/',
   'changelog', 'es', 'es', 'revenue-rms', 5, 1,
   'RMS espanol'),
  ('BEONx', 'https://beonx.com/feed/', 'https://beonx.com/',
   'changelog', 'es', 'es', 'revenue-rms', 5, 1,
   'Revenue management espanol, con mucha IA en el discurso'),
  ('Avantio', 'https://www.avantio.com/feed/', 'https://www.avantio.com/',
   'changelog', 'es', 'es', 'distribucion-otas', 5, 1,
   'Alquiler vacacional gestionado, empresa espanola'),
  ('Restauracion News', 'https://restauracionnews.com/feed/', 'https://restauracionnews.com/',
   'prensa', 'es', 'es', 'operaciones-iot', 3, 1,
   'Restauracion; roza la hoteleria en punto de venta y cocina'),
  ('Mensajero Turistico', 'https://www.mensajeroweb.com.ar/rss', 'https://www.mensajeroweb.com.ar/',
   'prensa', 'es', 'global', 'distribucion-otas', 3, 1,
   'Turismo latinoamericano en espanol'),

  -- --- Producto internacional ----------------------------------------------
  ('RoomPriceGenie', 'https://www.roompricegenie.com/feed/', 'https://www.roompricegenie.com/',
   'changelog', 'en', 'global', 'revenue-rms', 5, 1,
   'RMS para hoteles pequenos'),
  ('Net Affinity', 'https://www.netaffinity.com/feed/', 'https://www.netaffinity.com/',
   'changelog', 'en', 'eu', 'distribucion-otas', 4, 1,
   'Venta directa y motor de reservas, Irlanda'),
  ('Newbook', 'https://www.newbook.cloud/feed/', 'https://www.newbook.cloud/',
   'changelog', 'en', 'global', 'pms-crs', 4, 1,
   'PMS para alojamientos y campings'),
  ('ThinkReservations', 'https://www.thinkreservations.com/resources/articles/feed.xml', 'https://www.thinkreservations.com/',
   'changelog', 'en', 'global', 'pms-crs', 4, 1,
   'PMS para alojamientos pequenos')
ON DUPLICATE KEY UPDATE activa = 1;
