<?php
require_once __DIR__ . '/includes/config.php';

// Verificar autenticação
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$loja_id = (int)$_SESSION['usuario']['id'];
$erros = [];
$sucesso = '';

// Processar exclusão se action=delete e id estiverem na URL
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

// Processar atualização se o formulário for submetido
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

// Buscar vendas
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
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; text-align: center; }
        .container { width: 100%; max-width: 1000px; margin: 30px auto; background: white; padding: 30px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; text-align: center; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: center; }
        th { background-color: #f4f4f4; font-size: 16px; }
        .acoes { display: flex; justify-content: center; gap: 10px; }
        .button { width: 50px; height: 50px; border-radius: 50%; background-color: rgb(20, 20, 20); border: none; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 10px rgba(0, 0, 0, 0.164); cursor: pointer; transition: .3s; position: relative; }
        .button:hover { width: 140px; border-radius: 50px; align-items: center; }
        .button::before { position: absolute; top: -20px; color: white; font-size: 2px; transition: .3s; }
        .button:hover::before { font-size: 13px; opacity: 1; transform: translateY(30px); }
        .button-delete { background-color: rgb(255, 69, 69); }
        .button-delete::before { content: "Excluir"; }
        .button-edit { background-color: rgb(69, 130, 255); }
        .button-edit::before { content: "Editar"; }
        .svgIcon { width: 12px; transition-duration: .3s; }
    </style>
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
                                <svg class="svgIcon" viewBox="0 0 448 512">
                                    <path d="M362.7 19.3C375.9 6 394.1 0 412.3 0S448.7 6 461.9 19.3c26 26 26 68.2 0 94.2L168.3 407.1c-2.8 2.8-6.3 4.8-10.1 5.7l-99.8 23.2c-5.9 1.4-12-0.4-16.3-4.7s-6.1-10.3-4.7-16.3l23.2-99.8c.9-3.8 2.9-7.3 5.7-10.1L362.7 19.3z"></path>
                                </svg>
                            </a>
                            <a href="relatorio.php?action=delete&id=<?= $v['id'] ?>" class="button button-delete" onclick="return confirm('Tem certeza que deseja excluir?')">
                                <svg class="svgIcon" viewBox="0 0 448 512">
                                    <path d="M432 32H312l-9.4-18.7A24 24 0 0 0 280 0H168a24 24 0 0 0-22.6 13.3L136 32H16A16 16 0 0 0 0 48V80a16 16 0 0 0 16 16h16v336c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48V96h16a16 16 0 0 0 16-16V48a16 16 0 0 0-16-16z"></path>
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