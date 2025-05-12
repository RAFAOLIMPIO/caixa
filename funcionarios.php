<?php
include 'includes/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// =============================================
// 1. VERIFICAÇÃO DE AUTENTICAÇÃO
// =============================================
if (!isset($_SESSION['usuario']['id'])) {
    header("Location: login.php");
    exit();
}

// =============================================
// 2. VALIDAÇÃO DO ID DA LOJA
// =============================================
$numero_loja = (int)$_SESSION['usuario']['id'];

try {
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->execute([$numero_loja]);
    if ($stmt->rowCount() === 0) {
        session_destroy();
        die("Erro crítico: Loja não encontrada! Faça login novamente.");
    }
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// =============================================
// 3. LÓGICA PRINCIPAL
// =============================================
$erros = [];
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = htmlspecialchars(trim($_POST['nome'] ?? ''));
    $tipo = in_array($_POST['tipo'] ?? '', ['autozoner', 'motoboy']) ? $_POST['tipo'] : '';
    $funcionario_id = isset($_POST['funcionario_id']) ? (int)$_POST['funcionario_id'] : null;

    if (strlen($nome) < 3) $erros[] = "Nome deve ter pelo menos 3 caracteres";
    if (empty($tipo)) $erros[] = "Selecione um tipo válido";

    if (empty($erros)) {
        try {
            if ($funcionario_id) {
                $stmt = $pdo->prepare("UPDATE funcionarios SET nome = ?, tipo = ? WHERE id = ? AND numero_loja = ?");
                $stmt->execute([$nome, $tipo, $funcionario_id, $numero_loja]);
                $sucesso = "Funcionário atualizado!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO funcionarios (nome, tipo, numero_loja) VALUES (?, ?, ?)");
                $stmt->execute([$nome, $tipo, $numero_loja]);
                $sucesso = "Funcionário cadastrado!";
            }
        } catch (PDOException $e) {
            error_log("Erro DB: " . $e->getMessage());
            $erros[] = "Operação falhou. Tente novamente.";
        }
    }
}

// =============================================
// 4. PROCESSAR EXCLUSÃO
// =============================================
if (isset($_GET['excluir'])) {
    $id_excluir = (int)$_GET['excluir'];
    try {
        $stmt = $pdo->prepare("DELETE FROM funcionarios WHERE id = ? AND numero_loja = ?");
        $stmt->execute([$id_excluir, $numero_loja]);
        $sucesso = $stmt->rowCount() > 0 ? "Excluído com sucesso!" : "Registro não encontrado";
    } catch (PDOException $e) {
        $erros[] = "Erro ao excluir: " . $e->getMessage();
    }
}

// =============================================
// 5. BUSCAR DADOS
// =============================================
try {
    $editar = [];
    if (isset($_GET['editar'])) {
        $stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE id = ? AND numero_loja = ?");
        $stmt->execute([(int)$_GET['editar'], $numero_loja]);
        $editar = $stmt->fetch() ?: [];
    }

    $stmt_autozoners = $pdo->prepare("SELECT * FROM funcionarios WHERE numero_loja = ? AND tipo = 'autozoner' ORDER BY nome");
    $stmt_autozoners->execute([$numero_loja]);
    $autozoners = $stmt_autozoners->fetchAll();

    $stmt_motoboys = $pdo->prepare("SELECT * FROM funcionarios WHERE numero_loja = ? AND tipo = 'motoboy' ORDER BY nome");
    $stmt_motoboys->execute([$numero_loja]);
    $motoboys = $stmt_motoboys->fetchAll();

} catch (PDOException $e) {
    die("Erro de carregamento: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Funcionários</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="main-container">
        <main class="content-area">
            <div class="card">
                <div class="flex-header">
                    <h1 class="brand-title">
                        <i class="fas fa-users-cog"></i>
                        Gestão de Funcionários
                    </h1>
                    <a href="menu.php" class="btn btn-secondary hover-scale">
                        <i class="fas fa-arrow-left"></i>
                        Voltar ao Menu
                    </a>
                </div>

                <?php if (!empty($erros)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= implode('<br>', array_map('htmlspecialchars', $erros)) ?>
                    </div>
                <?php endif; ?>

                <?php if ($sucesso): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($sucesso) ?>
                    </div>
                <?php endif; ?>

                <section class="card form-section">
                    <h2 class="section-title">
                        <i class="fas fa-<?= empty($editar) ? 'plus' : 'edit' ?>"></i>
                        <?= empty($editar) ? 'Cadastrar Novo' : 'Editar' ?> Funcionário
                    </h2>
                    
                    <form method="POST" class="form-stack">
                        <input type="hidden" name="funcionario_id" value="<?= htmlspecialchars($editar['id'] ?? '') ?>">
                        
                        <div class="form-group">
                            <label class="input-label">
                                <i class="fas fa-user-tag"></i>
                                Nome Completo
                            </label>
                            <input type="text" 
                                   name="nome" 
                                   class="form-input"
                                   value="<?= htmlspecialchars($editar['nome'] ?? '') ?>" 
                                   required
                                   minlength="3">
                        </div>

                        <div class="form-group">
                            <label class="input-label">
                                <i class="fas fa-user-check"></i>
                                Tipo de Funcionário
                            </label>
                            <div class="select-wrapper">
                                <select name="tipo" class="form-input" required>
                                    <option value="">Selecione o tipo...</option>
                                    <option value="autozoner" <?= ($editar['tipo'] ?? '') === 'autozoner' ? 'selected' : '' ?>>
                                        Autozoner
                                    </option>
                                    <option value="motoboy" <?= ($editar['tipo'] ?? '') === 'motoboy' ? 'selected' : '' ?>>
                                        Motoboy
                                    </option>
                                </select>
                                <i class="fas fa-chevron-down select-arrow"></i>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary hover-scale">
                            <i class="fas fa-save"></i>
                            <?= empty($editar) ? 'Cadastrar' : 'Atualizar' ?>
                        </button>
                    </form>
                </section>

                <div class="grid-col-2">
                    <section class="card">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-user-tie"></i>
                                Autozoners
                            </h2>
                        </div>
                        
                        <?php if (!empty($autozoners)): ?>
                            <div class="funcionarios-list">
                                <?php foreach ($autozoners as $funcionario): ?>
                                    <div class="funcionario-item">
                                        <div class="funcionario-info">
                                            <h3><?= htmlspecialchars($funcionario['nome']) ?></h3>
                                            <span class="funcionario-tipo">Autozoner</span>
                                        </div>
                                        <div class="funcionario-actions">
                                            <a href="?editar=<?= $funcionario['id'] ?>" class="btn btn-success hover-scale">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?excluir=<?= $funcionario['id'] ?>" 
                                               class="btn btn-danger hover-scale"
                                               onclick="return confirm('Tem certeza que deseja excluir?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-user-slash"></i>
                                Nenhum autozoner cadastrado
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="card">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-motorcycle"></i>
                                Motoboys
                            </h2>
                        </div>
                        
                        <?php if (!empty($motoboys)): ?>
                            <div class="funcionarios-list">
                                <?php foreach ($motoboys as $funcionario): ?>
                                    <div class="funcionario-item">
                                        <div class="funcionario-info">
                                            <h3><?= htmlspecialchars($funcionario['nome']) ?></h3>
                                            <span class="funcionario-tipo">Motoboy</span>
                                        </div>
                                        <div class="funcionario-actions">
                                            <a href="?editar=<?= $funcionario['id'] ?>" class="btn btn-success hover-scale">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?excluir=<?= $funcionario['id'] ?>" 
                                               class="btn btn-danger hover-scale"
                                               onclick="return confirm('Tem certeza que deseja excluir?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-user-slash"></i>
                                Nenhum motoboy cadastrado
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
