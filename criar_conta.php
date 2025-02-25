<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/config.php';

// Redirecionar se já estiver logado
if(isset($_SESSION['usuario'])) {
    header("Location: menu.php");
    exit();
}

$erros = [];
$sucesso = '';

// Processar formulário
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $numero_loja = sanitizar($_POST['numero_loja'] ?? '');
    $email = sanitizar($_POST['email'] ?? '');
    $senha = sanitizar($_POST['senha'] ?? '');
    $confirmar_senha = sanitizar($_POST['confirmar_senha'] ?? '');
    $pergunta = sanitizar($_POST['pergunta'] ?? '');
    $resposta = sanitizar($_POST['resposta'] ?? '');

    // Validações
    if(empty($numero_loja)) {
        $erros[] = "Número da loja é obrigatório!";
    }

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "E-mail inválido!";
    }

    if(strlen($senha) < 8) {
        $erros[] = "Senha deve ter pelo menos 8 caracteres!";
    }

    if($senha !== $confirmar_senha) {
        $erros[] = "Senhas não coincidem!";
    }

    if(empty($pergunta) || empty($resposta)) {
        $erros[] = "Pergunta e resposta de segurança são obrigatórias!";
    }

    if(empty($erros)) {
        try {
            // Verificar se loja ou email já existem
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE numero_loja = ? OR email = ?");
            $stmt->execute([$numero_loja, $email]);
            
            if($stmt->rowCount() > 0) {
                $erros[] = "Loja ou e-mail já cadastrados!";
            } else {
                // Criar hash da senha
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                
                // Inserir no banco
                $stmt = $pdo->prepare("INSERT INTO usuarios 
                    (numero_loja, email, senha, pergunta_seguranca, resposta_seguranca) 
                    VALUES (?, ?, ?, ?, ?)");
                
                if($stmt->execute([
                    $numero_loja,
                    $email,
                    $senha_hash,
                    $pergunta,
                    $resposta
                ])) {
                    $sucesso = "Conta criada com sucesso! Faça login.";
                } else {
                    $erros[] = "Erro ao criar conta!";
                }
            }
        } catch(PDOException $e) {
            $erros[] = "Erro no sistema: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Criar Conta</title>
    <!-- Incluindo o arquivo CSS -->
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Criar Nova Conta</h1>
        
        <?php if(!empty($erros)): ?>
            <div class="alert alert-error">
                <?php foreach($erros as $erro): ?>
                    <p><?= $erro ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if($sucesso): ?>
            <div class="alert alert-success"><?= $sucesso ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Número da Loja:</label>
                <input type="text" name="numero_loja" required 
                    value="<?= htmlspecialchars($_POST['numero_loja'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>E-mail:</label>
                <input type="email" name="email" required 
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Senha (mínimo 8 caracteres):</label>
                <input type="password" name="senha" required>
            </div>

            <div class="form-group">
                <label>Confirmar Senha:</label>
                <input type="password" name="confirmar_senha" required>
            </div>

            <div class="form-group">
                <label>Pergunta de Segurança:</label>
                <select name="pergunta" required>
                    <option value="">Selecione uma pergunta</option>
                    <option value="Nome do seu primeiro pet?" <?= isset($_POST['pergunta']) && $_POST['pergunta'] == 'Nome do seu primeiro pet?' ? 'selected' : '' ?>>Nome do seu primeiro pet?</option>
                    <option value="Nome da sua mãe solteira?" <?= isset($_POST['pergunta']) && $_POST['pergunta'] == 'Nome da sua mãe solteira?' ? 'selected' : '' ?>>Nome da sua mãe solteira?</option>
                    <option value="Cidade onde nasceu?" <?= isset($_POST['pergunta']) && $_POST['pergunta'] == 'Cidade onde nasceu?' ? 'selected' : '' ?>>Cidade onde nasceu?</option>
                </select>
            </div>

            <div class="form-group">
                <label>Resposta:</label>
                <input type="text" name="resposta" required value="<?= htmlspecialchars($_POST['resposta'] ?? '') ?>">
            </div>

            <button type="submit" class="btn btn-primary">Criar Conta</button>
            
            <div class="links">
                <a href="index.php" class="btn btn-secondary">Já tem conta? Faça login</a>
            </div>
        </form>
    </div>
</body>
</html>