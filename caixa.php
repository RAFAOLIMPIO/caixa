<?php
include 'includes/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// ... (mantenha o código PHP original sem alterações) ...

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Caixa</title>
    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="main-container">
        <main class="content-area">
            <div class="card">
                <div class="flex-header">
                    <h1 class="brand-title">
                        <i class="fas fa-cash-register"></i>
                        Controle de Caixa
                    </h1>
                    <a href="menu.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Voltar
                    </a>
                </div>

                <?php if (!empty($erros)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= implode('<br>', array_map('htmlspecialchars', $erros)) ?>
                    </div>
                <?php endif; ?>

                <?php if ($sucesso): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($sucesso) ?>
                    </div>
                <?php endif; ?>

                <div class="grid-col-2">
                    <!-- Formulário de Venda -->
                    <section class="card">
                        <h2 class="section-title">
                            <i class="fas fa-file-invoice-dollar"></i>
                            Nova Venda
                        </h2>
                        
                        <form method="POST" id="formVenda" class="form-stack">
                            <div class="form-group">
                                <label class="input-label">
                                    <i class="fas fa-user"></i>
                                    Cliente
                                </label>
                                <input type="text" name="cliente" class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label class="input-label">
                                    <i class="fas fa-id-badge"></i>
                                    Autozoner Responsável
                                </label>
                                <select name="autozoner_id" class="form-input" required>
                                    <?php foreach ($autozoners as $a): ?>
                                        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="input-label">
                                    <i class="fas fa-money-check-alt"></i>
                                    Forma de Pagamento
                                </label>
                                <select name="forma_pagamento" id="formaPagamento" class="form-input" required>
                                    <option value="cartao">Cartão</option>
                                    <option value="pix">PIX</option>
                                    <option value="dinheiro">Dinheiro</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="input-label">
                                    <i class="fas fa-coins"></i>
                                    Valor Total (R$)
                                </label>
                                <input type="number" step="0.01" name="valor" class="form-input" required>
                            </div>

                            <div class="dinheiro-section" id="dinheiroSection">
                                <div class="form-group">
                                    <label class="input-label">
                                        <i class="fas fa-hand-holding-usd"></i>
                                        Valor Recebido (R$)
                                    </label>
                                    <input type="number" step="0.01" name="valor_recebido" class="form-input">
                                </div>
                                
                                <div class="form-group">
                                    <label class="input-label">
                                        <i class="fas fa-exchange-alt"></i>
                                        Troco (R$)
                                    </label>
                                    <input type="number" step="0.01" name="troco" class="form-input" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="input-label">
                                    <i class="fas fa-motorcycle"></i>
                                    Entregador
                                </label>
                                <select name="motoboy" class="form-input">
                                    <option value="">Selecione</option>
                                    <?php foreach ($motoboys as $m): ?>
                                        <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
                                    <?php endforeach; ?>
                                    <option value="uber">Uber</option>
                                    <option value="balcao">Balcão</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary hover-scale">
                                <i class="fas fa-save"></i>
                                Registrar Venda
                            </button>
                        </form>
                    </section>

                    <!-- Últimas Vendas -->
                    <section class="card">
                        <h2 class="section-title">
                            <i class="fas fa-history"></i>
                            Últimas Vendas
                        </h2>
                        
                        <div class="vendas-list">
                            <?php foreach ($vendas as $venda): ?>
                                <div class="venda-item">
                                    <div class="venda-header">
                                        <span class="venda-date"><?= $venda['data_formatada'] ?></span>
                                        <span class="badge badge-<?= $venda['forma_pagamento'] ?>">
                                            <?= ucfirst($venda['forma_pagamento']) ?>
                                        </span>
                                    </div>
                                    <div class="venda-body">
                                        <p class="venda-cliente"><?= htmlspecialchars($venda['cliente']) ?></p>
                                        <p class="venda-valor">R$ <?= number_format($venda['valor'], 2, ',', '.') ?></p>
                                    </div>
                                    <?php if ($venda['forma_pagamento'] === 'dinheiro'): ?>
                                        <div class="venda-details">
                                            <small>Recebido: R$ <?= number_format($venda['valor_recebido'], 2, ',', '.') ?></small>
                                            <small>Troco: R$ <?= number_format($venda['troco'], 2, ',', '.') ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mantenha o JavaScript original com ajuste nos seletores
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

        document.querySelector('[name="valor_recebido"]')?.addEventListener('input', function() {
            const valor = parseFloat(document.querySelector('[name="valor"]').value) || 0;
            const recebido = parseFloat(this.value) || 0;
            document.querySelector('[name="troco"]').value = (recebido - valor).toFixed(2);
        });
    </script>
</body>
</html>