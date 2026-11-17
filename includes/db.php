<?php
declare(strict_types=1);

/**
 * Conexión a MySQL con PDO. Un único punto de acceso para toda la aplicación.
 */

/**
 * Devuelve la conexión PDO (se crea la primera vez y se reutiliza).
 *
 * @return PDO Conexión con excepciones activadas y sentencias preparadas reales.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = (require __DIR__ . '/../config.php')['db'];
        $pdo = new PDO(
            "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4",
            $c['user'],
            $c['pass'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // false = el servidor MySQL prepara la consulta: los datos nunca se mezclan con el SQL.
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        // MySQL y PHP deben usar la misma zona horaria, o las fechas saldrían desplazadas.
        $pdo->exec("SET time_zone = '" . (new DateTime())->format('P') . "'");
    }
    return $pdo;
}
