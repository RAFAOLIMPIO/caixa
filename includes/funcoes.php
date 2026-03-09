<?php

// Iniciar sessão se ainda não iniciou
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ================================
   VERIFICAR LOGIN
================================ */

function verificar_login() {

    if (!isset($_SESSION['usuario'])) {
        header("Location: index.php");
        exit();
    }

}


/* ================================
   USUÁRIO ATUAL
================================ */

function usuario_atual() {

    return $_SESSION['usuario'] ?? null;

}


/* ================================
   SANITIZAR DADOS
================================ */

function sanitizar($valor) {

    if (is_array($valor)) {
        return array_map('sanitizar', $valor);
    }

    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');

}


/* ================================
   CALCULAR TROCO
================================ */

function calcular_troco($valor, $valor_pago) {

    if ($valor_pago > $valor) {
        return $valor_pago - $valor;
    }

    return 0;

}


/* ================================
   BUSCAR AUTOZONERS
================================ */

function obter_autozoners($pdo, $numero_loja) {

    try {

        $stmt = $pdo->prepare("
            SELECT id, nome
            FROM funcionarios
            WHERE numero_loja = ?
            AND LOWER(tipo) = 'autozoner'
            AND ativo = TRUE
            ORDER BY nome
        ");

        $stmt->execute([$numero_loja]);

        return $stmt->fetchAll();

    } catch (Exception $e) {

        error_log("Erro autozoners: " . $e->getMessage());

        return [];

    }

}


/* ================================
   BUSCAR MOTOBOYS
================================ */

function obter_motoboys($pdo, $numero_loja) {

    try {

        $stmt = $pdo->prepare("
            SELECT id, nome
            FROM funcionarios
            WHERE numero_loja = ?
            AND LOWER(tipo) = 'motoboy'
            AND ativo = TRUE
            ORDER BY nome
        ");

        $stmt->execute([$numero_loja]);

        return $stmt->fetchAll();

    } catch (Exception $e) {

        error_log("Erro motoboys: " . $e->getMessage());

        return [];

    }

}


/* ================================
   MANTER SESSÃO ATIVA
================================ */

function manter_sessao_ativa() {

    echo '<script>
        setInterval(function(){
            fetch("keep_alive.php");
        },300000);
    </script>';

}

?>
