<?php
declare(strict_types=1);

/**
 * Inicio de sesión. Comprueba la contraseña con password_verify(), regenera el id de sesión
 * y, si se pide, recuerda SOLO el correo en una cookie durante 30 días.
 */
require_once __DIR__ . '/../includes/funciones.php';

if (usuario_actual() !== null) {
    redirigir('incidencias.php');
}

$error = '';
// Se rellena el correo desde la cookie «recordar» (si existe y es un correo válido).
$email = (string) ($_COOKIE['recordar_email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $email = '';
}
$recordar = $email !== '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $email    = mb_strtolower(leer_texto('email'));
    $clave    = (string) ($_POST['clave'] ?? '');
    $recordar = isset($_POST['recordar']);

    $st = db()->prepare('SELECT id, nombre, clave_hash, rol FROM usuarios WHERE email = ?');
    $st->execute([$email]);
    $fila = $st->fetch();

    // Si el correo no existe se verifica contra un hash falso: tarda lo mismo y no revela si la cuenta existe.
    $hashFalso = '$argon2id$v=19$m=65536,t=4,p=1$ZHVtbXlzYWx0ZHVtbXk$0000000000000000000000000000000000000000000';
    $valido = password_verify($clave, $fila !== false ? $fila['clave_hash'] : $hashFalso) && $fila !== false;

    if ($valido) {
        // Regenerar el id evita la «fijación de sesión»: un id conocido antes de entrar deja de valer.
        session_regenerate_id(true);
        $_SESSION['usuario'] = ['id' => (int) $fila['id'], 'nombre' => $fila['nombre'], 'rol' => $fila['rol']];
        setcookie('recordar_email', $recordar ? $email : '', [
            'expires'  => $recordar ? time() + 30 * 86400 : time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        redirigir('incidencias.php');
    }
    // Mismo mensaje para correo inexistente y contraseña errónea.
    $error = 'Correo o contraseña incorrectos.';
}

$titulo = 'Entrar';
include __DIR__ . '/../includes/cabecera.php';
?>
<h1>Iniciar sesión</h1>
<?php if ($error !== '') : ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post">
    <?= csrf_campo() ?>
    <div class="campo"><label for="email">Correo</label>
        <input id="email" name="email" type="email" value="<?= e($email) ?>" required></div>
    <div class="campo"><label for="clave">Contraseña</label>
        <input id="clave" name="clave" type="password" required></div>
    <div class="campo"><label><input type="checkbox" name="recordar" <?= $recordar ? 'checked' : '' ?>> Recordar mi correo</label></div>
    <button type="submit">Entrar</button>
</form>
<?php include __DIR__ . '/../includes/pie.php'; ?>
