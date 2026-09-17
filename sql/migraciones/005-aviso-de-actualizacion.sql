-- =============================================================================
--  005 - aviso de actualizacion
--
--  Dos cosas que se piden a la vez y que necesitan lo mismo: saber que ha
--  cambiado desde la ultima vez.
--
--    - Una barra arriba de la web: cuando se actualizo, cuantas noticias
--      entraron y cuantas pasaron al archivo.
--    - Un correo al terminar cada pasada del cron con lo que ha hecho.
--
--  Los tres primeros ajustes son la memoria de la ultima generacion. Sin ellos
--  no hay "desde la ultima vez" que valga: habria que guardar un historico
--  para contestar una pregunta que se contesta con tres numeros.
--
--  cron_aviso viene en 'siempre' porque es lo que se pidio. Conviene saber lo
--  que significa: el cron corre cada hora, asi que son veinticuatro correos al
--  dia, y salen por el mismo buzon que el boletin, que tiene limite por hora.
--  Con 'cambios' solo escribe cuando ha pasado algo, que en la practica es una
--  o dos veces al dia.
-- =============================================================================

INSERT INTO ajustes (clave, valor, descripcion) VALUES
  ('web_actualizada_en',  '',  'Cuando se genero la web la ultima vez'),
  ('web_edicion_frente',  '0', 'Numero de la edicion que estaba en portada entonces'),
  ('web_bits_frente',     '0', 'Cuantos bits tenia esa portada'),
  ('cron_aviso',          'siempre', 'Aviso por correo del cron: siempre | cambios | no'),
  ('cron_aviso_correo',   '',  'A quien se le manda el aviso; vacio = al propio buzon')
ON DUPLICATE KEY UPDATE clave = clave;
