-- =============================================================================
--  010 - el dia de descubrimiento, en vez de la edicion
--
--  Las ediciones desaparecen de la web. El motivo no es estetico: una edicion
--  solo se ve cuando se cierra, asi que todo lo que el radar encontraba hoy
--  estaba invisible hasta mañana. Un agregador que esconde lo de hoy no es un
--  agregador, es una revista; y lo que se pidio es un agregador.
--
--  A partir de aqui manda el dia en que se descubrio la noticia:
--
--    - bits.dia guarda ese dia. Se rellena con la fecha en que se escribio el
--      bit, que es exactamente el dia en que este sitio se entero.
--    - El bit se publica al escribirse, no al cerrar nada. Por eso todo lo que
--      estaba 'aprobado' -esperando a que cerrara su edicion- pasa aqui a
--      'publicado': son noticias ya escritas que nadie ha llegado a ver.
--
--  La tabla ediciones se queda, y no por nostalgia: el boletin necesita saber
--  que mando ayer y a quien, y eso ya esta resuelto con envios(edicion_id).
--  Asi que sigue habiendo un cajon por dia, pero es un detalle de fontaneria:
--  la web no lo nombra en ninguna parte. Y deja de ser un tope -32 bits- para
--  ser lo que debe: todo lo que haya pasado las puertas ese dia.
-- =============================================================================

ALTER TABLE bits
  ADD COLUMN IF NOT EXISTS dia DATE NULL DEFAULT NULL AFTER estado;

ALTER TABLE bits
  ADD INDEX IF NOT EXISTS idx_dia (dia, id);

UPDATE bits SET dia = DATE(creado) WHERE dia IS NULL;

-- Lo escrito y aprobado que esperaba a que cerrase su edicion. Son noticias
-- terminadas que no se han llegado a publicar por un detalle de calendario.
UPDATE bits SET estado = 'publicado' WHERE estado = 'aprobado';

-- Y sus racimos, que si no se quedan contados como candidatos para siempre.
UPDATE racimos SET estado = 'publicado'
 WHERE estado = 'candidato'
   AND id IN (SELECT racimo_id FROM bits WHERE racimo_id IS NOT NULL AND estado = 'publicado');

-- El cajon del dia deja de ser un tope. Doscientos no es un limite editorial:
-- es un tope de seguridad para que un fallo de las puertas no llene la portada
-- con mil entradas de una agencia.
UPDATE ajustes SET valor = '200' WHERE clave = 'edicion_max_bits';

INSERT INTO ajustes (clave, valor, descripcion) VALUES
  ('web_bits_portada', '80',  'Cuantos bits caben en la portada, contando hacia atras'),
  ('web_dias_archivo', '180', 'Cuantos dias lista el archivo')
ON DUPLICATE KEY UPDATE clave = clave;
