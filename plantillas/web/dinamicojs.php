<?php
/**
 * Script para busqueda dinamica en cabecera.
 */
declare(strict_types=1);
?>
(function() {
    const input = document.getElementById('q-dinamico');
    const caja = document.getElementById('resultados-dinamicos');
    if (!input || !caja) return;

    let indice = null;
    let cargando = false;

    function limpiar(s) {
        return s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    }

    input.addEventListener('input', function() {
        const query = limpiar(input.value.trim());
        if (query.length < 3) {
            caja.style.display = 'none';
            return;
        }

        if (!indice) {
            if (!cargando) {
                cargando = true;
                const pathParts = window.location.pathname.split('/');
                pathParts.pop();
                const basePath = pathParts.join('/') + '/';
                fetch(basePath + 'indice.json')
                    .then(res => res.json())
                    .then(data => {
                        indice = data;
                        mostrarResultados(query);
                    })
                    .catch(e => console.error('Error buscando', e));
            }
            return;
        }
        mostrarResultados(query);
    });

    function mostrarResultados(query) {
        const terms = query.split(' ').filter(t => t.length > 0);
        let coincidencias = [];

        for (let i = 0; i < indice.length; i++) {
            const bit = indice[i];
            const texto = limpiar(bit.t + ' ' + (bit.q || '') + ' ' + (bit.c || '') + ' ' + (bit.m || ''));
            let coincide = true;
            for (const term of terms) {
                if (!texto.includes(term)) {
                    coincide = false;
                    break;
                }
            }
            if (coincide) coincidencias.push(bit);
            if (coincidencias.length >= 8) break;
        }

        if (coincidencias.length === 0) {
            caja.innerHTML = '<p style="color:var(--tinta);margin:0;font-size:0.9rem;">Sin resultados.</p>';
        } else {
            caja.innerHTML = coincidencias.map(b => 
                `<a href="bit.html?id=${b.id}" style="display:block; padding: 0.5rem; border-bottom:1px solid var(--filete); color:var(--tinta); text-decoration:none; font-size:0.9rem;">
                   <strong>${b.t}</strong>
                 </a>`
            ).join('');
        }
        caja.style.display = 'block';
    }

    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !caja.contains(e.target)) {
            caja.style.display = 'none';
        }
    });
})();
