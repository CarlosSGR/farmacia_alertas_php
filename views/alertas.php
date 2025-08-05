<!DOCTYPE html>
<!-- Listado general de alertas agrupadas por tipo -->
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Alertas Activas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <h1 class="mb-4">📢 Alertas Activas</h1>
    <?php if ($tipos): ?>
        <?php foreach ($tipos as $categoria => $lista): ?>
            <h4 class="mt-4"><?= htmlspecialchars($categoria) ?></h4>
            <ul class="list-group mb-3">
                <?php foreach ($lista as $alerta): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong><?= htmlspecialchars($alerta['nombre']) ?></strong>
                                (<?= htmlspecialchars($alerta['telefono']) ?>)<br>
                                <small class="text-muted">Productos: <?= htmlspecialchars($alerta['productos']) ?></small><br>
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($alerta['fecha_programada'])) ?></small>
                            </div>
                            <span class="badge bg-warning text-dark h-fit">Pendiente</span>
                        </div>
                        <div class="d-flex gap-2 flex-wrap mt-2">
                            <?php foreach ($alerta['items'] as $item): ?>
                                <form action="./alertas/resolver" method="post" class="m-0">
                                    <input type="hidden" name="id" value="<?= $item['alerta_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <?= htmlspecialchars($item['producto']) ?> ✅
                                    </button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-success">No hay alertas activas ✅</div>
    <?php endif; ?>
</div>
</body>
</html>
