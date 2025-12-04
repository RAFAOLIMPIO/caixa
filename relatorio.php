<?php
// relatorio.php
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$usuario_id = $_SESSION['usuario']['id'];
$numero_loja = $_SESSION['usuario']['numero_loja'];

// AJAX: salvar observação, toggle pago e exclusões
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'salvar_obs' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $obs = sanitizar($_POST['obs'] ?? '');
        try {
            $stmt = $pdo->prepare("UPDATE vendas SET obs = :obs WHERE id = :id AND usuario_id = :usuario_id");
            $stmt->execute([':obs' => $obs, ':id' => $id, ':usuario_id' => $usuario_id]);
            echo json_encode(['ok' => true, 'message' => 'Observação salva com sucesso!']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit();
        
    } elseif ($_POST['action'] === 'toggle_pago' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $pago = isset($_POST['pago']) && ($_POST['pago'] == '1' || $_POST['pago'] == 1) ? 1 : 0;
        try {
            $stmt = $pdo->prepare("UPDATE vendas SET pago = :pago WHERE id = :id AND usuario_id = :usuario_id");
            $stmt->execute([':pago' => $pago, ':id' => $id, ':usuario_id' => $usuario_id]);
            echo json_encode(['ok' => true, 'message' => 'Status atualizado!']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit();
        
    } elseif ($_POST['action'] === 'excluir_venda' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM vendas WHERE id = :id AND usuario_id = :usuario_id");
            $stmt->execute([':id' => $id, ':usuario_id' => $usuario_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['ok' => true, 'message' => 'Venda excluída com sucesso!']);
            } else {
                echo json_encode(['ok' => false, 'error' => 'Venda não encontrada.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit();
        
    } elseif ($_POST['action'] === 'limpar_tudo') {
        try {
            $stmt = $pdo->prepare("DELETE FROM vendas WHERE usuario_id = :usuario_id");
            $stmt->execute([':usuario_id' => $usuario_id]);
            $total_excluido = $stmt->rowCount();
            
            echo json_encode([
                'ok' => true, 
                'message' => "Todas as {$total_excluido} vendas foram excluídas com sucesso!",
                'total_excluido' => $total_excluido
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }
}

// Busca vendas
try {
    $stmt = $pdo->prepare("
        SELECT v.*, f.nome AS autozoner_nome 
        FROM vendas v 
        LEFT JOIN funcionarios f ON v.autozoner_id = f.id 
        WHERE v.usuario_id = :usuario_id 
        ORDER BY v.data_venda DESC
    ");
    $stmt->execute([':usuario_id' => $usuario_id]);
    $vendas = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erro ao buscar vendas: " . $e->getMessage());
}

// Calcular totais
$total_vendas = 0;
$total_pago = 0;
$total_pendente = 0;
foreach ($vendas as $v) {
    $valor = (float)$v['valor_total'];
    $total_vendas += $valor;
    if (!empty($v['pago'])) {
        $total_pago += $valor;
    } else {
        $total_pendente += $valor;
    }
}
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
        .checkbox-custom {
            width: 20px;
            height: 20px;
            cursor: pointer;
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
    </style>
</head>
<body class="relatorio-bg min-h-screen px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8 fade-in">
            <img src="logo.png" alt="AutoGest" class="mx-auto w-16 h-16 mb-4 rounded-full">
            <h1 class="text-3xl font-bold text-white mb-2">Relatório de Vendas</h1>
            <p class="text-gray-400">Loja <?= htmlspecialchars($numero_loja) ?> - Total de <?= count($vendas) ?> vendas registradas</p>
        </div>

        <!-- Botões -->
        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
            <a href="menu.php" class="inline-flex items-center px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg shadow-lg transition">
                <i class="fas fa-arrow-left mr-2"></i> Voltar ao Menu
            </a>
            <div class="flex gap-2">
                <a href="caixa.php" class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-lg shadow-lg">
                    <i class="fas fa-cash-register mr-2"></i> Nova Venda
                </a>
                <?php if (!empty($vendas)): ?>
                <button onclick="gerarPDF()" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg shadow-lg">
                    <i class="fas fa-file-pdf mr-2"></i> Exportar PDF
                </button>
                <button onclick="abrirModalLimparTudo()" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-500 text-white rounded-lg shadow-lg">
                    <i class="fas fa-trash-alt mr-2"></i> Limpar Tudo
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="glass-effect p-6 rounded-2xl text-center fade-in-up">
                <div class="w-12 h-12 mx-auto mb-3 bg-blue-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-white text-xl"></i>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Total em Vendas</h3>
                <p class="text-2xl font-bold text-white">R$ <?= number_format($total_vendas, 2, ',', '.') ?></p>
                <p class="text-gray-500 text-xs mt-1"><?= count($vendas) ?> vendas</p>
            </div>

            <div class="glass-effect p-6 rounded-2xl text-center fade-in-up">
                <div class="w-12 h-12 mx-auto mb-3 bg-green-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-white text-xl"></i>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Vendas Pagas</h3>
                <p class="text-2xl font-bold text-white">R$ <?= number_format($total_pago, 2, ',', '.') ?></p>
                <p class="text-green-400 text-xs mt-1">Recebido</p>
            </div>

            <div class="glass-effect p-6 rounded-2xl text-center fade-in-up">
                <div class="w-12 h-12 mx-auto mb-3 bg-yellow-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-white text-xl"></i>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">A Receber</h3>
                <p class="text-2xl font-bold text-white">R$ <?= number_format($total_pendente, 2, ',', '.') ?></p>
                <p class="text-yellow-400 text-xs mt-1">Pendente</p>
            </div>
        </div>

        <!-- Tabela -->
        <div class="glass-effect rounded-2xl overflow-hidden fade-in-up">
            <div class="overflow-x-auto">
                <table class="w-full table-modern">
                    <thead>
                        <tr class="bg-gray-800 bg-opacity-50">
                            <th class="p-4 text-left text-white font-semibold">Data/Hora</th>
                            <th class="p-4 text-left text-white font-semibold">Cliente</th>
                            <th class="p-4 text-left text-white font-semibold">Valor</th>
                            <th class="p-4 text-left text-white font-semibold">Autozoner</th>
                            <th class="p-4 text-left text-white font-semibold">Pagamento</th>
                            <th class="p-4 text-left text-white font-semibold">Entrega</th>
                            <th class="p-4 text-left text-white font-semibold">Status</th>
                            <th class="p-4 text-left text-white font-semibold">Observações</th>
                            <th class="p-4 text-left text-white font-semibold">Excluir</th>
                        </tr>
                    </thead>
                    <tbody id="tabelaVendas">
                        <?php foreach ($vendas as $v): 
                            $pago = !empty($v['pago']);
                            $data = isset($v['data_venda']) ? date('d/m/Y H:i', strtotime($v['data_venda'])) : '-';
                            $valor = number_format((float)$v['valor_total'], 2, ',', '.');
                            $troco = $v['troco'] ? number_format((float)$v['troco'], 2, ',', '.') : '0,00';
                            $valor_pago = $v['valor_pago'] ? number_format((float)$v['valor_pago'], 2, ',', '.') : '';
                        ?>
                        <tr class="venda-item <?= $pago ? 'status-pago' : 'status-pendente' ?>" data-id="<?= $v['id'] ?>">
                            <td class="p-4 text-white">
                                <div class="font-semibold"><?= htmlspecialchars($data) ?></div>
                            </td>
                            <td class="p-4 text-white font-medium"><?= htmlspecialchars($v['cliente'] ?? '-') ?></td>
                            <td class="p-4">
                                <div class="text-white font-bold">R$ <?= $valor ?></div>
                                <?php if ($v['forma_pagamento'] === 'Dinheiro' && $v['valor_pago']): ?>
                                    <div class="text-gray-400 text-xs">
                                        Pago: R$ <?= $valor_pago ?><br>
                                        Troco: R$ <?= $troco ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-white"><?= htmlspecialchars($v['autozoner_nome'] ?? '-') ?></td>
                            <td class="p-4">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    <?= $v['forma_pagamento'] === 'Dinheiro' ? 'bg-yellow-500 bg-opacity-20 text-yellow-300' : 
                                       ($v['forma_pagamento'] === 'Cartão' ? 'bg-blue-500 bg-opacity-20 text-blue-300' : 
                                       'bg-green-500 bg-opacity-20 text-green-300') ?>">
                                    <i class="fas <?= $v['forma_pagamento'] === 'Dinheiro' ? 'fa-money-bill-wave' : 
                                                  ($v['forma_pagamento'] === 'Cartão' ? 'fa-credit-card' : 'fa-qrcode') ?> mr-1"></i>
                                    <?= htmlspecialchars($v['forma_pagamento'] ?? '-') ?>
                                </span>
                            </td>
                            <td class="p-4 text-white"><?= htmlspecialchars($v['motoboy'] ?? '-') ?></td>
                            <td class="p-4">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="pago-checkbox checkbox-custom rounded bg-gray-700 border-gray-600 text-green-500 focus:ring-green-500" 
                                           data-id="<?= $v['id'] ?>" <?= $pago ? 'checked' : '' ?>>
                                    <span class="ml-2 text-sm font-medium <?= $pago ? 'text-green-400' : 'text-red-400' ?>">
                                        <?= $pago ? 'Pago' : 'Pendente' ?>
                                    </span>
                                </label>
                            </td>
                            <td class="p-4">
                                <div class="flex items-start group">
                                    <span class="obs-text text-white text-sm flex-1 cursor-pointer hover:text-purple-300 transition duration-200" 
                                          data-id="<?= $v['id'] ?>"
                                          onclick="abrirModalObs(<?= $v['id'] ?>, '<?= htmlspecialchars(addslashes($v['obs'] ?? '')) ?>')">
                                        <?= htmlspecialchars($v['obs'] ? (strlen($v['obs']) > 50 ? substr($v['obs'], 0, 50) . '...' : $v['obs']) : 'Clique para adicionar') ?>
                                    </span>
                                </div>
                            </td>
                            <td class="p-4">
                                <button class="excluir-venda btn-excluir text-red-400 hover:text-red-300 transition duration-200 p-2 rounded-lg hover:bg-red-500 hover:bg-opacity-20"
                                        data-id="<?= $v['id'] ?>"
                                        data-cliente="<?= htmlspecialchars($v['cliente'] ?? '') ?>"
                                        title="Excluir esta venda">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (empty($vendas)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-chart-bar text-4xl text-gray-500 mb-4"></i>
                        <p class="text-gray-400 text-lg mb-2">Nenhuma venda registrada</p>
                        <p class="text-gray-500 mb-4">Comece registrando sua primeira venda no caixa</p>
                        <a href="caixa.php" class="inline-flex items-center px-6 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-lg transition">
                            <i class="fas fa-cash-register mr-2"></i>Ir para o Caixa
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modais e scripts -->
    <script src="js/relatorio.js"></script>
</body>
</html>