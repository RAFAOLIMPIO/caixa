<?php

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
            SELECT id, nome
            FROM funcionarios
            WHERE numero_loja = ?
            ORDER BY nome
        ");
        $stmt->execute([$numero_loja]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Erro autozoners: " . $e->getMessage());
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

?>