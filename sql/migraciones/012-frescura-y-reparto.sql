-- =============================================================================
--  012 - frescura y reparto
--
--  Dos cosas que se vieron en cuanto la portada dejo de ser una edicion y paso
--  a ser el dia entero, con treinta y siete noticias en vez de siete:
--
--    1. Una fuente nueva entra con su archivo completo. El radar lo descubre
--       hoy -tecnicamente cierto- y la portada abre con una nota de prensa de
--       julio. auto_dias_frescura pone el limite en treinta dias. El margen es
--       ancho porque hay medios con fechas raras y changelogs sin fecha: sin
--       fecha se deja pasar, que descartar por no saber es tirar lo que no se
--       ha podido mirar.
--
--    2. Tres noticias seguidas del mismo blog de fabricante no son un radar,
--       son su boletin. Cada una pasaba todas las puertas por separado; juntas
--       decian otra cosa. auto_max_por_medio reparte: cuatro al dia por medio.
--
--  Los dos son ajustes y no constantes porque el numero bueno depende del
--  catalogo, y el catalogo crece.
-- =============================================================================

INSERT INTO ajustes (clave, valor, descripcion) VALUES
  ('auto_dias_frescura', '30', 'Dias maximos desde que se publico la noticia'),
  ('auto_max_por_medio', '4',  'Bits como mucho por medio y dia')
ON DUPLICATE KEY UPDATE clave = clave;
