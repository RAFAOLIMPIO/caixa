<?php
session_start();

// Pega dados do banco via variáveis de ambiente (se configuradas no painel Render)
$host = getenv('DB_HOST') ?: 'dpg-d1c3qummcj7s73a60q30-a.oregon-postgres.render.com';
$port = getenv('DB_PORT') ?: '5432';
$user = getenv('DB_USER') ?: 'cx7670_user';
$password = getenv('DB_PASS') ?: 'a7JoRWJCdN6v5dpuIYZVD0fvww2S5n3O';
$database = getenv('DB_NAME') ?: 'cx7670';

try {
    // Força uso de SSL obrigatório no Render
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$database;sslmode=require",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Erro na conexão com o banco: " . $e->getMessage());
}

// Função para sanitizar dados
function sanitizar($dado) {
    return htmlspecialchars(trim($dado), ENT_QUOTES, 'UTF-8');
}