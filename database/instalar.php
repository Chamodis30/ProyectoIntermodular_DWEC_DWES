<?php
declare(strict_types=1);

/**
 * Instalador de AulaFix (uso: php database/instalar.php).
 * Crea la base de datos, ejecuta esquema.sql y carga usuarios, equipos e incidencias de ejemplo.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo se ejecuta desde la línea de comandos.');
}
date_default_timezone_set('Europe/Madrid');
$cfg = (require __DIR__ . '/../config.php')['db'];

// Se conecta sin elegir base de datos porque puede que todavía no exista.
// Las consultas emuladas permiten ejecutar el fichero SQL (varias sentencias) de una vez.
$pdo = new PDO(
    "mysql:host={$cfg['host']};port={$cfg['port']};charset=utf8mb4",
    $cfg['user'],
    $cfg['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => true]
);
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `{$cfg['name']}`");
$pdo->exec("SET time_zone = '" . (new DateTime())->format('P') . "'");
$pdo->exec((string) file_get_contents(__DIR__ . '/esquema.sql'));
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

/** Argon2id: algoritmo recomendado actualmente para guardar contraseñas. */
$hash = fn (string $clave): string => password_hash($clave, PASSWORD_ARGON2ID);

$st = $pdo->prepare('INSERT INTO usuarios (nombre, email, clave_hash, rol) VALUES (?, ?, ?, ?)');
$st->execute(['Paula Profesora', 'profesor@aulafix.test', $hash('Profesor2026'), 'profesor']);
$st->execute(['Lucía Alumna', 'alumno@aulafix.test', $hash('Alumno20261'), 'alumno']);
$st->execute(['Marcos Alumno', 'marcos@aulafix.test', $hash('Alumno20262'), 'alumno']);

$st = $pdo->prepare('INSERT INTO equipos (codigo, descripcion) VALUES (?, ?)');
foreach ([['PC-01', 'Ordenador del puesto 1'], ['PC-02', 'Ordenador del puesto 2'], ['PC-03', 'Ordenador del puesto 3'],
          ['PROY-01', 'Proyector del aula'], ['IMP-01', 'Impresora láser'], ['RED-01', 'Router del aula']] as $e) {
    $st->execute($e);
}

// [equipo, usuario, categoría, prioridad, descripción, estado, días de antigüedad]
$datos = [
    [4, 2, 'hardware', 'alta',  'El proyector se apaga solo a los diez minutos.', 'abierta', 1],
    [1, 3, 'hardware', 'media', 'El ordenador no arranca, la pantalla queda en negro.', 'abierta', 3],
    [5, 2, 'hardware', 'baja',  'La impresora se ha quedado sin tóner.', 'en_curso', 5],
    [6, 3, 'red',      'alta',  'No hay conexión a internet en las filas del fondo.', 'en_curso', 2],
    [2, 2, 'software', 'media', 'Falta instalar el editor de código en el puesto 2.', 'resuelta', 9],
    [3, 3, 'otro',     'baja',  'El ratón del puesto 3 va a saltos.', 'resuelta', 12],
];
$ins = $pdo->prepare('INSERT INTO incidencias (equipo_id, usuario_id, categoria, prioridad, descripcion, estado, creada_en)
                      VALUES (?, ?, ?, ?, ?, ?, ?)');
$his = $pdo->prepare('INSERT INTO historial (incidencia_id, usuario_id, estado_anterior, estado_nuevo, comentario) VALUES (?, 1, ?, ?, ?)');
foreach ($datos as [$eq, $us, $cat, $pri, $desc, $est, $dias]) {
    $ins->execute([$eq, $us, $cat, $pri, $desc, $est, date('Y-m-d H:i:s', strtotime("-{$dias} days"))]);
    $id = (int) $pdo->lastInsertId();
    if ($est !== 'abierta') {
        $his->execute([$id, 'abierta', 'en_curso', 'Lo reviso esta semana.']);
    }
    if ($est === 'resuelta') {
        $his->execute([$id, 'en_curso', 'resuelta', 'Solucionado.']);
    }
}
echo "Base de datos '{$cfg['name']}' instalada con datos de ejemplo.\n";
