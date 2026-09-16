<?php
/**
 * La hoja de estilo del sitio publicado.
 *
 * Es una plantilla y no un .css suelto porque el generador la escribe como
 * escribe todo lo demas: asi hay un unico sitio del que sale publico/, y
 * borrar esa carpeta entera nunca pierde nada que no se pueda regenerar.
 *
 * Tres decisiones que conviene no deshacer sin pensarlo:
 *
 *   - Movil primero. Las reglas base son las del telefono y las medias
 *     consultas solo anaden, nunca corrigen. Mas de la mitad de los correos
 *     se abren en el movil y de ahi llega el trafico.
 *   - Tipografia fluida con clamp(), sin saltos bruscos entre tamanos. Un
 *     titular no puede pasar de gigante a diminuto en un pixel de diferencia.
 *   - Cero dependencias: ni fuentes de Google, ni iconos, ni reset de nadie.
 *     La politica de seguridad del sitio no permite cargar nada de fuera, y
 *     eso es una decision, no una limitacion.
 */

declare(strict_types=1);

?>
:root {
  color-scheme: light dark;

  --fondo: #f6f5f2;
  --papel: #fffefb;
  --tinta: #191917;
  --apagado: #6b675e;
  --borde: #e2ded4;
  --acento: #8a3a12;
  --realce: #fdf6e8;

  --ancho: 40rem;
  --gutter: clamp(1.15rem, 5vw, 2.5rem);
}

@media (prefers-color-scheme: dark) {
  :root {
    --fondo: #14140f;
    --papel: #1b1b16;
    --tinta: #ece9e1;
    --apagado: #a8a295;
    --borde: #302f27;
    --acento: #e8a071;
    --realce: #221f18;
  }
}

*, *::before, *::after { box-sizing: border-box; }

html { -webkit-text-size-adjust: 100%; }

body {
  margin: 0;
  background: var(--fondo);
  color: var(--tinta);
  font: 1.0625rem/1.65 Georgia, "Iowan Old Style", "Times New Roman", serif;
  font-size: clamp(1.0625rem, 1rem + 0.25vw, 1.1875rem);
  overflow-wrap: break-word;
}

a { color: var(--tinta); text-decoration-thickness: 1px; text-underline-offset: .18em; }
a:hover { text-decoration-style: dotted; }
a:focus-visible { outline: 2px solid var(--acento); outline-offset: 3px; border-radius: 2px; }

/* El enlace de salto solo aparece al tabular: teclado sin ratón, que es como
   navega quien usa lector de pantalla. */
.saltar {
  position: absolute;
  left: -9999px;
  padding: .6rem 1rem;
  background: var(--papel);
  border: 1px solid var(--borde);
}
.saltar:focus { left: 1rem; top: 1rem; z-index: 10; }

.sans {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
}

/* --- Cabecera ------------------------------------------------------------
   El logotipo va a la derecha y grande. En el movil ocupa el ancho entero y
   el menu se coloca debajo, alineado con el. */

.cabecera {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: 2rem var(--gutter) 1.25rem;
}

.cabecera-interior {
  display: flex;
  flex-direction: column-reverse;
  align-items: flex-end;
  gap: .5rem;
}

.logo {
  display: block;
  text-align: right;
  text-decoration: none;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
  font-weight: 800;
  font-size: clamp(2.4rem, 13vw, 4.5rem);
  line-height: .95;
  letter-spacing: -.035em;
  color: var(--tinta);
}

.logo:hover { text-decoration: none; }
.logo-amp { color: var(--acento); padding: 0 .04em; }

.promesa {
  margin: .75rem 0 0;
  text-align: right;
  color: var(--apagado);
  font-size: .82rem;
  line-height: 1.4;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
}

.menu {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 1.1rem;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
  font-size: .82rem;
}

/* Area tactil comoda sin agrandar el texto. */
.menu a {
  display: inline-block;
  padding: .4rem 0;
  color: var(--apagado);
  text-decoration: none;
}
.menu a:hover { color: var(--tinta); text-decoration: underline; }
.menu a[aria-current="page"] { color: var(--tinta); font-weight: 600; }

/* --- Estructura ---------------------------------------------------------- */

main {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: 0 var(--gutter);
}

.edicion-cabecera {
  padding: 1.5rem 0 0;
  border-top: 3px solid var(--tinta);
}

.sello {
  margin: 1rem 0 .35rem;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
  font-size: .7rem;
  font-weight: 700;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: var(--acento);
}

h1 {
  margin: 0 0 .6rem;
  font-size: clamp(1.75rem, 7vw, 2.6rem);
  line-height: 1.15;
  letter-spacing: -.02em;
}

.datos {
  margin: 0;
  color: var(--apagado);
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
  font-size: .82rem;
}
.punto { padding: 0 .3rem; }

.intro {
  margin-top: 1.5rem;
  color: var(--apagado);
  font-size: 1.05em;
}
.intro p { margin: 0 0 .8rem; }

/* --- Sumario -------------------------------------------------------------
   Lo que convierte esto en un radar: en diez segundos se sabe si la semana
   trae algo. Va antes que los bits y a la vista, no plegado. */

.sumario {
  margin: 2.5rem 0 0;
  padding: 1.25rem var(--gutter) .5rem;
  margin-left: calc(var(--gutter) * -1);
  margin-right: calc(var(--gutter) * -1);
  background: var(--papel);
  border-top: 1px solid var(--borde);
  border-bottom: 1px solid var(--borde);
}

.sumario h2 {
  margin: 0 0 .75rem;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
  font-size: .7rem;
  font-weight: 700;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: var(--apagado);
}

.sumario ol {
  margin: 0;
  padding: 0 0 0 1.4rem;
  font-size: .95em;
}

.sumario li { margin-bottom: .7rem; padding-left: .2rem; }
.sumario li::marker { color: var(--apagado); font-size: .8em; }
.sumario a { text-decoration: none; }
.sumario a:hover { text-decoration: underline; }

.sumario-etiqueta {
  display: block;
  margin-top: .1rem;
  color: var(--apagado);
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
  font-size: .72rem;
}

/* --- Bits ----------------------------------------------------------------
   Numerados y separados por una linea: el lector sabe siempre por cual va y
   cuanto le queda. */

.bit {
  display: flex;
  gap: 1rem;
  padding: 2rem 0;
  border-bottom: 1px solid var(--borde);
  /* Para que el ancla no quede pegada al borde superior al saltar. */
  scroll-margin-top: 1.5rem;
}

.bit:first-child { padding-top: 2.5rem; }

.numero {
  flex: 0 0 auto;
  width: 1.6rem;
  margin: .35rem 0 0;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
  font-size: .85rem;
  font-weight: 700;
  color: var(--acento);
  font-variant-numeric: tabular-nums;
}

.bit-cuerpo { min-width: 0; flex: 1; }

.bit h2 {
  margin: 0 0 .6rem;
  font-size: clamp(1.2rem, 4.6vw, 1.45rem);
  line-height: 1.25;
  letter-spacing: -.01em;
}

.etiquetas { margin: 0 0 .9rem; display: flex; flex-wrap: wrap; gap: .3rem; }

.etiqueta {
  display: inline-block;
  padding: .18rem .5rem;
  border: 1px solid var(--borde);
  border-radius: 999px;
  color: var(--apagado);
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
  font-size: .68rem;
  letter-spacing: .02em;
  white-space: nowrap;
}

.etiqueta-categoria { border-color: var(--acento); color: var(--acento); }

.texto p { margin: 0 0 .9rem; }

.por-que {
  margin: 1.1rem 0;
  padding: .85rem 1rem;
  background: var(--realce);
  border-left: 3px solid var(--acento);
  font-size: .96em;
}

.pie-bit {
  display: flex;
  flex-wrap: wrap;
  gap: .5rem 1.25rem;
  align-items: baseline;
  margin: 1rem 0 0;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
  font-size: .82rem;
}

.fuente { color: var(--acento); text-decoration: none; font-weight: 600; }
.fuente:hover { text-decoration: underline; }

.volver { margin-left: auto; color: var(--apagado); text-decoration: none; }
.volver:hover { text-decoration: underline; }

/* --- Archivo -------------------------------------------------------------- */

.ano h2 {
  margin: 2.5rem 0 .5rem;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
  font-size: .78rem;
  font-weight: 700;
  letter-spacing: .12em;
  color: var(--apagado);
}

.archivo { list-style: none; margin: 0; padding: 0; }

.archivo li { padding: .9rem 0; border-top: 1px solid var(--borde); }

.archivo-titulo {
  display: block;
  font-size: 1.1rem;
  line-height: 1.3;
  text-decoration: none;
}
.archivo-titulo:hover { text-decoration: underline; }
.archivo .datos { margin-top: .2rem; }

.vacio {
  margin: 2rem 0;
  padding: 1.5rem;
  border: 1px dashed var(--borde);
  color: var(--apagado);
}

/* --- Pie ------------------------------------------------------------------ */

.pie {
  max-width: var(--ancho);
  margin: 3rem auto 0;
  padding: 1.5rem var(--gutter) 4rem;
  border-top: 1px solid var(--borde);
}

.pie .menu { justify-content: flex-start; }

.letra-pequena {
  margin: 1rem 0 0;
  max-width: 32rem;
  color: var(--apagado);
  font-size: .82rem;
  line-height: 1.5;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
}

/* --- Pantallas grandes ---------------------------------------------------
   Lo unico que cambia es que el menu se pone a la izquierda del logotipo, en
   la misma linea. Todo lo demas ya funcionaba. */

@media (min-width: 34rem) {
  .cabecera-interior {
    flex-direction: row;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1.5rem;
  }

  /* El menu no se encoge: si se le deja, el contenedor flexible lo estruja
     hasta partir "Ultima edicion" en tres lineas. Quien cede es el logotipo,
     que para eso es fluido. */
  .menu {
    justify-content: flex-start;
    padding-bottom: .55rem;
    flex: 0 0 auto;
    flex-wrap: nowrap;
  }

  .logo { flex: 0 1 auto; }
}

@media print {
  .menu, .saltar, .volver { display: none; }
  body { background: #fff; color: #000; font-size: 11pt; }
  .bit { break-inside: avoid; }
}
