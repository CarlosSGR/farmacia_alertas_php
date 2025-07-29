<?php
function get_db() {
    static $db = null;
    if ($db === null) {
        $host = getenv('DB_HOST') ?: '192.168.1.199';
        $name = getenv('DB_NAME') ?: 'dbsicofa';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '123456';
        $dsn  = "mysql:host=$host;dbname=$name;charset=utf8mb4";
        $db = new PDO($dsn, $user, $pass);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $db;
}

?>