<?php
require 'includes/config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['usuario']['id'])) {
    echo json_encode(['error' => 'Não autorizado']);
    exit();
}

$usuario_id = $_SESSION['usuario']['id'];

if (isset($data['ordem']) && is_array($data['ordem'])) {

    $data = $data['ordem'];

    try {
        $pdo->beginTransaction();

        foreach ($data as $index => $item) {

            $id = (int)$item['id'];

            $stmt = $pdo->prepare("
                UPDATE vendas
                SET ordem = ?
                WHERE id = ?
                AND usuario_id = ?
            ");

            $stmt->execute([$index, $id, $usuario_id]);
        }

        $pdo->commit();

       echo json_encode(['ok' => true]);


    } catch (Exception $e) {
        $pdo->rollBack();
      echo json_encode([
    'ok' => false,
    'error' => $e->getMessage()
]);

    }

} else {
    echo json_encode(['error' => 'Dados inválidos']);
}
