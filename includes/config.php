<?php
session_start();

// Conexão com o banco (Railway externo)
$host = 'yamanote.proxy.rlwy.net';
$port = 57420;
$user = 'root';
$password = 'OlLAHAxVBKtEbKdpcpuryBKFcOlwtvhy';
$database = 'railway';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4",
        $user,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

function sanitizar($dado) {
    return htmlspecialchars(trim($dado), ENT_QUOTES, 'UTF-8');
}
?>
