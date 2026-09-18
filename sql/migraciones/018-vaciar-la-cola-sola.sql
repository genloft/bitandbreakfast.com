-- =============================================================================
--  018 - que la cola se vacie sola
--
--  Hay cuatrocientas noticias esperando: entraron, se entendieron y se
--  descartaron por estar en ingles, y ahora hay traductor. El problema es el
--  ritmo. Escribir un bit ya no es solo escribirlo: si viene en otro idioma
--  hay que traducirlo, y eso es esperar a un servicio de fuera. Con los
--  veinticinco segundos de siempre entran cuatro o cinco por pasada, y con el
--  cron cada hora eso son semanas.
--
--  La escritura pasa a tener noventa segundos propios y el techo de la pasada
--  sube a doscientos cuarenta. No es tiempo quemando procesador: es tiempo
--  esperando a la red, que es lo que un alojamiento compartido puede
--  permitirse sin molestar a nadie. A ese ritmo la cola se vacia en unas horas
--  sin que nadie tenga que hacer nada.
--
--  Cuando la cola baje, esto no sobra: la escritura sale enseguida si no hay
--  candidatos, asi que el presupuesto largo solo se usa cuando hace falta.
-- =============================================================================

INSERT INTO ajustes (clave, valor, descripcion) VALUES
  ('presupuesto_auto',       '90',  'Segundos para escribir y traducir bits en cada pasada'),
  ('presupuesto_cron_total', '240', 'Techo de segundos de la pasada entera')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);
