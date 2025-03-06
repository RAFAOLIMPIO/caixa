<?php
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$loja_id = (int)$_SESSION['usuario']['id'];

// Processar Exclusão
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM vendas WHERE id = ? AND loja_id = ?");
        $stmt->execute([$id, $loja_id]);
        $_SESSION['sucesso'] = "Venda excluída com sucesso!";
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao excluir: " . $e->getMessage();
    }
    header("Location: relatorio.php");
    exit();
}

// Processar Edição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $dados = [
        'cliente' => $_POST['cliente'] ?? '',
        'valor' => (float)$_POST['valor'],
        'forma_pagamento' => $_POST['forma_pagamento'] ?? '',
        'motoboy' => $_POST['motoboy'] ?? '',
        'id' => (int)$_POST['id'],
        'loja_id' => $loja_id
    ];

    try {
        $stmt = $pdo->prepare("UPDATE vendas SET 
            cliente = :cliente,
            valor = :valor,
            forma_pagamento = :forma_pagamento,
            motoboy = :motoboy
            WHERE id = :id AND loja_id = :loja_id
        ");
        $stmt->execute($dados);
        $_SESSION['sucesso'] = "Venda atualizada com sucesso!";
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao atualizar: " . $e->getMessage();
    }
    header("Location: relatorio.php");
    exit();
}

// Buscar Vendas
try {
    $stmt = $pdo->prepare("SELECT 
        v.*,
        DATE_FORMAT(v.criado_em, '%d/%m/%Y %H:%i') as data_formatada,
        COALESCE(f.nome, 'Não informado') as autozoner_nome
        FROM vendas v
        LEFT JOIN funcionarios f ON v.autozoner_id = f.id
        WHERE v.loja_id = ?
        ORDER BY v.criado_em DESC
    ");
    $stmt->execute([$loja_id]);
    $vendas = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erro ao buscar vendas: " . $e->getMessage());
}

// Verificar Edição
$venda_editar = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM vendas WHERE id = ? AND loja_id = ?");
        $stmt->execute([$_GET['id'], $loja_id]);
        $venda_editar = $stmt->fetch();
    } catch (PDOException $e) {
        die("Erro ao buscar venda: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Vendas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>📊 Relatório de Vendas</h1>
        
        <?php if (isset($venda_editar)): ?>
        <div class="modal-edicao">
            <h2>✏️ Editar Venda</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= $venda_editar['id'] ?>">
                
                <div class="form-group">
                    <input type="text" name="cliente" value="<?= htmlspecialchars($venda_editar['cliente']) ?>" 
                           placeholder="Nome do Cliente" required>
                </div>
                
                <div class="form-group">
                    <input type="number" step="0.01" name="valor" 
                           value="<?= $venda_editar['valor'] ?>" placeholder="Valor" required>
                </div>
                
                <div class="form-group">
                    <select name="forma_pagamento" required>
                        <option value="pix" <?= $venda_editar['forma_pagamento'] === 'pix' ? 'selected' : '' ?>>PIX</option>
                        <option value="credito" <?= $venda_editar['forma_pagamento'] === 'credito' ? 'selected' : '' ?>>Cartão de Crédito</option>
                        <option value="debito" <?= $venda_editar['forma_pagamento'] === 'debito' ? 'selected' : '' ?>>Cartão de Débito</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <input type="text" name="motoboy" 
                           value="<?= htmlspecialchars($venda_editar['motoboy']) ?>" 
                           placeholder="Nome do Motoboy">
                </div>
                
                <div class="form-group" style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    <a href="relatorio.php" class="btn btn-cancelar">Cancelar</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Valor</th>
                        <th>Pagamento</th>
                        <th>Autozoner</th>
                        <th>Motoboy</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendas as $venda): ?>
                    <tr>
                        <td><?= htmlspecialchars($venda['data_formatada']) ?></td>
                        <td><?= htmlspecialchars($venda['cliente']) ?></td>
                        <td>R$ <?= number_format($venda['valor'], 2, ',', '.') ?></td>
                        <td><?= ucfirst($venda['forma_pagamento']) ?></td>
                        <td><?= htmlspecialchars($venda['autozoner_nome']) ?></td>
                        <td><?= htmlspecialchars($venda['motoboy'] ?? 'Não informado') ?></td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="relatorio.php?action=edit&id=<?= $venda['id'] ?>" 
                                   class="button button-edit" data-tooltip="Editar">
                                    <svg class="svgIcon" viewBox="0 0 512 512">
                                        <path d="M362.7 19.3L314.3 68.7 450.3 204.7l49.4-48.4c25-25 25-65.5 0-90.5L453.3 19.3c-25-25-65.5-25-90.5 0zM291.7 90.2L48 343.9V464h120.1l243.7-243.7-120.1-130zM120 432H80v-40h40v40z"/>
                                    </svg>
                                </a>
                                <a href="relatorio.php?action=delete&id=<?= $venda['id'] ?>" 
                                   class="button button-delete" data-tooltip="Excluir">
                                    <svg class="svgIcon" viewBox="0 0 448 512">
                                        <path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>