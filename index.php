<?php
include 'includes/config.php';

if(isset($_SESSION['usuario'])) {
    header("Location: menu.php");
    exit();
}

$erro = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ... (mantenha o código PHP existente sem alterações) ...
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acessar Sistema</title>
    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card pulse">
            <div class="brand-header">
                <svg class="brand-logo" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L2 7V17L12 22L22 17V7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M7 10L11 12.5L17 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h1 class="brand-title">Sistema de Gestão</h1>
            </div>

            <?php if($erro): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $erro ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label class="input-label">
                        <i class="fas fa-store input-icon"></i>
                        Número da Loja
                    </label>
                    <input type="text" name="numero_loja" class="form-input" placeholder="Digite seu número de loja" required>
                </div>

                <div class="form-group">
                    <label class="input-label">
                        <i class="fas fa-lock input-icon"></i>
                        Senha
                    </label>
                    <div class="password-wrapper">
                        <input type="password" name="senha" id="senha" class="form-input" placeholder="••••••••" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group" style="flex-direction: row; align-items: center; gap: 0.75rem;">
                    <label class="input-label" style="margin: 0;">
                        <input type="checkbox" name="lembrar" class="hidden-checkbox">
                        <span class="custom-checkbox"></span>
                        Lembrar minha conta
                    </label>
                </div>

                <button type="submit" class="btn btn-primary hover-scale">
                    Acessar
                    <i class="fas fa-arrow-right btn-icon"></i>
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
            const toggleIcon = document.querySelector('.password-toggle i');
            
            if(passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>