-- =============================================================================
--  020 - fecha de alta de cada fuente
--
--  Hasta ahora nada decia cuando entro una fuente en el catalogo: ni columna
--  ni migracion la guardaba. Con el catalogo a punto de crecer mucho -de
--  cincuenta y pico a varios cientos-, hace falta poder ver el directorio
--  ordenado por lo mas nuevo y saber que se ha ido incorporando cada semana,
--  no solo cuantas hay en total.
--
--  Las fuentes ya existentes se quedan con fecha_alta en NULL: no se sabe de
--  verdad cuando entraron -esta columna no existia-, y ponerles una fecha
--  inventada seria peor que dejarlo en blanco. El panel lo dice como "de
--  antes de esta cuenta" en vez de fingir un dato que no hay. De aqui en
--  adelante, toda fuente nueva -por migracion o desde el panel- lleva la suya.
-- =============================================================================

ALTER TABLE fuentes
  ADD COLUMN IF NOT EXISTS fecha_alta DATE NULL DEFAULT NULL AFTER notas;
