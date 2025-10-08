<?php
require_once __DIR__ . '/../bootstrap.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_DATABASE . ";charset=utf8mb4";
    $options = [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new \PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
    echo "Veritabanı bağlantısı başarılı!<br>";
    
    // Tabloları listele
    $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
    echo "Mevcut tablolar: <br>";
    foreach ($tables as $table) {
        echo "- $table<br>";
    }
} catch (\PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
