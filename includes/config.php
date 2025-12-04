<?php
// includes/config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();

// Configurações do banco Render PostgreSQL
define('DB_HOST', 'dpg-d4no0k24d50c739ok92g-a');
define('DB_PORT', '5432');
define('DB_NAME', 'cx7670');
define('DB_USER', 'cx7670_user');
define('DB_PASS', 'a7JoRWJCdN6v5dpuIYZVD0fvww2S5n3O');

// Tentativa de conexão segura via SSL
try {
    $pdo = new PDO(
        "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require;sslrootcert=/etc/ssl/certs/ca-certificates.crt",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT => false,
        ]
    );
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}
