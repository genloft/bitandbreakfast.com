-- =============================================================================
--  Bit & Breakfast - esquema de base de datos
--  MySQL 5.7+ / MariaDB 10.3+ . InnoDB . utf8mb4_unicode_ci
--
--  Ejecutable de una sola pasada en phpMyAdmin sobre una base de datos vacia.
--  No crea la base de datos: en Hostinger la crea hPanel con su propio nombre.
--
--  Se usa utf8mb4_unicode_ci y no utf8mb4_0900_ai_ci porque esta ultima no
--  existe en MariaDB, que es lo que sirve Hostinger en la mayoria de planes.
-- =============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- fuentes: el catalogo de feeds que rastrea el bot.
-- etag y last_modified guardan lo que devolvio el servidor la ultima vez para
-- poder mandar If-None-Match / If-Modified-Since y cobrar un 304 barato.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS fuentes;
CREATE TABLE fuentes (
  id                  SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre              VARCHAR(120)      NOT NULL,
  url_feed            VARCHAR(500)      NOT NULL,
  url_sitio           VARCHAR(500)      NOT NULL DEFAULT '',
  tipo                ENUM('prensa','changelog','estado','empleo','financiacion',
                           'normativa','investigacion','ferias','general')
                                        NOT NULL DEFAULT 'prensa',
  idioma              CHAR(2)           NOT NULL DEFAULT 'en',
  region              ENUM('es','eu','global') NOT NULL DEFAULT 'global',
  categoria_defecto   VARCHAR(40)       NOT NULL DEFAULT 'tecnologia-general',
  peso                TINYINT UNSIGNED  NOT NULL DEFAULT 5,
  activa              TINYINT(1)        NOT NULL DEFAULT 1,
  ultimo_intento      DATETIME          NULL DEFAULT NULL,
  ultimo_ok           DATETIME          NULL DEFAULT NULL,
  etag                VARCHAR(255)      NULL DEFAULT NULL,
  last_modified       VARCHAR(120)      NULL DEFAULT NULL,
  fallos_consecutivos SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  notas               VARCHAR(500)      NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  -- Prefijo de 190 caracteres: con utf8mb4 un indice de 500 no cabe en el
  -- limite de 3072 bytes de InnoDB. 190 basta para distinguir feeds reales.
  UNIQUE KEY uq_url_feed (url_feed(190)),
  KEY idx_activa (activa, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- racimos: noticias que cuentan lo mismo, agrupadas.
-- Se declara antes que items solo para que la clave ajena quede legible.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS racimos;
CREATE TABLE racimos (
  id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  titulo_representativo VARCHAR(500) NOT NULL,
  primer_visto          DATETIME     NOT NULL,
  ultimo_visto          DATETIME     NOT NULL,
  n_items               SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  puntuacion            INT          NOT NULL DEFAULT 0,
  estado                ENUM('candidato','descartado','publicado')
                                     NOT NULL DEFAULT 'candidato',
  motivo_descarte       VARCHAR(120) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  -- La cola del panel pide exactamente esto: candidatos recientes por puntuacion.
  KEY idx_cola (estado, puntuacion DESC, ultimo_visto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- items: cada entrada cruda leida de un feed.
-- resumen_origen guarda SOLO el resumen que publica el propio feed y truncado
-- en la ingesta: hay feeds que sirven el articulo entero y no queremos
-- almacenar texto ajeno completo.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS items;
CREATE TABLE items (
  id             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  fuente_id      SMALLINT UNSIGNED NOT NULL,
  guid           VARCHAR(500)      NOT NULL DEFAULT '',
  url            VARCHAR(800)      NOT NULL,
  url_canonica   VARCHAR(800)      NOT NULL,
  hash_url       CHAR(40)          NOT NULL,
  titulo         VARCHAR(500)      NOT NULL,
  titulo_norm    VARCHAR(500)      NOT NULL,
  resumen_origen TEXT              NULL,
  autor          VARCHAR(160)      NOT NULL DEFAULT '',
  publicado      DATETIME          NULL DEFAULT NULL,
  capturado      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  idioma         CHAR(2)           NOT NULL DEFAULT 'en',
  racimo_id      INT UNSIGNED      NULL DEFAULT NULL,
  puntuacion     INT               NOT NULL DEFAULT 0,
  estado         ENUM('nuevo','agrupado','descartado','usado')
                                   NOT NULL DEFAULT 'nuevo',
  PRIMARY KEY (id),
  -- El SHA-1 de la URL canonica es la unica barrera real contra duplicados.
  UNIQUE KEY uq_hash_url (hash_url),
  KEY idx_publicado (publicado),
  KEY idx_racimo (racimo_id),
  KEY idx_estado (estado),
  KEY idx_fuente_guid (fuente_id, guid(100)),
  -- Ventana de agrupacion: items nuevos de las ultimas 72 horas.
  KEY idx_ventana (estado, publicado),
  CONSTRAINT fk_items_fuente FOREIGN KEY (fuente_id) REFERENCES fuentes (id)
    ON DELETE CASCADE,
  CONSTRAINT fk_items_racimo FOREIGN KEY (racimo_id) REFERENCES racimos (id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- item_token: indice invertido de tokens poco frecuentes del titular.
-- Sin esto, agrupar exige comparar cada item nuevo contra todos los de la
-- ventana (del orden de un millon de comparaciones) y no cabe en 30 segundos.
-- Con esto, cada item se compara solo contra los que comparten algun token raro.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS item_token;
CREATE TABLE item_token (
  token   VARCHAR(40)  NOT NULL,
  item_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (token, item_id),
  KEY idx_item (item_id),
  CONSTRAINT fk_token_item FOREIGN KEY (item_id) REFERENCES items (id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- ediciones: cada envio semanal.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS ediciones;
CREATE TABLE ediciones (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  numero          INT UNSIGNED NOT NULL,
  slug            VARCHAR(120) NOT NULL,
  titulo          VARCHAR(200) NOT NULL DEFAULT '',
  intro           TEXT         NULL,
  fecha_prevista  DATE         NOT NULL,
  fecha_envio     DATETIME     NULL DEFAULT NULL,
  estado          ENUM('abierta','cerrada','enviada') NOT NULL DEFAULT 'abierta',
  id_campana_esp  VARCHAR(80)  NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  UNIQUE KEY uq_numero (numero),
  UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- bits: la pieza editorial. Un bit por racimo publicado.
-- revisado es la barrera dura: publicar.php se niega a incluir revisado = 0.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS bits;
CREATE TABLE bits (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  racimo_id     INT UNSIGNED NULL DEFAULT NULL,
  titular       VARCHAR(120) NOT NULL,
  cuerpo        TEXT         NOT NULL,
  por_que       TEXT         NOT NULL,
  categoria     VARCHAR(40)  NOT NULL DEFAULT 'tecnologia-general',
  madurez       ENUM('rumor','anuncio','disponible','implantado')
                             NOT NULL DEFAULT 'anuncio',
  tipo          ENUM('producto','inversion','regulacion','caso_real','incidente')
                             NOT NULL DEFAULT 'producto',
  seccion       VARCHAR(40)  NOT NULL DEFAULT '',
  estado        ENUM('borrador','aprobado','publicado') NOT NULL DEFAULT 'borrador',
  edicion_id    INT UNSIGNED NULL DEFAULT NULL,
  orden         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  redactado_por ENUM('ia','humano') NOT NULL DEFAULT 'humano',
  revisado      TINYINT(1)   NOT NULL DEFAULT 0,
  creado        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  modificado    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                             ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_edicion (edicion_id, orden),
  KEY idx_estado (estado),
  KEY idx_racimo (racimo_id),
  CONSTRAINT fk_bits_racimo FOREIGN KEY (racimo_id) REFERENCES racimos (id)
    ON DELETE SET NULL,
  CONSTRAINT fk_bits_edicion FOREIGN KEY (edicion_id) REFERENCES ediciones (id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- proveedores y sus alias.
-- Los alias van en tabla propia y no en una columna JSON: MariaDB trata JSON
-- como LONGTEXT, no se indexa, y detectar proveedores exigiria un LIKE por fila.
-- alias_norm guarda el alias ya normalizado por lib/texto.php.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS proveedores;
CREATE TABLE proveedores (
  id        SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre    VARCHAR(120) NOT NULL,
  slug      VARCHAR(120) NOT NULL,
  web       VARCHAR(300) NOT NULL DEFAULT '',
  categoria VARCHAR(40)  NOT NULL DEFAULT 'tecnologia-general',
  PRIMARY KEY (id),
  UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS proveedor_alias;
CREATE TABLE proveedor_alias (
  id           SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  proveedor_id SMALLINT UNSIGNED NOT NULL,
  alias        VARCHAR(120) NOT NULL,
  alias_norm   VARCHAR(120) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_alias_norm (alias_norm),
  KEY idx_proveedor (proveedor_id),
  CONSTRAINT fk_alias_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores (id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS item_proveedor;
CREATE TABLE item_proveedor (
  item_id      INT UNSIGNED      NOT NULL,
  proveedor_id SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (item_id, proveedor_id),
  KEY idx_proveedor (proveedor_id),
  CONSTRAINT fk_ip_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE,
  CONSTRAINT fk_ip_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS bit_proveedor;
CREATE TABLE bit_proveedor (
  bit_id       INT UNSIGNED      NOT NULL,
  proveedor_id SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (bit_id, proveedor_id),
  KEY idx_proveedor (proveedor_id),
  CONSTRAINT fk_bp_bit FOREIGN KEY (bit_id) REFERENCES bits (id) ON DELETE CASCADE,
  CONSTRAINT fk_bp_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- diccionario: terminos que suman o restan en la puntuacion.
-- peso negativo = filtro anti-publirreportaje.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS diccionario;
CREATE TABLE diccionario (
  id        SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  termino   VARCHAR(80)  NOT NULL,
  peso      TINYINT      NOT NULL DEFAULT 1,
  categoria VARCHAR(40)  NOT NULL DEFAULT '',
  activo    TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_termino (termino),
  KEY idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- votos: realimentacion del lector desde el correo.
-- token debe ser distinto por destinatario (HMAC de bit + id de suscriptor del
-- ESP). Si fuera el mismo para todos, la unicidad de abajo dejaria un solo voto
-- por bit en todo el mundo. hash_ip es la red de seguridad, nunca la IP.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS votos;
CREATE TABLE votos (
  id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  bit_id  INT UNSIGNED NOT NULL,
  valor   TINYINT      NOT NULL,
  token   VARCHAR(64)  NOT NULL,
  hash_ip CHAR(64)     NOT NULL DEFAULT '',
  creado  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_bit_token (bit_id, token),
  KEY idx_bit (bit_id),
  CONSTRAINT fk_votos_bit FOREIGN KEY (bit_id) REFERENCES bits (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- clics: cada paso por api/r.php.
-- hash_ua permite descontar los prefetch de Gmail y los escaneres de correo,
-- que siguen los enlaces antes de que ningun humano los vea.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS clics;
CREATE TABLE clics (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  bit_id     INT UNSIGNED NOT NULL,
  edicion_id INT UNSIGNED NULL DEFAULT NULL,
  creado     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  hash_ua    CHAR(64)     NOT NULL DEFAULT '',
  sospechoso TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_bit (bit_id),
  KEY idx_edicion (edicion_id),
  CONSTRAINT fk_clics_bit FOREIGN KEY (bit_id) REFERENCES bits (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- log_ingesta: una fila por fuente y ejecucion. Es lo que mira el panel para
-- saber que feed se ha muerto.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS log_ingesta;
CREATE TABLE log_ingesta (
  id        INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  fuente_id SMALLINT UNSIGNED NOT NULL,
  inicio    DATETIME          NOT NULL,
  fin       DATETIME          NULL DEFAULT NULL,
  nuevos    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  resultado ENUM('ok','error','sin_cambios') NOT NULL DEFAULT 'ok',
  mensaje   VARCHAR(500)      NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  KEY idx_fuente_inicio (fuente_id, inicio),
  CONSTRAINT fk_log_fuente FOREIGN KEY (fuente_id) REFERENCES fuentes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- usuarios: acceso al panel. No aparece en el documento de especificacion pero
-- hace falta para el login con password_hash del apartado de seguridad.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
  id            SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario       VARCHAR(60)  NOT NULL,
  hash_clave    VARCHAR(255) NOT NULL,
  nombre        VARCHAR(120) NOT NULL DEFAULT '',
  activo        TINYINT(1)   NOT NULL DEFAULT 1,
  ultimo_acceso DATETIME     NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- ajustes: todo lo que quiero poder tocar sin volver a subir ficheros.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS ajustes;
CREATE TABLE ajustes (
  clave        VARCHAR(60) NOT NULL,
  valor        TEXT        NOT NULL,
  descripcion  VARCHAR(255) NOT NULL DEFAULT '',
  actualizado  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP
                           ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ajustes (clave, valor, descripcion) VALUES
  ('ingesta_puntero',        '0',  'Id de la ultima fuente procesada; puntero circular del lote'),
  ('ingesta_lote',           '15', 'Fuentes por ejecucion del cron de ingesta'),
  ('ingesta_timeout',        '10', 'Segundos de espera por peticion HTTP'),
  ('ingesta_resumen_max',    '1200','Caracteres que se guardan del resumen del feed'),
  ('procesar_lote',          '40', 'Items por ejecucion del cron de procesado'),
  ('agrupar_ventana_horas',  '72', 'Ventana en horas para considerar dos items la misma noticia'),
  ('agrupar_umbral_alto',    '0.45','Similitud Jaccard que agrupa sin mas condiciones'),
  ('agrupar_umbral_bajo',    '0.30','Similitud Jaccard que agrupa si ademas comparten proveedores'),
  ('agrupar_min_proveedores','2',  'Proveedores compartidos exigidos con el umbral bajo'),
  ('punt_peso_fuente',       '3',  'Multiplicador del peso de la fuente'),
  ('punt_tope_diccionario',  '30', 'Tope de puntos que puede aportar el diccionario'),
  ('punt_por_fuente_racimo', '6',  'Puntos por cada fuente del racimo, hasta cinco'),
  ('punt_frescura_24h',      '10', 'Puntos si la noticia tiene menos de 24 horas'),
  ('punt_frescura_72h',      '5',  'Puntos si tiene menos de 72 horas'),
  ('punt_frescura_7d',       '2',  'Puntos si tiene menos de 7 dias'),
  ('punt_proveedor',         '8',  'Puntos si menciona algun proveedor del catalogo'),
  ('punt_bonus_incidente',   '12', 'Bonus por tipo incidente'),
  ('punt_bonus_regulacion',  '10', 'Bonus por tipo regulacion'),
  ('punt_bonus_changelog',   '6',  'Bonus por fuente de tipo changelog'),
  ('punt_bonus_empleo',      '3',  'Bonus por fuente de tipo empleo'),
  ('punt_publirreportaje',   '12', 'Penalizacion del filtro anti-publirreportaje'),
  ('edicion_max_bits',       '20', 'Techo de bits por edicion'),
  ('edicion_cuota_es_eu',    '30', 'Porcentaje minimo aconsejado de bits de fuentes ES o EU'),
  ('retencion_items_dias',   '180','Dias que se conservan los items no usados'),
  ('api_limite_hora',        '60', 'Peticiones por hora permitidas en api/candidatos.php');

SET FOREIGN_KEY_CHECKS = 1;
