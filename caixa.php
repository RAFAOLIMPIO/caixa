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

/* ======================================================
   BUSCAR AUTOZONERS E MOTOBOYS (SEM ATIVO / SEM LOJA)
====================================================== */
try {
    $stmtAuto = $pdo->prepare("
        SELECT id, nome
        FROM funcionarios
        WHERE usuario_id = :usuario_id
          AND tipo = 'autozoner'
        ORDER BY nome
    ");
    $stmtAuto->execute([':usuario_id' => $usuario_id]);
    $autozoners = $stmtAuto->fetchAll();

    $stmtMoto = $pdo->prepare("
        SELECT id, nome
        FROM funcionarios
        WHERE usuario_id = :usuario_id
          AND tipo = 'motoboy'
        ORDER BY nome
    ");
    $stmtMoto->execute([':usuario_id' => $usuario_id]);
    $motoboys = $stmtMoto->fetchAll();
} catch (PDOException $e) {
    $autozoners = [];
    $motoboys = [];
    error_log("Erro ao buscar funcionários: ".$e->getMessage());
}

/* ======================================================
   PROCESSAR VENDA
====================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $cliente = sanitizar($_POST['cliente'] ?? '');
    $forma_pagamento = sanitizar($_POST['forma_pagamento'] ?? '');
    $obs = sanitizar($_POST['obs'] ?? '');
    $autozoner_id = (int)($_POST['autozoner_id'] ?? 0);
    $motoboy_id = $_POST['motoboy_id'] ?? '';

    // Conversão segura de moeda
    $valor = floatval(str_replace(['.', ','], ['', '.'], $_POST['valor'] ?? 0));
    $valor_pago = !empty($_POST['valor_pago'])
        ? floatval(str_replace(['.', ','], ['', '.'], $_POST['valor_pago']))
        : null;

    // Troco
    $troco = 0;
    if ($forma_pagamento === 'Dinheiro' && $valor_pago !== null) {
        $troco = max($valor_pago - $valor, 0);
    }

    /* ======================
       VALIDAÇÕES
    ====================== */
    if ($cliente === '') $erros[] = "Cliente é obrigatório.";
    if ($valor <= 0) $erros[] = "Valor da venda inválido.";
    if ($autozoner_id <= 0) $erros[] = "Autozoner é obrigatório.";

    if ($forma_pagamento === 'Dinheiro' && $valor_pago < $valor) {
        $erros[] = "Valor pago não pode ser menor que o valor da venda.";
    }

    if (empty($autozoners)) {
        $erros[] = "Cadastre ao menos um autozoner antes de registrar vendas.";
    }

    /* ======================
       SALVAR VENDA
    ====================== */
    if (empty($erros)) {
        try {

            // Motoboy final
            $motoboy_final = 'Balcão';

            if ($motoboy_id === 'uber') {
                $motoboy_final = 'Uber';
            } elseif (!empty($motoboy_id)) {
                foreach ($motoboys as $m) {
                    if ($m['id'] == $motoboy_id) {
                        $motoboy_final = $m['nome'];
                        break;
                    }
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO vendas (
                    cliente,
                    valor_total,
                    valor_pago,
                    troco,
                    forma_pagamento,
                    motoboy,
                    pago,
                    usuario_id,
                    autozoner_id,
                    obs
                ) VALUES (
                    :cliente,
                    :valor_total,
                    :valor_pago,
                    :troco,
                    :forma_pagamento,
                    :motoboy,
                    :pago,
                    :usuario_id,
                    :autozoner_id,
                    :obs
                )
            ");

            $stmt->execute([
                ':cliente'        => $cliente,
                ':valor_total'    => $valor,
                ':valor_pago'     => $forma_pagamento === 'Dinheiro' ? $valor_pago : null,
                ':troco'          => $troco,
                ':forma_pagamento'=> $forma_pagamento,
                ':motoboy'        => $motoboy_final,
                ':pago'           => ($forma_pagamento === 'Dinheiro'),
                ':usuario_id'     => $usuario_id,
                ':autozoner_id'   => $autozoner_id,
                ':obs'            => $obs
            ]);

            $sucesso = "Venda registrada com sucesso!";
            $_POST = [];

        } catch (PDOException $e) {
            $erros[] = "Erro ao registrar venda.";
            error_log("Erro PDO caixa: ".$e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Caixa - AutoGest</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="css/style.css">
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="caixa-bg min-h-screen px-4 py-8">

<div class="max-w-4xl mx-auto">

<?php if ($sucesso): ?>
<div class="bg-green-500 bg-opacity-20 border border-green-500 text-green-200 p-4 rounded mb-6">
    <?= htmlspecialchars($sucesso) ?>
</div>
<?php endif; ?>

<?php if (!empty($erros)): ?>
<div class="bg-red-500 bg-opacity-20 border border-red-500 text-red-200 p-4 rounded mb-6">
<?php foreach ($erros as $e): ?>
    <p>• <?= htmlspecialchars($e) ?></p>
<?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" id="formVenda" class="grid grid-cols-1 md:grid-cols-2 gap-6">

<input type="text" name="cliente" class="input-modern" placeholder="Cliente" required>

<input type="text" name="valor" id="valor" class="input-modern" placeholder="0,00" required>

<select name="forma_pagamento" id="forma_pagamento" class="input-modern" onchange="toggleDinheiro()" required>
<option value="">Forma de pagamento</option>
<option value="Dinheiro">Dinheiro</option>
<option value="Pix">Pix</option>
<option value="Cartão">Cartão</option>
</select>

<select name="autozoner_id" class="input-modern" required>
<option value="">Autozoner</option>
<?php foreach ($autozoners as $a): ?>
<option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nome']) ?></option>
<?php endforeach; ?>
</select>

<div id="dinheiro" class="md:col-span-2 hidden">
<input type="text" name="valor_pago" id="valor_pago" class="input-modern" placeholder="Valor pago">
<div id="troco" class="text-green-400 font-bold mt-2">Troco: R$ 0,00</div>
</div>

<select name="motoboy_id" class="input-modern md:col-span-2">
<option value="">Balcão</option>
<option value="uber">Uber</option>
<?php foreach ($motoboys as $m): ?>
<option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nome']) ?></option>
<?php endforeach; ?>
</select>

<textarea name="obs" class="input-modern md:col-span-2" placeholder="Observações"></textarea>

<button class="btn-modern md:col-span-2">Registrar Venda</button>

</form>
</div>

<script>
/* ==========================
   MOEDA – DIGITA NORMAL
========================== */
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
        if(n>0) el.value=formatMoeda(n);
    });
});

function toggleDinheiro(){
    document.getElementById('dinheiro').classList.toggle(
        'hidden',
        forma_pagamento.value!=='Dinheiro'
    );
}

document.addEventListener('input',()=>{
    const v=parseMoeda(valor.value);
    const p=parseMoeda(valor_pago.value);
    document.getElementById('troco').innerText =
        'Troco: R$ '+formatMoeda(Math.max(p-v,0));
});
</script>

</body>
</html>