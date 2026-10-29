-- =============================================================================
--  026 - cargo y empresa, opcionales
--
--  Quien se une a la red puede decir, si quiere, que cargo tiene y en que
--  empresa trabaja. No es un requisito del alta -el formulario no los pide- y
--  vacio es el valor por defecto: ninguna fila existente, ni ninguna alta que
--  no toque estos campos, cambia de comportamiento.
--
--  Sirven para una sola cosa: saber a quien se escribe y poder agrupar la red
--  por tipo de hotel o de puesto el dia que haga falta. Dos campos de texto y
--  de ancho limitado, sin mas datos: ni nombre, ni telefono, ni web.
-- =============================================================================

ALTER TABLE suscriptores
  ADD COLUMN IF NOT EXISTS cargo   VARCHAR(80)  NOT NULL DEFAULT '' AFTER alerta,
  ADD COLUMN IF NOT EXISTS empresa VARCHAR(100) NOT NULL DEFAULT '' AFTER cargo;
