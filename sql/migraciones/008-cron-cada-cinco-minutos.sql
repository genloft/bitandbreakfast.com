-- =============================================================================
--  008 - el cron pasa a cada cinco minutos
--
--  El catalogo de fuentes crece mucho, y la frecuencia del cron es lo que
--  decide cuantas veces al dia se recorre entero: con una pasada por hora y
--  quince fuentes por pasada, doscientas fuentes tardan medio dia en dar una
--  vuelta. A cinco minutos, hora y media.
--
--  Lo que hay que ajustar para que eso no se convierta en un problema:
--
--    - El aviso por correo. "Uno por pasada" eran veinticuatro al dia; ahora
--      serian doscientos ochenta y ocho, por el mismo buzon que manda el
--      boletin y que tiene limite por hora. Pasa a 'cambios' y, por encima,
--      un minimo de sesenta minutos entre avisos. Lo que ocurre mientras
--      tanto no se calla: se suma y sale en el siguiente.
--    - El lote de ingesta. Quince fuentes cada cinco minutos son mas de
--      cuatro mil lecturas al dia, de sobra para el catalogo que viene.
-- =============================================================================

INSERT INTO ajustes (clave, valor, descripcion) VALUES
  ('cron_aviso_minutos',    '60', 'Minutos minimos entre dos avisos del cron'),
  ('cron_aviso_ultimo',     '0',  'Cuando salio el ultimo aviso (marca unix)'),
  ('cron_aviso_nuevos',     '0',  'Noticias nuevas pendientes de contar en el proximo aviso'),
  ('cron_aviso_archivados', '0',  'Archivadas pendientes de contar en el proximo aviso'),
  ('cron_aviso_errores',    '0',  'Errores pendientes de contar en el proximo aviso'),
  ('mantenimiento_dia',     '',   'Ultimo dia en que corrio el mantenimiento')
ON DUPLICATE KEY UPDATE clave = clave;

UPDATE ajustes SET valor = 'cambios' WHERE clave = 'cron_aviso' AND valor = 'siempre';
