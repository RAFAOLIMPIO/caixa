<?php
// Unified config: PDO supporting PostgreSQL or MySQL via DB_TYPE env or default to 'postgres'
/*
Set environment variables or edit the values below if necessary.
DB_TYPE = 'pgsql' or 'mysql'
*/
$db_type = getenv('DB_TYPE') ?: 'pgsql';

if ($db_type === 'pgsql') {
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '5432';
    $dbname = getenv('DB_NAME') ?: 'cx7670';
    $user = getenv('DB_USER') ?: 'cx7670_user';
    $password = getenv('DB_PASS') ?: 'changeme';
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    // on some hosts (Render) you may need sslmode=require
    if (getenv('DB_SSLMODE')) {
        $dsn .= ';sslmode=' . getenv('DB_SSLMODE');
    }
} else {
    // mysql
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $dbname = getenv('DB_NAME') ?: 'caixa';
    $user = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') ?: '';
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
}

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    // Friendly message for dev; in production consider logging and showing generic message.
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}

// Session security & long-lived session (keeps session alive server-side for 24h by default)
$session_lifetime = intval(getenv('SESSION_LIFETIME') ?: 86400); // 24 hours
ini_set('session.gc_maxlifetime', $session_lifetime);
session_set_cookie_params([
    'lifetime' => $session_lifetime,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper for base URL detection (useful in JS keep-alive)
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
    . "://".$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\').'/';
?>