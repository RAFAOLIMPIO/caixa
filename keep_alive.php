<?php
// keep_alive.php - usado para manter sessão ativa via AJAX
include __DIR__ . '/includes/config.php';
http_response_code(200);
echo json_encode(['ok' => true, 'time' => time()]);
?>