<?php
include 'includes/config.php';
include 'includes/funcoes.php';

if (!isset($_SESSION['usuario']['id'])) {
    header("Location: index.php");
    exit();
}

$numero_loja = $_SESSION['usuario']['numero_loja'] ?? $_SESSION['usuario']['id'];
$sucesso = '';
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente = sanitizar($_POST['cliente'] ?? '');
    $valor = floatval(str_replace([',','R$',' '], ['.','',''], $_POST['valor'] ?? 0));
    $valor_pago = floatval(str_replace([',','R$',' '], ['.','',''], $_POST['valor_pago'] ?? 0));
    $troco = ($valor_pago > $valor) ? ($valor_pago - $valor) : 0;
    $forma_pagamento = sanitizar($_POST['forma_pagamento'] ?? '');
    $motoboy = sanitizar($_POST['motoboy'] ?? '');
    $obs = sanitizar($_POST['obs'] ?? '');
    $autozoner_id = $_POST['autozoner_id'] ?? null;

    // Autozoner obrigatório conforme solicitado
    $erros = array_merge($erros, validar_campos_obrigatorios(['autozoner_id' => 'Campo Autozoner é obrigatório.'], ['autozoner_id' => 'Campo Autozoner é obrigatório.']));

    if (!$cliente) $erros[] = "Cliente é obrigatório.";
    if (!$valor || $valor <= 0) $erros[] = "Valor inválido.";

    if (count($erros) === 0) {
        try {
            $sql = "INSERT INTO vendas (cliente, valor, valor_pago, troco, forma_pagamento, motoboy, pago, numero_loja, autozoner_id, obs) 
                    VALUES (:cliente, :valor, :valor_pago, :troco, :forma_pagamento, :motoboy, :pago, :numero_loja, :autozoner_id, :obs)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':cliente' => $cliente,
                ':valor' => $valor,
                ':valor_pago' => $valor_pago > 0 ? $valor_pago : null,
                ':troco' => $troco,
                ':forma_pagamento' => $forma_pagamento,
                ':motoboy' => $motoboy,
                ':pago' => 0,
                ':numero_loja' => $numero_loja,
                ':autozoner_id' => $autozoner_id ?: null,
                ':obs' => $obs
            ]);
            $sucesso = "Venda registrada com sucesso.";
        } catch (PDOException $e) {
            $erros[] = "Erro ao salvar: " . $e->getMessage();
        }
    }
}

// Busca autozoners (lista de funcionarios) - se não houver coluna 'cargo', pega todos
try {
    $stmtAuto = $pdo->prepare("SELECT id, nome, cargo FROM funcionarios WHERE numero_loja = ? OR numero_loja IS NULL");
    $stmtAuto->execute([$numero_loja]);
    $autozoners = $stmtAuto->fetchAll();
} catch (Exception $e) {
    $autozoners = [];
}

// renderiza página (simples)
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Caixa - Registrar Venda</title>
<script src="js/scripts.js"></script>
<link rel="stylesheet" href="https://cdn.tailwindcss.com">
</head>
<body class="bg-gray-900 text-white p-6">
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">Registrar Venda</h1>
    <?php if ($sucesso): ?>
        <div class="bg-green-600 p-3 rounded mb-4"><?=htmlspecialchars($sucesso)?></div>
    <?php endif; ?>
    <?php if ($erros): ?>
        <div class="bg-red-600 p-3 rounded mb-4">
            <?php foreach ($erros as $err) echo htmlspecialchars($err)."<br>"; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="space-y-4 bg-gray-800 p-4 rounded">
        <div>
            <label class="block text-sm">Cliente</label>
            <input name="cliente" required class="w-full p-2 bg-gray-700 rounded" />
        </div>
        <div>
            <label class="block text-sm">Valor</label>
            <input name="valor" required class="w-full p-2 bg-gray-700 rounded" />
        </div>
        <div>
            <label class="block text-sm">Valor Pago (opcional)</label>
            <input name="valor_pago" class="w-full p-2 bg-gray-700 rounded" />
        </div>
        <div>
            <label class="block text-sm">Forma Pagamento</label>
            <select name="forma_pagamento" class="w-full p-2 bg-gray-700 rounded">
                <option value="">Selecione</option>
                <option>Dinheiro</option>
                <option>Cartão</option>
                <option>Pix</option>
                <option>Transferência</option>
            </select>
        </div>
        <div>
            <label class="block text-sm">Motoboy</label>
            <select name="motoboy" class="w-full p-2 bg-gray-700 rounded">
                <option value="">Selecione</option>
                <option>Uber</option>
                <option>Balcão</option>
                <option>Motoboy A</option>
                <option>Motoboy B</option>
            </select>
        </div>
        <div>
            <label class="block text-sm">Autozoner (obrigatório)</label>
            <select name="autozoner_id" class="w-full p-2 bg-gray-700 rounded" required>
                <option value="">Selecione Autozoner</option>
                <?php foreach ($autozoners as $a): ?>
                    <option value="<?=htmlspecialchars($a['id'])?>"><?=htmlspecialchars($a['nome'] . ($a['cargo'] ? " ({$a['cargo']})" : ""))?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm">Observação (obs)</label>
            <textarea name="obs" class="w-full p-2 bg-gray-700 rounded"></textarea>
        </div>
        <div class="flex gap-2">
            <button class="bg-blue-600 px-4 py-2 rounded">Salvar</button>
            <a href="relatorio.php" class="bg-gray-600 px-4 py-2 rounded">Relatório</a>
        </div>
    </form>
</div>
</body>
</html>
