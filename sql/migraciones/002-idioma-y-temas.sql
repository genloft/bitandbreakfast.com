-- =============================================================================
--  002 - solo espanol
--
--  El radar se lee en Espana. Hasta ahora entraba lo mejor puntuado en
--  cualquier idioma y la mitad de la edicion salia en ingles, con su etiqueta
--  avisando; util para quien lee ingles, un muro para quien no.
--
--  Traducir no es una opcion: el bit dice lo que dijo la fuente, y una
--  traduccion automatica ya no es lo que dijo la fuente. Asi que la regla es
--  la unica honrada que queda: entra lo que alguien cuente en espanol. Se
--  pierde alguna primicia internacional; a cambio no hay una sola linea que el
--  lector no pueda leer.
--
--  Se apaga poniendo este ajuste a 0.
-- =============================================================================

INSERT INTO ajustes (clave, valor, descripcion) VALUES
  ('auto_solo_espanol', '1', 'Publicar solo racimos que alguien cuente en espanol')
ON DUPLICATE KEY UPDATE clave = clave;
