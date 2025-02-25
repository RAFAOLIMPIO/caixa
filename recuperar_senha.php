<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/config.php';

$etapa = $_GET['etapa'] ?? 1;
$erros = [];
$sucesso = '';

// Processar recuperação
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if($etapa == 1) {
        $numero_loja = sanitizar($_POST['numero_loja'] ?? '');
        
        try {
            $stmt = $pdo->prepare("SELECT pergunta_seguranca FROM usuarios WHERE numero_loja = ?");
            $stmt->execute([$numero_loja]);
            
            if($stmt->rowCount() > 0) {
                $usuario = $stmt->fetch();
                $_SESSION['recuperacao'] = [
                    'numero_loja' => $numero_loja,
                    'pergunta' => $usuario['pergunta_seguranca']
                ];
                header("Location: recuperar_senha.php?etapa=2");
                exit();
            } else {
                $erros[] = "Loja não encontrada!";
            }
        } catch(PDOException $e) {
            $erros[] = "Erro no sistema: " . $e->getMessage();
        }
    }
    elseif($etapa == 2) {
        $resposta = sanitizar($_POST['resposta'] ?? '');
        
        try {
            $stmt = $pdo->prepare("SELECT resposta_seguranca FROM usuarios WHERE numero_loja = ?");
            $stmt->execute([$_SESSION['recuperacao']['numero_loja']]);
            $usuario = $stmt->fetch();

            if(strcasecmp($resposta, $usuario['resposta_seguranca']) === 0) {
                $_SESSION['recuperacao']['etapa'] = 3;
                $sucesso = "Resposta correta! Defina uma nova senha.";
            } else {
                $erros[] = "Resposta incorreta!";
            }
        } catch(PDOException $e) {
            $erros[] = "Erro no sistema: " . $e->getMessage();
        }
    }
    elseif($etapa == 3) {
        $nova_senha = sanitizar($_POST['nova_senha'] ?? '');
        $confirmar_senha = sanitizar($_POST['confirmar_senha'] ?? '');

        if(strlen($nova_senha) < 8) {
            $erros[] = "Senha deve ter pelo menos 8 caracteres!";
        }

        if($nova_senha !== $confirmar_senha) {
            $erros[] = "Senhas não coincidem!";
        }

        if(empty($erros)) {
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            
            try {
                $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE numero_loja = ?");
                if($stmt->execute([$senha_hash, $_SESSION['recuperacao']['numero_loja']])) {
                    unset($_SESSION['recuperacao']);
                    $sucesso = "Senha alterada com sucesso! Redirecionando para login...";
                    header("refresh:3;url=login.php");
                }
            } catch(PDOException $e) {
                $erros[] = "Erro ao atualizar senha: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Senha</title>
    <!-- Incluindo o arquivo CSS -->
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Recuperação de Senha</h1>
        
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

        <?php if($etapa == 1): ?>
            <form method="POST">
                <div class="form-group">
                    <label>Número da Loja:</label>
                    <input type="text" name="numero_loja" required>
                </div>
                <button type="submit" class="btn btn-primary">Continuar</button>
            </form>

        <?php elseif($etapa == 2): ?>
            <form method="POST">
                <div class="form-group">
                    <label>Pergunta de Segurança:</label>
                    <p><strong><?= $_SESSION['recuperacao']['pergunta'] ?></strong></p>
                </div>
                <div class="form-group">
                    <label>Resposta:</label>
                    <input type="text" name="resposta" required>
                </div>
                <button type="submit" class="btn btn-primary">Verificar</button>
            </form>

        <?php elseif($etapa == 3): ?>
            <form method="POST">
                <div class="form-group">
                    <label>Nova Senha:</label>
                    <input type="password" name="nova_senha" required>
                </div>
                <div class="form-group">
                    <label>Confirmar Nova Senha:</label>
                    <input type="password" name="confirmar_senha" required>
                </div>
                <button type="submit" class="btn btn-primary">Alterar Senha</button>
            </form>
        <?php endif; ?>

        <div class="links">
            <a href="index.php" class="btn btn-secondary">← Voltar para Login</a>
        </div>
    </div>
</body>
</html>