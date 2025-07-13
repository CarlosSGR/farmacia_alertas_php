<?php
function get_db() {
    static $db = null;
    if ($db === null) {
        $instanceDir = __DIR__ . '/../instance';
        if (!is_dir($instanceDir)) {
            mkdir($instanceDir, 0777, true);
        }
        $db = new PDO('sqlite:' . $instanceDir . '/farmacia.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $db;
}

function init_db() {
    $db = get_db();
    $queries = [
        "CREATE TABLE IF NOT EXISTS proveedor (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            dia_pedido_fijo TEXT NOT NULL,
            dias_entrega INTEGER NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS medicamento (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            proveedor_id INTEGER REFERENCES proveedor(id)
        )",
        "CREATE TABLE IF NOT EXISTS sucursal (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS stock_local (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            medicamento_id INTEGER REFERENCES medicamento(id),
            sucursal_id INTEGER REFERENCES sucursal(id),
            existencias INTEGER NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS cliente_cronico (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            medicamento_id INTEGER REFERENCES medicamento(id),
            sucursal_id INTEGER REFERENCES sucursal(id),
            frecuencia_dias INTEGER NOT NULL,
            fecha_ultima_compra DATE NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS alerta (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tipo TEXT NOT NULL,
            mensaje TEXT NOT NULL,
            fecha_programada DATETIME NOT NULL,
            sucursal_id INTEGER,
            atendida INTEGER DEFAULT 0,
            destinatario TEXT
        )",
        "CREATE TABLE IF NOT EXISTS pedido_marcado (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            proveedor_id INTEGER REFERENCES proveedor(id),
            fecha DATE NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS venta (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            cliente_id INTEGER REFERENCES cliente_cronico(id) NOT NULL,
            medicamento_id INTEGER REFERENCES medicamento(id) NOT NULL,
            sucursal_id INTEGER REFERENCES sucursal(id) NOT NULL,
            fecha DATE NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS justificacion_no_venta (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            alerta_id INTEGER REFERENCES alerta(id) NOT NULL,
            motivo TEXT NOT NULL,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP
        )"
    ];
    foreach ($queries as $sql) {
        $db->exec($sql);
    }
}
?>