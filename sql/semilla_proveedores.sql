-- =============================================================================
--  Bit & Breakfast - semilla del catalogo de proveedores
--
--  Para que sirve, ademas de para las fichas de la fase 6: es la segunda
--  puerta del agrupador. La similitud de titulares no cruza idiomas, asi que
--  la misma noticia en Skift y en Hosteltur da un Jaccard cercano a cero. Lo
--  que si cruza es que las dos hablen de Mews y de Cloudbeds. Sin este
--  catalogo, esa puerta no se abre nunca y solo agrupa el umbral alto.
--
--  alias_norm va YA NORMALIZADO por lib/texto.php: minusculas, sin acentos y
--  con cualquier signo convertido en espacio. Por eso "Booking.com" se guarda
--  como "booking com" y "OPERA Cloud" como "opera cloud".
--
--  Criterio para los alias: solo los que no puedan confundirse con una
--  palabra corriente. "Sabre" u "Opera" sueltos casarian con cualquier cosa,
--  asi que van siempre acompanados. La deteccion busca palabras completas,
--  pero una palabra completa ambigua sigue siendo ambigua.
--
--  El campo web se deja vacio a proposito: las fichas de proveedor se
--  completan desde el panel en la fase 6, y una URL inventada aqui seria peor
--  que ninguna.
--
--  Las dos sentencias son INSERT IGNORE y los ids van explicitos, asi que el
--  fichero se puede reimportar sin romper nada: es lo que hay que hacer en una
--  instalacion anterior a la fase 2, y lo que permitira ampliar el catalogo
--  mas adelante anadiendo filas al final.
--
--  Ejecutar despues de sql/esquema.sql.
-- =============================================================================

SET NAMES utf8mb4;

INSERT IGNORE INTO proveedores (id, nombre, slug, web, categoria) VALUES
  ( 1, 'Mews',                  'mews',                  '', 'pms'),
  ( 2, 'Cloudbeds',             'cloudbeds',             '', 'pms'),
  ( 3, 'Oracle Hospitality',    'oracle-hospitality',    '', 'pms'),
  ( 4, 'Amadeus Hospitality',   'amadeus-hospitality',   '', 'distribucion'),
  ( 5, 'Shiji Group',           'shiji',                 '', 'pms'),
  ( 6, 'Apaleo',                'apaleo',                '', 'pms'),
  ( 7, 'Guestline',             'guestline',             '', 'pms'),
  ( 8, 'Protel',                'protel',                '', 'pms'),
  ( 9, 'Stayntouch',            'stayntouch',            '', 'pms'),
  (10, 'RoomRaccoon',           'roomraccoon',           '', 'pms'),
  (11, 'Infor Hospitality',     'infor-hospitality',     '', 'pms'),
  (12, 'Noray',                 'noray',                 '', 'pms'),
  (13, 'SiteMinder',            'siteminder',            '', 'channel-manager'),
  (14, 'Dingus',                'dingus',                '', 'channel-manager'),
  (15, 'Booking.com',           'booking-com',           '', 'distribucion'),
  (16, 'Expedia Group',         'expedia',               '', 'distribucion'),
  (17, 'Airbnb',                'airbnb',                '', 'distribucion'),
  (18, 'Tripadvisor',           'tripadvisor',           '', 'distribucion'),
  (19, 'Hotelbeds',             'hotelbeds',             '', 'distribucion'),
  (20, 'Mirai',                 'mirai',                 '', 'motor-reserva'),
  (21, 'Paraty Tech',           'paraty-tech',           '', 'motor-reserva'),
  (22, 'Roiback',               'roiback',               '', 'motor-reserva'),
  (23, 'Witbooking',            'witbooking',            '', 'motor-reserva'),
  (24, 'Bookassist',            'bookassist',            '', 'motor-reserva'),
  (25, 'The Hotels Network',    'the-hotels-network',    '', 'motor-reserva'),
  (26, 'Duetto',                'duetto',                '', 'rms'),
  (27, 'IDeaS',                 'ideas',                 '', 'rms'),
  (28, 'Atomize',               'atomize',               '', 'rms'),
  (29, 'BEONx',                 'beonx',                 '', 'rms'),
  (30, 'Lighthouse',            'lighthouse',            '', 'inteligencia-mercado'),
  (31, 'Revinate',              'revinate',              '', 'crm-marketing'),
  (32, 'Cendyn',                'cendyn',                '', 'crm-marketing'),
  (33, 'Triptease',             'triptease',             '', 'crm-marketing'),
  (34, 'HiJiffy',               'hijiffy',               '', 'mensajeria'),
  (35, 'Quicktext',             'quicktext',             '', 'mensajeria'),
  (36, 'Asksuite',              'asksuite',              '', 'mensajeria'),
  (37, 'Hotelinking',           'hotelinking',           '', 'crm-marketing'),
  (38, 'Civitfun',              'civitfun',              '', 'operaciones'),
  (39, 'Hotelkit',              'hotelkit',              '', 'operaciones'),
  (40, 'SuitePad',              'suitepad',              '', 'operaciones'),
  (41, 'ASSA ABLOY Global Solutions', 'assa-abloy',      '', 'accesos'),
  (42, 'Salto Systems',         'salto-systems',         '', 'accesos'),
  (43, 'dormakaba',             'dormakaba',             '', 'accesos'),
  (44, 'Onity',                 'onity',                 '', 'accesos'),
  (45, 'Adyen',                 'adyen',                 '', 'pagos'),
  (46, 'Planet',                'planet-payment',        '', 'pagos'),
  (47, 'Sabre Hospitality',     'sabre-hospitality',     '', 'distribucion');

INSERT IGNORE INTO proveedor_alias (proveedor_id, alias, alias_norm) VALUES
  ( 1, 'Mews',                   'mews'),
  ( 1, 'Mews Systems',           'mews systems'),
  ( 2, 'Cloudbeds',              'cloudbeds'),
  ( 3, 'Oracle Hospitality',     'oracle hospitality'),
  ( 3, 'Oracle OPERA',           'oracle opera'),
  ( 3, 'OPERA Cloud',            'opera cloud'),
  ( 3, 'OPERA PMS',              'opera pms'),
  ( 3, 'Suite8',                 'suite8'),
  ( 3, 'Suite 8',                'suite 8'),
  ( 4, 'Amadeus Hospitality',    'amadeus hospitality'),
  ( 4, 'Amadeus IT Group',       'amadeus it group'),
  ( 4, 'iHotelier',              'ihotelier'),
  ( 5, 'Shiji',                  'shiji'),
  ( 5, 'Shiji Group',            'shiji group'),
  ( 5, 'Infrasys',               'infrasys'),
  ( 6, 'Apaleo',                 'apaleo'),
  ( 7, 'Guestline',              'guestline'),
  ( 8, 'Protel',                 'protel'),
  ( 8, 'protel hotelsoftware',   'protel hotelsoftware'),
  ( 9, 'Stayntouch',             'stayntouch'),
  (10, 'RoomRaccoon',            'roomraccoon'),
  (11, 'Infor Hospitality',      'infor hospitality'),
  (11, 'Infor HMS',              'infor hms'),
  (12, 'Noray Hotel',            'noray hotel'),
  (13, 'SiteMinder',             'siteminder'),
  (13, 'Little Hotelier',        'little hotelier'),
  (14, 'Dingus',                 'dingus'),
  (15, 'Booking.com',            'booking com'),
  (15, 'Booking Holdings',       'booking holdings'),
  (16, 'Expedia',                'expedia'),
  (16, 'Expedia Group',          'expedia group'),
  (16, 'Vrbo',                   'vrbo'),
  (17, 'Airbnb',                 'airbnb'),
  (18, 'Tripadvisor',            'tripadvisor'),
  (19, 'Hotelbeds',              'hotelbeds'),
  (20, 'Mirai Bookings',         'mirai bookings'),
  (20, 'Mirai Espana',           'mirai espana'),
  (21, 'Paraty Tech',            'paraty tech'),
  (22, 'Roiback',                'roiback'),
  (23, 'Witbooking',             'witbooking'),
  (24, 'Bookassist',             'bookassist'),
  (25, 'The Hotels Network',     'the hotels network'),
  (26, 'Duetto',                 'duetto'),
  (27, 'IDeaS Revenue Solutions', 'ideas revenue solutions'),
  (27, 'IDeaS G3 RMS',           'ideas g3 rms'),
  (28, 'Atomize',                'atomize'),
  (29, 'BEONx',                  'beonx'),
  (30, 'OTA Insight',            'ota insight'),
  (30, 'Lighthouse Intelligence', 'lighthouse intelligence'),
  (31, 'Revinate',               'revinate'),
  (32, 'Cendyn',                 'cendyn'),
  (33, 'Triptease',              'triptease'),
  (34, 'HiJiffy',                'hijiffy'),
  (35, 'Quicktext',              'quicktext'),
  (36, 'Asksuite',               'asksuite'),
  (37, 'Hotelinking',            'hotelinking'),
  (38, 'Civitfun',               'civitfun'),
  (39, 'Hotelkit',               'hotelkit'),
  (40, 'SuitePad',               'suitepad'),
  (41, 'ASSA ABLOY Global Solutions', 'assa abloy global solutions'),
  (41, 'ASSA ABLOY',             'assa abloy'),
  (42, 'Salto Systems',          'salto systems'),
  (43, 'dormakaba',              'dormakaba'),
  (44, 'Onity',                  'onity'),
  (45, 'Adyen',                  'adyen'),
  (46, 'Planet Payment',         'planet payment'),
  (47, 'Sabre Hospitality',      'sabre hospitality'),
  (47, 'Sabre Corporation',      'sabre corporation'),
  (47, 'SynXis',                 'synxis');
