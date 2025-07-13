<?php
require_once __DIR__ . '/db.php';
init_db();

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
    $stmt = $db->prepare("SELECT * FROM alerta WHERE fecha_programada <= datetime('now') AND atendida = 0");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tipos = [];
    foreach ($rows as $row) {
        $tipos[$row['tipo']][] = $row;
    }
    include __DIR__ . '/views/alertas.php';
}

function show_alertas_sucursal(int $sucursal_id) {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM alerta WHERE sucursal_id = ? AND fecha_programada <= datetime('now') AND atendida = 0");
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

function show_justificaciones() {
    $db = get_db();
    $motivos = [
        "Cliente no contestó",
        "Lo encontró más barato",
        "Ya no lo necesita",
        "Otro proveedor"
    ];
    $registros = [];
    foreach ($motivos as $m) {
        $stmt = $db->prepare("SELECT * FROM justificacion_no_venta WHERE motivo = ?");
        $stmt->execute([$m]);
        $registros[$m] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    include __DIR__ . '/views/justificaciones.php';
}
?>