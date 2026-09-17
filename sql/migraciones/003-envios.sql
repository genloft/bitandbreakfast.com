-- =============================================================================
--  003 - envios
--
--  Quien ha recibido que. Hace falta por una razon muy concreta: el envio va
--  por lotes -un buzon compartido tiene limite por hora-, asi que entre la
--  primera tanda y la ultima pasa tiempo, y sin dejar constancia de a quien se
--  le ha mandado ya, la pasada siguiente volveria a empezar por el primero.
--
--  Dos identificadores y una fecha. Ni asunto, ni contenido, ni aperturas, ni
--  clics: no se guarda quien lee que, solo si le tocaba y si salio.
-- =============================================================================

CREATE TABLE IF NOT EXISTS envios (
  edicion_id    INT UNSIGNED NOT NULL,
  suscriptor_id INT UNSIGNED NOT NULL,
  enviado       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resultado     ENUM('ok','fallo') NOT NULL DEFAULT 'ok',
  PRIMARY KEY (edicion_id, suscriptor_id),
  KEY idx_edicion (edicion_id),
  CONSTRAINT fk_envios_edicion FOREIGN KEY (edicion_id) REFERENCES ediciones (id) ON DELETE CASCADE,
  CONSTRAINT fk_envios_suscriptor FOREIGN KEY (suscriptor_id) REFERENCES suscriptores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ajustes (clave, valor, descripcion) VALUES
  ('envio_automatico', '1',  'Mandar sola la edicion cerrada a los confirmados'),
  ('envio_por_pasada', '25', 'Correos por ejecucion del cron, por el limite del buzon')
ON DUPLICATE KEY UPDATE clave = clave;
