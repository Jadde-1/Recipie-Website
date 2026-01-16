<?php
require_once __DIR__ . '/config.php';

function get_db() {
    static $pdo = null;
    global $dbHost, $dbName, $dbUser, $dbPass;
    if ($pdo === null) {
        $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
