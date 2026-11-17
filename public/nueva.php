<?php
declare(strict_types=1);

/**
 * Formulario para avisar de una incidencia. Valida cada campo con listas blancas, muestra los errores
 * junto al campo, conserva lo escrito y, si todo es correcto, guarda y redirige (PRG).
 */
require_once __DIR__ . '/../includes/funciones.php';
$usuario = requerir_login();

$equipos = db()->query('SELECT id, codigo, descripcion FROM equipos ORDER BY codigo')->fetchAll();
$errores = [];
$v = ['equipo_id' => '', 'categoria' => '', 'prioridad' => 'media', 'descripcion' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    foreach ($v as $campo => $_) {
        $v[$campo] = leer_texto($campo);
    }

    // Lista blanca: el id debe ser uno de los equipos que existen realmente.
    if (!in_array((int) $v['equipo_id'], array_map('intval', array_column($equipos, 'id')), true)) {
        $errores['equipo_id'] = 'Elige un equipo de la lista.';
    }
    if (!in_array($v['categoria'], config('categorias'), true)) {
        $errores['categoria'] = 'Elige una categoría válida.';
    }
    if (!in_array($v['prioridad'], config('prioridades'), true)) {
        $errores['prioridad'] = 'Elige una prioridad válida.';
    }
    if ($m = validar_longitud($v['descripcion'], 10, 500, 'La descripción')) {
        $errores['descripcion'] = $m;
    }

    if (!$errores) {
        $st = db()->prepare(
            'INSERT INTO incidencias (equipo_id, usuario_id, categoria, prioridad, descripcion) VALUES (?, ?, ?, ?, ?)'
        );
        $st->execute([(int) $v['equipo_id'], $usuario['id'], $v['categoria'], $v['prioridad'], $v['descripcion']]);
        flash('Incidencia registrada. ¡Gracias por avisar!');
        redirigir('incidencias.php'); // PRG: recargar con F5 no reenvía el formulario.
    }
}

/**
 * Pinta el mensaje de error de un campo, si lo tiene.
 *
 * @param array<string,string> $errores Errores por campo.
 * @param string $campo Campo a consultar.
 * @return string HTML del mensaje o cadena vacía.
 */
function error_campo(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? '<p class="error">' . e($errores[$campo]) . '</p>' : '';
}

$titulo = 'Nueva incidencia';
$modulos = ['nueva.js'];
include __DIR__ . '/../includes/cabecera.php';
?>
<h1>Nueva incidencia</h1>
<form method="post" novalidate id="form-nueva">
    <?= csrf_campo() ?>
    <div class="campo">
        <label for="equipo_id">Equipo</label>
        <select id="equipo_id" name="equipo_id" required>
            <option value="">— Elige —</option>
            <?php foreach ($equipos as $eq) : ?>
                <option value="<?= e($eq['id']) ?>" <?= (string) $eq['id'] === $v['equipo_id'] ? 'selected' : '' ?>>
                    <?= e($eq['codigo']) ?> · <?= e($eq['descripcion']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?= error_campo($errores, 'equipo_id') ?>
    </div>
    <div class="campo">
        <label for="categoria">Categoría</label>
        <select id="categoria" name="categoria" required>
            <option value="">— Elige —</option>
            <?php foreach (config('categorias') as $c) : ?>
                <option value="<?= e($c) ?>" <?= $c === $v['categoria'] ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
        </select>
        <?= error_campo($errores, 'categoria') ?>
    </div>
    <fieldset class="campo">
        <legend>Prioridad</legend>
        <?php foreach (config('prioridades') as $p) : ?>
            <label class="en-linea"><input type="radio" name="prioridad" value="<?= e($p) ?>" <?= $p === $v['prioridad'] ? 'checked' : '' ?>> <?= e($p) ?></label>
        <?php endforeach; ?>
        <?= error_campo($errores, 'prioridad') ?>
    </fieldset>
    <div class="campo">
        <label for="descripcion">Descripción (10 a 500 caracteres)</label>
        <textarea id="descripcion" name="descripcion" rows="5" maxlength="500" required><?= e($v['descripcion']) ?></textarea>
        <output id="contador-descripcion" for="descripcion"></output>
        <?= error_campo($errores, 'descripcion') ?>
    </div>
    <button type="submit">Enviar aviso</button>
</form>
<?php include __DIR__ . '/../includes/pie.php'; ?>
