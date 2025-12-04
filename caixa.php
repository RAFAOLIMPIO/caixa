<?php
include 'includes/config.php';

if (!isset($_SESSION['usuario']['id'])) {
    header("Location: index.php");
    exit();
}

$usuario_id = $_SESSION['usuario']['id'];
$numero_loja = $_SESSION['usuario']['numero_loja'];
$sucesso = '';
$erros = [];

// Buscar autozoners e motoboys
try {
    // Autozoners
    $stmtAuto = $pdo->prepare("SELECT id, nome FROM funcionarios WHERE usuario_id = ? AND tipo = 'autozoner' AND ativo = TRUE ORDER BY nome");
    $stmtAuto->execute([$usuario_id]);
    $autozoners = $stmtAuto->fetchAll();
    
    // Motoboys
    $stmtMoto = $pdo->prepare("SELECT id, nome FROM funcionarios WHERE usuario_id = ? AND tipo = 'motoboy' AND ativo = TRUE ORDER BY nome");
    $stmtMoto->execute([$usuario_id]);
    $motoboys = $stmtMoto->fetchAll();
} catch (Exception $e) {
    $autozoners = [];
    $motoboys = [];
    error_log("Erro ao buscar funcionários: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente = sanitizar($_POST['cliente'] ?? '');
    $valor = floatval(str_replace(['.', ','], ['', '.'], $_POST['valor'] ?? 0));
    $valor_pago = !empty($_POST['valor_pago']) ? floatval(str_replace(['.', ','], ['', '.'], $_POST['valor_pago'])) : 0;
    $forma_pagamento = sanitizar($_POST['forma_pagamento'] ?? '');
    $motoboy_id = sanitizar($_POST['motoboy_id'] ?? '');
    $obs = sanitizar($_POST['obs'] ?? '');
    $autozoner_id = (int)($_POST['autozoner_id'] ?? 0);

    // Calcular troco apenas se for dinheiro e valor pago > 0
    $troco = 0;
    if ($forma_pagamento === 'Dinheiro' && $valor_pago > 0) {
        $troco = $valor_pago - $valor;
        if ($troco < 0) $troco = 0;
    }

    // Validações
    if (empty($cliente)) $erros[] = "Cliente é obrigatório.";
    if ($valor <= 0) $erros[] = "Valor deve ser maior que zero.";
    if ($autozoner_id <= 0) $erros[] = "Autozoner é obrigatório.";
    if ($forma_pagamento === 'Dinheiro' && $valor_pago < $valor) {
        $erros[] = "Valor pago não pode ser menor que o valor da venda.";
    }

    // Verificar se existe pelo menos um autozoner cadastrado
    if (empty($autozoners)) {
        $erros[] = "É necessário cadastrar pelo menos um autozoner antes de registrar vendas.";
    }

    if (empty($erros)) {
        try {
            // Determinar o motoboy (se for entrega)
            $motoboy_final = 'Balcão'; // Valor padrão
            
            if (!empty($motoboy_id)) {
                if ($motoboy_id === 'uber') {
                    $motoboy_final = 'Uber';
                } else {
                    foreach ($motoboys as $motoboy) {
                        if ($motoboy['id'] == $motoboy_id) {
                            $motoboy_final = $motoboy['nome'];
                            break;
                        }
                    }
                }
            }

            $sql = "INSERT INTO vendas (cliente, valor, valor_pago, troco, forma_pagamento, motoboy, pago, usuario_id, numero_loja, autozoner_id, obs) 
                    VALUES (:cliente, :valor, :valor_pago, :troco, :forma_pagamento, :motoboy, :pago, :usuario_id, :numero_loja, :autozoner_id, :obs)";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                ':cliente' => $cliente,
                ':valor' => $valor,
                ':valor_pago' => $forma_pagamento === 'Dinheiro' ? $valor_pago : null,
                ':troco' => $troco,
                ':forma_pagamento' => $forma_pagamento,
                ':motoboy' => $motoboy_final,
                ':pago' => ($forma_pagamento === 'Dinheiro') ? 1 : 0,
                ':usuario_id' => $usuario_id,
                ':numero_loja' => $numero_loja,
                ':autozoner_id' => $autozoner_id,
                ':obs' => $obs
            ]);
            
            if ($result) {
                $sucesso = "Venda registrada com sucesso!";
                // Limpar o formulário após sucesso
                $_POST = [];
            } else {
                $erros[] = "Erro ao registrar venda. Tente novamente.";
            }
            
        } catch (PDOException $e) {
            // Tratamento mais específico de erros
            if (strpos($e->getMessage(), 'foreign key') !== false) {
                $erros[] = "Erro: Autozoner inválido. Verifique se o autozoner selecionado existe.";
            } else if (strpos($e->getMessage(), 'null value') !== false) {
                $erros[] = "Erro: Campos obrigatórios não preenchidos corretamente.";
            } else {
                $erros[] = "Erro ao salvar: " . $e->getMessage();
            }
            error_log("Erro PDO no caixa: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caixa - AutoGest</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .caixa-bg {
            background: linear-gradient(135deg, #000000 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
        }
        .troco-display {
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .required-field::after {
            content: " *";
            color: #ef4444;
        }
    </style>
</head>
<body class="caixa-bg">
    <div class="min-h-screen px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8 fade-in">
                <img src="logo.png" alt="AutoGest" class="mx-auto w-16 h-16 mb-4 rounded-full shadow-lg">
                <h1 class="text-3xl font-bold text-white mb-2">Controle de Caixa</h1>
                <p class="text-gray-400">Registro de Vendas - Loja <?= htmlspecialchars($numero_loja) ?></p>
            </div>

            <!-- Botões de Navegação -->
            <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
                <a href="menu.php" class="inline-flex items-center px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition duration-200 shadow-lg">
                    <i class="fas fa-arrow-left mr-2"></i> Voltar ao Menu
                </a>
                <a href="relatorio.php" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition duration-200 shadow-lg">
                    <i class="fas fa-chart-line mr-2"></i> Ver Relatórios
                </a>
            </div>

            <?php if ($sucesso): ?>
                <div class="bg-green-500 bg-opacity-20 border border-green-500 text-green-200 p-4 rounded-lg mb-6 fade-in shadow-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-3 text-xl"></i>
                        <div>
                            <p class="font-semibold"><?= htmlspecialchars($sucesso) ?></p>
                            <p class="text-sm opacity-90 mt-1">A venda foi registrada com sucesso no sistema.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($erros)): ?>
                <div class="bg-red-500 bg-opacity-20 border border-red-500 text-red-200 p-4 rounded-lg mb-6 fade-in shadow-lg">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-circle mr-3 text-xl mt-0.5"></i>
                        <div>
                            <p class="font-semibold mb-2">Erros encontrados:</p>
                            <?php foreach ($erros as $err): ?>
                                <p class="text-sm">• <?= htmlspecialchars($err) ?></p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Formulário de Venda -->
            <div class="glass-effect p-6 rounded-2xl mb-8 fade-in shadow-2xl">
                <h2 class="text-xl font-bold text-white mb-6 flex items-center">
                    <i class="fas fa-cash-register mr-3 text-purple-400"></i> 
                    Registrar Nova Venda
                </h2>

                <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-6" id="formVenda">
                    <!-- Coluna 1 -->
                    <div class="space-y-4">
                        <!-- Cliente -->
                        <div>
                            <label class="block text-white text-sm font-medium mb-2 required-field">
                                <i class="fas fa-user mr-2"></i>Cliente
                            </label>
                            <input type="text" name="cliente" value="<?= htmlspecialchars($_POST['cliente'] ?? '') ?>" required
                                class="input-modern" placeholder="Nome do cliente"
                                autocomplete="off">
                        </div>

                        <!-- Valor -->
                        <div>
                            <label class="block text-white text-sm font-medium mb-2 required-field">
                                <i class="fas fa-dollar-sign mr-2"></i>Valor da Venda
                            </label>
                            <input type="text" name="valor" id="valor" value="<?= htmlspecialchars($_POST['valor'] ?? '') ?>" required
                                class="input-modern" placeholder="0,00"
                                oninput="formatarMoeda(this); calcularTroco()"
                                autocomplete="off">
                        </div>

                        <!-- Forma de Pagamento -->
                        <div>
                            <label class="block text-white text-sm font-medium mb-2 required-field">
                                <i class="fas fa-credit-card mr-2"></i>Forma de Pagamento
                            </label>
                            <select name="forma_pagamento" id="forma_pagamento" required 
                                    class="input-modern" onchange="toggleCamposDinheiro()">
                                <option value="">Selecione...</option>
                                <option value="Dinheiro" <?= ($_POST['forma_pagamento'] ?? '') === 'Dinheiro' ? 'selected' : '' ?>>Dinheiro</option>
                                <option value="Cartão" <?= ($_POST['forma_pagamento'] ?? '') === 'Cartão' ? 'selected' : '' ?>>Cartão</option>
                                <option value="Pix" <?= ($_POST['forma_pagamento'] ?? '') === 'Pix' ? 'selected' : '' ?>>Pix</option>
                            </select>
                        </div>

                        <!-- Campos Dinheiro (inicialmente ocultos) -->
                        <div id="campos_dinheiro" style="display: none;" class="space-y-4 bg-gray-800 bg-opacity-30 p-4 rounded-lg border border-gray-700">
                            <div class="text-center mb-2">
                                <span class="text-yellow-400 text-sm font-semibold">
                                    <i class="fas fa-money-bill-wave mr-1"></i>PAGAMENTO EM DINHEIRO
                                </span>
                            </div>
                            
                            <!-- Valor Pago -->
                            <div>
                                <label class="block text-white text-sm font-medium mb-2">
                                    <i class="fas fa-money-bill-wave mr-2"></i>Valor Pago
                                </label>
                                <input type="text" name="valor_pago" id="valor_pago" 
                                    class="input-modern" placeholder="0,00"
                                    oninput="formatarMoeda(this); calcularTroco()"
                                    autocomplete="off">
                            </div>

                            <!-- Troco -->
                            <div>
                                <label class="block text-white text-sm font-medium mb-2">
                                    <i class="fas fa-exchange-alt mr-2"></i>Troco
                                </label>
                                <div id="troco_display" class="troco-display p-3 rounded-lg text-white font-bold text-center text-lg shadow-lg">
                                    R$ 0,00
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Coluna 2 -->
                    <div class="space-y-4">
                        <!-- Autozoner -->
                        <div>
                            <label class="block text-white text-sm font-medium mb-2 required-field">
                                <i class="fas fa-user-tie mr-2"></i>Autozoner
                            </label>
                            <select name="autozoner_id" required class="input-modern">
                                <option value="">Selecione o autozoner...</option>
                                <?php foreach ($autozoners as $a): ?>
                                    <option value="<?= $a['id'] ?>" <?= ($_POST['autozoner_id'] ?? '') == $a['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($a['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($autozoners)): ?>
                                <div class="mt-2 p-3 bg-yellow-500 bg-opacity-20 border border-yellow-500 rounded-lg">
                                    <p class="text-yellow-400 text-sm flex items-center">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        Nenhum autozoner cadastrado. 
                                        <a href="funcionarios.php" class="underline ml-1 font-semibold">Cadastre um autozoner</a>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Motoboy -->
                        <div>
                            <label class="block text-white text-sm font-medium mb-2">
                                <i class="fas fa-motorcycle mr-2"></i>Entrega por
                            </label>
                            <select name="motoboy_id" class="input-modern">
                                <option value="">Balcão (Cliente retira)</option>
                                <option value="uber" <?= ($_POST['motoboy_id'] ?? '') === 'uber' ? 'selected' : '' ?>>Uber</option>
                                <?php foreach ($motoboys as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= ($_POST['motoboy_id'] ?? '') == $m['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($motoboys)): ?>
                                <p class="text-gray-400 text-xs mt-2">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Cadastre motoboys em "Funcionários" para ver as opções
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Observações -->
                        <div>
                            <label class="block text-white text-sm font-medium mb-2">
                                <i class="fas fa-sticky-note mr-2"></i>Observações
                            </label>
                            <textarea name="obs" class="input-modern h-32 resize-none" 
                                placeholder="Observações adicionais sobre a venda..."><?= htmlspecialchars($_POST['obs'] ?? '') ?></textarea>
                        </div>

                        <!-- Botão Salvar -->
                        <div class="pt-4">
                            <button type="submit" class="btn-modern">
                                <i class="fas fa-save mr-2"></i> Registrar Venda
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Informações Úteis -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                <div class="glass-effect p-4 rounded-2xl text-center card-hover">
                    <i class="fas fa-user-tie text-purple-400 text-2xl mb-3"></i>
                    <h3 class="text-white font-semibold mb-1">Autozoners</h3>
                    <p class="text-gray-400 text-lg font-bold"><?= count($autozoners) ?></p>
                    <p class="text-gray-500 text-xs mt-1">cadastrados</p>
                </div>
                <div class="glass-effect p-4 rounded-2xl text-center card-hover">
                    <i class="fas fa-motorcycle text-blue-400 text-2xl mb-3"></i>
                    <h3 class="text-white font-semibold mb-1">Motoboys</h3>
                    <p class="text-gray-400 text-lg font-bold"><?= count($motoboys) ?></p>
                    <p class="text-gray-500 text-xs mt-1">cadastrados</p>
                </div>
                <div class="glass-effect p-4 rounded-2xl text-center card-hover">
                    <i class="fas fa-lightbulb text-yellow-400 text-2xl mb-3"></i>
                    <h3 class="text-white font-semibold mb-1">Dica Rápida</h3>
                    <p class="text-gray-400 text-xs">Vendas em dinheiro são marcadas como <span class="text-green-400">pagas</span> automaticamente</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Função para formatar moeda
        function formatarMoeda(input) {
            let value = input.value.replace(/\D/g, '');
            if (value === '') {
                input.value = '';
                return;
            }
            
            // Garantir que temos pelo menos 3 dígitos para centavos
            while (value.length < 3) {
                value = '0' + value;
            }
            
            const reais = value.slice(0, -2);
            const centavos = value.slice(-2);
            
            let valorFormatado = '';
            if (reais) {
                valorFormatado = reais.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            } else {
                valorFormatado = '0';
            }
            
            input.value = valorFormatado + ',' + centavos;
        }

        // Função para calcular troco
        function calcularTroco() {
            const formaPagamento = document.getElementById('forma_pagamento').value;
            
            if (formaPagamento === 'Dinheiro') {
                const valorInput = document.getElementById('valor').value;
                const valorPagoInput = document.getElementById('valor_pago').value;
                
                const valor = parseFloat(valorInput.replace(/\./g, '').replace(',', '.')) || 0;
                const valorPago = parseFloat(valorPagoInput.replace(/\./g, '').replace(',', '.')) || 0;
                
                let troco = 0;
                if (valorPago > valor) {
                    troco = valorPago - valor;
                }
                
                document.getElementById('troco_display').textContent = 'R$ ' + troco.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                
                // Efeito visual no troco
                const trocoDisplay = document.getElementById('troco_display');
                if (troco > 0) {
                    trocoDisplay.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                } else {
                    trocoDisplay.style.background = 'linear-gradient(135deg, #6b7280 0%, #4b5563 100%)';
                }
            }
        }

        // Função para mostrar/ocultar campos de dinheiro
        function toggleCamposDinheiro() {
            const formaPagamento = document.getElementById('forma_pagamento').value;
            const camposDinheiro = document.getElementById('campos_dinheiro');
            
            if (formaPagamento === 'Dinheiro') {
                camposDinheiro.style.display = 'block';
                // Focar no campo valor pago quando aparecer
                setTimeout(() => {
                    document.getElementById('valor_pago').focus();
                }, 300);
            } else {
                camposDinheiro.style.display = 'none';
                document.getElementById('valor_pago').value = '';
                document.getElementById('troco_display').textContent = 'R$ 0,00';
                document.getElementById('troco_display').style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
            }
            
            calcularTroco();
        }

        // Inicializar campos ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            toggleCamposDinheiro();
            
            // Aplicar formatação inicial se houver valores
            const valorInput = document.getElementById('valor');
            if (valorInput.value) {
                formatarMoeda(valorInput);
            }
            
            const valorPagoInput = document.getElementById('valor_pago');
            if (valorPagoInput.value) {
                formatarMoeda(valorPagoInput);
            }
            
            // Focar no primeiro campo
            document.querySelector('input[name="cliente"]').focus();
        });

        // Validar formulário antes de enviar
        document.getElementById('formVenda').addEventListener('submit', function(e) {
            const formaPagamento = document.getElementById('forma_pagamento').value;
            const valorInput = document.getElementById('valor').value;
            const valorPagoInput = document.getElementById('valor_pago').value;
            
            const valor = parseFloat(valorInput.replace(/\./g, '').replace(',', '.')) || 0;
            const valorPago = parseFloat(valorPagoInput.replace(/\./g, '').replace(',', '.')) || 0;
            
            if (formaPagamento === 'Dinheiro' && valorPago < valor) {
                e.preventDefault();
                alert('❌ Para pagamento em dinheiro, o valor pago não pode ser menor que o valor da venda.');
                document.getElementById('valor_pago').focus();
                return false;
            }
            
            return true;
        });

        // Tecla Enter avança para próximo campo
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const form = document.getElementById('formVenda');
                const index = Array.prototype.indexOf.call(form, e.target);
                form.elements[index + 1].focus();
            }
        });
    </script>
</body>
</html>
