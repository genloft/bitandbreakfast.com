-- =============================================================================
--  011 - traduccion al espanol
--
--  Hasta ahora solo se publicaba lo que alguien contaba en espanol. La razon
--  era buena -traducir a maquina es poner en boca de un medio algo que no ha
--  dicho- pero el efecto era que, de setenta fuentes, publicaban ocho: la
--  portada se quedaba en ocho noticias mientras el radar leia mil doscientas
--  entradas al dia. Una promesa que se cumple dejando la casa vacia no es una
--  promesa, es una excusa.
--
--  Asi que se traduce el titular y el resumen -nunca el articulo, que es del
--  medio y se lee en el medio- y cada bit traducido lo dice en su cara.
--  traducido_de guarda de que idioma viene; NULL quiere decir que esas
--  palabras son las que escribio quien las escribio.
--
--  La clave de DeepL no vive aqui ni en el repositorio: la teclea el dueno del
--  sitio en el panel y se guarda en config/traductor.php con permisos 0600,
--  igual que la del buzon. Sin clave, todo esto se queda quieto y el sitio
--  vuelve a publicar solo lo que este en espanol.
-- =============================================================================

ALTER TABLE bits
  ADD COLUMN IF NOT EXISTS traducido_de CHAR(2) NULL DEFAULT NULL AFTER dia;

INSERT INTO ajustes (clave, valor, descripcion) VALUES
  ('traductor_mes',     '',  'Mes al que corresponde la cuenta de caracteres'),
  ('traductor_gastado', '0', 'Caracteres traducidos este mes')
ON DUPLICATE KEY UPDATE clave = clave;

-- Con traductor, la puerta del idioma deja de tener sentido: lo extranjero se
-- publica traducido en vez de descartarse. El ajuste se queda por si algun dia
-- se quiere volver a lo de antes, y el codigo lo respeta.
UPDATE ajustes SET valor = '0' WHERE clave = 'auto_solo_espanol';

-- Y los racimos que se descartaron solo por el idioma vuelven a la cola. Son
-- las noticias que el radar encontro, entendio y tiro por no estar en espanol:
-- ahora hay con que contarlas.
UPDATE racimos
   SET estado = 'candidato', motivo_descarte = ''
 WHERE estado = 'descartado'
   AND motivo_descarte LIKE '%espanol%';
