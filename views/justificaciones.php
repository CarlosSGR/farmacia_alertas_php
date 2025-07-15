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
        <details class="mb-3">
            <summary><?= htmlspecialchars($motivo) ?> (<?= count($lista) ?>)</summary>
            <ul class="list-group mt-2">
            <?php foreach ($lista as $j): ?>
                <li class="list-group-item">
                    Alerta ID: <?= $j['alerta_id'] ?> <br>
                    Motivo: <?= htmlspecialchars($j['motivo']) ?> <br>
                    Fecha: <?= date('d/m/Y H:i', strtotime($j['fecha'])) ?>
                </li>
            <?php endforeach; ?>
            </ul>
        </details>
    <?php endforeach; ?>
</div>
</body>
</html>