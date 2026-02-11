<?php
include 'includes/config.php';

if (!isset($_SESSION['usuario']['id'])) {
    header("Location: index.php");
    exit();
}

$numero_loja = $_SESSION['usuario']['numero_loja'];
$termo = $_GET['q'] ?? '';

if (strlen($termo) < 2) {
    echo json_encode([]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT cliente 
        FROM vendas 
        WHERE numero_loja = ? 
          AND cliente LIKE ? 
        ORDER BY cliente 
        LIMIT 10
    ");
    $stmt->execute([$numero_loja, "%$termo%"]);
    $clientes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode($clientes);
} catch (Exception $e) {
    echo json_encode([]);
}
