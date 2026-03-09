<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/funcoes.php';

verificar_login();

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    echo json_encode(["ok" => false, "error" => "Dados inválidos"]);
    exit;
}

try {
    $pdo->beginTransaction();
    foreach ($data as $item) {
        $stmt = $pdo->prepare("UPDATE vendas SET ordem = ? WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$item['ordem'], $item['id'], $_SESSION['usuario_id']]);
    }
    $pdo->commit();
    echo json_encode(["ok" => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
