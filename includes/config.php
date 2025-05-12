<?php
session_start();

// Conexão com o banco (Railway externo para uso no Render)
$host = 'yamanoote.proxy.rlwy.net'; // Pegue o domínio visível na variável MYSQL_PUBLIC_URL
$port = 57420; // Pegue da mesma variável
$user = 'root'; // MYSQLUSER
$password = 'OlLAHAxVBKtEbKdpcpuryBKFcOlwtvhy'; // MYSQLPASSWORD
$database = 'railway'; // MYSQLDATABASE

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4",
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
