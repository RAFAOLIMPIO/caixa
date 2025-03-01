<?php
session_start();
include 'includes/config.php';

// Se o usuário já estiver logado, redireciona para o menu
if (isset($_SESSION['usuario'])) {
    header("Location: menu.php");
    exit();
}

// Gera um token CSRF se não existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verifica o token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $erro = "Requisição inválida.";
    } else {
        $numero_loja = sanitizar($_POST['numero_loja']);
        $senha = sanitizar($_POST['senha']);

        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE numero_loja = ?");
            $stmt->execute([$numero_loja]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                $_SESSION['usuario'] = [
                    'id' => $usuario['id'],
                    'numero_loja' => $usuario['numero_loja'],
                    'email' => $usuario['email']
                ];
                
                // Lembrar conta
                if (isset($_POST['lembrar'])) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('lembrar_token', $token, time() + (86400 * 30), "/");
                    
                    $stmt = $pdo->prepare("UPDATE usuarios SET lembrar_token = ? WHERE id = ?");
                    $stmt->execute([$token, $usuario['id']]);
                }
                
                header("Location: menu.php");
                exit();
            } else {
                $erro = "Credenciais inválidas!";
            }
        } catch (PDOException $e) {
            $erro = "Erro no sistema: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
    <h1 class="title-login">Login</h1>
    
    <?php if ($erro): ?>
        <div class="erro"><?= $erro ?></div>
    <?php endif; ?>

    <form method="POST">
        <!-- Campo oculto para o token CSRF -->
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="form-group">
            <label>Número da Loja:</label>
            <input type="text" name="numero_loja" required>
        </div>

        <div class="form-group">
            <label>Senha:</label>
            <input type="password" name="senha" id="senha" required>
            <label>
                <input type="checkbox" onclick="document.getElementById('senha').type = this.checked ? 'text' : 'password'">
                Mostrar senha
            </label>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="lembrar">
                Lembrar minha conta
            </label>
        </div>

        <button type="submit">Entrar</button>

        <div style="margin-top: 20px;">
            <a href="recuperar_senha.php">Esqueci a senha</a> | 
            <a href="criar_conta.php">Criar conta</a>
        </div>
    </form>
</body>
</html>