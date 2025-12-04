<?php
session_start();
ob_start();

// Configurações de banco de dados - Render PostgreSQL
define('DB_HOST', 'dpg-d1c3qummcj7s73a60q30-a.oregon-postgres.render.com');
define('DB_NAME', 'cx7670');
define('DB_USER', 'cx7670_user');
define('DB_PASS', 'a7JoRWJCdN6v5dpuIYZVD0fvww2S5n3O');
define('DB_PORT', '5432');

// Conexão PDO com SSL habilitado
try {
    $pdo = new PDO(
        "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require",
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
