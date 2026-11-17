<?php
declare(strict_types=1);

/**
 * Listado de incidencias. PHP genera la tabla HTML (funciona sin JavaScript) y además incrusta los
 * mismos datos en JSON para que el panel interactivo (js/main.js) los lea con JSON.parse().
 */
require_once __DIR__ . '/../includes/funciones.php';
requerir_login();

$filas = db()->query(
    'SELECT i.id, e.codigo AS equipo, i.categoria, i.prioridad, i.descripcion, i.estado, u.nombre AS autor, i.creada_en
       FROM incidencias i
       JOIN equipos e ON e.id = i.equipo_id
       JOIN usuarios u ON u.id = i.usuario_id
      ORDER BY i.creada_en DESC'
)->fetchAll();

// «Contrato» con el cliente: mismos campos y formato de fecha ISO 8601 (con zona horaria).
$lista = array_map(static fn (array $f): array => [
    'id'          => (int) $f['id'],
    'equipo'      => $f['equipo'],
    'categoria'   => $f['categoria'],
    'prioridad'   => $f['prioridad'],
    'descripcion' => $f['descripcion'],
    'estado'      => $f['estado'],
    'autor'       => $f['autor'],
    'creada_en'   => (new DateTimeImmutable($f['creada_en']))->format('c'),
], $filas);

$titulo = 'Incidencias';
$modulos = ['main.js'];
include __DIR__ . '/../includes/cabecera.php';
?>
<h1>Incidencias del aula</h1>
<p><a class="boton" href="nueva.php">Avisar de una incidencia</a></p>

<div id="zona-panel">
<table id="tabla-php">
    <caption>Listado de incidencias</caption>
    <thead><tr><th>Fecha</th><th>Equipo</th><th>Prioridad</th><th>Estado</th><th>Descripción</th><th>Autor</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($lista as $i) : ?>
        <tr class="<?= $i['prioridad'] === 'alta' && $i['estado'] !== 'resuelta' ? 'urgente' : '' ?>">
            <td><?= e((new DateTimeImmutable($i['creada_en']))->format('d/m/Y H:i')) ?></td>
            <td><?= e($i['equipo']) ?></td>
            <td><?= e($i['prioridad']) ?></td>
            <td><?= e(str_replace('_', ' ', $i['estado'])) ?></td>
            <td><?= e($i['descripcion']) ?></td>
            <td><?= e($i['autor']) ?></td>
            <td><a href="incidencia.php?id=<?= e($i['id']) ?>">Ver</a></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$lista) : ?><tr><td colspan="7">No hay incidencias.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<!-- El navegador no ejecuta este bloque (type="application/json"): es solo un contenedor de datos. -->
<script id="datos-incidencias" type="application/json"><?= json_seguro($lista) ?></script>
<?php include __DIR__ . '/../includes/pie.php'; ?>
