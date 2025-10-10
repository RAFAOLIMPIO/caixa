<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/funcoes.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$numero_loja = $_SESSION['usuario']['numero_loja'] ?? $_SESSION['usuario']['id'];

// AJAX: salvar observação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'salvar_obs' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $obs = sanitizar($_POST['obs'] ?? '');
        try {
            $stmt = $pdo->prepare("UPDATE vendas SET obs = :obs WHERE id = :id AND numero_loja = :loja");
            $stmt->execute([':obs'=>$obs, ':id'=>$id, ':loja'=>$numero_loja]);
            echo json_encode(['ok' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit();
    } elseif ($_POST['action'] === 'toggle_pago' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $pago = isset($_POST['pago']) && ($_POST['pago'] == '1' || $_POST['pago'] == 1) ? 1 : 0;
        try {
            $stmt = $pdo->prepare("UPDATE vendas SET pago = :pago WHERE id = :id AND numero_loja = :loja");
            $stmt->execute([':pago'=>$pago, ':id'=>$id, ':loja'=>$numero_loja]);
            echo json_encode(['ok'=>true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
        }
        exit();
    }
}

// Busca vendas
try {
    $stmt = $pdo->prepare("SELECT v.*, f.nome AS autozoner_nome FROM vendas v LEFT JOIN funcionarios f ON v.autozoner_id::text = f.id::text WHERE v.numero_loja = :loja ORDER BY v.data DESC");
    $stmt->execute([':loja' => $numero_loja]);
    $vendas = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erro ao buscar vendas: " . $e->getMessage());
}

// renderiza
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Relatório de Vendas</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<link rel="stylesheet" href="https://cdn.tailwindcss.com">
</head>
<body class="bg-gray-900 text-white p-6">
<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">Relatório de Vendas</h1>

    <table class="w-full table-auto mb-6 bg-gray-800 rounded">
        <thead>
            <tr class="text-left">
                <th class="p-3">Data</th>
                <th class="p-3">Cliente</th>
                <th class="p-3">Valor</th>
                <th class="p-3">Autozoner</th>
                <th class="p-3">Motoboy</th>
                <th class="p-3">Pago</th>
                <th class="p-3">Obs</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vendas as $v): 
                $pago = isset($v['pago']) && ($v['pago'] == true || $v['pago'] == 1); 
                $data = isset($v['data']) ? date('d/m/Y H:i', strtotime($v['data'])) : '-';
            ?>
            <tr class="border-b border-gray-700 <?= $pago ? 'bg-green-900' : '' ?>">
                <td class="p-3"><?= htmlspecialchars($data) ?></td>
                <td class="p-3"><?= htmlspecialchars($v['cliente'] ?? '-') ?></td>
                <td class="p-3"><?= htmlspecialchars(number_format((float)$v['valor'],2,',','.')) ?></td>
                <td class="p-3"><?= htmlspecialchars($v['autozoner_nome'] ?? ($v['autozoner_id'] ?? '-')) ?></td>
                <td class="p-3"><?= htmlspecialchars($v['motoboy'] ?? '-') ?></td>
                <td class="p-3">
                    <input type="checkbox" class="pago-checkbox" data-id="<?= $v['id'] ?>" <?= $pago ? 'checked' : '' ?>>
                </td>
                <td class="p-3">
                    <span class="obs-text" data-id="<?= $v['id'] ?>"><?= htmlspecialchars($v['obs'] ? (strlen($v['obs'])>30? substr($v['obs'],0,30).'...':$v['obs']) : '') ?></span>
                    <button class="editar-obs ml-2" data-id="<?= $v['id'] ?>">Editar</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="caixa.php" class="bg-blue-600 px-4 py-2 rounded">Voltar para Caixa</a>
</div>

<!-- Modal simples para editar observação -->
<div id="modalObs" style="display:none;">
    <div style="position:fixed;left:0;top:0;right:0;bottom:0;background:#0008;"></div>
    <div style="position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);background:#1f2937;padding:16px;border-radius:8px;">
        <h3 style="margin:0 0 8px 0;color:#fff">Editar Observação</h3>
        <textarea id="obsField" style="width:400px;height:120px;padding:8px;background:#111;color:#fff"></textarea>
        <div style="margin-top:8px;">
            <button id="saveObs" style="background:#2563eb;color:#fff;padding:8px 12px;border-radius:6px;">Salvar</button>
            <button id="cancelObs" style="margin-left:8px;padding:8px 12px;border-radius:6px;">Cancelar</button>
        </div>
    </div>
</div>

<script>
$(function(){
    $('.pago-checkbox').on('change', function(){
        var id = $(this).data('id');
        var pago = $(this).is(':checked') ? 1 : 0;
        $.post('relatorio.php', {action: 'toggle_pago', id: id, pago: pago}, function(resp){
            location.reload();
        });
    });

    var currentId = null;
    $('.editar-obs').on('click', function(){
        currentId = $(this).data('id');
        $('#obsField').val($('.obs-text[data-id="'+currentId+'"]').text());
        $('#modalObs').show();
    });
    $('#cancelObs').on('click', function(){ $('#modalObs').hide(); });
    $('#saveObs').on('click', function(){
        var obs = $('#obsField').val();
        $.post('relatorio.php', {action: 'salvar_obs', id: currentId, obs: obs}, function(resp){
            $('#modalObs').hide();
            location.reload();
        });
    });
});
</script>
</body>
</html>
