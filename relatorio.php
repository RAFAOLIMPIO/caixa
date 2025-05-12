<?php
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$numero_loja = (int)$_SESSION['usuario']['id'];

// Processar Exclusão
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

// Processar Edição
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

// Buscar Vendas (corrigido: criado_em → data)
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

// Verificar Edição
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
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Vendas</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="main-container">
        <main class="content-area">
            <div class="card">
                <div class="flex-header">
                    <h1 class="brand-title">
                        <i class="fas fa-chart-line"></i>
                        Relatório de Vendas
                    </h1>
                    <a href="menu.php" class="btn btn-secondary hover-scale">
                        <i class="fas fa-arrow-left"></i>
                        Voltar
                    </a>
                </div>

                <?php if (isset($_SESSION['erro'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($_SESSION['erro']) ?>
                        <?php unset($_SESSION['erro']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['sucesso'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($_SESSION['sucesso']) ?>
                        <?php unset($_SESSION['sucesso']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($venda_editar)): ?>
                <div class="modal-overlay active">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="brand-title">
                                <i class="fas fa-edit"></i>
                                Editar Venda
                            </h2>
                            <a href="relatorio.php" class="btn btn-icon">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                        
                        <form method="POST" class="form-stack">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= $venda_editar['id'] ?>">
                            
                            <div class="form-group">
                                <label class="input-label">
                                    <i class="fas fa-user"></i>
                                    Cliente
                                </label>
                                <input type="text" 
                                       name="cliente" 
                                       class="form-input"
                                       value="<?= htmlspecialchars($venda_editar['cliente']) ?>" 
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label class="input-label">
                                    <i class="fas fa-coins"></i>
                                    Valor
                                </label>
                                <input type="number" 
                                       step="0.01" 
                                       name="valor" 
                                       class="form-input"
                                       value="<?= $venda_editar['valor'] ?>" 
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label class="input-label">
                                    <i class="fas fa-money-bill-wave"></i>
                                    Forma de Pagamento
                                </label>
                                <div class="select-wrapper">
                                    <select name="forma_pagamento" class="form-input" required>
                                        <option value="pix" <?= $venda_editar['forma_pagamento'] === 'pix' ? 'selected' : '' ?>>PIX</option>
                                        <option value="credito" <?= $venda_editar['forma_pagamento'] === 'credito' ? 'selected' : '' ?>>Cartão de Crédito</option>
                                        <option value="debito" <?= $venda_editar['forma_pagamento'] === 'debito' ? 'selected' : '' ?>>Cartão de Débito</option>
                                        <option value="dinheiro" <?= $venda_editar['forma_pagamento'] === 'dinheiro' ? 'selected' : '' ?>>Dinheiro</option>
                                    </select>
                                    <i class="fas fa-chevron-down select-arrow"></i>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="input-label">
                                    <i class="fas fa-motorcycle"></i>
                                    Motoboy
                                </label>
                                <input type="text" 
                                       name="motoboy" 
                                       class="form-input"
                                       value="<?= htmlspecialchars($venda_editar['motoboy']) ?>">
                            </div>
                            
                            <div class="form-group grid-col-2">
                                <button type="submit" class="btn btn-primary hover-scale">
                                    <i class="fas fa-save"></i>
                                    Salvar
                                </button>
                                <a href="relatorio.php" class="btn btn-danger hover-scale">
                                    <i class="fas fa-times"></i>
                                    Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-calendar-alt"></i> Data</th>
                                <th><i class="fas fa-user"></i> Cliente</th>
                                <th><i class="fas fa-dollar-sign"></i> Valor</th>
                                <th><i class="fas fa-wallet"></i> Pagamento</th>
                                <th><i class="fas fa-user-tie"></i> Autozoner</th>
                                <th><i class="fas fa-motorcycle"></i> Motoboy</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vendas as $venda): ?>
                            <tr>
                                <td><?= htmlspecialchars($venda['data_formatada']) ?></td>
                                <td><?= htmlspecialchars($venda['cliente']) ?></td>
                                <td>R$ <?= number_format($venda['valor'], 2, ',', '.') ?></td>
                                <td>
                                    <span class="badge badge-<?= $venda['forma_pagamento'] ?>">
                                        <?= ucfirst($venda['forma_pagamento']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($venda['autozoner_nome']) ?></td>
                                <td><?= htmlspecialchars($venda['motoboy'] ?? 'Não informado') ?></td>
                                <td>
                                    <div class="flex-actions">
                                        <a href="relatorio.php?action=edit&id=<?= $venda['id'] ?>" 
                                           class="btn btn-success hover-scale btn-icon"
                                           data-tooltip="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="relatorio.php?action=delete&id=<?= $venda['id'] ?>" 
                                           class="btn btn-danger hover-scale btn-icon"
                                           data-tooltip="Excluir"
                                           onclick="return confirm('Tem certeza que deseja excluir esta venda?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
