<?php
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['usuario']['numero_loja'])) {
    http_response_code(403);
    exit();
}

$numero_loja = $_SESSION['usuario']['numero_loja'];
$termo = trim($_GET['q'] ?? '');

if (strlen($termo) < 2) {
    echo json_encode([]);
    exit();
}

$stmt = $pdo->prepare("
    SELECT DISTINCT cliente
    FROM vendas
    WHERE numero_loja = :numero_loja
      AND cliente ILIKE :termo
    ORDER BY cliente
    LIMIT 10
");
$stmt->execute([
    ':numero_loja' => $numero_loja,
    ':termo' => "%{$termo}%"
]);

echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
