<?php
// includes/funcoes.php

function validar_campos_obrigatorios($campos, $mensagens) {
    $erros = [];
    foreach ($campos as $campo => $mensagem) {
        if (empty($_POST[$campo])) {
            $erros[] = $mensagem;
        }
    }
    return $erros;
}

function calcular_troco($valor, $valor_pago) {
    if ($valor_pago > $valor) {
        return $valor_pago - $valor;
    }
    return 0;
}

function obter_autozoners($pdo, $numero_loja) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, nome, tipo, cargo 
            FROM funcionarios 
            WHERE numero_loja = ? AND tipo = 'autozoner' AND ativo = TRUE
            ORDER BY nome
        ");
        $stmt->execute([$numero_loja]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Erro ao buscar autozoners: " . $e->getMessage());
        return [];
    }
}

function obter_motoboys($pdo, $numero_loja) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, nome, tipo, cargo 
            FROM funcionarios 
            WHERE numero_loja = ? AND tipo = 'motoboy' AND ativo = TRUE
            ORDER BY nome
        ");
        $stmt->execute([$numero_loja]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Erro ao buscar motoboys: " . $e->getMessage());
        return [];
    }
}

function manter_sessao_ativa() {
    echo '<script>
    setInterval(function() {
        fetch("keep_alive.php")
            .then(response => response.json())
            .then(data => console.log("Sessão mantida:", data.time))
            .catch(err => console.error("Erro sessão:", err));
    }, 300000); // 5 minutos
    </script>';
}
?>
