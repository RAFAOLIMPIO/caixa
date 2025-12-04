<?php
// includes/config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();

// Configurações Render PostgreSQL
define('DB_HOST', 'dpg-d1c3qummcj7s73a60q30-a.oregon-postgres.render.com');
define('DB_PORT', '5432');
define('DB_NAME', 'cx7670');
define('DB_USER', 'cx7670_user');
define('DB_PASS', 'a7JoRWJCdN6v5dpuIYZVD0fvww2S5n3O');

// Conexão PDO com SSL
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
    error_log("Erro de conexão: " . $e->getMessage());
    die("<div style='color:white; background:red; padding:10px; text-align:center; font-family:sans-serif'>
        Erro ao conectar ao banco de dados. Verifique as credenciais e o SSL.
    </div>");
}
