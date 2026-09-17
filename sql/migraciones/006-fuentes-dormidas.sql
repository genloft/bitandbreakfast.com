-- =============================================================================
--  006 - las fuentes se duermen, no se mueren
--
--  Hasta ahora, cinco fallos seguidos apagaban una fuente para siempre:
--  activa = 0 y a esperar a que alguien lo viera en el panel. Eso esta bien
--  para un feed que ha desaparecido y fatal para todo lo demas, porque este
--  sitio vive en un alojamiento compartido: la IP la comparten miles, y hay
--  cortafuegos -Cloudflare y parecidos- que contestan 403 a esa IP durante un
--  rato y luego dejan de hacerlo. Con la regla vieja, una tarde mala se lleva
--  la fuente por delante y no vuelve nunca.
--
--  Duele especialmente con lo que se publica en espanol, que son cuatro
--  medios contados: perder uno es perder una cuarta parte de lo unico que este
--  sitio puede publicar.
--
--  Asi que ahora se duermen. dormida_hasta dice hasta cuando no se pide, y el
--  plazo crece con los fallos: seis horas, un dia, tres, siete como mucho. El
--  primer intento que salga bien lo borra. activa = 0 queda para lo que es:
--  una decision de una persona.
--
--  Y de paso se despierta lo que la regla vieja dejo apagado, que por la nota
--  se sabe cual fue.
-- =============================================================================

ALTER TABLE fuentes
  ADD COLUMN IF NOT EXISTS dormida_hasta DATETIME NULL DEFAULT NULL AFTER activa;

UPDATE fuentes
   SET activa = 1,
       fallos_consecutivos = 0,
       dormida_hasta = NULL,
       notas = LEFT(CONCAT('Reactivada por la migracion 006. ', notas), 500)
 WHERE activa = 0
   AND notas LIKE 'Desactivada automaticamente%';
