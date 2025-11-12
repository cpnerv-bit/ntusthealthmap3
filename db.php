<?php
// db.php - PDO connection
session_start();

// TiDB Connection Settings
// 請填入你的 TiDB 連線資訊
define('DB_HOST', 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com');  // TiDB host
define('DB_PORT', '4000');  // TiDB port (預設 4000)
define('DB_NAME', 'ntust_healthmap');  // 你的資料庫名稱
define('DB_USER', 'your_tidb_user');  // TiDB 使用者名稱
define('DB_PASS', 'your_tidb_password');  // TiDB 密碼

// Local MySQL Settings (註解掉，備用)
// define('DB_HOST', '127.0.0.1');
// define('DB_PORT', '3306');
// define('DB_NAME', 'ntust_healthmap');
// define('DB_USER', 'root');
// define('DB_PASS', '');

try {
    // TiDB 連線需要加上 port 和 SSL 設定
    $dsn = 'mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4';
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // TiDB 通常需要 SSL 連線
        PDO::MYSQL_ATTR_SSL_CA => true,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (Exception $e) {
    // In production, don't echo details. For development, helpful to show.
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

