<?php
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['usuario']['id'])) {
    header("Location: index.php");
    exit();
}

$usuario_id  = $_SESSION['usuario']['id'];
$numero_loja = $_SESSION['usuario']['numero_loja'];

/* ==============================
   BUSCAR VENDAS
============================== */
$stmt = $pdo->prepare("
    SELECT v.*, f.nome as autozoner_nome
    FROM vendas v
    LEFT JOIN funcionarios f ON v.autozoner_id = f.id
    WHERE v.usuario_id = :uid
    ORDER BY v.data_venda DESC
");
$stmt->execute([':uid' => $usuario_id]);
$vendas = $stmt->fetchAll();

/* ==============================
   CALCULAR TOTAIS
============================== */
$total_vendas = 0;
$total_pago = 0;
$total_pendente = 0;
$total_devolvido = 0;
$total_pos = 0;

foreach ($vendas as $v) {

    $valor = (float)$v['valor_total'];
    $status = $v['status'] ?? 'normal';
    $devolvido = (float)($v['valor_devolvido'] ?? 0);

    // POS (uber ou motoboy)
    if (!empty($v['motoboy']) && strtolower($v['motoboy']) !== 'balcão') {
        $total_pos += $valor;
    }

    if ($status === 'devolvido') {
        $total_devolvido += $valor;
        continue;
    }

    if ($status === 'parcial') {
        $total_devolvido += $devolvido;
        $valor -= $devolvido;
    }

    $total_vendas += $valor;

    if (!empty($v['pago'])) {
        $total_pago += $valor;
    } else {
        $total_pendente += $valor;
    }
}

/* ==============================
   HTML DO RELATÓRIO
============================== */
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Relatório de Vendas - AutoGest</title>

<style>
body {
    font-family: Arial, sans-serif;
    font-size: 12px;
    color: #333;
    margin: 0;
    padding: 20px;
}

.header {
    text-align: center;
    border-bottom: 2px solid #7c3aed;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.header h1 {
    margin: 0;
    color: #7c3aed;
}

.info-box {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.info-item {
    width: 18%;
    background: #f3f4f6;
    padding: 10px;
    border-radius: 6px;
    text-align: center;
}

.info-item h3 {
    margin: 0;
    font-size: 12px;
    color: #666;
}

.info-item p {
    margin: 5px 0 0;
    font-size: 16px;
    font-weight: bold;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #7c3aed;
    color: white;
    padding: 8px;
    text-align: left;
}

td {
    padding: 8px;
    border-bottom: 1px solid #ddd;
}

tr:nth-child(even) {
    background: #f9f9f9;
}

.status-pago { color: #10b981; font-weight: bold; }
.status-pendente { color: #ef4444; font-weight: bold; }
.status-devolvido { color: #6b7280; text-decoration: line-through; }
.status-parcial { color: #f59e0b; font-weight: bold; }

.footer {
    text-align: center;
    margin-top: 30px;
    font-size: 10px;
    color: #666;
}
</style>
</head>

<body>

<div class="header">
    <h1>AutoGest - Relatório de Vendas</h1>
    <p>Loja <?= htmlspecialchars($numero_loja) ?></p>
    <p>Gerado em <?= date('d/m/Y H:i:s') ?></p>
</div>

<div class="info-box">
    <div class="info-item">
        <h3>Total Vendas</h3>
        <p>R$ <?= number_format($total_vendas,2,',','.') ?></p>
    </div>

    <div class="info-item">
        <h3>Vendas Pagas</h3>
        <p>R$ <?= number_format($total_pago,2,',','.') ?></p>
    </div>

    <div class="info-item">
        <h3>A Receber</h3>
        <p>R$ <?= number_format($total_pendente,2,',','.') ?></p>
    </div>

    <div class="info-item">
        <h3>Devoluções</h3>
        <p>R$ <?= number_format($total_devolvido,2,',','.') ?></p>
    </div>

    <div class="info-item">
        <h3>POS</h3>
        <p>R$ <?= number_format($total_pos,2,',','.') ?></p>
    </div>
</div>

<table>
<thead>
<tr>
    <th>Data/Hora</th>
    <th>Cliente</th>
    <th>Valor</th>
    <th>Autozoner</th>
    <th>Pagamento</th>
    <th>Status</th>
</tr>
</thead>

<tbody>
<?php foreach ($vendas as $v):

$pago = !empty($v['pago']);
$status = $v['status'] ?? 'normal';

$status_class = 'status-pendente';
$status_text = 'Pendente';

if ($status === 'devolvido') {
    $status_class = 'status-devolvido';
    $status_text = 'Devolvido';
} elseif ($status === 'parcial') {
    $status_class = 'status-parcial';
    $status_text = 'Parcial';
} elseif ($pago) {
    $status_class = 'status-pago';
    $status_text = 'Pago';
}
?>
<tr>
<td><?= date('d/m/Y H:i', strtotime($v['data_venda'])) ?></td>
<td><?= htmlspecialchars($v['cliente']) ?></td>
<td>R$ <?= number_format($v['valor_total'],2,',','.') ?></td>
<td><?= htmlspecialchars($v['autozoner_nome'] ?? '-') ?></td>
<td><?= htmlspecialchars($v['forma_pagamento']) ?></td>
<td class="<?= $status_class ?>"><?= $status_text ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<div class="footer">
    AutoGest - Sistema de Gestão Automotiva | <?= date('Y') ?><br>
    Total de <?= count($vendas) ?> vendas registradas
</div>

<script>
window.onload = function(){
    window.print();
}
</script>

</body>
</html>
