-- =============================================================================
--  Bit & Breakfast - semilla del catalogo de fuentes
--
--  Fuentes verificadas una a una el 12 de septiembre de 2026: todas
--  devolvieron HTTP 200, raiz RSS o Atom valida y al menos una entrada.
--  Mayoria estadounidense y anglosajona; cinco fuentes en espanol para
--  sostener la cuota ES/EU del 30 % que vigila el montaje de la edicion.
--
--  Ejecutar despues de sql/esquema.sql.
-- =============================================================================

SET NAMES utf8mb4;

INSERT INTO fuentes
  (nombre, url_feed, url_sitio, tipo, idioma, region, categoria_defecto, peso, activa, notas)
VALUES
  ('Skift', 'https://skift.com/feed/', 'https://skift.com/', 'prensa', 'en', 'global', 'distribucion-otas', 9, 1, 'Referencia del sector viajes. Mucho volumen, conviene filtrar duro'),
  ('Hotel Dive', 'https://www.hoteldive.com/feeds/news/', 'https://www.hoteldive.com/', 'prensa', 'en', 'global', 'tecnologia-general', 9, 1, 'Industry Dive. Redaccion sobria y poco publirreportaje'),
  ('Hotel Technology News', 'https://hoteltechnologynews.com/feed/', 'https://hoteltechnologynews.com/', 'prensa', 'en', 'global', 'tecnologia-general', 8, 1, 'Monotematica de tecnologia hotelera'),
  ('Hospitality Net', 'https://www.hospitalitynet.org/rss/news.xml', 'https://www.hospitalitynet.org/', 'prensa', 'en', 'global', 'tecnologia-general', 8, 1, 'Mezcla noticia y nota de prensa, vigilar filtro anti-publirreportaje'),
  ('Hotel Management', 'https://www.hotelmanagement.net/rss.xml', 'https://www.hotelmanagement.net/', 'prensa', 'en', 'global', 'tecnologia-general', 8, 1, 'Questex. Cobertura operativa y tecnologica de EEUU'),
  ('Hotel Business', 'https://hotelbusiness.com/feed/', 'https://hotelbusiness.com/', 'prensa', 'en', 'global', 'inversion-mercado', 7, 1, 'Negocio hotelero estadounidense'),
  ('LODGING Magazine', 'https://lodgingmagazine.com/feed/', 'https://lodgingmagazine.com/', 'prensa', 'en', 'global', 'tecnologia-general', 7, 1, 'Revista oficial de la AHLA'),
  ('Hotel News Resource', 'https://www.hotelnewsresource.com/feeds/latest.xml', 'https://www.hotelnewsresource.com/', 'prensa', 'en', 'global', 'tecnologia-general', 6, 1, 'Agregador veterano, densidad alta de notas de prensa'),
  ('Hotel Executive', 'https://www.hotelexecutive.com/rss/', 'https://www.hotelexecutive.com/', 'prensa', 'en', 'global', 'operaciones-iot', 6, 1, 'Hotel Business Review. Articulos de fondo'),
  ('Hotel Interactive', 'https://hotelinteractive.com/feed/', 'https://hotelinteractive.com/', 'prensa', 'en', 'global', 'tecnologia-general', 5, 1, 'Prensa hotelera estadounidense'),
  ('eHotelier Insights', 'https://insights.ehotelier.com/feed/', 'https://insights.ehotelier.com/', 'prensa', 'en', 'global', 'tecnologia-general', 6, 1, 'Australiana con alcance global'),
  ('Hotel Management Network', 'https://www.hotelmanagement-network.com/feed/', 'https://www.hotelmanagement-network.com/', 'prensa', 'en', 'global', 'tecnologia-general', 6, 1, 'GlobalData. Analisis de mercado y tecnologia'),
  ('Hospitality Design', 'https://hospitalitydesign.com/feed/', 'https://hospitalitydesign.com/', 'prensa', 'en', 'global', 'experiencia-huesped', 5, 1, 'Exige User-Agent con prefijo Mozilla o responde 403'),
  ('Boutique Hotel News', 'https://boutiquehotelnews.com/feed/', 'https://boutiquehotelnews.com/', 'prensa', 'en', 'eu', 'tecnologia-general', 5, 1, 'Independientes y boutique en Reino Unido y Europa'),
  ('Hotel Owner', 'https://www.hotelowner.co.uk/feed/', 'https://www.hotelowner.co.uk/', 'prensa', 'en', 'eu', 'operaciones-iot', 5, 1, 'Perspectiva del pequeno hotelero britanico'),
  ('Hospitality Ireland', 'https://hospitalityireland.com/feed', 'https://hospitalityireland.com/', 'prensa', 'en', 'eu', 'tecnologia-general', 4, 1, 'Irlanda. Volumen alto y senal media'),
  ('Hotel Management Australia', 'https://www.hotelmanagement.com.au/feed/', 'https://www.hotelmanagement.com.au/', 'prensa', 'en', 'global', 'tecnologia-general', 5, 1, 'Asia-Pacifico'),
  ('Hotel Speak', 'https://www.hotelspeak.com/feed/', 'https://www.hotelspeak.com/', 'prensa', 'en', 'eu', 'distribucion-otas', 5, 1, 'Marketing y distribucion directa'),
  ('Revfine', 'https://www.revfine.com/feed/', 'https://www.revfine.com/', 'prensa', 'en', 'global', 'revenue-rms', 5, 1, 'Divulgacion de revenue management, contenido perenne'),
  ('Skift Meetings', 'https://skift.com/meetings/feed/', 'https://skift.com/meetings/', 'prensa', 'en', 'global', 'experiencia-huesped', 5, 1, 'Eventos y MICE'),
  ('Travel Daily News International', 'https://www.traveldailynews.com/feed/', 'https://www.traveldailynews.com/', 'prensa', 'en', 'global', 'distribucion-otas', 5, 1, 'Mucha nota de prensa, peso bajo a proposito'),
  ('Short Term Rentalz', 'https://shorttermrentalz.com/feed/', 'https://shorttermrentalz.com/', 'prensa', 'en', 'global', 'distribucion-otas', 5, 1, 'Alquiler vacacional y alojamiento alternativo'),
  ('Restaurant Dive', 'https://www.restaurantdive.com/feeds/news/', 'https://www.restaurantdive.com/', 'prensa', 'en', 'global', 'operaciones-iot', 5, 1, 'Tecnologia de restauracion aplicable a hoteles'),
  ('Nation''s Restaurant News', 'https://www.nrn.com/rss.xml', 'https://www.nrn.com/', 'prensa', 'en', 'global', 'operaciones-iot', 4, 1, 'Restauracion estadounidense, senal indirecta'),
  ('HSMAI Americas', 'https://americas.hsmai.org/feed/', 'https://americas.hsmai.org/', 'investigacion', 'en', 'global', 'revenue-rms', 5, 1, 'Asociacion de marketing y revenue management'),
  ('EHL Hospitality Insights', 'https://hospitalityinsights.ehl.edu/rss.xml', 'https://hospitalityinsights.ehl.edu/', 'investigacion', 'en', 'eu', 'experiencia-huesped', 5, 1, 'Escuela hotelera de Lausana'),
  ('AHLA', 'https://www.ahla.com/rss.xml', 'https://www.ahla.com/', 'normativa', 'en', 'global', 'inversion-mercado', 6, 1, 'Patronal hotelera estadounidense, regulacion y posicionamiento'),
  ('Hilton Stories', 'https://stories.hilton.com/feed', 'https://stories.hilton.com/', 'prensa', 'en', 'global', 'experiencia-huesped', 5, 1, 'Sala de prensa corporativa, penalizada si va sola en el racimo'),
  ('Hyatt Newsroom', 'https://newsroom.hyatt.com/news-releases?pagetemplate=rss', 'https://newsroom.hyatt.com/', 'prensa', 'en', 'global', 'experiencia-huesped', 5, 1, 'Sala de prensa corporativa'),
  ('Cloudbeds', 'https://www.cloudbeds.com/feed/', 'https://www.cloudbeds.com/', 'changelog', 'en', 'global', 'pms-crs', 6, 1, 'PMS en la nube, producto y estudios de mercado'),
  ('SiteMinder', 'https://www.siteminder.com/r/feed/', 'https://www.siteminder.com/', 'changelog', 'en', 'global', 'distribucion-otas', 6, 1, 'Channel manager lider'),
  ('Duetto', 'https://www.duettocloud.com/library/rss.xml', 'https://www.duettocloud.com/', 'changelog', 'en', 'global', 'revenue-rms', 6, 1, 'RMS, referencia en precios dinamicos'),
  ('Stayntouch', 'https://www.stayntouch.com/feed/', 'https://www.stayntouch.com/', 'changelog', 'en', 'global', 'pms-crs', 5, 1, 'PMS movil'),
  ('Canary Technologies', 'https://www.canarytechnologies.com/post/rss.xml', 'https://www.canarytechnologies.com/', 'changelog', 'en', 'global', 'experiencia-huesped', 5, 1, 'Check-in digital y pagos sin contacto'),
  ('Actabl', 'https://actabl.com/feed/', 'https://actabl.com/', 'changelog', 'en', 'global', 'operaciones-iot', 5, 1, 'Grupo de Hotel Effectiveness, ProfitSword y Transcendent'),
  ('Infor Hospitality', 'https://www.infor.com/rss/news', 'https://www.infor.com/industries/hospitality', 'changelog', 'en', 'global', 'pms-crs', 5, 1, 'Exige User-Agent con prefijo Mozilla o responde 403'),
  ('Stripe', 'https://stripe.com/blog/feed.rss', 'https://stripe.com/blog', 'changelog', 'en', 'global', 'pagos-fraude', 6, 1, 'Pagos, cambios que afectan a pasarelas hoteleras'),
  ('Google Cloud', 'https://cloudblog.withgoogle.com/rss/', 'https://cloud.google.com/blog/', 'changelog', 'en', 'global', 'ia-aplicada', 5, 1, 'IA aplicada, volumen alto, filtrar por diccionario'),
  ('AWS Novedades', 'https://aws.amazon.com/about-aws/whats-new/recent/feed/', 'https://aws.amazon.com/new/', 'changelog', 'en', 'global', 'tecnologia-general', 4, 1, 'Cien entradas por feed, peso bajo y filtro estricto'),
  ('TechCrunch IA', 'https://techcrunch.com/category/artificial-intelligence/feed/', 'https://techcrunch.com/', 'prensa', 'en', 'global', 'ia-aplicada', 6, 1, 'IA mainstream, util para el angulo tecnologico'),
  ('TechCrunch Viajes', 'https://techcrunch.com/tag/travel/feed/', 'https://techcrunch.com/', 'prensa', 'en', 'global', 'inversion-mercado', 6, 1, 'Rondas y lanzamientos del vertical viajes'),
  ('Crunchbase News', 'https://news.crunchbase.com/feed/', 'https://news.crunchbase.com/', 'financiacion', 'en', 'global', 'inversion-mercado', 6, 1, 'Financiacion y operaciones corporativas'),
  ('CIO Dive', 'https://www.ciodive.com/feeds/news/', 'https://www.ciodive.com/', 'prensa', 'en', 'global', 'tecnologia-general', 6, 1, 'Angulo del director de sistemas'),
  ('Techmeme', 'https://www.techmeme.com/feed.xml', 'https://www.techmeme.com/', 'prensa', 'en', 'global', 'tecnologia-general', 5, 1, 'Agregador de titulares, util para confirmar racimos'),
  ('The Verge', 'https://www.theverge.com/rss/index.xml', 'https://www.theverge.com/', 'prensa', 'en', 'global', 'tecnologia-general', 4, 1, 'Tecnologia generalista, peso bajo'),
  ('PYMNTS', 'https://www.pymnts.com/feed/', 'https://www.pymnts.com/', 'prensa', 'en', 'global', 'pagos-fraude', 6, 1, 'Pagos y fraude'),
  ('Payments Dive', 'https://www.paymentsdive.com/feeds/news/', 'https://www.paymentsdive.com/', 'prensa', 'en', 'global', 'pagos-fraude', 6, 1, 'Industry Dive, pagos en EEUU'),
  ('Finextra', 'https://www.finextra.com/rss/headlines.aspx', 'https://www.finextra.com/', 'prensa', 'en', 'global', 'pagos-fraude', 5, 1, 'Tecnologia financiera, volumen alto'),
  ('PCI Security Standards Council', 'https://blog.pcisecuritystandards.org/rss.xml', 'https://blog.pcisecuritystandards.org/', 'normativa', 'en', 'global', 'pagos-fraude', 7, 1, 'Fuente primaria de PCI DSS'),
  ('CISA Avisos', 'https://www.cisa.gov/cybersecurity-advisories/all.xml', 'https://www.cisa.gov/', 'estado', 'en', 'global', 'ciberseguridad-cumplimiento', 8, 1, 'Avisos oficiales, cruzar con proveedores del catalogo'),
  ('Cybersecurity Dive', 'https://www.cybersecuritydive.com/feeds/news/', 'https://www.cybersecuritydive.com/', 'prensa', 'en', 'global', 'ciberseguridad-cumplimiento', 7, 1, 'Brechas e incidentes corporativos'),
  ('BleepingComputer', 'https://www.bleepingcomputer.com/feed/', 'https://www.bleepingcomputer.com/', 'prensa', 'en', 'global', 'ciberseguridad-cumplimiento', 7, 1, 'Suele adelantar brechas de cadenas hoteleras'),
  ('Krebs on Security', 'https://krebsonsecurity.com/feed/', 'https://krebsonsecurity.com/', 'prensa', 'en', 'global', 'ciberseguridad-cumplimiento', 7, 1, 'Investigacion, poco volumen y mucha senal'),
  ('Comite Europeo de Proteccion de Datos', 'https://www.edpb.europa.eu/rss.xml', 'https://www.edpb.europa.eu/', 'normativa', 'en', 'eu', 'ciberseguridad-cumplimiento', 7, 1, 'Fuente primaria del RGPD'),
  ('Hosteltur', 'https://www.hosteltur.com/rss/', 'https://www.hosteltur.com/', 'prensa', 'es', 'es', 'tecnologia-general', 8, 1, 'Referencia en Espana, clave para la cuota ES/EU'),
  ('TecnoHotel', 'https://tecnohotelnews.com/feed/', 'https://tecnohotelnews.com/', 'prensa', 'es', 'es', 'tecnologia-general', 8, 1, 'Monotematica de tecnologia hotelera en espanol'),
  ('Smart Travel News', 'https://www.smarttravel.news/feed/', 'https://www.smarttravel.news/', 'prensa', 'es', 'es', 'distribucion-otas', 6, 1, 'Distribucion y tecnologia, feed muy largo'),
  ('Preferente', 'https://www.preferente.com/feed', 'https://www.preferente.com/', 'prensa', 'es', 'es', 'inversion-mercado', 5, 1, 'Negocio hotelero espanol, tono a veces sensacionalista'),
  ('Nexotur', 'https://www.nexotur.com/rss/', 'https://www.nexotur.com/', 'prensa', 'es', 'es', 'distribucion-otas', 4, 1, 'Agencias y distribucion en Espana');

-- 59 fuentes insertadas.

-- La fuente del boletin de vulnerabilidades KEV: no es un feed RSS -es un
-- JSON con su propia forma, y la alimenta cron/kev.php, no el rastreador
-- generico-, asi que necesita su columna "gestion" propia. Ver
-- sql/migraciones/023-fuente-cisa-kev.sql para la instalacion que ya corria.
INSERT INTO fuentes
  (nombre, url_feed, url_sitio, tipo, gestion, idioma, region,
   categoria_defecto, peso, activa, notas, fecha_alta)
VALUES
  ('CISA KEV', 'https://www.cisa.gov/sites/default/files/feeds/known_exploited_vulnerabilities.json',
   'https://www.cisa.gov/known-exploited-vulnerabilities-catalog', 'estado', 'manual', 'en', 'global',
   'ciberseguridad', 8, 1,
   'No la lee el rastreador de feeds: la alimenta cron/kev.php, que cruza cada entrada nueva del catalogo contra proveedores y proveedor_alias.',
   UTC_DATE());
