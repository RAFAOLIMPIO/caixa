<?php
session_start();

// Conexão com o banco (Railway)
$host = 'mysql.railway.interno'; // MYSQLHOST
$user = 'raiz';                   // USUÁRIO MYSQL
$password = 'eLCxrwCATavhCwyXQSoSNIWlINzTHXTk'; // SENHA MYSQL
$database = 'ferrovia';          // BANCO DE DADOS MYSQL

try {
    $pdo = new PDO(
        "mysql:host=$host;port=3306;dbname=$database;charset=utf8mb4",
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
