<?php
// recuperar_senha.php
ob_start(); 
require 'includes/config.php';

$etapa = isset($_GET['etapa']) ? (int) $_GET['etapa'] : 1;
$erros = [];
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($etapa == 1) {
        $numero_loja = sanitizar($_POST['numero_loja'] ?? '');
        try {
            $stmt = $pdo->prepare("SELECT pergunta_seguranca FROM usuarios WHERE numero_loja = ?");
            $stmt->execute([$numero_loja]);
            if ($stmt->rowCount() > 0) {
                $usuario = $stmt->fetch();
                $_SESSION['recuperacao'] = [
                    'numero_loja' => $numero_loja,
                    'pergunta' => $usuario['pergunta_seguranca'],
                    'etapa' => 2
                ];
                header("Location: recuperar_senha.php?etapa=2");
                exit();
            } else {
                $erros[] = "Loja não encontrada!";
            }
        } catch (PDOException $e) {
            $erros[] = "Erro no sistema: " . $e->getMessage();
        }
    } elseif ($etapa == 2) {
        if (!isset($_SESSION['recuperacao'])) {
            header("Location: recuperar_senha.php");
            exit();
        }

        $resposta = sanitizar($_POST['resposta'] ?? '');
        try {
            $stmt = $pdo->prepare("SELECT resposta_seguranca FROM usuarios WHERE numero_loja = ?");
            $stmt->execute([$_SESSION['recuperacao']['numero_loja']]);
            $usuario = $stmt->fetch();

            if ($usuario && strcasecmp(trim($resposta), trim($usuario['resposta_seguranca'])) === 0) {
                $_SESSION['recuperacao']['etapa'] = 3;
                header("Location: recuperar_senha.php?etapa=3");
                exit();
            } else {
                $erros[] = "Resposta incorreta!";
            }
        } catch (PDOException $e) {
            $erros[] = "Erro no sistema: " . $e->getMessage();
        }
    } elseif ($etapa == 3) {
        if (!isset($_SESSION['recuperacao'])) {
            header("Location: recuperar_senha.php");
            exit();
        }

        $nova_senha = $_POST['nova_senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';

        if (strlen($nova_senha) < 8) $erros[] = "A senha deve ter pelo menos 8 caracteres!";
        if ($nova_senha !== $confirmar_senha) $erros[] = "As senhas não coincidem!";

        if (empty($erros)) {
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            try {
                // CORREÇÃO: Usando 'senha' em vez de 'password'
                $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE numero_loja = ?");
                if ($stmt->execute([$senha_hash, $_SESSION['recuperacao']['numero_loja']])) {
                    unset($_SESSION['recuperacao']);
                    $sucesso = "Senha alterada com sucesso! Redirecionando para login...";
                    header("refresh:3;url=index.php");
                }
            } catch (PDOException $e) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - AutoGest</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .recovery-bg {
            background: linear-gradient(135deg, #000000 0%, #1a1a2e 50%, #16213e 100%);
        }
    </style>
</head>
<body class="recovery-bg min-h-screen flex items-center justify-center px-4 py-8">
    <div class="glass-effect rounded-2xl w-full max-w-md shadow-2xl fade-in">
        <div class="p-8">
            <div class="text-center mb-8">
                <img src="logo.png" alt="AutoGest" class="mx-auto w-16 h-16 mb-4 rounded-full">
                <h2 class="text-2xl font-bold text-white">Recuperar Senha</h2>
                <p class="text-gray-400 mt-2">Etapa <?= $etapa ?> de 3</p>
            </div>

            <?php if (!empty($erros)): ?>
                <div class="bg-red-500 bg-opacity-20 border border-red-500 text-red-200 p-4 rounded-lg mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php foreach ($erros as $erro): ?>
                        <p><?= $erro ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="bg-green-500 bg-opacity-20 border border-green-500 text-green-200 p-4 rounded-lg mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?= $sucesso ?>
                </div>
            <?php endif; ?>

            <?php if ($etapa == 1): ?>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-white text-sm font-medium mb-2">
                            <i class="fas fa-store mr-2"></i>Número da Loja
                        </label>
                        <input type="text" name="numero_loja" required 
                            class="input-modern"
                            placeholder="Digite o número da sua loja">
                    </div>
                    <button type="submit" class="btn-modern">
                        <i class="fas fa-arrow-right mr-2"></i> Continuar
                    </button>
                </form>

            <?php elseif ($etapa == 2 && isset($_SESSION['recuperacao'])): ?>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-white text-sm font-medium mb-2">Pergunta de Segurança:</label>
                        <div class="bg-gray-800 bg-opacity-50 p-4 rounded-lg border border-gray-700">
                            <p class="text-purple-300 font-semibold"><?= $_SESSION['recuperacao']['pergunta'] ?></p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-white text-sm font-medium mb-2">
                            <i class="fas fa-key mr-2"></i>Resposta
                        </label>
                        <input type="text" name="resposta" required 
                            class="input-modern"
                            placeholder="Digite a resposta">
                    </div>
                    <button type="submit" class="btn-modern">
                        <i class="fas fa-check mr-2"></i> Verificar
                    </button>
                </form>

            <?php elseif ($etapa == 3 && isset($_SESSION['recuperacao'])): ?>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-white text-sm font-medium mb-2">
                            <i class="fas fa-lock mr-2"></i>Nova Senha
                        </label>
                        <input type="password" name="nova_senha" required 
                            class="input-modern"
                            placeholder="Mínimo 8 caracteres">
                    </div>
                    <div>
                        <label class="block text-white text-sm font-medium mb-2">
                            <i class="fas fa-lock mr-2"></i>Confirmar Nova Senha
                        </label>
                        <input type="password" name="confirmar_senha" required 
                            class="input-modern"
                            placeholder="Digite novamente a senha">
                    </div>
                    <button type="submit" class="btn-modern">
                        <i class="fas fa-save mr-2"></i> Alterar Senha
                    </button>
                </form>
            <?php endif; ?>

            <div class="text-center mt-6 pt-4 border-t border-gray-700">
                <a href="index.php" class="text-purple-400 hover:text-purple-300 font-medium transition duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>Voltar para Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>
