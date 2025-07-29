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
    $por_tipo = $db->query("SELECT tipo, COUNT(*) cnt FROM alerta WHERE atendida = 0 GROUP BY tipo")->fetchAll(PDO::FETCH_ASSOC);
    $total_j = $db->query("SELECT COUNT(*) FROM justificacion_no_venta")->fetchColumn();
    include __DIR__ . '/views/panel.php';
}

function show_alertas() {
    $db = get_db();

    $stmt = $db->query("
        SELECT 
            t1.INTCLIENTEID AS cliente_id,
            tc.STRNOMBRE AS nombre,
            tc.STRTELEFONO AS telefono,
            ta.STRAMECOP AS codigo,
            ta.STRNOMBRE AS producto,
            t1.DTMFECHA AS fecha_ultima_compra,
            '3' AS frecuencia_dias,
            t1.INTIDSUCURSAL AS sucursal_id
        FROM tblclsventa t1
        INNER JOIN tblclsdetventa t2 ON t1.INTIDSUCURSAL = t2.INTIDSUCURSAL AND t1.INTNUMEROVENTA = t2.INTNUMEROVENTA
        INNER JOIN tblclsarticulo ta ON t2.STRAMECOP = ta.STRAMECOP AND t2.INTIDSUCURSAL = ta.INTIDSUCURSAL
        INNER JOIN tblclscliente tc ON t1.INTCLIENTEID = tc.INTCLIENTEID
        WHERE t1.INTCLIENTEID <> 0
        AND ta.STRSECTORID IN (70,88)
        AND t1.DTMFECHA BETWEEN '2025-01-01' AND '2025-01-01 23:59:59'
    ");
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tipos = [];

    foreach ($resultados as $i => $row) {
        $tipos['Recompra'][] = [
            'id' => $row['cliente_id'], // Puedes usar cualquier identificador único
            'tipo' => 'Recompra',
            'mensaje' => "Llamar a {$row['nombre']} ({$row['telefono']}) para ofrecerle {$row['producto']}",
            'fecha_programada' => date('Y-m-d') // O ajusta la lógica si quieres otra fecha
        ];
    }

    include __DIR__ . '/views/alertas.php';
}


function show_alertas_sucursal(int $sucursal_id) {
    global $MOTIVOS;
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM alerta WHERE sucursal_id = ? AND fecha_programada <= NOW() AND atendida = 0");
    $stmt->execute([$sucursal_id]);
    $alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    include __DIR__ . '/views/alertas_sucursal.php';
}

function resolver_alerta() {
    $id = $_POST['id'] ?? null;
    if (!$id) { header('Location: /alertas'); exit; }
    $db = get_db();
    $stmt = $db->prepare("UPDATE alerta SET atendida = 1 WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/alertas'));
}

function registrar_no_venta() {
    global $MOTIVOS;
    $alerta_id = $_POST['id'] ?? null;
    $motivo    = $_POST['motivo'] ?? '';
    if (!$alerta_id || !in_array($motivo, $MOTIVOS)) {
        header('Location: /alertas');
        exit;
    }
    $db = get_db();
    $stmt = $db->prepare("INSERT INTO justificacion_no_venta (alerta_id, motivo) VALUES (?, ?)");
    $stmt->execute([$alerta_id, $motivo]);
    $upd  = $db->prepare("UPDATE alerta SET atendida = 1 WHERE id = ?");
    $upd->execute([$alerta_id]);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/alertas'));
}

function show_justificaciones() {
    global $MOTIVOS;
    $db = get_db();
    $registros = [];
    foreach ($MOTIVOS as $m) {
        $stmt = $db->prepare("SELECT * FROM justificacion_no_venta WHERE motivo = ? ORDER BY fecha DESC");
        $stmt->execute([$m]);
        $registros[$m] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    include __DIR__ . '/views/justificaciones.php';
}
?>