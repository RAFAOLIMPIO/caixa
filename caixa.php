<?php
include 'includes/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar autenticação
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$numero_loja = $_SESSION['usuario']['numero_loja'] ?? null;
$erros = [];
$sucesso = '';

// PROCESSAR NOVA VENDA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente = htmlspecialchars(trim($_POST['cliente'] ?? ''));
    $forma_pagamento = in_array($_POST['forma_pagamento'] ?? '', ['Cartão', 'Pix', 'Dinheiro']) 
                        ? $_POST['forma_pagamento'] 
                        : '';
    $valor = (float)str_replace(',', '.', $_POST['valor'] ?? 0);
    $valor_recebido = (float)str_replace(',', '.', $_POST['valor_recebido'] ?? 0);
    $motoboy = htmlspecialchars(trim($_POST['motoboy'] ?? ''));
    $autozoner = htmlspecialchars(trim($_POST['autozoner'] ?? ''));

    if (empty($cliente)) $erros[] = "Nome do cliente é obrigatório!";
    if (empty($forma_pagamento)) $erros[] = "Selecione a forma de pagamento!";
    if ($valor <= 0) $erros[] = "Valor deve ser maior que zero!";

    if ($forma_pagamento === 'Dinheiro' && $valor_recebido < $valor) {
        $erros[] = "Valor recebido insuficiente!";
    }

    if (empty($erros)) {
        try {
            $troco = ($forma_pagamento === 'Dinheiro') ? ($valor_recebido - $valor) : 0;

            $stmt = $pdo->prepare("INSERT INTO vendas 
                (cliente, pagamento, valor, valor_recebido, troco, motoboy, autozoner, numero_loja) 
                VALUES (:cliente, :pagamento, :valor, :recebido, :troco, :motoboy, :autozoner, :loja)");

            $stmt->execute([
                ':cliente' => $cliente,
                ':pagamento' => $forma_pagamento,
                ':valor' => $valor,
                ':recebido' => ($forma_pagamento === 'Dinheiro') ? $valor_recebido : null,
                ':troco' => $troco,
                ':motoboy' => $motoboy,
                ':autozoner' => $autozoner,
                ':loja' => $numero_loja
            ]);

            $sucesso = "Venda registrada com sucesso!";

        } catch (PDOException $e) {
            $erros[] = "Erro ao registrar venda: " . $e->getMessage();
        }
    }
}

// BUSCAR DADOS PARA O FORMULÁRIO
try {
    $stmt_autozoners = $pdo->query("SELECT DISTINCT autozoner FROM vendas WHERE numero_loja = '$numero_loja'");
    $autozoners = $stmt_autozoners->fetchAll(PDO::FETCH_COLUMN);

    $stmt_motoboys = $pdo->query("SELECT DISTINCT motoboy FROM vendas WHERE numero_loja = '$numero_loja'");
    $motoboys = $stmt_motoboys->fetchAll(PDO::FETCH_COLUMN);

    $stmt_vendas = $pdo->prepare("SELECT *, DATE_FORMAT(data, '%d/%m/%Y %H:%i') as data_formatada 
                                FROM vendas 
                                WHERE numero_loja = ? 
                                ORDER BY data DESC 
                                LIMIT 10");
    $stmt_vendas->execute([$numero_loja]);
    $vendas = $stmt_vendas->fetchAll();

} catch (PDOException $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}
?>
