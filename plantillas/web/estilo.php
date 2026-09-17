<?php
/**
 * La hoja de estilo del sitio publicado.
 *
 * Es una plantilla y no un .css suelto porque el generador la escribe como
 * escribe todo lo demas: asi hay un unico sitio del que sale publico/, y
 * borrar esa carpeta entera nunca pierde nada que no se pueda regenerar.
 *
 * El estilo es el de un agregador de noticias impreso: papel, tinta negra y
 * retícula de filetes. Las decisiones de fondo, para no deshacerlas sin
 * querer:
 *
 *   - Todo vive dentro de cajas con filete de un pixel, y las cajas comparten
 *     borde. Es lo que da el aire de periodico maquetado y lo que permite
 *     ordenar veinte noticias sin una sola imagen.
 *   - Este sitio no tiene fotos y no las va a tener: agrega titulares ajenos y
 *     las fotos son de sus medios. Asi que el peso visual lo llevan la
 *     tipografia, los filetes y el negro macizo. Un agregador con huecos grises
 *     donde deberian ir fotos parece roto; sin ellas, parece deliberado.
 *   - Condensada, en mayusculas y muy apretada para los rotulos; sans normal y
 *     en negrita para los titulares; serif solo para el texto seguido. Tres
 *     voces y ni una mas.
 *   - Un acento, rojo, y casi nunca: la tira de ultima hora, lo que esta
 *     activo y poco mas.
 *   - Movil primero. Las reglas base son las del telefono y las medias
 *     consultas solo anaden.
 *   - Cero dependencias: ni fuentes de Google, ni iconos, ni reset de nadie.
 *     La politica de seguridad del sitio no permite cargar nada de fuera.
 *
 * Sobre las familias: no hay fuente incrustada a proposito. Las pilas eligen
 * la mejor de cada sistema -Arial Narrow y Helvetica Neue estan en Mac y en
 * Windows- y quedan bien en los tres sin pedirle al lector que descargue nada.
 */

declare(strict_types=1);

?>
:root {
  color-scheme: light;

  /* Papel de periodico: un blanco que tira a hueso. El blanco puro en pantalla
     deslumbra y ademas delata que no hay nada impreso detras. */
  --papel:   #f4f2ee;
  --tarjeta: #faf9f7;
  --tinta:   #0d0d0d;
  --apagado: #5f5c57;
  --suave:   #8d8982;
  --filete:  #0d0d0d;
  --filete-fino: #cfcac2;
  --negro:   #0d0d0d;
  --realce:  #e9e5df;

  /* El acento, rojo de tinta, para la ultima hora y lo que esta activo. */
  --acento:  #d02b1f;

  /* Condensada para rotulos y titulares: es la letra de los periodicos
     precisamente porque cabe mas en menos sitio. Arial Narrow esta en Windows
     y en Mac; Helvetica Neue cierra en Mac; el resto es red de seguridad. */
  --titular: "Arial Narrow", "Helvetica Neue", Helvetica, Arial, ui-sans-serif, sans-serif;
  --ui:      ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI",
             Inter, Helvetica, Arial, sans-serif;
  --cuerpo:  Georgia, Charter, "Iowan Old Style", "Times New Roman", serif;

  --ancho:   80rem;
  --lectura: 38rem;
  --gutter:  clamp(.9rem, 3vw, 1.6rem);
}

*, *::before, *::after { box-sizing: border-box; }

html { -webkit-text-size-adjust: 100%; }

body {
  margin: 0;
  background: var(--papel);
  color: var(--tinta);
  font-family: var(--cuerpo);
  font-size: clamp(.98rem, .94rem + .2vw, 1.05rem);
  line-height: 1.55;
  overflow-wrap: break-word;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

a { color: inherit; text-decoration-color: var(--filete-fino); text-underline-offset: .18em; }
a:hover { text-decoration-color: var(--acento); }
a:focus-visible { outline: 2px solid var(--acento); outline-offset: 2px; }

::selection { background: var(--tinta); color: var(--papel); }

img { max-width: 100%; height: auto; }

/* Rotulo: mayusculas condensadas y muy apretadas. Es la voz de la casa para
   todo lo que no es texto seguido, y es lo que hace que esto parezca un
   periodico y no un blog. */
.sello, .facetas h3, .explorar-grupo, .ano h2, .rotulo {
  margin: 0 0 .8rem;
  font-family: var(--titular);
  font-size: .72rem;
  font-weight: 700;
  letter-spacing: .14em;
  text-transform: uppercase;
  color: var(--tinta);
}

.datos, .menu, .etiqueta, .letra-pequena, .cuenta, .menciona, .pie-bit,
.buscador label, .alta-formulario label, .opcion, .limpiar, .numero,
.nube a, .rejilla-cuenta, .cuenta-opcion, .aviso-texto, .promesa,
.fuentes-bit summary, .sumario-etiqueta {
  font-family: var(--ui);
}

.saltar {
  position: absolute;
  left: -9999px;
  padding: .7rem 1.1rem;
  background: var(--tarjeta);
  border: 1px solid var(--tinta);
}
.saltar:focus { left: 1rem; top: 1rem; z-index: 10; }

/* --- La tira de ultima hora -------------------------------------------------
   Una banda con filete arriba y abajo: lo que ha cambiado desde la ultima vez
   y los titulares de la edicion pasando de largo. Es lo primero que se ve. */

.aviso-barra {
  border-bottom: 1px solid var(--filete);
  background: var(--tinta);
  color: var(--papel);
}

.aviso-texto {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: .2rem .7rem;
  max-width: var(--ancho);
  margin: 0 auto;
  padding: .45rem var(--gutter);
  font-size: .68rem;
  letter-spacing: .06em;
}

.aviso-punto {
  width: .45rem;
  height: .45rem;
  flex: none;
  border-radius: 50%;
  background: var(--acento);
}

.aviso-cuando { text-transform: uppercase; letter-spacing: .1em; font-weight: 600; }
.aviso-dato { color: #b9b4ac; }
.aviso-dato::before { content: "·"; margin-right: .7rem; color: #5a5651; }
.aviso-nuevos strong, .aviso-archivo strong { color: #fff; }
.aviso-archivo a { color: #b9b4ac; }
.aviso-quieto { color: #918c85; }

/* --- Cabecera ---------------------------------------------------------------
   Barra de navegacion con el sello a la izquierda, y debajo el nombre a todo
   lo ancho. Las dos cosas dentro de filetes, como el resto de la pagina. */

.cabecera { max-width: var(--ancho); margin: 0 auto; padding: 0 var(--gutter); }

.cabecera-barra {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: .55rem 0;
  border-bottom: 1px solid var(--filete);
}

/* El sello: un circulo negro con la inicial. No es un icono ilustrativo -de
   esos ya hubo uno y sobraba-, es la marca reducida a una letra. */
.sello-marca {
  display: grid;
  place-items: center;
  flex: none;
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  background: var(--tinta);
  color: var(--papel);
  font-family: var(--titular);
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: .02em;
  text-decoration: none;
}

.menu {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: .1rem 1.1rem;
  font-size: .7rem;
  font-weight: 600;
  letter-spacing: .1em;
  text-transform: uppercase;
}

.menu a { display: inline-block; padding: .3rem 0; color: var(--apagado); text-decoration: none; }
.menu a:hover { color: var(--tinta); }
.menu a[aria-current="page"] { color: var(--tinta); border-bottom: 2px solid var(--acento); }

.menu-fin { margin-left: auto; }

.logo { display: block; text-decoration: none; padding: clamp(1rem, 3.5vw, 2rem) 0 .7rem; }
.logo:hover { text-decoration: none; }

/* El nombre ocupa el ancho entero. Es la pieza mas grande de la pagina y no
   compite con nada porque encima solo hay filetes. */
.logo-bloque {
  display: block;
  font-family: var(--titular);
  font-weight: 700;
  font-size: clamp(2.6rem, 12.5vw, 8rem);
  line-height: .86;
  letter-spacing: -.02em;
  text-transform: uppercase;
  white-space: nowrap;
}

.logo-amp { color: var(--acento); }

.cabecera-pie {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: .3rem 1rem;
  padding-bottom: .6rem;
  border-bottom: 3px solid var(--filete);
}

.promesa {
  margin: 0;
  font-size: .7rem;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--apagado);
}

.promesa-punto { color: var(--filete-fino); padding: 0 .2rem; }

/* --- Estructura -------------------------------------------------------------- */

main { max-width: var(--ancho); margin: 0 auto; padding: 0 var(--gutter) 3rem; }

.edicion-cabecera {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: .4rem 1.5rem;
  padding: 1.6rem 0 .9rem;
}

h1 {
  margin: 0;
  font-family: var(--titular);
  font-weight: 700;
  font-size: clamp(2rem, 8vw, 3.6rem);
  line-height: .95;
  letter-spacing: -.01em;
  text-transform: uppercase;
}

.datos { margin: 0; color: var(--apagado); font-size: .72rem; letter-spacing: .06em; text-transform: uppercase; }
.punto { padding: 0 .35rem; color: var(--filete-fino); }

.intro { margin: 1rem 0 0; max-width: var(--lectura); font-size: 1.05em; color: var(--apagado); }
.intro p { margin: 0 0 .7rem; }

.edicion-numero { display: none; }

/* El indice lateral se quito: repetia los titulares que estaban dos dedos mas
   abajo y se apelotonaba en una columna estrecha. */
.sumario { display: none; }

/* --- La portada: retícula de filetes ------------------------------------------
   Las celdas comparten borde. Sin imagenes, es el filete el que separa una
   noticia de la siguiente y el que hace que veinte quepan sin agobiar. */

.bits {
  display: grid;
  gap: 0;
  border-top: 3px solid var(--filete);
  border-left: 1px solid var(--filete);
}

.bit {
  display: block;
  padding: 1.1rem 1.1rem 1.3rem;
  border-right: 1px solid var(--filete);
  border-bottom: 1px solid var(--filete);
}

.bit-carril { display: flex; align-items: center; gap: .45rem; margin-bottom: .5rem; }

.numero {
  margin: 0;
  font-family: var(--titular);
  font-size: 1.15rem;
  font-weight: 700;
  letter-spacing: .02em;
  color: var(--acento);
  font-variant-numeric: tabular-nums;
}

.bit-icono { display: grid; place-items: center; width: 1.3rem; height: 1.3rem; color: var(--tinta); }

.icono { width: .95rem; height: .95rem; display: block; }
.icono-mini { width: .78rem; height: .78rem; }

.bit-cuerpo { min-width: 0; }

.bit h2 {
  margin: 0 0 .5rem;
  font-family: var(--ui);
  font-weight: 700;
  font-size: clamp(1.05rem, 3.4vw, 1.2rem);
  line-height: 1.22;
  letter-spacing: -.01em;
}

.texto p { margin: 0 0 .6rem; color: var(--apagado); font-size: .93rem; }

.por-que {
  margin: .8rem 0;
  padding: .7rem .8rem;
  background: var(--realce);
  font-size: .9rem;
}
.por-que strong { font-weight: 700; }

/* La primera pieza manda: ocupa el ancho entero de la reticula y su titular
   va en condensada y en grande, como la apertura de un periodico. */
.bit-lead { padding: 1.5rem 1.2rem 1.8rem; }

.bit-lead h2 {
  font-family: var(--titular);
  font-size: clamp(1.9rem, 6.5vw, 3.2rem);
  line-height: 1;
  letter-spacing: -.015em;
  text-transform: uppercase;
}

.bit-lead .texto { max-width: var(--lectura); }
.bit-lead .texto p { font-size: 1rem; }
.bit-lead .numero { font-size: 1.6rem; }

.etiquetas { display: flex; flex-wrap: wrap; align-items: baseline; gap: .45rem; margin: 0 0 .5rem; }

.etiqueta {
  font-size: .62rem;
  font-weight: 700;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: var(--suave);
}

.etiqueta-categoria {
  padding: .12rem .4rem;
  background: var(--tinta);
  color: var(--papel);
  text-decoration: none;
}
.etiqueta-categoria:hover { background: var(--acento); }
.etiqueta-idioma { color: var(--acento); }

.menciona { margin: .5rem 0 0; font-size: .72rem; color: var(--apagado); }
.menciona a { color: var(--apagado); }

.pie-bit {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: .25rem .8rem;
  margin: .8rem 0 0;
  padding-top: .6rem;
  border-top: 1px solid var(--filete-fino);
  font-size: .68rem;
  letter-spacing: .04em;
  text-transform: uppercase;
}

.fuente { color: var(--tinta); font-weight: 700; text-decoration: none; }
.fuente:hover { color: var(--acento); }
.ficha-medio { color: var(--suave); text-decoration: none; }
.ficha-medio:hover { color: var(--tinta); }
.pie-bit .datos { font-size: .68rem; color: var(--suave); }
.volver { display: none; }

.fuentes-bit { margin: .8rem 0 0; font-size: .8rem; }
.fuentes-bit summary { cursor: pointer; color: var(--apagado); font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; }
.fuentes-bit ul { margin: .7rem 0 0; padding: 0; list-style: none; }
.fuentes-bit li { padding: .5rem 0; border-top: 1px solid var(--filete-fino); }
.titular-fuente { display: block; color: var(--apagado); }

.vacio { margin: 2rem 0; color: var(--apagado); }

/* --- Temas y medios ----------------------------------------------------------- */

.explorar { margin: 2.5rem 0 0; padding: 1.5rem 0 0; border-top: 3px solid var(--filete); }

.explorar h2 {
  margin: 0 0 1.2rem;
  font-family: var(--titular);
  font-weight: 700;
  font-size: clamp(1.6rem, 6vw, 2.6rem);
  line-height: 1;
  text-transform: uppercase;
}

.explorar-grupo { margin: 1.4rem 0 .7rem; }
.explorar-grupo:first-of-type { margin-top: 0; }

.nube { display: flex; flex-wrap: wrap; gap: .4rem; margin: 0; padding: 0; list-style: none; }

.nube a {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .35rem .65rem;
  border: 1px solid var(--filete);
  background: var(--tarjeta);
  color: var(--tinta);
  font-size: .7rem;
  letter-spacing: .04em;
  text-transform: uppercase;
  text-decoration: none;
}

.nube a:hover { background: var(--tinta); color: var(--papel); }
.nube a:hover .icono, .nube a:hover .nube-cuenta { color: var(--papel); }
.nube-cuenta { color: var(--suave); font-variant-numeric: tabular-nums; }
.explorar-pie { margin: 1rem 0 0; max-width: var(--lectura); }

/* --- Fichas de tema y de medio ------------------------------------------------- */

.ficha-titulo { display: flex; align-items: center; gap: .7rem; }

.ficha-icono {
  display: grid;
  place-items: center;
  flex: none;
  width: 2.4rem;
  height: 2.4rem;
  border: 1px solid var(--filete);
  color: var(--tinta);
}

.ficha-icono .icono { width: 1.2rem; height: 1.2rem; }

.lista-fichas { list-style: none; margin: 1.5rem 0 0; padding: 0; border-top: 1px solid var(--filete); }
.lista-fichas li { padding: 1rem 0; border-bottom: 1px solid var(--filete-fino); }

.lista-fichas h2 {
  margin: 0 0 .3rem;
  font-family: var(--ui);
  font-weight: 700;
  font-size: clamp(1.05rem, 3.6vw, 1.25rem);
  line-height: 1.2;
}
.lista-fichas h2 a { text-decoration: none; }
.lista-fichas h2 a:hover { color: var(--acento); }
.lista-fichas .resumen { margin: .3rem 0 0; color: var(--apagado); font-size: .92rem; }

.rejilla-fichas {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(11rem, 1fr));
  gap: 0;
  list-style: none;
  margin: 1.5rem 0 0;
  padding: 0;
  border-top: 1px solid var(--filete);
  border-left: 1px solid var(--filete);
}

.rejilla-fichas a {
  display: flex;
  flex-direction: column;
  gap: .5rem;
  height: 100%;
  padding: 1rem;
  border-right: 1px solid var(--filete);
  border-bottom: 1px solid var(--filete);
  background: var(--tarjeta);
  text-decoration: none;
}

.rejilla-fichas a:hover { background: var(--tinta); color: var(--papel); }
.rejilla-fichas a:hover .rejilla-cuenta, .rejilla-fichas a:hover .icono { color: var(--papel); }

.rejilla-nombre { font-family: var(--titular); font-size: 1.25rem; font-weight: 700; line-height: 1.1; text-transform: uppercase; }
.rejilla-cuenta { margin-top: auto; font-size: .66rem; letter-spacing: .1em; text-transform: uppercase; color: var(--suave); }

/* --- Archivo -------------------------------------------------------------------- */

.ano { margin-top: 2rem; }
.fichas, .archivo { list-style: none; margin: 1rem 0 0; padding: 0; border-top: 1px solid var(--filete); }
.archivo li, .fichas li { padding: 1rem 0; border-bottom: 1px solid var(--filete-fino); }

.archivo-titulo {
  font-family: var(--titular);
  font-size: clamp(1.3rem, 4.5vw, 1.8rem);
  font-weight: 700;
  text-transform: uppercase;
  text-decoration: none;
}
.archivo-titulo:hover { color: var(--acento); }

.archivo-temas { display: flex; gap: .45rem; margin: .45rem 0 0; }
.archivo-tema { display: grid; place-items: center; width: 1.3rem; height: 1.3rem; color: var(--suave); }

.fichas h2 { margin: 0 0 .3rem; font-family: var(--ui); font-weight: 700; font-size: 1.15rem; line-height: 1.2; }

/* --- Buscador --------------------------------------------------------------------- */

.buscador { margin: 1.5rem 0 0; }
.buscador label { display: block; margin-bottom: .45rem; font-size: .68rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }

.buscador input {
  width: 100%;
  padding: .75rem .9rem;
  border: 1px solid var(--filete);
  background: var(--tarjeta);
  color: var(--tinta);
  font-family: var(--titular);
  font-size: 1.3rem;
  font-weight: 700;
  text-transform: uppercase;
}
.buscador input:focus { outline: 2px solid var(--acento); outline-offset: -2px; }

.facetas { margin: 1.4rem 0 0; }
.faceta { margin-bottom: 1.1rem; }
.opciones { display: flex; flex-wrap: wrap; gap: .4rem; }

.opcion {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  padding: .35rem .65rem;
  border: 1px solid var(--filete);
  background: var(--tarjeta);
  color: var(--apagado);
  font-size: .7rem;
  letter-spacing: .04em;
  text-transform: uppercase;
  cursor: pointer;
}

.opcion:hover { color: var(--tinta); }
.opcion[aria-pressed="true"] { background: var(--tinta); color: var(--papel); }
.opcion[aria-pressed="true"] .icono, .opcion[aria-pressed="true"] .cuenta-opcion { color: var(--papel); }
.opcion .icono { color: var(--suave); }
.cuenta-opcion { color: var(--suave); font-variant-numeric: tabular-nums; }

.limpiar {
  margin-top: .4rem;
  padding: .3rem 0;
  border: 0;
  background: none;
  color: var(--acento);
  font-size: .7rem;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  cursor: pointer;
}

#resultados { list-style: none; margin: 1.2rem 0 0; padding: 0; border-top: 1px solid var(--filete); }
#resultados li { padding: 1rem 0; border-bottom: 1px solid var(--filete-fino); }
#resultados h2 { margin: 0 0 .25rem; font-family: var(--ui); font-weight: 700; font-size: 1.12rem; line-height: 1.22; }
#resultados h2 a { text-decoration: none; }
#resultados h2 a:hover { color: var(--acento); }
#resultados h2 .icono { display: inline-block; vertical-align: baseline; margin-right: .35rem; color: var(--suave); }
#resultados .resumen { margin: .3rem 0 0; color: var(--apagado); font-size: .92rem; }

/* El explorador: los filtros en una caja y los resultados al lado. En el movil
   se apilan, que es lo unico que cabe. */

.explorador .rail {
  margin: 1.5rem 0 0;
  padding: 1.1rem;
  border: 1px solid var(--filete);
  background: var(--tarjeta);
}

.explorador .buscador { margin: 0; }
.resultados-panel { min-width: 0; }

/* --- Paginas de texto ---------------------------------------------------------------- */

.pagina { margin-top: 1.5rem; max-width: var(--lectura); }
.pagina h2 { margin: 2rem 0 .5rem; font-family: var(--titular); font-weight: 700; font-size: 1.6rem; text-transform: uppercase; }
.pagina ul { margin: 0 0 1rem; padding-left: 1.1rem; }
.pagina li { margin-bottom: .4rem; }
.letra-pequena { font-size: .74rem; color: var(--apagado); }
.alta-respuesta { max-width: var(--lectura); }

/* --- Alta en el boletin ---------------------------------------------------------------- */

.alta { margin: 2.5rem 0 0; padding: 1.8rem; border: 3px solid var(--filete); background: var(--tinta); color: var(--papel); }

.alta h2 {
  margin: 0 0 .6rem;
  font-family: var(--titular);
  font-weight: 700;
  font-size: clamp(1.7rem, 6vw, 2.6rem);
  line-height: 1;
  text-transform: uppercase;
}

.alta p { margin: 0 0 1rem; color: #b9b4ac; max-width: var(--lectura); }
.alta a { color: var(--papel); }
.alta-formulario label { display: block; margin-bottom: .45rem; font-size: .66rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: #b9b4ac; }
.alta-fila { display: flex; flex-wrap: wrap; gap: .5rem; max-width: 30rem; }

.alta-fila input {
  flex: 1 1 14rem;
  padding: .7rem .85rem;
  border: 1px solid #3a3733;
  background: #171614;
  color: var(--papel);
  font-family: var(--ui);
  font-size: 1rem;
}

.alta-fila button {
  padding: .7rem 1.3rem;
  border: 0;
  background: var(--acento);
  color: #fff;
  font-family: var(--ui);
  font-size: .74rem;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
  cursor: pointer;
}
.alta-fila button:hover { background: #b0241a; }
.alta .letra-pequena { color: #918c85; }

.trampa { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }

/* --- Pie -------------------------------------------------------------------------------- */

.pie {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: 1.5rem var(--gutter) 3rem;
  border-top: 3px solid var(--filete);
  color: var(--apagado);
  font-family: var(--ui);
  font-size: .7rem;
  letter-spacing: .06em;
  text-transform: uppercase;
}

.pie a { color: var(--apagado); margin-right: 1rem; }
.pie p { margin: 0 0 .5rem; }

/* --- Tableta y escritorio ----------------------------------------------------------------
   La reticula se abre a dos y luego a tres columnas. La apertura ocupa la fila
   entera hasta que hay sitio para ponerle dos noticias al lado. */

@media (min-width: 46rem) {
  .bits { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .bit-lead { grid-column: 1 / -1; }
}

@media (min-width: 46rem) {
  .explorador {
    display: grid;
    grid-template-columns: 17rem minmax(0, 1fr);
    gap: 0 2rem;
    align-items: start;
  }

  .explorador > .edicion-cabecera { grid-column: 1 / -1; }
  .explorador > .alta { grid-column: 1 / -1; }

  .explorador .rail { position: sticky; top: 1rem; }
}

@media (min-width: 68rem) {
  .bits { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .bit-lead { grid-column: 1 / 3; }
  .rejilla-fichas { grid-template-columns: repeat(auto-fill, minmax(13rem, 1fr)); }
  .logo-bloque { letter-spacing: -.025em; }
}
