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
 *   - Papel, no pantalla. Fondo claro, tinta casi negra y filetes finos. Hubo
 *     una version oscura y otra retrofuturista; las dos hacian que el sitio
 *     pareciera una aplicacion. Esto es una publicacion, y una publicacion se
 *     lee sobre papel.
 *   - La tipografia ES el diseno. Una didona de mucho contraste para los
 *     titulares -Didot en Mac, Bodoni en Windows, Georgia de red de seguridad-
 *     y un sans pequeno en versalitas para todo lo que es dato. No hace falta
 *     nada mas, y por eso no hay nada mas.
 *   - Jerarquia por tamano, no por color. El color -un solo rojo de tinta de
 *     imprenta- aparece dos o tres veces por pagina. Un acento que sale en
 *     todas partes deja de ser un acento.
 *   - Rejilla de revista: una pieza grande arriba y el resto en columnas. Una
 *     lista vertical de veinte titulares es un listado; una portada no.
 *   - Sin indice lateral. Repetia los titulares que estaban dos dedos mas
 *     abajo y se apelotonaba en una columna estrecha. Un indice sirve cuando
 *     hay mucho que recorrer; aqui la portada se ve de una pasada.
 *   - Movil primero. Las reglas base son las del telefono y las medias
 *     consultas solo anaden.
 *   - Cero dependencias: ni fuentes de Google, ni iconos, ni reset de nadie.
 *     La politica de seguridad del sitio no permite cargar nada de fuera.
 *
 * Sobre las familias: no hay fuente incrustada a proposito. Las pilas eligen
 * la mejor de cada sistema y quedan bien en los tres sin pedirle al lector que
 * descargue nada. El dia que haya licencia de una fuente propia, se sirve
 * desde publico/ y solo cambia la variable --display.
 */

declare(strict_types=1);

?>
:root {
  color-scheme: light;

  /* Papel: un blanco roto, no el del folio recien salido de la impresora, que
     en pantalla deslumbra. */
  --papel:   #f7f6f4;
  --tarjeta: #ffffff;
  --tinta:   #12100e;
  --apagado: #6e6862;
  --suave:   #97908a;
  --filete:  #e3ded7;
  --filete-fuerte: #cdc6bd;
  --negro:   #12100e;

  /* Un acento y nada mas: el rojo de la tinta de imprenta. */
  --acento:  #c4362a;
  --realce:  #f0ece6;

  /* Didot y Bodoni son las didonas que ya estan en Mac y en Windows: mucho
     contraste entre gruesos y finos, que es lo que hace que un titular
     parezca de revista y no de blog. Georgia cierra la pila porque esta en
     todas partes y no desentona. */
  --display: Didot, "Didot LT STD", "Bodoni MT", "Hoefler Text", Garamond,
             "Times New Roman", Georgia, serif;
  --cuerpo:  Georgia, Charter, "Iowan Old Style", "Times New Roman", serif;
  --ui:      ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI",
             Inter, system-ui, sans-serif;

  --ancho:   72rem;   /* el papel entero */
  --lectura: 40rem;   /* la columna de texto seguido */
  --gutter:  clamp(1.1rem, 4vw, 2.5rem);
}

*, *::before, *::after { box-sizing: border-box; }

html { -webkit-text-size-adjust: 100%; }

body {
  margin: 0;
  background: var(--papel);
  color: var(--tinta);
  font-family: var(--cuerpo);
  font-size: clamp(1rem, .95rem + .25vw, 1.08rem);
  line-height: 1.6;
  overflow-wrap: break-word;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

a { color: inherit; text-decoration-color: var(--filete-fuerte); text-underline-offset: .18em; }
a:hover { text-decoration-color: var(--acento); }
a:focus-visible { outline: 2px solid var(--acento); outline-offset: 3px; }

::selection { background: var(--tinta); color: var(--papel); }

img { max-width: 100%; height: auto; }

/* Dato: todo lo que no es texto seguido. Sans, pequeno, en versalitas y con
   aire entre letras. Es la voz secundaria de la casa y no compite con nada. */
.sello, .datos, .menu, .etiqueta, .letra-pequena, .cuenta, .menciona,
.pie-bit, .buscador label, .alta-formulario label, .facetas h3, .opcion,
.limpiar, .sumario-etiqueta, .numero, .explorar-grupo, .nube a, .ano h2,
.rejilla-cuenta, .cuenta-opcion, .aviso-texto, .promesa, .fuentes-bit summary {
  font-family: var(--ui);
}

.sello, .facetas h3, .explorar-grupo, .ano h2 {
  margin: 0 0 1rem;
  font-size: .68rem;
  font-weight: 600;
  letter-spacing: .14em;
  text-transform: uppercase;
  color: var(--apagado);
}

.saltar {
  position: absolute;
  left: -9999px;
  padding: .7rem 1.1rem;
  background: var(--tarjeta);
  border: 1px solid var(--tinta);
}
.saltar:focus { left: 1rem; top: 1rem; z-index: 10; }

/* --- La tira de arriba -----------------------------------------------------
   Una banda negra con lo que ha cambiado. Es lo primero que se ve y contesta
   la unica pregunta de quien vuelve: si hay algo nuevo desde la ultima vez. */

.aviso-barra { background: var(--negro); color: #f2efe9; }

.aviso-texto {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: .25rem .8rem;
  max-width: var(--ancho);
  margin: 0 auto;
  padding: .5rem var(--gutter);
  font-size: .7rem;
  letter-spacing: .04em;
}

.aviso-punto {
  width: .4rem;
  height: .4rem;
  flex: none;
  border-radius: 50%;
  background: var(--acento);
}

.aviso-cuando { color: #f2efe9; }
.aviso-dato { color: #b5ada4; }
.aviso-dato::before { content: "·"; margin-right: .8rem; color: #5c554e; }
.aviso-nuevos strong, .aviso-archivo strong { color: #fff; }
.aviso-archivo a { color: #b5ada4; }
.aviso-quieto { color: #8f8881; }

/* --- Cabecera --------------------------------------------------------------
   El nombre centrado y grande, y debajo la navegacion. Como la cabecera de un
   periodico: lo primero que se lee es como se llama esto. */

.cabecera {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: clamp(1.6rem, 5vw, 2.8rem) var(--gutter) 0;
  text-align: center;
}

.cabecera-interior { display: block; }

.logo { display: inline-block; text-decoration: none; }
.logo:hover { text-decoration: none; }

.logo-bloque {
  display: block;
  font-family: var(--display);
  font-weight: 400;
  font-size: clamp(1.8rem, 7.2vw, 3.3rem);
  line-height: 1;
  /* El interletrado ancho es lo que convierte un nombre en una cabecera. */
  letter-spacing: .12em;
  text-transform: uppercase;
  /* El interletrado deja aire de sobra a la derecha de la ultima letra: se
     compensa para que el conjunto quede centrado de verdad. */
  text-indent: .12em;
}

.logo-amp { font-style: italic; letter-spacing: 0; padding: 0 .16em; }

.promesa {
  margin: .9rem auto 0;
  max-width: 34rem;
  font-size: .76rem;
  letter-spacing: .02em;
  color: var(--apagado);
}

.promesa-punto { color: var(--filete-fuerte); padding: 0 .2rem; }

.menu {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: .2rem 1.4rem;
  margin-top: 1.4rem;
  padding-top: .9rem;
  border-top: 1px solid var(--filete);
  font-size: .78rem;
  letter-spacing: .02em;
}

.menu a { display: inline-block; padding: .35rem 0; color: var(--apagado); text-decoration: none; }
.menu a:hover { color: var(--tinta); }
.menu a[aria-current="page"] { color: var(--tinta); font-weight: 600; }

/* --- Estructura ------------------------------------------------------------ */

main {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: 0 var(--gutter) 3rem;
}

.edicion-cabecera {
  padding: clamp(1.8rem, 5vw, 3rem) 0 1.4rem;
  border-bottom: 1px solid var(--filete);
}

h1 {
  margin: 0 0 .6rem;
  font-family: var(--display);
  font-weight: 400;
  font-size: clamp(2rem, 7vw, 3.4rem);
  line-height: 1.04;
  letter-spacing: -.01em;
}

.datos { margin: 0; color: var(--apagado); font-size: .76rem; letter-spacing: .02em; }
.punto { padding: 0 .4rem; color: var(--filete-fuerte); }

.intro {
  margin: 1.2rem 0 0;
  max-width: var(--lectura);
  font-size: 1.1em;
  color: var(--apagado);
}
.intro p { margin: 0 0 .8rem; }

/* El numero de la edicion ya no se pinta en grande: la fecha es el titulo y el
   numero es un dato. */
.edicion-numero { display: none; }

/* El indice lateral se quito, por lo dicho arriba del fichero. */
.sumario { display: none; }

/* --- La portada: una pieza grande y una rejilla ---------------------------- */

.bits { display: grid; gap: 0; margin-top: 2rem; }

/* La rejilla arranca con un filete grueso, como el que separa la cabecera de
   la portada en un periodico. */
.bits::before { content: ""; grid-column: 1 / -1; height: 2px; background: var(--tinta); }

.bit { display: block; padding: 1.6rem 0; border-top: 1px solid var(--filete); }
.bit:first-child { border-top: 0; padding-top: .6rem; }

/* El numero y el icono del tema, en linea y pequenos: son una linea de dato
   mas, no una columna. */
.bit-carril { display: flex; align-items: center; gap: .5rem; margin-bottom: .5rem; }

.numero {
  margin: 0;
  font-size: .7rem;
  font-weight: 600;
  letter-spacing: .12em;
  color: var(--suave);
  font-variant-numeric: tabular-nums;
}

.bit-icono { display: grid; place-items: center; width: 1.4rem; height: 1.4rem; color: var(--tinta); }

.icono { width: 1rem; height: 1rem; display: block; }
.icono-mini { width: .8rem; height: .8rem; }

.bit-cuerpo { min-width: 0; }

.bit h2 {
  margin: 0 0 .5rem;
  font-family: var(--display);
  font-weight: 400;
  font-size: clamp(1.35rem, 4.4vw, 1.75rem);
  line-height: 1.12;
  letter-spacing: -.005em;
}

.texto p { margin: 0 0 .7rem; color: var(--apagado); }

.por-que {
  margin: .9rem 0;
  padding-left: .9rem;
  border-left: 2px solid var(--acento);
  font-size: .98em;
}
.por-que strong { font-weight: 600; }

/* La primera pieza manda: titular al doble y cuerpo a la anchura de lectura.
   Es lo que convierte una lista en una portada. */
.bit-lead { padding-bottom: 2rem; }
.bit-lead h2 { font-size: clamp(2rem, 6.5vw, 3rem); line-height: 1.02; }
.bit-lead .texto { max-width: var(--lectura); font-size: 1.05em; }

.etiquetas { display: flex; flex-wrap: wrap; align-items: baseline; gap: .4rem; margin: 0 0 .6rem; }

.etiqueta {
  font-size: .64rem;
  font-weight: 600;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: var(--suave);
}

/* Solo la del tema se ve de verdad; las otras dos son contexto. */
.etiqueta-categoria { color: var(--tinta); text-decoration: none; }
.etiqueta-categoria:hover { color: var(--acento); }
.etiqueta-idioma { color: var(--acento); }

.menciona { margin: .6rem 0 0; font-size: .74rem; color: var(--apagado); }
.menciona a { color: var(--apagado); }

.pie-bit {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: .3rem .9rem;
  margin: .9rem 0 0;
  font-size: .72rem;
  letter-spacing: .02em;
}

.fuente { color: var(--tinta); font-weight: 600; text-decoration: none; }
.fuente:hover { color: var(--acento); }
.ficha-medio { color: var(--suave); text-decoration: none; }
.ficha-medio:hover { color: var(--tinta); }
.pie-bit .datos { font-size: .72rem; color: var(--suave); }

/* El "volver al sumario" sobra sin sumario. */
.volver { display: none; }

.fuentes-bit { margin: .9rem 0 0; font-size: .82rem; }
.fuentes-bit summary { cursor: pointer; color: var(--apagado); font-size: .74rem; }
.fuentes-bit ul { margin: .8rem 0 0; padding: 0; list-style: none; }
.fuentes-bit li { padding: .6rem 0; border-top: 1px solid var(--filete); }
.titular-fuente { display: block; color: var(--apagado); }

.vacio { margin: 2rem 0; color: var(--apagado); }

/* --- Temas y medios, al final de la edicion -------------------------------- */

.explorar { margin: 2.5rem 0 0; padding: 2rem 0 0; border-top: 2px solid var(--tinta); }

.explorar h2 {
  margin: 0 0 1.4rem;
  font-family: var(--display);
  font-weight: 400;
  font-size: clamp(1.5rem, 5vw, 2rem);
  line-height: 1.1;
}

.explorar-grupo { margin: 1.6rem 0 .8rem; }
.explorar-grupo:first-of-type { margin-top: 0; }

.nube { display: flex; flex-wrap: wrap; gap: .45rem; margin: 0; padding: 0; list-style: none; }

.nube a {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .4rem .75rem;
  border: 1px solid var(--filete-fuerte);
  border-radius: 999px;
  background: var(--tarjeta);
  color: var(--tinta);
  font-size: .74rem;
  text-decoration: none;
}

.nube a:hover { border-color: var(--tinta); }
.nube-cuenta { color: var(--suave); font-variant-numeric: tabular-nums; }
.explorar-pie { margin: 1rem 0 0; max-width: var(--lectura); }

/* --- Fichas de tema y de medio --------------------------------------------- */

.ficha-titulo { display: flex; align-items: center; gap: .7rem; }

.ficha-icono {
  display: grid;
  place-items: center;
  flex: none;
  width: 2.4rem;
  height: 2.4rem;
  border: 1px solid var(--filete-fuerte);
  border-radius: 50%;
  color: var(--tinta);
}

.ficha-icono .icono { width: 1.2rem; height: 1.2rem; }

.lista-fichas { list-style: none; margin: 2rem 0 0; padding: 0; }
.lista-fichas li { padding: 1.2rem 0; border-top: 1px solid var(--filete); }

.lista-fichas h2 {
  margin: 0 0 .35rem;
  font-family: var(--display);
  font-weight: 400;
  font-size: clamp(1.15rem, 4vw, 1.45rem);
  line-height: 1.16;
}
.lista-fichas h2 a { text-decoration: none; }
.lista-fichas h2 a:hover { color: var(--acento); }
.lista-fichas .resumen { margin: .4rem 0 0; color: var(--apagado); font-size: .95rem; }

.rejilla-fichas {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(12rem, 1fr));
  gap: 1px;
  list-style: none;
  margin: 2rem 0 0;
  padding: 0;
  background: var(--filete);
  border: 1px solid var(--filete);
}

.rejilla-fichas a {
  display: flex;
  flex-direction: column;
  gap: .6rem;
  height: 100%;
  padding: 1.1rem;
  background: var(--tarjeta);
  text-decoration: none;
}

.rejilla-fichas a:hover { background: var(--realce); }
.rejilla-nombre { font-family: var(--display); font-size: 1.15rem; line-height: 1.2; }
.rejilla-cuenta { margin-top: auto; font-size: .68rem; letter-spacing: .1em; text-transform: uppercase; color: var(--suave); }

/* --- Archivo ---------------------------------------------------------------- */

.ano { margin-top: 2.5rem; }
.fichas, .archivo { list-style: none; margin: 1.2rem 0 0; padding: 0; }
.archivo li, .fichas li { padding: 1.1rem 0; border-top: 1px solid var(--filete); }

.archivo-titulo {
  font-family: var(--display);
  font-size: clamp(1.2rem, 4vw, 1.5rem);
  text-decoration: none;
}
.archivo-titulo:hover { color: var(--acento); }

.archivo-temas { display: flex; gap: .5rem; margin: .5rem 0 0; }
.archivo-tema { display: grid; place-items: center; width: 1.4rem; height: 1.4rem; color: var(--suave); }

.fichas h2 { margin: 0 0 .3rem; font-family: var(--display); font-weight: 400; font-size: 1.25rem; line-height: 1.18; }

/* --- Buscador --------------------------------------------------------------- */

.buscador { margin: 2rem 0 0; }
.buscador label { display: block; margin-bottom: .5rem; font-size: .68rem; letter-spacing: .14em; text-transform: uppercase; color: var(--apagado); }

.buscador input {
  width: 100%;
  padding: .8rem 1rem;
  border: 1px solid var(--filete-fuerte);
  background: var(--tarjeta);
  color: var(--tinta);
  font-family: var(--display);
  font-size: 1.15rem;
}
.buscador input:focus { outline: 2px solid var(--acento); outline-offset: -2px; }

.facetas { margin: 1.6rem 0 0; }
.faceta { margin-bottom: 1.2rem; }
.opciones { display: flex; flex-wrap: wrap; gap: .45rem; }

.opcion {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  padding: .4rem .75rem;
  border: 1px solid var(--filete-fuerte);
  border-radius: 999px;
  background: var(--tarjeta);
  color: var(--apagado);
  font-size: .74rem;
  cursor: pointer;
}

.opcion:hover { color: var(--tinta); border-color: var(--tinta); }
.opcion[aria-pressed="true"] { background: var(--tinta); border-color: var(--tinta); color: var(--papel); }
.opcion[aria-pressed="true"] .icono { color: var(--papel); }
.opcion .icono { color: var(--suave); }
.cuenta-opcion { color: var(--suave); font-variant-numeric: tabular-nums; }
.opcion[aria-pressed="true"] .cuenta-opcion { color: #b5ada4; }

.limpiar {
  margin-top: .4rem;
  padding: .35rem 0;
  border: 0;
  background: none;
  color: var(--acento);
  font-size: .74rem;
  cursor: pointer;
}

#resultados { list-style: none; margin: 1.5rem 0 0; padding: 0; }
#resultados li { padding: 1.1rem 0; border-top: 1px solid var(--filete); }
#resultados h2 { margin: 0 0 .3rem; font-family: var(--display); font-weight: 400; font-size: 1.25rem; line-height: 1.18; }
#resultados h2 a { text-decoration: none; }
#resultados h2 a:hover { color: var(--acento); }
#resultados h2 .icono { display: inline-block; vertical-align: baseline; margin-right: .35rem; color: var(--suave); }
#resultados .resumen { margin: .35rem 0 0; color: var(--apagado); font-size: .95rem; }

/* --- Paginas de texto -------------------------------------------------------- */

.pagina { margin-top: 2rem; max-width: var(--lectura); }
.pagina h2 { margin: 2.2rem 0 .6rem; font-family: var(--display); font-weight: 400; font-size: 1.5rem; line-height: 1.2; }
.pagina ul { margin: 0 0 1rem; padding-left: 1.1rem; }
.pagina li { margin-bottom: .4rem; }
.letra-pequena { font-size: .76rem; color: var(--apagado); }
.alta-respuesta { max-width: var(--lectura); }

/* --- Alta en el boletin ------------------------------------------------------ */

.alta { margin: 3rem 0 0; padding: 2rem; background: var(--negro); color: #f2efe9; }
.alta h2 { margin: 0 0 .7rem; font-family: var(--display); font-weight: 400; font-size: clamp(1.5rem, 5vw, 2rem); }
.alta p { margin: 0 0 1rem; color: #b5ada4; max-width: var(--lectura); }
.alta a { color: #f2efe9; }
.alta-formulario label { display: block; margin-bottom: .5rem; font-size: .68rem; letter-spacing: .14em; text-transform: uppercase; color: #b5ada4; }
.alta-fila { display: flex; flex-wrap: wrap; gap: .6rem; max-width: 30rem; }

.alta-fila input {
  flex: 1 1 14rem;
  padding: .75rem .9rem;
  border: 1px solid #3a342e;
  background: #1c1916;
  color: #f2efe9;
  font-family: var(--cuerpo);
  font-size: 1rem;
}

.alta-fila button {
  padding: .75rem 1.4rem;
  border: 0;
  background: var(--acento);
  color: #fff;
  font-family: var(--ui);
  font-size: .8rem;
  font-weight: 600;
  letter-spacing: .08em;
  text-transform: uppercase;
  cursor: pointer;
}
.alta-fila button:hover { background: #a92c21; }
.alta .letra-pequena { color: #8f8881; }

/* La trampa para robots: invisible para las personas, presente para el que
   rellena formularios a ciegas. */
.trampa { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }

/* --- Pie ---------------------------------------------------------------------- */

.pie {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: 2rem var(--gutter) 3rem;
  border-top: 1px solid var(--filete);
  color: var(--apagado);
  font-family: var(--ui);
  font-size: .74rem;
}

.pie a { color: var(--apagado); margin-right: 1.1rem; }
.pie p { margin: 0 0 .5rem; }

/* --- Tableta y escritorio ------------------------------------------------------
   La rejilla de revista: la primera pieza grande y el resto en dos o tres
   columnas. Sin indice lateral, el ancho entero es para lo que se lee. */

@media (min-width: 48rem) {
  .bits { grid-template-columns: repeat(2, minmax(0, 1fr)); column-gap: 2.5rem; }
  .bit-lead { grid-column: 1 / -1; border-top: 0; }
  .alta { padding: 2.5rem; }
}

@media (min-width: 72rem) {
  .bits { grid-template-columns: repeat(3, minmax(0, 1fr)); column-gap: 3rem; }

  /* Una grande y dos de apoyo en la misma fila, que es como se maqueta una
     portada. */
  .bit-lead { grid-column: 1 / 3; }

  .rejilla-fichas { grid-template-columns: repeat(auto-fill, minmax(14rem, 1fr)); }
}
