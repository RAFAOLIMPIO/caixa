<?php
include 'includes/config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['usuario']['id'])) {
    header("Location: login.php");
    exit();
}

$numero_loja = (int)$_SESSION['usuario']['id'];
$erros = [];
$sucesso = '';

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

$editar = [];
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE id = ? AND numero_loja = ?");
    $stmt->execute([(int)$_GET['editar'], $numero_loja]);
    $editar = $stmt->fetch() ?: [];
}

$stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE numero_loja = ? ORDER BY tipo, nome");
$stmt->execute([$numero_loja]);
$funcionarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Funcionários</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-black text-white min-h-screen p-4">
    <div class="max-w-5xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold"><i class="fas fa-users-cog mr-2"></i>Gestão de Funcionários</h1>
            <a href="menu.php" class="text-sm text-purple-400 hover:underline"><i class="fas fa-arrow-left"></i> Voltar ao Menu</a>
        </div>

        <?php if (!empty($erros)): ?>
            <div class="bg-red-600 p-3 rounded mb-4">
                <?= implode('<br>', array_map('htmlspecialchars', $erros)) ?>
            </div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="bg-green-600 p-3 rounded mb-4"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <div class="bg-gray-900 p-6 rounded-xl mb-6">
            <h2 class="text-lg font-bold mb-4">
                <i class="fas fa-<?= empty($editar) ? 'plus' : 'edit' ?>"></i> <?= empty($editar) ? 'Cadastrar Novo' : 'Editar' ?> Funcionário
            </h2>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="funcionario_id" value="<?= htmlspecialchars($editar['id'] ?? '') ?>">
                <div>
                    <label>Nome</label>
                    <input type="text" name="nome" class="w-full p-2 bg-gray-800 border border-gray-700 rounded" value="<?= htmlspecialchars($editar['nome'] ?? '') ?>" required>
                </div>
                <div>
                    <label>Tipo</label>
                    <select name="tipo" class="w-full p-2 bg-gray-800 border border-gray-700 rounded" required>
                        <option value="">Selecione...</option>
                        <option value="autozoner" <?= ($editar['tipo'] ?? '') === 'autozoner' ? 'selected' : '' ?>>Autozoner</option>
                        <option value="motoboy" <?= ($editar['tipo'] ?? '') === 'motoboy' ? 'selected' : '' ?>>Motoboy</option>
                    </select>
                </div>
                <div class="col-span-2 flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 rounded text-white font-bold">
                        <i class="fas fa-save mr-2"></i><?= empty($editar) ? 'Cadastrar' : 'Atualizar' ?>
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-gray-800 p-6 rounded-xl">
            <h2 class="text-lg font-bold mb-4"><i class="fas fa-list mr-2"></i>Funcionários Cadastrados</h2>
            <?php if (empty($funcionarios)): ?>
                <div class="text-gray-400"><i class="fas fa-user-slash mr-2"></i>Nenhum funcionário cadastrado</div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($funcionarios as $funcionario): ?>
                        <div class="flex justify-between items-center bg-gray-700 p-4 rounded">
                            <div>
                                <h3 class="font-bold"><?= htmlspecialchars($funcionario['nome']) ?></h3>
                                <span class="text-sm text-gray-300"><?= ucfirst($funcionario['tipo']) ?></span>
                            </div>
                            <div class="flex space-x-2">
                                <a href="?editar=<?= $funcionario['id'] ?>" class="text-green-400 hover:text-green-300"><i class="fas fa-edit"></i></a>
                                <a href="?excluir=<?= $funcionario['id'] ?>" onclick="return confirm('Confirmar exclusão?')" class="text-red-400 hover:text-red-300"><i class="fas fa-trash"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
