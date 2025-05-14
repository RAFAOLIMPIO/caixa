<?php
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$numero_loja = (int)$_SESSION['usuario']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'update_pago') {
        $stmt = $pdo->prepare("UPDATE vendas SET pago = ? WHERE id = ? AND numero_loja = ?");
        $stmt->execute([(int)$_POST['pago'], (int)$_POST['id'], $numero_loja]);
        exit();
    }

    if ($_POST['action'] === 'update_obs') {
        $stmt = $pdo->prepare("UPDATE vendas SET obs = ? WHERE id = ? AND numero_loja = ?");
        $stmt->execute([$_POST['obs'], (int)$_POST['id'], $numero_loja]);
        exit();
    }
}

try {
    $stmt = $pdo->prepare("SELECT 
        v.*, 
        DATE_FORMAT(v.data, '%d/%m/%Y %H:%i') as data_formatada,
        COALESCE(f.nome, 'Não informado') as autozoner_nome
        FROM vendas v
        LEFT JOIN funcionarios f ON v.autozoner_id = f.id
        WHERE v.numero_loja = ?
        ORDER BY v.data DESC
    ");
    $stmt->execute([$numero_loja]);
    $vendas = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erro ao buscar vendas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Vendas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-black text-white min-h-screen p-4">
<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold"><i class="fas fa-chart-line mr-2"></i>Relatório de Vendas</h1>
        <a href="menu.php" class="text-sm text-purple-400 hover:underline"><i class="fas fa-arrow-left"></i> Voltar ao Menu</a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-800">
                    <th class="p-3">Data</th>
                    <th class="p-3">Cliente</th>
                    <th class="p-3">Valor</th>
                    <th class="p-3">Pagamento</th>
                    <th class="p-3">Autozoner</th>
                    <th class="p-3">Motoboy</th>
                    <th class="p-3">Pago</th>
                    <th class="p-3">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vendas as $venda): ?>
                    <tr class="border-b border-gray-700 hover:bg-gray-800">
                        <td class="p-3"><?= $venda['data_formatada'] ?></td>
                        <td class="p-3 relative group">
                            <span class="cursor-pointer">
                                <?= htmlspecialchars($venda['cliente']) ?>
                                <?php if (!empty($venda['obs'])): ?>
                                    <span class="text-red-500 font-bold ml-1">!</span>
                                <?php endif; ?>
                            </span>
                            <textarea 
                                class="absolute hidden group-hover:block top-full left-0 mt-2 bg-gray-900 text-white p-2 rounded w-64 z-10"
                                placeholder="Digite uma observação..."
                                onblur="salvarObs(<?= $venda['id'] ?>, this.value)"
                            ><?= htmlspecialchars($venda['obs']) ?></textarea>
                        </td>
                        <td class="p-3">R$ <?= number_format($venda['valor'], 2, ',', '.') ?></td>
                        <td class="p-3"><?= ucfirst($venda['forma_pagamento']) ?></td>
                        <td class="p-3"><?= htmlspecialchars($venda['autozoner_nome']) ?></td>
                        <td class="p-3"><?= htmlspecialchars($venda['motoboy']) ?></td>
                        <td class="p-3 text-center">
                            <input type="checkbox" onchange="atualizarPago(<?= $venda['id'] ?>, this.checked)" <?= $venda['pago'] ? 'checked' : '' ?>>
                            <?php if ($venda['pago']): ?>
                                <i class="fas fa-check-circle text-green-400 ml-1"></i>
                            <?php endif; ?>
                        </td>
                        <td class="p-3 flex space-x-2">
                            <a href="relatorio.php?action=edit&id=<?= $venda['id'] ?>" class="text-green-400 hover:text-green-300"><i class="fas fa-edit"></i></a>
                            <a href="relatorio.php?action=delete&id=<?= $venda['id'] ?>" onclick="return confirm('Deseja excluir esta venda?')" class="text-red-400 hover:text-red-300"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function atualizarPago(id, marcado) {
    $.post("relatorio.php", {
        action: 'update_pago',
        id: id,
        pago: marcado ? 1 : 0
    });
}

function salvarObs(id, valor) {
    $.post("relatorio.php", {
        action: 'update_obs',
        id: id,
        obs: valor
    });
}
</script>
</body>
</html>