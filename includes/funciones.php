<?php
declare(strict_types=1);

/**
 * Funciones comunes: escape de salida, sesión, CSRF, permisos, mensajes flash y lectura de campos.
 * Se incluye al principio de cada página y arranca la sesión.
 */

require_once __DIR__ . '/db.php';

date_default_timezone_set('Europe/Madrid');
// Los errores van al log, nunca a la pantalla (podrían revelar rutas o consultas).
ini_set('display_errors', '0');

/**
 * Escapa un texto antes de escribirlo en el HTML.
 * Evita que un usuario meta etiquetas o scripts en la página (XSS).
 * Se escapa al SALIR y no al guardar: el dato se guarda tal cual y cada destino (HTML, JSON…) lo protege a su manera.
 *
 * @param mixed $valor Texto que se va a mostrar.
 * @return string Texto seguro para HTML.
 */
function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/**
 * Lee un valor de la configuración (config.php).
 *
 * @param string $clave Clave de primer nivel, p. ej. 'estados'.
 * @return mixed Valor de la configuración.
 */
function config(string $clave): mixed
{
    static $c = null;
    $c ??= require __DIR__ . '/../config.php';
    return $c[$clave];
}

/**
 * Arranca la sesión con una cookie protegida.
 * HttpOnly: JavaScript no puede leerla (limita el robo por XSS). SameSite=Lax: no viaja en peticiones
 * enviadas desde otras webs (limita el CSRF). Secure: solo por HTTPS cuando el sitio lo usa.
 */
function iniciar_sesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

/**
 * Devuelve el token CSRF de la sesión (lo crea si no existe).
 *
 * @return string Token aleatorio de 64 caracteres hexadecimales.
 */
function csrf_token(): string
{
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

/**
 * Genera el campo oculto con el token CSRF para un formulario POST.
 *
 * @return string HTML del campo oculto.
 */
function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/**
 * Comprueba el token CSRF del POST actual; si no coincide responde 403 y termina.
 * hash_equals compara en tiempo constante para no filtrar información.
 */
function csrf_validar(): void
{
    $enviado = (string) ($_POST['csrf'] ?? '');
    if (!hash_equals(csrf_token(), $enviado)) {
        prohibido('Formulario no válido o caducado. Vuelve atrás y reinténtalo.');
    }
}

/**
 * Datos del usuario con sesión iniciada.
 *
 * @return array{id:int,nombre:string,rol:string}|null null si no hay sesión.
 */
function usuario_actual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

/**
 * Indica si el usuario actual es profesor.
 *
 * @return bool true si es profesor.
 */
function es_profesor(): bool
{
    return (usuario_actual()['rol'] ?? '') === 'profesor';
}

/**
 * Obliga a tener sesión: si no la hay, redirige al inicio de sesión.
 *
 * @return array{id:int,nombre:string,rol:string} Usuario actual.
 */
function requerir_login(): array
{
    $u = usuario_actual();
    if ($u === null) {
        redirigir('login.php');
    }
    return $u;
}

/**
 * Redirige y termina (parte final del patrón Post-Redirect-Get).
 *
 * @param string $url Destino relativo.
 */
function redirigir(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Guarda un mensaje para mostrarlo una sola vez en la siguiente página, o lo recoge si no se pasa nada.
 *
 * @param string|null $mensaje Mensaje a guardar, o null para leerlo (y borrarlo).
 * @return string|null Mensaje pendiente, si lo había.
 */
function flash(?string $mensaje = null): ?string
{
    if ($mensaje !== null) {
        $_SESSION['flash'] = $mensaje;
        return null;
    }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

/**
 * Responde 403 con un mensaje y termina. Los permisos se comprueban SIEMPRE en el servidor.
 *
 * @param string $mensaje Texto que se muestra.
 */
function prohibido(string $mensaje = 'No tienes permiso para hacer esto.'): never
{
    http_response_code(403);
    $titulo = 'Acceso prohibido';
    include __DIR__ . '/cabecera.php';
    echo '<h1>403 · Acceso prohibido</h1><p>' . e($mensaje) . '</p><p><a href="incidencias.php">Volver al listado</a></p>';
    include __DIR__ . '/pie.php';
    exit;
}

/**
 * Responde 404 y termina.
 */
function no_encontrada(): never
{
    http_response_code(404);
    $titulo = 'No encontrada';
    include __DIR__ . '/cabecera.php';
    echo '<h1>404 · No encontrada</h1><p><a href="incidencias.php">Volver al listado</a></p>';
    include __DIR__ . '/pie.php';
    exit;
}

/**
 * Lee un campo de texto del POST, sin espacios sobrantes.
 *
 * @param string $campo Nombre del campo.
 * @return string Valor limpio ('' si no llegó).
 */
function leer_texto(string $campo): string
{
    return trim((string) ($_POST[$campo] ?? ''));
}

/**
 * Valida la longitud de un texto (en caracteres, no bytes, por las tildes).
 *
 * @param string $valor Texto a comprobar.
 * @param int $min Longitud mínima.
 * @param int $max Longitud máxima.
 * @param string $nombre Nombre del campo para el mensaje.
 * @return string|null Mensaje de error, o null si es válido.
 */
function validar_longitud(string $valor, int $min, int $max, string $nombre): ?string
{
    $n = mb_strlen($valor);
    return ($n < $min || $n > $max) ? "{$nombre} debe tener entre {$min} y {$max} caracteres." : null;
}

/**
 * Convierte datos a JSON seguro para incrustarlo en un <script>.
 * Las opciones JSON_HEX_* sustituyen < > & ' " por secuencias \uXXXX, así un texto como "</script>"
 * no puede cerrar la etiqueta ni inyectar HTML.
 *
 * @param mixed $datos Datos a codificar.
 * @return string JSON seguro.
 */
function json_seguro(mixed $datos): string
{
    return json_encode(
        $datos,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );
}

iniciar_sesion();
