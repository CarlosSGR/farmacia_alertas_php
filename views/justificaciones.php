<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Justificaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="mb-4">📑 Justificaciones</h1>
    <?php foreach ($registros as $motivo => $lista): ?>
        <h4 class="mt-4">
            <?= htmlspecialchars($motivo) ?> (<?= count($lista) ?>)
        </h4>
        <ul class="list-group mb-3">
        <?php foreach ($lista as $j): ?>
            <li class="list-group-item">
                Alerta ID: <?= $j['alerta_id'] ?>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>
</div>
</body>
</html>