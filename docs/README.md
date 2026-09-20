# Bit & Breakfast

Agregador de noticias de tecnología hotelera en español. Rastrea casi cien
fuentes, agrupa las que cuentan la misma noticia, las puntúa, y lo que pasa las
puertas se publica en el momento como HTML estático. Los elige la puntuación:
el panel de curación sigue ahí para escribirlos a mano, pero ya no es el único
camino.

**La web se ordena por el día en que el radar descubrió cada noticia**, no por
ediciones. Una edición solo existía cuando se cerraba, así que lo de hoy no se
veía hasta mañana; un día existe en cuanto cae en él la primera noticia. La
portada es el río de los últimos días, cada día tiene su página en
`/d/AAAA-MM-DD/` y el boletín manda lo del día anterior.

La promesa al lector: **lo que ha aparecido hoy en tecnología hotelera, en
español y con enlace a la fuente.**

## Restricciones de partida

- Alojamiento compartido de Hostinger, coste cero. Ni VPS ni contenedores.
- PHP 8.1 y MySQL/MariaDB. Sin framework, sin Composer en producción.
- Sin build de frontend: CSS a mano y un único JavaScript propio, el del buscador.
- Todo proceso largo va por lotes con puntero persistente, nunca en una pasada.
- **Modo automático activado**: los racimos mejor puntuados se publican solos.
  Era al revés —ningún bit sin revisión humana— y se cambió a petición del
  dueño del sitio. Se apaga con el ajuste `auto_publicar` a 0.

## Estructura

```
index.php     arranque: sin configuración lleva al instalador, con ella a la portada
salud.php     estado del radar en JSON: cron, esquema, cola, fuentes y contenido
instalar.php  instalador web; se borra solo al terminar
config/       configuración (config.php no está en el repositorio)
lib/          utilidades: PDO, feeds, robots.txt, texto, URLs, agrupación,
              puntuación, reglas del bit, sesión del panel, instalador,
              migraciones, cliente SMTP y lista de suscriptores
cron/         tareas programadas; tareas.php es el despachador único
              (ingesta, procesar, auto, publicar, enviar, mantenimiento)
api/          endpoints públicos: alta, confirmación, baja y redirección contada
panel/        zona privada de curación: cola, edición del bit y cierre
plantillas/   plantillas: web/ publicada, panel/ e instalador
publico/      salida estática generada por cron/publicar.php (no se versiona)
pruebas/      scripts de prueba sin framework
sql/          esquema, semillas y migraciones/ (cambios que se aplican solos)
docs/         esta documentación, INSTALACION.md y CRON.md
```

## Puesta en marcha

Ver [INSTALACION.md](INSTALACION.md), y [CRON.md](CRON.md) para entender qué hace
el cron y qué esperar cada día. Resumen: crear una base de datos vacía en
hPanel, clonar el repositorio en `public_html` desde hPanel → GIT, abrir el
dominio —mientras no exista `config/config.php` todo redirige al instalador,
que pide los datos, importa el esquema y las semillas, escribe la configuración
y se borra a sí mismo— y crear la tarea cron horaria que da en pantalla.

## Pruebas

Sin framework. Comparan salida esperada contra salida real y devuelven código
de salida 1 si algo falla.

```bash
php pruebas/texto.php
php pruebas/canonica.php
php pruebas/instalacion.php
php pruebas/procesar.php
php pruebas/panel.php
php pruebas/web.php
php pruebas/correo.php
php pruebas/auto.php
php pruebas/comprobar_feeds.php
```

Las ocho primeras no tocan la base de datos. La novena sí la lee, y sale a la
red a comprobar que las fuentes del catálogo siguen vivas.

Y una más, `pruebas/humo.php`, que monta el esquema y las semillas desde
cero, mete items de prueba y ejecuta el procesado entero. **Es destructiva**:
empieza por un `DROP TABLE`. Exige `BITB_HUMO=1` y las credenciales por
variables de entorno, y se niega a arrancar si existe `config/config.php`, así
que no puede ejecutarse por accidente contra la base buena.

Todo esto corre solo en cada push: `.github/workflows/pruebas.yml` pasa
`php -l` a todos los ficheros, ejecuta las pruebas sin base de datos y levanta
un MariaDB 10.6 para la de humo.

## Estado

| Fase | Alcance | Estado |
|---|---|---|
| 1 | Esquema, utilidades de texto y URL, ingesta, semillas | completada |
| 2 | Agrupación, puntuación, `cron/procesar.php` | completada |
| 3 | Panel de curación | completada |
| 4 | Generador estático, archivo, RSS | completada |
| 5 | Correo: buzón propio, alta con doble confirmación, baja y envío | completada |
| 6 | Fichas de proveedor, buscador, votos, redacción asistida | fichas, buscador, clics y votos hechos |

## Decisiones que conviene no olvidar

- **La similitud de titulares no cruza idiomas.** Jaccard sobre shingles de
  tres caracteres da casi cero entre un titular inglés y su equivalente en
  español. La agrupación entre idiomas se apoyará en proveedores y cifras
  compartidas, no en el texto.
- **La agrupación tiene dos puertas.** La similitud de titulares agrupa por sí
  sola a partir de 0,45. Por debajo, hasta 0,30, solo agrupa si las dos
  noticias mencionan al menos dos proveedores en común. Esa segunda puerta es
  la única que cruza idiomas, y por eso `sql/semilla_proveedores.sql` no es
  decoración: sin catálogo de proveedores, la mitad del agrupador no existe.
- **Los racimos se cosen, no solo se abren.** Si un item se parece mucho a dos
  racimos a la vez, es que esos dos racimos cuentan lo mismo y nacieron
  separados porque cuando llegaron no había nada que los uniera. Ese es el
  único momento en que se puede saber, así que es ahí donde se fusionan. Sin
  esa costura, la misma noticia acabaría dos veces en la misma edición.
- **Un fallo transitorio no descarta una noticia.** Un interbloqueo de InnoDB
  no dice nada del item: se queda en la cola y se reintenta. Solo se descarta
  lo que falla de forma determinista, porque `descartado` es indistinguible
  del descarte editorial y no habría forma de saber qué se perdió.
- **La puntuación vive en dos niveles.** El item vale por su fuente, su
  diccionario, su frescura y sus proveedores. El racimo vale lo que su mejor
  item más un extra por cada fuente distinta que cuenta la misma noticia, con
  tope en cinco: que la cuenten seis medios en vez de cinco ya no añade
  información, y sin tope cualquier nota de prensa muy distribuida ganaría a
  una exclusiva buena.
- **El modo automático no inventa.** Coge el titular del racimo, el resumen
  que publica la propia fuente y la lista de medios que lo cuentan. No escribe
  análisis y deja el «por qué importa» vacío, porque eso es un juicio
  editorial y ahí no hay nadie para hacerlo. Todo lo que publica es
  comprobable, y los bits quedan marcados como `redactado_por = 'ia'`.
- **La web tira de la cadena mientras el cron no conteste.** Cada visita a la
  portada, como mucho una cada cinco minutos, empuja un paso: rastrear,
  agrupar, publicar y generar. Ocurre en dos casos: cuando no hay nada
  publicado todavía y cuando `cache/.cron` —la marca que deja el despachador
  al arrancar— lleva más de dos horas sin tocarse. El cron se configura en el
  panel del alojamiento, fuera del repositorio, y equivocarse allí es fácil:
  ya pasó. Mantenerse con las visitas es más lento y más tosco, pero la
  diferencia entre un radar lento y un radar muerto no es de grado. En cuanto
  el cron vuelve a latir, esto se apaga solo y el sitio vuelve a ser estático.
- **`/salud.php` cuenta lo que no se ve.** Cuándo corrió el cron, cuántos
  items esperan en la cola, cuántas fuentes fallaron hoy y qué versión de
  criterios lleva aplicada lo publicado. Existe porque el sitio se mantiene
  sin acceso al servidor: sin esto, una portada quieta puede ser «no hay
  noticias» o «la ingesta lleva dos días fallando», y desde fuera no hay forma
  de distinguirlo. Solo devuelve cuentas y fechas, nunca configuración ni
  nada que identifique a nadie.
- **Una fuente que falla se duerme, no se muere.** Cinco fallos seguidos la
  apagaban para siempre, y eso solo es correcto cuando el feed ha
  desaparecido. Este sitio vive en un alojamiento compartido: la IP la
  comparten miles y hay cortafuegos que contestan 403 durante unas horas a
  quien no ha hecho nada. Con la regla vieja, una tarde mala se llevaba la
  fuente por delante y nadie volvía a encenderla. Ahora duerme un plazo que
  crece —6 h, 1 día, 3, 7— y la despierta el primer intento que sale bien.
  Duele sobre todo en español, que son cuatro medios contados: perder uno es
  perder un cuarto de lo único que este radar puede publicar.
- **La puntuación no sabe de qué va la noticia, y por eso hay puertas.** El
  diccionario puntúa palabras, no contextos: «malware» o «agente de IA» valen
  lo mismo en una noticia sobre un PMS que en una sobre Outlook. Antes de
  escribir un bit, el modo automático comprueba cinco cosas más, y cada una
  nació de algo que se publicó y no debería:
  1. Que el sector aparezca en el texto (`auto_es_del_sector`). Es la puerta
     más barata y la que más basura para: el juzgado de California, los
     botones de Copilot, el rastreador de despidos de Crunchbase.
  2. Que tenga cuerpo. Un bit que solo dice quién lo publica no ahorra el clic.
  3. Que cuente una noticia y no cinco (`auto_es_recopilatorio`): hay boletines
     que meten su resumen semanal en una sola entrada del feed.
  4. Que no sea material promocional (`auto_es_promocional`) ni una guía
     (`auto_es_didactico`). Una guía no caduca: si entra una vez, entra
     siempre, y desplaza a lo que sí ha pasado esta semana.
  5. Que no sea una entrevista (`auto_es_entrevista`). No son malas; no son un
     hecho.
  Todas son heurísticas, así que descartan candidatos —que no cuesta nada— y
  solo retiran algo ya publicado mientras su edición no se haya enviado por
  correo. Una edición enviada es un hecho consumado.
- **Ningún bit llega a una edición sin pasar por el formato.** El titular
  cabe en 120 caracteres, el cuerpo entre 25 y 110 palabras y el "por qué
  importa" es obligatorio. Un borrador se guarda como sea, pero aprobarlo
  exige cumplirlo: la promesa de los cinco minutos se rompe aquí o no se
  rompe en ningún sitio.
- **La cuota de fuentes españolas y europeas avisa, no bloquea.** Es una
  intención editorial, y una semana floja en Europa no puede impedir el envío.
- **La web se genera, no se sirve.** Lo publicado se convierte en ficheros
  dentro de `publico/` y a partir de ahí Apache los sirve sin tocar PHP ni la
  base de datos. Es lo único que aguanta una portada compartida de
  golpe, y en un alojamiento compartido no hay plan B.
- **Todo en español, y diciendo cuándo es traducido.** Durante meses la regla
  fue no traducir: entraba solo lo que alguien contara en español. La razón era
  buena —traducir a máquina es poner en boca de un medio algo que no ha
  dicho— y el efecto, malo: de casi cien fuentes publicaban ocho, y la portada
  se quedaba vacía mientras el radar leía mil trescientas entradas al día. Una
  promesa que se cumple dejando la casa vacía es una excusa.

  Ahora se traduce con DeepL, con tres reglas: titular y resumen y nunca el
  artículo —el artículo es del medio y se lee en el medio—; cada bit traducido
  lo dice en su cara y mantiene el enlace y el nombre de la fuente; y solo se
  traduce lo que ya pasó todas las puertas, para no gastar cuota en lo que se
  va a descartar. **Sin clave de DeepL, la puerta del idioma se cierra sola**
  aunque el ajuste diga lo contrario: quien lo apaga cuenta con que hay
  traductor, y sin él lo que saldría no es una web en español con más
  contenido, es una portada de titulares en inglés.
- **Un icono por temática, y de una sola lista.** Los once se dibujan en
  `plantillas/web/iconos.php` y de ahí salen la edición, los filtros, los
  resultados y el archivo: una lista, no cuatro copias. Los colores por tema
  se quitaron con el cambio a papel —en una retícula en blanco y negro, once
  colores son un arcoíris y no una guía—, pero siguen definidos por si vuelven.
- **La portada se lee con el pulgar.** El sumario va antes que los bits porque
  un radar tiene que decir en diez segundos si esta semana traía algo. Todo lo
  demás es consecuencia de eso.
- **La lista de correo se guarda aquí, y no era el plan.** El plan era que
  viviera entera en el proveedor —que ya sabe gestionar bajas, rebotes y doble
  confirmación mejor de lo que se escribiría aquí—, y ese camino sigue estando
  para el día que haya cuenta de proveedor. Pero el correo que hay es un buzón
  SMTP del propio alojamiento, y con un buzón SMTP la lista no puede vivir en
  ningún otro sitio. Lo que no cambia es lo que se guarda: dirección, estado,
  fechas y una huella HMAC de la IP del alta. Ni nombre, ni aperturas, ni
  clics por persona.
- **Quien apunta a alguien en la lista es el clic, no el formulario.**
  Cualquiera puede escribir la dirección de otro; solo el dueño del buzón
  puede pulsar el enlace de confirmación, que es de un solo uso y caduca en
  tres días. Y la baja es un clic sin preguntas y sin sesión: cualquier
  fricción ahí solo consigue que marquen el correo como spam, que para un
  boletín es mucho peor que perder un lector.
- **La barra de arriba contesta la única pregunta de quien vuelve.** Cuándo se
  actualizó esto, cuántas noticias entraron y cuántas pasaron al archivo. Es
  una foto del momento en que se generó la página, no un dato en vivo: el sitio
  es HTML estático y esa es la razón de que aguante. Si no ha cambiado nada, lo
  dice; escribir «0 noticias nuevas» es ruido con forma de dato.
- **El delta se calcula con dos ajustes, no con un histórico.** Cuándo fue la
  última generación y cuántos bits había. Lo nuevo es lo que ha crecido; lo
  archivado, lo que esos nuevos han empujado fuera de la portada. Dos números
  contestan la pregunta; un diario de operaciones habría sido otra tabla que
  mantener.
- **El cron manda un parte por correo al terminar.** Con el cron cada cinco
  minutos, «uno por pasada» serían doscientos ochenta y ocho al día por el
  mismo buzón que envía el boletín, que tiene límite por hora. Por eso hay un
  mínimo de una hora entre avisos y lo que ocurre mientras tanto no se calla:
  se suma y sale en el siguiente. Se elige en el panel → Correo.
- **Una migración que falla para la cola entera, y eso tiene que verse.** El
  despliegue trae el código nuevo antes de que el cron aplique la migración, así
  que hay una ventana en la que el código pide algo que no existe. Si la
  migración además falla, la ventana no se cierra nunca y el síntoma no es un
  error: es que el sitio deja de publicar. `/salud.php` lo dice ahora —última
  aplicada, cuántas hay, el error y el motor— y el código pide lo accesorio con
  `bd_columna()` o en su propia consulta, para que no arrastre a lo principal.
- **El boletín se manda por tandas, y la tabla `envios` es la que lo permite.**
  Un buzón de alojamiento compartido tiene un límite de correos por hora, y
  pasárselo no devuelve un error amable: bloquea el buzón. Se manda un puñado
  por pasada del cron y se retoma donde se quedó, y por eso hace falta saber a
  quién se le ha mandado ya. Se anota también el fallo: si no, una dirección
  muerta se reintentaría eternamente y esa edición no terminaría de enviarse
  nunca.
- **Cada envío lleva su enlace de baja y la cabecera `List-Unsubscribe`.** Con
  esa cabecera, el cliente de correo enseña su propio botón de baja encima del
  mensaje; quien se quiere ir lo usa en vez de marcar el correo como spam, que
  es lo que hunde la reputación de un dominio. Y no hay píxel de seguimiento
  ni enlaces contados por persona: no hace falta para escribir un radar.
- **La contraseña del buzón se teclea en el panel, y solo ahí.** Se guarda en
  `config/correo.php`, que no está en el repositorio, lo escribe el propio
  panel con permisos 0600 y Apache no lo sirve. No está en la base de datos a
  propósito: una contraseña en una tabla acaba en un volcado, y un volcado
  acaba viajando por correo.
- **Los cambios de esquema se aplican solos.** Un fichero por cambio en
  `sql/migraciones/`, y el despachador los aplica en orden antes de cualquier
  tarea. Antes, cada tabla nueva era una visita a phpMyAdmin, que es
  exactamente el tipo de tarea que este proyecto le quita de encima a su
  dueño; en la práctica significaba que el cambio no se hacía y el código
  nuevo convivía con la base vieja.
- **Al que ya estaba suscrito se le dice lo mismo que al que no.** Distinguir
  las dos respuestas permitiría averiguar quién está en la lista probando
  direcciones.
- **El redirector va firmado.** `api/ir.php` cuenta el clic y redirige, pero
  solo si la URL trae el HMAC del bit. Sin esa firma sería un redirector
  abierto, y un redirector abierto en un dominio que manda correo se convierte
  en munición para phishing en cuestión de semanas. De quien pulsa no se
  guarda nada: ni IP ni identificador, solo el HMAC del agente de usuario para
  poder descontar los escáneres de correo.
- **Las fichas de proveedor salen solas.** Un bit cuelga de un racimo y los
  proveedores se detectan en los items de ese racimo, así que la ficha de Mews
  es una consulta, no una tabla que mantener. Solo se generan las de los
  proveedores con algo publicado: cuarenta y siete fichas vacías no ayudan a
  nadie y además son mala señal para un buscador.
- **El explorador no tiene servidor.** Se descarga `indice.json` y se busca y
  filtra en el navegador, así que el sitio sigue siendo ficheros estáticos: no
  hay endpoint que tumbar ni que limitar. El texto buscable se normaliza en
  PHP con `texto_normalizar()` y el navegador aplica exactamente las mismas
  reglas sobre lo que teclea el lector.
- **Los filtros suman dentro de un grupo y restan entre grupos.** Dos
  temáticas es «una u otra»; una temática y un idioma es «las dos cosas». Es
  lo que espera cualquiera que haya usado una tienda, y evita el callejón de
  elegir dos idiomas y no obtener nada. Los recuentos de cada opción se
  calculan con el resto de filtros aplicados pero sin el propio grupo, que es
  lo que impide que un filtro te lleve a cero resultados.
- **El ámbito no es el país.** `fuentes.region` dice si un medio cubre España,
  Europa o el mundo; no se guarda el país de cada medio. Llamarlo «país» sería
  prometer una precisión que el dato no tiene.
- **Papel y retícula de filetes.** El sitio ha pasado por un oscuro elegante y
  por un retrofuturismo con rejilla; los dos hacían que pareciera una
  aplicación. Es un agregador de noticias, así que se maqueta como un
  periódico: fondo hueso, tinta negra, cada noticia en una celda con filete de
  un píxel y las celdas compartiendo borde. Tres voces tipográficas —condensada
  en mayúsculas para rótulos y aperturas, sans en negrita para titulares, serif
  para el texto seguido— y un solo acento rojo que sale dos o tres veces por
  página.
- **Sin fotos, y a propósito.** Este sitio agrega titulares ajenos: las fotos
  son de sus medios y no se toman prestadas. Un agregador con huecos grises
  donde deberían ir imágenes parece roto; sin ellas, con el peso en la
  tipografía y los filetes, parece deliberado.
- **Sin índice lateral.** Lo hubo y se quitó: repetía los titulares que estaban
  dos dedos más abajo, se apelotonaba en una columna estrecha y no había scroll
  que indexar. Un índice sirve cuando hay mucho que recorrer.
- **`item_token`** existe para no comparar cada item nuevo contra toda la
  ventana de 72 horas. Sin ese índice invertido, agrupar no cabe en el límite
  de tiempo del alojamiento.
- **Los alias de proveedor van en tabla propia**, no en una columna JSON:
  MariaDB trata JSON como texto largo y no se puede indexar.
- **El token del voto tiene que ser distinto por destinatario.** Si fuera el
  mismo para todos, la restricción de unicidad dejaría un solo voto por bit en
  todo el mundo.
- **El User-Agent lleva prefijo `Mozilla/5.0 (compatible; …)`** porque varias
  fuentes devuelven 403 a cualquier cosa que no lo tenga. El nombre del bot y
  la URL de contacto siguen siendo visibles.
- **Los datos de schema.org van en microdatos, no en JSON-LD.** Un `<script
  type="application/ld+json">`, aunque no ejecute nada, sigue siendo un
  `<script>`: cae bajo el mismo `script-src 'self'` que el resto de la
  página, y esta web no lleva ni uno en línea a propósito. Los atributos
  `itemscope`/`itemprop` describen lo mismo -titular, fecha, fuente original,
  editor- sin abrir esa puerta.
- **`sitemap.xml` solo lista lo que sigue vivo.** Los mismos `$dias`, `$temas`
  y `$medios` que ya calcula `publicar_pendiente()` para el resto de páginas,
  no una consulta aparte. Un día que `publicar_barrer()` ya ha borrado de
  `publico/d/` no puede aparecer aquí: un sitemap con enlaces a 404 le dice a
  Google que el sitio no se cuida.
- **"Lo más leído" no entra en `publicar_firma()`.** Esa firma decide si se
  regenera el sitio entero, y los clics cambian constantemente mientras que
  las noticias no. Metería el contador ahí habría significado reescribir
  todo cada vez que alguien pulsara un enlace, justo lo que la firma existe
  para evitar. El ranking se congela hasta la siguiente vez que haya algo
  nuevo que publicar, como el resto de cifras de la cabecera: es una foto, no
  un dato en vivo.
- **Por debajo de tres bits con dos clics cada uno, "lo más leído" no se
  pinta.** Una caja de "lo más leído" con una sola entrada no informa,
  delata que casi no hay tráfico. Mejor no enseñar nada.
- **`/estadisticas.html` es la única página que no sale de la base propia.**
  Todo lo demás se genera a partir de lo que el radar ha rastreado; esta
  compara cifras de organismos ajenos -INE, Eurostat, IBM, AEPD, informes del
  sector- que no hay forma de convertir en una consulta porque son media
  docena de fuentes distintas, con metodologías distintas. Vive en un array
  escrito a mano en `plantillas/web/estadisticas.php`, con su fecha de
  revisión también escrita a mano -nunca `gmdate()`- porque esta página no
  se pone al día sola cuando el cron regenera el sitio por cualquier otro
  motivo: alguien tiene que volver a mirarla, y hay que poder saber cuándo lo
  hizo por última vez.
- **`/tendencias.html` es lo contrario: se regenera sola.** Compara los
  últimos 90 días con los 90 anteriores, tema a tema, con los propios bits
  publicados. Dos ventanas iguales y no un trimestre natural -enero a
  marzo-, porque comparar un trimestre a medio llenar contra uno ya cerrado
  siempre da una caída falsa: el que empieza ha tenido menos días para
  acumular bits. Por debajo de cuatro bits entre los dos periodos no se
  enseña nada, la misma regla que "lo más leído": un tema que pasa de uno a
  tres bits "sube un 200 %" y no ha pasado nada. Y no dice si subir es bueno
  ni si bajar es malo -eso es un juicio, y el modo automático no inventa
  juicios-, solo cuenta y ordena por el movimiento más grande.
- **Los destacados de la portada son dos, y solo en la portada.** Cifras y
  Tendencias, arriba del río de noticias. Más de dos habría sido un menú
  escondido dentro de otro menú, y ponerlos en cada página del sitio los
  convertiría en ruido de fondo en vez de en un aviso. El de Tendencias
  cambia solo -toma el primer resultado de `publicar_tendencias()`, el mismo
  dato que ya calcula la página-; el de Cifras es un texto fijo porque esa
  página la revisa una persona, no el cron.
- **El cron no trae las cifras solas, avisa cuando hay que traerlas.** INE y
  Eurostat tienen una API abierta y estable; RateGain, IBM, AEPD y las
  encuestas de viajeros no -son informes y notas de prensa, no un servicio
  que se pueda leer sin vigilancia-, y un cron que las raspara se rompería
  con el primer cambio de maquetación de cualquiera de ellas, en silencio.
  Como no se puede automatizar todo sin inventar una parte, no se automatiza
  ninguna: `cron/mantenimiento.php` -la tarea que estaba anunciada en el
  despachador desde el principio y nunca había tenido fichero- comprueba
  cada día si `/estadisticas.html` lleva más de `CIFRAS_CADUCIDAD_DIAS`
  (120, unos cuatro meses) sin que una persona la revise, y si es así manda
  un aviso por el mismo buzón que ya usa el cron para su propio parte. Un
  aviso por revisión, no uno por día: `ajustes.cifras_aviso_revisado` guarda
  para qué fecha de revisión ya se avisó, así que actualizar la página
  también apaga el aviso. La fecha de revisión y el umbral viven en
  `lib/cifras.php`, no dentro de la plantilla, para que el mantenimiento
  pueda leerlos sin ejecutar la página entera.
- **Los destacados de la portada son un titular, no un enlace de menú.**
  La primera versión era dos cajas de papel con un rótulo y un título: se
  leía como dos entradas más del menú, no como algo que mereciera pararse.
  Ahora cada uno es una cifra a la escala del nombre de la cabecera, sobre
  fondo negro, con una línea corta debajo de qué va. La cifra de Tendencias
  sale sola del primer resultado de `publicar_tendencias()`, la misma
  regla que ya tenía el teaser; la de Cifras sigue fija porque esa página
  la revisa una persona.
- **`/glosario.html` es un diccionario, no una cifra.** Un bit menciona
  "NDC" o "RevPAR" sin explicarlo -explicarlo en cada noticia sería
  repetirse-, y hasta ahora quien no conocía la sigla tenía que buscarla
  fuera. Vive en un array escrito a mano, como `/estadisticas.html`, pero a
  diferencia de esa página no lleva fecha de revisión ni aviso de
  mantenimiento: un PMS sigue siendo un PMS el año que viene, así que no
  hay nada que pueda caducar aquí. Cada término enlaza opcionalmente a un
  tema del catálogo de `bits_categorias()`, para quien ya sabe qué
  significa la sigla y quiere ver qué se ha publicado sobre ello.
- **Cada tema tiene su propio RSS, en `t/<tema>/feed.xml`.** A quien solo
  le interesa Pagos y fraude o Revenue y RMS, suscribirse al feed general
  es suscribirse a diez temas para leer uno. Vive en `feed_tema.php`,
  aparte de `feed.php`, para que un cambio pensado para el feed de un tema
  no pueda romper el general por accidente. Como esa carpeta ya no
  contiene solo `index.html`, `publicar_barrer()` tiene que borrar también
  `feed.xml` antes de intentar `rmdir()` la carpeta de un tema retirado:
  sin eso, la carpeta se queda huérfana para siempre porque `rmdir()` se
  niega a vaciar algo que no está vacío.
- **Compartir apunta al permalink del bit, nunca a la fuente.** Cada bit
  lleva "compartir en WhatsApp" y "compartir en LinkedIn", con `wa.me` y
  `linkedin.com/sharing/share-offsite` -intents por URL, ni script ni
  píxel de terceros, nada que pese o que llame a nadie hasta que alguien
  pulsa-. El enlace compartido es siempre `/d/<día>/#bit-<id>`, no la URL
  de la fuente: es lo único de los dos que este sitio puede prometer que
  sigue existiendo, y es donde vive el contexto -las demás fuentes que lo
  cuentan, el tema, el «por qué importa»- que la fuente sola no tiene.
- **`/salud.php` también cuenta la caducidad de Cifras.** El bloque
  `cifras` dice desde cuándo no se revisa `/estadisticas.html` y si ya ha
  cruzado el umbral de `cron/mantenimiento.php`. No hace nada que ese cron
  no haga ya -el aviso por correo sigue siendo el que de verdad avisa-,
  pero es la misma pregunta que el resto de esta página contesta para todo
  lo demás: se puede comprobar desde fuera sin esperar a que llegue un
  correo.
- **Cifras, Tendencias y Glosario viven bajo un "Recursos" en el menú.**
  Cada una llegó a la barra al día siguiente de nacer y, sumadas a
  Portada, Temas, Medios, Archivo y Qué es, la dejaron en diez enlaces:
  demasiados para leerse de un vistazo, que es justo lo que una cabecera
  tiene que permitir. Las tres comparten una naturaleza -son consulta, no
  la lectura diaria de lo que ha entrado hoy- y por eso se agrupan bajo un
  `<details>`/`<summary>` nativo en `cabecera.php`: sin una línea de
  JavaScript, porque abrir y cerrar un desplegable es exactamente lo que
  el navegador ya sabe hacer solo. El resto del menú no se toca: Portada,
  Temas, Medios, Archivo y Qué es siguen siendo la lectura de todos los
  días y se quedan al primer nivel.
- **`/estadisticas.html` dice hasta cuándo vale su "revisado el".** La
  fecha de revisión sin más era una promesa sin plazo: quien la leía no
  tenía forma de saber si "revisado el" seguía significando algo hoy o si
  llevaba diez meses caducada. `cifras_limite_revision()`, en
  `lib/cifras.php`, suma `CIFRAS_CADUCIDAD_DIAS` a la fecha de revisión -el
  mismo umbral que ya usa `cron/mantenimiento.php` para avisar por
  correo, contado hacia delante en vez de hacia atrás- y la página lo
  enseña como "con revisión antes del…". No es una promesa nueva, es la
  misma que ya existía hecha visible.
- **El banner de Cifras en portada no repite el número a mano.** Citaba
  "21,1 %" como literal suelto, sin nada que lo atara al array `$grupos` de
  `estadisticas.php` de donde en realidad sale ese dato -el propio
  comentario de `lib/cifras.php` ya avisaba de que solo debían quedar dos
  sitios que tocar el día que cambien los datos, y portada.php era un
  tercero sin documentar-. Ahora los dos leen `cifras_valor_ia_espana()`,
  en `lib/cifras.php`: cambiar el número una vez lo cambia en las dos
  páginas a la vez, en vez de dejar que portada.html siga citando un dato
  que la propia página de Cifras ya haya dejado atrás.
- **`/sobre.html` menciona Cifras, Tendencias, Glosario y Medios.** La
  página que explica qué es el sitio solo hablaba del río de bits: nada
  decía que también hay un cuadro de mandos comparando España con el
  mundo, un termómetro de qué tema sube y cuál baja, un glosario de siglas
  o una ficha por medio. Para un directivo que llega por un enlace suelto
  y quiere saber en veinte segundos qué puede sacar de este sitio, callarse
  la mitad de lo que ofrece es la misma clase de fallo que un `<details>`
  que nunca se abre. Añadido un párrafo "Más que el día a día" con enlace
  a las cuatro.
- **El buscador filtra por proveedor, no solo por texto libre.** `bit['v']`
  ya llevaba los nombres de los proveedores mencionados, pero unidos en una
  frase, solo para la búsqueda de texto: no había forma de marcar "Mews" y
  ver solo esos bits. Nueva faceta `pv` en el índice -lista, no cadena,
  porque un bit puede mencionar más de un proveedor a la vez- y `pasa()` en
  `buscarjs.php` distingue ahora facetas de un solo valor (tema, ámbito,
  idioma, fuente) de facetas de varios (proveedor): coincide con cualquiera
  de los marcados, no con todos, igual que ya sumaban entre sí los valores
  de una misma faceta escalar. Sin librería de pruebas para JavaScript en
  este proyecto, se verificó a mano con un guion de Node de usar y tirar
  -no vive en el repositorio- antes de escribir esto: las facetas
  existentes (escalares) se comportan byte a byte igual que antes.
- **Todas las páginas enlazan el RSS general para autodescubrimiento,
  menos la que no tiene nada que ofrecer todavía.** `temas.html` y
  `medios.html` eran las únicas páginas completas del sitio sin
  `<link rel="alternate">`: un lector de feeds que llegara ahí no
  encontraba el camino de vuelta al feed. `provisional.php` -la portada de
  antes del primer bit- sigue sin él a propósito: nada que enlazar cuando
  todavía no hay ni una noticia.
- **Los votos, la pieza que quedaba pendiente de la fase 6, ya escriben en
  la tabla `votos`.** Cada bit del correo lleva ahora "¿te ha servido esta
  noticia? Sí / No" -dos enlaces de un clic, igual que la baja-, en
  `lib/votos.php` y `api/votar.php`. El token que exige la clave única
  `(bit_id, token)` es el propio HMAC de bit y destinatario a la vez, no
  solo de bit: así dos destinatarios nunca chocan entre sí y el mismo
  destinatario pulsando el mismo enlace dos veces cuenta un solo voto, sin
  guardar su identidad en la tabla. `envio_texto()` y `envio_html()` siguen
  siendo puras -reciben los enlaces ya hechos en `$votos_urls`, no llaman a
  nada que firme ni que toque la base-, y `cron/enviar.php` es el único
  sitio que construye esos enlaces, porque es el único que sabe a quién le
  toca cada correo. El mismo riesgo que ya acepta `api/baja.php` sin
  evitarlo -un escáner de correo que abra los enlaces del cuerpo antes de
  que el destinatario lo lea- se documenta en `api/votar.php`, no se
  resuelve con máquinaria nueva: aquí la clave única es la protección real,
  no falta nada más. El voto se queda sin mostrar en ningún sitio público
  por ahora, igual que los clics: es una señal para quien escribe, no un
  contador de cara al lector. El `Content-Security-Policy` de `.htaccess`
  menciona "los votos" junto al buscador como los dos sitios donde
  cualquier script iría en su propio `.js`, nunca en línea; esta pieza no
  añade ningún script -son enlaces `<a href>`, como los de compartir-, así
  que esa política sigue intacta, y un futuro widget en la propia web,
  si algún día se quiere, seguiría esa misma regla.
- **`api/candidatos.php` da la cola de curación en JSON, de solo lectura.**
  `secretos.token_api` llevaba generándose desde el instalador -y
  documentado en `config/config.ejemplo.php` y en la propia pantalla de
  "3. Token de la API" del instalador, que ya decía "lo necesitarás en la
  fase 6, para la redacción asistida"- sin que ningún fichero lo leyera
  todavía. Este es la mitad de lectura de esa promesa: cabecera
  `Authorization: Bearer <token>`, y la misma cola que ya pinta
  `panel/index.php`, para revisarla desde fuera sin iniciar sesión. El
  formateo vive en `datos_formatear_candidato()`, en `panel/datos.php` y
  no en el propio `api/candidatos.php`: es una función pura sobre lo que ya
  devuelven `datos_cola()` y `datos_items_racimo()`, así que se puede
  probar sin base de datos ni servidor, igual que el resto de
  `panel/datos.php`.
  **`api/bits.php` -crear o publicar un bit por API, no solo leerlo- se
  queda sin escribir, a propósito.** Abrir un camino para publicar
  contenido sin pasar por una persona es un cambio de proceso editorial,
  no una función más, y es exactamente el tipo de decisión que este sitio
  reserva para quien lo lleva, no para quien programa. "Redacción
  asistida" se queda como la única pieza de la fase 6 sin resolver.
- **`/salud.php` también cuenta los votos.** Total, positivos y negativos,
  nada más -ni por bit, ni por destinatario: la tabla `votos` no guarda esa
  identidad, y esta página tampoco la reconstruye cruzando datos-. Mismo
  motivo que el resto de la página: poder comprobar desde fuera si algo se
  mueve, sin entrar a la base.
- **El catálogo de fuentes tiene pantalla propia en el panel.** Hasta ahora
  solo se podía ver o tocar por SQL directo, y con el catálogo a punto de
  crecer de cincuenta y pico a varios cientos eso deja de ser razonable.
  `panel/index.php?p=fuentes` enseña todas, con un diagnóstico de los
  últimos catorce días por fuente -cuánto ha entrado, cuánto se ha
  descartado y por qué, cuánto sigue en cola, cuánto ha llegado a
  publicarse-, porque "esta fuente no trae nada" y "esta fuente trae mucho
  pero todo se descarta" antes se veían exactamente igual. También da de
  alta fuentes nuevas sin tocar SQL, validadas con `fuentes_validar()`
  (nueva en `lib/fuentes.php`, pura) antes de escribir.
  Nueva columna `fecha_alta` en `fuentes` -migración 020-: `NULL` en las
  que ya estaban, porque no se sabe de verdad cuándo entraron y una fecha
  inventada sería peor que dejarlo en blanco; a partir de ahora, toda
  fuente nueva lleva la suya, para poder ver el catálogo ordenado por lo
  más reciente y no solo por el total.
- **Las fuentes de la migración 021 no están verificadas de primera mano,
  y lo dicen en su propia nota.** Las migraciones 009 y 014 se escribieron
  tras comprobar cada feed uno a uno -robots.txt, HTTP 200, entradas
  reales-. Esta vez el entorno donde se preparó el cambio no tenía salida
  de red a ningún sitio externo -ni siquiera a fuentes ya verificadas del
  catálogo, como Skift-, así que esa comprobación no se pudo hacer igual.
  Entran igual, activas: el propio sistema ya sabe distinguir una fuente
  que no responde de una que sí -se duerme sola, migración 006-, y con el
  directorio del panel (punto anterior) se puede ver en un par de días
  cuáles de verdad traen contenido. Mejor esto, dicho con claridad, que
  fingir la misma comprobación que las migraciones anteriores sí hicieron.
  De paso, `web_idiomas()` reconoce ya `'pt'`: la primera fuente en
  portugués del catálogo lo necesitaba, o un bit traducido desde ahí
  hubiera enseñado "Titular en pt" en vez de "Titular en portugués".
- **Cifras ya no es solo adopción de IA: también es el tamaño real del
  turismo.** Tres grupos nuevos -turismo internacional, contribución
  económica del sector y rendimiento hotelero-, con España frente al dato
  global o frente a Estados Unidos según qué organismo cubre qué lado,
  siguiendo la misma regla que ya tenía la página: nunca inventar el lado
  que falta. Se quedó fuera, a propósito, la sostenibilidad hotelera -el
  Cornell Hotel Sustainability Benchmarking Index es la referencia del
  sector, pero su cifra pública más reciente y citable de un vistazo es de
  2018-2021, no del último año que esta página se exige a sí misma-: mejor
  no meter un grupo que no que meter uno con la fecha caducada desde el
  primer día.
- **Las migas de pan son invisibles a propósito.** `plantillas/web/migas.php`
  añade el `BreadcrumbList` de schema.org a las fichas de día, tema y medio,
  pero sin ningún `<ol>` visible: esas páginas ya tienen menú y enlaces de
  "volver", y una fila más de navegación no le dice nada nuevo a quien lee.
  Lo que sí necesitaba decirse es la jerarquía -que una ficha de tema cuelga
  de Temas, y Temas de la portada- a quien no lee la página como una
  persona: un buscador, o una IA que la resuma. Mismo criterio que el resto
  del sitio, `itemprop` y no JSON-LD.
  Descartado sin llegar a escribir código: "seguimiento silencioso de un
  proveedor" -una de las ideas de la lista original-, porque choca de
  frente con la razón de ser de `tema.php`, ya escrita en su propio
  comentario: una ficha de proveedor contesta "qué se ha dicho de Mews",
  que es la pregunta de Mews, no la del hotel al que sirve este radar.
- **La portada dejó de partirse por días.** Cada tramo de `$rio` llevaba su
  propia caja de "HOY"/"AYER", y con el volumen que ya mueve el catálogo
  eso volvía a parecerse a una edición -justo lo que este sitio dejó de
  ser cuando la portada se convirtió en un río-. Ahora es un solo flujo:
  `portada.php` aplana `$rio` y cada ficha lleva su propia fecha encima
  (`.etiqueta-fecha` en `bit.php`, con las mismas palabras -"Hoy", "Ayer"-
  que antes llevaba la cabecera de día). De paso, el tope de la portada
  sube de 80 a 300: sin fotos ni JS, trescientas fichas de texto siguen
  pesando poco, y ochenta se notaba justo cuando se aprobaba un lote
  grande desde el panel.
- **Todas las fichas del río, del mismo alto.** `.bit:not(.bit-lead)` fija
  una altura y recorta lo que no cabe con `-webkit-line-clamp` (titular,
  cuerpo) o con un `max-height` liso ("por qué importa"). Ese segundo caso
  no es capricho: con el padding que lleva ese cuadro, un `-webkit-line-clamp`
  calculado justo para dos líneas dejaba pintarse un trozo de una tercera
  por debajo del recorte -comprobado a mano, capturando la página, no en
  teoría-, y bajando el límite del `max-height` claramente por debajo de
  esa medida el recorte vuelve a ser limpio. Se pierden los tres puntos
  finales; no se pierde la portada con una ficha rota.
- **Lo que cuentan varios medios sale en negativo.** `.bit-multifuente`
  (fondo negro, letra blanca) se aplica en el propio `bit.php`, calculando
  cuántas fuentes hay antes de abrir la ficha, no al final: la señal de
  "esto lo confirman varios medios independientes" es la más fuerte que
  puede dar esta página, y una fila más al pie no se veía lo bastante.
- **El pie de cada ficha son iconos, no texto.** Leer el original, la
  ficha del medio, compartir por WhatsApp o LinkedIn: los cuatro con su
  `aria-label` como nombre accesible y, con un `::after` en CSS
  (`.icono-boton`), como el globo que aparece al pasar el ratón o al
  llegar por teclado. El nombre del medio se queda visible al lado, sin
  enlace: no puede depender de pasar el ratón por un icono para saber
  quién cuenta la noticia.
- **Cifras suma inversión, empleo y gasto turístico.** Tres grupos más
  -Colliers para la inversión hotelera, la EPA del INE para el empleo en
  alojamiento frente a restauración, Egatur del INE para el gasto del
  turista internacional-, siguiendo la misma disciplina que ya tenía la
  página: doce fuentes citadas, ninguna cifra sin fecha ni enlace. El
  empleo se cita "vía Hostelería Digital" -mismo patrón que ya usaba la
  AEPD vía Moncloa- porque el desglose por rama de actividad que hace
  falta aquí (alojamiento sube, restauración baja) sale mejor explicado en
  la prensa del sector que en la tabla cruda del INE, aunque el dato en sí
  sea de la EPA.
- **Ciberseguridad y cumplimiento se separan en dos categorías.** Un
  director de sistemas que sigue "Ciberseguridad" quiere saber si su
  cadena está en riesgo esta noche; una multa de la AEPD o una sentencia
  la lee legal, no TI, y en otro momento. `bits_categorias()` en
  `lib/bits.php` pasa a tener `ciberseguridad` y `cumplimiento`, y
  `bits_categoria_canonica()` manda lo publicado con el slug viejo a
  `ciberseguridad` -mismo mecanismo que ya usaba el reparto de
  `pms-gestion` o `distribucion-revenue`-, así que ningún bit existente se
  queda sin categoría válida. No hay ningún prompt de IA que retocar: la
  categoría sale de `diccionario` por coincidencia de término
  (`auto_categoria_diccionario()`) o, en su defecto, del
  `categoria_defecto` de la fuente, así que repartir el catálogo es
  cuestión de una migración de datos
  (`sql/migraciones/022-separar-ciberseguridad-y-cumplimiento.sql`) que
  retoca esas dos tablas, no de reescribir ninguna lógica de
  clasificación.
- **`/medios.html` dice cuántas fuentes vigila el radar, no solo cuántas
  publican.** La página siempre fue sincera -"el radar rastrea bastantes
  más medios de los que aparecen aquí"-, pero una frase sin número no
  transmite alcance. `publicar_radar_total()` en `cron/publicar.php`
  cuenta las fuentes activas por ámbito (España, Europa, global) y el pie
  de la página pasa a decir, por ejemplo, "vigila 187 fuentes activas
  -104 de España, 38 de Europa y 45 de alcance global-; aquí solo
  aparecen las que ya han contado algo que pasó el filtro". Mismo hecho
  de siempre, ahora con la cifra real en vez de una vaguedad.
- **Por qué un racimo no llega a un bit ya se guardaba; solo faltaba
  dónde leerlo.** `racimos.motivo_descarte` se escribe desde el
  principio, a mano al descartar o con el prefijo "automatico:" que
  pone `cron/auto.php`, pero antes de esto solo se veía agregado -el
  motivo más frecuente por fuente, en `plantillas/panel/fuentes.php`-.
  `datos_descartados()` en `panel/datos.php` lo enseña racimo a racimo,
  debajo de la cola: la pregunta de quien ve pocos bits llegar a una
  edición y no sabe por qué tiene ahora una respuesta sin abrir la base
  de datos.
- **Vaciar la cola es una decisión, y se pide un motivo para tomarla.**
  `datos_descartar_cola()` descarta de golpe todo lo que sigue en la
  cola, para cuando se ha acumulado un backlog que ya no interesa
  revisar racimo a racimo. Reutiliza el mismo criterio que `datos_cola()`
  -candidatos sin bit todavía, no un `WHERE estado='candidato'` a
  secas-, porque un racimo con un bit ya escrito se queda en
  `'candidato'` hasta que su edición se cierra: sin ese filtro, vaciar
  la cola se llevaría por delante bits en borrador o aprobados que
  solo están esperando su turno. El motivo es obligatorio en este
  formulario -a diferencia del descarte de uno en uno, que si se deja
  en blanco pone "sin interés"- porque descartar sesenta candidatos de
  una vez es una decisión más grande que descartar uno, y no se toma
  sin decir por qué. El botón no promete una cifra -la cola solo enseña
  como mucho sesenta- para no anunciar un número que podría no ser el
  real: la cifra de verdad la da el aviso de después.
- **El pie ya no promete lo que el modo automático desmiente.** Decía
  "ninguno se publica sin que lo haya leído una persona", y con
  `auto_publicar` activo eso es falso -`/sobre.html`, dos párrafos más
  abajo, ya lo decía bien: "Los elige la puntuación, no una persona"-. El
  modo automático es una decisión defendible y está explicada; lo que no
  se sostenía era que el pie siguiera prometiendo lo contrario en todas
  las páginas. Ahora dice que todo pasa el mismo filtro de puntuación y
  comprobación, a mano o en modo automático, y enlaza a `/sobre.html`
  para quien quiera el porqué completo.
- **`/sobre.html` se puso al día con dos decisiones que ya había tomado
  el resto del sitio.** "Medio centenar largo de fuentes" venía de antes
  de `publicar_radar_total()` (commit #30): ahora usa la misma cifra real
  que ya enseña `/medios.html`. Y la frase sobre no traducir nunca
  -"traducir sería dejar de decir lo que dijo la fuente"- se escribió
  antes de que el sitio empezara a traducir con DeepL: la regla nueva ya
  estaba bien explicada en este README, pero esta página, que es la que
  lee quien llega por un enlace suelto, se había quedado con la vieja.
- **La tarjeta que se comparte es PNG, no SVG, aunque favicon.svg sea
  SVG.** `bit.php` construye botones para compartir en WhatsApp y
  LinkedIn desde hace tiempo, pero sin ninguna etiqueta `og:image` lo que
  se compartía era un enlace desnudo. La corrección obvia -escribir la
  tarjeta en SVG, como ya escribe `favicon.svg`- no funciona aquí:
  ninguna red que enseña vista previa (WhatsApp, LinkedIn, Facebook, X)
  rasteriza SVG en `og:image`, solo PNG/JPG/WebP. `lib/imagen_social.php`
  usa GD -viene con casi cualquier PHP, no hace falta Composer- para
  dibujar la misma tarjeta tipográfica -papel, filete, el sello y el
  titular- pero en un PNG de verdad. La tipografía es Big Shoulders (SIL
  Open Font License, en `plantillas/web/fuentes/`): condensada, como pide
  `--titular` en `estilo.php`, y usada solo en el servidor para generar
  la imagen, así que no toca la regla de "nada de Google Fonts" del CSP
  -esa regla es sobre cargar fuentes en el navegador, no sobre qué
  tipografía dibuja una imagen que ya sale terminada-. Una tarjeta
  genérica para todo lo que no es un día, y una por día con el titular
  del bit más reciente de esa fecha; una por bit, dice el propio
  documento de mejoras, es el final del camino, no el principio.
- **`/cumplimiento.html`: el calendario normativo, investigado de cero,
  no copiado del documento de mejoras.** El propio `docs/MEJORAS.md` que
  proponía esta página traía fechas ya desactualizadas -Verifactu decía
  "1 ene 2026 / 1 jul 2026", y un Real Decreto-ley de diciembre de 2025
  (el RDL 15/2025) ya lo había aplazado a 2027 antes de escribirse ese
  documento-, así que la tabla se rehizo entera con fuentes oficiales
  buscadas una por una -AEAT, Ministerio del Interior, EUR-Lex, la propia
  Comisión Europea-, no copiada de la propuesta. El caso de NIS2 cambió
  más que la fecha: para cuando se escribió esta página, la Comisión ya
  había llevado a España ante el Tribunal de Justicia de la UE (8 de
  julio de 2026) por no transponerla, y encima el sector hotelero ni
  siquiera está en la lista de sectores que cubre la directiva europea
  -"cadenas de más de 50 empleados" no es una afirmación que se pudiera
  sostener sin más comprobación-, así que la entrada dice exactamente esa
  incertidumbre en vez de una cifra inventada.
- **Los datos de `/cumplimiento.html` viven en `lib/cumplimiento.php`, no
  en la plantilla.** Distinto de `$grupos` en `estadisticas.php`, que sí
  vive en la plantilla: aquí `cron/mantenimiento.php` necesita las
  fechas de cada norma para calcular cuándo avisar, no solo una fecha de
  revisión suelta -la caducidad de esta página no es un plazo fijo como
  en Cifras, es la fecha pendiente más próxima de su propia tabla-, así
  que la tabla entera tenía que poder leerse sin ejecutar la plantilla.
  `cumplimiento_normas()` es la única fuente de la verdad; la plantilla y
  `cron/mantenimiento.php` la leen igual.
- **La tipografía autoalojada es un solo corte estático, no la variable
  entera.** `docs/MEJORAS.md` pedía "una condensada variable autoalojada".
  Big Shoulders (SIL Open Font License) es de verdad variable -ejes `opsz` y
  `wght`-, pero un repaso de todos los usos de `var(--titular)` en
  `estilo.php` -con `awk`, no a ojo- confirmó que ninguno pide un peso
  distinto de 700: siempre negrita. Cargar el eje `wght` completo son bytes
  que nadie va a usar nunca. `plantillas/web/fuentes/BigShoulders-Titular-Bold.woff2`
  es esa variable fijada a `wght=700, opsz=36` con `fontTools.varLib.instancer`
  y subconjuntada al latino -18 KB-, no la fuente variable sin tocar. Sigue
  siendo autoalojada -mismo origen, el CSP no se toca- y sigue sin ser Google
  Fonts -eso se descargó una vez de `raw.githubusercontent.com/google/fonts`
  para construir el fichero, no lo carga el navegador de ahí-. El problema
  que resuelve es real y medible en este mismo entorno: sin ella, `--titular`
  cae a Arial sin condensar en cualquier Linux o Android sin Arial Narrow
  instalada -exactamente lo que enseñan las capturas hechas con el Chromium
  de este sandbox, que tampoco la tiene-.
- **El modo oscuro redefine variables, no reescribe reglas.** `.bit-multifuente`
  y `.alta` ya pintaban "al revés" con `var(--tinta)`/`var(--papel)` antes de
  esto, así que en `prefers-color-scheme: dark` basta con invertir esas
  variables en `:root` para que seas coherentes en los dos sentidos. Lo único
  que no se resuelve solo redefiniendo son los colores que estaban fijados a
  mano para un caso concreto y no vivían en una variable: el barrido del
  sello (`.sello-marca`, pensado para un tramo "más claro que el resto" sobre
  tinta casi negra) y el interior de la caja de alta (`.alta p`,
  `.alta-formulario label`, `.alta .letra-pequena`, `.alta-fila input`,
  pensados para una caja oscura sobre página clara que en modo oscuro pasa a
  ser una caja clara sobre página oscura). Esos cinco sitios llevan su propio
  bloque dentro de `@media (prefers-color-scheme: dark)`. De paso se corrigió
  un bug ya existente: `.bit-multifuente .etiqueta-fecha`, `.titular-fuente` e
  `.icono-boton` tenían `color: #cfcac2` fijo en vez de `var(--filete-fino)`,
  así que no habrían cambiado de tono en modo oscuro. Cada color nuevo se
  comprobó contra WCAG AA (4,5:1 en texto) con una calculadora de contraste
  hecha a mano, no a ojo.
- **El bloque de modo oscuro vive al final de la hoja, no junto a `:root`.**
  Colocarlo justo debajo del `:root` base rompía en silencio: hay reglas
  incondicionales más abajo en el fichero -`.alta-fila input` entre ellas-
  que fijan las mismas propiedades para el mismo selector, y en un empate de
  especificidad gana el orden de aparición en el CSS, no si la media query es
  cierta. El resultado, visto en una captura real en modo oscuro, era un
  campo de alta con fondo oscuro sobre página oscura, ilegible. Recordatorio
  general: un bloque `@media` no "gana" solo por estar en una media query
  activa, si algo después en el mismo fichero apunta al mismo selector con la
  misma especificidad.
- **El boletín de vulnerabilidades KEV se guarda como un item normal, no
  como una tabla propia.** `cron/kev.php` cruza el catálogo de CISA contra
  `proveedores`/`proveedor_alias` y, cuando una entrada menciona a uno,
  llama a la misma `ingesta_guardar_item()` que usa la ingesta de RSS. A
  partir de ahí `cron/procesar.php` y `cron/auto.php` no saben ni les
  importa de dónde ha salido: lo agrupan, lo puntúan y lo publican con las
  mismas puertas que cualquier otra noticia, incluida la de que hable de
  hoteles. Encaja con la arquitectura tal cual estaba, que es justo lo que
  pedía `docs/MEJORAS.md`.
- **Esa fuente no es un feed RSS, y por eso `fuentes` tiene una columna
  `gestion`.** `items.fuente_id` exige una fila en `fuentes`, pero el KEV es
  un JSON con su propia forma, no XML: si la fuente se dejara `activa = 1`
  sin más, el rastreador genérico la cogería en su turno, descargaría el
  catálogo entero como si fuera un feed roto y la dormiría para siempre. La
  columna `gestion` (`rss` | `manual`) es lo que separa "el rastreador
  genérico puede tocar esto" de "esto lo alimenta su propia tarea";
  `INGESTA_DESPIERTAS` ahora exige `gestion = 'rss'`. La fuente sigue con
  `activa = 1` -sigue siendo verdad que se vigila-, y `cron/kev.php` respeta
  esa columna: apagarla a mano desde el panel para la tarea de verdad, el
  mismo control que ya tenía cualquier otra fuente.
- **La primera pasada del KEV no cruza el catálogo histórico entero.** CISA
  lleva más de mil quinientas entradas desde 2021, casi todas ya parcheadas
  hace años. Convertirlas todas en avisos el día que se activa esta tarea
  enterraría las noticias de ese día bajo un vertedero de CVEs viejas, así
  que `kev_puntero_inicial()` pone el puntero al día en silencio -salta
  directo a la fecha más reciente del catálogo- y solo entra, de ahí en
  adelante, lo que CISA añada de nuevo. El puntero en sí no es un id
  incremental como el de la ingesta -aquí solo hay un fichero, no
  doscientas fuentes-: es una fecha más la lista de CVE ya vistos de ese
  mismo día, para poder reanudar a medias un día con muchas entradas nuevas
  sin dejar huecos ni repetir trabajo. `lib/kev.php` recorre siempre de más
  antigua a más nueva -aunque CISA sirve el catálogo al revés- para que un
  corte por presupuesto de tiempo deje siempre un tramo continuo hecho,
  nunca un hueco en medio que la siguiente pasada dé por visto sin estarlo.
- **El titular del aviso va en inglés, con la palabra "hotel" a la fuerza.**
  El texto de CISA nunca dice "hotel": habla de "Oracle" o de "SiteMinder",
  no de para qué sirven. La puerta del sector de `auto.php`
  (`auto_es_del_sector()`, deliberadamente barata: busca la palabra en el
  texto) descartaría un aviso real de una vulnerabilidad en un PMS exactamente
  igual que descarta una de Chrome. `kev_categoria_legible()` añade esa
  palabra a partir de la categoría que ya tiene el proveedor en
  `sql/semilla_proveedores.sql` -un dato verificado de antemano, no una
  suposición sobre esta vulnerabilidad concreta-, y lo hace en inglés porque
  el resto del titular también lo está -es el texto de CISA, sin traducir
  todavía-: así el bit entero pasa por el mismo traductor que cualquier otra
  fuente en inglés, en vez de dejar una frase en español a medio traducir.
- **Las fichas de tema se enriquecen con datos que ya existían, no con
  contenido nuevo.** `plantillas/web/tema.php` ganó cuatro secciones -qué es
  el tema, siglas del glosario, cumplimiento y cifras relacionadas- y las
  cuatro leen de sitios que ya estaban: `bits_categoria_descripcion()`
  (nueva, en `lib/bits.php`, un párrafo evergreen por categoría),
  `glosario_por_tema()`, `cumplimiento_por_tema()` y `cifras_por_tema()`.
  Ninguna se pinta si viene vacía -un tema puede no tener ninguna norma ni
  ninguna cifra asociada, y un bloque de sección vacío es peor que no
  ponerlo-.
- **Los términos del glosario y los grupos de Cifras se trasladaron a
  `lib/glosario.php` y `lib/cifras.php` (`cifras_grupos()`), fuera de sus
  plantillas.** Antes de esta revisión solo `lib/cumplimiento.php` vivía
  fuera de su plantilla, y por el motivo de siempre -`cron/mantenimiento.php`
  necesita leer sus fechas sin ejecutar la página entera-. Ahora
  `plantillas/web/tema.php` necesita esos mismos datos -qué siglas y qué
  grupos de cifras tocan a un tema-, así que aplica el mismo motivo: un solo
  sitio que tocar cuando se actualicen, no dos copias que puedan
  desincronizarse. El traslado es mecánico -mismo texto, mismas cifras,
  mismas fechas, solo cambia el fichero que las contiene-; ninguna cifra ni
  fecha se retocó al moverla, para no arriesgar una transcripción en una
  página cuyo único valor es que cada dato se pueda comprobar.
- **`cifras_grupos()` marca con `'categoria' => null` los tres grupos que no
  son del sector hotelero, en vez de omitir el campo.** "IA en la empresa,
  en general", "Cloud computing" y "Comercio electrónico" son la vara de
  medir de fondo -la misma encuesta del INE sirve para cualquier sector-, no
  un dato de hotel, y por eso no deben aparecer en la ficha de ningún tema.
  Un campo `null` explícito dice esa decisión a quien lea el array; omitir
  el campo habría dejado la misma pregunta sin contestar la próxima vez que
  alguien añada un grupo.
- **`cumplimiento_normas()` deja el alquiler de corta duración sin ningún
  tema (`'temas' => []`).** Esa norma ya dice en su propio campo `aplica`
  que no afecta a hoteles -entra en `/cumplimiento.html` solo por contexto
  competitivo-, así que enlazarla desde la ficha de cualquier tema hotelero
  habría contradicho lo que la propia entrada explica.
- **Los tres enlaces que llevan al artículo original abren en pestaña
  nueva.** El titular del bit, el botón "externo" del pie y cada fuente del
  desplegable "N fuentes lo cuentan" -en `bit.php` y en `mas_leido.php`-
  llevan `target="_blank"` a petición expresa del dueño del sitio: un
  agregador vive de que el lector siga en el sitio entre un clic y el
  siguiente, y perder la portada -o la ficha del bit- cada vez que se pulsa
  un enlace es justo lo contrario. Los enlaces de cita -las fuentes de
  Cifras y Cumplimiento, "ir al medio" en la ficha de un medio- se quedan
  como estaban: no son "una noticia" en el sentido de lo que pidió el
  dueño, son referencias de una página que ya de por sí es de consulta, no
  de lectura seguida.
- **El glosario pasó de veinte a cuarenta y ocho términos, siguiendo al
  pie de la letra la lista de `docs/MEJORAS.md` -reserva agéntica,
  métricas de negocio, pagos, arquitectura genérica, seis siglas
  normativas y BMS/BEMS-.** Cada término nuevo se verificó por separado
  antes de escribirlo, no se copió de memoria: en particular, CSRD llevaba
  en la cabeza el ámbito de aplicación original de la directiva, y una
  búsqueda mostró que el "Omnibus I" de 2026 lo redujo a partir de 1.000
  empleados y 450 M€ de facturación -deja fuera a la inmensa mayoría de
  cadenas hoteleras-, así que la definición dice eso, no el ámbito viejo.
  BMS y BEMS se separaron en dos entradas -el documento los escribía juntos
  como "BMS/BEMS"- porque son siglas distintas con significados distintos
  y el glosario no tiene precedente de entradas combinadas.
- **Los cinco términos de "arquitectura genérica" -CDP, middleware, iPaaS,
  webhook, SSO- se quedan sin tema (`tema => null`), igual que API y
  KPI.** El propio documento los agrupaba aparte de "PMS y CRS", y son
  conceptos que no son privativos de la tecnología hotelera: forzarles un
  tema habría sido una etiqueta menos precisa que no ponerles ninguna,
  el mismo criterio que ya regía para API y KPI antes de esta ampliación.
- **`/legal.html`: la identidad del responsable sale de `config('legal')`,
  nunca de un dato inventado en la plantilla.** Este proyecto no tiene en
  ningún sitio del código el nombre, el NIF ni el domicilio de quien lo
  edita -y no le corresponde a quien escribe la plantilla decidirlos-, así
  que `plantillas/web/legal.php` los lee de tres claves de configuración
  nuevas (`legal.titular`, `legal.identificacion`, `legal.domicilio`),
  todas opcionales. Sin rellenar, la página se publica igual pero lo dice
  -"todavía no tiene rellenos los datos de identificación"- y deja el
  contacto por correo como única vía, en vez de fingir una identidad que
  nadie ha confirmado. **Hay que rellenar esas tres claves antes de que el
  sitio reciba visitas de verdad**: la LSSI exige identificar a quien
  responde de un sitio en español, y esta página no lo hace sola.
- **La sección de cookies dice que la web pública no instala ninguna
  cookie porque es verdad, no porque tocara escribirlo así.** Se comprobó
  contra el código, no se asumió: `grep` por todo `plantillas/`, `lib/`,
  `api/` y `panel/` encontró un único `setcookie()` en `lib/panel.php`, la
  sesión del panel de administración, que un lector de la web pública ni
  ve ni recibe. La sección de datos personales sigue el mismo criterio -se
  verificó línea por línea, no se copió un texto genérico de política de
  privacidad-: `api/suscribir.php` no guarda el correo en la base propia
  -viaja directo al proveedor configurado-, `api/ir.php` no guarda ni IP ni
  identificador para los clics, y los votos y el límite de altas por IP
  usan un HMAC de la dirección, nunca la dirección en sí.
- **La base legal para publicar fragmentos de otros medios se describe sin
  afirmar que el sitio ya cumple con ella del todo.** El artículo 32.2 del
  Texto Refundido de la Ley de Propiedad Intelectual -el mismo que en 2014
  forzó el cierre de Google News en España, conocido como "canon AEDE"- sí
  permite a un agregador poner a disposición fragmentos no significativos
  de contenido periódico sin pedir autorización previa, pero a cambio de
  una remuneración equitativa e irrenunciable a los editores, gestionada
  por una entidad de gestión de derechos: citar la fuente y enlazarla no
  exime de esa remuneración, son dos cosas distintas. `/legal.html`
  explica el mecanismo -la excepción existe y este sitio encaja en su
  descripción- sin declarar que ya se ha liquidado esa remuneración,
  porque eso es una gestión real con una entidad real que no consta en
  ningún sitio de este repositorio y no le corresponde a esta redacción
  inventar que ya está resuelta. Es una decisión de negocio del dueño del
  sitio, no una que se pueda dar por buena escribiendo un párrafo.
- **`/legal.html` lleva `noindex, follow` y no entra en `sitemap.xml`.**
  No aporta nada a quien busca tecnología hotelera -es la única página del
  sitio pensada para cumplir un trámite, no para leerse-, y Google
  desaconseja expresamente listar en el sitemap una página marcada
  `noindex`: son dos señales contradictorias sobre la misma URL. El
  `follow` se queda para que el rastreador sí siga el enlace desde el pie
  hacia el resto del sitio.
- **§2.3 de `docs/MEJORAS.md` pedía tres cosas; solo se hicieron dos.** El
  mix de canal directo y OTA -un grupo nuevo en Cifras- y `cifras.json` sí
  se verificaron y se publican. La digitalización de la hostelería
  española vía la encuesta ETICCE del INE se queda pendiente: `ine.es` no
  es accesible desde este entorno de desarrollo -el mismo bloqueo de red
  que ya afectó a una fuente de `/cumplimiento.html`-, y lo único que dieron
  las fuentes secundarias fue una cifra aproximada ("inferior al 15 %"),
  no la precisión con fuente directa que exige esta página. Publicar un
  número aproximado habría sido peor que dejarlo pendiente y decirlo:
  `docs/MEJORAS.md` conserva ese punto, reescrito para que quien lo retome
  sepa exactamente qué falta y por qué no se resolvió esta vez.
- **El mix de canal directo y OTA cita la edición de 2025 del informe
  «State of Distribution», no la de 2026 que ya citaba el grupo "IA en los
  hoteles".** Son dos hallazgos de dos ediciones anuales distintas del
  mismo informe -la paridad canal directo/OTA se publicó en la 2.ª edición,
  junio de 2025-, así que cada cifra enlaza a la nota de prensa de su
  propia edición en vez de citar la más reciente por comodidad. La entrada
  de `$fuentes` que los describe ya era lo bastante genérica -"Informe
  anual «State of Distribution»", sin fijar un año- para cubrir ambas sin
  tocarla.
- **`cifras_exportar()` expone exactamente `cifras_grupos()`, sin filtrar
  los grupos que no son del sector hotelero.** Los tres grupos con
  `categoria => null` -la vara de medir de fondo: IA, cloud y comercio
  electrónico en la empresa española en general- siguen siendo datos
  reales, con fuente y fecha, que alguien puede querer citar aunque no
  sean específicos de hoteles; filtrarlos del JSON solo porque
  `/estadisticas.html` los pinta con una nota aclaratoria habría sido
  esconder un dato válido, no limpiar el fichero.
- **La reserva agéntica (§2.4) es una página en Recursos, no una categoría
  nueva del catálogo de temas.** Una categoría habría exigido reclasificar
  bits ya publicados y repartir de nuevo el diccionario -el mismo trabajo
  que costó separar ciberseguridad de cumplimiento-, apostando fuerte por
  un terreno que todavía cambia de mes en mes: MCP a mediados de 2026, ACP
  retirado de viajes en marzo de 2026 tras solo seis meses, UCP ampliado a
  alojamiento en mayo de 2026. El propio `docs/MEJORAS.md` ya dejaba esta
  salida como mínimo aceptable, y reutiliza entradas del glosario que ya
  existían (MCP, ACP, Agentic booking) en vez de duplicar definiciones.
- **`AGENTICA_CADUCIDAD_DIAS` es 90, no los 120 de `CIFRAS_CADUCIDAD_DIAS`.**
  Mismo patrón exacto que `lib/cifras.php` -fecha de revisión fija, aviso
  por correo, bloque en `/salud.php`-, con un plazo más corto porque los
  protocolos de reserva agéntica cambian de mes en mes, no de trimestre en
  trimestre como una encuesta del INE.
- **"¿Desaparece mi canal directo?" se contesta con los propios tropiezos
  del sector, no con una opinión.** La retirada de Instant Checkout de
  OpenAI en viajes -el caso más citado fue que un chat no gestiona tarifa
  dinámica, cancelación ni una incidencia postventa- es la prueba de que
  ningún protocolo actual sustituye a un motor de reservas propio; se cruza
  con la cifra ya publicada en Cifras -el canal directo iguala a las OTAs
  por primera vez- para responder sin alarmismo ni negacionismo.
- **UCP y AP2 de Google, no solo "Agent Payments Protocol" como decía
  `docs/MEJORAS.md` al pie de la letra.** AP2 es la capa de pago dentro de
  UCP (Universal Commerce Protocol), y es UCP -ampliado a alojamiento en
  mayo de 2026 con Amadeus, Booking.com, Expedia, Hilton, Marriott y
  Trip.com como socios- el que de verdad compite por la reserva de hotel;
  citar solo AP2 habría dejado fuera la pieza más relevante para un
  director comercial.
