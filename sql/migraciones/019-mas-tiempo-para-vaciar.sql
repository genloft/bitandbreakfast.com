-- =============================================================================
--  019 - mas tiempo para vaciar la cola
--
--  La 017 devolvio a la cola todo lo que se habia descartado solo por el
--  idioma, y la cola paso de 842 candidatos a 1.329. Con la medicion de la
--  primera pasada con traductor -diez bits en veinticinco segundos, o sea dos
--  segundos y medio por bit, que es lo que tarda DeepL en contestar- la 018 se
--  queda corta: noventa segundos son treinta y seis bits por pasada, y con el
--  cron cada hora eso es mas de un dia.
--
--  Ciento cincuenta segundos son unos sesenta bits por pasada. Sigue siendo
--  tiempo esperando a la red y no quemando procesador, y el cerrojo evita que
--  dos pasadas se solapen si alguna se alarga.
--
--  Esto es para el atasco de hoy. Cuando la cola baje, la escritura sale
--  enseguida si no hay candidatos: el presupuesto largo deja de gastarse solo,
--  sin que nadie tenga que volver a bajarlo.
-- =============================================================================

UPDATE ajustes SET valor = '150' WHERE clave = 'presupuesto_auto';
UPDATE ajustes SET valor = '300' WHERE clave = 'presupuesto_cron_total';
