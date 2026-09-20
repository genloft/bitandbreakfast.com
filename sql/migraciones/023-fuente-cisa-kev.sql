-- =============================================================================
--  023 - la fuente del boletin de vulnerabilidades KEV
--
--  cron/kev.php cruza el catalogo KEV de CISA (JSON publico, sin clave) contra
--  proveedores y proveedor_alias, y cuando una entrada nombra a un proveedor
--  del catalogo, la guarda en items como cualquier otra noticia, para que la
--  agrupe, la puntue y la publique el mismo cauce de siempre. Para eso hace
--  falta una fila en fuentes: items.fuente_id es NOT NULL y tiene clave ajena.
--
--  Esta fuente no es un feed RSS de verdad -es un JSON con su propia forma, y
--  el rastreador generico solo sabe leer RSS/Atom/RDF-, asi que no puede
--  quedar activa = 1 sin mas: la ronda de ingesta la cogeria en su turno,
--  descargaria el JSON entero como si fuera XML y lo marcaria como una fuente
--  rota para siempre. La columna "gestion" distingue las dos pocas fuentes que
--  se alimentan de su propia tarea -por ahora, esta- del resto, que sigue
--  entrando por el rastreador de siempre.
--
--  activa se queda en 1: sigue significando "se esta vigilando", y aqui es
--  verdad. Apagarla desde el panel para de verdad esta tarea -cron/kev.php lo
--  respeta-, que es el mismo control que ya tiene cualquier otra fuente.
-- =============================================================================

ALTER TABLE fuentes
  ADD COLUMN IF NOT EXISTS gestion ENUM('rss','manual') NOT NULL DEFAULT 'rss' AFTER tipo;

INSERT IGNORE INTO fuentes
  (nombre, url_feed, url_sitio, tipo, gestion, idioma, region,
   categoria_defecto, peso, activa, notas, fecha_alta)
VALUES
  ('CISA KEV', 'https://www.cisa.gov/sites/default/files/feeds/known_exploited_vulnerabilities.json',
   'https://www.cisa.gov/known-exploited-vulnerabilities-catalog', 'estado', 'manual', 'en', 'global',
   'ciberseguridad', 8, 1,
   'No la lee el rastreador de feeds: la alimenta cron/kev.php, que cruza cada entrada nueva del catalogo contra proveedores y proveedor_alias.',
   UTC_DATE());
