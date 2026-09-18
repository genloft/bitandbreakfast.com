-- =============================================================================
--  016 - afinar lo que la 013 dejo demasiado ancho
--
--  La 013 metio sesenta terminos en espanol y funciono: la portada paso de
--  siete noticias a cuarenta y seis. Pero con la portada entera a la vista se
--  ve lo que se colo por el camino, y son siempre del mismo tipo: politica
--  turistica y economia del turismo. "El turismo replantea su modelo: del
--  crecimiento a la sostenibilidad social" es un buen articulo y no es de
--  tecnologia hotelera.
--
--  La culpa es de los terminos anchos: "sostenibilidad", "ocupacion",
--  "normativa", "comisiones", "inspeccion". Cada uno vale poco, pero dos o
--  tres juntos llegan al minimo sin que el texto hable de un sistema.
--
--  No se quitan -cuando acompanan a un termino de verdad tecnico suman bien-,
--  se bajan a dos puntos: aportan, pero ya no deciden solos.
-- =============================================================================

UPDATE diccionario SET peso = 2
 WHERE termino IN ('sostenibilidad', 'ocupacion', 'normativa', 'comisiones',
                   'inspeccion', 'segmentacion', 'digitalizacion');

UPDATE diccionario SET peso = 3 WHERE termino IN ('sancion', 'transformacion digital');
