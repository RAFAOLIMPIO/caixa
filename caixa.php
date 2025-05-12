<?php
include 'includes/config.php';

if (!isset($_SESSION['usuario']['id'])) {
    header("Location: login.php");
    exit();
}

$numero_loja = (int)$_SESSION['usuario']['id'];
$sucesso = '';
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente = htmlspecialchars(trim($_POST['cliente'] ?? ''));
    $valor = (float)$_POST['valor'];
    $forma_pagamento = $_POST['forma_pagamento'] ?? '';
    $motoboy = $_POST['motoboy'] ?? '';
    $autozoner_id = $_POST['autozoner_id'] ?? null;

    if (!$cliente || !$valor || !$forma_pagamento) {
        $erros[] = "Preencha todos os campos obrigatórios.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO vendas (cliente, valor, forma_pagamento, motoboy, numero_loja, autozoner_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$cliente, $valor, $forma_pagamento, $motoboy, $numero_loja, $autozoner_id]);
            $sucesso = "Venda registrada com sucesso.";
        } catch (PDOException $e) {
            $erros[] = "Erro ao salvar: " . $e->getMessage();
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE tipo = 'autozoner' AND numero_loja = ?");
$stmt->execute([$numero_loja]);
$autozoners = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Controle de Caixa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-black text-white min-h-screen p-4">
    <div class="max-w-xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold"><i class="fas fa-cash-register mr-2"></i>Controle de Caixa</h1>
            <a href="menu.php" class="text-sm text-purple-400 hover:underline"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>

        <?php if (!empty($erros)): ?>
            <div class="bg-red-600 p-3 rounded mb-4">
                <?= implode('<br>', array_map('htmlspecialchars', $erros)) ?>
            </div>
        <?php endif; ?>
        <?php if ($sucesso): ?>
            <div class="bg-green-600 p-3 rounded mb-4"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4 bg-gray-900 p-6 rounded-xl">
            <div>
                <label>Cliente</label>
                <input type="text" name="cliente" required class="w-full p-2 bg-gray-800 border border-gray-700 rounded">
            </div>
            <div>
                <label>Valor</label>
                <input type="number" name="valor" step="0.01" required class="w-full p-2 bg-gray-800 border border-gray-700 rounded">
            </div>
            <div>
                <label>Forma de Pagamento</label>
                <select name="forma_pagamento" class="w-full p-2 bg-gray-800 border border-gray-700 rounded" required>
                    <option value="pix">PIX</option>
                    <option value="credito">Crédito</option>
                    <option value="debito">Débito</option>
                    <option value="dinheiro">Dinheiro</option>
                </select>
            </div>
            <div>
                <label>Motoboy</label>
                <input type="text" name="motoboy" class="w-full p-2 bg-gray-800 border border-gray-700 rounded">
            </div>
            <div>
                <label>Autozoner</label>
                <select name="autozoner_id" class="w-full p-2 bg-gray-800 border border-gray-700 rounded">
                    <option value="">Não informado</option>
                    <?php foreach ($autozoners as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="w-full py-2 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 rounded font-bold text-white">
                <i class="fas fa-save mr-2"></i>Salvar
            </button>
        </form>
    </div>
</body>
</html>
