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
                    <a href="farmacia_alertas_php/alertas" class="btn btn-primary w-100">Ver alertas</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Alertas por sucursal</h5>
                    <ul class="list-group">
                        <?php 
                            $nombres = [
                                1 => 'Matriz',
                                2 => 'Tampico',
                                4 => 'Ampliacion',
                                13 => 'Ejercito Mexicano',
                                16 => 'Curva Texas',
                                6 => 'Civil'
                            ];
                            foreach ($por_sucursal as $row):
                                $id = $row['sucursal_id'];
                                $nombre = $nombres[$id] ?? "Sucursal #{$id}";
                        ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <a href="farmacia_alertas_php/alertas_sucursal/<?= $id ?>" class="text-decoration-none">
                                <?= htmlspecialchars($nombre) ?>
                            </a>
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
                    <a href="farmacia_alertas_php/justificaciones" class="btn btn-success w-100">Ver justificaciones</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h3 class="card-title"><?= htmlspecialchars($reprogramadas) ?></h3>
                    <p class="card-text">Llamadas reprogramadas</p>
                    <a href="farmacia_alertas_php/alertas?reprogramadas=1" class="btn btn-warning w-100">Ver reprogramadas</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
