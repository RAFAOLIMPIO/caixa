<?php
session_start();

$host = 'mysql.railway.internal';
$db   = 'railway';
$user = 'root';
$pass = 'OlLAHAxVBKtEbKdpcpuryBKFcOlwtvhy'; // substitua se a senha mudar
$port = 3306;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro na conexão com o banco: " . $e->getMessage());
}

function sanitizar($dado) {
    return htmlspecialchars(trim($dado), ENT_QUOTES, 'UTF-8');
}
?>
