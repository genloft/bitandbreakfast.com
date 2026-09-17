# El cron, explicado

Este documento contesta tres preguntas: **qué hace el cron**, **qué va a pasar
cada día** y **qué mirar cuando algo no cuadra**.

---

## 1. Qué es

Hay **una sola entrada de cron** en hPanel. Ejecuta esto:

```
php /home/TU_USUARIO/domains/bitandbreakfast.com/public_html/cron/tareas.php
```

Ese fichero es un despachador: mira qué toca y llama a las tareas en orden.
No hay más crons. Si esa línea deja de correr, el sitio se congela (y la
portada lo nota: pasa a mantenerse sola con las visitas, más lento y más
tosco, hasta que el cron vuelve).

**Frecuencia: cada 5 minutos.** En hPanel → Avanzado → Trabajos cron.

Por qué cinco y no sesenta: cada pasada lee un lote de fuentes y la siguiente
sigue por donde iba. La frecuencia es, literalmente, cuántas veces al día se
recorre el catálogo entero. Con 200 fuentes y 15 por pasada:

| Cron cada | Vuelta completa al catálogo | Lecturas al día |
|-----------|------------------------------|-----------------|
| 60 min    | 13 h                         | 360             |
| 15 min    | 3 h 20                       | 1.440           |
| **5 min** | **1 h 7**                    | **4.320**       |

---

## 2. Qué hace en cada pasada

Siempre en este orden. Cada tarea tiene **25 segundos** de presupuesto y la
pasada entera un techo de **75**. Quedarse sin tiempo no pierde trabajo: se
reparte en más pasadas, porque todas llevan puntero.

| # | Tarea | Qué hace | Cuándo corre |
|---|-------|----------|--------------|
| 0 | **cerrojo** | Si la pasada anterior sigue viva, ésta se va sin tocar nada | siempre |
| 0 | **migraciones** | Aplica los cambios de esquema que traiga el despliegue | siempre |
| 1 | **ingesta** | Descarga un lote de feeds y guarda las entradas nuevas | siempre |
| 2 | **procesar** | Limpia, deduplica y **agrupa en racimos** lo que dice lo mismo | siempre |
| 3 | **auto** | Aplica las puertas y **escribe los bits** que las pasan | siempre |
| 4 | **publicar** | Regenera el HTML estático si algo ha cambiado | siempre |
| 5 | **enviar** | Manda una tanda del boletín | siempre |
| 6 | **mantenimiento** | Limpieza diaria | 1 vez al día, a partir de las 05:00 UTC |

Las tareas 4 y 5 no entran en el reparto de tiempo: son lo único que el lector
llega a ver, y una pasada que rastrea y escribe pero no genera la web no ha
servido de nada. Las dos salen en milisegundos cuando no hay nada que hacer.

### El embudo, con números reales

De cada 100 entradas que entran por ingesta, llegan a la web muy pocas, y eso
es deliberado:

```
1.200 entradas nuevas al día      ingesta
  ↓ deduplicado y agrupado
  ~700 racimos                    procesar
  ↓ puerta de puntuación (umbral 30)
  ↓ puerta del diccionario (habla de tecnología hotelera)
  ↓ puerta del sector (habla de hoteles)
  ↓ no es recopilatorio, ni promoción, ni guía, ni entrevista
  ↓ tiene cuerpo utilizable
   ~10-30 bits                    auto
```

Cuando la portada trae poco, **el embudo dice dónde se cae**: `/salud.php`
tiene `cola.racimos_candidatos` (lo que espera) y `cola.racimos_descartados`
(lo que se cayó). Cada descarte guarda su motivo en `racimos.motivo_descarte`.

---

## 3. Qué esperar cada día

Con el cron a 5 minutos y el catálogo grande:

- **Cada 5 min**: entran entradas nuevas, se agrupan, se escriben los bits que
  pasen las puertas y, si hay alguno, se regenera la web. La barra de arriba
  del sitio dice la hora exacta de la última actualización.
- **Cada hora como mucho**: un correo de aviso con lo que ha pasado desde el
  anterior. No es uno por pasada: lo de las doce pasadas intermedias se suma y
  sale junto. Se controla con el ajuste `cron_aviso`
  (`siempre` | `cambios` | `no`) y `cron_aviso_minutos`.
- **Una vez al día, a partir de las 05:00 UTC**: mantenimiento.
- **Una vez al día**: el boletín con lo descubierto el día anterior.

---

## 4. Cuando algo no cuadra

**Todo empieza en `https://bitandbreakfast.com/salud.php`.** Es JSON y se lee
de un vistazo:

| Lo que ves | Lo que significa | Qué hacer |
|------------|------------------|-----------|
| `cron.callado: true` | El cron lleva más de 2 h sin pasar | Revisar la tarea en hPanel |
| `web.generada` muy vieja pero `cron.ultima_vez` reciente | El cron pasa pero no hay nada nuevo que publicar | Mirar el embudo |
| `fuentes.dormidas` sube y no baja | Muchas fuentes fallando a la vez | Casi siempre es la IP compartida; se pasa solo |
| `fuentes.fallando` con `robots.txt prohibe…` | Ese medio no deja leer su feed | Nada: duerme una semana y se reintenta |
| `fuentes.fallando` con `http 403` | Cortafuegos del medio o de su CDN | Si persiste días, hablar con el medio |
| `cola.items_sin_agrupar` creciendo | `procesar` no da abasto | Subir `presupuesto_cron` |
| `criterios.codigo` ≠ `criterios.aplicados` | Hay criterios nuevos sin aplicar a lo ya publicado | Se aplica solo en las siguientes pasadas |

**Los ajustes que gobiernan el ritmo** (tabla `ajustes` en la base de datos):

| Ajuste | Qué hace | Valor hoy |
|--------|----------|-----------|
| `ingesta_lote` | Fuentes por pasada | 15 |
| `auto_umbral` | Puntuación mínima para publicar sin revisar | 30 |
| `auto_min_diccionario` | Señal temática mínima | 8 |
| `auto_publicar` | Interruptor general del modo automático | 1 |
| `cron_aviso` | Avisos por correo | cambios |
| `cron_aviso_minutos` | Mínimo entre avisos | 60 |

---

## 5. Probar una tarea suelta

Por SSH, si el plan lo permite:

```bash
php cron/tareas.php ingesta     # solo la ingesta
php cron/tareas.php auto        # solo la escritura de bits
php cron/tareas.php publicar    # solo regenerar la web
```

Forzar una tarea se salta el reparto de tiempo y el calendario, pero **no el
cerrojo**: si el cron está corriendo en ese momento, espera a la siguiente.
