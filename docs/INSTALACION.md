# Instalación y despliegue en Hostinger

Despliegue por **Git** desde hPanel e instalación de la base de datos con el
**instalador web**. No hace falta FTP ni tocar phpMyAdmin.

El orden es este y conviene no saltárselo:

1. Preparar PHP.
2. Crear la base de datos vacía.
3. Conectar el repositorio en hPanel → GIT y desplegar.
4. Armar y ejecutar `instalar.php`.
5. Comprobar los cerrojos y crear la tarea cron.

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

### 4.1 Armarlo

El instalador viene desarmado a propósito: como está en el repositorio, cada
despliegue lo vuelve a dejar en el servidor, y una página capaz de escribir la
configuración no puede quedarse accesible.

Administrador de archivos → carpeta `config/` → **Nuevo archivo** → nombre
exacto, sin extensión:

```
INSTALAR_PERMITIDO
```

Déjalo vacío. Con eso queda armado.

### 4.2 Ejecutarlo

Abre en el navegador:

```
https://bitandbreakfast.com/instalar.php
```

Verás primero una tabla de comprobaciones del servidor: versión de PHP,
extensiones, permisos de escritura de `config/`, `cache/` y `publico/`, y
presencia de los tres ficheros SQL. Si algo sale en rojo, arréglalo antes de
seguir.

Después rellena:

| Campo | Qué poner |
|---|---|
| Servidor | `localhost` |
| Nombre de la base | `uXXXXXXXXX_bitb` |
| Usuario | `uXXXXXXXXX_bitb` |
| Contraseña | la del paso 2 |
| Dominio | `bitandbreakfast.com`, sin `https://` ni barra final |
| Usuario del panel | opcional, hace falta en la fase 3 |
| Contraseña del panel | mínimo 12 caracteres |

Pulsa **Instalar**. El instalador:

1. Conecta con la base.
2. Ejecuta `sql/esquema.sql`, `sql/semilla_fuentes.sql` y
   `sql/semilla_diccionario.sql`.
3. Genera los tres secretos con `random_bytes`.
4. Escribe `config/config.php` con permisos 600.
5. Crea el usuario del panel con `password_hash`.
6. **Borra `config/INSTALAR_PERMITIDO`**, con lo que se desarma solo.

Al terminar te enseña el recuento (59 fuentes, 137 términos, 24 ajustes), el
token de la API y la línea de cron ya con tu ruta real. Cópiala.

> ⚠️ El esquema empieza con `DROP TABLE IF EXISTS`. Volver a ejecutar el
> instalador sobre una base con datos los borra. Por eso, si ya existe
> `config/config.php`, se niega a hacer nada.

---

## 5. Comprobar los cerrojos

Abre en el navegador estas tres direcciones. Las tres tienen que devolver
**403 Forbidden**:

```
https://bitandbreakfast.com/config/config.ejemplo.php
https://bitandbreakfast.com/lib/db.php
https://bitandbreakfast.com/cron/ingesta.php
```

Si alguna devuelve el contenido del fichero, el `.htaccess` no se está
aplicando: borra `config/config.php` y no sigas hasta arreglarlo, porque ahí
están tus credenciales.

---

## 6. La tarea programada

Una sola entrada de cron: `cron/tareas.php` decide qué toca en cada ejecución.

hPanel → **Avanzado → Trabajos cron** → *Crear nuevo trabajo cron*:

- Tipo: **Comando personalizado**
- Frecuencia: **Cada hora**
- Comando: el que te dio el instalador, con esta forma:

```
/usr/bin/php /home/uXXXXXXXXX/domains/bitandbreakfast.com/public_html/cron/tareas.php >> /home/uXXXXXXXXX/logs/bitb-cron.log 2>&1
```

Crea antes la carpeta `logs/` colgando de `/home/uXXXXXXXXX/`, **fuera** de
`public_html`, para que el registro no se pueda leer desde la web.

Si `/usr/bin/php` no fuese PHP 8.1, usa la ruta que ofrezca el desplegable de
esa misma pantalla, del estilo `/opt/alt/php81/usr/bin/php`.

---

## 7. Primera ejecución y criterio de aceptación

Para no esperar una hora, pon la tarea *cada minuto*, déjala correr tres o
cuatro veces y devuélvela a *cada hora*. Con Terminal SSH es más directo:

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

Qué sobrevive a cada despliegue, porque no está en el repositorio:

- `config/config.php` — tu configuración.
- `publico/` — la web generada.
- `cache/` — los `robots.txt` cacheados.

Si un despliegue trae SQL nuevo, lo ejecutas a mano en phpMyAdmin: el
instalador no sirve para actualizar, solo para instalar desde cero.

---

## 9. Registros DNS del correo (fase 5)

Todavía no hace falta. Cuando decidas proveedor, en hPanel → **Dominios → DNS**:

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

## 10. Si algo falla

| Síntoma | Causa probable | Arreglo |
|---|---|---|
| `instalar.php` dice que está desarmado | falta `config/INSTALAR_PERMITIDO` | créalo en el Administrador de archivos |
| `instalar.php` descarga el fichero en vez de ejecutarlo | PHP no está activo en ese dominio | revisa Configuración PHP |
| Error de clave ajena al instalar | la base se creó en MyISAM | pídeme el esquema sin claves ajenas |
| Error de colación `utf8mb4_unicode_ci` | MariaDB muy antigua | pídeme el esquema con `utf8mb4_general_ci` |
| `/config/config.ejemplo.php` se descarga | el `.htaccess` no se aplica | borra `config/config.php` y avísame |
| El cron no deja rastro en el log | ruta de PHP o de `logs/` equivocada | prueba con la ruta del desplegable de hPanel |
