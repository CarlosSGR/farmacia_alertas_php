<?php
function get_db() {
    static $db = null;
    if ($db === null) {
        $host = getenv('DB_HOST') ?: 'localhost';
        $name = getenv('DB_NAME') ?: 'dbsicofa';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $dsn  = "mysql:host=$host;dbname=$name;charset=utf8mb4";
        $db = new PDO($dsn, $user, $pass);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $db;
}

function init_db() {
    $db = get_db();
    $queries = [
        "CREATE TABLE IF NOT EXISTS proveedor (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            dia_pedido_fijo VARCHAR(10) NOT NULL,
            dias_entrega INT NOT NULL
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS medicamento (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            proveedor_id INT,
            FOREIGN KEY (proveedor_id) REFERENCES proveedor(id)
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS sucursal (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS stock_local (
            id INT AUTO_INCREMENT PRIMARY KEY,
            medicamento_id INT,
            sucursal_id INT,
            existencias INT NOT NULL,
            FOREIGN KEY (medicamento_id) REFERENCES medicamento(id),
            FOREIGN KEY (sucursal_id) REFERENCES sucursal(id)
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS cliente_cronico (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            medicamento_id INT,
            sucursal_id INT,
            frecuencia_dias INT NOT NULL,
            fecha_ultima_compra DATE NOT NULL,
            FOREIGN KEY (medicamento_id) REFERENCES medicamento(id),
            FOREIGN KEY (sucursal_id) REFERENCES sucursal(id)
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS alerta (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tipo VARCHAR(50) NOT NULL,
            mensaje VARCHAR(255) NOT NULL,
            fecha_programada DATETIME NOT NULL,
            sucursal_id INT,
            atendida TINYINT(1) DEFAULT 0,
            destinatario VARCHAR(100),
            INDEX (sucursal_id)
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS pedido_marcado (
            id INT AUTO_INCREMENT PRIMARY KEY,
            proveedor_id INT,
            fecha DATE NOT NULL,
            FOREIGN KEY (proveedor_id) REFERENCES proveedor(id)
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS venta (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cliente_id INT NOT NULL,
            medicamento_id INT NOT NULL,
            sucursal_id INT NOT NULL,
            fecha DATE NOT NULL,
            FOREIGN KEY (cliente_id) REFERENCES cliente_cronico(id),
            FOREIGN KEY (medicamento_id) REFERENCES medicamento(id),
            FOREIGN KEY (sucursal_id) REFERENCES sucursal(id)
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS justificacion_no_venta (
            id INT AUTO_INCREMENT PRIMARY KEY,
            alerta_id INT NOT NULL,
            motivo VARCHAR(100) NOT NULL,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (alerta_id) REFERENCES alerta(id)
        ) ENGINE=InnoDB"
    ];
    foreach ($queries as $sql) {
        $db->exec($sql);
    }
}
?>