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
config/     configuración (config.php no está en el repositorio)
lib/        utilidades: PDO, feeds, robots.txt, normalización de texto, URLs
cron/       tareas programadas; tareas.php es el despachador único
api/        endpoints públicos: redirección contada, votos, redacción asistida
panel/      zona privada de curación
plantillas/ plantillas de la web y del correo
publico/    salida estática generada (no se versiona)
pruebas/    scripts de prueba sin framework
sql/        esquema y semillas
docs/       esta documentación e INSTALACION.md
```

## Puesta en marcha

Ver [INSTALACION.md](INSTALACION.md). Resumen: crear la base de datos, importar
`sql/esquema.sql` y las dos semillas, copiar `config/config.ejemplo.php` a
`config/config.php`, subir por FTP y crear una tarea cron horaria que ejecute
`cron/tareas.php`.

## Pruebas

Sin framework. Comparan salida esperada contra salida real y devuelven código
de salida 1 si algo falla.

```bash
php pruebas/texto.php
php pruebas/canonica.php
php pruebas/comprobar_feeds.php
```

Las dos primeras no tocan la base de datos. La tercera sí la lee, y sale a la
red a comprobar que las fuentes del catálogo siguen vivas.

## Estado

| Fase | Alcance | Estado |
|---|---|---|
| 1 | Esquema, utilidades de texto y URL, ingesta, semillas | completada |
| 2 | Agrupación, puntuación, `cron/procesar.php` | pendiente |
| 3 | Panel de curación | pendiente |
| 4 | Generador estático, archivo, RSS | pendiente |
| 5 | Proveedor de correo y alta con doble confirmación | pendiente |
| 6 | Fichas de proveedor, buscador, votos, redacción asistida | pendiente |

## Decisiones que conviene no olvidar

- **La similitud de titulares no cruza idiomas.** Jaccard sobre shingles de
  tres caracteres da casi cero entre un titular inglés y su equivalente en
  español. La agrupación entre idiomas se apoyará en proveedores y cifras
  compartidas, no en el texto.
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
