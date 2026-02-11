<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/config.php';


$erros = [];
$sucesso = '';
$editar = null;

verificar_login();

$usuario = usuario_atual();
$usuario_id = $usuario['id'];
$numero_loja = $usuario['numero_loja'];


// Verificar se usuário existe
try {
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);

    if (!$stmt->fetch()) {
        session_destroy();
        header("Location: index.php");
        exit();
    }
} catch (PDOException $e) {
    $erros[] = "Erro ao verificar usuário: " . $e->getMessage();
}

// Verificar edição via GET
if (isset($_GET['editar'])) {
    $id_editar = (int)$_GET['editar'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$id_editar, $usuario_id]);
        $editar = $stmt->fetch();
        
        if (!$editar) {
            $erros[] = "Funcionário não encontrado ou sem permissão.";
            unset($editar);
        }
    } catch (PDOException $e) {
        $erros[] = "Erro ao buscar funcionário para edição: " . $e->getMessage();
    }
}

// CADASTRO / EDIÇÃO via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $cargo = trim($_POST['cargo'] ?? '');
    $funcionario_id = (int)($_POST['funcionario_id'] ?? 0);

    // Validações
    if (strlen($nome) < 3) {
        $erros[] = "Nome deve ter pelo menos 3 caracteres.";
    }
    
    if (empty($tipo) || !in_array($tipo, ['autozoner', 'motoboy'])) {
        $erros[] = "Tipo inválido.";
    }

    if (empty($erros)) {
        try {
            if ($funcionario_id > 0) {
                // EDIÇÃO
                $stmt = $pdo->prepare("
                    UPDATE funcionarios
                    SET nome = ?, tipo = ?, cargo = ?
                    WHERE id = ? AND usuario_id = ?
                ");
                
                $stmt->execute([
                    $nome,
                    $tipo,
                    $cargo,
                    $funcionario_id,
                    $usuario_id
                ]);
                
                $sucesso = "Funcionário atualizado com sucesso.";
                unset($editar); // Limpar modo edição
                
            } else {
                // CADASTRO NOVO
                $stmt = $pdo->prepare("
                    INSERT INTO funcionarios (nome, tipo, cargo, usuario_id, numero_loja)
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $nome,
                    $tipo,
                    $cargo,
                    $usuario_id,
                    $numero_loja
                ]);
                
                $sucesso = "Funcionário cadastrado com sucesso.";
            }
            
            // Redirecionar para evitar reenvio do formulário
            header("Location: funcionarios.php?success=" . urlencode($sucesso));
            exit();
            
        } catch (PDOException $e) {
            $erros[] = "Erro ao salvar funcionário: " . $e->getMessage();
        }
    }
}

// EXCLUSÃO via GET
if (isset($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];
    
    try {
        // Verificar se funcionário existe e pertence ao usuário
        $stmt = $pdo->prepare("
            SELECT nome, tipo FROM funcionarios
            WHERE id = ? AND usuario_id = ?
        ");
        $stmt->execute([$id, $usuario_id]);
        $func = $stmt->fetch();
        
        if ($func) {
            if ($func['tipo'] === 'autozoner') {
                // Verificar se tem vendas vinculadas
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM vendas
                    WHERE autozoner_id = ?
                ");
                $stmt->execute([$id]);
                
                if ($stmt->fetchColumn() > 0) {
                    $erros[] = "Não é possível excluir. Existem vendas vinculadas a este autozoner.";
                } else {
                    $pdo->prepare("DELETE FROM funcionarios WHERE id = ?")->execute([$id]);
                    $sucesso = "Funcionário excluído com sucesso.";
                }
            } else {
                // Motoboy - pode excluir diretamente
                $pdo->prepare("DELETE FROM funcionarios WHERE id = ?")->execute([$id]);
                $sucesso = "Funcionário excluído com sucesso.";
            }
            
            // Redirecionar após exclusão
            header("Location: funcionarios.php?success=" . urlencode($sucesso));
            exit();
        } else {
            $erros[] = "Funcionário não encontrado ou sem permissão.";
        }
    } catch (PDOException $e) {
        $erros[] = "Erro ao excluir funcionário: " . $e->getMessage();
    }
}

// Verificar mensagem de sucesso via GET
if (isset($_GET['success'])) {
    $sucesso = $_GET['success'];
}

// LISTAR FUNCIONÁRIOS
try {
    $stmt = $pdo->prepare("
        SELECT * FROM funcionarios
        WHERE usuario_id = ?
        ORDER BY 
            CASE tipo 
                WHEN 'autozoner' THEN 1
                WHEN 'motoboy' THEN 2
                ELSE 3
            END,
            nome
    ");
    $stmt->execute([$usuario_id]);
    $funcionarios = $stmt->fetchAll();
} catch (PDOException $e) {
    $erros[] = "Erro ao listar funcionários: " . $e->getMessage();
    $funcionarios = [];
}

// CALCULAR ESTATÍSTICAS
$estatisticas = [];
$entregas_motoboys = [];

try {
    foreach ($funcionarios as $func) {
        if ($func['tipo'] === 'autozoner') {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total 
                FROM vendas 
                WHERE autozoner_id = ?
            ");
            $stmt->execute([$func['id']]);
            $estatisticas[$func['id']] = $stmt->fetchColumn();
        }
        
        if ($func['tipo'] === 'motoboy') {
            // Correção importante: usar o campo correto para motoboy
            // Assumindo que a tabela vendas tem um campo 'motoboy' que armazena o NOME do motoboy
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total 
                FROM vendas 
                WHERE LOWER(motoboy) = LOWER(?)
                AND DATE(data_venda) = CURRENT_DATE
                AND status = 'normal'
            ");
            $stmt->execute([$func['nome']]);
            $entregas_motoboys[$func['id']] = $stmt->fetchColumn();
        }
    }
} catch (PDOException $e) {
    $erros[] = "Erro ao calcular estatísticas: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funcionários - AutoGest</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .funcionarios-bg {
            background: linear-gradient(135deg, #000000 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
        }
        .vendas-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 5px;
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
        .glass-effect {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .btn-modern {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        .btn-modern:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e3a8a 100%);
            transform: translateY(-1px);
        }
        .required-field:after {
            content: " *";
            color: #ef4444;
        }
    </style>
</head>
<body class="funcionarios-bg min-h-screen px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8 fade-in">
            <img src="logo.png" alt="AutoGest" class="mx-auto w-16 h-16 mb-4 rounded-full">
            <h1 class="text-3xl font-bold text-white mb-2">Gestão de Funcionários</h1>
            <p class="text-gray-400">Gerencie autozoners e motoboys da loja <?= htmlspecialchars($numero_loja) ?></p>
        </div>

        <!-- Botão Voltar -->
        <div class="flex justify-between items-center mb-6">
            <a href="menu.php" class="inline-flex items-center px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition duration-200">
                <i class="fas fa-arrow-left mr-2"></i> Voltar ao Menu
            </a>
        </div>

        <?php if (!empty($erros)): ?>
            <div class="bg-red-500 bg-opacity-20 border border-red-500 text-red-200 p-4 rounded-lg mb-6">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-circle mr-3 text-xl mt-0.5"></i>
                    <div>
                        <p class="font-semibold mb-2">Atenção:</p>
                        <?php foreach ($erros as $err): ?>
                            <p class="text-sm">• <?= $err ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="bg-green-500 bg-opacity-20 border border-green-500 text-green-200 p-4 rounded-lg mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3 text-xl"></i>
                    <p class="font-semibold"><?= htmlspecialchars($sucesso) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Formulário -->
        <div class="glass-effect p-6 rounded-2xl mb-8">
            <h2 class="text-xl font-bold text-white mb-4">
                <i class="fas fa-<?= empty($editar) ? 'plus' : 'edit' ?> mr-2"></i> 
                <?= empty($editar) ? 'Cadastrar Novo Funcionário' : 'Editar Funcionário' ?>
            </h2>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="funcionario_id" value="<?= htmlspecialchars($editar['id'] ?? '') ?>">
                
                <div class="md:col-span-2">
                    <label class="block text-white text-sm font-medium mb-2 required-field">Nome Completo</label>
                    <input type="text" name="nome" class="input-modern" 
                           value="<?= htmlspecialchars($editar['nome'] ?? '') ?>" 
                           required placeholder="Digite o nome completo" autocomplete="off">
                </div>
                
                <div>
                    <label class="block text-white text-sm font-medium mb-2 required-field">Tipo</label>
                    <select name="tipo" class="input-modern" required>
                        <option value="">Selecione o tipo...</option>
                        <option value="autozoner" <?= ($editar['tipo'] ?? '') === 'autozoner' ? 'selected' : '' ?>>Autozoner</option>
                        <option value="motoboy" <?= ($editar['tipo'] ?? '') === 'motoboy' ? 'selected' : '' ?>>Motoboy</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-white text-sm font-medium mb-2">Cargo (Opcional)</label>
                    <input type="text" name="cargo" class="input-modern" 
                           value="<?= htmlspecialchars($editar['cargo'] ?? '') ?>"
                           placeholder="Ex: Vendedor, Entregador, etc." autocomplete="off">
                </div>
                
                <div class="md:col-span-2 flex justify-end space-x-4">
                    <?php if (!empty($editar)): ?>
                        <a href="funcionarios.php" class="px-6 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition duration-200">
                            Cancelar
                        </a>
                    <?php endif; ?>
                    <button type="submit" class="btn-modern">
                        <i class="fas fa-save mr-2"></i><?= empty($editar) ? 'Cadastrar' : 'Atualizar' ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Lista de Funcionários -->
        <div class="glass-effect p-6 rounded-2xl">
            <h2 class="text-xl font-bold text-white mb-4">
                <i class="fas fa-list mr-2"></i>Funcionários Cadastrados
                <span class="text-gray-400 text-sm font-normal">(<?= count($funcionarios) ?> no total)</span>
            </h2>
            
            <?php if (empty($funcionarios)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-user-slash text-4xl text-gray-500 mb-4"></i>
                    <p class="text-gray-400 text-lg mb-2">Nenhum funcionário cadastrado</p>
                    <p class="text-gray-500">Comece cadastrando seu primeiro funcionário usando o formulário acima</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($funcionarios as $funcionario): 
                        $total_vendas = $estatisticas[$funcionario['id']] ?? 0;
                        $total_entregas = $entregas_motoboys[$funcionario['id']] ?? 0;
                    ?>
                        <div class="bg-gray-800 bg-opacity-50 p-4 rounded-lg border border-gray-700 hover:border-gray-600 transition duration-200">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex-1">
                                    <h3 class="font-bold text-white text-lg mb-1">
                                        <?= htmlspecialchars($funcionario['nome']) ?>
                                    </h3>
                                    
                                    <div class="flex items-center gap-2 mb-2">
                                        <!-- Tipo -->
                                        <span class="inline-block px-2 py-1 text-xs rounded-full 
                                            <?= $funcionario['tipo'] === 'autozoner' ? 'bg-purple-600' : 'bg-blue-600' ?>">
                                            <?= ucfirst($funcionario['tipo']) ?>
                                        </span>

                                        <!-- BADGE AUTOZONER -->
                                        <?php if ($funcionario['tipo'] === 'autozoner' && $total_vendas > 0): ?>
                                            <span class="vendas-badge bg-green-600 text-white"
                                                  title="<?= $total_vendas ?> venda(s) vinculada(s)">
                                                <i class="fas fa-chart-line mr-1"></i>
                                                <?= $total_vendas ?>
                                            </span>
                                        <?php endif; ?>

                                        <!-- BADGE MOTOBOY -->
                                        <?php if ($funcionario['tipo'] === 'motoboy' && $total_entregas > 0): ?>
                                            <span class="vendas-badge bg-blue-600 text-white"
                                                  title="<?= $total_entregas ?> entrega(s) hoje">
                                                <i class="fas fa-motorcycle mr-1"></i>
                                                <?= $total_entregas ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <a href="?editar=<?= $funcionario['id'] ?>" 
                                       class="text-green-400 hover:text-green-300 transition duration-200 p-1 rounded"
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?excluir=<?= $funcionario['id'] ?>" 
                                       onclick="return confirmExclusao(<?= $funcionario['id'] ?>, '<?= addslashes($funcionario['nome']) ?>', <?= $total_vendas ?>, '<?= $funcionario['tipo'] ?>')"
                                       class="text-red-400 hover:text-red-300 transition duration-200 p-1 rounded"
                                       title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <?php if (!empty($funcionario['cargo'])): ?>
                                <p class="text-gray-300 text-sm mb-2">
                                    <i class="fas fa-briefcase mr-1"></i>
                                    <?= htmlspecialchars($funcionario['cargo']) ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($funcionario['tipo'] === 'autozoner' && $total_vendas > 0): ?>
                                <div class="mt-2 p-2 bg-yellow-500 bg-opacity-20 border border-yellow-500 rounded text-xs text-yellow-300">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    Possui <?= $total_vendas ?> venda(s) vinculada(s)
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($funcionario['criado_em'])): ?>
                                <p class="text-gray-400 text-xs mt-2">
                                    <i class="fas fa-calendar mr-1"></i>
                                    Cadastrado em: <?= date('d/m/Y', strtotime($funcionario['criado_em'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function confirmExclusao(id, nome, totalVendas, tipo) {
        if (tipo === 'autozoner' && totalVendas > 0) {
            alert(`❌ Não é possível excluir o autozoner "${nome}"\n\nMotivo: Existem ${totalVendas} venda(s) vinculadas a este autozoner.\n\nTransfira as vendas para outro autozoner antes de excluir.`);
            return false;
        }
        
        return confirm(`Tem certeza que deseja excluir o ${tipo} "${nome}"?\n\nEsta ação não pode ser desfeita.`);
    }
    
    // Prevenir envio duplo do formulário
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        if (form) {
            let submitted = false;
            
            form.addEventListener('submit', function(e) {
                if (submitted) {
                    e.preventDefault();
                    return false;
                }
                submitted = true;
                
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Salvando...';
                    submitBtn.disabled = true;
                    
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        submitted = false;
                    }, 5000);
                }
            });
        }
    });
    </script>
</body>
</html>