<?php
include 'includes/config.php';

if (!isset($_SESSION['usuario']['id'])) {
    header("Location: index.php");
    exit();
}

$usuario_id  = $_SESSION['usuario']['id'];
$numero_loja = $_SESSION['usuario']['numero_loja'];
$sucesso = '';
$erros = [];

// =======================
// BUSCAR AUTOZONERS E MOTOBOYS
// =======================
try {
    $stmtAuto = $pdo->prepare("
        SELECT id, nome 
        FROM funcionarios 
        WHERE usuario_id = ? AND tipo = 'autozoner'
        ORDER BY nome
    ");
    $stmtAuto->execute([$usuario_id]);
    $autozoners = $stmtAuto->fetchAll();

    $stmtMoto = $pdo->prepare("
        SELECT id, nome 
        FROM funcionarios 
        WHERE usuario_id = ? AND tipo = 'motoboy'
        ORDER BY nome
    ");
    $stmtMoto->execute([$usuario_id]);
    $motoboys = $stmtMoto->fetchAll();
} catch (Exception $e) {
    $autozoners = [];
    $motoboys = [];
}

// =======================
// REGISTRAR VENDA
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $cliente = sanitizar($_POST['cliente'] ?? '');
    $valor = floatval(str_replace(['.', ','], ['', '.'], $_POST['valor'] ?? 0));
    $valor_pago = !empty($_POST['valor_pago']) 
        ? floatval(str_replace(['.', ','], ['', '.'], $_POST['valor_pago'])) 
        : null;

    $forma_pagamento = sanitizar($_POST['forma_pagamento'] ?? '');
    $motoboy_id = $_POST['motoboy_id'] ?? '';
    $obs = sanitizar($_POST['obs'] ?? '');
    $autozoner_id = (int)($_POST['autozoner_id'] ?? 0);

    $troco = 0;
    if ($forma_pagamento === 'Dinheiro' && $valor_pago !== null) {
        $troco = max($valor_pago - $valor, 0);
    }

    if (!$cliente) $erros[] = "Cliente é obrigatório.";
    if ($valor <= 0) $erros[] = "Valor inválido.";
    if ($autozoner_id <= 0) $erros[] = "Autozoner obrigatório.";

    if (empty($erros)) {
        try {
            $motoboy_final = 'Balcão';

            if ($motoboy_id === 'uber') {
                $motoboy_final = 'Uber';
            } elseif ($motoboy_id) {
                foreach ($motoboys as $m) {
                    if ($m['id'] == $motoboy_id) {
                        $motoboy_final = $m['nome'];
                        break;
                    }
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO vendas
                (cliente, valor_total, valor_pago, troco, forma_pagamento, motoboy, pago, usuario_id, autozoner_id, obs)
                VALUES
                (:cliente, :valor, :valor_pago, :troco, :forma_pagamento, :motoboy, :pago, :usuario_id, :autozoner_id, :obs)
            ");

            $stmt->execute([
                ':cliente' => $cliente,
                ':valor' => $valor,
                ':valor_pago' => $forma_pagamento === 'Dinheiro' ? $valor_pago : null,
                ':troco' => $troco,
                ':forma_pagamento' => $forma_pagamento,
                ':motoboy' => $motoboy_final,
                ':pago' => $forma_pagamento === 'Dinheiro' ? 1 : 0,
                ':usuario_id' => $usuario_id,
                ':autozoner_id' => $autozoner_id,
                ':obs' => $obs
            ]);

            $sucesso = "Venda registrada com sucesso!";
            $_POST = [];

        } catch (PDOException $e) {
            $erros[] = "Erro ao salvar venda.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Caixa - AutoGest</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="css/style.css">
</head>

<body class="caixa-bg">
<div class="max-w-4xl mx-auto p-6">

<form method="post" id="formVenda" class="grid grid-cols-2 gap-6">

<input type="text" name="cliente" placeholder="Cliente" class="input-modern" required>

<input type="text" name="valor" id="valor" placeholder="0,00" class="input-modern" required>

<select name="forma_pagamento" id="forma_pagamento" class="input-modern" onchange="toggleDinheiro()" required>
<option value="">Forma de pagamento</option>
<option value="Dinheiro">Dinheiro</option>
<option value="Pix">Pix</option>
<option value="Cartão">Cartão</option>
</select>

<select name="autozoner_id" class="input-modern" required>
<option value="">Selecione o autozoner</option>
<?php foreach ($autozoners as $a): ?>
<option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nome']) ?></option>
<?php endforeach; ?>
</select>

<div id="dinheiro" style="display:none" class="col-span-2">
<input type="text" name="valor_pago" id="valor_pago" placeholder="Valor pago" class="input-modern">
<div id="troco" class="mt-2 text-green-400 font-bold">Troco: R$ 0,00</div>
</div>

<select name="motoboy_id" class="input-modern col-span-2">
<option value="">Balcão</option>
<option value="uber">Uber</option>
<?php foreach ($motoboys as $m): ?>
<option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nome']) ?></option>
<?php endforeach; ?>
</select>

<textarea name="obs" class="input-modern col-span-2" placeholder="Observações"></textarea>

<button class="btn-modern col-span-2">Registrar Venda</button>

</form>
</div>

<script>
// =======================
// MOEDA – DIGITA NORMAL (ESQ → DIR)
// =======================
function parseMoeda(v){
    return parseFloat(v.replace(/\./g,'').replace(',','.')) || 0;
}
function formatMoeda(v){
    return v.toLocaleString('pt-BR',{minimumFractionDigits:2});
}

['valor','valor_pago'].forEach(id=>{
    const el=document.getElementById(id);
    if(!el) return;
    el.addEventListener('blur',()=>{
        const n=parseMoeda(el.value);
        if(n) el.value=formatMoeda(n);
    });
});

function toggleDinheiro(){
    const f=document.getElementById('forma_pagamento').value;
    document.getElementById('dinheiro').style.display = f==='Dinheiro'?'block':'none';
}

document.addEventListener('input',()=>{
    const v=parseMoeda(valor.value);
    const p=parseMoeda(valor_pago.value);
    document.getElementById('troco').innerText='Troco: R$ '+formatMoeda(Math.max(p-v,0));
});
</script>

</body>
</html>
