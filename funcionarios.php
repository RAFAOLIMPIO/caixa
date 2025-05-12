<?php
include 'includes/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. VERIFICAÇÃO DE AUTENTICAÇÃO
if (!isset($_SESSION['usuario']['id'])) {
    header("Location: login.php");
    exit();
}

$numero_loja = (int)$_SESSION['usuario']['id'];
$erros = [];
$sucesso = '';

// 2. INSERÇÃO/ATUALIZAÇÃO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = htmlspecialchars(trim($_POST['nome'] ?? ''));
    $tipo = $_POST['tipo'] ?? '';
    $funcionario_id = isset($_POST['funcionario_id']) ? (int)$_POST['funcionario_id'] : null;

    if (strlen($nome) < 3) $erros[] = "Nome deve ter pelo menos 3 caracteres.";
    if (!in_array($tipo, ['autozoner', 'motoboy'])) $erros[] = "Tipo inválido.";

    if (empty($erros)) {
        try {
            if ($funcionario_id) {
                $stmt = $pdo->prepare("UPDATE funcionarios SET nome = ?, tipo = ? WHERE id = ? AND numero_loja = ?");
                $stmt->execute([$nome, $tipo, $funcionario_id, $numero_loja]);
                $sucesso = "Funcionário atualizado com sucesso.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO funcionarios (nome, tipo, numero_loja) VALUES (?, ?, ?)");
                $stmt->execute([$nome, $tipo, $numero_loja]);
                $sucesso = "Funcionário cadastrado com sucesso.";
            }
        } catch (PDOException $e) {
            $erros[] = "Erro ao salvar funcionário: " . $e->getMessage();
        }
    }
}

// 3. EXCLUSÃO
if (isset($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];
    try {
        $stmt = $pdo->prepare("DELETE FROM funcionarios WHERE id = ? AND numero_loja = ?");
        $stmt->execute([$id, $numero_loja]);
        $sucesso = "Funcionário excluído com sucesso.";
    } catch (PDOException $e) {
        $erros[] = "Erro ao excluir: " . $e->getMessage();
    }
}

// 4. EDIÇÃO
$editar = [];
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE id = ? AND numero_loja = ?");
    $stmt->execute([(int)$_GET['editar'], $numero_loja]);
    $editar = $stmt->fetch() ?: [];
}

// 5. LISTAGEM
$stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE numero_loja = ? ORDER BY tipo, nome");
$stmt->execute([$numero_loja]);
$funcionarios = $stmt->fetchAll();
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
                        <i class="fas fa-users-cog"></i> Gestão de Funcionários
                    </h1>
                    <a href="menu.php" class="btn btn-secondary hover-scale">
                        <i class="fas fa-arrow-left"></i> Voltar ao Menu
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
                            <label class="input-label"><i class="fas fa-user"></i> Nome</label>
                            <input type="text" name="nome" class="form-input" value="<?= htmlspecialchars($editar['nome'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="input-label"><i class="fas fa-user-tag"></i> Tipo</label>
                            <select name="tipo" class="form-input" required>
                                <option value="">Selecione...</option>
                                <option value="autozoner" <?= ($editar['tipo'] ?? '') === 'autozoner' ? 'selected' : '' ?>>Autozoner</option>
                                <option value="motoboy" <?= ($editar['tipo'] ?? '') === 'motoboy' ? 'selected' : '' ?>>Motoboy</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary hover-scale">
                            <i class="fas fa-save"></i>
                            <?= empty($editar) ? 'Cadastrar' : 'Atualizar' ?>
                        </button>
                    </form>
                </section>

                <section class="card">
                    <h2 class="section-title"><i class="fas fa-list"></i> Funcionários Cadastrados</h2>
                    <?php if (empty($funcionarios)): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-slash"></i> Nenhum funcionário cadastrado
                        </div>
                    <?php else: ?>
                        <div class="funcionarios-list">
                            <?php foreach ($funcionarios as $funcionario): ?>
                                <div class="funcionario-item">
                                    <div class="funcionario-info">
                                        <h3><?= htmlspecialchars($funcionario['nome']) ?></h3>
                                        <span class="funcionario-tipo"><?= ucfirst($funcionario['tipo']) ?></span>
                                    </div>
                                    <div class="funcionario-actions">
                                        <a href="?editar=<?= $funcionario['id'] ?>" class="btn btn-success btn-icon hover-scale" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?excluir=<?= $funcionario['id'] ?>" class="btn btn-danger btn-icon hover-scale" onclick="return confirm('Confirmar exclusão?')" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</body>
</html>

