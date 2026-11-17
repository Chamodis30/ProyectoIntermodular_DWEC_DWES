<?php
declare(strict_types=1);

/**
 * Configuración única de AulaFix: conexión a MySQL y valores permitidos.
 * Los datos de conexión pueden venir de variables de entorno (Docker, CI)
 * para no subir contraseñas al repositorio.
 */
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'aulafix',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
    ],
    // Listas blancas: se usan en la validación del servidor (y coinciden con los ENUM de MySQL).
    'categorias'  => ['hardware', 'software', 'red', 'otro'],
    'prioridades' => ['baja', 'media', 'alta'],
    'estados'     => ['abierta', 'en_curso', 'resuelta'],
];
