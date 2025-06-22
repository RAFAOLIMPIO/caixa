<?php
session_start();

// Conexão com o banco PostgreSQL no Render
$host = 'dpg-d1c3qummcj7s73a60q30-a.oregon-postgres.render.com';
$port = '5432';
$user = 'cx7670_user';
$password = 'a7JoRWJCdN6v5dpuIYZVD0fvww2S5n3O';
$database = 'cx7670';

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$database",
        $user,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erro na conexão com o banco: " . $e->getMessage());
}

// Função para sanitizar dados
function sanitizar($dado) {
    return htmlspecialchars(trim($dado), ENT_QUOTES, 'UTF-8');
}