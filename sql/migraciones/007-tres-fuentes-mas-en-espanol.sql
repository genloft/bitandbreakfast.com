-- =============================================================================
--  007 - tres fuentes mas en espanol
--
--  Al enseñar /salud.php el motivo real de cada fallo se vio que tres fuentes
--  no fallaban: su robots.txt no deja leer el feed a nadie que no sea Google o
--  Facebook. Preferente es una de ellas, y era de las cuatro en espanol. Como
--  aqui solo se publica lo que alguien cuenta en espanol, perderla se nota en
--  la portada.
--
--  Saltarse el robots.txt no es una opcion, asi que la salida es buscar mas
--  medios que si dejen. Estos tres se han comprobado uno a uno el 17/09/2026:
--  robots.txt permite la ruta del feed, HTTP 200, RSS valido con entradas
--  dentro y contenido en espanol.
--
--    - Nexotur: negocio turistico espanol, diario. Disallow vacio, o sea,
--      puerta abierta.
--    - Revista Gran Hotel: hoteleria espanola, mas de gestion que de producto.
--    - INCIBE-CERT: los avisos oficiales de seguridad del Estado, en espanol.
--      Es el equivalente de CISA, que ya estaba, con la diferencia de que este
--      se publica en el idioma en el que se puede publicar aqui. Entra con
--      peso medio porque la mayoria de sus avisos no son de hoteleria: de eso
--      ya se encarga la puerta del sector.
--
--  Con ON DUPLICATE KEY, como las anteriores: url_feed es unica y la
--  migracion se puede repetir sin duplicar nada.
-- =============================================================================

INSERT INTO fuentes
  (nombre, url_feed, url_sitio, tipo, idioma, region, categoria_defecto, peso, activa, notas)
VALUES
  ('Nexotur', 'https://www.nexotur.com/rss/', 'https://www.nexotur.com/',
   'prensa', 'es', 'es', 'inversion-mercado', 5, 1,
   'Negocio turistico espanol, diario'),

  ('Revista Gran Hotel', 'https://www.revistagranhotel.com/feed/', 'https://www.revistagranhotel.com/',
   'prensa', 'es', 'es', 'operaciones-iot', 5, 1,
   'Hoteleria espanola, gestion y operacion'),

  ('INCIBE-CERT', 'https://www.incibe.es/incibe-cert/alerta-temprana/avisos/feed', 'https://www.incibe.es/',
   'estado', 'es', 'es', 'ciberseguridad-cumplimiento', 6, 1,
   'Avisos oficiales de seguridad en espanol; cruzar con el catalogo de proveedores')
ON DUPLICATE KEY UPDATE activa = 1;
