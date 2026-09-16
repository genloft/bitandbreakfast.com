<?php
/**
 * La hoja de estilo del sitio publicado.
 *
 * Es una plantilla y no un .css suelto porque el generador la escribe como
 * escribe todo lo demas: asi hay un unico sitio del que sale publico/, y
 * borrar esa carpeta entera nunca pierde nada que no se pueda regenerar.
 *
 * CSS a mano, un solo fichero, sin build y sin fuentes de terceros. La
 * politica de seguridad del sitio no permite cargar nada de fuera, y eso es
 * una decision, no una limitacion.
 */

declare(strict_types=1);

?>
:root {
  color-scheme: light dark;
  --fondo: #f6f5f2;
  --papel: #fff;
  --tinta: #1b1b1a;
  --apagado: #6b675e;
  --borde: #e0ddd6;
  --acento: #1b1b1a;
}

@media (prefers-color-scheme: dark) {
  :root {
    --fondo: #14140f;
    --papel: #1c1c18;
    --tinta: #eceae4;
    --apagado: #a29d91;
    --borde: #2e2e27;
    --acento: #eceae4;
  }
}

* { box-sizing: border-box; }

body {
  margin: 0;
  background: var(--fondo);
  color: var(--tinta);
  font: 17px/1.65 Georgia, "Iowan Old Style", "Times New Roman", serif;
  -webkit-text-size-adjust: 100%;
}

a { color: var(--tinta); text-decoration-thickness: 1px; text-underline-offset: 2px; }
a:hover { text-decoration-style: dotted; }

/* --- Cabecera y pie ------------------------------------------------------ */

.cabecera, .pie, main { max-width: 38rem; margin: 0 auto; padding: 0 1.5rem; }

.cabecera { padding-top: 3rem; padding-bottom: 1rem; }
.cabecera .marca {
  margin: 0;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
  font-weight: 700;
  letter-spacing: -.01em;
}
.cabecera .marca a { text-decoration: none; }
.cabecera .promesa { margin: .25rem 0 0; color: var(--apagado); font-size: .9rem; }

.pie {
  margin-top: 4rem;
  padding-top: 1.5rem;
  padding-bottom: 4rem;
  border-top: 1px solid var(--borde);
  color: var(--apagado);
  font-size: .85rem;
}
.pie p { margin: .4rem 0; }
.letra-pequena { max-width: 30rem; }

/* --- Edicion -------------------------------------------------------------- */

h1 {
  font-size: 1.8rem;
  line-height: 1.25;
  margin: 1.5rem 0 .5rem;
  letter-spacing: -.01em;
}

.datos {
  margin: 0 0 2rem;
  color: var(--apagado);
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
  font-size: .82rem;
}

.intro { font-size: 1.05rem; color: var(--apagado); margin-bottom: 2.5rem; }

.bit {
  padding: 1.75rem 0;
  border-top: 1px solid var(--borde);
}

.bit h2 {
  font-size: 1.22rem;
  line-height: 1.3;
  margin: 0 0 .5rem;
}

.bit p { margin: 0 0 .9rem; }

.etiquetas {
  margin: 0 0 .9rem;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
  font-size: .7rem;
  text-transform: uppercase;
  letter-spacing: .04em;
}

.etiqueta {
  display: inline-block;
  padding: .15rem .45rem;
  margin-right: .3rem;
  border: 1px solid var(--borde);
  border-radius: 3px;
  color: var(--apagado);
}

.por-que {
  padding: .8rem 1rem;
  background: var(--papel);
  border-left: 2px solid var(--acento);
  font-size: .95rem;
}

.fuente {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
  font-size: .82rem;
}
.fuente a { color: var(--apagado); }

/* --- Archivo -------------------------------------------------------------- */

.archivo { list-style: none; padding: 0; }
.archivo li { padding: .9rem 0; border-bottom: 1px solid var(--borde); }
.archivo .datos {
  display: block;
  margin: .2rem 0 0;
  font-size: .8rem;
}

.vacio { color: var(--apagado); }

@media (max-width: 30rem) {
  body { font-size: 16px; }
  h1 { font-size: 1.5rem; }
}
