-- =============================================================================
--  025 - alerta por palabra o proveedor
--
--  "Avisame cuando se hable de Mews, o de ransomware": la funcionalidad por
--  la que un profesional deja su correo. No es una ficha de proveedor -esta
--  decidido que no las hay, "la pregunta de Mews, no la del hotel"-: aqui el
--  proveedor no es una pagina que se publica, es un filtro que elige el
--  lector, exactamente igual que una palabra suelta.
--
--  Por eso una sola columna de texto libre, no dos -una de proveedores y
--  otra de palabras clave-: para quien escribe "Mews, ransomware" al
--  suscribirse son la misma cosa, un termino que le importa. Que un termino
--  case contra el titular y el cuerpo del bit, o contra el nombre de un
--  proveedor ya identificado, lo decide envio_bits_para_alerta() en
--  lib/envio.php, no el esquema.
--
--  Vacio significa "sin alerta", que es tambien el valor por defecto: ninguna
--  fila existente, ni ninguna alta que no toque el selector, cambia de
--  comportamiento.
-- =============================================================================

ALTER TABLE suscriptores
  ADD COLUMN IF NOT EXISTS alerta VARCHAR(300) NOT NULL DEFAULT '' AFTER temas;
