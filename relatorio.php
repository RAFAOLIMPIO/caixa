<?php
// relatorio.php - VERSÃO COMPLETA COM API INTEGRADA
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$usuario_id = $_SESSION['usuario']['id'];
$numero_loja = $_SESSION['usuario']['numero_loja'];

// Buscar autozoners para edição
try {
    $stmtAutozoners = $pdo->prepare("
        SELECT id, nome 
        FROM funcionarios 
        WHERE usuario_id = ? AND tipo = 'autozoner'
        ORDER BY nome
    ");
    $stmtAutozoners->execute([$usuario_id]);
    $autozoners = $stmtAutozoners->fetchAll();
} catch (PDOException $e) {
    error_log('Erro ao buscar autozoners: ' . $e->getMessage());
    $autozoners = [];
}

// Busca vendas ordenadas
try {
    $stmt = $pdo->prepare("
        SELECT v.*, f.nome AS autozoner_nome 
        FROM vendas v 
        LEFT JOIN funcionarios f ON v.autozoner_id = f.id 
        WHERE v.usuario_id = :usuario_id 
        ORDER BY v.ordem ASC, v.id DESC
    ");
    $stmt->execute([':usuario_id' => $usuario_id]);
    $vendas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Erro ao buscar vendas: ' . $e->getMessage());
    die("
    <div style='padding: 20px; background: #f8d7da; color: #721c24; border-radius: 5px; margin: 20px;'>
        <h3>Erro ao carregar vendas</h3>
        <p>" . htmlspecialchars($e->getMessage()) . "</p>
        <p><a href='menu.php'>Voltar ao Menu</a></p>
    </div>");
}

// Calcular totais
$total_vendas = 0;
$total_pago = 0;
$total_pendente = 0;
$total_devolvido = 0;
$total_parcial = 0;
$total_pos = 0;
$total_balcao = 0;
$total_parcial_pago = 0;

foreach ($vendas as $v) {
    $valor = (float)$v['valor_total'];
    $status = $v['status'] ?? 'normal';
    $motoboy = strtolower(trim($v['motoboy'] ?? ''));
    $pago = isset($v['pago']) && ($v['pago'] == true || $v['pago'] == 1);
    
    // Calcular total POS (tudo que NÃO é balcão)
    if ($motoboy !== 'balcão' && $status === 'normal') {
        $total_pos += $valor;
    } elseif ($motoboy === 'balcão' && $status === 'normal') {
        $total_balcao += $valor;
    }
    
    if ($status === 'devolvido') {
        $total_devolvido += $valor;
    } elseif ($status === 'parcial') {
        $valor_devolvido = (float)$v['valor_devolvido'];
        $total_parcial += $valor_devolvido;
        $valor_liquido = $valor - $valor_devolvido;
        $total_vendas += $valor_liquido;
        
        // Se devolução parcial estiver paga
        if ($pago) {
            $total_parcial_pago += $valor_liquido;
        }
        
        if ($motoboy !== 'balcão') {
            $total_pos += $valor_liquido;
        } elseif ($motoboy === 'balcão') {
            $total_balcao += $valor_liquido;
        }
    } else {
        $total_vendas += $valor;
    }
    
    if (!empty($v['pago']) && $status === 'normal') {
        $total_pago += $valor;
    } elseif ($status === 'normal') {
        $total_pendente += $valor;
    }
}

// --- AJUSTES SOLICITADOS ---
// 1. Total de Vendas Lançadas = soma de todos os valores originais
$total_lancadas = $total_vendas + $total_devolvido + $total_parcial;

// 2. Incluir os pagos parciais no total de vendas pagas
$total_pago += $total_parcial_pago;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - AutoGest</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .relatorio-bg {
            background: linear-gradient(135deg, #000000 0%, #1a1a2e 50%, #16213e 100%);
        }
        .status-pago {
            background: rgba(16, 185, 129, 0.2) !important;
            border-left: 4px solid #10b981;
        }
        .status-pendente {
            background: rgba(239, 68, 68, 0.2) !important;
            border-left: 4px solid #ef4444;
        }
        .status-devolvido {
            background: rgba(107, 114, 128, 0.3) !important;
            color: #6b7280 !important;
            border-left: 4px solid #6b7280;
        }
        .status-parcial {
            background: rgba(245, 158, 11, 0.2) !important;
            border-left: 4px solid #f59e0b;
        }
        .status-parcial-pago {
            background: linear-gradient(90deg, rgba(245, 158, 11, 0.2) 50%, rgba(16, 185, 129, 0.2) 50%) !important;
            border-left: 4px solid #f59e0b;
            position: relative;
        }
        .status-parcial-pago::after {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 50%;
            background: rgba(16, 185, 129, 0.1);
            z-index: 0;
        }
        .checkbox-custom {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #10b981;
        }
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        .btn-excluir {
            transition: all 0.3s ease;
        }
        .btn-excluir:hover {
            transform: scale(1.1);
            background: rgba(239, 68, 68, 0.2) !important;
        }
        .sortable-ghost {
            background: rgba(124, 58, 237, 0.2) !important;
            border: 2px dashed #7c3aed !important;
        }
        .sortable-drag {
            opacity: 0.8;
            transform: rotate(2deg);
        }
        .sortable-row {
            cursor: move;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .input-modern {
            width: 100%;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: white;
            transition: all 0.3s;
        }
        .input-modern:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
        }
        .table-modern {
            width: 100%;
            border-collapse: collapse;
        }
        .table-modern th, .table-modern td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .badge-total {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .badge-pago {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .badge-pendente {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        .badge-devolucao {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
        }
        .badge-pos {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }
        .badge-balcao {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .loading-spinner {
            font-size: 50px;
            color: #3b82f6;
        }
        .btn-reverter {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.5);
        }
        .btn-reverter:hover {
            background: rgba(59, 130, 246, 0.3);
        }
    </style>
</head>
<body class="relatorio-bg min-h-screen px-4 py-8">
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
        </div>
    </div>

    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8 fade-in">
            <img src="logo.png" alt="AutoGest" class="mx-auto w-16 h-16 mb-4 rounded-full">
            <h1 class="text-3xl font-bold text-white mb-2">Relatório de Vendas</h1>
            <p class="text-gray-400">Loja <?= htmlspecialchars($numero_loja) ?> - Total de <?= count($vendas) ?> vendas registradas</p>
        </div>

        <!-- Botões de Navegação -->
        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
            <a href="menu.php" class="inline-flex items-center px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition duration-200 shadow-lg">
                <i class="fas fa-arrow-left mr-2"></i> Voltar ao Menu
            </a>
            <div class="flex gap-2">
                <a href="caixa.php" class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-lg transition duration-200 shadow-lg">
                    <i class="fas fa-cash-register mr-2"></i> Nova Venda
                </a>
                <?php if (!empty($vendas)): ?>
                <!-- BOTÃO PDF -->
                <a href="relatorio_pdf.php" target="_blank" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg transition duration-200 shadow-lg">
                   <i class="fas fa-file-pdf mr-2"></i> Exportar PDF
                </a>
                <button onclick="abrirModalLimparTudo()" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-500 text-white rounded-lg transition duration-200 shadow-lg">
                    <i class="fas fa-trash-alt mr-2"></i> Limpar Tudo
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cards de Resumo (6 cards, conforme solicitado) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 mb-8">
            <!-- Vendas Lançadas -->
            <div class="glass-effect p-4 rounded-2xl text-center fade-in-up">
                <div class="w-10 h-10 mx-auto mb-2 badge-total rounded-full flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-white text-lg"></i>
                </div>
                <h3 class="text-gray-400 text-xs mb-1">Vendas Lançadas</h3>
                <p class="text-lg font-bold text-white">R$ <?= number_format($total_lancadas, 2, ',', '.') ?></p>
            </div>

            <!-- Vendas Pagas (agora inclui parciais pagas) -->
            <div class="glass-effect p-4 rounded-2xl text-center fade-in-up" style="animation-delay: 0.1s">
                <div class="w-10 h-10 mx-auto mb-2 badge-pago rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-white text-lg"></i>
                </div>
                <h3 class="text-gray-400 text-xs mb-1">Vendas Pagas</h3>
                <p class="text-lg font-bold text-white">R$ <?= number_format($total_pago, 2, ',', '.') ?></p>
            </div>

            <!-- A Receber -->
            <div class="glass-effect p-4 rounded-2xl text-center fade-in-up" style="animation-delay: 0.2s">
                <div class="w-10 h-10 mx-auto mb-2 badge-pendente rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-white text-lg"></i>
                </div>
                <h3 class="text-gray-400 text-xs mb-1">A Receber</h3>
                <p class="text-lg font-bold text-white">R$ <?= number_format($total_pendente, 2, ',', '.') ?></p>
            </div>

            <!-- Devoluções (total devolvido, parcial + total) -->
            <div class="glass-effect p-4 rounded-2xl text-center fade-in-up" style="animation-delay: 0.3s">
                <div class="w-10 h-10 mx-auto mb-2 badge-devolucao rounded-full flex items-center justify-center">
                    <i class="fas fa-exchange-alt text-white text-lg"></i>
                </div>
                <h3 class="text-gray-400 text-xs mb-1">Devoluções</h3>
                <p class="text-lg font-bold text-white">R$ <?= number_format($total_devolvido + $total_parcial, 2, ',', '.') ?></p>
            </div>

            <!-- POS (Uber / Motoboy) -->
            <div class="glass-effect p-4 rounded-2xl text-center fade-in-up" style="animation-delay: 0.4s">
                <div class="w-10 h-10 mx-auto mb-2 badge-pos rounded-full flex items-center justify-center">
                    <i class="fas fa-mobile-alt text-white text-lg"></i>
                </div>
                <h3 class="text-gray-400 text-xs mb-1">POS (Uber/Motoboy)</h3>
                <p class="text-lg font-bold text-white">R$ <?= number_format($total_pos, 2, ',', '.') ?></p>
            </div>

            <!-- Balcão -->
            <div class="glass-effect p-4 rounded-2xl text-center fade-in-up" style="animation-delay: 0.5s">
                <div class="w-10 h-10 mx-auto mb-2 badge-balcao rounded-full flex items-center justify-center">
                    <i class="fas fa-store text-white text-lg"></i>
                </div>
                <h3 class="text-gray-400 text-xs mb-1">Balcão</h3>
                <p class="text-lg font-bold text-white">R$ <?= number_format($total_balcao, 2, ',', '.') ?></p>
            </div>
        </div>

        <!-- Tabela de Vendas -->
        <div class="glass-effect rounded-2xl overflow-hidden fade-in-up" style="animation-delay: 0.3s">
            <div class="p-4 border-b border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-white font-semibold">
                        <i class="fas fa-list mr-2"></i>Lista de Vendas
                        <span class="text-gray-400 text-sm font-normal">(Arraste para reordenar)</span>
                    </h3>
                    <div class="text-gray-400 text-sm">
                        <i class="fas fa-arrows-alt mr-1"></i> Segure e arraste para mover
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full table-modern">
                    <thead>
                        <tr class="bg-gray-800 bg-opacity-50">
                            <th class="p-3 text-left text-white font-semibold">Data/Hora</th>
                            <th class="p-3 text-left text-white font-semibold">Cliente</th>
                            <th class="p-3 text-left text-white font-semibold">Valor</th>
                            <th class="p-3 text-left text-white font-semibold">Autozoner</th>
                            <th class="p-3 text-left text-white font-semibold">Pagamento</th>
                            <th class="p-3 text-left text-white font-semibold">Status</th>
                            <th class="p-3 text-left text-white font-semibold">Observações</th>
                            <th class="p-3 text-left text-white font-semibold">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="tabelaVendas" class="sortable-table">
                        <?php if (empty($vendas)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-12">
                                    <i class="fas fa-chart-bar text-4xl text-gray-500 mb-4"></i>
                                    <p class="text-gray-400 text-lg mb-2">Nenhuma venda registrada</p>
                                    <p class="text-gray-500 mb-4">Comece registrando sua primeira venda no caixa</p>
                                    <a href="caixa.php" class="inline-flex items-center px-6 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-lg transition duration-200">
                                        <i class="fas fa-cash-register mr-2"></i>Ir para o Caixa
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($vendas as $v): 
                                $pago = isset($v['pago']) && ($v['pago'] == true || $v['pago'] == 1); 
                                $data = isset($v['data_venda']) ? date('d/m/Y H:i', strtotime($v['data_venda'])) : '-';
                                $valor = number_format((float)$v['valor_total'], 2, ',', '.');
                                $status = $v['status'] ?? 'normal';
                                $status_class = '';
                                $status_text = '';
                                $valor_devolvido = $v['valor_devolvido'] ?? 0;
                                
                                if ($status === 'devolvido') {
                                    $status_class = 'status-devolvido';
                                    $status_text = 'Devolvido';
                                } elseif ($status === 'parcial' && $pago) {
                                    $status_class = 'status-parcial-pago';
                                    $status_text = 'Pago (Parcial)';
                                } elseif ($status === 'parcial') {
                                    $status_class = 'status-parcial';
                                    $status_text = 'Parcial';
                                } elseif ($pago) {
                                    $status_class = 'status-pago';
                                    $status_text = 'Pago';
                                } else {
                                    $status_class = 'status-pendente';
                                    $status_text = 'Pendente';
                                }
                            ?>
                            <tr class="venda-item <?= $status_class ?> sortable-row" data-id="<?= $v['id'] ?>">
                                <td class="p-3 text-white">
                                    <div class="font-semibold"><?= htmlspecialchars($data) ?></div>
                                    <div class="text-gray-400 text-xs">
                                        <?= isset($v['data_venda']) ? date('d/m/Y', strtotime($v['data_venda'])) : '' ?>
                                    </div>
                                </td>
                                <td class="p-3 text-white font-medium"><?= htmlspecialchars($v['cliente'] ?? '-') ?></td>
                                <td class="p-3">
                                    <div class="text-white font-bold">R$ <?= $valor ?></div>
                                    <?php if ($status === 'parcial' && $valor_devolvido > 0): ?>
                                        <div class="text-yellow-400 text-xs">
                                            Devolvido: R$ <?= number_format((float)$valor_devolvido, 2, ',', '.') ?>
                                        </div>
                                        <div class="text-green-400 text-xs">
                                            Restante: R$ <?= number_format((float)$v['valor_total'] - (float)$valor_devolvido, 2, ',', '.') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 text-white"><?= htmlspecialchars($v['autozoner_nome'] ?? ($v['autozoner_id'] ?? '-')) ?></td>
                                <td class="p-3">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        <?= $v['forma_pagamento'] === 'Dinheiro' ? 'bg-yellow-500 bg-opacity-20 text-yellow-300' : 
                                           ($v['forma_pagamento'] === 'Cartão' ? 'bg-blue-500 bg-opacity-20 text-blue-300' : 
                                           'bg-green-500 bg-opacity-20 text-green-300') ?>">
                                        <i class="fas <?= $v['forma_pagamento'] === 'Dinheiro' ? 'fa-money-bill-wave' : 
                                                      ($v['forma_pagamento'] === 'Cartão' ? 'fa-credit-card' : 'fa-qrcode') ?> mr-1"></i>
                                        <?= htmlspecialchars($v['forma_pagamento'] ?? '-') ?>
                                    </span>
                                </td>
                                <td class="p-3">
                                    <div class="flex items-center">
                                        <?php if ($status !== 'devolvido'): ?>
                                        <input type="checkbox" 
                                               class="checkbox-custom mr-2" 
                                               <?= $pago ? 'checked' : '' ?>
                                               onchange="togglePago(<?= $v['id'] ?>, this.checked)">
                                        <?php endif; ?>
                                        <span class="text-sm font-medium <?= 
                                            $status === 'devolvido' ? 'text-gray-400' : 
                                            ($status === 'parcial' && !$pago ? 'text-yellow-400' : 
                                            ($pago ? 'text-green-400' : 'text-red-400')) ?>">
                                            <?= $status_text ?>
                                        </span>
                                        <?php if ($status === 'devolvido' || $status === 'parcial'): ?>
                                            <i class="fas fa-exchange-alt ml-2 <?= 
                                                $status === 'devolvido' ? 'text-gray-400' : 
                                                ($pago ? 'text-green-400' : 'text-yellow-400') ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="p-3">
                                    <div class="flex items-start group">
                                        <span class="obs-text text-white text-sm flex-1 cursor-pointer hover:text-purple-300 transition duration-200" 
                                              data-id="<?= $v['id'] ?>"
                                              onclick="abrirModalObs(<?= $v['id'] ?>, '<?= htmlspecialchars(addslashes($v['obs'] ?? '')) ?>')">
                                            <?= htmlspecialchars($v['obs'] ? (strlen($v['obs']) > 30 ? substr($v['obs'], 0, 30) . '...' : $v['obs']) : 'Clique para editar') ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="p-3">
                                    <div class="flex space-x-2">
                                        <!-- Botão Editar -->
                                        <button class="editar-venda text-blue-400 hover:text-blue-300 transition duration-200 p-2 rounded-lg hover:bg-blue-500 hover:bg-opacity-20"
                                                data-id="<?= $v['id'] ?>"
                                                data-cliente="<?= htmlspecialchars($v['cliente'] ?? '') ?>"
                                                data-valor="<?= $v['valor_total'] ?>"
                                                data-autozoner="<?= $v['autozoner_id'] ?? '' ?>"
                                                data-forma="<?= htmlspecialchars($v['forma_pagamento'] ?? '') ?>"
                                                data-motoboy="<?= htmlspecialchars($v['motoboy'] ?? '') ?>"
                                                data-obs="<?= htmlspecialchars($v['obs'] ?? '') ?>"
                                                title="Editar esta venda"
                                                onclick="abrirModalEditar(this)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <!-- Botão Devolução/Reverter -->
                                        <?php if ($status === 'devolvido' || $status === 'parcial'): ?>
                                        <button class="reverter-devolucao text-blue-400 hover:text-blue-300 transition duration-200 p-2 rounded-lg hover:bg-blue-500 hover:bg-opacity-20"
                                                data-id="<?= $v['id'] ?>"
                                                data-cliente="<?= htmlspecialchars($v['cliente'] ?? '') ?>"
                                                data-status="<?= $status ?>"
                                                title="Reverter devolução"
                                                onclick="abrirModalReverterDevolucao(this)">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                        <?php else: ?>
                                        <button class="devolver-venda text-orange-400 hover:text-orange-300 transition duration-200 p-2 rounded-lg hover:bg-orange-500 hover:bg-opacity-20"
                                                data-id="<?= $v['id'] ?>"
                                                data-cliente="<?= htmlspecialchars($v['cliente'] ?? '') ?>"
                                                data-valor="<?= $v['valor_total'] ?>"
                                                title="Registrar devolução"
                                                onclick="abrirModalDevolucao(this)">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <!-- Botão Excluir -->
                                        <button class="excluir-venda btn-excluir text-red-400 hover:text-red-300 transition duration-200 p-2 rounded-lg hover:bg-red-500 hover:bg-opacity-20"
                                                data-id="<?= $v['id'] ?>"
                                                data-cliente="<?= htmlspecialchars($v['cliente'] ?? '') ?>"
                                                title="Excluir esta venda">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para editar observação -->
    <div id="modalObs" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-70" onclick="fecharModalObs()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="glass-effect rounded-2xl p-6 w-full max-w-md transform transition-all duration-300 scale-95 opacity-0" id="modalContent">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                    <i class="fas fa-sticky-note mr-2 text-purple-400"></i>
                    Editar Observação
                </h3>
                <textarea id="obsField" class="input-modern w-full h-32 resize-none" placeholder="Digite a observação sobre esta venda..."></textarea>
                <input type="hidden" id="obsId">
                <div class="flex justify-end space-x-3 mt-4">
                    <button onclick="fecharModalObs()" class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition duration-200">
                        Cancelar
                    </button>
                    <button onclick="salvarObservacao()" class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-lg transition duration-200 flex items-center">
                        <i class="fas fa-save mr-2"></i>Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar venda -->
    <div id="modalEditar" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-70" onclick="fecharModalEditar()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="glass-effect rounded-2xl p-6 w-full max-w-md transform transition-all duration-300 scale-95 opacity-0" id="modalEditarContent">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                    <i class="fas fa-edit mr-2 text-blue-400"></i>
                    Editar Venda
                </h3>
                <div class="space-y-4">
                    <input type="hidden" id="editarId">
                    <div>
                        <label class="block text-white text-sm font-medium mb-2">Cliente</label>
                        <input type="text" id="editarCliente" class="input-modern w-full">
                    </div>
                    <div>
                        <label class="block text-white text-sm font-medium mb-2">Valor</label>
                        <input type="text" id="editarValor" class="input-modern w-full" oninput="formatarMoeda(this)">
                    </div>
                    <div>
                        <label class="block text-white text-sm font-medium mb-2">Autozoner</label>
                        <select id="editarAutozoner" class="input-modern w-full">
                            <option value="">Selecione...</option>
                            <?php foreach ($autozoners as $a): ?>
                                <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-white text-sm font-medium mb-2">Forma de Pagamento</label>
                        <select id="editarForma" class="input-modern w-full">
                            <option value="Dinheiro">Dinheiro</option>
                            <option value="Cartão">Cartão</option>
                            <option value="Pix">Pix</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-white text-sm font-medium mb-2">Motoboy/Entrega</label>
                        <input type="text" id="editarMotoboy" class="input-modern w-full" placeholder="Balcão, Uber, ou nome do motoboy">
                    </div>
                    <div>
                        <label class="block text-white text-sm font-medium mb-2">Observações</label>
                        <textarea id="editarObs" class="input-modern w-full h-24 resize-none"></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-4">
                    <button onclick="fecharModalEditar()" class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition duration-200">
                        Cancelar
                    </button>
                    <button onclick="salvarEdicao()" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition duration-200 flex items-center">
                        <i class="fas fa-save mr-2"></i>Salvar Alterações
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para devolução -->
    <div id="modalDevolucao" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-70" onclick="fecharModalDevolucao()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="glass-effect rounded-2xl p-6 w-full max-w-md transform transition-all duration-300 scale-95 opacity-0" id="modalDevolucaoContent">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                    <i class="fas fa-exchange-alt mr-2 text-orange-400"></i>
                    Registrar Devolução
                </h3>
                <div class="mb-4">
                    <p class="text-white">Cliente: <span id="devolucaoCliente" class="font-semibold"></span></p>
                    <p class="text-white">Valor da venda: R$ <span id="devolucaoValor" class="font-semibold"></span></p>
                </div>
                <div class="space-y-4">
                    <input type="hidden" id="devolucaoId">
                    <div>
                        <label class="block text-white text-sm font-medium mb-2">Tipo de Devolução</label>
                        <div class="flex space-x-4">
                            <label class="flex items-center">
                                <input type="radio" name="tipoDevolucao" value="total" checked class="mr-2" onchange="toggleValorDevolucao()">
                                <span class="text-white">Total</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="tipoDevolucao" value="parcial" class="mr-2" onchange="toggleValorDevolucao()">
                                <span class="text-white">Parcial</span>
                            </label>
                        </div>
                    </div>
                    <div id="campoValorParcial" style="display: none;">
                        <label class="block text-white text-sm font-medium mb-2">Valor Devolvido</label>
                        <input type="text" id="valorDevolvido" class="input-modern w-full" oninput="formatarMoeda(this)" placeholder="0,00">
                        <p class="text-gray-400 text-xs mt-1">O restante do valor continuará como pendente/pago</p>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-4">
                    <button onclick="fecharModalDevolucao()" class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition duration-200">
                        Cancelar
                    </button>
                    <button onclick="confirmarDevolucao()" class="px-4 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-lg transition duration-200 flex items-center">
                        <i class="fas fa-check mr-2"></i>Confirmar Devolução
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para reverter devolução -->
    <div id="modalReverterDevolucao" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-70" onclick="fecharModalReverterDevolucao()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="glass-effect rounded-2xl p-6 w-full max-w-md transform transition-all duration-300 scale-95 opacity-0" id="modalReverterContent">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                    <i class="fas fa-undo mr-2 text-blue-400"></i>
                    Reverter Devolução
                </h3>
                <div class="mb-4">
                    <p class="text-white">Cliente: <span id="reverterCliente" class="font-semibold"></span></p>
                    <p class="text-white">Status atual: <span id="reverterStatus" class="font-semibold"></span></p>
                </div>
                <p class="text-yellow-300 mb-4 bg-yellow-500 bg-opacity-20 p-3 rounded-lg">
                    <i class="fas fa-warning mr-2"></i>
                    Esta ação irá remover a devolução e restaurar o status original da venda.
                </p>
                <input type="hidden" id="reverterId">
                <div class="flex justify-end space-x-3 mt-4">
                    <button onclick="fecharModalReverterDevolucao()" class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition duration-200">
                        Cancelar
                    </button>
                    <button onclick="confirmarReverterDevolucao()" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition duration-200 flex items-center">
                        <i class="fas fa-undo mr-2"></i>Reverter Devolução
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para limpar tudo -->
    <div id="modalLimparTudo" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-70" onclick="fecharModalLimparTudo()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="glass-effect rounded-2xl p-6 w-full max-w-md transform transition-all duration-300 scale-95 opacity-0" id="modalLimparContent">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2 text-red-400"></i>
                    Limpar Todas as Vendas
                </h3>
                <div class="text-yellow-300 bg-yellow-500 bg-opacity-20 p-4 rounded-lg mb-4">
                    <i class="fas fa-warning mr-2"></i>
                    <strong>Atenção!</strong> Esta ação é irreversível.
                </div>
                <p class="text-white mb-4">
                    Você está prestes a excluir <strong class="text-red-400">todas as <?= count($vendas) ?> vendas</strong> do sistema.<br>
                    <span class="text-gray-400 text-sm">Total lançado: R$ <?= number_format($total_lancadas, 2, ',', '.') ?></span>
                </p>
                <p class="text-gray-400 text-sm mb-4">
                    Esta ação não pode ser desfeita. Certifique-se de que exportou o relatório antes de continuar.
                </p>
                <div class="flex justify-end space-x-3 mt-4">
                    <button onclick="fecharModalLimparTudo()" class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition duration-200">
                        Cancelar
                    </button>
                    <button onclick="confirmarLimparTudo()" class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white rounded-lg transition duration-200 flex items-center">
                        <i class="fas fa-trash-alt mr-2"></i>Sim, Limpar Tudo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Variáveis globais
    let currentObsId = null;
    let currentEditarId = null;
    let currentDevolucaoId = null;
    let currentReverterId = null;
    
    // Função para mostrar/ocultar loading
    function showLoading(show) {
        if (show) {
            $('#loadingOverlay').css('display', 'flex');
        } else {
            $('#loadingOverlay').hide();
        }
    }
    
    // Função para toggle Pago
    function togglePago(id, pago) {
        showLoading(true);
        
        $.post('api_vendas.php', {
            action: 'toggle_pago',
            id: id,
            pago: pago ? 1 : 0
        }).done(function(response){
            showLoading(false);
            if (response.ok) {
                location.reload();
            } else {
                alert('Erro: ' + response.error);
            }
        }).fail(function(jqXHR, textStatus, errorThrown){
            showLoading(false);
            console.error('Erro AJAX:', textStatus, errorThrown);
            alert('Erro de conexão. Verifique o console para detalhes.');
        });
    }
    
    // Inicializar SortableJS para arrastar linhas
    document.addEventListener('DOMContentLoaded', function() {
        const tbody = document.getElementById('tabelaVendas');
        if (tbody && <?= !empty($vendas) ? 'true' : 'false' ?>) {
            new Sortable(tbody, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onEnd: function(evt) {
                    salvarOrdemVendas();
                }
            });
        }
        
        // Configurar exclusão de vendas
        $(document).on('click', '.excluir-venda', function() {
            const id = $(this).data('id');
            const cliente = $(this).data('cliente');
            
            if (confirm(`Tem certeza que deseja excluir a venda do cliente "${cliente}"?`)) {
                showLoading(true);
                $.post('api_vendas.php', {
                    action: 'excluir_venda',
                    id: id
                }).done(function(response) {
                    showLoading(false);
                    if (response.ok) {
                        mostrarNotificacao('Venda excluída com sucesso!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        alert('Erro: ' + response.error);
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    showLoading(false);
                    console.error('Erro AJAX:', textStatus, errorThrown);
                    alert('Erro de conexão. Verifique o console para detalhes.');
                });
            }
        });
    });
    
    // Salvar nova ordem das vendas
    function salvarOrdemVendas() {
        const rows = document.querySelectorAll('.sortable-row');
        const ordem = [];
        
        rows.forEach((row, index) => {
            ordem.push({
                id: row.dataset.id,
                ordem: index
            });
        });
        
        $.ajax({
            url: 'api_vendas.php',
            method: 'POST',
            data: JSON.stringify({
                action: 'salvar_ordem',
                ordem: ordem
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.ok) {
                    mostrarNotificacao('Ordem salva com sucesso!', 'success');
                } else {
                    mostrarNotificacao('Erro: ' + response.error, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro ao salvar ordem:', error);
                mostrarNotificacao('Erro ao salvar ordem', 'error');
            }
        });
    }
    
    // Formatar moeda
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
    
    $(document).ready(function() {
        // Tecla ESC fecha modais
        $(document).on('keyup', function(e) {
            if (e.key === 'Escape') {
                fecharModalObs();
                fecharModalEditar();
                fecharModalDevolucao();
                fecharModalReverterDevolucao();
                fecharModalLimparTudo();
            }
        });
    });

    // Modal Observação
    function abrirModalObs(id, obsAtual) {
        currentObsId = id;
        $('#obsId').val(id);
        $('#obsField').val(obsAtual || '');
        $('#modalObs').removeClass('hidden').addClass('flex');
        setTimeout(() => {
            $('#modalContent').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
            $('#obsField').focus();
        }, 50);
    }

    function fecharModalObs() {
        $('#modalContent').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
        setTimeout(() => {
            $('#modalObs').removeClass('flex').addClass('hidden');
        }, 300);
    }

    function salvarObservacao() {
        if (!currentObsId) return;
        
        const obs = $('#obsField').val().trim();
        
        showLoading(true);
        
        $.post('api_vendas.php', {
            action: 'salvar_obs',
            id: currentObsId,
            obs: obs
        }).done(function(response) {
            showLoading(false);
            if (response.ok) {
                $(`.obs-text[data-id="${currentObsId}"]`).text(obs || 'Clique para editar');
                fecharModalObs();
                mostrarNotificacao('Observação salva com sucesso!', 'success');
            } else {
                alert('Erro: ' + response.error);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            showLoading(false);
            console.error('Erro AJAX:', textStatus, errorThrown);
            alert('Erro de conexão. Verifique o console para detalhes.');
        });
    }

    // Modal Editar Venda
    function abrirModalEditar(btn) {
        currentEditarId = $(btn).data('id');
        $('#editarId').val(currentEditarId);
        $('#editarCliente').val($(btn).data('cliente'));
        
        const valor = parseFloat($(btn).data('valor'));
        $('#editarValor').val(valor.toFixed(2).replace('.', ','));
        
        $('#editarAutozoner').val($(btn).data('autozoner'));
        $('#editarForma').val($(btn).data('forma'));
        $('#editarMotoboy').val($(btn).data('motoboy'));
        $('#editarObs').val($(btn).data('obs'));
        
        $('#modalEditar').removeClass('hidden').addClass('flex');
        setTimeout(() => {
            $('#modalEditarContent').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
            $('#editarCliente').focus();
        }, 50);
    }

    function fecharModalEditar() {
        $('#modalEditarContent').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
        setTimeout(() => {
            $('#modalEditar').removeClass('flex').addClass('hidden');
        }, 300);
    }

    function salvarEdicao() {
        if (!currentEditarId) return;
        
        const dados = {
            action: 'editar_venda',
            id: currentEditarId,
            cliente: $('#editarCliente').val().trim(),
            valor: $('#editarValor').val(),
            autozoner_id: $('#editarAutozoner').val(),
            forma_pagamento: $('#editarForma').val(),
            motoboy: $('#editarMotoboy').val().trim(),
            obs: $('#editarObs').val().trim()
        };
        
        if (!dados.cliente) {
            alert('Preencha o nome do cliente.');
            return;
        }
        
        const valorNumerico = parseFloat(dados.valor.replace(',', '.'));
        if (!valorNumerico || valorNumerico <= 0) {
            alert('Informe um valor válido.');
            return;
        }
        
        showLoading(true);
        
        $.post('api_vendas.php', dados)
            .done(function(response) {
                showLoading(false);
                if (response.ok) {
                    fecharModalEditar();
                    mostrarNotificacao('Venda atualizada com sucesso!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('Erro: ' + response.error);
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                showLoading(false);
                console.error('Erro AJAX:', textStatus, errorThrown);
                alert('Erro de conexão. Verifique o console para detalhes.');
            });
    }

    // Modal Devolução
    function abrirModalDevolucao(btn) {
        currentDevolucaoId = $(btn).data('id');
        $('#devolucaoId').val(currentDevolucaoId);
        $('#devolucaoCliente').text($(btn).data('cliente'));
        
        const valorOriginal = parseFloat($(btn).data('valor')).toFixed(2);
        $('#devolucaoValor').text(valorOriginal.replace('.', ','));
        $('#valorDevolvido').val('');
        $('#campoValorParcial').hide();
        
        $('input[name="tipoDevolucao"][value="total"]').prop('checked', true);
        
        $('#modalDevolucao').removeClass('hidden').addClass('flex');
        setTimeout(() => {
            $('#modalDevolucaoContent').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 50);
    }

    function fecharModalDevolucao() {
        $('#modalDevolucaoContent').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
        setTimeout(() => {
            $('#modalDevolucao').removeClass('flex').addClass('hidden');
        }, 300);
    }

    function toggleValorDevolucao() {
        const tipo = $('input[name="tipoDevolucao"]:checked').val();
        if (tipo === 'parcial') {
            $('#campoValorParcial').show();
            $('#valorDevolvido').focus();
        } else {
            $('#campoValorParcial').hide();
        }
    }

    function confirmarDevolucao() {
        if (!currentDevolucaoId) {
            alert('ID da venda não encontrado');
            return;
        }
        
        const tipo = $('input[name="tipoDevolucao"]:checked').val();
        const valorOriginalText = $('#devolucaoValor').text();
        const valorOriginal = parseFloat(valorOriginalText.replace(',', '.'));
        
        let valorDevolvido;
        
        if (tipo === 'parcial') {
            valorDevolvido = $('#valorDevolvido').val();
            if (!valorDevolvido || valorDevolvido.trim() === '') {
                alert('Informe o valor devolvido para devolução parcial.');
                return;
            }
            
            // Converter para número
            const valorDevolvidoNum = parseFloat(valorDevolvido.replace(',', '.'));
            if (isNaN(valorDevolvidoNum) || valorDevolvidoNum <= 0) {
                alert('Valor devolvido inválido. Informe um número positivo.');
                return;
            }
            
            if (valorDevolvidoNum >= valorOriginal) {
                alert('Valor devolvido não pode ser maior ou igual ao valor original da venda.');
                return;
            }
        } else {
            valorDevolvido = valorOriginalText;
        }
        
        const dados = {
            action: 'devolver_venda',
            id: currentDevolucaoId,
            tipo: tipo,
            valor_devolvido: valorDevolvido
        };
        
        showLoading(true);
        
        $.ajax({
            url: 'api_vendas.php',
            method: 'POST',
            data: dados,
            dataType: 'json'
        }).done(function(response) {
            showLoading(false);
            if (response.ok) {
                mostrarNotificacao(response.message || 'Devolução registrada com sucesso!', 'success');
                fecharModalDevolucao();
                setTimeout(() => location.reload(), 1500);
            } else {
                alert('Erro: ' + (response.error || 'Erro desconhecido'));
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            showLoading(false);
            console.error('Erro AJAX:', textStatus, errorThrown);
            alert('Erro de conexão com o servidor. Verifique o console para detalhes.');
        });
    }

    // Modal Reverter Devolução
    function abrirModalReverterDevolucao(btn) {
        currentReverterId = $(btn).data('id');
        $('#reverterId').val(currentReverterId);
        $('#reverterCliente').text($(btn).data('cliente'));
        $('#reverterStatus').text($(btn).data('status') === 'devolvido' ? 'Devolvido Total' : 'Devolução Parcial');
        
        $('#modalReverterDevolucao').removeClass('hidden').addClass('flex');
        setTimeout(() => {
            $('#modalReverterContent').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 50);
    }

    function fecharModalReverterDevolucao() {
        $('#modalReverterContent').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
        setTimeout(() => {
            $('#modalReverterDevolucao').removeClass('flex').addClass('hidden');
        }, 300);
    }

    function confirmarReverterDevolucao() {
        if (!currentReverterId) {
            alert('ID da venda não encontrado');
            return;
        }
        
        showLoading(true);
        
        $.ajax({
            url: 'api_vendas.php',
            method: 'POST',
            data: {
                action: 'reverter_devolucao',
                id: currentReverterId
            },
            dataType: 'json'
        }).done(function(response) {
            showLoading(false);
            if (response.ok) {
                mostrarNotificacao(response.message || 'Devolução revertida com sucesso!', 'success');
                fecharModalReverterDevolucao();
                setTimeout(() => location.reload(), 1500);
            } else {
                alert('Erro: ' + (response.error || 'Erro desconhecido'));
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            showLoading(false);
            console.error('Erro AJAX:', textStatus, errorThrown);
            alert('Erro de conexão com o servidor. Verifique o console para detalhes.');
        });
    }

    // Modal Limpar Tudo
    function abrirModalLimparTudo() {
        $('#modalLimparTudo').removeClass('hidden').addClass('flex');
        setTimeout(() => {
            $('#modalLimparContent').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 50);
    }

    function fecharModalLimparTudo() {
        $('#modalLimparContent').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
        setTimeout(() => {
            $('#modalLimparTudo').removeClass('flex').addClass('hidden');
        }, 300);
    }

    function confirmarLimparTudo() {
        showLoading(true);
        
        $.post('api_vendas.php', {action: 'limpar_tudo'})
            .done(function(response) {
                showLoading(false);
                if (response.ok) {
                    mostrarNotificacao('Todas as vendas foram excluídas com sucesso!', 'success');
                    fecharModalLimparTudo();
                    setTimeout(() => location.reload(), 2000);
                } else {
                    alert('Erro: ' + response.error);
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                showLoading(false);
                console.error('Erro AJAX:', textStatus, errorThrown);
                alert('Erro de conexão. Verifique o console para detalhes.');
            });
    }

    function mostrarNotificacao(mensagem, tipo = 'info') {
        const tipos = {
            'success': {icon: 'fa-check-circle', color: 'bg-green-500'},
            'error': {icon: 'fa-exclamation-circle', color: 'bg-red-500'},
            'warning': {icon: 'fa-exclamation-triangle', color: 'bg-yellow-500'},
            'info': {icon: 'fa-info-circle', color: 'bg-blue-500'}
        };
        
        const config = tipos[tipo] || tipos.info;
        
        const notificacao = $(`
            <div class="fixed top-4 right-4 glass-effect p-4 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50 max-w-sm">
                <div class="flex items-center">
                    <i class="fas ${config.icon} mr-3 text-white text-xl"></i>
                    <div class="text-white text-sm">${mensagem}</div>
                </div>
            </div>
        `);
        
        $('body').append(notificacao);
        
        setTimeout(() => {
            notificacao.removeClass('translate-x-full');
        }, 100);
        
        setTimeout(() => {
            notificacao.addClass('translate-x-full');
            setTimeout(() => notificacao.remove(), 300);
        }, 3000);
    }
    </script>
</body>
</html>