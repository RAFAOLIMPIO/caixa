<?php
include 'includes/config.php';

// Redireciona usuário logado
if(isset($_SESSION['usuario'])) {
    header("Location: menu.php");
    exit();
}

$erro = '';

// Processa o formulário
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $numero_loja = sanitizar($_POST['numero_loja']);
    $senha = sanitizar($_POST['senha']);

    try {  
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE numero_loja = ?");  
        $stmt->execute([$numero_loja]);  
        $usuario = $stmt->fetch();  

        if($usuario && password_verify($senha, $usuario['senha'])) {  
            // Cria sessão
            $_SESSION['usuario'] = [  
                'id' => $usuario['id'],  
                'numero_loja' => $usuario['numero_loja'],  
                'email' => $usuario['email']  
            ];  
            
            // Lembrar conta
            if(isset($_POST['lembrar'])) {  
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
        
    } catch(PDOException $e) {  
        $erro = "Erro no sistema: " . $e->getMessage();  
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso ao Sistema</title>
    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="brand-header">
                <svg class="brand-logo" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L2 7V17L12 22L22 17V7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M7 10L11 12.5L17 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h1 class="brand-title">Gestão Comercial</h1>
            </div>

            <?php if($erro): ?>
                <div class="alert alert-error pulse">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label class="input-label">
                        <i class="fas fa-store input-icon"></i>
                        Número da Loja
                    </label>
                    <input type="text" 
                           name="numero_loja" 
                           class="form-input" 
                           placeholder="Digite seu número" 
                           required
                           autofocus>
                </div>

                <div class="form-group">
                    <label class="input-label">
                        <i class="fas fa-lock input-icon"></i>
                        Senha
                    </label>
                    <div class="password-wrapper">
                        <input type="password" 
                               name="senha" 
                               id="senha" 
                               class="form-input" 
                               placeholder="••••••••" 
                               required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group remember-container">
                    <label class="remember-label">
                        <input type="checkbox" 
                               name="lembrar" 
                               class="hidden-checkbox">
                        <span class="custom-checkbox"></span>
                        Manter conectado
                    </label>
                </div>

                <button type="submit" class="btn btn-primary hover-scale">
                    <i class="fas fa-sign-in-alt"></i>
                    Acessar Sistema
                </button>

                <div class="auth-links">
                    <a href="recuperar_senha.php" class="link-icon">
                        <i class="fas fa-key"></i>
                        Recuperar Senha
                    </a>
                    <a href="criar_conta.php" class="link-icon">
                        <i class="fas fa-user-plus"></i>
                        Criar Conta
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('senha');
            const icon = document.querySelector('.password-toggle i');
            
            if(passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>