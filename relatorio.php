
<?php
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$loja_id = (int)$_SESSION['usuario']['id'];
$erros = [];
$sucesso = '';

// Processamento de exclusão
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt_delete = $pdo->prepare("DELETE FROM vendas WHERE id = ? AND loja_id = ?");
        $stmt_delete->execute([$id, $loja_id]);
        $_SESSION['sucesso'] = "Venda excluída com sucesso.";
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao excluir a venda: " . $e->getMessage();
    }
    header("Location: relatorio.php");
    exit();
}

// Processamento de atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update') {
    $id = (int)$_POST['id'];
    $cliente = $_POST['cliente'] ?? '';
    $valor = $_POST['valor'] ?? '';
    $forma_pagamento = $_POST['forma_pagamento'] ?? '';
    $motoboy = $_POST['motoboy'] ?? '';
    try {
        $stmt_update = $pdo->prepare("UPDATE vendas SET cliente = ?, valor = ?, forma_pagamento = ?, motoboy = ? WHERE id = ? AND loja_id = ?");
        $stmt_update->execute([$cliente, $valor, $forma_pagamento, $motoboy, $id, $loja_id]);
        $_SESSION['sucesso'] = "Venda atualizada com sucesso.";
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao atualizar a venda: " . $e->getMessage();
    }
    header("Location: relatorio.php");
    exit();
}

// Busca de vendas
$vendas = [];
try {
    $stmt_vendas = $pdo->prepare("SELECT 
        v.*, 
        DATE_FORMAT(v.criado_em, '%d/%m/%Y %H:%i') as data_formatada,
        COALESCE(f.nome, 'Não informado') as autozoner_nome
        FROM vendas v
        LEFT JOIN funcionarios f ON v.autozoner_id = f.id
        WHERE v.loja_id = ?
        ORDER BY v.criado_em DESC");
    $stmt_vendas->execute([$loja_id]);
    $vendas = $stmt_vendas->fetchAll();
} catch (PDOException $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Vendas</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Relatório de Vendas</h1>
        <a href="menu.php" class="btn">&larr; Voltar</a>

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
                <?php if (!empty($vendas)): ?>
                    <?php foreach ($vendas as $v): ?>
                    <tr>
                        <td><?= htmlspecialchars($v['data_formatada']) ?></td>
                        <td><?= htmlspecialchars($v['cliente']) ?></td>
                        <td>R$ <?= number_format($v['valor'], 2, ',', '.') ?></td>
                        <td><?= ucfirst($v['forma_pagamento']) ?></td>
                        <td><?= htmlspecialchars($v['autozoner_nome']) ?></td>
                        <td><?= htmlspecialchars($v['motoboy'] ?? 'Não informado') ?></td>
                        <td class="acoes">
                            <a href="relatorio.php?action=edit&id=<?= $v['id'] ?>" class="button button-edit">
                                <svg class="svgIcon" viewBox="0 0 512 512">
                                    <path d="M362.7 19.3L314.3 68.7 450.3 204.7l49.4-48.4c25-25 25-65.5 0-90.5L453.3 19.3c-25-25-65.5-25-90.5 0zM291.7 90.2L48 343.9V464h120.1l243.7-243.7-120.1-130zM120 432H80v-40h40v40z"/>
                                </svg>
                            </a>
                            <a href="relatorio.php?action=delete&id=<?= $v['id'] ?>" class="button button-delete" onclick="return confirm('Tem certeza?')">
                                <svg class="svgIcon" viewBox="0 0 448 512">
                                    <path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>