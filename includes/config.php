<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();

define('DB_HOST', 'dpg-d4no0k24d50c739ok92g-a');
define('DB_PORT', '5432');
define('DB_NAME', 'banco7670_4bf6');
define('DB_USER', 'banco7670_4bf6_user');
define('DB_PASS', 'lu9ziOuXCSFAh3j8au0S8O5lqwz6b1kP');

try {
    $pdo = new PDO(
        "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    error_log('Erro de conexão: ' . $e->getMessage());
    die("<div style='background:red;color:white;padding:10px;text-align:center'>
        ⚠️ Erro de conexão com o banco de dados.<br>Mensagem: {$e->getMessage()}
    </div>");
}