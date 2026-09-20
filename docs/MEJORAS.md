# Mejoras pendientes

Revisión completa del sitio —plantillas, generador, hoja de estilo, `.htaccess`
y documentación— cruzada con lo que hoy demandan los directivos del sector.
Fecha de la revisión: 2026-09-20.

Este documento no es una lista de deseos: cada punto dice qué falla o qué
falta, dónde está en el código y por qué merece la pena. Lo que se vaya
haciendo se borra de aquí y se cuenta, como todo lo demás, en las
"Decisiones que conviene no olvidar" del README.

Orden recomendado si hay que elegir cinco:

1. La reserva agéntica merece cobertura propia (§2.4). El tema del año para un director comercial.

## 1. Lo que resta credibilidad hoy

Son fallos, no ideas. Van primero porque un directivo que pilla una
contradicción deja de fiarse del resto de la página, y este sitio no tiene
más activo que ese.

### 1.4 Contraste insuficiente en el texto pequeño

En `plantillas/web/estilo.php`, `--suave: #8d8982` sobre `--papel: #f4f2ee`
da 3,11:1, por debajo del 4,5:1 que pide WCAG AA para texto normal. Y no se
usa en rótulos grandes: se usa a `.68rem` en `.pie-bit .datos`,
`.nube-cuenta`, `.rejilla-cuenta` y una docena de sitios más.

Bajarlo a algo del orden de `#6b6862` lo resuelve sin tocar la estética.
`--apagado: #5f5c57` está bien (5,95:1) y no hay que moverlo.

### 1.5 `sitemap.xml` solo lleva `lastmod` en los días

`plantillas/web/sitemap.php` pone `<lastmod>` en las fichas de día y en nada
más. Temas, medios, Cifras, Tendencias y Glosario van sin fecha, así que un
rastreador no tiene forma de saber cuáles revisitar —que es justo lo que el
propio comentario de cabecera de ese fichero dice que un sitemap existe para
contar—. Los datos ya los calcula `publicar_pendiente()`.

## 2. Contenido para directivos

El sitio contesta hoy «¿qué ha pasado?». Un director general, de sistemas o
de revenue necesita «¿qué significa y qué tengo que hacer?». Varias piezas
cubren ese hueco, y ninguna obliga a inventar nada.

### 2.1 El «por qué importa» vacío es la mayor deuda de contenido

El modo automático lo deja en blanco a propósito, y el motivo es correcto:
es un juicio editorial. Pero para un directivo ese es el producto. Tres
salidas, compatibles las tres con no inventar:

- Un parte semanal escrito a mano. Cinco líneas: lo que cambió, lo que
  caduca, la cifra, la vulnerabilidad, el evento. La infraestructura de
  correo por tandas ya está entera.
- Rellenarlo solo en los bits multifuente, los que ya se pintan en negativo
  (`.bit-multifuente`). Son dos o tres al día, no cuarenta.
- Marcar en portada cuáles lo llevan, para que se note que hay criterio
  humano donde lo hay. Ahora mismo un bit con «por qué importa» y uno sin
  él se distinguen solo leyéndolos enteros.

### 2.2 `/calendario.html`: los eventos del sector

FITURTECHY (21-23 de enero, IFEMA), HIP (16-18 de febrero, décimo
aniversario, con Global CEOs Summit), TIS (6-8 de octubre, FIBES Sevilla),
ITH Hotel Energy Meetings, IHTF. Evergreen, se actualiza dos veces al año y
es de lo primero que un directivo busca al empezar el ejercicio.

Mismo patrón de mantenimiento que Cifras: revisión a mano y aviso al
caducar.

### 2.3 Cifras: digitalización de la hostelería española

Falta un dato que un directivo mira siempre: cómo de digitalizada está la
hostelería española frente al resto de sectores, no solo frente al resto
de Europa.

La encuesta de uso de TIC y comercio electrónico del INE tiene desglose
por rama de actividad -sección I del CNAE, hostelería- y API abierta, así
que es de los pocos grupos que se podrían traer sin depender de que
RateGain, IBM o una encuestadora de viajeros publiquen su informe anual.
Pendiente de una cifra concreta y verificable con fuente directa: la nota
de prensa de la ETICCE (`ine.es/dyngs/Prensa/ETICCE20241T2025.htm`) trae
el desglose sectorial, pero `ine.es` no es accesible desde este entorno de
desarrollo y las fuentes secundarias solo dan una cifra aproximada
("inferior al 15 %", sin precisar) que no llega al nivel de precisión que
exige esta página. Falta comprobarlo contra la tabla original antes de
publicar un número.

### 2.4 La reserva agéntica merece cobertura propia

El tema del año para un director comercial, y hoy cae disuelto en
«Distribución y OTAs»: Booking y Expedia dentro de ChatGPT, el Agentic
Commerce Protocol de OpenAI y Stripe, el Agent Payments Protocol de Google,
y OpenAI retirando Instant Checkout de las transacciones de viaje en marzo
de 2026.

La pregunta que hay debajo —«¿desaparece mi canal directo?»— da para una
categoría propia o, como mínimo, para una página explicativa permanente en
Recursos.

## 3. Funcionalidades

### 3.1 Suscripción por tema y por frecuencia

El alta es hoy todo o nada, y diaria. Un director de sistemas quiere
Ciberseguridad y Cumplimiento, una vez por semana. La tabla `envios` y el
envío por tandas ya soportan la mecánica: es una columna más en la lista de
suscriptores y un filtro en `cron/enviar.php`.

### 3.2 Alertas por palabra clave o por proveedor

«Avísame cuando se hable de Mews, o de ransomware». Es la funcionalidad por
la que un profesional deja su correo.

No choca con la decisión de no hacer fichas de proveedor —«la pregunta de
Mews, no la del hotel»—: aquí el proveedor no es una página que se publica,
es un filtro que elige el lector.

### 3.3 Enseñar los votos

Se recogen desde el commit #17 y no se ven en ninguna parte. «Lo más útil»
(voto) y «lo más leído» (clic) son dos señales distintas, y la primera dice
mucho más que la segunda.

Aplican las mismas dos reglas que ya tiene `mas_leido.php`: fuera de
`publicar_firma()`, para no regenerar el sitio cada vez que alguien vota, y
un mínimo por debajo del cual no se pinta nada.

### 3.4 API pública de lectura, e `indice.json` documentado

`api/candidatos.php` ya abrió el camino de la lectura por API. Un endpoint
público de lo publicado, más documentar el `indice.json` que el buscador ya
descarga, hace que consultoras y escuelas de hostelería citen y enlacen.

Esto no reabre `api/bits.php`: publicar por API es un cambio de proceso
editorial y sigue descartado por el motivo que dice el README. Leer no es
escribir.

Un widget embebible, en cambio, chocaría con el `X-Frame-Options: DENY` del
`.htaccess`. El JSON no.

### 3.5 `/radar.html`: la transparencia como producto

`salud.php` ya reúne datos que ningún medio publica: cuántas fuentes
vigila, cuántas duermen y por qué, cuánto entra y cuánto se descarta. Una
versión pública y maquetada de eso es una señal de rigor enorme ante un
directivo escéptico —y no hay que calcular nada nuevo, solo pintarlo—.

Con el mismo cuidado que ya tiene esa página: cuentas y fechas, nunca
configuración.

### 3.6 Buscador: resaltar y ordenar

`buscarjs.php` hace las facetas bien. Le falta resaltar el término
encontrado dentro del resultado y poder ordenar por fecha además de por
relevancia.

### 3.7 Hoja de estilo de impresión

Un directivo imprime o exporta a PDF el resumen del día para una reunión.
Con una estética de papel ya construida, es media hora de CSS y es de lo
más coherente con la marca que se puede hacer.

## 4. Diseño y usabilidad

### 4.3 La portada es demasiado larga

`web_bits_portada` está en 300 y `.bit:not(.bit-lead)` fija
`height: 28.5rem`. En escritorio, a tres columnas, son unas cien filas: del
orden de 45.000 px de scroll, con todas las fichas del mismo alto y
recortadas con `line-clamp`.

El tope subió de 80 a 300 por un motivo bueno —ochenta se notaba al aprobar
un lote grande—, pero la respuesta a «caben más» no tiene por qué ser
«todas iguales y muy altas». Propuesta: tres niveles de jerarquía —el lead
y los multifuente a tamaño ficha, y el resto como lista compacta de una
línea (titular, medio, tema)—. Cabe el triple de información en un tercio
de scroll, y se lee como la segunda página de un periódico, que es
exactamente la referencia declarada.

### 4.4 Una tira de temas bajo la cabecera

«Seguir tirando del hilo» (`explorar.php`) está al final, y está bien que
esté al final. Pero una fila compacta de temas justo debajo de la cabecera
da navegación lateral sin JavaScript y sin convertirse en un menú: es lo
que hoy falta entre «he llegado a la portada» y «quiero solo
ciberseguridad».

### 4.5 Resumen del día en tres líneas

`/d/<fecha>/` empieza directamente con las fichas. Tres líneas arriba
—cuántas noticias, de qué temas, qué es lo más confirmado— convierten una
lista en una página que se puede mandar por WhatsApp.

### 4.6 Toda la celda debería ser zona de clic

Ahora solo lo son el titular y los iconos del pie. En móvil, un objetivo de
toque del tamaño de la celda cambia por completo la sensación de uso, y en
una retícula de celdas con filete el gesto es obvio.

### 4.7 Los puntos suspensivos perdidos

El `max-height` de respaldo de `.por-que` corta limpio, pero sin decir que
hay más —está documentado en el README y aceptado a conciencia—. Un
degradado de dos píxeles hacia el papel resuelve la ambigüedad entre «está
cortado» y «acaba ahí» sin recuperar el problema del recorte sucio.

### 4.8 Las cifras del radar, separadas del nombre

«Actualizado hace X, N noticias» es la pregunta de quien vuelve, y en
`cabecera.php` compite tipográficamente con el nombre del sitio. Un filete
y un rótulo propio las separan sin devolverlas a la tira negra de la que
vinieron.

## 5. SEO, GEO y distribución

- Sitemap de noticias (`<news:news>`, últimas 48 horas): requisito de
  Google News y Discover, y se genera con datos que ya tiene
  `publicar_pendiente()`.
- Microdatos más ricos, en la línea ya elegida y nunca en JSON-LD:
  `DefinedTermSet`/`DefinedTerm` en el glosario, `Dataset` en Cifras,
  `ItemList` en archivo y temas, y `dateModified`, `articleSection` e
  `isAccessibleForFree` en cada `NewsArticle`.
- Faltan `og:description` y `twitter:card` en `medio.php`, `tema.php`,
  `temas.php` y `sobre.php`.
- RSS con `<category>` por tema: hoy cada item lleva título, enlace, fecha
  y descripción, y nada que permita filtrar.
- `llms.txt`: hacerlo si se quiere, pero sin ilusiones. Ninguna IA grande
  se ha comprometido a leerlo y la guía de Google de mayo de 2026 dice
  explícitamente que no hace falta para AI Overviews ni para AI Mode. La
  apuesta de GEO de este sitio ya está bien hecha y es otra: HTML
  estático, semántico y citable. Lo que sí rinde es decidir a conciencia,
  en `robots.txt`, qué rastreadores de IA entran —si el objetivo es que le
  citen a uno, cerrarlos es tirarse a los pies—.

## 6. Jugar a lo grande

El informe anual. El radar vigila casi doscientas fuentes y lleva un año de
bits clasificados por tema, ámbito e idioma. Eso es un conjunto de datos
que no tiene nadie más en español: no «qué opina el sector», sino qué se
contó, cuándo y cuántas veces.

Un PDF anual —«Estado de la tecnología hotelera en España»— es lo que
convierte un agregador en fuente citada, y es exactamente el material que
un directivo lleva a un comité. El cálculo ya está casi entero en
`publicar_tendencias()`: lo que falta es la ventana larga y alguien que
escriba las conclusiones, que es justo la parte que el modo automático no
debe tocar.

## Fuentes consultadas

De la revisión del sector, septiembre de 2026:

- Hotel Management — 2026 hotel technology outlook
- Hospitality Net — The Future of Hotel Technology: 30 Key Trends for 2026
- Stayntouch — 2026 Hotel Tech Outlook
- Revista Gran Hotel — Verifactu obligatorio desde enero de 2026
- Chekin — guía de SES.HOSPEDAJES
- EUR-Lex — Reglamento (UE) 2024/1028
- Satec — Regulación digital europea 2026: NIS2, DORA, AI Act y CRA
- Audidat — Transposición española de NIS2 en 2026
- ITH — FiturtechY
- TecnoHotel — HIP 2026
- Tourism Innovation Summit
- Skift — OpenAI retira Instant Checkout de viajes
- Hospitality Net — quién cobra la comisión en la reserva agéntica
- CISA — Known Exploited Vulnerabilities Catalog
- Passionfruit — llms.txt y la postura de Google en 2026
