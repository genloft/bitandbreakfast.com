# Instalación de Bit & Breakfast en Hostinger

Guía completa para dejar el sistema funcionando en un alojamiento compartido de
Hostinger con hPanel. Está escrita para el escenario que hemos decidido: **todo
el proyecto vive dentro de `public_html`** y los directorios privados se
protegen con `.htaccess`.

Sustituye en todos los ejemplos:

| Marcador | Qué es | Dónde lo ves |
|---|---|---|
| `uXXXXXXXXX` | tu identificador de usuario en el servidor | hPanel → Archivos → Administrador de archivos, en la ruta |
| `bitandbreakfast.com` | tu dominio | hPanel → Dominios |
| `uXXXXXXXXX_bitb` | nombre de la base de datos | lo eliges tú en el paso 2 |

---

## 1. Comprobar el entorno

En hPanel → **Avanzado → Configuración PHP**:

1. Selecciona **PHP 8.1 o superior**.
2. En la pestaña *Extensiones PHP*, asegúrate de que están activas:
   `pdo_mysql`, `curl`, `mbstring`, `simplexml`, `openssl`, `json`.
3. En la pestaña *Opciones PHP*, sube `max_execution_time` a 60 si te deja.
   No es imprescindible: todos los procesos largos están troceados en lotes,
   pero da margen.

> El cron no usa esta configuración, sino la del PHP de línea de comandos, que
> en Hostinger normalmente no tiene límite de tiempo. Aun así, el sistema está
> diseñado para no depender de eso.

---

## 2. Crear la base de datos

hPanel → **Bases de datos → MySQL**:

1. *Crear una nueva base de datos MySQL*.
2. Nombre de la base: `bitb` (hPanel le antepone `uXXXXXXXXX_`).
3. Usuario: `bitb` (queda como `uXXXXXXXXX_bitb`).
4. Contraseña: genera una larga y guárdala; la necesitas en el paso 5.
5. Anota el **host**: casi siempre `localhost`.

---

## 3. Importar el esquema y las semillas

hPanel → **Bases de datos → phpMyAdmin** → entra en la base recién creada →
pestaña **Importar**. Sube y ejecuta **en este orden**:

1. `sql/esquema.sql` — crea las 16 tablas y rellena la tabla `ajustes`.
2. `sql/semilla_fuentes.sql` — 59 fuentes verificadas.
3. `sql/semilla_diccionario.sql` — términos de puntuación y filtro anti-publirreportaje.

Comprobación rápida, en la pestaña **SQL**:

```sql
SELECT COUNT(*) AS fuentes FROM fuentes;        -- debe dar 59
SELECT COUNT(*) AS terminos FROM diccionario;   -- debe dar 137 (117 positivos y 20 negativos)
SELECT valor FROM ajustes WHERE clave = 'ingesta_puntero';  -- debe dar 0
```

---

## 4. Subir los ficheros por FTP

Sube el contenido del repositorio a `/home/uXXXXXXXXX/domains/bitandbreakfast.com/public_html/`,
respetando esta estructura:

```
public_html/
├── .htaccess              <- cabeceras de seguridad y bloqueo de directorios
├── config/                <- PRIVADO
│   └── config.php         <- lo creas tú en el paso 5, nunca viene del repositorio
├── lib/                   <- PRIVADO
├── cron/                  <- PRIVADO
├── sql/                   <- PRIVADO
├── docs/                  <- PRIVADO
├── pruebas/               <- PRIVADO
├── api/                   <- público
├── panel/                 <- público, protegido por login
├── plantillas/            <- PRIVADO
└── publico/               <- la web estática generada
```

**Permisos** (hPanel → Administrador de archivos → clic derecho → Permisos):

| Ruta | Permisos | Motivo |
|---|---|---|
| directorios en general | `755` | |
| ficheros `.php` y `.sql` | `644` | |
| `publico/` | `755` con escritura del usuario | lo reescribe `cron/publicar.php` |
| `cache/` | `755` con escritura del usuario | guarda los `robots.txt` cacheados |
| `config/config.php` | `600` | contiene credenciales |

Crea a mano los directorios `publico/` y `cache/` si no han subido vacíos.

### Los ficheros `.htaccess` no son opcionales

Como todo está dentro de `public_html`, lo único que impide que alguien lea
`https://bitandbreakfast.com/config/config.php` es el `.htaccess`. Verifícalo
**antes** de meter credenciales reales: abre esa URL en el navegador y confirma
que devuelve **403 Forbidden**. Si devuelve el contenido del fichero, para y
avísame: ese plan no respeta `.htaccess` y hay que cambiar de estrategia.

Repite la comprobación con `/lib/db.php` y `/cron/ingesta.php`.

---

## 5. Crear `config/config.php`

Copia `config/config.ejemplo.php` a `config/config.php` y rellénalo:

```php
'bd' => [
    'host'    => 'localhost',
    'nombre'  => 'uXXXXXXXXX_bitb',
    'usuario' => 'uXXXXXXXXX_bitb',
    'clave'   => 'la contraseña del paso 2',
],
```

Genera los tres secretos con phpMyAdmin o con cualquier generador de claves de
64 caracteres hexadecimales:

- `secreto_hmac` — firma los enlaces de voto del correo.
- `token_api` — autentica `api/candidatos.php` y `api/bits.php`.
- `sal_hash` — sal para el hash de IP de los votos.

`config/config.php` **no se sube al repositorio**. Ya está en `.gitignore`.

---

## 6. Crear tu usuario del panel

En phpMyAdmin, pestaña SQL. Primero genera el hash de tu contraseña: crea un
fichero temporal `hash.php` en `public_html/`, ábrelo en el navegador, copia el
resultado y **bórralo inmediatamente**.

```php
<?php echo password_hash('tu-contraseña-larga-aqui', PASSWORD_DEFAULT);
```

Después:

```sql
INSERT INTO usuarios (usuario, hash_clave, nombre, activo)
VALUES ('juan', '$2y$10$el-hash-que-has-copiado', 'Juan', 1);
```

---

## 7. La tarea programada

Has elegido el despachador único, así que **solo hace falta una entrada de
cron**. `cron/tareas.php` mira el reloj y decide qué toca en cada ejecución:
ingesta cada hora, procesado a continuación, mantenimiento de madrugada y
publicación cuando hay bits nuevos. Cada tarea trabaja por lotes con puntero
persistente, así que si una ejecución se queda a medias, la siguiente continúa
donde lo dejó.

hPanel → **Avanzado → Trabajos cron** → *Crear nuevo trabajo cron*:

- Tipo de comando: **Comando personalizado**.
- Frecuencia: **Cada hora** (o el intervalo más corto que permita tu plan).
- Comando, literal:

```
/usr/bin/php /home/uXXXXXXXXX/domains/bitandbreakfast.com/public_html/cron/tareas.php >> /home/uXXXXXXXXX/logs/bitb-cron.log 2>&1
```

Si `/usr/bin/php` no fuese PHP 8.1, mira el que ofrece hPanel en el desplegable
de la sección de cron (suele ser algo como `/opt/alt/php81/usr/bin/php`) y usa
esa ruta.

Crea el directorio `logs/` fuera de `public_html` para que el registro no sea
accesible desde la web:

```
/home/uXXXXXXXXX/logs/
```

### Comprobar que funciona sin esperar una hora

Desde el navegador no se puede: `cron/` está bloqueado por `.htaccess`, y así
debe seguir. Usa el **Terminal SSH** de hPanel si tu plan lo incluye:

```bash
php /home/uXXXXXXXXX/domains/bitandbreakfast.com/public_html/cron/tareas.php ingesta
```

Si no tienes SSH, crea temporalmente la tarea cron con frecuencia *cada minuto*,
déjala correr tres veces, y devuélvela a *cada hora*.

Criterio de aceptación de la fase 1, en phpMyAdmin:

```sql
SELECT COUNT(*) FROM items;                      -- cientos de filas
SELECT COUNT(*) - COUNT(DISTINCT hash_url) FROM items;  -- tiene que dar 0
SELECT valor FROM ajustes WHERE clave = 'ingesta_puntero';  -- ha avanzado
SELECT f.nombre, l.resultado, l.nuevos, l.mensaje
  FROM log_ingesta l JOIN fuentes f ON f.id = l.fuente_id
 ORDER BY l.inicio DESC LIMIT 20;
```

---

## 8. Registros DNS del correo

Esto es lo que decide si la newsletter llega a la bandeja de entrada o a correo
no deseado. Documento los dos proveedores porque decidiremos en la fase 5.

hPanel → **Dominios → DNS / Nameservers**.

> Los **valores** de DKIM los genera cada proveedor en su propio panel cuando
> añades y verificas el dominio. Aquí están los nombres y la forma de cada
> registro; el contenido exacto lo copias de allí. No te fíes de ningún valor
> de DKIM que no venga del panel del proveedor.

### 8.1 SPF

Solo puede existir **un** registro SPF en el dominio. Si ya tienes uno, añade el
`include` al existente en vez de crear otro.

| Proveedor | Tipo | Nombre | Valor |
|---|---|---|---|
| MailerLite | TXT | `@` | `v=spf1 include:_spf.mlsend.com ~all` |
| Brevo | TXT | `@` | `v=spf1 include:spf.brevo.com ~all` |
| Los dos a la vez | TXT | `@` | `v=spf1 include:_spf.mlsend.com include:spf.brevo.com ~all` |

Usa `~all` (softfail) mientras pruebas y pasa a `-all` cuando lleves varios
envíos limpios.

### 8.2 DKIM

| Proveedor | Tipo | Nombre | Valor |
|---|---|---|---|
| MailerLite | TXT | `ml._domainkey` | `k=rsa; p=…` el que te da MailerLite |
| Brevo | TXT | `brevo._domainkey` | `k=rsa; p=…` el que te da Brevo |
| Brevo (verificación) | TXT | `@` | `brevo-code:…` el que te da Brevo |

Si acabas usando los dos proveedores a la vez, no hay conflicto: cada uno firma
con su propio selector.

### 8.3 DMARC

Un único registro:

| Tipo | Nombre | Valor |
|---|---|---|
| TXT | `_dmarc` | `v=DMARC1; p=none; rua=mailto:dmarc@bitandbreakfast.com; adkim=r; aspf=r; pct=100` |

Empieza con `p=none` durante al menos dos semanas y revisa los informes que
lleguen a esa dirección. Cuando confirmes que todo el correo legítimo pasa SPF
y DKIM, sube a `p=quarantine` y más tarde a `p=reject`. Poner `p=reject` desde
el primer día es la forma más rápida de que tus propios envíos desaparezcan.

### 8.4 Comprobación

Después de propagar (de 15 minutos a 4 horas), verifica con
`https://mxtoolbox.com/spf.aspx` y `https://mxtoolbox.com/dmarc.aspx`, o manda
un correo de prueba a `check-auth@verifier.port25.com`.

---

## 9. El rastreador y la buena ciudadanía

El bot se identifica así:

```
Mozilla/5.0 (compatible; BitAndBreakfastBot/1.0; +https://bitandbreakfast.com/bot)
```

El prefijo `Mozilla/5.0 (compatible; …)` es el formato que usan los rastreadores
legítimos (bingbot entre ellos) y evita que dos fuentes buenas nos devuelvan un
403 automático. El nombre del bot y la URL de contacto siguen ahí, que es lo que
importa.

Cuando publiques la web (fase 4), la página `/bot` explicará qué es el
rastreador y cómo pedir la exclusión. Hasta entonces, el sistema ya respeta
`robots.txt` y espera un segundo entre peticiones al mismo dominio.

---

## 10. Resumen de lo que hay que hacer en cada despliegue

1. Subir por FTP los ficheros cambiados.
2. Si hay SQL nuevo, ejecutarlo en phpMyAdmin.
3. Comprobar el registro: `/home/uXXXXXXXXX/logs/bitb-cron.log`.
4. Comprobar `log_ingesta` en busca de fuentes con `resultado = 'error'`.
