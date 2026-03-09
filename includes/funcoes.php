<?php
// includes/funcoes.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| VERIFICAR LOGIN
|--------------------------------------------------------------------------
*/
function verificar_login() {
    if (!isset($_SESSION['usuario'])) {
        header("Location: index.php");
        exit();
    }
}

/*
|--------------------------------------------------------------------------
| USUÁRIO ATUAL
|--------------------------------------------------------------------------
*/
function usuario_atual() {
    if (!isset($_SESSION['usuario'])) {
        return null;
    }

    return $_SESSION['usuario'];
}

/*
|--------------------------------------------------------------------------
| CALCULAR TROCO
|--------------------------------------------------------------------------
*/
function calcular_troco($valor, $valor_pago) {
    $valor = (float)$valor;
    $valor_pago = (float)$valor_pago;

    if ($valor_pago > $valor) {
        return $valor_pago - $valor;
    }

    return 0;
}

/*
|--------------------------------------------------------------------------
| BUSCAR AUTOZONERS
|--------------------------------------------------------------------------
*/
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

/*
|--------------------------------------------------------------------------
| BUSCAR MOTOBOYS
|--------------------------------------------------------------------------
*/
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

/*
|--------------------------------------------------------------------------
| MANTER SESSÃO ATIVA
|--------------------------------------------------------------------------
function manter_sessao_ativa() {
    echo '<script>
    setInterval(function() {
        fetch("keep_alive.php")
            .then(response => response.json())
            .then(data => console.log("Sessão mantida:", data.time))
            .catch(err => console.error("Erro sessão:", err));
    }, 300000);
    </script>';
}

function sanitizar($valor) {
    if (is_array($valor)) {
        return array_map('sanitizar', $valor);
    }
    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}
