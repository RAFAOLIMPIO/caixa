<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/funcoes.php';

verificar_login();

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'];
$pago = $data['pago'];

$stmt = $pdo->prepare("UPDATE vendas SET pago = ? WHERE id = ?");
$stmt->execute([$pago, $id]);

echo json_encode(["ok"=>true]);
