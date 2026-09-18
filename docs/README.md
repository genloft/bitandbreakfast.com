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
| 6 | Fichas de proveedor, buscador, votos, redacción asistida | fichas, buscador y clics hechos |

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
