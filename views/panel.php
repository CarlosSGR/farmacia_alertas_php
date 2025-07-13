<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="mb-5">📊 Panel del Jefe Virtual</h1>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h3 class="card-title"><?= htmlspecialchars($total) ?></h3>
                    <p class="card-text">Alertas activas</p>
                    <a href="/alertas" class="btn btn-primary w-100">Ver alertas</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Alertas por tipo</h5>
                    <ul class="list-group">
                        <?php foreach ($por_tipo as $row): ?>
                          <li class="list-group-item d-flex justify-content-between">
                            <?= htmlspecialchars($row['tipo']) ?>
                            <span class="badge bg-secondary"><?= $row['cnt'] ?></span>
                          </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h3 class="card-title"><?= htmlspecialchars($total_j) ?></h3>
                    <p class="card-text">Justificaciones registradas</p>
                    <a href="/justificaciones" class="btn btn-success w-100">Ver justificaciones</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>