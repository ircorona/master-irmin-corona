<?php
require __DIR__ . '/validar.php';

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$enviado = $_SERVER['REQUEST_METHOD'] === 'POST';
$errores = $enviado ? validar_registro($_POST) : [];
$creada  = $enviado && !$errores;

// Al fallar se conserva lo escrito, salvo las contraseñas (heurística 3: no empezar de cero).
$valor = fn(string $campo): string => e(trim($_POST[$campo] ?? ''));

$etiquetas = [
    'nombre' => 'Nombre',
    'email'  => 'Correo electrónico',
    'pass'   => 'Contraseña',
    'pass2'  => 'Confirma tu contraseña',
];

// Atributos de cada campo: invalid + mensaje asociado para lectores de pantalla.
function estado(string $campo, array $errores): string
{
    return isset($errores[$campo])
        ? ' aria-invalid="true" aria-describedby="error-' . $campo . '"'
        : '';
}

function error(string $campo, array $errores): string
{
    return isset($errores[$campo])
        ? '<p class="error" id="error-' . $campo . '">' . e($errores[$campo]) . '</p>'
        : '';
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Crear cuenta · heurísticas de Nielsen</title>
<style>
  body { font: 16px/1.5 system-ui, sans-serif; max-width: 26rem; margin: 2rem auto; padding: 0 1rem; color: #111; }
  label { display: block; font-weight: 600; margin-top: 1rem; }
  input { width: 100%; box-sizing: border-box; padding: .5rem; font: inherit; border: 1px solid #888; border-radius: 4px; }
  input[aria-invalid="true"] { border: 2px solid #b00020; }
  .error { color: #b00020; margin: .25rem 0 0; }
  .ayuda { color: #555; font-size: .9rem; margin: .25rem 0 0; }
  .resumen { border: 2px solid #b00020; padding: .75rem 1rem; border-radius: 4px; }
  .ok { border: 2px solid #1b7f3b; padding: .75rem 1rem; border-radius: 4px; }
  button { margin-top: 1.5rem; padding: .6rem 1.2rem; font: inherit; font-weight: 600; background: #f7ca00; border: 1px solid #a88a00; border-radius: 6px; cursor: pointer; }
</style>
</head>
<body>
<h1>Crear cuenta</h1>

<?php if ($creada): ?>
  <div class="ok" role="status">
    <p><strong>Cuenta creada.</strong> Hemos enviado un enlace de verificación a <?= $valor('email') ?>.</p>
    <p><a href="./">Crear otra cuenta</a></p>
  </div>
<?php else: ?>

  <?php if ($errores): ?>
    <div class="resumen" role="alert">
      <p><strong>Revisa <?= count($errores) === 1 ? 'un campo' : count($errores) . ' campos' ?>:</strong></p>
      <ul>
        <?php foreach ($errores as $campo => $msg): ?>
          <li><a href="#<?= $campo ?>"><?= e($etiquetas[$campo]) ?></a>: <?= e($msg) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" action="" novalidate>
    <label for="nombre">Nombre</label>
    <input id="nombre" name="nombre" autocomplete="name" value="<?= $valor('nombre') ?>"<?= estado('nombre', $errores) ?>>
    <?= error('nombre', $errores) ?>

    <label for="email">Correo electrónico</label>
    <input id="email" name="email" type="email" inputmode="email" autocomplete="email" value="<?= $valor('email') ?>"<?= estado('email', $errores) ?>>
    <?= error('email', $errores) ?>

    <label for="pass">Contraseña</label>
    <input id="pass" name="pass" type="password" autocomplete="new-password"<?= estado('pass', $errores) ?>>
    <?= error('pass', $errores) ?: '<p class="ayuda">Mínimo 8 caracteres.</p>' ?>

    <label for="pass2">Confirma tu contraseña</label>
    <input id="pass2" name="pass2" type="password" autocomplete="new-password"<?= estado('pass2', $errores) ?>>
    <?= error('pass2', $errores) ?>

    <button type="submit">Crear cuenta</button>
  </form>

<?php endif; ?>
</body>
</html>
