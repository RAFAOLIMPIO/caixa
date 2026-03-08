<?php
// includes/funcoes.php

function verificar_login() {
    if (!isset($_SESSION['usuario'])) {
        header("Location: index.php");
        exit();
    }
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
            WHERE numero_loja = ? 
            AND tipo = 'autozoner'
            ORDER BY nome
        ");
        $stmt->execute([$numero_loja]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            WHERE numero_loja = ? 
            AND tipo = 'motoboy'
            ORDER BY nome
        ");
        $stmt->execute([$numero_loja]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erro ao buscar motoboys: " . $e->getMessage());
        return [];
    }
}

function manter_sessao_ativa() {
    echo '<script>
    setInterval(function() {
        fetch("keep_alive.php");
    }, 300000);
    </script>';
}