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
$vendas = [];
$autozoners = [];
$motoboys = [];
$editing = false;

// =============================================
// PROCESSAR AÇÕES (DELETE, EDITAR)
// =============================================
if (isset($_GET['action'])) {
    // DELETE
    if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM vendas WHERE id = ? AND loja_id = ?");
            $stmt->execute([$id, $loja_id]);
            $_SESSION['sucesso'] = "Venda excluída com sucesso!";
        } catch (PDOException $e) {
            $erros[] = "Erro ao excluir: " . $e->getMessage();
        }
        header("Location: relatorio.php");
        exit();
    }
}

// =============================================
// BUSCAR DADOS
// =============================================
try {
    $stmt_vendas = $pdo->prepare("SELECT 
        v.*, 
        DATE_FORMAT(v.criado_em, '%d/%m/%Y %H:%i') as data_formatada,
        COALESCE(f.nome, 'Não informado') as autozoner_nome
        FROM vendas v
        LEFT JOIN funcionarios f ON v.autozoner_id = f.id AND f.tipo = 'autozoner'
        WHERE v.loja_id = ?
        ORDER BY v.criado_em DESC");

    $stmt_vendas->execute([$loja_id]);
    $vendas = $stmt_vendas->fetchAll();
} catch (PDOException $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}

// Mensagens de sucesso
if (isset($_SESSION['sucesso'])) {
    $sucesso = $_SESSION['sucesso'];
    unset($_SESSION['sucesso']);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Vendas</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <style>
        .container {
            width: 80%;
            margin: auto;
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            margin: 10px;
            text-decoration: none;
            color: white;
            background-color: #007bff;
            border-radius: 5px;
            transition: 0.3s;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 15px;
            text-align: center;
        }

        th {
            background-color: #f4f4f4;
            font-size: 16px;
        }

        td {
            font-size: 15px;
        }

        .acoes a {
            margin: 5px;
            padding: 8px 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Relatório de Vendas</h1>
        <a href="menu.php" class="btn">&larr; Voltar</a>

        <?php if (!empty($erros)): ?>
            <div class="alert alert-error">
                <?= implode('<br>', array_map('htmlspecialchars', $erros)) ?>
            </div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th>Valor</th>
                    <th>Pagamento</th>
                    <th>Autozoner</th>
                    <th>Motoboy</th>
                    <th>Status</th>
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
                        <td>
                            <input 
                                type="checkbox" 
                                class="status-pago" 
                                data-venda-id="<?= $v['id'] ?>" 
                                <?= $v['pago'] ? 'checked' : '' ?>
                            >
                            <span class="status-text"><?= $v['pago'] ? 'Pago' : 'Pendente' ?></span>
                        </td>
                        <td class="acoes">
                            <a href="relatorio.php?action=edit&id=<?= $v['id'] ?>" class="btn editar">Editar</a>
                            <a href="relatorio.php?action=delete&id=<?= $v['id'] ?>" 
                               class="btn excluir" 
                               onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="sem-dados">Nenhuma venda registrada ainda</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        $('.status-pago').change(function() {
            const vendaId = $(this).data('venda-id');
            const isPago = $(this).is(':checked');
            
            $.ajax({
                url: 'atualizar_status.php',
                method: 'POST',
                data: {
                    id: vendaId,
                    pago: isPago ? 1 : 0
                },
                success: function(response) {
                    $(this).next('.status-text').text(isPago ? 'Pago' : 'Pendente');
                }.bind(this)
            });
        });
    });
    </script>
</body>
</html>