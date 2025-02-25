<?php
session_start();

// Conexão com o banco (CORRIGIDO)
$host = 'localhost';
$user = 'root';
$password = ''; // Antes estava $pass
$database = 'sistema_loja'; // Antes estava $dbname

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4", 
        $user, 
        $password // Usar $password que foi definida
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// Função de segurança
function sanitizar($dado) {
    return htmlspecialchars(trim($dado), ENT_QUOTES, 'UTF-8');
}
?>