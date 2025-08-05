<?php
/**
 * Obtiene una conexión PDO a la base de datos.
 * La instancia se reutiliza para evitar múltiples conexiones.
 */
function get_db() {
    static $db = null; // Conexión persistente en memoria
    if ($db === null) {
        $host = getenv('DB_HOST') ?: 'localhost';
        $name = getenv('DB_NAME') ?: 'dbsicofa';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $dsn  = "mysql:host=$host;dbname=$name;charset=utf8mb4";
        $db = new PDO($dsn, $user, $pass);
        // Activar excepciones para manejar errores
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $db;
}

?>

