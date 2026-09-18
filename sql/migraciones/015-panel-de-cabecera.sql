-- =============================================================================
--  015 - el panel de cifras de la cabecera
--
--  A la derecha del logo van, a partir de ahora, las cuentas del radar: cuando
--  fue la ultima actualizacion, cuando sera la siguiente y cuanto ha crecido
--  cada cosa -noticias, medios, temas- desde la vez anterior.
--
--  Para el "cuanto ha crecido" hacen falta dos numeros mas de memoria, los
--  mismos que ya se guardaban para las noticias. Y para el "cuando sera la
--  siguiente", la cadencia del cron: no vive en ningun sitio consultable -esta
--  en el panel del alojamiento- asi que el propio cron la mide al pasar y la
--  deja aqui. Sesenta de partida porque es lo que hay hoy; en cuanto la
--  frecuencia cambie, el numero se corrige solo en la primera pasada.
-- =============================================================================

INSERT INTO ajustes (clave, valor, descripcion) VALUES
  ('web_medios_frente',  '0',  'Cuantos medios tenian algo publicado en la generacion anterior'),
  ('web_temas_frente',   '0',  'Cuantos temas tenian algo publicado en la generacion anterior'),
  ('cron_cada_minutos',  '60', 'Cada cuantos minutos pasa el cron; lo mide el propio cron')
ON DUPLICATE KEY UPDATE clave = clave;
