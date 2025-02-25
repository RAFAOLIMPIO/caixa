<?php
session_start();

// Conexão com o banco (AWS RDS)
$host = 'sistema-caixa.cpqiw2qka4z1.sa-east-1.rds.amazonaws.com'; // Endpoint do RDS
$user = 'admin'; // Nome de usuário do RDS
$password = 'Melancia09'; // Senha do RDS
$database = 'sistema-caixa'; // Nome do banco de dados

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4", 
        $user, 
        $password
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
