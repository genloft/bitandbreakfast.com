-- =============================================================================
--  024 - suscripcion por tema
--
--  El alta era todo o nada: un correo al dia con todos los temas, sin poder
--  elegir. Un director de sistemas quiere Ciberseguridad y Cumplimiento, no
--  el resto; con el alta actual, o se apunta a todo o no se apunta.
--
--  Una columna de texto, no una tabla nueva: los temas de bits_categorias()
--  ya son un catalogo cerrado y pequeño (una docena), y una fila por
--  suscriptor por tema habria sido una tabla entera para guardar lo mismo
--  que cabe en una cadena separada por comas.
--
--  Vacio significa "todos", no "ninguno": es el valor por defecto, asi que
--  cualquier fila ya existente -y cualquier alta que no toque el nuevo
--  campo, como la de quien usa MailerLite o Brevo- sigue recibiendolo todo
--  exactamente como hasta ahora. Elegir es una opcion, no un requisito.
-- =============================================================================

ALTER TABLE suscriptores
  ADD COLUMN IF NOT EXISTS temas VARCHAR(255) NOT NULL DEFAULT '' AFTER origen;
