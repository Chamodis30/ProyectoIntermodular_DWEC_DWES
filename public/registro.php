<?php
declare(strict_types=1);

/**
 * Registro de usuarios. Valida en el servidor, guarda la clave con Argon2id y crea siempre rol "alumno"
 * (el rol profesor lo asigna el centro; así nadie puede auto-concederse permisos).
 */
require_once __DIR__ . '/../includes/funciones.php';

$errores = [];
$v = ['nombre' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $v['nombre'] = leer_texto('nombre');
    $v['email']  = mb_strtolower(leer_texto('email'));
    // Las contraseñas no se recortan ni se devuelven al formulario.
    $clave  = (string) ($_POST['clave'] ?? '');
    $clave2 = (string) ($_POST['clave2'] ?? '');

    if ($m = validar_longitud($v['nombre'], 2, 80, 'El nombre')) {
        $errores['nombre'] = $m;
    }
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($v['email']) > 190) {
        $errores['email'] = 'Escribe un correo válido.';
    } else {
        $st = db()->prepare('SELECT 1 FROM usuarios WHERE email = ?');
        $st->execute([$v['email']]);
        if ($st->fetchColumn()) {
            $errores['email'] = 'Ese correo ya está registrado.';
        }
    }
    if (mb_strlen($clave) < 10 || !preg_match('/[A-Za-z]/', $clave) || !preg_match('/\d/', $clave)) {
        $errores['clave'] = 'La contraseña necesita al menos 10 caracteres, con letras y números.';
    }
    if ($clave !== $clave2) {
        $errores['clave2'] = 'Las contraseñas no coinciden.';
    }

    if (!$errores) {
        try {
            $st = db()->prepare('INSERT INTO usuarios (nombre, email, clave_hash, rol) VALUES (?, ?, ?, ?)');
            $st->execute([$v['nombre'], $v['email'], password_hash($clave, PASSWORD_ARGON2ID), 'alumno']);
            flash('Cuenta creada. Ya puedes iniciar sesión.');
            redirigir('login.php');
        } catch (PDOException $ex) {
            // 23000 = violación de UNIQUE: dos registros simultáneos con el mismo correo.
            if ($ex->getCode() !== '23000') {
                throw $ex;
            }
            $errores['email'] = 'Ese correo ya está registrado.';
        }
    }
}

$titulo = 'Registro';
include __DIR__ . '/../includes/cabecera.php';
?>
<h1>Crear cuenta</h1>
<form method="post" novalidate>
    <?= csrf_campo() ?>
    <?php foreach ([['nombre', 'Nombre', 'text'], ['email', 'Correo', 'email'], ['clave', 'Contraseña', 'password'], ['clave2', 'Repite la contraseña', 'password']] as [$n, $et, $t]) : ?>
        <div class="campo">
            <label for="<?= e($n) ?>"><?= e($et) ?></label>
            <input id="<?= e($n) ?>" name="<?= e($n) ?>" type="<?= e($t) ?>" value="<?= e($v[$n] ?? '') ?>" required>
            <?php if (isset($errores[$n])) : ?><p class="error"><?= e($errores[$n]) ?></p><?php endif; ?>
        </div>
    <?php endforeach; ?>
    <button type="submit">Registrarme</button>
</form>
<?php include __DIR__ . '/../includes/pie.php'; ?>
