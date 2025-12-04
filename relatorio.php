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
        ORDER BY v.data DESC
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
    $valor = (float)$v['valor'];
    $total_vendas += $valor;
    if ($v['pago']) {
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
                <button onclick="gerarPDF()" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg transition duration-200 shadow-lg">
                    <i class="fas fa-file-pdf mr-2"></i> Exportar PDF
                </button>
                <button onclick="abrirModalLimparTudo()" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-500 text-white rounded-lg transition duration-200 shadow-lg">
                    <i class="fas fa-trash-alt mr-2"></i> Limpar Tudo
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cards de Resumo Simplificados -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="glass-effect p-6 rounded-2xl text-center fade-in-up">
                <div class="w-12 h-12 mx-auto mb-3 bg-blue-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-white text-xl"></i>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Total em Vendas</h3>
                <p class="text-2xl font-bold text-white">R$ <?= number_format($total_vendas, 2, ',', '.') ?></p>
                <p class="text-gray-500 text-xs mt-1"><?= count($vendas) ?> vendas</p>
            </div>

            <div class="glass-effect p-6 rounded-2xl text-center fade-in-up" style="animation-delay: 0.1s">
                <div class="w-12 h-12 mx-auto mb-3 bg-green-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-white text-xl"></i>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Vendas Pagas</h3>
                <p class="text-2xl font-bold text-white">R$ <?= number_format($total_pago, 2, ',', '.') ?></p>
                <p class="text-green-400 text-xs mt-1">Recebido</p>
            </div>

            <div class="glass-effect p-6 rounded-2xl text-center fade-in-up" style="animation-delay: 0.2s">
                <div class="w-12 h-12 mx-auto mb-3 bg-yellow-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-white text-xl"></i>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">A Receber</h3>
                <p class="text-2xl font-bold text-white">R$ <?= number_format($total_pendente, 2, ',', '.') ?></p>
                <p class="text-yellow-400 text-xs mt-1">Pendente</p>
            </div>
        </div>

        <!-- Tabela de Vendas Simplificada -->
        <div class="glass-effect rounded-2xl overflow-hidden fade-in-up" style="animation-delay: 0.3s">
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
                            $pago = isset($v['pago']) && ($v['pago'] == true || $v['pago'] == 1); 
                            $data = isset($v['data']) ? date('d/m/Y H:i', strtotime($v['data'])) : '-';
                            $valor = number_format((float)$v['valor'], 2, ',', '.');
                            $troco = $v['troco'] ? number_format((float)$v['troco'], 2, ',', '.') : '0,00';
                            $valor_pago = $v['valor_pago'] ? number_format((float)$v['valor_pago'], 2, ',', '.') : '';
                        ?>
                        <tr class="venda-item <?= $pago ? 'status-pago' : 'status-pendente' ?>" data-id="<?= $v['id'] ?>">
                            <td class="p-4 text-white">
                                <div class="font-semibold"><?= htmlspecialchars($data) ?></div>
                                <div class="text-gray-400 text-xs"><?= date('d/m/Y', strtotime($v['data'])) ?></div>
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
                            <td class="p-4 text-white"><?= htmlspecialchars($v['autozoner_nome'] ?? ($v['autozoner_id'] ?? '-')) ?></td>
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
                        <a href="caixa.php" class="inline-flex items-center px-6 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-lg transition duration-200">
                            <i class="fas fa-cash-register mr-2"></i>Ir para o Caixa
                        </a>
                    </div>
                <?php endif; ?>
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
                    <span class="text-gray-400 text-sm">Total: R$ <?= number_format($total_vendas, 2, ',', '.') ?></span>
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

    $(document).ready(function() {
        // Toggle status pago/pendente
        $('.pago-checkbox').on('change', function() {
            const id = $(this).data('id');
            const pago = $(this).is(':checked') ? 1 : 0;
            const $checkbox = $(this);
            
            // Mostrar loading
            $checkbox.prop('disabled', true);
            
            $.post('relatorio.php', {action: 'toggle_pago', id: id, pago: pago})
                .done(function(response) {
                    if (response.ok) {
                        // Atualizar visualmente
                        const $row = $checkbox.closest('tr');
                        if (pago) {
                            $row.removeClass('status-pendente').addClass('status-pago');
                            $row.find('.text-red-400').removeClass('text-red-400').addClass('text-green-400').text('Pago');
                        } else {
                            $row.removeClass('status-pago').addClass('status-pendente');
                            $row.find('.text-green-400').removeClass('text-green-400').addClass('text-red-400').text('Pendente');
                        }
                        
                        // Recarregar a página para atualizar os totais
                        setTimeout(() => location.reload(), 500);
                    } else {
                        alert('Erro ao atualizar: ' + response.error);
                        $checkbox.prop('checked', !pago);
                    }
                })
                .fail(function() {
                    alert('Erro de conexão. Tente novamente.');
                    $checkbox.prop('checked', !pago);
                })
                .always(function() {
                    $checkbox.prop('disabled', false);
                });
        });

        // Excluir venda individual
        $('.excluir-venda').on('click', function() {
            const id = $(this).data('id');
            const cliente = $(this).data('cliente') || 'Venda';
            
            if (confirm(`Tem certeza que deseja excluir a venda para "${cliente}"?\n\nEsta ação não pode ser desfeita.`)) {
                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                
                $.post('relatorio.php', {action: 'excluir_venda', id: id})
                    .done(function(response) {
                        if (response.ok) {
                            mostrarNotificacao(response.message, 'success');
                            // Remover a linha da tabela
                            $btn.closest('tr').fadeOut(300, function() {
                                $(this).remove();
                                // Recarregar se não houver mais vendas
                                if ($('.venda-item').length === 0) {
                                    setTimeout(() => location.reload(), 1000);
                                }
                            });
                        } else {
                            alert('Erro: ' + response.error);
                            $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                        }
                    })
                    .fail(function() {
                        alert('Erro de conexão. Tente novamente.');
                        $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                    });
            }
        });

        // Tecla ESC fecha modais
        $(document).on('keyup', function(e) {
            if (e.key === 'Escape') {
                fecharModalObs();
                fecharModalLimparTudo();
            }
        });
    });

    function abrirModalObs(id, obsAtual) {
        currentObsId = id;
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

    function salvarObservacao() {
        if (!currentObsId) return;
        
        const obs = $('#obsField').val().trim();
        
        $.post('relatorio.php', {action: 'salvar_obs', id: currentObsId, obs: obs})
            .done(function(response) {
                if (response.ok) {
                    // Atualizar visualmente
                    $(`.obs-text[data-id="${currentObsId}"]`).text(obs || 'Clique para adicionar');
                    fecharModalObs();
                    mostrarNotificacao('Observação salva com sucesso!', 'success');
                } else {
                    alert('Erro ao salvar: ' + response.error);
                }
            })
            .fail(function() {
                alert('Erro de conexão. Tente novamente.');
            });
    }

    function confirmarLimparTudo() {
        $.post('relatorio.php', {action: 'limpar_tudo'})
            .done(function(response) {
                if (response.ok) {
                    mostrarNotificacao(response.message, 'success');
                    fecharModalLimparTudo();
                    // Recarregar a página após 2 segundos
                    setTimeout(() => location.reload(), 2000);
                } else {
                    alert('Erro: ' + response.error);
                }
            })
            .fail(function() {
                alert('Erro de conexão. Tente novamente.');
            });
    }

    function gerarPDF() {
        // Simulação de geração de PDF
        mostrarNotificacao('Gerando PDF... Em produção isso criaria um arquivo PDF para download', 'info');
        
        // Em produção, você poderia usar bibliotecas como:
        // jsPDF, pdfmake, ou gerar no servidor com Dompdf
        setTimeout(() => {
            mostrarNotificacao('PDF gerado com sucesso! (Simulação)', 'success');
        }, 1500);
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
