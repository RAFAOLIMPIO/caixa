<?php  
require_once __DIR__ . '/includes/config.php';  
  
if (!isset($_SESSION['usuario'])) {  
    header("Location: login.php");  
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
  
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {  
    $dados = [  
        'cliente' => $_POST['cliente'] ?? '',  
        'valor' => (float)$_POST['valor'],  
        'forma_pagamento' => $_POST['forma_pagamento'] ?? '',  
        'motoboy' => $_POST['motoboy'] ?? '',  
        'id' => (int)$_POST['id'],  
        'numero_loja' => $numero_loja  
    ];  
  
    try {  
        $stmt = $pdo->prepare("UPDATE vendas SET   
            cliente = :cliente,  
            valor = :valor,  
            forma_pagamento = :forma_pagamento,  
            motoboy = :motoboy  
            WHERE id = :id AND numero_loja = :numero_loja  
        ");  
        $stmt->execute($dados);  
        $_SESSION['sucesso'] = "Venda atualizada com sucesso!";  
    } catch (PDOException $e) {  
        $_SESSION['erro'] = "Erro ao atualizar: " . $e->getMessage();  
    }  
    header("Location: relatorio.php");  
    exit();  
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
  
$venda_editar = null;  
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {  
    try {  
        $stmt = $pdo->prepare("SELECT * FROM vendas WHERE id = ? AND numero_loja = ?");  
        $stmt->execute([$_GET['id'], $numero_loja]);  
        $venda_editar = $stmt->fetch();  
    } catch (PDOException $e) {  
        die("Erro ao buscar venda: " . $e->getMessage());  
    }  
}  
?>  <!DOCTYPE html>  <html lang="pt-br">  
<head>  
    <meta charset="UTF-8">  
    <title>Relatório de Vendas</title>  
    <script src="https://cdn.tailwindcss.com"></script>  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">  
</head>  
<body class="bg-black text-white min-h-screen p-4">  
    <div class="max-w-7xl mx-auto">  
        <div class="flex justify-between items-center mb-6">  
            <h1 class="text-2xl font-bold"><i class="fas fa-chart-line mr-2"></i>Relatório de Vendas</h1>  
            <a href="menu.php" class="text-sm text-purple-400 hover:underline"><i class="fas fa-arrow-left"></i> Voltar ao Menu</a>  
        </div>  <?php if (isset($_SESSION['erro'])): ?>  
        <div class="bg-red-600 p-3 rounded mb-4"><?= htmlspecialchars($_SESSION['erro']); unset($_SESSION['erro']); ?></div>  
    <?php endif; ?>  
    <?php if (isset($_SESSION['sucesso'])): ?>  
        <div class="bg-green-600 p-3 rounded mb-4"><?= htmlspecialchars($_SESSION['sucesso']); unset($_SESSION['sucesso']); ?></div>  
    <?php endif; ?>  

    <?php if ($venda_editar): ?>  
        <div class="bg-gray-800 p-6 rounded mb-6">  
            <h2 class="text-lg font-bold mb-4"><i class="fas fa-edit"></i> Editar Venda</h2>  
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">  
                <input type="hidden" name="action" value="update">  
                <input type="hidden" name="id" value="<?= $venda_editar['id'] ?>">  
                <div>  
                    <label>Cliente</label>  
                    <input type="text" name="cliente" value="<?= htmlspecialchars($venda_editar['cliente']) ?>" class="w-full bg-gray-700 p-2 rounded" required>  
                </div>  
                <div>  
                    <label>Valor</label>  
                    <input type="number" name="valor" step="0.01" value="<?= $venda_editar['valor'] ?>" class="w-full bg-gray-700 p-2 rounded" required>  
                </div>  
                <div>  
                    <label>Forma de Pagamento</label>  
                    <select name="forma_pagamento" class="w-full bg-gray-700 p-2 rounded" required>  
                        <option value="pix" <?= $venda_editar['forma_pagamento'] === 'pix' ? 'selected' : '' ?>>PIX</option>  
                        <option value="credito" <?= $venda_editar['forma_pagamento'] === 'credito' ? 'selected' : '' ?>>Crédito</option>  
                        <option value="debito" <?= $venda_editar['forma_pagamento'] === 'debito' ? 'selected' : '' ?>>Débito</option>  
                        <option value="dinheiro" <?= $venda_editar['forma_pagamento'] === 'dinheiro' ? 'selected' : '' ?>>Dinheiro</option>  
                    </select>  
                </div>  
                <div>  
                    <label>Motoboy</label>  
                    <input type="text" name="motoboy" value="<?= htmlspecialchars($venda_editar['motoboy']) ?>" class="w-full bg-gray-700 p-2 rounded">  
                </div>  
                <div class="col-span-2 flex justify-end space-x-4 mt-2">  
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded">Salvar</button>  
                    <a href="relatorio.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded">Cancelar</a>  
                </div>  
            </form>  
        </div>  
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
                    <th class="p-3">Ações</th>  
                </tr>  
            </thead>  
            <tbody>  
                <?php foreach ($vendas as $venda): ?>  
                    <tr class="border-b border-gray-700 hover:bg-gray-800">  
                        <td class="p-3"><?= $venda['data_formatada'] ?></td>  
                        <td class="p-3"><?= htmlspecialchars($venda['cliente']) ?></td>  
                        <td class="p-3">R$ <?= number_format($venda['valor'], 2, ',', '.') ?></td>  
                        <td class="p-3"><?= ucfirst($venda['forma_pagamento']) ?></td>  
                        <td class="p-3"><?= htmlspecialchars($venda['autozoner_nome']) ?></td>  
                        <td class="p-3"><?= htmlspecialchars($venda['motoboy']) ?></td>  
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

</body>  
</html>  