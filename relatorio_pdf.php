<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/vendor/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['usuario']['id'])) {
    exit('Acesso negado');
}

$usuario_id = $_SESSION['usuario']['id'];
$numero_loja = $_SESSION['usuario']['numero_loja'];

// Buscar vendas
$stmt = $pdo->prepare("
    SELECT v.*, f.nome AS autozoner_nome
    FROM vendas v
    LEFT JOIN funcionarios f ON v.autozoner_id = f.id
    WHERE v.usuario_id = :usuario_id
    ORDER BY v.id DESC
");
$stmt->execute([':usuario_id' => $usuario_id]);
$vendas = $stmt->fetchAll();

// Totais
$total = 0;
foreach ($vendas as $v) {
    $total += (float)$v['valor_total'];
}

// HTML DO PDF
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans; font-size: 12px; }
h1 { text-align: center; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border: 1px solid #ccc; padding: 6px; }
th { background: #eee; }
.total { font-weight: bold; }
</style>
</head>
<body>

<h1>Relatório de Vendas</h1>
<p><strong>Loja:</strong> <?= $numero_loja ?></p>
<p><strong>Data:</strong> <?= date('d/m/Y H:i') ?></p>

<table>
<thead>
<tr>
    <th>Data</th>
    <th>Cliente</th>
    <th>Valor</th>
    <th>Pagamento</th>
    <th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach ($vendas as $v): ?>
<tr>
    <td><?= date('d/m/Y', strtotime($v['data_venda'])) ?></td>
    <td><?= htmlspecialchars($v['cliente']) ?></td>
    <td>R$ <?= number_format($v['valor_total'], 2, ',', '.') ?></td>
    <td><?= $v['forma_pagamento'] ?></td>
    <td><?= $v['pago'] ? 'Pago' : 'Pendente' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<p class="total">Total Geral: R$ <?= number_format($total, 2, ',', '.') ?></p>

</body>
</html>
<?php
$html = ob_get_clean();

// DOMPDF
$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Forçar download
$dompdf->stream("relatorio_loja_{$numero_loja}.pdf", ["Attachment" => true]);
