<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/vendor/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['usuario']['id'])) {
    die('Sessão expirada');
}

$usuario_id  = $_SESSION['usuario']['id'];
$numero_loja = $_SESSION['usuario']['numero_loja'];

$stmt = $pdo->prepare("
    SELECT cliente, valor_total, forma_pagamento, pago, data_venda
    FROM vendas
    WHERE usuario_id = :uid
    ORDER BY id DESC
");
$stmt->execute([':uid' => $usuario_id]);
$vendas = $stmt->fetchAll();

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans; font-size: 12px; }
h2 { text-align: center; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border: 1px solid #000; padding: 6px; }
th { background: #eee; }
</style>
</head>
<body>

<h2>Relatório de Vendas - Loja <?= $numero_loja ?></h2>

<table>
<tr>
    <th>Data</th>
    <th>Cliente</th>
    <th>Valor</th>
    <th>Pagamento</th>
    <th>Status</th>
</tr>

<?php foreach ($vendas as $v): ?>
<tr>
    <td><?= date('d/m/Y H:i', strtotime($v['data_venda'])) ?></td>
    <td><?= htmlspecialchars($v['cliente']) ?></td>
    <td>R$ <?= number_format($v['valor_total'], 2, ',', '.') ?></td>
    <td><?= $v['forma_pagamento'] ?></td>
    <td><?= $v['pago'] ? 'Pago' : 'Pendente' ?></td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);

$pdf = new Dompdf($options);
$pdf->loadHtml($html);
$pdf->setPaper('A4', 'portrait');
$pdf->render();
$pdf->stream("relatorio_loja_{$numero_loja}.pdf", ["Attachment" => true]);
