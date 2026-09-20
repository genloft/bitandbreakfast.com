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
 * Sobre las familias: Arial Narrow y Helvetica Neue -las condensadas del
 * viejo --titular- solo estan en Mac y en Windows. En Android y en Linux no
 * existe ninguna de las dos, y la pila caia a Arial sin condensar: un
 * periodico maquetado para caber, mostrado con la letra de una hoja de
 * calculo. La solucion no es una fuente de Google -eso es justo lo que la
 * politica de seguridad prohibe, cargar algo de fuera-, es una propia,
 * servida desde el propio origen: Big Shoulders (SIL Open Font License, en
 * plantillas/web/fuentes/), la misma familia condensada que ya usa
 * lib/imagen_social.php para las tarjetas que se comparten. Un solo corte
 * -negrita, el unico peso que --titular usa en toda esta hoja-, recortado al
 * latino y sin hinting: 18 KB en woff2, menos que un icono de los que este
 * sitio ya ha decidido no llevar.
 */

declare(strict_types=1);

?>
/* Un solo corte, autoalojado: negrita es el unico peso que --titular pide en
   toda esta hoja, y font-display: swap ensena el rotulo con la fuente de
   respaldo mientras esta llega, en vez de dejarlo en blanco. */
@font-face {
  font-family: "BB Titular";
  src: url("fuentes/BigShoulders-Titular-Bold.woff2") format("woff2");
  font-weight: 700;
  font-style: normal;
  font-display: swap;
}

:root {
  color-scheme: light;

  /* Papel de periodico: un blanco que tira a hueso. El blanco puro en pantalla
     deslumbra y ademas delata que no hay nada impreso detras. */
  --papel:   #f4f2ee;
  --tarjeta: #faf9f7;
  --tinta:   #0d0d0d;
  /* El texto seguido, casi negro. Estaba en --apagado, que es el gris de los
     datos secundarios: valido para una fecha de tres palabras y no para
     cuatro lineas seguidas. La diferencia entre leer y descifrar. */
  --texto:   #26241f;
  --apagado: #5f5c57;
  /* 4,97:1 sobre --papel y 5,28:1 sobre --tarjeta -antes #8d8982, 3,11:1 y
     3,31:1-: por debajo del 4,5:1 que exige WCAG AA para texto normal, y se
     usa a .68rem en más de una docena de sitios (.pie-bit .datos,
     .nube-cuenta, .rejilla-cuenta...), muy por debajo del tamaño de "texto
     grande" que se conformaría con menos contraste. */
  --suave:   #6b6862;
  --filete:  #0d0d0d;
  --filete-fino: #cfcac2;
  --negro:   #0d0d0d;
  --realce:  #e9e5df;

  /* El acento, rojo de tinta, para la ultima hora y lo que esta activo. */
  --acento:  #d02b1f;

  /* Condensada para rotulos y titulares: es la letra de los periodicos
     precisamente porque cabe mas en menos sitio. BB Titular -autoalojada,
     @font-face arriba- va primero porque es la unica que se ve igual en
     Windows, Mac, Android y Linux; Arial Narrow y Helvetica Neue quedan como
     respaldo por si el fichero no llega a cargar, y el resto es red de
     seguridad. */
  --titular: "BB Titular", "Arial Narrow", "Helvetica Neue", Helvetica, Arial, ui-sans-serif, sans-serif;
  --ui:      ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI",
             Inter, Helvetica, Arial, sans-serif;
  /* El cuerpo pasa de serif a sans. La serif es mas bonita en una columna
     ancha y de papel; aqui el texto vive en celdas de veinte caracteres y a
     quince pixeles, y ahi la sans de sistema se lee sin esfuerzo y la serif se
     emborrona. Se mantiene la serif para las paginas de texto seguido, que es
     donde tenia razon de ser. */
  --cuerpo:  ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI",
             Roboto, Inter, Helvetica, Arial, sans-serif;
  --lectura-serif: Georgia, Charter, "Iowan Old Style", "Times New Roman", serif;

  --ancho:   80rem;
  /* Subido de 38rem: en un monitor ancho una columna de lectura tan
     estrecha se quedaba a media pagina, con todo el hueco de al lado vacio
     -en la pagina de "Que es" sobre todo, que es texto de principio a fin y
     no tiene ninguna cuadricula al lado que justifique el corte-. Sigue
     siendo mas estrecha que --ancho: no es "sin limite", es un limite mas
     generoso. */
  --lectura: 46rem;
  --gutter:  clamp(.9rem, 3vw, 1.6rem);

  color-scheme: light dark;
}


*, *::before, *::after { box-sizing: border-box; }

html { -webkit-text-size-adjust: 100%; }

body {
  margin: 0;
  background: var(--papel);
  color: var(--tinta);
  font-family: var(--cuerpo);
  font-size: clamp(1rem, .96rem + .25vw, 1.1rem);
  line-height: 1.6;
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
  font-size: .74rem;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: var(--tinta);
}

.datos, .menu, .etiqueta, .letra-pequena, .cuenta, .menciona, .pie-bit,
.buscador label, .alta-formulario label, .opcion, .limpiar, .numero,
.nube a, .rejilla-cuenta, .cuenta-opcion, .promesa,
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

/* --- Cabecera ---------------------------------------------------------------
   Barra de navegacion con el sello a la izquierda, y debajo el nombre a todo
   lo ancho. Las dos cosas dentro de filetes, como el resto de la pagina. */

.cabecera { max-width: var(--ancho); margin: 0 auto; padding: 0 var(--gutter); }

.cabecera-barra {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: .5rem 1rem;
  padding: .55rem 0;
  border-bottom: 1px solid var(--filete);
}

/* En movil el menu ya ocupa varias lineas por su cuenta -flex-wrap propio-;
   sin esto el buscador se colaba a media altura junto al enlace que
   quedara centrado en ese bloque, en vez de caer limpio en su propia
   linea. */
.cabecera-buscar { flex-basis: 100%; }

@media (min-width: 30rem) {
  .cabecera-buscar { flex-basis: auto; }
}

/* El sello: un circulo negro con la inicial. No es un icono ilustrativo -de
   esos ya hubo uno y sobraba-, es la marca reducida a una letra.

   El unico gesto de movimiento que se permite el sitio: un barrido de radar
   -mas oscuro que claro- girando muy despacio detras de la letra. No es un
   icono nuevo, es la misma letra de siempre con una prueba de vida detras, y
   dice sin palabras lo que la copia del sitio repite todo el rato: esto es
   un radar, no una foto fija. Se pinta con una variable de angulo animada
   -@property, sin ella el navegador se queda con el circulo liso de
   siempre-, nunca con un <script> que la CSP del sitio no deja entrar. */
@property --angulo-sello {
  syntax: '<angle>';
  inherits: false;
  initial-value: 0deg;
}

.sello-marca {
  display: grid;
  place-items: center;
  flex: none;
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  --angulo-sello: 0deg;
  background: conic-gradient(from var(--angulo-sello),
              var(--tinta) 0deg, var(--tinta) 300deg, #302d28 330deg, var(--tinta) 360deg);
  color: var(--papel);
  font-family: var(--titular);
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: .02em;
  text-decoration: none;
  animation: barrido-sello 9s linear infinite;
}

@keyframes barrido-sello {
  to { --angulo-sello: 360deg; }
}

/* El mismo gesto, en forma de punto: dice "esto sigue latiendo" junto a un
   dato que de verdad viene de una medicion -la hora de la ultima generacion,
   el titulo de "lo mas leido"-, nunca junto a algo decorativo. Un pulso
   suave y no un parpadeo, porque parpadear pide atencion y esto solo la
   ofrece. */
.pulso {
  display: inline-block;
  vertical-align: middle;
  flex: none;
  width: .5rem;
  height: .5rem;
  border-radius: 50%;
  background: var(--acento);
  animation: pulso 2.4s ease-in-out infinite;
}

@keyframes pulso {
  0%, 100% { opacity: .4; transform: scale(.8); }
  50%      { opacity: 1;  transform: scale(1); }
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

/* Cifras, Tendencias y Glosario, agrupadas: son paginas de consulta, no de
   lectura diaria, y no merecen el mismo peso que Portada o Temas en la
   barra. <details> nativo, sin JS: se abre y cierra solo. */
.menu-recursos { position: relative; }

.menu-recursos summary {
  display: inline-block;
  padding: .3rem 0;
  color: var(--apagado);
  cursor: pointer;
  list-style: none;
}

.menu-recursos summary::-webkit-details-marker { display: none; }
.menu-recursos summary::after { content: ' \25BE'; font-size: .85em; }
.menu-recursos summary:hover { color: var(--tinta); }

.menu-recursos[open] summary,
.menu-recursos summary[aria-current="page"] {
  color: var(--tinta);
  border-bottom: 2px solid var(--acento);
}

.menu-recursos-lista {
  position: absolute;
  z-index: 5;
  top: 100%;
  left: 0;
  display: flex;
  flex-direction: column;
  gap: .7rem;
  min-width: 9rem;
  margin-top: .5rem;
  padding: .9rem 1.1rem;
  background: var(--tarjeta);
  border: 1px solid var(--filete);
}

.menu-recursos-lista a { white-space: nowrap; }

/* El buscador en vivo de la cabecera: mismo filete y misma tarjeta que el
   desplegable de "Recursos" de aqui arriba, para que se lea como parte de
   la misma barra y no como un elemento prestado de otra pagina. */
.cabecera-buscar { position: relative; margin-left: auto; }

.cabecera-buscar-forma {
  display: flex;
  align-items: center;
  border: 1px solid var(--filete);
  background: var(--tarjeta);
}
.cabecera-buscar-forma:focus-within { border-color: var(--acento); }

.cabecera-buscar-forma input {
  width: 11rem;
  max-width: 40vw;
  padding: .35rem .5rem;
  border: none;
  background: transparent;
  color: var(--tinta);
  font-family: var(--ui);
  font-size: .8rem;
}
.cabecera-buscar-forma input::placeholder { color: var(--suave); }
.cabecera-buscar-forma input:focus { outline: none; }

.cabecera-buscar-forma button {
  display: grid;
  place-items: center;
  padding: 0 .55rem;
  border: none;
  background: transparent;
  color: var(--apagado);
  cursor: pointer;
}
.cabecera-buscar-forma button:hover,
.cabecera-buscar-forma button:focus-visible { color: var(--tinta); }

.cabecera-resultados {
  position: absolute;
  z-index: 5;
  top: 100%;
  right: 0;
  margin-top: .4rem;
  width: 20rem;
  max-width: 80vw;
  max-height: 22rem;
  overflow-y: auto;
  background: var(--tarjeta);
  border: 1px solid var(--filete);
}

.cabecera-resultados a {
  display: block;
  padding: .55rem .7rem;
  border-bottom: 1px solid var(--filete-fino);
  color: var(--tinta);
  font-family: var(--ui);
  font-size: .82rem;
  font-weight: 600;
  text-decoration: none;
}
.cabecera-resultados a:last-child { border-bottom: none; }
.cabecera-resultados a:hover,
.cabecera-resultados a:focus-visible { background: var(--papel); }

.cabecera-resultados-vacio { margin: 0; padding: .6rem .7rem; color: var(--suave); font-size: .82rem; }

/* El nombre y las cifras, en la misma fila. En el movil se apilan: el panel
   debajo del nombre y a todo lo ancho, que es lo unico que cabe. */
.cabecera-marca { display: block; }

.logo { display: block; text-decoration: none; padding: clamp(.9rem, 3vw, 1.6rem) 0 .7rem; }
.logo:hover { text-decoration: none; }

/* El nombre sigue siendo la pieza mas grande de la pagina. Encoge para dejar
   sitio a las cifras, pero no tanto como para dejar de mandar.

   Entra una vez por pagina, como un sello de imprenta: llega un poco ancho y
   aplastado -el golpe-, rebota un pelo hacia el lado contrario y se asienta.
   Nada en bucle: es el unico gesto de la cabecera que no vuelve a repetirse
   solo, para que siga leyendose como la marca y no como una animacion de
   aplicacion. */
.logo-bloque {
  display: block;
  font-family: var(--titular);
  font-weight: 700;
  font-size: clamp(2.4rem, 11vw, 5.6rem);
  line-height: .86;
  letter-spacing: -.02em;
  text-transform: uppercase;
  white-space: nowrap;
  transform-origin: left center;
  animation: sello-imprenta .55s cubic-bezier(.16, 1, .3, 1) both;
}

@keyframes sello-imprenta {
  0%   { opacity: 0; transform: scale(1.05, .92); filter: blur(3px); }
  55%  { opacity: 1; transform: scale(.99, 1.02); filter: blur(0); }
  100% { opacity: 1; transform: scale(1, 1); }
}

/* El ampersand es el unico caracter de la marca con el acento de color, asi
   que es el que contesta al gesto de volver a portada: un giro pequeño y
   con rebote, no una vuelta entera. Y no espera a que alguien pase por
   encima: cada pocos segundos se guiña solo, el mismo gesto pero sin que
   nadie lo pida, para que la marca de señales de vida aunque nadie toque
   nada. Al pasar el raton o el foco, el guiño automatico se para y manda
   el gesto deliberado, para que las dos animaciones no se pisen. */
.logo-amp {
  display: inline-block;
  color: var(--acento);
  transition: transform .4s cubic-bezier(.34, 1.56, .64, 1);
  animation: guino-amp 9s ease-in-out infinite;
}

@keyframes guino-amp {
  0%, 90%, 100% { transform: rotate(0deg) scale(1); }
  94%           { transform: rotate(-14deg) scale(1.15); }
  97%           { transform: rotate(6deg) scale(1.05); }
}

.logo:hover .logo-amp,
.logo:focus-visible .logo-amp {
  animation-play-state: paused;
  transform: rotate(-14deg) scale(1.15);
}

/* La misma idea del barrido del sello -una señal de que el radar sigue
   encendido-, pero detras del nombre entero y mucho mas discreta: no un
   dibujo que se note, solo un respirar de opacidad. Vive en el ::before de
   .logo, con z-index negativo, asi que pinta por debajo del texto y nunca
   le cambia el contraste a lo que se lee encima -las letras tapan el
   resplandor donde caen, y solo asoma en los huecos-. */
.logo { position: relative; }

.logo::before {
  content: '';
  position: absolute;
  inset: -1.2rem -.6rem;
  z-index: -1;
  background: radial-gradient(ellipse at center, var(--acento) 0%, transparent 68%);
  opacity: .05;
  animation: resplandor-logo 7s ease-in-out infinite;
}

@keyframes resplandor-logo {
  0%, 100% { opacity: .04; }
  50%      { opacity: .12; }
}

/* Quien pide menos movimiento no tiene por que verlo: el barrido, el pulso,
   el sello de imprenta, el giro del ampersand y el resplandor de detras se
   paran y dejan sitio a la version quieta de siempre.

   Tiene que ir despues de las reglas que anula, no antes: con la misma
   especificidad, en un empate gana la que aparezca ultima en la hoja, asi
   que puesto aqui arriba esto nunca desactivaba nada -.sello-marca era la
   unica excepcion, porque esa es la unica animacion que ya estaba definida
   por encima-. Comprobado con Playwright emulando
   prefers-reduced-motion: reduce, no solo leyendo la regla. */
@media (prefers-reduced-motion: reduce) {
  .sello-marca, .pulso, .logo-bloque, .logo-amp, .logo::before { animation: none; }
  .logo-amp { transition: none; }
  .logo::before { opacity: .05; }
}

/* --- El panel de cifras ------------------------------------------------------
   Las cuentas del radar: cuando fue, cuando sera y cuanto ha crecido cada
   cosa. Numeros tabulares para que las columnas cuadren aunque cambien las
   cifras, y el rojo solo en lo que ha entrado desde la ultima vez. */

.panel {
  margin: 0 0 .9rem;
  border-top: 1px solid var(--filete);
  padding-top: .6rem;
  font-family: var(--ui);
}

.panel dl { margin: 0; }
.panel dt, .panel dd { margin: 0; }

.panel dt {
  font-size: .64rem;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: var(--suave);
}

.panel-relojes {
  display: flex;
  flex-wrap: wrap;
  gap: .2rem 1.6rem;
  padding-bottom: .55rem;
  border-bottom: 1px solid var(--filete-fino);
}

.panel-reloj dd {
  font-family: var(--titular);
  font-size: 1.05rem;
  font-weight: 700;
  letter-spacing: .01em;
  text-transform: uppercase;
  font-variant-numeric: tabular-nums;
}

.panel-cifras {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0;
  margin-top: .55rem;
}

.panel-cifra { padding-right: .9rem; }
.panel-cifra + .panel-cifra { padding-left: .9rem; border-left: 1px solid var(--filete-fino); }

.panel-cifra dd {
  display: flex;
  align-items: baseline;
  gap: .4rem;
  font-variant-numeric: tabular-nums;
}

.panel-nuevas {
  font-family: var(--titular);
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--suave);
}

.panel-nuevas-hay { color: var(--acento); }

.panel-total {
  font-size: .72rem;
  font-weight: 600;
  letter-spacing: .04em;
  color: var(--apagado);
}

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

/* --- Destacados: dos fichas de acceso rapido, no un titular de tapa ---------
   La primera version llenaba una banda negra a todo lo ancho con una cifra a
   la escala del nombre de la cabecera: paraba el pulgar, pero tambien
   ocupaba media pantalla en movil y el rojo de una cifra tan grande pesaba
   mas de lo que el dato merecia. Esta version es una ficha compacta -numero
   a un lado, texto al otro, como el panel de la cabecera-, con el acento
   reducido a un filete lateral: sigue distinguiendose de un enlace de menu,
   pero sin construir un cartel encima de la portada. */

.destacados {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr));
  gap: .8rem;
  margin-top: 1.6rem;
}

.destacado {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: .9rem 1.1rem;
  background: var(--tarjeta);
  border: 1px solid var(--filete-fino);
  border-left: 3px solid var(--acento);
  color: var(--tinta);
  text-decoration: none;
  transition: background .15s ease, border-color .15s ease;
}

.destacado:hover { background: var(--realce); border-left-color: var(--tinta); }

.destacado-cifra {
  flex: none;
  font-family: var(--titular);
  font-weight: 700;
  font-size: clamp(1.7rem, 4.5vw, 2.2rem);
  line-height: 1;
  letter-spacing: -.01em;
  color: var(--tinta);
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}

.destacado-texto { display: flex; flex-direction: column; gap: .2rem; min-width: 0; }

.destacado-rotulo {
  font-family: var(--ui);
  font-size: .66rem;
  font-weight: 700;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: var(--apagado);
}

.destacado-pie {
  font-family: var(--ui);
  font-size: .82rem;
  line-height: 1.35;
  color: var(--apagado);
}

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

/* Las mayusculas espaciadas se leen peor que las minusculas, asi que aqui van
   lo justo: un poco mas grandes y con menos espaciado del que tenian. */
.datos { margin: 0; color: var(--apagado); font-size: .76rem; letter-spacing: .04em; text-transform: uppercase; }
.punto { padding: 0 .35rem; color: var(--filete-fino); }

/* Sin max-width a proposito: es una sola frase de aviso, no una lectura
   larga, y capada a --lectura se quedaba a media pantalla en un monitor
   ancho, con todo el hueco de al lado vacio -justo lo que la cuadricula o
   la lista que viene despues, a todo lo ancho, no hacen-. */
.intro { margin: 1rem 0 0; font-size: 1.05em; color: var(--apagado); }
.intro p { margin: 0 0 .7rem; }

.edicion-numero { display: none; }

/* El indice lateral se quito: repetia los titulares que estaban dos dedos mas
   abajo y se apelotonaba en una columna estrecha. */
.sumario { display: none; }

/* --- El rio: un solo flujo -----------------------------------------------------
   La portada ya no separa un dia de otro con su propia barra: cada ficha
   lleva la fecha encima (.etiqueta-fecha, en bit.php), y lo que antes hacia
   una cabecera de dia -"esto es de hoy, esto es de ayer"- lo hace ahora esa
   etiqueta, sin cortar la reticula en tramos. */

.mas-dias {
  margin: 1.6rem 0 0;
  font-family: var(--ui);
  font-size: .72rem;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
}

.mas-dias a { text-decoration: none; border-bottom: 2px solid var(--acento); padding-bottom: .1rem; }

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
  position: relative;
  padding: 1.1rem 1.1rem 1.3rem;
  border-right: 1px solid var(--filete);
  border-bottom: 1px solid var(--filete);
}

/* Toda la celda es zona de clic, no solo el titular. El truco del "enlace
   estirado": el titular lleva un ::after que cubre la celda entera -.bit ya
   es su contenedor posicionado, arriba-, y cada control propio de la ficha
   -las etiquetas, "menciona", compartir y votar, "N fuentes lo cuentan"-
   sube de plano con z-index para seguir siendo su propio objetivo de toque
   en vez de desaparecer bajo el titular. Lo que queda debajo del estirado
   -el numero, el icono de tema, el nombre de la fuente, las fechas- no
   pierde nada: nunca eran zona de clic propia, y ahora tocarlos lleva al
   mismo sitio que tocar el titular. */
.bit-resumen h2 a::after {
  content: "";
  position: absolute;
  inset: 0;
}

.etiquetas,
.menciona,
.pie-acciones,
.fuentes-bit {
  position: relative;
  z-index: 1;
}

/* Todas las fichas del mismo alto, para que la reticula se lea como una
   reticula y no como columnas de periodico a las que les falta cortar. La
   apertura del dia (.bit-lead) queda fuera a proposito: ese es el titular
   grande, no una celda mas. Lo que no cabe en la altura fija se recorta con
   -webkit-line-clamp en el texto, y de respaldo con el overflow del propio
   contenedor: entre perder una linea de cuerpo y que la ficha de al lado se
   quede vacia, la primera opcion es la que no rompe la reticula. */
.bit:not(.bit-lead) {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.bit:not(.bit-lead) .bit-cuerpo {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
}

.bit:not(.bit-lead) .bit-resumen {
  flex: 1;
  min-height: 0;
  overflow: hidden;
}

/* max-height de respaldo en las tres: dentro de un padre flex con flex:1,
   -webkit-line-clamp a veces deja de recortar de verdad -el ellipsis sale
   bien, pero una linea de mas se sigue pintando por debajo del cuadro-. El
   tope en altura no depende de ese modo de caja y no falla. */
.bit:not(.bit-lead) h2 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  max-height: 4.5rem;
}

.bit:not(.bit-lead) .texto p {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  max-height: 5rem;
}

/* Sin esto, "por que importa" es el unico bloque de la ficha sin tope de
   lineas: a un movil, con menos caracteres por linea, le basta para
   desbordar la altura fija y cortarse a medio caracter en vez de a final de
   linea. Recortado y ya.
   Sin -webkit-line-clamp a proposito: con el padding que lleva este cuadro,
   un max-height calculado justo para dos lineas deja que se pinte un
   fragmento de una tercera por debajo del recorte -probado a mano, no es
   teoria-. Bajando el tope claramente por debajo de esa medida el recorte
   vuelve a ser limpio, a costa de perder el "..." final -recorte limpio,
   pero sin decir que hay mas: un corte de dos pixeles no arregla eso solo,
   asi que un degradado, no un "...", marca la diferencia entre "esta
   cortado" y "acaba ahi" sin volver al recorte sucio de -webkit-line-clamp-. */
.bit:not(.bit-lead) .por-que {
  position: relative;
  max-height: 3.5rem;
  overflow: hidden;
}

/* El degradado funde el texto en el propio fondo de la caja -no en el de la
   pagina-, para que valga tanto en la version normal (--realce) como en la
   negativa de .bit-multifuente (un negro translucido): funde hacia el mismo
   color que ya pinta el resto de la caja, así que nunca deja una costura. */
.bit:not(.bit-lead) .por-que::after {
  content: "";
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  height: 2px;
  background: linear-gradient(to bottom, transparent, var(--realce));
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

.bit h2 a { color: inherit; text-decoration: none; }
.bit h2 a:hover { color: var(--acento); }

.bit h2 {
  margin: 0 0 .5rem;
  font-family: var(--ui);
  font-weight: 700;
  font-size: clamp(1.05rem, 3.4vw, 1.2rem);
  line-height: 1.22;
  letter-spacing: -.01em;
}

.texto p {
  margin: 0 0 .65rem;
  color: var(--texto);
  font-size: 1.02rem;
  line-height: 1.58;
}

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
.bit-lead .texto p { font-size: 1.12rem; line-height: 1.55; }
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

.etiqueta-fecha { color: var(--apagado); text-decoration: none; }
.etiqueta-fecha:hover { color: var(--tinta); }

/* La unica etiqueta que no describe el bit -de cuando es, de que tema-, sino
   que apunta a un juicio humano dentro de el: mismo fondo que .por-que, para
   que se note que son la misma cosa antes incluso de seguir el enlace. */
.etiqueta-analisis {
  padding: .12rem .4rem;
  background: var(--realce);
  color: var(--tinta);
  text-decoration: none;
}
.etiqueta-analisis:hover { background: var(--acento); color: var(--papel); }

.menciona { margin: .5rem 0 0; font-size: .78rem; color: var(--apagado); }
.menciona a { color: var(--apagado); }

.pie-bit {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: .5rem .8rem;
  margin: .8rem 0 0;
  padding-top: .6rem;
  border-top: 1px solid var(--filete-fino);
  font-size: .68rem;
  letter-spacing: .04em;
  text-transform: uppercase;
}

.pie-fuente { font-weight: 700; color: var(--tinta); }

/* La fila de acciones: un icono por gesto, sin texto al lado. Lo que dice
   cada uno vive en su aria-label, que hace de nombre accesible y -con el
   ::after de aqui abajo- de globo al pasar el raton o al llegar por
   teclado, asi que nadie se queda sin saber que va a pasar al pulsar. */
.pie-acciones { display: flex; align-items: center; gap: .15rem; margin-left: auto; }

.icono-boton {
  position: relative;
  display: grid;
  place-items: center;
  width: 1.9rem;
  height: 1.9rem;
  color: var(--suave);
  text-decoration: none;
}
.icono-boton:hover, .icono-boton:focus-visible { color: var(--tinta); }
.icono-boton .icono { width: 1.05rem; height: 1.05rem; }

.icono-boton::after {
  content: attr(aria-label);
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translate(-50%, -.35rem);
  background: var(--tinta);
  color: var(--papel);
  font-size: .62rem;
  font-weight: 600;
  letter-spacing: .03em;
  text-transform: none;
  white-space: nowrap;
  padding: .3rem .55rem;
  border-radius: 2px;
  opacity: 0;
  pointer-events: none;
  transition: opacity .12s ease;
  z-index: 2;
}
.icono-boton:hover::after, .icono-boton:focus-visible::after { opacity: 1; }

.pie-bit .datos { font-size: .68rem; color: var(--suave); }
.volver { display: none; }

.fuentes-bit { margin: .8rem 0 0; font-size: .8rem; }
.fuentes-bit summary { cursor: pointer; color: var(--apagado); font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; }
.fuentes-bit ul { margin: .7rem 0 0; padding: 0; list-style: none; }
.fuentes-bit li { padding: .5rem 0; border-top: 1px solid var(--filete-fino); }
.titular-fuente { display: block; color: var(--apagado); }

/* Lo que cuentan varios medios a la vez no es un dato mas: es lo que un
   directivo tiene que haber visto si solo mira la portada un momento. El
   negativo -fondo negro, letra blanca- es la misma paleta que ya usaba el
   resto del sitio para "esto manda" (la cabecera, el rotulo de categoria),
   llevada a la ficha entera en vez de a una esquina. */
.bit-multifuente {
  background: var(--tinta);
  color: var(--papel);
}
.bit-multifuente .numero { color: var(--papel); }
.bit-multifuente .bit-icono { color: var(--papel); }
.bit-multifuente h2 a:hover { color: var(--papel); text-decoration: underline; }
.bit-multifuente .texto p { color: var(--papel); }
.bit-multifuente .etiqueta-categoria { background: var(--papel); color: var(--tinta); }
.bit-multifuente .etiqueta-categoria:hover { background: var(--acento); color: var(--papel); }
.bit-multifuente .etiqueta-idioma { color: var(--papel); }
.bit-multifuente .etiqueta-fecha { color: var(--filete-fino); }
.bit-multifuente .etiqueta-fecha:hover { color: var(--papel); }
.bit-multifuente .por-que { background: rgba(244, 242, 238, .14); color: var(--papel); }
.bit-multifuente .por-que::after { background: linear-gradient(to bottom, transparent, rgba(244, 242, 238, .14)); }
.bit-multifuente .etiqueta-analisis { background: rgba(244, 242, 238, .14); color: var(--papel); }
.bit-multifuente .etiqueta-analisis:hover { background: var(--acento); color: var(--papel); }
.bit-multifuente .menciona,
.bit-multifuente .menciona a,
.bit-multifuente .pie-bit .datos,
.bit-multifuente .fuentes-bit summary,
.bit-multifuente .titular-fuente { color: var(--filete-fino); }
.bit-multifuente .pie-bit { border-top-color: rgba(244, 242, 238, .3); }
.bit-multifuente .pie-fuente { color: var(--papel); }
.bit-multifuente .icono-boton { color: var(--filete-fino); }
.bit-multifuente .icono-boton:hover, .bit-multifuente .icono-boton:focus-visible { color: var(--papel); }
.bit-multifuente .icono-boton::after { background: var(--papel); color: var(--tinta); }
.bit-multifuente .fuentes-bit li { border-top-color: rgba(244, 242, 238, .2); }
.bit-multifuente .fuentes-bit a { color: var(--papel); }

.vacio { margin: 2rem 0; color: var(--apagado); }

/* --- Lo mas leido ---------------------------------------------------------------
   Entre el rio del dia y "seguir tirando del hilo": ya se ha leido lo de hoy,
   esto es la version corta de "que me he perdido esta semana". Ranking de
   verdad, sacado de clics, por eso el numero va en el mismo rojo que el de
   cada bit y no en un color nuevo. */

.masleido { margin: 2.5rem 0 0; padding: 1.5rem 0 0; border-top: 3px solid var(--filete); }

.masleido h2 {
  display: flex;
  align-items: center;
  gap: .6rem;
  margin: 0 0 1rem;
  font-family: var(--titular);
  font-weight: 700;
  font-size: clamp(1.4rem, 5vw, 2rem);
  line-height: 1;
  text-transform: uppercase;
}

.masleido-lista { list-style: none; margin: 0; padding: 0; border-top: 1px solid var(--filete); }

.masleido-lista li {
  display: flex;
  align-items: baseline;
  gap: .8rem;
  padding: .7rem 0;
  border-bottom: 1px solid var(--filete-fino);
}

.masleido-numero {
  flex: none;
  font-family: var(--titular);
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--acento);
  font-variant-numeric: tabular-nums;
}

.masleido-cuerpo { min-width: 0; font-family: var(--ui); font-weight: 700; }
.masleido-cuerpo a { text-decoration: none; }
.masleido-cuerpo a:hover { color: var(--acento); }
.masleido-cuerpo .datos { display: block; margin-top: .15rem; font-weight: 600; }

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

/* Ancho propio y no --lectura: esta pagina es texto de principio a fin, sin
   ninguna cuadricula al lado que explique un corte a media pantalla. La
   tipografia de lectura -serif, mas grande- ya la distingue del resto del
   sitio sin necesidad de dejarle un hueco vacio de adorno. */
.pagina { margin-top: 1.5rem; max-width: 60rem; font-family: var(--lectura-serif); font-size: 1.05rem; }
.pagina h2 { margin: 2rem 0 .5rem; font-family: var(--titular); font-weight: 700; font-size: 1.6rem; text-transform: uppercase; }
.pagina ul { margin: 0 0 1rem; padding-left: 1.1rem; }
.pagina li { margin-bottom: .4rem; }
.letra-pequena { font-size: .74rem; color: var(--apagado); }
.alta-respuesta { max-width: var(--lectura); }

/* --- Cifras: cuadro de mandos --------------------------------------------------------
   La unica pagina que no sale de la base propia. Cada tarjeta es un tema y
   dentro, una cifra por ambito -España, Global, Union Europea-, para que
   comparar sea leer, no calcular. Movil primero: en una columna hasta que
   hay sitio para dos cifras o dos tarjetas en la misma fila. */

.cuadro {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr));
  gap: 0;
  margin: 2rem 0;
  border-top: 3px solid var(--filete);
  border-left: 1px solid var(--filete);
}

.cuadro-tarjeta {
  padding: 1.3rem 1.3rem 1.5rem;
  border-right: 1px solid var(--filete);
  border-bottom: 1px solid var(--filete);
  background: var(--tarjeta);
}

.cuadro-tema {
  margin: 0 0 .5rem;
  font-family: var(--titular);
  font-weight: 700;
  font-size: 1.3rem;
  letter-spacing: .01em;
  text-transform: uppercase;
}

.cuadro-nota { margin: 0 0 .9rem; font-size: .82rem; color: var(--apagado); }

.cuadro-cifras { display: flex; flex-wrap: wrap; gap: .8rem 0; margin: .4rem 0; }

.cuadro-cifra { flex: 1 1 9rem; padding-right: .9rem; }
.cuadro-cifra + .cuadro-cifra { padding-left: .9rem; border-left: 1px solid var(--filete-fino); }

.cuadro-ambito {
  margin: 0;
  font-size: .62rem;
  font-weight: 700;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: var(--suave);
}

.cuadro-valor {
  margin: .2rem 0 .3rem;
  font-family: var(--titular);
  font-weight: 700;
  font-size: clamp(1.7rem, 6vw, 2.3rem);
  line-height: 1;
  color: var(--acento);
  font-variant-numeric: tabular-nums;
}

.cuadro-detalle { margin: 0; font-size: .85rem; color: var(--texto); line-height: 1.45; }

/* La fuente no es la letra pequeña de la cifra, es la otra mitad: por eso
   lleva su propia etiqueta en rojo y el nombre va en negro y en negrita, no
   apagado como el resto de metadatos de la tarjeta. */
.cuadro-fuente {
  margin: .6rem 0 0;
  padding-top: .5rem;
  border-top: 1px dotted var(--filete-fino);
  font-size: .72rem;
  letter-spacing: .01em;
  color: var(--suave);
}

.cuadro-fuente-etiqueta {
  margin-right: .3rem;
  font-family: var(--ui);
  font-size: .6rem;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: var(--acento);
}

.cuadro-fuente a,
.cuadro-fuente strong {
  color: var(--tinta);
  font-weight: 700;
  text-decoration-color: var(--filete-fino);
}
.cuadro-fuente a:hover { color: var(--acento); text-decoration-color: var(--acento); }

.cuadro-destacado {
  margin: 1rem 0 0;
  padding-top: .8rem;
  border-top: 1px solid var(--filete-fino);
  font-size: .88rem;
  font-weight: 700;
}

.cuadro-revision { margin: 1rem 0 0; }

/* --- Cumplimiento: mismo cuadro, con el estado marcado en el borde -------
   Reutiliza .cuadro/.cuadro-tarjeta entero -misma reticula, misma tarjeta-;
   lo unico propio es el color del borde izquierdo, que dice de un vistazo
   si una norma cuenta atras, ya esta vigente, sigue en tramite o ni
   siquiera aplica a un hotel. */

.cumplimiento-lista { list-style: none; padding: 0; }

.cumplimiento-norma { border-left: 4px solid var(--filete-fino); }
.cumplimiento-plazo { border-left-color: var(--acento); }
.cumplimiento-vigente { border-left-color: var(--tinta); }
.cumplimiento-tramite,
.cumplimiento-contexto { border-left-color: var(--suave); }

.cumplimiento-norma .cuadro-detalle + .cuadro-detalle { margin-top: .5rem; }

/* --- Calendario: mismo cuadro, en orden de fecha -------------------------
   Reutiliza .cuadro/.cuadro-tarjeta entero, igual que Cumplimiento: aquí no
   hay estado que marcar en el borde, solo el orden en que llegan los
   eventos que quedan por delante. */

.calendario-lista { list-style: none; padding: 0; }

/* --- Fuentes de Cifras --------------------------------------------------------------
   La bibliografia de la pagina de arriba, con el mismo peso que las cifras
   que sostiene: un nombre grande que enlaza, y debajo quien es. */

.fuentes { margin: 2.5rem 0 0; padding-top: 1.5rem; border-top: 3px solid var(--filete); }

.fuentes h2 {
  margin: 0 0 .5rem;
  font-family: var(--titular);
  font-weight: 700;
  font-size: clamp(1.4rem, 5vw, 2rem);
  line-height: 1;
  text-transform: uppercase;
}

.fuentes-lista { margin: 1.2rem 0 0; padding: 0; border-top: 1px solid var(--filete); }

.fuentes-fila { padding: .9rem 0; border-bottom: 1px solid var(--filete-fino); }

.fuentes-fila dt {
  margin: 0;
  font-family: var(--titular);
  font-weight: 700;
  font-size: 1.05rem;
  letter-spacing: .01em;
  text-transform: uppercase;
}
.fuentes-fila dt a { text-decoration: none; }
.fuentes-fila dt a:hover { color: var(--acento); }

.fuentes-fila dd { margin: .25rem 0 0; font-size: .85rem; color: var(--apagado); }

/* --- Glosario -------------------------------------------------------------------------
   Un diccionario, no una ficha: la sigla manda, la definicion es una frase
   y el enlace al tema es lo ultimo, para quien ya sabe que significa y
   quiere ver que se ha publicado sobre ello. */

.glosario-lista { margin: 1.5rem 0 0; padding: 0; border-top: 1px solid var(--filete); }

.glosario-fila { padding: 1rem 0; border-bottom: 1px solid var(--filete-fino); scroll-margin-top: 5rem; }

.glosario-fila dt {
  margin: 0 0 .3rem;
  font-family: var(--titular);
  font-weight: 700;
  font-size: 1.1rem;
  letter-spacing: .02em;
  text-transform: uppercase;
}

.glosario-nombre {
  margin-left: .5rem;
  font-family: var(--ui);
  font-size: .72rem;
  font-weight: 400;
  letter-spacing: normal;
  text-transform: none;
  color: var(--suave);
}

.glosario-fila dd { margin: 0; font-size: .92rem; color: var(--texto); line-height: 1.55; }

.glosario-tema {
  display: inline-flex;
  align-items: center;
  gap: .3rem;
  margin-left: .4rem;
  font-family: var(--ui);
  font-size: .72rem;
  font-weight: 700;
  letter-spacing: .04em;
  text-transform: uppercase;
  color: var(--suave);
  text-decoration: none;
  white-space: nowrap;
}
.glosario-tema:hover { color: var(--acento); }

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

.alta-temas { margin: 1rem 0; max-width: 30rem; }
.alta-temas summary { cursor: pointer; font-size: .74rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--papel); }
.alta-temas .letra-pequena { margin: .6rem 0; }
.alta-temas-lista { display: flex; flex-wrap: wrap; gap: .4rem .9rem; margin-top: .3rem; }
/* Selector mas especifico que ".alta-formulario label" a proposito: sin
   esto, ese estilo -pensado para la etiqueta del campo de correo- ganaria
   el empate y dejaria estas etiquetas en mayusculas y en bloque. */
.alta-formulario .alta-tema {
  display: flex;
  align-items: center;
  gap: .4rem;
  margin-bottom: 0;
  font-size: .82rem;
  font-weight: 400;
  text-transform: none;
  letter-spacing: normal;
  color: #b9b4ac;
}
.alta-tema input { margin: 0; }

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

@media (min-width: 52rem) {
  .cabecera-marca {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(16rem, 21rem);
    align-items: end;
    gap: 0 2.5rem;
  }

  /* El nombre baja de tamaño al compartir fila, y no por gusto: va en una sola
     linea, asi que si no cabe no parte, se sale. El tope se calcula contra el
     hueco que le queda, no contra la ventana, porque la letra condensada no
     mide lo mismo en todos los sistemas y aqui no hay fuente incrustada que
     garantice el ancho. */
  .logo-bloque { font-size: clamp(2.2rem, 7.4vw, 5rem); }

  .panel { margin-bottom: 1.1rem; }
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

  /* Un dia con una sola noticia no tiene con que llenar la tercera columna:
     dejarle su hueco reservado deja un bloque en blanco al lado, como una
     pagina de periodico con un agujero. Si es la unica del dia, que ocupe
     la fila entera. */
  .bit-lead:only-child { grid-column: 1 / -1; }

  .rejilla-fichas { grid-template-columns: repeat(auto-fill, minmax(13rem, 1fr)); }
  .logo-bloque { letter-spacing: -.025em; }
}

/* Modo oscuro: invertir papel y tinta, no reescribir la hoja. Las variables
   ya estaban todas en :root para esto -.bit-multifuente y .alta ya pintaban
   "al reves" con var(--tinta)/var(--papel), así que en modo oscuro siguen
   siendo lo contrario del fondo de la página, solo que ahora ese fondo es
   oscuro-. Lo unico que no se resuelve solo redefiniendo son los colores
   fijos que no vivian en una variable: el barrido del sello y el interior
   de la caja de alta, pensados a mano para un fondo negro concreto. */
@media (prefers-color-scheme: dark) {
  :root {
    color-scheme: dark;

    --papel:   #16140f;
    --tarjeta: #1c1911;
    --tinta:   #f0ede6;
    --texto:   #d8d4c9;
    --apagado: #a39d8f;
    --suave:   #8b8477;
    --filete:  #f0ede6;
    --filete-fino: #3a352c;
    --realce:  #2a251c;

    /* Mas claro que en papel -el mismo rojo sobre un fondo oscuro pierde
       intensidad, y este acento marca la ultima hora y lo que esta activo,
       no puede quedarse apagado-, pero sin pasarse: tambien es el fondo del
       boton de alta, con texto blanco encima, y un rojo muy claro deja ese
       texto casi sin contraste. */
    --acento: #e8462e;
  }

  /* El barrido del sello es un tramo del circulo un poco mas claro que el
     resto -mas oscuro que claro, dice el comentario de mas abajo-, para dar
     sensacion de brillo girando. En papel, tinta es casi negro y el tramo es
     un gris muy oscuro; aqui tinta es casi blanco, y el tramo tiene que ser
     un poco mas apagado que blanco, no mas oscuro que negro. */
  .sello-marca {
    background: conic-gradient(from var(--angulo-sello),
                var(--tinta) 0deg, var(--tinta) 300deg, #d6d2c8 330deg, var(--tinta) 360deg);
  }

  /* La caja de alta sigue pintandose "al reves" del fondo -ahora clara sobre
     una pagina oscura-, pero estos colores estaban fijados a mano para una
     caja oscura -campos casi negros, texto gris claro- y no siguen a la
     variable. Se recalculan para la caja clara que toca en este modo. */
  .alta p,
  .alta-formulario label { color: #4a4438; }
  .alta .letra-pequena { color: #57503f; }
  .alta-fila input {
    border-color: #c9c4b8;
    background: #e8e4da;
  }
  .alta-temas summary,
  .alta-formulario .alta-tema { color: #4a4438; }
}

/* --- Impresion --------------------------------------------------------------
   Un directivo imprime o exporta a PDF el resumen del dia para una reunion.
   No es la pagina con estilo distinto: es la misma pagina sin lo que en papel
   no sirve de nada -menu, buscador, botones de compartir y votar, el bloque
   de alta- y sin lo que en papel solo gasta tinta -fondos oscuros, pastillas
   de color solido-. El contenido -titulares, cuerpo, "por que importa",
   quien lo cuenta- se queda igual. */
@media print {
  .saltar,
  .cabecera-barra,
  .cabecera-pie,
  .panel,
  .pie,
  .alta,
  .masleido,
  .explorar,
  .pie-acciones,
  .trampa {
    display: none !important;
  }

  body {
    background: #fff;
    color: #000;
  }

  a, a:visited { color: #000; text-decoration: underline; }

  /* Los mismos filetes negros de siempre, no la sombra ni el fondo con
     tinte que llevan en pantalla: en papel no aportan nada y gastan tinta
     por gastarla. */
  .etiqueta-categoria,
  .etiqueta-analisis {
    background: none;
    color: #000;
    border: 1px solid #000;
  }

  /* Lo que en pantalla sale en negativo -fondo negro, letra blanca- para
     destacar, en papel es la tinta entera de la pagina: mismo contenido,
     un marco en vez de un fondo solido. */
  .bit-multifuente {
    background: none;
    color: #000;
    border: 2px solid #000;
  }
  .bit-multifuente .por-que,
  .bit-multifuente .etiqueta-categoria,
  .bit-multifuente .etiqueta-analisis {
    background: none;
    color: #000;
  }

  /* Un <details> cerrado no imprime su contenido en la mayoria de
     navegadores, y "que otros medios cuentan la misma noticia" es
     precisamente el tipo de dato que vale la pena llevarse en papel. */
  .fuentes-bit:not([open]) > :not(summary) {
    display: block !important;
  }
  .fuentes-bit summary::-webkit-details-marker { display: none; }

  /* Que un bit no se parta entre dos paginas por el medio de un parrafo. */
  .bit, .cuadro-tarjeta, .fuentes-bit {
    break-inside: avoid;
  }
}
