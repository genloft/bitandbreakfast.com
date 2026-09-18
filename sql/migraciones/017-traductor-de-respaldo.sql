-- =============================================================================
--  017 - un traductor de respaldo, sin clave
--
--  La traduccion estaba montada y esperando a que alguien pegase una clave de
--  DeepL. Mientras tanto, la regla seguia siendo "solo se publica lo que este
--  en espanol", y eso son cuatro de cada cinco noticias que este radar entiende
--  y tira: 403 en dos dias. Esperar a una clave para ensenar lo que ya se tiene
--  es una forma tonta de tener la casa vacia.
--
--  Asi que hay un segundo proveedor que no necesita clave -MyMemory- y entra
--  solo cuando no hay otra cosa. Traduce algo peor y tiene cuota diaria por
--  palabras, no mensual por caracteres, asi que lleva su propia cuenta. En
--  cuanto aparezca la clave de DeepL, se aparta.
--
--  traductor_contacto es opcional: un correo multiplica por diez esa cuota
--  diaria. Va a un tercero, asi que lo pone quien quiera ponerlo y se queda
--  vacio de fabrica.
--
--  Y con respaldo ya hay traductor, asi que la puerta del idioma se abre: lo
--  extranjero deja de descartarse. Los racimos que se tiraron solo por eso
--  vuelven a la cola.
-- =============================================================================

INSERT INTO ajustes (clave, valor, descripcion) VALUES
  ('traductor_respaldo',  '1', 'Traducir con el respaldo sin clave cuando no hay DeepL'),
  ('traductor_contacto',  '',  'Correo que se manda al respaldo para ampliar su cuota'),
  ('traductor_dia',       '',  'Dia al que corresponde la cuenta de palabras del respaldo'),
  ('traductor_palabras',  '0', 'Palabras traducidas hoy por el respaldo')
ON DUPLICATE KEY UPDATE clave = clave;

UPDATE ajustes SET valor = '0' WHERE clave = 'auto_solo_espanol';

UPDATE racimos
   SET estado = 'candidato', motivo_descarte = ''
 WHERE estado = 'descartado'
   AND motivo_descarte LIKE '%espanol%';
