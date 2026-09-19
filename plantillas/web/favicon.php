<?php
/**
 * El favicon: el mismo sello que la cabecera, en un fichero aparte.
 *
 * Un circulo negro con una "B", igual que .sello-marca en la cabecera -sin
 * dibujo, porque el sitio no tiene ninguna imagen y una taza de cafe con
 * vapor de wifi ya se probo y se quito de ahi por la misma razon: una letra
 * dentro de un circulo no ilustra nada, y por eso funciona.
 *
 * SVG y no PNG/ICO: es una sola forma geometrica y un caracter, no hace
 * falta rasterizar nada, y evita generar un binario a mano cada vez que
 * cambiara. Todos los navegadores con soporte de pestañas SVG lo escalan
 * solos a cualquier tamaño de pestaña o de marcador.
 */

declare(strict_types=1);

?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
  <circle cx="16" cy="16" r="16" fill="#0d0d0d"/>
  <text x="16" y="22" text-anchor="middle" font-family="'Arial Narrow', 'Helvetica Neue', Helvetica, Arial, sans-serif"
        font-size="18" font-weight="700" fill="#f4f2ee">B</text>
</svg>
