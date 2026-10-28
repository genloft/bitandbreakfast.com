<?php
/**
 * Cargo y empresa, opcionales, para las cajas de alta. Recibe $alta_id, el
 * prefijo de los id del campo -dos cajas en la misma pagina no pueden repetir
 * id-.
 *
 * Va plegado y sin ningun campo obligatorio: el alta es el correo y nada mas,
 * y esto es para quien quiera decir quien es. Sirve a la red -saber a quien se
 * escribe y poder agrupar por tipo de puesto o de hotel el dia que haga
 * falta-, no al lector, y por eso no se insiste.
 *
 * Solo se enseña con el buzon propio, que es el que guarda estos campos. Con
 * un proveedor de correo externo se ignorarian, y pedir un dato que no se
 * guarda es peor que no pedirlo.
 */

declare(strict_types=1);

$alta_id = $alta_id ?? 'alta';

?>
<details class="alta-temas alta-extra">
  <summary>Cargo y empresa (opcional)</summary>
  <p class="letra-pequena">Para saber a quién escribimos. Puedes dejarlo en blanco.</p>

  <div class="alta-fila">
    <label for="<?= web_e($alta_id) ?>-cargo">Cargo</label>
    <input id="<?= web_e($alta_id) ?>-cargo" name="cargo" type="text" maxlength="80"
           autocomplete="organization-title" placeholder="Director de operaciones">

    <label for="<?= web_e($alta_id) ?>-empresa">Empresa</label>
    <input id="<?= web_e($alta_id) ?>-empresa" name="empresa" type="text" maxlength="100"
           autocomplete="organization" placeholder="Hotel, cadena o proveedor">
  </div>
</details>
