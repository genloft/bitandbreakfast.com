# Bit & Breakfast

Radar de noticias de tecnología hotelera. Rastrea 59 fuentes, agrupa las que
cuentan la misma noticia, las puntúa y presenta una cola de candidatos donde
se seleccionan y editan entre 15 y 20 *bits* que se publican como edición HTML
estática y se envían como newsletter semanal en español.

La promesa al lector: **cinco minutos de lectura a la semana y no te pierdes
nada relevante.** Es un radar, no un agregador: filtra duro y enseña poco.

## Restricciones de partida

- Alojamiento compartido de Hostinger, coste cero. Ni VPS ni contenedores.
- PHP 8.1 y MySQL/MariaDB. Sin framework, sin Composer en producción.
- Sin build de frontend: CSS a mano, un único fichero.
- Todo proceso largo va por lotes con puntero persistente, nunca en una pasada.
- Ningún bit se publica sin revisión humana.

## Estructura

```
index.php     arranque: sin configuración lleva al instalador, con ella a la portada
instalar.php  instalador web; se borra solo al terminar
config/       configuración (config.php no está en el repositorio)
lib/          utilidades: PDO, feeds, robots.txt, texto, URLs, agrupación,
              puntuación, reglas del bit, sesión del panel e instalador
cron/         tareas programadas; tareas.php es el despachador único
api/          endpoints públicos: redirección contada, votos, redacción asistida
panel/        zona privada de curación: cola, edición del bit y cierre
plantillas/   plantillas: web/ publicada, panel/ e instalador
publico/      salida estática generada por cron/publicar.php (no se versiona)
pruebas/      scripts de prueba sin framework
sql/          esquema y semillas
docs/         esta documentación e INSTALACION.md
```

## Puesta en marcha

Ver [INSTALACION.md](INSTALACION.md). Resumen: crear una base de datos vacía en
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
php pruebas/comprobar_feeds.php
```

Las siete primeras no tocan la base de datos. La octava sí la lee, y sale a la
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
| 5 | Proveedor de correo y alta con doble confirmación | alta hecha; envío pendiente |
| 6 | Fichas de proveedor, buscador, votos, redacción asistida | pendiente |

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
- **Ningún bit llega a una edición sin pasar por el formato.** El titular
  cabe en 120 caracteres, el cuerpo entre 25 y 110 palabras y el "por qué
  importa" es obligatorio. Un borrador se guarda como sea, pero aprobarlo
  exige cumplirlo: la promesa de los cinco minutos se rompe aquí o no se
  rompe en ningún sitio.
- **La cuota de fuentes españolas y europeas avisa, no bloquea.** Es una
  intención editorial, y una semana floja en Europa no puede impedir el envío.
- **La web se genera, no se sirve.** Una edición cerrada se convierte en
  ficheros dentro de `publico/` y a partir de ahí Apache los sirve sin tocar
  PHP ni la base de datos. Es lo único que aguanta una portada compartida de
  golpe, y en un alojamiento compartido no hay plan B.
- **La portada se lee con el pulgar.** El sumario va antes que los bits porque
  un radar tiene que decir en diez segundos si esta semana traía algo. Todo lo
  demás es consecuencia de eso.
- **La lista de correo no se guarda aquí.** Vive entera en el proveedor. Una
  base de datos en alojamiento compartido no es sitio para una lista de
  direcciones, y el proveedor ya sabe gestionar bajas, rebotes y doble
  confirmación mejor de lo que se escribiría aquí.
- **Al que ya estaba suscrito se le dice lo mismo que al que no.** Distinguir
  las dos respuestas permitiría averiguar quién está en la lista probando
  direcciones.
- **El redirector va firmado.** `api/ir.php` cuenta el clic y redirige, pero
  solo si la URL trae el HMAC del bit. Sin esa firma sería un redirector
  abierto, y un redirector abierto en un dominio que manda correo se convierte
  en munición para phishing en cuestión de semanas. De quien pulsa no se
  guarda nada: ni IP ni identificador, solo el HMAC del agente de usuario para
  poder descontar los escáneres de correo.
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
