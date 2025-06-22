<?php
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$numero_loja = (int)$_SESSION['usuario']['id'];

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM vendas WHERE id = ? AND numero_loja = ?");
        $stmt->execute([$id, $numero_loja]);
        $_SESSION['sucesso'] = "Venda excluída com sucesso!";
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao excluir: " . $e->getMessage();
    }
    header("Location: relatorio.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'clear_all') {
    try {
        $stmt = $pdo->prepare("DELETE FROM vendas WHERE numero_loja = ?");
        $stmt->execute([$numero_loja]);
        $_SESSION['sucesso'] = "Todas as vendas foram apagadas.";
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao apagar todas as vendas: " . $e->getMessage();
    }
    header("Location: relatorio.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_obs'])) {
        $obs = $_POST['obs'] ?? '';
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("UPDATE vendas SET obs = ? WHERE id = ? AND numero_loja = ?");
            $stmt->execute([$obs, $id, $numero_loja]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo "Erro ao salvar observação: " . $e->getMessage();
        }
        exit();
    } elseif (isset($_POST['toggle_pago'])) {
        $pago = isset($_POST['pago']) ? 1 : 0;
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("UPDATE vendas SET pago = ? WHERE id = ? AND numero_loja = ?");
            $stmt->execute([$pago, $id, $numero_loja]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo "Erro ao atualizar status pago: " . $e->getMessage();
        }
        exit();
    }
}

try {
    $stmt = $pdo->prepare("SELECT v.*, TO_CHAR(v.data, 'DD/MM/YYYY HH24:MI') as data_formatada, COALESCE(f.nome, 'Não informado') as autozoner_nome 
                           FROM vendas v 
                           LEFT JOIN funcionarios f ON v.autozoner_id = f.id 
                           WHERE v.numero_loja = ? 
                           ORDER BY v.data DESC");
    $stmt->execute([$numero_loja]);
    $vendas = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erro ao buscar vendas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Relatório de Vendas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-black text-white min-h-screen p-4">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold"><i class="fas fa-chart-line mr-2"></i>Relatório de Vendas</h1>
            <a href="menu.php" class="text-sm text-purple-400 hover:underline"><i class="fas fa-arrow-left"></i> Voltar ao Menu</a>
        </div>

        <?php if (isset($_SESSION['erro'])): ?>
            <div class="bg-red-600 p-3 rounded mb-4" id="msg-erro"><?= htmlspecialchars($_SESSION['erro']); unset($_SESSION['erro']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['sucesso'])): ?>
            <div class="bg-green-600 p-3 rounded mb-4" id="msg-sucesso"><?= htmlspecialchars($_SESSION['sucesso']); unset($_SESSION['sucesso']); ?></div>
        <?php endif; ?>

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
                        <tr class="border-b border-gray-700 hover:bg-gray-800 <?= $venda['pago'] ? 'bg-green-900' : '' ?>">
                            <td class="p-3"><?= $venda['data_formatada'] ?></td>
                            <td class="p-3">
                                <span class="relative group cursor-pointer">
                                    <?= htmlspecialchars($venda['cliente']) ?>
                                    <?php if (!empty($venda['obs'])): ?>
                                        <span class="text-red-500 font-bold ml-1">❗</span>
                                    <?php endif; ?>
                                    <textarea class="absolute z-10 hidden group-hover:block w-64 p-2 bg-gray-700 text-white rounded shadow top-full mt-1" onblur="salvarObs(<?= $venda['id'] ?>, this.value)"><?= htmlspecialchars($venda['obs'] ?? '') ?></textarea>
                                </span>
                            </td>
                            <td class="p-3">R$ <?= number_format($venda['valor'], 2, ',', '.') ?></td>
                            <td class="p-3"><?= ucfirst($venda['forma_pagamento']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($venda['autozoner_nome']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($venda['motoboy']) ?></td>
                            <td class="p-3 text-center">
                                <input type="checkbox" onchange="togglePago(<?= $venda['id'] ?>, this.checked)" <?= $venda['pago'] ? 'checked' : '' ?> class="accent-green-500" />
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

        <form method="get" action="relatorio.php" class="flex justify-end mt-6">
            <input type="hidden" name="action" value="clear_all" />
            <button type="submit" class="bg-red-600 hover:bg-red-700 px-6 py-2 rounded-xl text-white">🧹 Limpar Tudo</button>
        </form>
    </div>

    <script>
        function salvarObs(id, valor) {
            const formData = new FormData();
            formData.append('update_obs', 1);
            formData.append('id', id);
            formData.append('obs', valor);
            fetch('relatorio.php', { method: 'POST', body: formData });
        }
        function togglePago(id, status) {
            const formData = new FormData();
            formData.append('toggle_pago', 1);
            formData.append('id', id);
            if (status) formData.append('pago', 1);
            fetch('relatorio.php', { method: 'POST', body: formData }).then(() => location.reload());
        }
        setTimeout(() => {
            document.getElementById('msg-sucesso')?.remove();
            document.getElementById('msg-erro')?.remove();
        }, 5000);
    </script>
</body>
</html>
