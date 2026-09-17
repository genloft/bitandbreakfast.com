<?php
/**
 * La hoja de estilo del sitio publicado.
 *
 * Es una plantilla y no un .css suelto porque el generador la escribe como
 * escribe todo lo demas: asi hay un unico sitio del que sale publico/, y
 * borrar esa carpeta entera nunca pierde nada que no se pueda regenerar.
 *
 * Las decisiones de fondo, para no deshacerlas sin querer:
 *
 *   - Oscuro siempre, no segun el sistema. Es una decision de marca, como la
 *     iluminacion del vestibulo de un hotel: no se enciende y se apaga segun
 *     quien entre.
 *   - Serif para todo lo que se lee y para el logotipo. El sans se reserva a
 *     las etiquetas diminutas en versalitas, que es donde una serif pequena
 *     se ensucia.
 *   - Laton sobre casi negro, y poco. El acento aparece en el ampersand del
 *     logotipo, en los numeros de los bits y en el filete del "por que
 *     importa". Un acento que sale en todas partes deja de ser un acento.
 *   - Movil primero. Las reglas base son las del telefono y las medias
 *     consultas solo anaden.
 *   - Cero dependencias: ni fuentes de Google, ni iconos, ni reset de nadie.
 *     La politica de seguridad del sitio no permite cargar nada de fuera.
 *
 * Sobre las familias: no hay fuente incrustada a proposito. Las pilas eligen
 * la mejor serif de cada sistema -Hoefler Text en Mac, Palatino en Windows,
 * Georgia en todas partes- y quedan bien en los tres sin pedirle al lector que
 * descargue nada. El dia que haya licencia de una fuente propia, se sirve
 * desde publico/ y solo cambia la variable --display.
 */

declare(strict_types=1);

?>
:root {
  color-scheme: dark;

  /* Retrofuturismo, que no es neon por todas partes: es el azul de una sala de
     control de 1979 con una pantalla ambar encendida. Dos acentos, el ambar de
     los fosforos viejos y el cian de los tubos, y nada mas. El tercero, el
     magenta, aparece cinco veces en toda la web y por eso se ve. */
  --fondo:   #060a18;
  --papel:   #0b1026;
  --realce:  #111838;
  --borde:   #232f63;
  --borde-suave: #18204a;
  --tinta:   #e8ecff;
  --apagado: #97a2d8;

  --acento:  #ffb454;   /* ambar de fosforo */
  --acento-suave: #8a6a34;
  --enlace:  #5ce1e6;   /* cian de tubo */
  --enlace-suave: #2b6f85;
  --magenta: #ff6ec7;

  /* Un color por tematica, dentro de la misma paleta: cian, ambar y magenta
     con desvios cortos. Juntos tienen que parecer los pilotos de un mismo
     panel, no once rotuladores. */
  --t-tecnologia-general:          #9fb2d6;
  --t-pms-crs:                     #5ce1e6;
  --t-distribucion-otas:           #56d7b0;
  --t-revenue-rms:                 #ffb454;
  --t-pagos-fraude:                #ff9c6e;
  --t-ciberseguridad-cumplimiento: #ff6ec7;
  --t-operaciones-iot:             #9aa6e8;
  --t-experiencia-huesped:         #c78cf5;
  --t-ia-aplicada:                 #8de06a;
  --t-sostenibilidad-energia:      #62d79a;
  --t-inversion-mercado:           #ffd166;

  /* Futura y Century Gothic son las geometricas que ya estan en Mac y en
     Windows, y son LA letra del futuro de hace cincuenta anos: la de 2001 y la
     de los manuales de la NASA. Sin descargar nada, que la politica del sitio
     no deja cargar de fuera. */
  --marca:   Futura, "Century Gothic", "Avenir Next", Avenir, "Trebuchet MS",
             ui-sans-serif, sans-serif;
  --display: "Hoefler Text", "Iowan Old Style", "Palatino Linotype", Palatino,
             "Book Antiqua", Georgia, "Times New Roman", serif;
  --cuerpo:  Georgia, "Iowan Old Style", "Times New Roman", serif;
  --ui:      ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI",
             system-ui, sans-serif;
  /* El monoespaciado es el que pone el "futurista" en todo lo que no es texto
     corrido: fechas, numeros, etiquetas, filtros. Voz de terminal. */
  --mono:    ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas,
             "Liberation Mono", monospace;

  --ancho:  44rem;
  --gutter: clamp(1.25rem, 5vw, 3rem);

  /* El chaflan de las esquinas, que es lo que hace que una caja parezca de un
     panel y no de un formulario. */
  --chaflan: 7px;
}

*, *::before, *::after { box-sizing: border-box; }

html { -webkit-text-size-adjust: 100%; }

body {
  margin: 0;
  color: var(--tinta);

  /* Tres capas que no se ven por separado y juntas son la sala de control: una
     rejilla de milimetrado muy tenue, una luz cian detras del logotipo y otra
     ambar abajo. Nada se mueve: una web que parpadea es un juguete, y esto se
     lee cinco minutos a la semana. */
  background:
    linear-gradient(rgba(92, 225, 230, .035) 1px, transparent 1px) 0 0 / 100% 3rem,
    linear-gradient(90deg, rgba(92, 225, 230, .035) 1px, transparent 1px) 0 0 / 3rem 100%,
    radial-gradient(120% 70% at 85% -10%, rgba(92, 225, 230, .13), transparent 62%),
    radial-gradient(90% 60% at 0% 110%, rgba(255, 180, 84, .08), transparent 60%),
    var(--fondo);
  background-attachment: fixed;
  font-family: var(--cuerpo);
  font-size: clamp(1.0625rem, 1rem + 0.3vw, 1.1875rem);
  line-height: 1.75;
  overflow-wrap: break-word;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

a {
  color: var(--tinta);
  text-decoration-color: var(--enlace-suave);
  text-decoration-thickness: 1px;
  text-underline-offset: .2em;
}
a:hover { text-decoration-color: var(--enlace); }
a:focus-visible { outline: 1px solid var(--enlace); outline-offset: 4px; }

::selection { background: var(--enlace); color: var(--fondo); }

/* Monoespaciada en todo lo que no es texto corrido: fechas, numeros,
   etiquetas, filtros, pies. Es la voz de la casa y es donde vive el
   "futurista" -una terminal-, mientras el texto que se lee de verdad sigue en
   serif, que es donde vive el "retro". */
.sello, .menu, .datos, .etiqueta, .letra-pequena, .sumario h2, .ano h2,
.cuenta, .menciona, .pie-bit, .buscador label, .alta-formulario label,
.facetas h3, .opcion, .limpiar, .sumario-etiqueta, .numero-lista,
.nube a, .explorar-grupo, .cuenta-opcion, .numero {
  font-family: var(--mono);
}

.sello, .sumario h2, .ano h2, .facetas h3, .explorar-grupo {
  font-size: .66rem;
  font-weight: 500;
  letter-spacing: .22em;
  text-transform: uppercase;
}

/* Los rotulos de seccion llevan delante dos barras, como un comentario de
   codigo. Es el guino mas barato que existe y coloca el tono en una linea. */
.sumario h2::before, .facetas h3::before, .explorar-grupo::before {
  content: "// ";
  color: var(--enlace-suave);
}

.saltar {
  position: absolute;
  left: -9999px;
  padding: .7rem 1.1rem;
  background: var(--papel);
  border: 1px solid var(--acento);
}
.saltar:focus { left: 1rem; top: 1rem; z-index: 10; }

/* --- Cabecera ------------------------------------------------------------
   El logotipo manda: grande, serif y a la derecha. Todo lo demas se aparta. */

.cabecera {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: clamp(2rem, 7vw, 3.5rem) var(--gutter) 1.5rem;
}

.cabecera-interior {
  display: flex;
  flex-direction: column-reverse;
  align-items: flex-end;
  gap: .9rem;
}

.logo {
  display: block;
  text-decoration: none;
  color: var(--tinta);
}

.logo:hover { text-decoration: none; color: var(--tinta); }

/* El bloque: dos lineas que miden lo mismo. La de arriba se completa con un
   filete hasta la anchura de la de abajo, asi que el conjunto es un rectangulo
   sin haber forzado el interletrado de nadie. */
.logo-bloque {
  display: block;
  width: min(100%, 26rem);
  margin-left: auto;
}

.logo-linea {
  display: flex;
  align-items: center;
  gap: .18em;
  font-family: var(--marca);
  font-weight: 500;
  font-size: clamp(1.9rem, 8.2vw, 3.1rem);
  line-height: 1.05;
  letter-spacing: .14em;
  text-transform: uppercase;
}

/* La segunda linea lleva el peso: es la palabra larga y la que fija la
   anchura del bloque. Se separa un pelo de la primera para que se lean como
   dos renglones de un rotulo y no como una palabra partida. */
.logo-linea-baja { justify-content: flex-end; margin-top: .06em; }

.logo-palabra { white-space: nowrap; }

/* El ampersand, en ambar: el unico color del logotipo. */
.logo-amp { color: var(--acento); font-weight: 400; }

/* El filete que cierra el rectangulo. Cian, porque el ambar ya esta usado en
   el ampersand y dos acentos en cuatro centimetros son uno de mas. */
.logo-filete {
  flex: 1;
  height: 2px;
  min-width: 1.5rem;
  background: linear-gradient(90deg, var(--enlace-suave), var(--enlace));
}

/* Al pasar por encima, el filete se enciende. Es el unico efecto de la casa. */
.logo:hover .logo-filete { box-shadow: 0 0 8px var(--enlace); }

.promesa {
  margin: 1.1rem 0 0 auto;
  max-width: 30rem;
  text-align: right;
  color: var(--apagado);
  font-family: var(--mono);
  font-size: .72rem;
  letter-spacing: .06em;
  line-height: 1.6;
}

.promesa-punto { color: var(--acento-suave); padding: 0 .15rem; }

.menu {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: .3rem 1.15rem;
  font-size: .7rem;
  letter-spacing: .14em;
  text-transform: uppercase;
}

.menu a {
  display: inline-block;
  padding: .45rem 0;
  color: var(--apagado);
  text-decoration: none;
}
.menu a:hover { color: var(--tinta); }
.menu a[aria-current="page"] { color: var(--acento); }

/* La linea que cierra la cabecera: doble, como el borde de un panel. */
.cabecera::after {
  content: "";
  display: block;
  height: 3px;
  margin-top: 1.6rem;
  border-top: 1px solid var(--borde);
  border-bottom: 1px solid var(--borde-suave);
  background: linear-gradient(90deg, transparent, rgba(92, 225, 230, .25) 60%, rgba(255, 180, 84, .35));
}

/* --- Estructura ---------------------------------------------------------- */

main {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: 0 var(--gutter);
}

.edicion-cabecera {
  position: relative;
  padding: 2rem 0 0;
  border-top: 1px solid var(--acento-suave);
}

/* El numero de la edicion, enorme y casi transparente, detras de la cabecera.
   No se lee: se ve. Es lo que hace que la portada parezca una portada y no la
   primera pagina de un documento. */
.edicion-numero {
  position: absolute;
  top: .2rem;
  right: 0;
  margin: 0;
  font-family: var(--display);
  font-size: clamp(5rem, 22vw, 9rem);
  line-height: .8;
  letter-spacing: -.05em;
  color: transparent;
  -webkit-text-stroke: 1px var(--borde);
  pointer-events: none;
  user-select: none;
}

.sello { margin: 0 0 .9rem; color: var(--acento); }

h1 {
  margin: 0 0 .8rem;
  font-family: var(--display);
  font-weight: 400;
  font-size: clamp(1.9rem, 7.5vw, 2.9rem);
  line-height: 1.12;
  letter-spacing: -.018em;
}

.datos {
  margin: 0;
  color: var(--apagado);
  font-size: .78rem;
  letter-spacing: .02em;
}
.punto { padding: 0 .45rem; color: var(--acento-suave); }

.intro {
  margin-top: 1.75rem;
  color: var(--apagado);
  font-size: 1.06em;
  font-style: italic;
}
.intro p { margin: 0 0 .9rem; }

/* --- Sumario -------------------------------------------------------------
   Lo que convierte esto en un radar: en diez segundos se sabe si la semana
   trae algo. */

.sumario {
  margin: 3rem calc(var(--gutter) * -1) 0;
  padding: 1.5rem var(--gutter) 1rem;
  background: var(--papel);
  border-top: 1px solid var(--borde);
  border-bottom: 1px solid var(--borde);
}

.sumario h2 { margin: 0 0 1rem; color: var(--apagado); }

.sumario ol {
  margin: 0;
  padding: 0;
  list-style: none;
  counter-reset: sumario;
}

.sumario li {
  counter-increment: sumario;
  position: relative;
  margin-bottom: .95rem;
  padding-left: 2.1rem;
  line-height: 1.45;
}

.sumario li::before {
  content: counter(sumario, decimal-leading-zero);
  position: absolute;
  left: 0;
  top: .15em;
  font-family: var(--ui);
  font-size: .7rem;
  letter-spacing: .06em;
  color: var(--acento-suave);
}

.sumario a { text-decoration: none; }
.sumario a:hover { text-decoration: underline; }

.sumario-etiqueta {
  display: flex;
  align-items: center;
  gap: .35rem;
  margin-top: .2rem;
  color: var(--tema, var(--apagado));
  font-size: .7rem;
  letter-spacing: .04em;
}

/* --- Bits ----------------------------------------------------------------- */

.bit {
  display: flex;
  gap: 1.1rem;
  padding: 2.75rem 0;
  border-bottom: 1px solid var(--borde);
  scroll-margin-top: 1.5rem;
}

.bit:first-child { padding-top: 3rem; }
.bit:last-child { border-bottom: 0; }

/* El carril de la izquierda: el numero y el icono del tema, uno debajo del
   otro. Es lo que convierte una lista de parrafos en una edicion con
   estructura, y lo que deja ver de que va cada bit antes de leerlo. */
.bit-carril {
  flex: 0 0 auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .7rem;
  width: 2.4rem;
}

.numero {
  margin: 0;
  font-size: .95rem;
  line-height: 1;
  letter-spacing: .06em;
  color: var(--acento);
  font-variant-numeric: tabular-nums;
}

/* Con cero delante, como un contador: 01, 02, 03. */
.numero::before { content: "0"; opacity: .45; }
.bit:nth-child(n+10) .numero::before { content: ""; }

/* El icono, con el color de su tema, dentro de un circulo tenue del mismo
   color. El color vive en --tema y lo pone el atributo data-tema. */
.bit-icono {
  display: grid;
  place-items: center;
  width: 2.5rem;
  height: 2.5rem;
  border: 1px solid color-mix(in srgb, var(--tema, var(--apagado)) 40%, transparent);
  background: color-mix(in srgb, var(--tema, var(--apagado)) 10%, transparent);
  color: var(--tema, var(--apagado));
  clip-path: polygon(var(--chaflan) 0, 100% 0, 100% calc(100% - var(--chaflan)), calc(100% - var(--chaflan)) 100%, 0 100%, 0 var(--chaflan));
}

.icono { width: 1.15rem; height: 1.15rem; display: block; }
.icono-mini { width: .85rem; height: .85rem; }

/* Un color por tema. Si el navegador no entiende color-mix, el circulo se
   queda sin fondo y sin borde y el icono sigue viendose: degrada solo. */
[data-tema="tecnologia-general"]          { --tema: var(--t-tecnologia-general); }
[data-tema="pms-crs"]                     { --tema: var(--t-pms-crs); }
[data-tema="distribucion-otas"]           { --tema: var(--t-distribucion-otas); }
[data-tema="revenue-rms"]                 { --tema: var(--t-revenue-rms); }
[data-tema="pagos-fraude"]                { --tema: var(--t-pagos-fraude); }
[data-tema="ciberseguridad-cumplimiento"] { --tema: var(--t-ciberseguridad-cumplimiento); }
[data-tema="operaciones-iot"]             { --tema: var(--t-operaciones-iot); }
[data-tema="experiencia-huesped"]         { --tema: var(--t-experiencia-huesped); }
[data-tema="ia-aplicada"]                 { --tema: var(--t-ia-aplicada); }
[data-tema="sostenibilidad-energia"]      { --tema: var(--t-sostenibilidad-energia); }
[data-tema="inversion-mercado"]           { --tema: var(--t-inversion-mercado); }

.bit-cuerpo { min-width: 0; flex: 1; }

.bit h2 {
  margin: 0 0 .7rem;
  font-family: var(--display);
  font-weight: 400;
  font-size: clamp(1.3rem, 5vw, 1.6rem);
  line-height: 1.24;
  letter-spacing: -.012em;
}

.etiquetas { margin: 0 0 1.1rem; display: flex; flex-wrap: wrap; gap: .35rem; }

.etiqueta {
  display: inline-block;
  padding: .24rem .65rem;
  border: 1px solid var(--borde);
  color: var(--apagado);
  font-size: .62rem;
  letter-spacing: .14em;
  text-transform: uppercase;
  white-space: nowrap;
  /* La esquina cortada: es lo que hace que una caja parezca de un panel y no
     de un formulario. Un pixel de diferencia y toda la diferencia. */
  clip-path: polygon(var(--chaflan) 0, 100% 0, 100% calc(100% - var(--chaflan)), calc(100% - var(--chaflan)) 100%, 0 100%, 0 var(--chaflan));
}

/* La etiqueta del tema lleva su color y ademas lleva a algun sitio: filtra el
   explorador por ese tema. */
.etiqueta-categoria {
  border-color: color-mix(in srgb, var(--tema, var(--acento)) 45%, transparent);
  color: var(--tema, var(--acento));
  text-decoration: none;
}
.etiqueta-categoria:hover {
  background: color-mix(in srgb, var(--tema, var(--acento)) 12%, transparent);
  text-decoration: none;
}

/* El idioma es un aviso, no una categoria: se queda en el borde discontinuo
   para que se lea sin competir con la etiqueta que de verdad clasifica. */
.etiqueta-idioma { border-style: dashed; }

.texto p { margin: 0 0 1rem; }

.por-que {
  margin: 1.4rem 0;
  padding: 1rem 1.2rem;
  background: var(--realce);
  border-left: 2px solid var(--acento);
  font-size: .97em;
}
.por-que strong { font-variant: small-caps; letter-spacing: .04em; }

.menciona {
  margin: 1.1rem 0 0;
  font-size: .76rem;
  color: var(--apagado);
}
.menciona a { color: var(--apagado); }

.pie-bit {
  display: flex;
  flex-wrap: wrap;
  gap: .5rem 1.5rem;
  align-items: baseline;
  margin: 1.2rem 0 0;
  font-size: .76rem;
  letter-spacing: .06em;
  text-transform: uppercase;
}

.fuente { color: var(--acento); text-decoration: none; }
.fuente:hover { text-decoration: underline; }

/* Los datos de la fuente unica van en el mismo pie, en minusculas: ahi no son
   una etiqueta, son una frase corta. */
.pie-bit .datos { font-size: .72rem; text-transform: none; letter-spacing: .04em; }

.volver { margin-left: auto; color: var(--apagado); text-decoration: none; }
.volver:hover { color: var(--tinta); }

/* --- Paginas de texto ----------------------------------------------------- */

.pagina { margin-top: 2rem; }

.pagina h2 {
  margin: 2.5rem 0 .7rem;
  font-family: var(--display);
  font-weight: 400;
  font-size: clamp(1.2rem, 4.6vw, 1.45rem);
  line-height: 1.25;
}

.pagina ul { margin: 0 0 1.2rem; padding-left: 1.2rem; }
.pagina li { margin-bottom: .5rem; }

/* --- Buscador y filtros ---------------------------------------------------- */

.buscador { margin: 2rem 0 1.5rem; }

.buscador label {
  display: block;
  margin-bottom: .45rem;
  font-size: .68rem;
  font-weight: 600;
  letter-spacing: .16em;
  text-transform: uppercase;
  color: var(--apagado);
}

.buscador input {
  width: 100%;
  padding: .85rem 1rem;
  border: 1px solid var(--borde);
  border-radius: 2px;
  background: var(--papel);
  color: var(--tinta);
  /* 16px o mas: por debajo, Safari en iPhone hace zoom al enfocar. */
  font-family: var(--cuerpo);
  font-size: 1.05rem;
  line-height: 1.3;
}
.buscador input:focus { outline: 1px solid var(--acento); outline-offset: 2px; }

.facetas { margin: 0 0 1.5rem; }
.faceta { margin-bottom: 1.15rem; }
.facetas h3 { margin: 0 0 .5rem; color: var(--apagado); }

/* Las opciones se desbordan a lo ancho en el movil en vez de apilarse en
   cinco lineas: se arrastra el dedo, que es el gesto natural. */
.opciones {
  display: flex;
  flex-wrap: nowrap;
  gap: .4rem;
  overflow-x: auto;
  padding-bottom: .35rem;
  scrollbar-width: thin;
  -webkit-overflow-scrolling: touch;
}

.opcion {
  flex: 0 0 auto;
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .45rem .8rem;
  border: 1px solid var(--borde);
  background: transparent;
  clip-path: polygon(var(--chaflan) 0, 100% 0, 100% calc(100% - var(--chaflan)), calc(100% - var(--chaflan)) 100%, 0 100%, 0 var(--chaflan));
  color: var(--apagado);
  font-size: .74rem;
  letter-spacing: .04em;
  cursor: pointer;
  white-space: nowrap;
  min-height: 2.3rem;
}

/* El icono lleva el color de su tema y el nombre se queda en tinta: si se
   colorearan los dos, la lista de filtros seria un arcoiris ilegible. */
.opcion .icono { color: var(--tema, var(--apagado)); }
.opcion-nombre { color: inherit; }

.opcion:hover {
  color: var(--tinta);
  border-color: var(--enlace-suave);
  box-shadow: 0 0 10px rgba(92, 225, 230, .18);
}

.opcion[aria-pressed="true"] {
  border-color: var(--acento);
  color: var(--fondo);
  background: var(--acento);
  box-shadow: 0 0 10px rgba(255, 180, 84, .35);
}

/* Pulsada, el fondo es laton: el icono tiene que dejar su color o desaparece. */
.opcion[aria-pressed="true"] .icono { color: var(--fondo); }

.cuenta-opcion { opacity: .6; margin-left: .1rem; font-variant-numeric: tabular-nums; }

.limpiar {
  margin: .25rem 0 0;
  padding: .3rem 0;
  border: 0;
  background: none;
  color: var(--acento);
  font-size: .74rem;
  letter-spacing: .04em;
  text-decoration: underline;
  cursor: pointer;
}

/* --- Listas de resultados, archivo y fichas -------------------------------- */

.fichas, .archivo { list-style: none; margin: 1.5rem 0 0; padding: 0; }

.fichas li, .archivo li { padding: 1.4rem 0; border-top: 1px solid var(--borde); }

.fichas h2, .archivo-titulo {
  display: block;
  margin: 0 0 .3rem;
  font-family: var(--display);
  font-weight: 400;
  font-size: clamp(1.15rem, 4.4vw, 1.35rem);
  line-height: 1.28;
  text-decoration: none;
}
.fichas h2 a { text-decoration: none; }
.fichas h2 a:hover, .archivo-titulo:hover { text-decoration: underline; }

.resumen { margin: .5rem 0 0; color: var(--apagado); font-size: .93em; }

.ano h2 { margin: 3rem 0 .25rem; color: var(--apagado); }


.cuenta {
  flex: 0 0 auto;
  min-width: 1.8rem;
  text-align: right;
  color: var(--acento-suave);
  font-size: .78rem;
  font-variant-numeric: tabular-nums;
}

.vacio {
  margin: 2rem 0;
  padding: 1.75rem;
  border: 1px solid var(--borde);
  color: var(--apagado);
}

/* El icono del tema delante del titular de cada resultado, alineado con la
   primera linea y no centrado: un icono centrado en un titular de tres lineas
   se queda flotando en mitad de la nada. */
#resultados h2 .icono {
  display: inline-block;
  vertical-align: baseline;
  margin-right: .4rem;
  color: var(--tema, var(--apagado));
}

/* Los temas de cada edicion en el archivo: iconos en fila, sin texto. El
   nombre va en el title, que es lo que hace falta y no estorba. */
.archivo-temas {
  display: flex;
  gap: .4rem;
  margin: .5rem 0 0;
}

.archivo-tema {
  display: grid;
  place-items: center;
  width: 1.6rem;
  height: 1.6rem;
  border: 1px solid color-mix(in srgb, var(--tema, var(--apagado)) 30%, transparent);
  border-radius: 50%;
  color: var(--tema, var(--apagado));
}

/* --- Temas y medios, al final de la edicion -------------------------------- */

.explorar {
  margin: 3rem calc(var(--gutter) * -1) 0;
  padding: 2rem var(--gutter) 2.2rem;
  background: var(--papel);
  border-top: 1px solid var(--borde);
  border-bottom: 1px solid var(--borde);
}

.explorar h2 {
  margin: 0 0 1.6rem;
  font-family: var(--display);
  font-weight: 400;
  font-size: clamp(1.35rem, 5.5vw, 1.7rem);
  line-height: 1.2;
}

.explorar-grupo {
  margin: 1.8rem 0 .9rem;
  font-family: var(--ui);
  font-size: .68rem;
  font-weight: 600;
  letter-spacing: .16em;
  text-transform: uppercase;
  color: var(--apagado);
}

.explorar-grupo:first-of-type { margin-top: 0; }

.nube {
  display: flex;
  flex-wrap: wrap;
  gap: .5rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.nube a {
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  padding: .45rem .75rem;
  border: 1px solid var(--borde);
  background: var(--realce);
  color: var(--tinta);
  font-size: .74rem;
  letter-spacing: .04em;
  text-decoration: none;
  clip-path: polygon(var(--chaflan) 0, 100% 0, 100% calc(100% - var(--chaflan)), calc(100% - var(--chaflan)) 100%, 0 100%, 0 var(--chaflan));
}

.nube a:hover {
  border-color: color-mix(in srgb, var(--tema, var(--enlace)) 55%, transparent);
  text-decoration: none;
}

.nube-temas a { color: var(--tema, var(--tinta)); }
.nube-temas .nube-nombre { color: var(--tinta); }

/* El numero, en laton y mas pequeno: es un dato, no el nombre. */
.nube-cuenta {
  color: var(--acento);
  font-variant-numeric: tabular-nums;
  font-size: .72rem;
}

.explorar-pie { margin: 1.1rem 0 0; max-width: 34rem; }

/* --- Fichas de tema y de medio --------------------------------------------- */

.ficha-titulo {
  display: flex;
  align-items: center;
  gap: .8rem;
}

/* El icono de la ficha, en grande y con el color de su tema. En la de medio no
   hay tema, asi que se queda en el cian de la casa. */
.ficha-icono {
  display: grid;
  place-items: center;
  flex: none;
  width: 3rem;
  height: 3rem;
  border: 1px solid color-mix(in srgb, var(--tema, var(--enlace)) 45%, transparent);
  background: color-mix(in srgb, var(--tema, var(--enlace)) 10%, transparent);
  color: var(--tema, var(--enlace));
  clip-path: polygon(var(--chaflan) 0, 100% 0, 100% calc(100% - var(--chaflan)), calc(100% - var(--chaflan)) 100%, 0 100%, 0 var(--chaflan));
}

.ficha-icono .icono { width: 1.5rem; height: 1.5rem; }

.lista-fichas { list-style: none; margin: 2.5rem 0 0; padding: 0; }

.lista-fichas li {
  padding: 1.4rem 0;
  border-top: 1px solid var(--borde-suave);
}

.lista-fichas h2 {
  margin: 0 0 .4rem;
  font-family: var(--display);
  font-weight: 400;
  font-size: clamp(1.15rem, 4.4vw, 1.4rem);
  line-height: 1.28;
}

.lista-fichas h2 a { text-decoration: none; }
.lista-fichas h2 a:hover { color: var(--enlace); }
.lista-fichas .resumen { margin: .5rem 0 0; color: var(--apagado); font-size: .95rem; }

/* La rejilla de los indices: tarjetas iguales, que es lo que hace que un
   indice se lea como un panel y no como una lista de la compra. */
.rejilla-fichas {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(13rem, 1fr));
  gap: .8rem;
  list-style: none;
  margin: 2.5rem 0 0;
  padding: 0;
}

.rejilla-fichas a {
  display: flex;
  flex-direction: column;
  gap: .7rem;
  height: 100%;
  padding: 1.1rem;
  border: 1px solid var(--borde);
  background: var(--papel);
  text-decoration: none;
  clip-path: polygon(var(--chaflan) 0, 100% 0, 100% calc(100% - var(--chaflan)), calc(100% - var(--chaflan)) 100%, 0 100%, 0 var(--chaflan));
}

.rejilla-fichas a:hover {
  border-color: color-mix(in srgb, var(--tema, var(--enlace)) 60%, transparent);
  background: var(--realce);
  text-decoration: none;
}

.rejilla-nombre {
  font-family: var(--display);
  font-size: 1.12rem;
  line-height: 1.25;
  color: var(--tinta);
}

.rejilla-cuenta {
  margin-top: auto;
  font-family: var(--mono);
  font-size: .68rem;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: var(--apagado);
}

/* El enlace a la ficha del medio, al lado del enlace a la noticia. Discreto:
   lo que quiere el lector es la noticia, no nuestra ficha. */
.ficha-medio {
  color: var(--apagado);
  text-decoration: none;
  border-bottom: 1px dotted var(--borde);
}
.ficha-medio:hover { color: var(--enlace); }

/* --- Alta en el boletin ---------------------------------------------------- */

.alta {
  margin: 3.5rem calc(var(--gutter) * -1) 0;
  padding: 2rem var(--gutter);
  background: var(--papel);
  border-top: 1px solid var(--borde);
  border-bottom: 1px solid var(--borde);
}

.alta h2 {
  margin: 0 0 .7rem;
  font-family: var(--display);
  font-weight: 400;
  font-size: clamp(1.3rem, 5vw, 1.6rem);
  line-height: 1.25;
}

.alta p { margin: 0 0 1.1rem; }

.alta-formulario label {
  display: block;
  margin-bottom: .45rem;
  font-size: .68rem;
  font-weight: 600;
  letter-spacing: .16em;
  text-transform: uppercase;
  color: var(--apagado);
}

.alta-fila { display: flex; flex-wrap: wrap; gap: .6rem; }

.alta-fila input {
  flex: 1 1 14rem;
  min-width: 0;
  padding: .8rem .9rem;
  border: 1px solid var(--borde);
  border-radius: 2px;
  background: var(--fondo);
  color: var(--tinta);
  font-family: var(--cuerpo);
  font-size: 1rem;
  line-height: 1.3;
}
.alta-fila input:focus { outline: 1px solid var(--acento); outline-offset: 2px; }

.alta-fila button {
  flex: 1 1 10rem;
  min-height: 3rem;
  padding: .8rem 1.4rem;
  border: 1px solid var(--acento);
  border-radius: 2px;
  background: var(--acento);
  color: var(--fondo);
  font-family: var(--ui);
  font-size: .78rem;
  font-weight: 600;
  letter-spacing: .12em;
  text-transform: uppercase;
  cursor: pointer;
}
.alta-fila button:hover { background: transparent; color: var(--acento); }

.alta .letra-pequena { margin-top: 1rem; }

/* La trampa para robots: fuera de la vista pero sin display:none, que algunos
   la detectan. */
.trampa {
  position: absolute;
  left: -9999px;
  width: 1px;
  height: 1px;
  overflow: hidden;
}

.alta-respuesta { margin: 1.75rem 0; font-size: 1.05em; }

/* --- Pie ------------------------------------------------------------------- */

.pie {
  max-width: var(--ancho);
  margin: 4rem auto 0;
  padding: 2rem var(--gutter) 5rem;
  border-top: 1px solid var(--borde);
}

.pie .menu { justify-content: flex-start; }

.letra-pequena {
  margin: 1.25rem 0 0;
  max-width: 30rem;
  color: var(--apagado);
  font-size: .78rem;
  line-height: 1.6;
}

/* --- Pantallas grandes ----------------------------------------------------- */

@media (min-width: 34rem) {
  /* La cabecera no cambia de forma con el ancho: el logotipo arriba y el menu
     debajo, los dos alineados al mismo borde derecho que el texto. Se probo a
     ponerlos en la misma linea y la columna de lectura, que son 38rem, no da
     para un logotipo de cinco y seis enlaces: el menu acaba partido en una
     columna de tres letras. Una cabecera que se comporta igual en el movil y
     en el escritorio ademas se reconoce antes. */

  /* Con sitio, las opciones de filtrado se reparten en varias lineas en vez
     de pedir que se arrastre el dedo. */
  .opciones { flex-wrap: wrap; overflow-x: visible; }
}

/* --- Escritorio: se usa el ancho ------------------------------------------
   La columna de lectura no crece -38rem es la medida a la que se lee sin
   cansarse-, pero el espacio que sobra deja de estar vacio: el sumario se va
   a un lateral fijo que acompana toda la edicion, y los filtros del explorador
   hacen lo mismo. En el movil todo eso vuelve a apilarse. */

@media (min-width: 70rem) {
  .cabecera, main, .pie { max-width: 78rem; }

  /* justify-content centra las dos columnas dentro del contenedor: sin eso, el
     espacio que sobra se queda todo a la derecha y la pagina parece torcida. */
  .edicion {
    display: grid;
    grid-template-columns: 18rem minmax(0, 46rem);
    justify-content: center;
    gap: 0 4.5rem;
    align-items: start;
  }

  .edicion-cabecera { grid-column: 1 / -1; }

  .sumario {
    position: sticky;
    top: 2.5rem;
    margin: 3rem 0 0;
    padding: 0 1.5rem 0 0;
    background: transparent;
    border: 0;
    border-right: 1px solid var(--borde);
    max-height: calc(100vh - 5rem);
    overflow-y: auto;
  }

  .bits { min-width: 0; }
  .bit:first-child { padding-top: 3rem; }

  /* El bloque de alta no entra en la rejilla: ocupa el ancho de abajo. */
  .alta { margin-left: 0; margin-right: 0; padding-left: 2rem; padding-right: 2rem; }
  .explorador > .alta { grid-column: 1 / -1; }
  .resultados-panel { min-width: 0; }

  /* Explorador: filtros a la izquierda, resultados a la derecha. */
  .explorador {
    display: grid;
    grid-template-columns: 20rem minmax(0, 46rem);
    justify-content: center;
    gap: 0 4.5rem;
    align-items: start;
  }

  .explorador > .cabecera-explorador { grid-column: 1 / -1; }

  .rail {
    position: sticky;
    top: 2.5rem;
    padding-right: 1.5rem;
    border-right: 1px solid var(--borde);
    max-height: calc(100vh - 5rem);
    overflow-y: auto;
  }

  .opciones { flex-direction: column; align-items: flex-start; }
  .opcion { width: 100%; text-align: left; }

  /* Listas largas a dos columnas: el archivo de un ano son cincuenta filas. */
  .archivo {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0 3rem;
  }
}

@media (prefers-reduced-motion: no-preference) {
  html { scroll-behavior: smooth; }
}

@media print {
  :root {
    --fondo: #fff; --papel: #fff; --realce: #f4f1ea; --tinta: #000;
    --apagado: #444; --borde: #ccc; --acento: #000; --acento-suave: #666;
  }
  .menu, .saltar, .volver, .alta, .buscador, .facetas { display: none; }
  body { font-size: 11pt; }
  .bit { break-inside: avoid; }
}
