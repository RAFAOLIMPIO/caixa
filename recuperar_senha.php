elseif ($etapa == 2) {
    $resposta = sanitizar($_POST['resposta'] ?? '');

    try {
        $stmt = $pdo->prepare("SELECT resposta_seguranca FROM usuarios WHERE numero_loja = ?");
        $stmt->execute([$_SESSION['recuperacao']['numero_loja']]);
        $usuario = $stmt->fetch();

        if (strcasecmp($resposta, $usuario['resposta_seguranca']) === 0) {
            $_SESSION['recuperacao']['etapa'] = 3;
            header("Location: recuperar_senha.php?etapa=3"); // Redireciona para a próxima etapa
            exit();
        } else {
            $erros[] = "Resposta incorreta!";
        }
    } catch (PDOException $e) {
        $erros[] = "Erro no sistema: " . $e->getMessage();
    }
}