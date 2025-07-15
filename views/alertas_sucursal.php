<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Alertas Sucursal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <?php
        global $MOTIVOS;
        $nombres = [
            1 => 'Matriz',
            2 => 'Tampico',
            4 => 'Ampliacion',
            13 => 'Ejercito Mexicano',
            16 => 'Curva Texas',
            6 => 'Civil'
        ];
    ?>
    <?php if (isset($nombres[$sucursal_id])): ?>
        <h1 class="mb-4">📍 Alertas para Sucursal <?= htmlspecialchars($nombres[$sucursal_id]) ?></h1>
    <?php else: ?>
        <h1 class="mb-4">📍 Alertas para Sucursal #<?= htmlspecialchars($sucursal_id) ?></h1>
    <?php endif; ?>

    <?php if ($alertas): ?>
    <ul class="list-group mb-3">
    <?php foreach ($alertas as $a): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
                <?= htmlspecialchars($a['mensaje']) ?><br>
                <small class="text-muted">
                    <?= date('d/m/Y H:i', strtotime($a['fecha_programada'])) ?>
                </small>
            </div>
            <form action="/alertas/resolver" method="post" class="m-0 me-2">
                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                <button type="submit" class="btn btn-sm btn-success">✅</button>
            </form>
            <form action="/no_venta" method="post" class="d-flex gap-2 m-0">
                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                <select name="motivo" class="form-select form-select-sm">
                    <?php foreach ($MOTIVOS as $m): ?>
                        <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-danger">❌</button>
            </form>
        </li>
    <?php endforeach; ?>
    </ul>
    <?php else: ?>
        <div class="alert alert-success">No hay alertas activas para esta sucursal ✅</div>
    <?php endif; ?>
</div>
</body>
</html>