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
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            text-align: center;
        }

        /* Container ampliado */
        .container {
            width: 95%;             /* Aumenta a largura para 95% da tela */
            max-width: 1000px;      /* Define uma largura máxima de 1000px para telas grandes */
            margin: 20px auto;      /* Centraliza horizontalmente com margem de 25px */
            background: white;
            padding: 30px;          /* Aumenta o espaçamento interno */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            margin: 10px 5px;
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
            text-align: center;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }

        th {
            background-color: #f4f4f4;
            font-size: 16px;
        }

        td {
            font-size: 15px;
        }

        .acoes {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .acoes a {
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