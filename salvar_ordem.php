<?php
// salvar_ordem.php
require 'includes/config.php';

if (!isset($_SESSION['usuario']['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Não autorizado']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$usuario_id = $_SESSION['usuario']['id'];

if (is_array($data)) {
    try {
        $pdo->beginTransaction();
        
        foreach ($data as $item) {
            $stmt = $pdo->prepare("
                UPDATE vendas 
                SET ordem = ? 
                WHERE id = ? 
                AND usuario_id = ?
            ");
            $stmt->execute([
                $item['ordem'],
                $item['id'],
                $usuario_id
            ]);
        }
        
        $pdo->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Dados inválidos']);
}