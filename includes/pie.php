<?php
declare(strict_types=1);

/**
 * Pie común. Si la página define $modulos (lista de ficheros JS), los carga como módulos ES.
 */
?>
</main>
<footer class="pie">AulaFix · Proyecto intermodular 0612/0613</footer>
<?php foreach ($modulos ?? [] as $m) : ?>
<script type="module" src="js/<?= e($m) ?>"></script>
<?php endforeach; ?>
</body>
</html>
