<?php
include 'includes/config.php';

if (!isset($_SESSION['usuario']['id'])) {
    header("Location: index.php");
    exit();
}

$usuario_id = $_SESSION['usuario']['id'];
$numero_loja = $_SESSION['usuario']['numero_loja'];
$sucesso = '';
$erros = [];

// =========================
// BUSCAR AUTOZONERS E MOTOBOYS
// =========================
try {
    $stmtAuto = $pdo->prepare("
        SELECT id, nome 
        FROM funcionarios 
        WHERE usuario_id = ? 
          AND tipo = 'autozoner'
        ORDER BY nome
    ");
    $stmtAuto->execute([$usuario_id]);
    $autozoners = $stmtAuto->fetchAll();

    $stmtMoto = $pdo->prepare("
        SELECT id, nome 
        FROM funcionarios 
        WHERE usuario_id = ? 
          AND tipo = 'motoboy'
        ORDER BY nome
    ");
    $stmtMoto->execute([$usuario_id]);
    $motoboys = $stmtMoto->fetchAll();
} catch (Exception $e) {
    $autozoners = [];
    $motoboys = [];
    error_log("Erro ao buscar funcionários: " . $e->getMessage());
}

// =========================
// PROCESSAR VENDA
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $cliente = sanitizar($_POST['cliente'] ?? '');
    $valor = floatval(str_replace(['.', ','], ['', '.'], $_POST['valor'] ?? 0));
    $valor_pago = $_POST['valor_pago'] !== ''
        ? floatval(str_replace(['.', ','], ['', '.'], $_POST['valor_pago']))
        : null;

    $forma_pagamento = sanitizar($_POST['forma_pagamento'] ?? '');
    $motoboy_id = $_POST['motoboy_id'] ?? '';
    $obs = sanitizar($_POST['obs'] ?? '');
    $autozoner_id = (int)($_POST['autozoner_id'] ?? 0);

    // Troco
    $troco = 0;
    if ($forma_pagamento === 'Dinheiro' && $valor_pago !== null) {
        $troco = max($valor_pago - $valor, 0);
    }

    // Validações
    if (empty($cliente)) $erros[] = "Cliente é obrigatório.";
    if ($valor <= 0) $erros[] = "Valor deve ser maior que zero.";
    if ($autozoner_id <= 0) $erros[] = "Autozoner é obrigatório.";

    if ($forma_pagamento === 'Dinheiro' && $valor_pago < $valor) {
        $erros[] = "Valor pago não pode ser menor que o valor da venda.";
    }

    if (empty($autozoners)) {
        $erros[] = "É necessário cadastrar pelo menos um autozoner antes de registrar vendas.";
    }

    if (empty($erros)) {
        try {
            // Motoboy final
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
                (:cliente, :valor_total, :valor_pago, :troco, :forma_pagamento, :motoboy, :pago, :usuario_id, :autozoner_id, :obs)
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
            $erros[] = "Erro ao salvar venda.";
            error_log("Erro PDO caixa: " . $e->getMessage());
        }
    }
}
?>

<!-- HTML permanece IGUAL AO SEU -->
<!-- JS abaixo é o ÚNICO AJUSTE DE MOEDA -->

<script>
function parseMoeda(v){
    return parseFloat(v.replace(/\./g,'').replace(',','.')) || 0;
}
function formatMoeda(v){
    return v.toLocaleString('pt-BR',{minimumFractionDigits:2});
}

// Formatar somente ao sair do campo
['valor','valor_pago'].forEach(id=>{
    const el=document.getElementById(id);
    if(!el) return;
    el.addEventListener('blur',()=>{
        const n=parseMoeda(el.value);
        if(n>0) el.value=formatMoeda(n);
    });
});

// Troco mantém funcionamento
document.addEventListener('input',()=>{
    const v=parseMoeda(valor.value);
    const p=parseMoeda(valor_pago.value);
    document.getElementById('troco_display').innerText =
        'R$ '+formatMoeda(Math.max(p-v,0));
});
</script>