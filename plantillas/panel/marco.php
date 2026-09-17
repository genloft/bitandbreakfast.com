<?php
/**
 * Marco comun del panel: cabecera, navegacion, avisos y pie.
 *
 * Lo incluye panel/index.php con $vista ya decidida, y el marco incluye a su
 * vez la vista. Asi la navegacion y los avisos se escriben una vez.
 *
 * Ni estilo ni script en linea: la politica de seguridad del sitio es 'self'
 * y no se le hace excepcion al panel. El estilo esta en panel/estilo.css.
 */

declare(strict_types=1);

// $vista la decide el controlador y nunca viene de la peticion, pero de aqui
// sale un require: una lista blanca cuesta tres lineas y cierra la puerta a
// que un descuido futuro convierta esto en una inclusion de ficheros.
$vista = in_array($vista ?? '', ['entrar', 'cola', 'bit', 'edicion', 'correo'], true) ? $vista : 'cola';

$usuario = $usuario ?? panel_usuario();
$avisos  = panel_avisos();

foreach ($errores ?? [] as $texto) {
    $avisos[] = ['texto' => $texto, 'tipo' => 'error'];
}

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Panel · Bit &amp; Breakfast</title>
<link rel="stylesheet" href="estilo.css">
</head>
<body>

<?php if ($usuario !== null): ?>
  <header class="barra">
    <strong class="marca">Bit &amp; Breakfast</strong>
    <nav>
      <a href="index.php?p=cola"<?= $vista === 'cola' ? ' class="activo"' : '' ?>>Cola</a>
      <a href="index.php?p=edicion"<?= $vista === 'edicion' ? ' class="activo"' : '' ?>>Edición</a>
      <a href="index.php?p=correo"<?= $vista === 'correo' ? ' class="activo"' : '' ?>>Correo</a>
    </nav>
    <span class="quien"><?= panel_e($usuario['usuario']) ?> · <a href="index.php?p=salir">salir</a></span>
  </header>
<?php endif; ?>

<main class="<?= $vista === 'entrar' ? 'estrecho' : '' ?>">

<?php foreach ($avisos as $aviso): ?>
  <p class="aviso <?= panel_e($aviso['tipo']) ?>"><?= panel_e($aviso['texto']) ?></p>
<?php endforeach; ?>

<?php require __DIR__ . '/' . $vista . '.php'; ?>

</main>
</body>
</html>
