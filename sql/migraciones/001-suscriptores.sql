-- =============================================================================
--  001 - suscriptores
--
--  El proyecto nacio decidiendo NO guardar direcciones de correo: la lista
--  vivia entera en el proveedor -MailerLite o Brevo-, que ya sabe gestionar
--  bajas, rebotes y doble confirmacion. Esa sigue siendo la mejor opcion.
--
--  Pero el correo que hay es un buzon SMTP del propio alojamiento, no una
--  cuenta de proveedor, y con un buzon SMTP no hay donde guardar la lista mas
--  que aqui. Asi que se guarda, y se guarda lo minimo:
--
--    - La direccion, porque sin ella no hay a quien escribir.
--    - El estado y las fechas, que son la prueba del consentimiento: cuando
--      se pidio el alta y cuando se confirmo.
--    - Una huella HMAC de la IP desde la que se pidio, nunca la IP. Sirve
--      para demostrar que el alta vino de algun sitio y para contar intentos,
--      y no sirve para saber de quien era.
--
--  No se guarda nombre, ni apellidos, ni aperturas, ni clics por persona.
-- =============================================================================

CREATE TABLE IF NOT EXISTS suscriptores (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email       VARCHAR(254) NOT NULL,
  estado      ENUM('pendiente','confirmado','baja') NOT NULL DEFAULT 'pendiente',
  -- Testigo de un solo uso del correo de confirmacion. Se borra al usarlo.
  testigo     CHAR(64)     NOT NULL DEFAULT '',
  huella      CHAR(32)     NOT NULL DEFAULT '',
  origen      VARCHAR(40)  NOT NULL DEFAULT 'web',
  pedido      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  confirmado  DATETIME     NULL DEFAULT NULL,
  dado_baja   DATETIME     NULL DEFAULT NULL,
  ultimo_envio DATETIME    NULL DEFAULT NULL,
  rebotes     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_email (email),
  KEY idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ajustes (clave, valor, descripcion) VALUES
  ('correo_alta_abierta', '1', 'Admitir altas nuevas en el boletin')
ON DUPLICATE KEY UPDATE clave = clave;
