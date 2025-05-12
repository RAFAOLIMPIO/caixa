<?php
include 'includes/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar autenticação
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$numero_loja = $_SESSION['usuario']['numero_loja'];
$erros = [];
$sucesso = '';

// =============================================
// 1. PROCESSAR NOVA VENDA
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente = htmlspecialchars(trim($_POST['cliente'] ?? ''));
    $forma_pagamento = in_array($_POST['forma_pagamento'] ?? '', ['cartao', 'pix', 'dinheiro']) 
                        ? $_POST['forma_pagamento'] 
                        : '';
    $valor = (float)str_replace(',', '.', $_POST['valor'] ?? 0);
    $valor_recebido = (float)str_replace(',', '.', $_POST['valor_recebido'] ?? 0);
    $motoboy = htmlspecialchars(trim($_POST['motoboy'] ?? ''));
    $autozoner_id = (int)($_POST['autozoner_id'] ?? 0);

    if (empty($cliente)) $erros[] = "Nome do cliente é obrigatório!";
    if (empty($forma_pagamento)) $erros[] = "Selecione a forma de pagamento!";
    if ($valor <= 0) $erros[] = "Valor deve ser maior que zero!";

    if ($forma_pagamento === 'dinheiro' && $valor_recebido < $valor) {
        $erros[] = "Valor recebido insuficiente!";
    }

    if (empty($erros)) {
        try {
            $troco = ($forma_pagamento === 'dinheiro') ? ($valor_recebido - $valor) : 0;

            $stmt = $pdo->prepare("INSERT INTO vendas 
                (cliente, forma_pagamento, valor, valor_recebido, troco, motoboy, pago, autozoner_id, numero_loja) 
                VALUES (:cliente, :forma, :valor, :recebido, :troco, :motoboy, :pago, :autozoner, :loja)");
            
            $stmt->execute([
                ':cliente' => $cliente,
                ':forma' => $forma_pagamento,
                ':valor' => $valor,
                ':recebido' => ($forma_pagamento === 'dinheiro') ? $valor_recebido : null,
                ':troco' => $troco,
                ':motoboy' => $motoboy,
                ':pago' => false,
                ':autozoner' => $autozoner_id,
                ':loja' => $numero_loja
            ]);

            $sucesso = "Venda registrada com sucesso!";

        } catch (PDOException $e) {
            $erros[] = "Erro ao registrar venda: " . $e->getMessage();
        }
    }
}

// =============================================
// 2. BUSCAR DADOS PARA O FORMULÁRIO
// =============================================
try {
    $stmt_autozoners = $pdo->prepare("SELECT id, nome FROM funcionarios 
                                      WHERE numero_loja = ? AND tipo = 'autozoner'");
    $stmt_autozoners->execute([$numero_loja]);
    $autozoners = $stmt_autozoners->fetchAll();

    $stmt_motoboys = $pdo->prepare("SELECT nome FROM funcionarios 
                                    WHERE numero_loja = ? AND tipo = 'motoboy'");
    $stmt_motoboys->execute([$numero_loja]);
    $motoboys = $stmt_motoboys->fetchAll(PDO::FETCH_COLUMN);

    $stmt_vendas = $pdo->prepare("SELECT *, DATE_FORMAT(criado_em, '%d/%m/%Y %H:%i') as data_formatada 
                                  FROM vendas 
                                  WHERE numero_loja = ? 
                                  ORDER BY criado_em DESC 
                                  LIMIT 10");
    $stmt_vendas->execute([$numero_loja]);
    $vendas = $stmt_vendas->fetchAll();

} catch (PDOException $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}
?>
