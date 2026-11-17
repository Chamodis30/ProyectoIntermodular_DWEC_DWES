<?php
declare(strict_types=1);

/** Cierre de sesión: solo por POST con token CSRF (así otra web no puede cerrarte la sesión con un enlace). */
require_once __DIR__ . '/../includes/funciones.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('incidencias.php');
}
csrf_validar();

// Cierre completo: vaciar datos, borrar la cookie de sesión y destruir la sesión en el servidor.
$_SESSION = [];
$p = session_get_cookie_params();
setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'httponly' => true, 'samesite' => 'Lax']);
session_destroy();

// Se arranca una sesión nueva solo para poder dejar el mensaje de despedida.
iniciar_sesion();
flash('Has cerrado la sesión.');
redirigir('login.php');
