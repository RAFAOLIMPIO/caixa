<?php
session_start();
include 'includes/config.php';

// Configurações seguras para cookies de sessão
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $_SERVER['HTTPS'] ?? false, // Apenas HTTPS em produção
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Redirecionar se já estiver logado
if (isset($_SESSION['usuario'])) {
    header("Location: menu.php");
    exit();
}

// Verificar cookie de lembrar conta
if (!empty($_COOKIE['lembrar_token']) && empty($_SESSION['usuario'])) {
    $token = $_COOKIE['lembrar_token'];
    try {
        $stmt = $pdo->prepare("SELECT user_id, token_hash FROM auth_tokens WHERE token_hash = ?");
        $stmt->execute([hash_hmac('sha256', $token, 'chave_secreta')]);
        $token_data = $stmt->fetch();
        
        if ($token_data) {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$token_data['user_id']]);
            $usuario = $stmt->fetch();
            
            if ($usuario) {
                $_SESSION['usuario'] = [
                    'id' => $usuario['id'],
                    'numero_loja' => $usuario['numero_loja'],
                    'email' => $usuario['email']
                ];
                session_regenerate_id(true);
                header("Location: menu.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        error_log("Token error: " . $e->getMessage());
    }
}

// Geração CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Proteção contra brute force
$ip = $_SERVER['REMOTE_ADDR'];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Verificar tentativas recentes
        $stmt = $pdo->prepare("SELECT tentativas, ultima_tentativa FROM login_tentativas 
                             WHERE ip = ? AND ultima_tentativa > NOW() - INTERVAL 15 MINUTE");
        $stmt->execute([$ip]);
        $tentativa = $stmt->fetch();

        if ($tentativa && $tentativa['tentativas'] >= 5) {
            $erro = "Muitas tentativas. Tente novamente mais tarde.";
        } else {
            // Processar login
            if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
                $erro = "Token inválido. Recarregue a página.";
            } else {
                $numero_loja = filter_input(INPUT_POST, 'numero_loja', FILTER_SANITIZE_NUMBER_INT);
                $senha = $_POST['senha'] ?? '';

                $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE numero_loja = ?");
                $stmt->execute([$numero_loja]);
                $usuario = $stmt->fetch();

                if ($usuario && password_verify($senha, $usuario['senha'])) {
                    // Login bem-sucedido
                    $_SESSION['usuario'] = [
                        'id' => $usuario['id'],
                        'numero_loja' => $usuario['numero_loja'],
                        'email' => $usuario['email']
                    ];
                    
                    // Regenerar ID da sessão
                    session_regenerate_id(true);

                    // Limpar tentativas
                    $stmt = $pdo->prepare("DELETE FROM login_tentativas WHERE ip = ?");
                    $stmt->execute([$ip]);

                    // Lembrar conta
                    if (isset($_POST['lembrar'])) {
                        $token = bin2hex(random_bytes(32));
                        $token_hash = hash_hmac('sha256', $token, 'chave_secreta');
                        
                        setcookie('lembrar_token', $token, [
                            'expires' => time() + 86400 * 30,
                            'path' => '/',
                            'secure' => $_SERVER['HTTPS'] ?? false,
                            'httponly' => true,
                            'samesite' => 'Strict'
                        ]);
                        
                        // Armazenar hash no banco
                        $stmt = $pdo->prepare("INSERT INTO auth_tokens (user_id, token_hash, expires) 
                                             VALUES (?, ?, NOW() + INTERVAL 30 DAY)");
                        $stmt->execute([$usuario['id'], $token_hash]);
                    }

                    header("Location: menu.php");
                    exit();
                } else {
                    $erro = "Credenciais inválidas!";
                    // Registrar tentativa
                    $stmt = $pdo->prepare("INSERT INTO login_tentativas (ip, tentativas) 
                                         VALUES (?, 1) 
                                         ON DUPLICATE KEY UPDATE 
                                         tentativas = tentativas + 1, 
                                         ultima_tentativa = NOW()");
                    $stmt->execute([$ip]);
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $erro = "Erro no sistema. Tente novamente mais tarde.";
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
        <div class="erro"><?= htmlspecialchars($erro, ENT_QUOTES) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
        
        <div class="form-group">
            <label>Número da Loja:</label>
            <input type="text" name="numero_loja" required pattern="\d+">
        </div>

        <div class="form-group">
            <label>Senha:</label>
            <input type="password" name="senha" id="senha" required minlength="8">
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