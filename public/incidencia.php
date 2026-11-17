<?php
declare(strict_types=1);

/**
 * Detalle de una incidencia con su historial. El profesorado cambia el estado (con comentario) y puede borrar;
 * el alumnado solo puede borrar sus propias incidencias mientras siguen abiertas.
 */
require_once __DIR__ . '/../includes/funciones.php';
$usuario = requerir_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    no_encontrada();
}

$st = db()->prepare(
    'SELECT i.*, e.codigo AS equipo, u.nombre AS autor
       FROM incidencias i JOIN equipos e ON e.id = i.equipo_id JOIN usuarios u ON u.id = i.usuario_id
      WHERE i.id = ?'
);
$st->execute([$id]);
$inc = $st->fetch();
if ($inc === false) {
    no_encontrada();
}

$errores = [];
$v = ['estado' => $inc['estado'], 'comentario' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = leer_texto('accion');

    if ($accion === 'borrar') {
        // Permiso comprobado en el servidor: profesor siempre; alumno solo lo suyo y si sigue abierta.
        $puede = es_profesor() || ((int) $inc['usuario_id'] === $usuario['id'] && $inc['estado'] === 'abierta');
        if (!$puede) {
            prohibido('No puedes borrar esta incidencia.');
        }
        // El historial se elimina solo por ON DELETE CASCADE.
        $st = db()->prepare('DELETE FROM incidencias WHERE id = ?');
        $st->execute([$id]);
        flash('Incidencia borrada.');
        redirigir('incidencias.php');
    }

    if ($accion === 'estado') {
        if (!es_profesor()) {
            prohibido('Solo el profesorado puede cambiar el estado.');
        }
        $v['estado']     = leer_texto('estado');
        $v['comentario'] = leer_texto('comentario');
        if (!in_array($v['estado'], config('estados'), true)) {
            $errores['estado'] = 'Estado no permitido.';
        }
        if ($m = validar_longitud($v['comentario'], 3, 300, 'El comentario')) {
            $errores['comentario'] = $m;
        }

        if (!$errores) {
            $pdo = db();
            // Transacción: actualizar la incidencia y anotar el historial son un único cambio.
            // Sin ella, un fallo entre las dos sentencias dejaría el estado cambiado sin rastro en el historial
            // (o un historial que habla de un cambio que no ocurrió).
            $pdo->beginTransaction();
            try {
                // FOR UPDATE bloquea la fila: dos profesores a la vez no pisan el «estado anterior» del otro.
                $st = $pdo->prepare('SELECT estado FROM incidencias WHERE id = ? FOR UPDATE');
                $st->execute([$id]);
                $anterior = (string) $st->fetchColumn();

                $pdo->prepare('UPDATE incidencias SET estado = ? WHERE id = ?')->execute([$v['estado'], $id]);
                $pdo->prepare(
                    'INSERT INTO historial (incidencia_id, usuario_id, estado_anterior, estado_nuevo, comentario) VALUES (?, ?, ?, ?, ?)'
                )->execute([$id, $usuario['id'], $anterior, $v['estado'], $v['comentario']]);
                $pdo->commit();
            } catch (Throwable $ex) {
                $pdo->rollBack();
                throw $ex;
            }
            flash('Estado actualizado.');
            redirigir('incidencia.php?id=' . $id);
        }
    }
}

$st = db()->prepare(
    'SELECT h.*, u.nombre AS autor FROM historial h JOIN usuarios u ON u.id = h.usuario_id
      WHERE h.incidencia_id = ? ORDER BY h.creado_en, h.id'
);
$st->execute([$id]);
$historial = $st->fetchAll();
$puedeBorrar = es_profesor() || ((int) $inc['usuario_id'] === $usuario['id'] && $inc['estado'] === 'abierta');

$titulo = 'Incidencia #' . $id;
include __DIR__ . '/../includes/cabecera.php';
?>
<h1>Incidencia #<?= e($id) ?> · <?= e($inc['equipo']) ?></h1>
<dl>
    <dt>Estado</dt><dd><?= e(str_replace('_', ' ', $inc['estado'])) ?></dd>
    <dt>Categoría</dt><dd><?= e($inc['categoria']) ?></dd>
    <dt>Prioridad</dt><dd><?= e($inc['prioridad']) ?></dd>
    <dt>Autor</dt><dd><?= e($inc['autor']) ?></dd>
    <dt>Creada</dt><dd><?= e((new DateTimeImmutable($inc['creada_en']))->format('d/m/Y H:i')) ?></dd>
    <dt>Descripción</dt><dd><?= e($inc['descripcion']) ?></dd>
</dl>

<h2>Historial</h2>
<?php if (!$historial) : ?><p>Sin cambios de estado todavía.</p><?php endif; ?>
<ul>
<?php foreach ($historial as $h) : ?>
    <li><?= e((new DateTimeImmutable($h['creado_en']))->format('d/m/Y H:i')) ?> · <?= e($h['autor']) ?>:
        <?= e($h['estado_anterior']) ?> → <?= e($h['estado_nuevo']) ?> — <?= e($h['comentario']) ?></li>
<?php endforeach; ?>
</ul>

<?php if (es_profesor()) : ?>
<h2>Cambiar estado</h2>
<form method="post" novalidate>
    <?= csrf_campo() ?>
    <input type="hidden" name="accion" value="estado">
    <div class="campo"><label for="estado">Nuevo estado</label>
        <select id="estado" name="estado">
            <?php foreach (config('estados') as $s) : ?>
                <option value="<?= e($s) ?>" <?= $s === $v['estado'] ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $s)) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errores['estado'])) : ?><p class="error"><?= e($errores['estado']) ?></p><?php endif; ?>
    </div>
    <div class="campo"><label for="comentario">Comentario</label>
        <textarea id="comentario" name="comentario" rows="3" maxlength="300" required><?= e($v['comentario']) ?></textarea>
        <?php if (isset($errores['comentario'])) : ?><p class="error"><?= e($errores['comentario']) ?></p><?php endif; ?>
    </div>
    <button type="submit">Guardar cambio</button>
</form>
<?php endif; ?>

<?php if ($puedeBorrar) : ?>
<form method="post" onsubmit="return confirm('¿Borrar esta incidencia?');">
    <?= csrf_campo() ?>
    <input type="hidden" name="accion" value="borrar">
    <button type="submit" class="peligro">Borrar incidencia</button>
</form>
<?php endif; ?>
<?php include __DIR__ . '/../includes/pie.php'; ?>
