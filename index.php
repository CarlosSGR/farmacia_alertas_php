<?php
require_once __DIR__ . '/db.php';

$MOTIVOS = [
    "Cliente no contestó",
    "Lo encontró más barato",
    "Ya no lo necesita",
    "Otro proveedor"
];

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/farmacia_alertas_php', '', $path);

switch ($path) {
    case '/':
    case '/panel':
        show_panel();
        break;
    case '/alertas':
        show_alertas();
        break;
    case '/generar_alertas':
        generar_alertas();
        break;
    case '/alertas/resolver':
        resolver_alerta();
        break;
    case '/no_venta':
        registrar_no_venta();
        break;
    case '/justificaciones':
        show_justificaciones();
        break;
    default:
        if (preg_match('#^/alertas_sucursal/(\d+)$#', $path, $m)) {
            show_alertas_sucursal((int)$m[1]);
        } else {
            http_response_code(404);
            echo 'Not Found';
        }
        break;
}

function show_panel() {
    $db = get_db();
    $total = $db->query("SELECT COUNT(*) FROM alerta WHERE atendida = 0")->fetchColumn();
    $por_sucursal = $db->query("
    SELECT sucursal_id, COUNT(*) AS cnt
    FROM alerta
    WHERE atendida = 0
    GROUP BY sucursal_id
    ")->fetchAll(PDO::FETCH_ASSOC);
    $total_j = $db->query("SELECT COUNT(*) FROM justificacion_no_venta")->fetchColumn();
    include __DIR__ . '/views/panel.php';
}

function show_alertas() {
    $db = get_db();

    $desde = date('Y-m-d', strtotime('-30 days'));
    $hasta = date('Y-m-d', strtotime('-27 days'));

    $stmt = $db->prepare("
        SELECT 
            t1.INTCLIENTEID AS cliente_id,
            tc.STRNOMBRE AS nombre,
            tc.STRTELEFONO AS telefono,
            ta.STRAMECOP AS codigo,
            ta.STRNOMBRE AS producto,
            t1.DTMFECHA AS fecha_ultima_compra,
            30 AS frecuencia_dias,
            t1.INTIDSUCURSAL AS sucursal_id
        FROM tblclsventa t1
        INNER JOIN tblclsdetventa t2 ON t1.INTIDSUCURSAL = t2.INTIDSUCURSAL AND t1.INTNUMEROVENTA = t2.INTNUMEROVENTA
        INNER JOIN tblclsarticulo ta ON t2.STRAMECOP = ta.STRAMECOP AND t2.INTIDSUCURSAL = ta.INTIDSUCURSAL
        INNER JOIN tblclscliente tc ON t1.INTCLIENTEID = tc.INTCLIENTEID
        WHERE t1.INTCLIENTEID <> 0
        AND ta.STRSECTORID IN (70,88)
        AND t1.DTMFECHA BETWEEN ? AND ?
    ");
    $stmt->execute([$desde, $hasta]);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($ventas as $venta) {
        $fecha_programada = (new DateTime($venta['fecha_ultima_compra']))->modify('+30 days')->format('Y-m-d H:i:s');
        $mensaje = "Llamar a {$venta['nombre']} ({$venta['telefono']}) para ofrecerle {$venta['producto']}";

        $alerta_id = md5("{$venta['cliente_id']}-{$venta['codigo']}-{$fecha_programada}-Recompra");

        $check = $db->prepare("SELECT COUNT(*) FROM alerta WHERE alerta_id = ?");
        $check->execute([$alerta_id]);

        if ($check->fetchColumn() == 0) {
            $insert = $db->prepare("
                INSERT INTO alerta (tipo, mensaje, fecha_programada, sucursal_id, cliente_id, alerta_id, atendida)
                VALUES (?, ?, ?, ?, ?, ?, 0)
            ");
            $insert->execute([
                'Recompra',
                $mensaje,
                $fecha_programada,
                $venta['sucursal_id'],
                $venta['cliente_id'],
                $alerta_id
            ]);
        }
    }

    $hoy = date('Y-m-d');
    $tres_dias = date('Y-m-d', strtotime('+3 days'));

    $query = $db->prepare("
        SELECT * FROM alerta
        WHERE fecha_programada BETWEEN ? AND ?
        AND atendida = 0
        ORDER BY fecha_programada ASC
    ");
    $query->execute([$hoy, $tres_dias]);
    $alertas = $query->fetchAll(PDO::FETCH_ASSOC);

    $tipos = [];
    foreach ($alertas as $a) {
        $tipos[$a['tipo']][] = $a;
    }

    include __DIR__ . '/views/alertas.php';
}

function generar_alertas() {
    show_alertas(); // Ahora reutiliza la lógica de show_alertas
}

function show_alertas_sucursal(int $sucursal_id) {
    global $MOTIVOS;
    $db = get_db();

    $hoy = date('Y-m-d');
    $tres_dias = date('Y-m-d', strtotime('+3 days'));

    $stmt = $db->prepare("
        SELECT * FROM alerta
        WHERE sucursal_id = ?
        AND fecha_programada BETWEEN ? AND ?
        AND atendida = 0
        ORDER BY fecha_programada ASC
    ");
    $stmt->execute([$sucursal_id, $hoy, $tres_dias]);
    $alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    include __DIR__ . '/views/alertas_sucursal.php';
}

function resolver_alerta() {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        header('Location: /alertas');
        exit;
    }

    $db = get_db();
    $stmt = $db->prepare("UPDATE alerta SET atendida = 1, fecha_atendida = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/alertas'));
}

function registrar_no_venta() {
    global $MOTIVOS;
    $alerta_id = $_POST['id'] ?? null;
    $motivo = $_POST['motivo'] ?? '';
    if (!$alerta_id || !in_array($motivo, $MOTIVOS)) {
        header('Location: /alertas');
        exit;
    }
    $db = get_db();
    $stmt = $db->prepare("INSERT INTO justificacion_no_venta (alerta_id, motivo) VALUES (?, ?)");
    $stmt->execute([$alerta_id, $motivo]);
    $upd = $db->prepare("UPDATE alerta SET atendida = 1, fecha_atendida = NOW() WHERE id = ?");
    $upd->execute([$alerta_id]);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/alertas'));
}

function show_justificaciones() {
    global $MOTIVOS;
    $db = get_db();
    $registros = [];

    foreach ($MOTIVOS as $m) {
        $stmt = $db->prepare("
            SELECT * FROM justificacion_no_venta 
            WHERE motivo = ? 
            ORDER BY fecha DESC
        ");
        $stmt->execute([$m]);
        $registros[$m] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    include __DIR__ . '/views/justificaciones.php';
}
?>
