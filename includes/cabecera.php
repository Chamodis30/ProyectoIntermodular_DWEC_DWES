<?php
declare(strict_types=1);

/**
 * Cabecera común: <head>, menú y mensaje flash. La página define antes $titulo.
 */
$usuario = usuario_actual();
$mensaje = flash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'AulaFix') ?> · AulaFix</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<header class="barra">
    <a class="logo" href="incidencias.php">AulaFix</a>
    <nav>
        <?php if ($usuario !== null) : ?>
            <a href="incidencias.php">Incidencias</a>
            <a href="nueva.php">Nueva</a>
            <span><?= e($usuario['nombre']) ?> (<?= e($usuario['rol']) ?>)</span>
            <form method="post" action="logout.php" class="en-linea">
                <?= csrf_campo() ?>
                <button type="submit">Salir</button>
            </form>
        <?php else : ?>
            <a href="login.php">Entrar</a>
            <a href="registro.php">Registrarse</a>
        <?php endif; ?>
    </nav>
</header>
<main>
<?php if ($mensaje !== null) : ?>
    <p class="aviso" role="status"><?= e($mensaje) ?></p>
<?php endif; ?>
