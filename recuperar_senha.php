<?php
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

            if ($usuario && strcasecmp($resposta, $usuario['resposta_seguranca']) === 0) {
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

        $nova_senha = sanitizar($_POST['nova_senha'] ?? '');
        $confirmar_senha = sanitizar($_POST['confirmar_senha'] ?? '');

        if (strlen($nova_senha) < 8) $erros[] = "A senha deve ter pelo menos 8 caracteres!";
        if ($nova_senha !== $confirmar_senha) $erros[] = "As senhas não coincidem!";

        if (empty($erros)) {
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE numero_loja = ?");
                if ($stmt->execute([$senha_hash, $_SESSION['recuperacao']['numero_loja']])) {
                    unset($_SESSION['recuperacao']);
                    $sucesso = "Senha alterada com sucesso! Redirecionando para login...";
                    header("refresh:3;url=login.php");
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
    <title>Recuperar Senha</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-white min-h-screen flex items-center justify-center px-4">
    <div class="bg-gray-900 bg-opacity-80 p-8 rounded-2xl shadow-2xl w-full max-w-lg">
        <h1 class="text-2xl font-bold text-center mb-6">Recuperação de Senha</h1>

        <?php if (!empty($erros)): ?>
            <div class="bg-red-600 text-white p-4 rounded mb-4">
                <?php foreach ($erros as $erro): ?>
                    <p><?= $erro ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="bg-green-600 text-white p-4 rounded mb-4"><?= $sucesso ?></div>
        <?php endif; ?>

        <?php if ($etapa == 1): ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block mb-1">Número da Loja:</label>
                    <input type="text" name="numero_loja" required class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded focus:ring-2 focus:ring-purple-500">
                </div>
                <button type="submit" class="w-full py-2 bg-purple-600 hover:bg-purple-700 rounded text-white font-bold">Continuar</button>
            </form>

        <?php elseif ($etapa == 2 && isset($_SESSION['recuperacao'])): ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block mb-1">Pergunta de Segurança:</label>
                    <p class="font-semibold"><?= $_SESSION['recuperacao']['pergunta'] ?></p>
                </div>
                <div>
                    <label class="block mb-1">Resposta:</label>
                    <input type="text" name="resposta" required class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded focus:ring-2 focus:ring-purple-500">
                </div>
                <button type="submit" class="w-full py-2 bg-purple-600 hover:bg-purple-700 rounded text-white font-bold">Verificar</button>
            </form>

        <?php elseif ($etapa == 3 && isset($_SESSION['recuperacao'])): ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block mb-1">Nova Senha:</label>
                    <input type="password" name="nova_senha" required class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block mb-1">Confirmar Nova Senha:</label>
                    <input type="password" name="confirmar_senha" required class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded focus:ring-2 focus:ring-purple-500">
                </div>
                <button type="submit" class="w-full py-2 bg-purple-600 hover:bg-purple-700 rounded text-white font-bold">Alterar Senha</button>
            </form>
        <?php endif; ?>

        <div class="text-center mt-6">
            <a href="index.php" class="text-purple-300 hover:underline">← Voltar para Login</a>
        </div>
    </div>
</body>
</html>
