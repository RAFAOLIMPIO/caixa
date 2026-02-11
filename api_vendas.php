<?php
// api_vendas.php - VERSÃO CORRIGIDA
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Habilitar CORS se necessário
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Verificar se é uma requisição OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/includes/config.php';

// Log de depuração
error_log("API Vendas chamada: " . date('Y-m-d H:i:s'));
error_log("Método: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));

// Verificar se usuário está logado
if (!isset($_SESSION['usuario']['id'])) {
    error_log("Erro: Usuário não autenticado");
    echo json_encode(['ok' => false, 'error' => 'Não autorizado. Faça login novamente.']);
    exit;
}

$usuario_id = $_SESSION['usuario']['id'];
$action = $_POST['action'] ?? ($_GET['action'] ?? '');

error_log("Ação: $action, Usuário ID: $usuario_id");

try {
    switch ($action) {
        /* ================= TOGGLE PAGO ================= */
        case 'toggle_pago':
            if (!isset($_POST['id']) || !isset($_POST['pago'])) {
                throw new Exception('Dados incompletos: id e pago são obrigatórios');
            }
            
            $id = (int)$_POST['id'];
            $pago = $_POST['pago'] == '1' || $_POST['pago'] == true || $_POST['pago'] == 'true' ? 1 : 0;
            
            error_log("Toggle pago: id=$id, pago=$pago");
            
            $stmt = $pdo->prepare("UPDATE vendas SET pago = :pago WHERE id = :id AND usuario_id = :usuario");
            $stmt->execute([
                ':pago' => $pago,
                ':id' => $id,
                ':usuario' => $usuario_id
            ]);
            
            echo json_encode(['ok' => true, 'message' => 'Status de pagamento atualizado']);
            break;

        /* ================= EDITAR VENDA ================= */
        case 'editar_venda':
            if (!isset($_POST['id'])) {
                throw new Exception('ID da venda não informado');
            }
            
            // Converter valor para formato numérico
            $valor = $_POST['valor'] ?? '0';
            $valor = str_replace(['R$', ' ', '.'], '', $valor);
            $valor = str_replace(',', '.', $valor);
            $valor = (float)$valor;
            
            error_log("Editar venda: id=" . $_POST['id'] . ", valor=$valor");
            
            $stmt = $pdo->prepare("
                UPDATE vendas SET
                    cliente = :cliente,
                    valor_total = :valor,
                    autozoner_id = :autozoner,
                    forma_pagamento = :forma,
                    motoboy = :motoboy,
                    obs = :obs
                WHERE id = :id AND usuario_id = :usuario
            ");
            
            $stmt->execute([
                ':cliente' => $_POST['cliente'] ?? '',
                ':valor' => $valor,
                ':autozoner' => !empty($_POST['autozoner_id']) ? (int)$_POST['autozoner_id'] : null,
                ':forma' => $_POST['forma_pagamento'] ?? 'Dinheiro',
                ':motoboy' => $_POST['motoboy'] ?? '',
                ':obs' => $_POST['obs'] ?? '',
                ':id' => (int)$_POST['id'],
                ':usuario' => $usuario_id
            ]);
            
            echo json_encode(['ok' => true, 'message' => 'Venda atualizada com sucesso']);
            break;
/* ================= DEVOLUÇÃO ================= */
case 'devolver_venda':

    if (!isset($_POST['id']) || !isset($_POST['tipo'])) {
        throw new Exception('Dados incompletos para devolução');
    }

    $id = (int)$_POST['id'];
    $tipo = $_POST['tipo'];

    // Buscar venda atual
    $stmt = $pdo->prepare("
        SELECT valor_total 
        FROM vendas 
        WHERE id = :id AND usuario_id = :usuario
    ");
    $stmt->execute([
        ':id' => $id,
        ':usuario' => $usuario_id
    ]);

    $venda = $stmt->fetch();

    if (!$venda) {
        throw new Exception('Venda não encontrada');
    }

    $valor_total = (float)$venda['valor_total'];

    if ($tipo === 'total') {

        $status = 'devolvido';
        $valor_devolvido = $valor_total;

    } else {

        $status = 'parcial';

        $valor_devolvido = $_POST['valor_devolvido'] ?? '0';
        $valor_devolvido = str_replace(['R$', ' ', '.'], '', $valor_devolvido);
        $valor_devolvido = str_replace(',', '.', $valor_devolvido);
        $valor_devolvido = (float)$valor_devolvido;

        if ($valor_devolvido <= 0 || $valor_devolvido >= $valor_total) {
            throw new Exception('Valor devolvido inválido');
        }
    }

    // ✅ UPDATE CORRETO
    $stmt = $pdo->prepare("
        UPDATE vendas SET
            status = :status,
            valor_devolvido = :valor_devolvido,
            pago = false
        WHERE id = :id AND usuario_id = :usuario
    ");

    $stmt->execute([
        ':status' => $status,
        ':valor_devolvido' => $valor_devolvido,
        ':id' => $id,
        ':usuario' => $usuario_id
    ]);

    echo json_encode([
        'ok' => true,
        'message' => 'Devolução registrada com sucesso'
    ]);

    break;

        /* ================= EXCLUIR VENDA ================= */
        case 'excluir_venda':
            if (!isset($_POST['id'])) {
                throw new Exception('ID da venda não informado');
            }
            
            $id = (int)$_POST['id'];
            error_log("Excluir venda: id=$id");
            
            $stmt = $pdo->prepare("DELETE FROM vendas WHERE id = :id AND usuario_id = :usuario");
            $stmt->execute([
                ':id' => $id,
                ':usuario' => $usuario_id
            ]);
            
            echo json_encode(['ok' => true, 'message' => 'Venda excluída com sucesso']);
            break;

        /* ================= SALVAR OBSERVAÇÃO ================= */
        case 'salvar_obs':
            if (!isset($_POST['id'])) {
                throw new Exception('ID da venda não informado');
            }
            
            $id = (int)$_POST['id'];
            $obs = $_POST['obs'] ?? '';
            error_log("Salvar obs: id=$id, obs=$obs");
            
            $stmt = $pdo->prepare("UPDATE vendas SET obs = :obs WHERE id = :id AND usuario_id = :usuario");
            $stmt->execute([
                ':obs' => $obs,
                ':id' => $id,
                ':usuario' => $usuario_id
            ]);
            
            echo json_encode(['ok' => true, 'message' => 'Observação salva com sucesso']);
            break;

        /* ================= SALVAR ORDEM ================= */
        case 'salvar_ordem':
            // Ler dados JSON do corpo da requisição
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            if (!$data || !isset($data['ordem'])) {
                throw new Exception('Dados de ordem inválidos');
            }
            
            error_log("Salvar ordem: " . print_r($data['ordem'], true));
            
            foreach ($data['ordem'] as $item) {
                if (!isset($item['id']) || !isset($item['ordem'])) continue;
                
                $stmt = $pdo->prepare("UPDATE vendas SET ordem = :ordem WHERE id = :id AND usuario_id = :usuario");
                $stmt->execute([
                    ':ordem' => (int)$item['ordem'],
                    ':id' => (int)$item['id'],
                    ':usuario' => $usuario_id
                ]);
            }
            
            echo json_encode(['ok' => true, 'message' => 'Ordem salva com sucesso']);
            break;

        /* ================= LIMPAR TUDO ================= */
        case 'limpar_tudo':
            error_log("Limpar todas as vendas do usuário: $usuario_id");
            
            $stmt = $pdo->prepare("DELETE FROM vendas WHERE usuario_id = :usuario");
            $stmt->execute([':usuario' => $usuario_id]);
            
            $deletadas = $stmt->rowCount();
            
            echo json_encode([
                'ok' => true, 
                'message' => "Todas as $deletadas vendas foram excluídas",
                'total_excluido' => $deletadas
            ]);
            break;
case 'reverter_devolucao':
    $id = $_POST['id'];
    
    try {
        // Buscar a venda
        $stmt = $pdo->prepare("SELECT * FROM vendas WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$id, $usuario_id]);
        $venda = $stmt->fetch();
        
        if (!$venda) {
            echo json_encode(['ok' => false, 'error' => 'Venda não encontrada.']);
            exit;
        }
        
        // Reverter a devolução: voltar status para 'normal' e zerar valor_devolvido
        $stmt = $pdo->prepare("UPDATE vendas SET status = 'normal', valor_devolvido = 0 WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['ok' => true, 'message' => 'Devolução revertida com sucesso!']);
    } catch (PDOException $e) {
        error_log('Erro ao reverter devolução: ' . $e->getMessage());
        echo json_encode(['ok' => false, 'error' => 'Erro ao reverter devolução: ' . $e->getMessage()]);
    }
    break;
        /* ================= TESTE ================= */
        case 'teste':
            echo json_encode([
                'ok' => true, 
                'message' => 'API funcionando',
                'usuario_id' => $usuario_id,
                'session' => $_SESSION['usuario']
            ]);
            break;

        default:
            throw new Exception("Ação não reconhecida: $action");
    }
    
} catch (PDOException $e) {
    error_log("Erro PDO: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'ok' => false, 
        'error' => 'Erro no banco de dados: ' . $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
} catch (Exception $e) {
    error_log("Erro geral: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine());
    http_response_code(400);
    echo json_encode([
        'ok' => false, 
        'error' => $e->getMessage()
    ]);
}