<!DOCTYPE html>
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
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <?= htmlspecialchars($alerta['mensaje']) ?><br>
                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($alerta['fecha_programada'])) ?></small>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <span class="badge bg-warning text-dark">Pendiente</span>
                            <form action="/alertas/resolver" method="post" class="m-0">
                                <input type="hidden" name="id" value="<?= $alerta['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-success">✅</button>
                            </form>
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