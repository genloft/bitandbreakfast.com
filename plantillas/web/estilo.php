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

  /* Azul de medianoche, no negro. El negro puro es una pantalla apagada; este
     azul tiene hora del dia -la del turno de noche en recepcion- y ademas
     hace que el laton parezca laton y no amarillo. */
  --fondo:   #070b16;
  --papel:   #0d1426;
  --realce:  #131c33;
  --borde:   #1e2a47;
  --borde-suave: #172340;
  --tinta:   #e7edfb;
  --apagado: #93a3c6;

  /* Dos acentos y no uno, con trabajos distintos: el laton es la marca -el
     ampersand, los numeros, los filetes- y el azul electrico es lo que se
     puede pulsar. Mezclarlos seria perder los dos. */
  --acento:  #c9a66b;
  --acento-suave: #8a7346;
  --enlace:  #74c4ff;
  --enlace-suave: #2f5d85;

  /* Un color por tematica. No son once colores elegidos por bonitos: son un
     azul de base con desvios cortos, para que juntos parezcan una familia y
     no una caja de rotuladores. Solo pintan el icono y su marco. */
  --t-tecnologia-general:          #9fb2d6;
  --t-pms-crs:                     #74c4ff;
  --t-distribucion-otas:           #6fd3c1;
  --t-revenue-rms:                 #e9b15e;
  --t-pagos-fraude:                #f0a184;
  --t-ciberseguridad-cumplimiento: #ef8496;
  --t-operaciones-iot:             #a8b6dd;
  --t-experiencia-huesped:         #cfa9e8;
  --t-ia-aplicada:                 #9ad681;
  --t-sostenibilidad-energia:      #7fd3a3;
  --t-inversion-mercado:           #e0c87a;

  --display: "Hoefler Text", "Iowan Old Style", "Palatino Linotype", Palatino,
             "Book Antiqua", Georgia, "Times New Roman", serif;
  --cuerpo:  Georgia, "Iowan Old Style", "Times New Roman", serif;
  --ui:      ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI",
             system-ui, sans-serif;

  --ancho:  38rem;
  --gutter: clamp(1.25rem, 5vw, 3rem);
}

*, *::before, *::after { box-sizing: border-box; }

html { -webkit-text-size-adjust: 100%; }

body {
  margin: 0;
  color: var(--tinta);

  /* Dos luces muy tenues, una fria arriba a la derecha -detras del logotipo- y
     otra calida abajo a la izquierda. No se ven; lo que se nota es que el
     fondo deja de ser una plancha de color plano. */
  background:
    radial-gradient(120% 70% at 85% -10%, rgba(116, 196, 255, .10), transparent 60%),
    radial-gradient(90% 60% at 0% 110%, rgba(201, 166, 107, .07), transparent 60%),
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

/* Etiqueta diminuta en versalitas: la voz de la casa para todo lo que no es
   texto corrido. */
.sello, .menu, .datos, .etiqueta, .letra-pequena, .sumario h2, .ano h2,
.cuenta, .menciona, .pie-bit, .buscador label, .alta-formulario label,
.facetas h3, .opcion, .limpiar, .sumario-etiqueta, .numero-lista {
  font-family: var(--ui);
}

.sello, .sumario h2, .ano h2, .facetas h3 {
  font-size: .68rem;
  font-weight: 600;
  letter-spacing: .16em;
  text-transform: uppercase;
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
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: clamp(.5rem, 2.2vw, .9rem);
  text-decoration: none;
  color: var(--tinta);
}

.logo:hover { text-decoration: none; color: var(--tinta); }

/* La marca crece con el nombre, no con la pantalla: si el nombre se encoge en
   un movil, la taza se encoge con el o se separan. */
.marca {
  flex: none;
  width: clamp(2.6rem, 12vw, 4.2rem);
  height: auto;
  color: var(--acento);
  overflow: visible;
}

/* El vapor un poco mas fino que la taza y un punto mas claro: asi se lee como
   vapor y no como tres asas. */
.marca-onda { opacity: .85; stroke-width: 1.4; }
.marca-taza { color: var(--tinta); stroke: var(--tinta); }

/* Al pasar por encima, el vapor sube. Es el unico movimiento del sitio. */
.logo:hover .marca-onda { opacity: 1; }
.logo .marca-onda { transition: opacity .25s ease, transform .25s ease; }
.logo:hover .marca-onda:nth-of-type(1) { transform: translateY(-.5px); }
.logo:hover .marca-onda:nth-of-type(2) { transform: translateY(-1px); }
.logo:hover .marca-onda:nth-of-type(3) { transform: translateY(-1.5px); }

@media (prefers-reduced-motion: reduce) {
  .logo .marca-onda { transition: none; }
  .logo:hover .marca-onda { transform: none; }
}

.logo-texto {
  display: block;
  text-align: right;
  font-family: var(--display);
  font-weight: 400;
  font-size: clamp(2.2rem, 11.5vw, 4.3rem);
  line-height: .92;
  letter-spacing: -.024em;
}

/* El ampersand en cursiva y en laton: el unico adorno de toda la marca. */
.logo-amp {
  font-style: italic;
  color: var(--acento);
  padding: 0 .04em;
}

.promesa {
  margin: 1.1rem 0 0 auto;
  max-width: 26rem;
  text-align: right;
  color: var(--apagado);
  font-family: var(--ui);
  font-size: .78rem;
  letter-spacing: .04em;
  line-height: 1.5;
}

.promesa-punto { color: var(--acento-suave); padding: 0 .15rem; }

.menu {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: .3rem 1.15rem;
  font-size: .74rem;
  letter-spacing: .1em;
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

/* Un filete de laton que cierra la cabecera y separa el sitio de su contenido. */
.cabecera::after {
  content: "";
  display: block;
  height: 1px;
  margin-top: 1.6rem;
  background: linear-gradient(90deg, transparent, var(--acento-suave) 35%, var(--enlace-suave));
}

/* --- Estructura ---------------------------------------------------------- */

main {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: 0 var(--gutter);
}

.edicion-cabecera {
  padding: 2rem 0 0;
  border-top: 1px solid var(--acento-suave);
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
  font-family: var(--display);
  font-size: 1.45rem;
  line-height: 1;
  color: var(--acento-suave);
  font-variant-numeric: tabular-nums;
}

/* El icono, con el color de su tema, dentro de un circulo tenue del mismo
   color. El color vive en --tema y lo pone el atributo data-tema. */
.bit-icono {
  display: grid;
  place-items: center;
  width: 2.4rem;
  height: 2.4rem;
  border: 1px solid color-mix(in srgb, var(--tema, var(--apagado)) 35%, transparent);
  border-radius: 50%;
  background: color-mix(in srgb, var(--tema, var(--apagado)) 9%, transparent);
  color: var(--tema, var(--apagado));
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
  padding: .22rem .6rem;
  border: 1px solid var(--borde);
  border-radius: 2px;
  color: var(--apagado);
  font-size: .64rem;
  letter-spacing: .09em;
  text-transform: uppercase;
  white-space: nowrap;
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
  padding: .45rem .8rem;
  border: 1px solid var(--borde);
  border-radius: 2px;
  background: transparent;
  color: var(--apagado);
  font-size: .74rem;
  letter-spacing: .04em;
  cursor: pointer;
  white-space: nowrap;
  min-height: 2.3rem;
}

.opcion:hover { color: var(--tinta); border-color: var(--acento-suave); }

.opcion[aria-pressed="true"] {
  border-color: var(--acento);
  color: var(--fondo);
  background: var(--acento);
}

.cuenta-opcion { opacity: .6; margin-left: .4rem; }

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

.fichas, .archivo, .proveedores { list-style: none; margin: 1.5rem 0 0; padding: 0; }

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

.proveedores li {
  display: flex;
  align-items: baseline;
  gap: .9rem;
  padding: .7rem 0;
  border-top: 1px solid var(--borde);
}

.proveedores a { flex: 1; text-decoration: none; }
.proveedores a:hover { text-decoration: underline; }

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
  padding: .42rem .7rem;
  border: 1px solid var(--borde);
  border-radius: 2px;
  background: var(--realce);
  color: var(--tinta);
  font-family: var(--ui);
  font-size: .78rem;
  letter-spacing: .02em;
  text-decoration: none;
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
  .cabecera, main, .pie { max-width: 66rem; }

  /* justify-content centra las dos columnas dentro del contenedor: sin eso, el
     espacio que sobra se queda todo a la derecha y la pagina parece torcida. */
  .edicion {
    display: grid;
    grid-template-columns: 16rem minmax(0, 40rem);
    justify-content: center;
    gap: 0 3.5rem;
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
    grid-template-columns: 18rem minmax(0, 40rem);
    justify-content: center;
    gap: 0 3.5rem;
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
  .archivo, .proveedores {
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
