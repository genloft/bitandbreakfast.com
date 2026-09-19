-- =============================================================================
--  022 - separar ciberseguridad de cumplimiento
--
--  "Ciberseguridad y cumplimiento" llevaba desde el catalogo inicial metiendo
--  dos noticias distintas bajo la misma etiqueta: un ataque que resuelve TI
--  esta noche y una multa o una norma que lee legal. Un director de sistemas
--  que sigue la seccion de "Ciberseguridad" para saber si su cadena de
--  hoteles esta en riesgo no quiere enterarse a la vez de la ultima sancion
--  de la AEPD a un tercero, y viceversa.
--
--  El catalogo (lib/bits.php) ya reparte los dos slugs nuevos, con
--  'ciberseguridad-cumplimiento' cayendo en 'ciberseguridad' via
--  bits_categoria_canonica() para lo ya publicado -mismo mecanismo que uso el
--  reparto de pms-gestion o distribucion-revenue-, asi que ningun bit
--  existente se queda sin categoria valida por este cambio.
--
--  Lo que si hace falta es retocar el diccionario y las fuentes seedeadas,
--  para que lo que se publique DESDE AHORA caiga en el lado que toca. El
--  reparto de terminos sigue el que ya separaba en dos bloques de comentario
--  sql/semilla_diccionario.sql y sql/migraciones/013: incidentes y ataques a
--  un lado, normativa y sanciones al otro.
-- =============================================================================

UPDATE diccionario SET categoria = 'ciberseguridad'
 WHERE categoria = 'ciberseguridad-cumplimiento'
   AND termino IN (
     'ransomware', 'data breach', 'brecha de datos', 'ciberataque', 'cyberattack',
     'filtracion de datos', 'vulnerabilidad', 'vulnerability', 'zero-day', 'cve-',
     'caida del sistema', 'outage', 'exploit', 'malware', 'phishing',
     'credenciales robadas', 'ciberseguridad', 'incidente de seguridad',
     'secuestro de datos', 'suplantacion', 'parche de seguridad',
     'autenticacion multifactor', 'doble factor', 'copia de seguridad'
   );

UPDATE diccionario SET categoria = 'cumplimiento'
 WHERE categoria = 'ciberseguridad-cumplimiento'
   AND termino IN (
     'ses hospedajes', 'registro de viajeros', 'rgpd', 'gdpr', 'nis2', 'aepd',
     'sentencia', 'dora', 'multa', 'accesibilidad web', 'proteccion de datos',
     'sancion', 'normativa', 'inspeccion'
   );

-- Trece fuentes seedeadas con el categoria_defecto viejo (sql/semilla_fuentes.sql
-- y migraciones 004, 007, 009): doce son prensa o avisos de incidentes
-- tecnicos -The Hacker News, Dark Reading... hasta INCIBE-CERT-, y solo el
-- comite que redacta el RGPD encaja en normativa.
UPDATE fuentes SET categoria_defecto = 'cumplimiento'
 WHERE categoria_defecto = 'ciberseguridad-cumplimiento'
   AND nombre = 'Comite Europeo de Proteccion de Datos';

-- Todo lo que quede con el categoria_defecto viejo -las doce fuentes de
-- incidentes- cae en ciberseguridad, el mismo lado por defecto que usa
-- bits_categoria_canonica() para lo ya publicado.
UPDATE fuentes SET categoria_defecto = 'ciberseguridad'
 WHERE categoria_defecto = 'ciberseguridad-cumplimiento';
