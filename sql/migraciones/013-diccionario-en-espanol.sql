-- =============================================================================
--  013 - el diccionario, con las palabras que se usan en espanol
--
--  El desglose de descartes de /salud.php puso numero a una sospecha: en dos
--  dias, 169 racimos se cayeron por "sin senal tematica". Mirando el
--  diccionario se entiende por que: tiene ciento treinta y siete terminos y
--  esta escrito mirando a la prensa internacional. Falta lo obvio en espanol
--  -"ciberseguridad", "tecnologia hotelera", "venta directa", "sostenibilidad"-
--  asi que una noticia espanola perfectamente pertinente podia no sumar los
--  ocho puntos que hacen falta.
--
--  Eso es lo peor que puede pasarle a este sitio: descartar precisamente lo
--  unico que puede publicar sin traductor.
--
--  Los pesos van bajos en lo generico -tres o cuatro- y altos solo en lo que no
--  aparece fuera del sector. No hay riesgo de que esto cuele tecnologia
--  generalista: la puerta de auto_es_del_sector() sigue exigiendo que el texto
--  hable de hoteles, de alojamientos o de turismo, y esa no se toca.
--
--  Todo normalizado -minusculas, sin acentos-, que es como se guarda y como se
--  busca.
-- =============================================================================

INSERT INTO diccionario (termino, peso, categoria, activo) VALUES
  -- --- Ciberseguridad y cumplimiento ---------------------------------------
  ('ciberseguridad',            7, 'ciberseguridad-cumplimiento', 1),
  ('incidente de seguridad',    7, 'ciberseguridad-cumplimiento', 1),
  ('secuestro de datos',        8, 'ciberseguridad-cumplimiento', 1),
  ('proteccion de datos',       6, 'ciberseguridad-cumplimiento', 1),
  ('suplantacion',              6, 'ciberseguridad-cumplimiento', 1),
  ('parche de seguridad',       6, 'ciberseguridad-cumplimiento', 1),
  ('autenticacion multifactor', 6, 'ciberseguridad-cumplimiento', 1),
  ('doble factor',              5, 'ciberseguridad-cumplimiento', 1),
  ('copia de seguridad',        4, 'ciberseguridad-cumplimiento', 1),
  ('sancion',                   5, 'ciberseguridad-cumplimiento', 1),
  ('normativa',                 4, 'ciberseguridad-cumplimiento', 1),
  ('inspeccion',                4, 'ciberseguridad-cumplimiento', 1),

  -- --- PMS, CRS y sistemas -------------------------------------------------
  ('tecnologia hotelera',       8, 'tecnologia-general', 1),
  ('software hotelero',         8, 'pms-crs', 1),
  ('gestion hotelera',          6, 'pms-crs', 1),
  ('software de gestion',       5, 'pms-crs', 1),
  ('migracion a la nube',       6, 'pms-crs', 1),
  ('integracion de sistemas',   5, 'pms-crs', 1),
  ('transformacion digital',    4, 'tecnologia-general', 1),
  ('digitalizacion',            4, 'tecnologia-general', 1),
  ('crm',                       5, 'experiencia-huesped', 1),
  ('erp',                       4, 'pms-crs', 1),

  -- --- Distribucion --------------------------------------------------------
  ('distribucion hotelera',     7, 'distribucion-otas', 1),
  ('venta directa',             6, 'distribucion-otas', 1),
  ('canal directo',             6, 'distribucion-otas', 1),
  ('intermediacion',            5, 'distribucion-otas', 1),
  ('disparidad de precios',     6, 'distribucion-otas', 1),
  ('gds',                       6, 'distribucion-otas', 1),
  ('comisiones',                4, 'distribucion-otas', 1),

  -- --- Revenue -------------------------------------------------------------
  ('yield management',          6, 'revenue-rms', 1),
  ('prevision de demanda',      6, 'revenue-rms', 1),
  ('estrategia de precios',     5, 'revenue-rms', 1),
  ('adr',                       5, 'revenue-rms', 1),
  ('ocupacion',                 4, 'revenue-rms', 1),
  ('segmentacion',              4, 'revenue-rms', 1),

  -- --- Operaciones e IoT ---------------------------------------------------
  ('punto de venta',            5, 'operaciones-iot', 1),
  ('tpv',                       5, 'operaciones-iot', 1),
  ('domotica',                  6, 'operaciones-iot', 1),
  ('gestion de tareas',         3, 'operaciones-iot', 1),
  ('wifi',                      4, 'operaciones-iot', 1),

  -- --- Datos e IA ----------------------------------------------------------
  ('analitica',                 5, 'ia-aplicada', 1),
  ('business intelligence',     5, 'ia-aplicada', 1),
  ('cuadro de mando',           4, 'ia-aplicada', 1),
  ('datos del huesped',         6, 'ia-aplicada', 1),
  ('modelo de lenguaje',        6, 'ia-aplicada', 1),

  -- --- Experiencia del huesped ---------------------------------------------
  ('app del hotel',             6, 'experiencia-huesped', 1),
  ('aplicacion movil',          4, 'experiencia-huesped', 1),
  ('reconocimiento facial',     6, 'experiencia-huesped', 1),
  ('biometria',                 6, 'experiencia-huesped', 1),
  ('personalizacion',           4, 'experiencia-huesped', 1),

  -- --- Pagos ---------------------------------------------------------------
  ('pago movil',                5, 'pagos-fraude', 1),
  ('bizum',                     4, 'pagos-fraude', 1),
  ('cobro anticipado',          4, 'pagos-fraude', 1),

  -- --- Sostenibilidad ------------------------------------------------------
  ('sostenibilidad',            5, 'sostenibilidad-energia', 1),
  ('huella de carbono',         6, 'sostenibilidad-energia', 1),
  ('consumo energetico',        6, 'sostenibilidad-energia', 1),
  ('certificacion energetica',  5, 'sostenibilidad-energia', 1),
  ('autoconsumo',               5, 'sostenibilidad-energia', 1),

  -- --- Inversion y mercado -------------------------------------------------
  ('capital riesgo',            5, 'inversion-mercado', 1),
  ('ronda de inversion',        6, 'inversion-mercado', 1),
  ('participacion mayoritaria', 5, 'inversion-mercado', 1)
ON DUPLICATE KEY UPDATE activo = 1;
