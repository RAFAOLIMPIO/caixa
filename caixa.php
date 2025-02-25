<?php
include 'includes/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar autenticação
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$loja_id = (int)$_SESSION['usuario']['id'];
$erros = [];
$sucesso = '';

// =============================================
// 1. PROCESSAR NOVA VENDA
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente = htmlspecialchars(trim($_POST['cliente'] ?? ''));
    $forma_pagamento = in_array($_POST['forma_pagamento'] ?? '', ['cartao', 'pix', 'dinheiro']) 
                        ? $_POST['forma_pagamento'] 
                        : '';
    $valor = (float)str_replace(',', '.', $_POST['valor'] ?? 0);
    $valor_recebido = (float)str_replace(',', '.', $_POST['valor_recebido'] ?? 0);
    $motoboy = htmlspecialchars(trim($_POST['motoboy'] ?? ''));
    $autozoner_id = (int)($_POST['autozoner_id'] ?? 0);

    // Validações
    if (empty($cliente)) $erros[] = "Nome do cliente é obrigatório!";
    if (empty($forma_pagamento)) $erros[] = "Selecione a forma de pagamento!";
    if ($valor <= 0) $erros[] = "Valor deve ser maior que zero!";
    
    if ($forma_pagamento === 'dinheiro' && $valor_recebido < $valor) {
        $erros[] = "Valor recebido insuficiente!";
    }

    if (empty($erros)) {
        try {
            $troco = ($forma_pagamento === 'dinheiro') ? ($valor_recebido - $valor) : 0;

            $stmt = $pdo->prepare("INSERT INTO vendas 
                (cliente, forma_pagamento, valor, valor_recebido, troco, motoboy, autozoner_id, loja_id) 
                VALUES (:cliente, :forma, :valor, :recebido, :troco, :motoboy, :autozoner, :loja)");
            
            $stmt->execute([
                ':cliente' => $cliente,
                ':forma' => $forma_pagamento,
                ':valor' => $valor,
                ':recebido' => ($forma_pagamento === 'dinheiro') ? $valor_recebido : null,
                ':troco' => $troco,
                ':motoboy' => $motoboy,
                ':autozoner' => $autozoner_id,
                ':loja' => $loja_id
            ]);

            $sucesso = "Venda registrada com sucesso!";

        } catch (PDOException $e) {
            $erros[] = "Erro ao registrar venda: " . $e->getMessage();
        }
    }
}

// =============================================
// 2. BUSCAR DADOS PARA O FORMULÁRIO (CORRIGIDO)
// =============================================
try {
    // Lista de Autozoners
    $stmt_autozoners = $pdo->prepare("SELECT id, nome FROM funcionarios 
                                    WHERE loja_id = ? AND tipo = 'autozoner'");
    $stmt_autozoners->execute([$loja_id]);
    $autozoners = $stmt_autozoners->fetchAll();

    // Lista de Motoboys
    $stmt_motoboys = $pdo->prepare("SELECT nome FROM funcionarios 
                                  WHERE loja_id = ? AND tipo = 'motoboy'");
    $stmt_motoboys->execute([$loja_id]);
    $motoboys = $stmt_motoboys->fetchAll(PDO::FETCH_COLUMN);

    // Últimas vendas
    $stmt_vendas = $pdo->prepare("SELECT *, DATE_FORMAT(criado_em, '%d/%m/%Y %H:%i') as data_formatada 
                                FROM vendas 
                                WHERE loja_id = ? 
                                ORDER BY criado_em DESC 
                                LIMIT 10");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Caixa</title>
    <!-- Incluindo o arquivo CSS -->
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <style>
        .container { max-width: 1200px; margin: 2rem auto; padding: 1rem; }
        .grid { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }
        .card { border: 1px solid #ddd; padding: 1rem; margin-bottom: 1rem; border-radius: 8px; }
        .form-section { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; }
        .dinheiro-section { display: none; }
        .alert { padding: 1rem; margin: 1rem 0; border-radius: 5px; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Controle de Caixa</h1>
        <a href="menu.php">&larr; Voltar ao Menu</a>

        <?php if (!empty($erros)): ?>
            <div class="alert alert-error">
                <?= implode('<br>', array_map('htmlspecialchars', $erros)) ?>
            </div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <div class="grid">
            <!-- Formulário de Venda -->
            <section class="form-section">
                <h2>Nova Venda</h2>
                <form method="POST" id="formVenda">
                    <div class="form-group">
                        <label>Cliente:</label>
                        <input type="text" name="cliente" required>
                    </div>

                    <div class="form-group">
                        <label>Autozoner Responsável:</label>
                        <select name="autozoner_id" required>
                            <?php foreach ($autozoners as $a): ?>
                                <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Forma de Pagamento:</label>
                        <select name="forma_pagamento" id="formaPagamento" required>
                            <option value="cartao">Cartão</option>
                            <option value="pix">PIX</option>
                            <option value="dinheiro">Dinheiro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Valor Total (R$):</label>
                        <input type="number" step="0.01" name="valor" required>
                    </div>

                    <div class="dinheiro-section" id="dinheiroSection">
                        <div class="form-group">
                            <label>Valor Recebido (R$):</label>
                            <input type="number" step="0.01" name="valor_recebido">
                        </div>
                        <div class="form-group">
                            <label>Troco (R$):</label>
                            <input type="number" step="0.01" name="troco" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Entregador:</label>
                        <select name="motoboy">
                            <option value="">Selecione</option>
                            <?php foreach ($motoboys as $m): ?>
                                <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
                            <?php endforeach; ?>
                            <option value="uber">Uber</option>
                            <option value="balcao">Balcão</option>
                        </select>
                    </div>

                    <button type="submit" class="btn">Registrar Venda</button>
                </form>
            </section>

            <!-- Últimas Vendas -->
            <section>
                <h2>Últimas 10 Vendas</h2>
                <?php foreach ($vendas as $venda): ?>
                    <div class="card">
                        <p><strong>Data:</strong> <?= $venda['data_formatada'] ?></p>
                        <p><strong>Cliente:</strong> <?= htmlspecialchars($venda['cliente']) ?></p>
                        <p><strong>Valor:</strong> R$ <?= number_format($venda['valor'], 2, ',', '.') ?></p>
                        <p><strong>Forma:</strong> <?= ucfirst($venda['forma_pagamento']) ?></p>
                        <?php if ($venda['forma_pagamento'] === 'dinheiro'): ?>
                            <p><small>Recebido: R$ <?= number_format($venda['valor_recebido'], 2, ',', '.') ?></small></p>
                            <p><small>Troco: R$ <?= number_format($venda['troco'], 2, ',', '.') ?></small></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        </div>
    </div>

    <script>
        // Controle dinâmico do campo de dinheiro
        const formaPagamento = document.getElementById('formaPagamento');
        const dinheiroSection = document.getElementById('dinheiroSection');

        formaPagamento.addEventListener('change', function() {
            dinheiroSection.style.display = this.value === 'dinheiro' ? 'block' : 'none';
            
            if (this.value === 'dinheiro') {
                document.querySelector('[name="valor_recebido"]').required = true;
            } else {
                document.querySelector('[name="valor_recebido"]').required = false;
            }
        });

        // Cálculo automático do troco
        document.querySelector('[name="valor_recebido"]')?.addEventListener('input', function() {
            const valor = parseFloat(document.querySelector('[name="valor"]').value) || 0;
            const recebido = parseFloat(this.value) || 0;
            document.querySelector('[name="troco"]').value = (recebido - valor).toFixed(2);
        });
    </script>
</body>
</html>