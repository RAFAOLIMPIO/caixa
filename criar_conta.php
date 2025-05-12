<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/config.php';

if(isset($_SESSION['usuario'])) {
    header("Location: menu.php");
    exit();
}

$erros = [];
$sucesso = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $numero_loja = sanitizar($_POST['numero_loja']);
    $email = sanitizar($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar = $_POST['confirmar_senha'];
    $pergunta = sanitizar($_POST['pergunta']);
    $resposta = sanitizar($_POST['resposta']);

    if(strlen($senha) < 8) {
        $erros[] = "A senha deve ter no mínimo 8 caracteres.";
    } elseif($senha !== $confirmar) {
        $erros[] = "As senhas não coincidem.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if($stmt->rowCount() > 0) {
                $erros[] = "E-mail já cadastrado.";
            } else {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (numero_loja, email, senha, pergunta_seguranca, resposta_seguranca) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$numero_loja, $email, $senha_hash, $pergunta, $resposta]);

                header("Location: index.php");
                exit();
            }
        } catch(PDOException $e) {
            $erros[] = "Erro ao cadastrar: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card pulse">
            <div class="brand-header">
                <h1 class="brand-title">
                    <i class="fas fa-user-shield"></i>
                    Cadastro de Nova Conta
                </h1>
            </div>

            <?php if(!empty($erros)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php foreach($erros as $erro): ?>
                        <div><?= htmlspecialchars($erro) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label class="input-label">
                        <i class="fas fa-hashtag"></i>
                        Número da Loja
                    </label>
                    <input type="text" 
                           name="numero_loja" 
                           class="form-input"
                           required
                           value="<?= htmlspecialchars($_POST['numero_loja'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="input-label">
                        <i class="fas fa-envelope"></i>
                        E-mail
                    </label>
                    <input type="email" 
                           name="email" 
                           class="form-input"
                           required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="input-label">
                        <i class="fas fa-lock"></i>
                        Senha (mínimo 8 caracteres)
                    </label>
                    <input type="password" 
                           name="senha" 
                           class="form-input"
                           required>
                </div>

                <div class="form-group">
                    <label class="input-label">
                        <i class="fas fa-lock"></i>
                        Confirmar Senha
                    </label>
                    <input type="password" 
                           name="confirmar_senha" 
                           class="form-input"
                           required>
                </div>

                <div class="form-group">
                    <label class="input-label">
                        <i class="fas fa-question-circle"></i>
                        Pergunta de Segurança
                    </label>
                    <div class="select-wrapper">
                        <select name="pergunta" class="form-input" required>
                            <option value="">Selecione uma pergunta</option>
                            <option value="Nome do seu primeiro pet?" <?= isset($_POST['pergunta']) && $_POST['pergunta'] == 'Nome do seu primeiro pet?' ? 'selected' : '' ?>>Nome do seu primeiro pet?</option>
                            <option value="Nome da sua mãe solteira?" <?= isset($_POST['pergunta']) && $_POST['pergunta'] == 'Nome da sua mãe solteira?' ? 'selected' : '' ?>>Nome da sua mãe solteira?</option>
                            <option value="Cidade onde nasceu?" <?= isset($_POST['pergunta']) && $_POST['pergunta'] == 'Cidade onde nasceu?' ? 'selected' : '' ?>>Cidade onde nasceu?</option>
                        </select>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="input-label">
                        <i class="fas fa-key"></i>
                        Resposta de Segurança
                    </label>
                    <input type="text" 
                           name="resposta" 
                           class="form-input"
                           required
                           value="<?= htmlspecialchars($_POST['resposta'] ?? '') ?>">
                </div>

                <button type="submit" class="btn btn-primary hover-scale">
                    <i class="fas fa-user-plus"></i>
                    Criar Conta
                </button>

                <div class="auth-links">
                    <a href="index.php" class="link-icon">
                        <i class="fas fa-sign-in-alt"></i>
                        Voltar para Login
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
