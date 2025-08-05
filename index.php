<?php
// Punto de entrada de la aplicación y enrutador principal
require_once __DIR__ . '/db.php'; // Conexión a la base de datos

// Motivos válidos para registrar una justificación de no venta
$MOTIVOS = [
    "Cliente no contestó",
    "Cliente contestó pido reprogramar",
    "Lo encontró más barato",
    "Ya no lo necesita",
    "Otro proveedor",
];

// Determinar ruta solicitada
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Ajustar ruta si el proyecto vive en una subcarpeta
$path = str_replace('/farmacia_alertas_php', '', $path);

// Enrutamiento básico según la URL
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
    case '/reprogramar':
        $id = $_POST['id'] ?? null;
        $nueva_fecha = $_POST['nueva_fecha'] ?? null;
        reprogramar_alerta($id, $nueva_fecha);
        break;
    case '/no_venta':
        registrar_no_venta();
        break;
    case '/justificaciones':
        show_justificaciones();
        break;
    default:
        // Coincidencia con /alertas/sucursal/{id}
        if (preg_match('#^/alertas/sucursal/(\d+)$#', $path, $m)) {
            show_alertas_sucursal((int)$m[1]);
        } else {
            http_response_code(404);
            echo 'Not Found';
        }
        break;
}

/**
 * Muestra el panel con estadísticas generales.
 */
function show_panel() {
    $db = get_db(); // Reutiliza la conexión
    // Total de alertas pendientes
    $total = $db->query("SELECT COUNT(*) FROM alerta WHERE atendida = 0")->fetchColumn();
    // Alertas agrupadas por sucursal
    $por_sucursal = $db->query("
    SELECT sucursal_id, COUNT(*) AS cnt
    FROM alerta
    WHERE atendida = 0
    GROUP BY sucursal_id
    ")->fetchAll(PDO::FETCH_ASSOC);
    // Total de justificaciones registradas
    $total_j = $db->query("SELECT COUNT(*) FROM justificacion_no_venta")->fetchColumn();
    // Llamadas reprogramadas futuras
    $reprogramadas = $db->query("SELECT COUNT(*) FROM alerta WHERE fecha_programada > NOW() AND atendida = 0")->fetchColumn();
    include __DIR__ . '/views/panel.php'; // Renderiza la vista del panel
}

/**
 * Genera alertas de recompra y muestra las pendientes.
 */
function show_alertas() {
    $db = get_db();

    // Primeros días del mes pasado
    $desde = (new DateTime('first day of last month'))->format('Y-m-d');
    $hasta = (new DateTime('first day of last month'))->modify('+2 days')->format('Y-m-d');

    // Consultar ventas de productos específicos
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

    // Crear una alerta por cada venta si no existe
    foreach ($ventas as $venta) {
        $fecha_programada = (new DateTime($venta['fecha_ultima_compra']))->modify('+30 days')->format('Y-m-d H:i:s');
        $mensaje = "Llamar a {$venta['nombre']} ({$venta['telefono']}) para ofrecerle {$venta['producto']}";

        // Identificador único de la alerta
        $alerta_id = md5("{$venta['cliente_id']}-{$venta['codigo']}-{$fecha_programada}-Recompra");

        $check = $db->prepare("SELECT COUNT(*) FROM alerta WHERE alerta_id = ?");
        $check->execute([$alerta_id]);
        // Registrar la alerta si no existe

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
                $alerta_id // ✅ Ahora va en el campo correcto
            ]);

        }
    }

    // Rango de fechas para mostrar alertas
    $hoy = date('Y-m-d');
    $tres_dias = date('Y-m-d', strtotime('+3 days'));

    // Obtener alertas programadas en el rango
    $query = $db->prepare("
        SELECT * FROM alerta
        WHERE fecha_programada BETWEEN ? AND ?
        AND atendida = 0
        ORDER BY fecha_programada ASC
    ");
    $query->execute([$hoy, $tres_dias]);
    $alertas = $query->fetchAll(PDO::FETCH_ASSOC);
    // Agrupar alertas por tipo para la vista

    $tipos = [];
    foreach ($alertas as $a) {
        $tipos[$a['tipo']][] = $a;
    }

    include __DIR__ . '/views/alertas.php';
}

/**
 * Permite generar alertas manualmente.
 */
function generar_alertas() {
    show_alertas(); // Reutiliza la lógica de show_alertas
}

/**
 * Muestra las alertas pendientes de una sucursal específica.
 */
function show_alertas_sucursal(int $sucursal_id) {
    global $MOTIVOS;
    $db = get_db();

    // Rango de fechas para filtrar
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

/**
 * Marca una alerta como atendida.
 */
function resolver_alerta() {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        header('Location: /alertas');
        exit;
    }

    $db = get_db();
    $stmt = $db->prepare("UPDATE alerta SET atendida = 1, fecha_atendida = NOW() WHERE alerta_id = ?");
    $stmt->execute([$id]);

    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/alertas'));
}

/**
 * Cambia la fecha programada de una alerta y guarda el historial del cambio.
 */
function reprogramar_alerta($id, $nueva_fecha) {
    if (!$id || !$nueva_fecha) {
        header('Location: /alertas');
        exit;
    }

    $db = get_db();

    $stmt = $db->prepare("SELECT fecha_programada FROM alerta WHERE alerta_id = ?");
    $stmt->execute([$id]);
    $fecha_anterior = $stmt->fetchColumn();

    if (!$fecha_anterior) {
        header('Location: /alertas');
        exit;
    }

    $upd = $db->prepare("UPDATE alerta SET fecha_programada = ? WHERE alerta_id = ?");
    $upd->execute([$nueva_fecha, $id]);

    try {
        $usuario_id = $_SESSION['usuario_id'] ?? null;
        $hist = $db->prepare("INSERT INTO alerta_reprogramacion (alerta_id, fecha_anterior, fecha_nueva, usuario_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        $hist->execute([$id, $fecha_anterior, $nueva_fecha, $usuario_id]);
    } catch (Exception $e) {
        // Tabla de historial opcional; ignorar si no existe
    }

    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/alertas'));
}

/**
 * Registra un motivo por el cual no se concretó la venta y cierra la alerta.
 */
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

    $upd = $db->prepare("UPDATE alerta SET atendida = 1, fecha_atendida = NOW() WHERE alerta_id = ?");
    $upd->execute([$alerta_id]);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/alertas'));
}

/**
 * Lista las justificaciones de no venta agrupadas por motivo.
 */
function show_justificaciones() {
    global $MOTIVOS;
    $db = get_db();
    $registros = [];

    // Consultar registros por cada motivo
    foreach ($MOTIVOS as $m) {
        $stmt = $db->prepare("
            SELECT j.*, 
                   a.cliente_id, 
                   c.STRNOMBRE AS cliente_nombre, 
                   c.STRTELEFONO AS cliente_telefono,
                   ta.STRNOMBRE AS producto
            FROM justificacion_no_venta j
            INNER JOIN alerta a ON j.alerta_id = a.alerta_id
            INNER JOIN tblclscliente c ON a.cliente_id = c.INTCLIENTEID
            INNER JOIN tblclsarticulo ta ON a.mensaje LIKE CONCAT('%', ta.STRNOMBRE, '%') AND ta.INTIDSUCURSAL = a.sucursal_id
            WHERE j.motivo = ?
            ORDER BY j.fecha DESC
        ");
        $stmt->execute([$m]);
        $registros[$m] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    include __DIR__ . '/views/justificaciones.php';
}


?>
