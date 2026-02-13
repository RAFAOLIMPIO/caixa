<?php
require 'includes/config.php';
require 'includes/funcoes.php';

verificar_login();

$usuario = usuario_atual();
$usuario_id = $usuario['id'];
$numero_loja = $usuario['numero_loja'];

$sucesso = '';
$erros = [];


// Buscar autozoners e motoboys
try {
    // Autozoners - usando numero_loja
    $stmtAuto = $pdo->prepare("
        SELECT id, nome 
        FROM funcionarios 
        WHERE numero_loja = ?
          AND LOWER(tipo) = 'autozoner'
          AND ativo = TRUE
        ORDER BY nome
    ");
    $stmtAuto->execute([$numero_loja]);
    $autozoners = $stmtAuto->fetchAll();
    
    // Motoboys - usando numero_loja
    $stmtMoto = $pdo->prepare("
        SELECT id, nome 
        FROM funcionarios 
        WHERE numero_loja = ?
          AND LOWER(tipo) = 'motoboy'
          AND ativo = TRUE
        ORDER BY nome
    ");
    $stmtMoto->execute([$numero_loja]);
    $motoboys = $stmtMoto->fetchAll();
} catch (Exception $e) {
    $autozoners = [];
    $motoboys = [];
    error_log("Erro ao buscar funcionários: " . $e->getMessage());
}

// Processar o formulário quando for enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente = sanitizar($_POST['cliente'] ?? '');
    $valor = floatval(str_replace(['.', ','], ['', '.'], $_POST['valor'] ?? 0));
    $valor_pago = !empty($_POST['valor_pago']) ? floatval(str_replace(['.', ','], ['', '.'], $_POST['valor_pago'])) : 0;
    $forma_pagamento = sanitizar($_POST['forma_pagamento'] ?? '');
    $motoboy_id = sanitizar($_POST['motoboy_id'] ?? '');
    $obs = sanitizar($_POST['obs'] ?? '');
    $autozoner_id = (int)($_POST['autozoner_id'] ?? 0);
    // ⏰ Captura a data/hora enviada pelo cliente
    $data_venda = $_POST['data_venda'] ?? date('Y-m-d H:i:s');

    // Calcular troco apenas se for dinheiro e valor pago > 0
    $troco = 0;
    if ($forma_pagamento === 'Dinheiro' && $valor_pago > 0) {
        $troco = $valor_pago - $valor;
        if ($troco < 0) $troco = 0;
    }

    // DEFINIÇÃO AUTOMÁTICA DA MÁQUINA
    if ($motoboy_id === '' || $motoboy_id === 'Balcão') {
        $maquina = 'Maquina Balcao';
    } else {
        $maquina = 'Maquina Movel';
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

            // 🕐 Incluímos a coluna `data_venda` no INSERT com o valor enviado pelo cliente
            $sql = "INSERT INTO vendas (cliente, valor_total, valor_pago, troco, forma_pagamento, motoboy, pago, usuario_id, numero_loja, autozoner_id, obs, maquina, status, data_venda) 
                    VALUES (:cliente, :valor, :valor_pago, :troco, :forma_pagamento, :motoboy, :pago, :usuario_id, :numero_loja, :autozoner_id, :obs, :maquina, 'normal', :data_venda)";
            
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
                ':obs' => $obs,
                ':maquina' => $maquina,
                ':data_venda' => $data_venda  // ⏰ Data/hora do cliente
            ]);
            
            if ($result) {
                $sucesso = "Venda registrada com sucesso!";
                // ❌ Redirecionamento automático REMOVIDO – apenas mensagem de sucesso
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
        .input-money {
            text-align: left;
            padding-left: 2.5rem;
            direction: ltr;
        }
        .input-money::placeholder {
            text-align: left;
            direction: ltr;
        }
        .suggestions-list {
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
        }
        .suggestion-item:hover {
            background-color: #4f46e5;
            color: white;
        }
        .suggestion-selected {
            background-color: #4f46e5;
            color: white;
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
                            <!-- Mensagem de redirecionamento removida -->
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
                    <!-- ⏰ Campo oculto que receberá a data/hora do computador do cliente -->
                    <input type="hidden" name="data_venda" id="data_venda">

                    <!-- Coluna 1 -->
                    <div class="space-y-4">
                        <!-- Cliente -->
                        <div class="relative">
                            <label class="block text-white text-sm font-medium mb-2 required-field">
                                <i class="fas fa-user mr-2"></i>Cliente
                            </label>
                            <input 
                                type="text" 
                                name="cliente" 
                                id="cliente" 
                                autocomplete="off" 
                                required 
                                class="input-modern w-full" 
                                placeholder="Nome do cliente" 
                                value="<?= htmlspecialchars($_POST['cliente'] ?? '') ?>"
                            >
                            <ul id="listaClientes" class="absolute z-50 w-full bg-gray-800 border border-gray-700 rounded-lg mt-1 hidden suggestions-list"></ul>
                        </div>

                        <!-- Valor -->
                        <div class="relative">
                            <label class="block text-white text-sm font-medium mb-2 required-field">
                                <i class="fas fa-dollar-sign mr-2"></i>Valor da Venda
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">R$</span>
                                <input 
                                    type="text" 
                                    name="valor" 
                                    id="valor" 
                                    value="<?= htmlspecialchars($_POST['valor'] ?? '') ?>" 
                                    required 
                                    class="input-modern input-money w-full" 
                                    placeholder="0,00" 
                                    oninput="formatarMoeda(this); calcularTroco()" 
                                    autocomplete="off"
                                >
                            </div>
                        </div>

                        <!-- Forma de Pagamento -->
                        <div>
                            <label class="block text-white text-sm font-medium mb-2 required-field">
                                <i class="fas fa-credit-card mr-2"></i>Forma de Pagamento
                            </label>
                            <select name="forma_pagamento" id="forma_pagamento" required class="input-modern w-full" onchange="toggleCamposDinheiro()">
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
                            <div class="relative">
                                <label class="block text-white text-sm font-medium mb-2">
                                    <i class="fas fa-money-bill-wave mr-2"></i>Valor Pago
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">R$</span>
                                    <input 
                                        type="text" 
                                        name="valor_pago" 
                                        id="valor_pago" 
                                        class="input-modern input-money w-full" 
                                        placeholder="0,00" 
                                        oninput="formatarMoeda(this); calcularTroco()" 
                                        autocomplete="off"
                                    >
                                </div>
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
                            <select name="autozoner_id" required class="input-modern w-full">
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
                            <select name="motoboy_id" class="input-modern w-full">
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
                            <textarea 
                                name="obs" 
                                id="obs" 
                                class="input-modern w-full h-32 resize-none" 
                                placeholder="Observações adicionais sobre a venda..."
                            ><?= htmlspecialchars($_POST['obs'] ?? '') ?></textarea>
                        </div>

                        <!-- Botão Salvar -->
                        <div class="pt-4">
                            <button type="submit" class="btn-modern w-full">
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
                    <p class="text-gray-400 text-xs">Pressione ENTER para avançar entre campos e salvar</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ========================
        // ⏰ DATA/HORA DO CLIENTE
        // ========================
        function setDataVenda() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const formatted = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
            document.getElementById('data_venda').value = formatted;
        }

        // Atualiza o campo oculto ao carregar e antes de enviar
        document.addEventListener('DOMContentLoaded', setDataVenda);
        document.getElementById('formVenda').addEventListener('submit', setDataVenda);

        // ========================
        // 💰 FUNÇÕES DE MOEDA E TROCO
        // ========================
        function formatarMoeda(input) {
            let valor = input.value.replace(/\D/g, '');
            if (valor === '') {
                input.value = '';
                return;
            }
            valor = (parseInt(valor) / 100).toFixed(2);
            let partes = valor.split('.');
            partes[0] = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            input.value = partes.join(',');
        }

        function calcularTroco() {
            const formaPagamento = document.getElementById('forma_pagamento').value;
            if (formaPagamento === 'Dinheiro') {
                const valorInput = document.getElementById('valor').value;
                const valorPagoInput = document.getElementById('valor_pago').value;
                const valor = parseFloat(valorInput.replace(/\./g, '').replace(',', '.')) || 0;
                const valorPago = parseFloat(valorPagoInput.replace(/\./g, '').replace(',', '.')) || 0;
                let troco = valorPago > valor ? valorPago - valor : 0;
                document.getElementById('troco_display').textContent = 'R$ ' + troco.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                const trocoDisplay = document.getElementById('troco_display');
                if (troco > 0) {
                    trocoDisplay.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                } else {
                    trocoDisplay.style.background = 'linear-gradient(135deg, #6b7280 0%, #4b5563 100%)';
                }
            }
        }

        function toggleCamposDinheiro() {
            const formaPagamento = document.getElementById('forma_pagamento').value;
            const camposDinheiro = document.getElementById('campos_dinheiro');
            if (formaPagamento === 'Dinheiro') {
                camposDinheiro.style.display = 'block';
                setTimeout(() => document.getElementById('valor_pago').focus(), 300);
            } else {
                camposDinheiro.style.display = 'none';
                document.getElementById('valor_pago').value = '';
                document.getElementById('troco_display').textContent = 'R$ 0,00';
                document.getElementById('troco_display').style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
            }
            calcularTroco();
        }

        // ========================
        // 🔍 BUSCA DE CLIENTES (sugestões)
        // ========================
        let selecionadoIndex = -1;
        let debounceTimer;
        const clienteInput = document.getElementById('cliente');
        const listaClientes = document.getElementById('listaClientes');

        clienteInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const termo = clienteInput.value.trim();
            if (termo.length < 2) {
                listaClientes.classList.add('hidden');
                return;
            }
            debounceTimer = setTimeout(async () => {
                try {
                    const res = await fetch(`buscar_clientes.php?q=${encodeURIComponent(termo)}&loja=<?= $numero_loja ?>`);
                    const clientes = await res.json();
                    if (clientes.length === 0) {
                        listaClientes.classList.add('hidden');
                        return;
                    }
                    listaClientes.innerHTML = '';
                    clientes.forEach((nome, i) => {
                        const li = document.createElement('li');
                        li.textContent = nome;
                        li.className = 'px-3 py-2 cursor-pointer hover:bg-purple-600 hover:text-white suggestion-item';
                        li.onclick = () => selecionarCliente(nome);
                        listaClientes.appendChild(li);
                    });
                    selecionadoIndex = -1;
                    listaClientes.classList.remove('hidden');
                } catch (error) {
                    console.error('Erro ao buscar clientes:', error);
                }
            }, 300);
        });

        clienteInput.addEventListener('keydown', (e) => {
            const itens = listaClientes.querySelectorAll('li');
            if (!listaClientes.classList.contains('hidden') && itens.length > 0) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selecionadoIndex = (selecionadoIndex + 1) % itens.length;
                    itens.forEach((item, i) => {
                        item.classList.toggle('bg-purple-600', i === selecionadoIndex);
                        item.classList.toggle('text-white', i === selecionadoIndex);
                    });
                }
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selecionadoIndex = (selecionadoIndex - 1 + itens.length) % itens.length;
                    itens.forEach((item, i) => {
                        item.classList.toggle('bg-purple-600', i === selecionadoIndex);
                        item.classList.toggle('text-white', i === selecionadoIndex);
                    });
                }
                if (e.key === 'Enter' && selecionadoIndex >= 0) {
                    e.preventDefault();
                    selecionarCliente(itens[selecionadoIndex].textContent);
                }
                if (e.key === 'Escape') {
                    listaClientes.classList.add('hidden');
                }
            }
        });

        function selecionarCliente(nome) {
            clienteInput.value = nome;
            listaClientes.classList.add('hidden');
            document.getElementById('valor').focus();
        }

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#cliente') && !e.target.closest('#listaClientes')) {
                listaClientes.classList.add('hidden');
            }
        });

        // ========================
        // ⌨️ NAVEGAÇÃO SEQUENCIAL COM ENTER (APENAS CAMPOS VISÍVEIS)
        // ========================
        function getCamposVisiveis() {
            const form = document.getElementById('formVenda');
            const campos = form.querySelectorAll('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])');
            return Array.from(campos).filter(campo => {
                // Verifica se o campo está visível (offsetParent !== null)
                return campo.offsetParent !== null;
            });
        }

        function validarFormulario() {
            const formaPagamento = document.getElementById('forma_pagamento').value;
            const valorInput = document.getElementById('valor').value;
            const valorPagoInput = document.getElementById('valor_pago').value;
            const valor = parseFloat(valorInput.replace(/\./g, '').replace(',', '.')) || 0;
            const valorPago = parseFloat(valorPagoInput.replace(/\./g, '').replace(',', '.')) || 0;
            
            if (formaPagamento === 'Dinheiro' && valorPago < valor) {
                alert('❌ Para pagamento em dinheiro, o valor pago não pode ser menor que o valor da venda.');
                document.getElementById('valor_pago').focus();
                return false;
            }
            if (document.getElementById('cliente').value.trim() === '') {
                alert('❌ Preencha o nome do cliente.');
                document.getElementById('cliente').focus();
                return false;
            }
            if (valor <= 0) {
                alert('❌ O valor da venda deve ser maior que zero.');
                document.getElementById('valor').focus();
                return false;
            }
            return true;
        }

        document.addEventListener('keydown', function(e) {
            // Ignora se estiver usando sugestões (já tratado)
            if (e.target === clienteInput && !listaClientes.classList.contains('hidden')) {
                return; // O próprio evento do clienteInput cuida disso
            }

            if (e.key === 'Enter') {
                const campoAtual = e.target;
                e.preventDefault();

                // Se o campo atual for obrigatório e estiver vazio, não avança
                if (campoAtual.required && campoAtual.value.trim() === '') {
                    campoAtual.focus();
                    return;
                }

                const camposVisiveis = getCamposVisiveis();
                const indexAtual = camposVisiveis.indexOf(campoAtual);
                
                if (indexAtual !== -1) {
                    if (indexAtual === camposVisiveis.length - 1) {
                        // Último campo → submeter se válido
                        if (validarFormulario()) {
                            document.getElementById('formVenda').submit();
                        }
                    } else {
                        // Avança para o próximo campo visível
                        const proximoCampo = camposVisiveis[indexAtual + 1];
                        if (proximoCampo) {
                            proximoCampo.focus();
                            // Seleciona todo o texto se for campo de moeda
                            if (proximoCampo.id === 'valor' || proximoCampo.id === 'valor_pago') {
                                proximoCampo.select();
                            }
                        }
                    }
                }
            }

            // ESC fecha sugestões
            if (e.key === 'Escape') {
                listaClientes.classList.add('hidden');
            }
        });

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            toggleCamposDinheiro();
            const valorInput = document.getElementById('valor');
            if (valorInput.value) formatarMoeda(valorInput);
            const valorPagoInput = document.getElementById('valor_pago');
            if (valorPagoInput.value) formatarMoeda(valorPagoInput);
            document.getElementById('cliente').focus();
        });

        // Validação extra no submit
        document.getElementById('formVenda').addEventListener('submit', function(e) {
            setDataVenda(); // Garante data/hora atualizada
            if (!validarFormulario()) {
                e.preventDefault();
                return false;
            }
            return true;
        });
    </script>
</body>
</html>