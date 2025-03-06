<?php
include 'includes/config.php';

if(isset($_SESSION['usuario'])) {
    header("Location: menu.php");
    exit();
}

$erro = '';

// ... (mantenha o código PHP de processamento do formulário igual) ...

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="brand-header">
                <h1 class="brand-title">Login</h1>
            </div>

            <?php if($erro): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
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
                        <input type="checkbox" name="lembrar" class="hidden-checkbox">
                        <span class="custom-checkbox"></span>
                        Lembrar minha conta
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Entrar
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