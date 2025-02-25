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
$loja_id = (int)$_SESSION['usuario']['id'];

// Verificar existência da loja no banco
try {
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->execute([$loja_id]);
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

// Processar formulário POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = htmlspecialchars(trim($_POST['nome'] ?? ''));
    $tipo = in_array($_POST['tipo'] ?? '', ['autozoner', 'motoboy']) ? $_POST['tipo'] : '';
    $funcionario_id = isset($_POST['funcionario_id']) ? (int)$_POST['funcionario_id'] : null;

    // Validações
    if (strlen($nome) < 3) $erros[] = "Nome deve ter pelo menos 3 caracteres";
    if (empty($tipo)) $erros[] = "Selecione um tipo válido";

    if (empty($erros)) {
        try {
            if ($funcionario_id) {
                // Atualização
                $stmt = $pdo->prepare("UPDATE funcionarios SET nome = ?, tipo = ? WHERE id = ? AND loja_id = ?");
                $stmt->execute([$nome, $tipo, $funcionario_id, $loja_id]);
                $sucesso = "Funcionário atualizado!";
            } else {
                // Inserção
                $stmt = $pdo->prepare("INSERT INTO funcionarios (nome, tipo, loja_id) VALUES (?, ?, ?)");
                $stmt->execute([$nome, $tipo, $loja_id]);
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
        $stmt = $pdo->prepare("DELETE FROM funcionarios WHERE id = ? AND loja_id = ?");
        $stmt->execute([$id_excluir, $loja_id]);
        $sucesso = $stmt->rowCount() > 0 ? "Excluído com sucesso!" : "Registro não encontrado";
    } catch (PDOException $e) {
        $erros[] = "Erro ao excluir: " . $e->getMessage();
    }
}

// =============================================
// 5. BUSCAR DADOS
// =============================================
try {
    // Dados para edição
    $editar = [];
    if (isset($_GET['editar'])) {
        $stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE id = ? AND loja_id = ?");
        $stmt->execute([(int)$_GET['editar'], $loja_id]);
        $editar = $stmt->fetch() ?: [];
    }

    // Listagem
    $stmt_autozoners = $pdo->prepare("SELECT * FROM funcionarios WHERE loja_id = ? AND tipo = 'autozoner' ORDER BY nome");
    $stmt_autozoners->execute([$loja_id]);
    $autozoners = $stmt_autozoners->fetchAll();

    $stmt_motoboys = $pdo->prepare("SELECT * FROM funcionarios WHERE loja_id = ? AND tipo = 'motoboy' ORDER BY nome");
    $stmt_motoboys->execute([$loja_id]);
    $motoboys = $stmt_motoboys->fetchAll();

} catch (PDOException $e) {
    die("Erro de carregamento: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Funcionários</title>
    <!-- Incluindo o arquivo CSS -->
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <style>
        .container { max-width: 1200px; margin: 2rem auto; padding: 1rem; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        .card { border: 1px solid #e0e0e0; padding: 1rem; margin-bottom: 1rem; border-radius: 8px; }
        .form-section { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; }
        .alert { padding: 1rem; margin: 1rem 0; border-radius: 5px; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Gestão de Funcionários</h1>
            <a href="menu.php">&larr; Voltar ao Menu</a>
        </header>

        <?php if (!empty($erros)): ?>
            <div class="alert alert-error">
                <?= implode("<br>", array_map('htmlspecialchars', $erros)) ?>
            </div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <section class="form-section">
            <h2><?= empty($editar) ? 'Cadastrar' : 'Editar' ?> Funcionário</h2>
            <form method="POST">
                <input type="hidden" name="funcionario_id" value="<?= htmlspecialchars($editar['id'] ?? '') ?>">
                
                <div style="margin-bottom: 1rem;">
                    <label>Nome Completo:</label>
                    <input type="text" name="nome" 
                           value="<?= htmlspecialchars($editar['nome'] ?? '') ?>" 
                           style="width: 100%; padding: 0.5rem;"
                           required minlength="3">
                </div>

                <div style="margin-bottom: 1rem;">
                    <label>Tipo:</label>
                    <select name="tipo" style="width: 100%; padding: 0.5rem;" required>
                        <option value="">Selecione...</option>
                        <option value="autozoner" <?= ($editar['tipo'] ?? '') === 'autozoner' ? 'selected' : '' ?>>
                            Autozoner
                        </option>
                        <option value="motoboy" <?= ($editar['tipo'] ?? '') === 'motoboy' ? 'selected' : '' ?>>
                            Motoboy
                        </option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?= empty($editar) ? 'Cadastrar' : 'Atualizar' ?>
                </button>
            </form>
        </section>

        <div class="grid">
            <!-- Seção Autozoners -->
            <div>
                <h2>Autozoners</h2>
                <?php if (!empty($autozoners)): ?>
                    <?php foreach ($autozoners as $funcionario): ?>
                        <div class="card">
                            <h3><?= htmlspecialchars($funcionario['nome']) ?></h3>
                            <div style="margin-top: 0.5rem;">
                                <a href="?editar=<?= $funcionario['id'] ?>" class="btn btn-success">Editar</a>
                                <a href="?excluir=<?= $funcionario['id'] ?>" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666;">Nenhum autozoner cadastrado</p>
                <?php endif; ?>
            </div>

            <!-- Seção Motoboys -->
            <div>
                <h2>Motoboys</h2>
                <?php if (!empty($motoboys)): ?>
                    <?php foreach ($motoboys as $funcionario): ?>
                        <div class="card">
                            <h3><?= htmlspecialchars($funcionario['nome']) ?></h3>
                            <div style="margin-top: 0.5rem;">
                                <a href="?editar=<?= $funcionario['id'] ?>" class="btn btn-success">Editar</a>
                                <a href="?excluir=<?= $funcionario['id'] ?>" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666;">Nenhum motoboy cadastrado</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>