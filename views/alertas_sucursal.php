<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Alertas Sucursal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="mb-4">Alertas Sucursal <?= htmlspecialchars($sucursal_id) ?></h1>
    <ul class="list-group">
    <?php foreach ($alertas as $a): ?>
        <li class="list-group-item d-flex justify-content-between">
            <?= htmlspecialchars($a['mensaje']) ?>
            <form action="/alertas/resolver" method="post" class="m-0">
                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                <button class="btn btn-sm btn-success">✅</button>
            </form>
        </li>
    <?php endforeach; ?>
    </ul>
</div>
</body>
</html>
