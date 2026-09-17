# Instalación y despliegue en Hostinger

Despliegue por **Git** desde hPanel e instalación con el **instalador web**,
que se ejecuta solo la primera vez que abres el dominio y se borra al terminar.
No hace falta FTP ni tocar phpMyAdmin.

El orden es este y conviene no saltárselo:

1. Preparar PHP.
2. Crear la base de datos vacía.
3. Conectar el repositorio en hPanel → GIT y desplegar.
4. Abrir el dominio: sale el instalador.
5. Crear la tarea cron y comprobar los cerrojos.

Sustituye en los ejemplos `uXXXXXXXXX` por tu identificador de usuario (lo ves
en la ruta del Administrador de archivos) y `bitandbreakfast.com` por tu dominio.

---

## 1. Preparar PHP

hPanel → **Avanzado → Configuración PHP**:

- Versión **8.1 o superior**.
- Pestaña *Extensiones PHP*: `pdo_mysql`, `curl`, `mbstring`, `simplexml`,
  `openssl`, `json`. Sin `curl` ni `simplexml` la ingesta no arranca, y el
  instalador te lo dirá antes de dejarte continuar.

---

## 2. Crear la base de datos vacía

hPanel → **Bases de datos → MySQL** → *Crear una nueva base de datos*:

- Nombre de la base: `bitb` → queda como `uXXXXXXXXX_bitb`.
- Usuario: `bitb` → queda como `uXXXXXXXXX_bitb`.
- Contraseña: larga; la necesitas en el paso 4.

**No importes nada aquí.** De eso se encarga el instalador.

---

## 3. Desplegar con Git

hPanel → **Avanzado → GIT**.

### 3.1 La carpeta de destino tiene que estar vacía

Git se niega a clonar sobre una carpeta con contenido. Entra en el
Administrador de archivos y vacía `public_html`: borra el `index.html` por
defecto de Hostinger y cualquier cosa que hubieras subido antes a mano.

### 3.2 Crear el repositorio

- **Repositorio**: `https://github.com/genloft/bitandbreakfast.com.git`
- **Rama**: `main`
- **Directorio**: `public_html` (déjalo vacío o escribe `public_html`, según
  te lo pida la pantalla)

Si el repositorio es **privado**, hPanel te mostrará una clave SSH pública.
Cópiala y añádela en GitHub → tu repositorio → *Settings* → *Deploy keys* →
*Add deploy key*, con acceso de solo lectura. Después usa la dirección SSH
`git@github.com:genloft/bitandbreakfast.com.git` en lugar de la `https`.

Pulsa **Crear**. hPanel clona el repositorio dentro de `public_html`.

### 3.3 Despliegue automático (opcional)

En la misma pantalla, hPanel te da una **URL de despliegue automático**.
Añádela en GitHub → *Settings* → *Webhooks* → *Add webhook*, con
*Content type* `application/json` y el evento *push*. A partir de ahí, cada
`git push` actualiza el servidor solo.

Sin webhook, cada despliegue es pulsar **Deploy** en hPanel.

---

## 4. El instalador web

### 4.1 Abrir el dominio

```
https://bitandbreakfast.com/
```

No hay que armar nada ni crear ficheros a mano. Mientras no exista
`config/config.php`, el sitio no está instalado y **cualquier dirección
redirige al instalador**. Es el `.htaccess` el que lo hace, y la regla deja de
cumplirse sola en cuanto se escribe la configuración.

Quien **envía** el formulario primero se queda con el turno: se escribe
`config/.instalacion`, un cerrojo con un testigo aleatorio que caduca a la
media hora. A partir de ahí, otro navegador que llegue ve un aviso y no puede
instalar encima. Si pierdes la pestaña y tienes prisa, borra ese fichero desde
el Administrador de archivos y recarga.

> ⚠️ **Instala justo después de desplegar.** Entre el despliegue y el momento
> en que pulsas *Instalar*, el formulario lo ve cualquiera que abra el dominio,
> y el primero que lo envíe se queda con el sitio. Suelen ser minutos, pero si
> vas a dejarlo a medias, abre `.htaccess` y descomenta las tres líneas del
> bloque *Sitio sin instalar* con tu IP: solo tú verás el instalador.

### 4.2 Rellenar y pulsar Instalar

Arriba verás la tabla de comprobaciones del servidor: versión de PHP,
extensiones, permisos de escritura de `config/`, `cache/` y `publico/`, los
cuatro ficheros SQL y si la raíz permite que el instalador se borre solo. Si algo
sale en rojo, arréglalo antes de seguir.

| Campo | Qué poner |
|---|---|
| Servidor | `localhost` |
| Nombre de la base | `uXXXXXXXXX_bitb` |
| Usuario | `uXXXXXXXXX_bitb` |
| Contraseña | la del paso 2 |
| Puerto | `3306` |
| Dominio | `bitandbreakfast.com`, sin `https://` ni barra final |
| Usuario del panel | opcional, hace falta en la fase 3 |
| Contraseña del panel | mínimo 12 caracteres |

El instalador:

1. Conecta con la base.
2. Ejecuta `sql/esquema.sql` y las tres semillas: `semilla_fuentes.sql`,
   `semilla_diccionario.sql` y `semilla_proveedores.sql`.
3. Crea el usuario del panel con `password_hash`.
4. Deja una portada provisional en `publico/index.html` y su `estilo.css`, para
   que la raíz del dominio conteste algo con sentido antes de la primera
   edición.
5. Busca o crea la carpeta de registro del cron, fuera de `public_html`.
6. Genera los tres secretos con `random_bytes` y escribe `config/config.php`
   con permisos 600.

Ese último paso va el último a propósito: en cuanto existe `config/config.php`
el sitio cuenta como instalado y el instalador se desarma. Si algo falla antes,
se limpia lo escrito y puedes corregir y reintentar sin salir de la página.

> ⚠️ El esquema empieza con `DROP TABLE IF EXISTS`. Volver a instalar sobre una
> base con datos los borra. Por eso, si ya existe `config/config.php`, el
> instalador no hace nada: se borra solo y avisa.

### 4.3 La pantalla de puesta en marcha

Al terminar salen cuatro cosas, en este orden:

1. **La línea de cron**, ya con la ruta real de tu servidor y del intérprete de
   PHP. **Cópiala antes de seguir**, es la que pegarás en el paso 5.
2. Un botón para **ejecutar la primera ingesta** sin esperar a que el cron se
   despierte. Rastrea unas cuantas fuentes durante una docena de segundos y
   enseña el recuento. Se puede repetir: el puntero sigue donde lo dejó, así
   que aunque el servidor corte la página a media pasada no se pierde nada.
3. El **token de la API**, que hará falta en la fase 6. Está guardado en
   `config/config.php`, no hace falta apuntarlo.
4. El botón **Terminar y borrar el instalador**, que es el último paso: borra
   `instalar.php` del servidor y suelta el cerrojo.

Si no pulsas ese botón no pasa nada grave, porque con `config/config.php`
escrito el instalador ya no hace nada. Pero púlsalo: es lo que lo deja limpio.

---

## 5. La tarea programada

Una sola entrada de cron: `cron/tareas.php` decide qué toca en cada ejecución.

hPanel → **Avanzado → Trabajos cron** → *Crear nuevo trabajo cron*.

> ⚠️ **La trampa de hPanel.** La pantalla tiene dos modos, *PHP* y *Custom*.
> Con **PHP** marcado, el panel escribe él solo el prefijo
> `/usr/bin/php /home/uXXXXXXXXX/` y en la casilla solo va **la ruta
> relativa a tu carpeta personal**. Si ahí pegas el comando entero, queda
> duplicado y no se ejecuta nada; y si escribes `cron/tareas.php`, apunta a
> `/home/uXXXXXXXXX/cron/tareas.php`, que no existe porque el proyecto está
> dentro de `public_html`. El cron falla en silencio y parece que el sitio
> «no hace nada».

**Opción A, modo *Custom*** (la recomendada, porque además guarda registro).
Comando completo, en una sola línea:

```
mkdir -p /home/uXXXXXXXXX/logs; for f in /home/uXXXXXXXXX/domains/*/public_html/cron/tareas.php /home/uXXXXXXXXX/public_html/cron/tareas.php; do [ -f "$f" ] && /usr/bin/php "$f"; done >> /home/uXXXXXXXXX/logs/bitb-cron.log 2>&1
```

Parece aparatoso a propósito: busca el proyecto en las dos ubicaciones que usa
Hostinger y ejecuta la que exista, así no hay que averiguar cuál es la tuya.

**Opción B, modo *PHP***. En la casilla, solo esto:

```
domains/bitandbreakfast.com/public_html/cron/tareas.php
```

Y si tu plan no usa carpeta `domains/`, entonces:

```
public_html/cron/tareas.php
```

Frecuencia: **cada hora**. Y **una sola entrada**: `tareas.php` llama él mismo
a la ingesta, al procesado y al generador. Una segunda entrada para
`procesar.php` duplica trabajo y las dos se pisan.

La carpeta `logs/` cuelga de `/home/uXXXXXXXXX/`, **fuera** de `public_html`,
para que el registro no se pueda leer desde la web. El instalador la busca
subiendo desde el proyecto y la crea en el primer sitio donde pueda escribir;
la ruta que salga en el comando es la que ha elegido. Si no pudo crearla, te lo
dijo en pantalla y la creas tú.

Si `/usr/bin/php` no fuese PHP 8.1, usa la ruta que ofrezca el desplegable de
esa misma pantalla, del estilo `/opt/alt/php81/usr/bin/php`.

### Si el cron no arranca, el radar sigue funcionando

El despachador deja una marca (`cache/.cron`) cada vez que se despierta. Si esa
marca lleva más de dos horas sin tocarse, `index.php` da por hecho que no hay
cron y **empuja la cadena entera en cada visita a la portada**, como mucho una
vez cada cinco minutos: rastrear, agrupar, publicar y generar.

O sea: sin cron el sitio se ve **y entra material**, sólo que al ritmo de las
visitas en vez de cada hora. En cuanto el cron vuelve a latir, esto se apaga
solo y la portada vuelve a ser un fichero estático.

Para saber cuál de los dos está tirando del carro, abre `/salud.php`:

```json
{
  "cron": { "ultima_vez": "hace 3 h", "callado": true,
            "suplencia": "la portada empuja la cadena en cada visita" },
  "cola": { "items_sin_agrupar": 0, "racimos_candidatos": 34 },
  "ediciones": { "publicadas": 1, "bits": 12 }
}
```

Esa página no pide contraseña porque no dice nada que no se pueda deducir
mirando la web: sólo cuentas y fechas.

---

## 6. Comprobar los cerrojos

Abre en el navegador estas cuatro direcciones. Las tres primeras tienen que
devolver **403 Forbidden**, y la cuarta **404**:

```
https://bitandbreakfast.com/config/config.ejemplo.php
https://bitandbreakfast.com/lib/db.php
https://bitandbreakfast.com/cron/ingesta.php
https://bitandbreakfast.com/instalar.php
```

Si alguna de las tres primeras devuelve el contenido del fichero, el
`.htaccess` no se está aplicando: borra `config/config.php` y no sigas hasta
arreglarlo, porque ahí están tus credenciales.

Si `instalar.php` no da 404 es que no consiguió borrarse por permisos. No es
urgente —con configuración escrita no hace nada y se borra solo en la
siguiente visita—, pero bórralo desde el Administrador de archivos.

---

## 6.bis El panel de curación

```
https://bitandbreakfast.com/panel/
```

Con el usuario y la contraseña que pusiste en el instalador. Si dejaste esos
campos vacíos, no hay usuario y hay que crearlo a mano en phpMyAdmin:

```sql
INSERT INTO usuarios (usuario, hash_clave, nombre, activo)
VALUES ('juan', '<el hash>', 'Juan', 1);
```

El hash se genera con `password_hash`, nunca a mano. Por SSH:

```bash
php -r 'echo password_hash("tu contraseña larga", PASSWORD_DEFAULT), "
";'
```

Dentro hay tres pantallas: la **cola** de candidatos por puntuación, el
**editor del bit** con las fuentes del racimo al lado, y la **edición**
semanal, que se cierra cuando todos sus bits están aprobados.

---

## 7. Criterio de aceptación de la fase 1

Deja pasar tres o cuatro ejecuciones del cron. Para no esperar, en Terminal SSH:

```bash
php /home/uXXXXXXXXX/domains/bitandbreakfast.com/public_html/cron/tareas.php ingesta
```

Y las pruebas, que conviene ejecutar al menos una vez:

```bash
php /home/uXXXXXXXXX/domains/bitandbreakfast.com/public_html/pruebas/texto.php
php /home/uXXXXXXXXX/domains/bitandbreakfast.com/public_html/pruebas/canonica.php
php /home/uXXXXXXXXX/domains/bitandbreakfast.com/public_html/pruebas/comprobar_feeds.php
```

En phpMyAdmin, la fase 1 está superada si esto cuadra:

```sql
SELECT COUNT(*) AS items FROM items;                      -- cientos
SELECT COUNT(*) - COUNT(DISTINCT hash_url) AS dups FROM items;  -- 0
SELECT valor AS puntero FROM ajustes WHERE clave = 'ingesta_puntero';  -- avanza

SELECT f.nombre, l.resultado, l.nuevos, LEFT(l.mensaje, 60) AS mensaje
  FROM log_ingesta l JOIN fuentes f ON f.id = l.fuente_id
 ORDER BY l.inicio DESC LIMIT 20;
```

Algún `error` suelto es normal. Una fuente que falla cinco veces seguidas se
desactiva sola y deja el motivo en `fuentes.notas`.

---

## 8. Despliegues siguientes

1. `git push` desde local.
2. hPanel → GIT → **Deploy** (o automático, si pusiste el webhook).

**Merece la pena poner el webhook.** Sin él, cada arreglo se queda en GitHub
hasta que alguien entra en hPanel y pulsa Deploy: el código puede estar
corregido y probado y el sitio seguir publicando lo de antes durante días, sin
que nada avise. Con webhook, `git push` y ya está. Está en hPanel → GIT →
*Auto deployment*, y se pega en GitHub → *Settings* → *Webhooks* → *Add
webhook*, con *Content type* `application/json` y el evento *push*.

Para comprobar qué versión hay desplegada, `/salud.php` da la versión de
criterios que lleva el código y la que se ha aplicado a lo publicado.

Cada despliegue vuelve a dejar `instalar.php` en el servidor, porque está en el
repositorio. No es un agujero: con `config/config.php` escrito, la página no
conecta, no lee formularios y no cuenta nada del servidor. Lo único que hace es
borrarse a sí misma la primera vez que alguien la abre.

Qué sobrevive a cada despliegue, porque no está en el repositorio:

- `config/config.php` — tu configuración.
- `publico/` — la web generada.
- `cache/` — los `robots.txt` cacheados.

Si un despliegue trae SQL nuevo, **no tienes que hacer nada**: los ficheros de
`sql/migraciones/` los aplica el despachador en orden antes de cualquier tarea,
una sola vez, y deja constancia en el registro del cron. El instalador sigue
sirviendo solo para instalar desde cero.

Pendiente ahora mismo si instalaste antes de la fase 2: importar
`sql/semilla_proveedores.sql` en phpMyAdmin. El catálogo de proveedores es la
segunda puerta del agrupador, y sin él solo agrupa la similitud de titulares.

---

## 9. El boletín: alta con doble confirmación

Hay dos caminos y el sitio elige solo:

- **Buzón propio (SMTP).** Es lo que está montado. La lista vive en la tabla
  `suscriptores` y los correos salen por el buzón del dominio.
- **Proveedor (MailerLite o Brevo).** Sigue soportado para el día que haya
  cuenta. Se activa poniendo `correo.api_key` y `correo.lista` en
  `config/config.php`.

Mientras no haya ni lo uno ni lo otro, el bloque del final de cada página dice
que el alta no está abierta y ofrece el RSS. No hay formulario roto en ningún
momento.

### 9.1 Configurar el buzón propio

**En el panel → Correo.** No se toca ningún fichero y no hace falta phpMyAdmin.

| Campo | Valor en Hostinger |
|---|---|
| Servidor de salida | `smtp.hostinger.com` |
| Puerto | `465` (SSL) o `587` (STARTTLS) |
| Usuario | la dirección completa, p. ej. `conserje@tudominio.com` |
| Contraseña | la del buzón |
| Remitente | normalmente la misma dirección |

La contraseña **solo se teclea ahí**. Se guarda en `config/correo.php`, que no
está en el repositorio, lo escribe el panel con permisos 0600 y Apache no lo
sirve. El formulario nunca la vuelve a mostrar: si hay que cambiarla, se
escribe entera.

Si esa contraseña ha pasado alguna vez por un correo, un chat o una captura,
cámbiala en hPanel → **Correos → Cuentas** y vuelve a guardarla aquí. Una
contraseña que ha viajado por un canal que no controlas es una contraseña
prestada.

En la misma página hay un botón para **mandarte una prueba**. Merece la pena
usarlo el día que lo configuras y no el martes, con la edición cerrada
esperando.

### 9.2 Qué pasa cuando alguien se suscribe

1. El formulario envía a `api/suscribir.php`, que valida la dirección, aplica
   el límite por IP y guarda el alta como **pendiente** con un testigo
   aleatorio.
2. Sale un correo con el enlace de confirmación, que vale **tres días** y solo
   se puede usar una vez.
3. Al pulsarlo, `api/confirmar.php` la marca como **confirmada**. Hasta ese
   momento no recibe nada.
4. Cada envío llevará el enlace de baja, firmado con HMAC y sin caducidad.
   `api/baja.php` la marca como **baja**; la fila no se borra, porque guardar
   la baja es lo único que impide volver a escribir a quien ya dijo que no.

El panel → Correo enseña cuántos hay confirmados, pendientes y de baja.
`/salud.php` también, sin direcciones.

### 9.3 Comprobarlo

Suscríbete con una dirección de verdad y confirma. Si algo falla, la página de
respuesta lo dice sin contar nada del servidor, y el detalle queda en el
registro de errores de PHP.

El endpoint permite cinco altas por hora y por IP, contadas en `cache/altas/`
con la IP convertida en HMAC: sirve para contar, no para saber quién es.

---

## 10. Registros DNS del correo

Con el buzón del propio Hostinger, el SPF y el DKIM normalmente ya están
puestos al activar el correo del dominio: compruébalo en hPanel → **Correos →
Configuración del dominio** antes de tocar nada. Lo de abajo es para cuando el
correo sale por un proveedor externo, en hPanel → **Dominios → DNS**:

### SPF

Solo puede existir un registro SPF. Si ya tienes uno, añade el `include`.

| Proveedor | Tipo | Nombre | Valor |
|---|---|---|---|
| MailerLite | TXT | `@` | `v=spf1 include:_spf.mlsend.com ~all` |
| Brevo | TXT | `@` | `v=spf1 include:spf.brevo.com ~all` |
| Los dos | TXT | `@` | `v=spf1 include:_spf.mlsend.com include:spf.brevo.com ~all` |

### DKIM

Los **valores** los genera cada proveedor en su panel al verificar el dominio.
Aquí van solo los nombres de registro.

| Proveedor | Tipo | Nombre |
|---|---|---|
| MailerLite | TXT | `ml._domainkey` |
| Brevo | TXT | `brevo._domainkey` |
| Brevo (verificación) | TXT | `@`, valor `brevo-code:…` |

### DMARC

| Tipo | Nombre | Valor |
|---|---|---|
| TXT | `_dmarc` | `v=DMARC1; p=none; rua=mailto:dmarc@bitandbreakfast.com; adkim=r; aspf=r; pct=100` |

Empieza en `p=none` un par de semanas, revisa los informes y solo entonces sube
a `p=quarantine`. Poner `p=reject` el primer día es la forma más rápida de que
tus propios envíos desaparezcan.

---

## 11. Si algo falla

| Síntoma | Causa probable | Arreglo |
|---|---|---|
| El dominio no lleva al instalador | `mod_rewrite` desactivado | abre `https://tudominio.com/instalar.php` directamente |
| El instalador dice que hay otra instalación en curso | quedó un cerrojo de otra pestaña | espera a que caduque o borra `config/.instalacion` |
| `instalar.php` se descarga en vez de ejecutarse | PHP no está activo en ese dominio | revisa Configuración PHP |
| La raíz devuelve 403 | `publico/` vacío y sin `index.php` desplegado | vuelve a desplegar; el arranque necesita `index.php` |
| Error de clave ajena al instalar | la base se creó en MyISAM | pídeme el esquema sin claves ajenas |
| Error de colación `utf8mb4_unicode_ci` | MariaDB muy antigua | pídeme el esquema con `utf8mb4_general_ci` |
| `/config/config.ejemplo.php` se descarga | el `.htaccess` no se aplica | borra `config/config.php` y avísame |
| El cron no deja rastro en el log | ruta de PHP o de `logs/` equivocada | prueba con la ruta del desplegable de hPanel |
