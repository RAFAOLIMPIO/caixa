<?php
// ATENÇÃO: Deixe o display_errors LIGADO APENAS para desenvolvimento.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclui a configuração e a função sanitizar() (que deve estar em includes/config.php)
include 'includes/config.php'; 

// Inicia a sessão para usar $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário já está logado
if(isset($_SESSION['usuario'])) {
    header("Location: menu.php");
    exit();
}

$erros = [];
$sucesso = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize os dados de entrada
    $numero_loja = sanitizar($_POST['numero_loja'] ?? '');
    $email = sanitizar($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar_senha'] ?? '';
    $pergunta = sanitizar($_POST['pergunta'] ?? '');
    $resposta = sanitizar($_POST['resposta'] ?? '');

    // Validação da senha
    if(strlen($senha) < 8) {
        $erros[] = "A senha deve ter no mínimo 8 caracteres.";
    } elseif($senha !== $confirmar) {
        $erros[] = "As senhas não coincidem.";
    } else {
        // Bloco ELSE para senhas válidas
        try {
            // Verifica se o e-mail já existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if($stmt->rowCount() > 0) {
                $erros[] = "E-mail já cadastrado.";
            } else {
                // Insere o novo usuário
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                
                // QUERY DE TESTE: Usando 'password' sem aspas duplas (Tentativa Final)
                $stmt = $pdo->prepare('INSERT INTO usuarios (numero_loja, email, password, pergunta_seguranca, resposta_seguranca) 
                                       VALUES (?, ?, ?, ?, ?)');
                                       
                $stmt->execute([$numero_loja, $email, $senha_hash, $pergunta, $resposta]);
                
                header("Location: index.php");
                exit();
            }
        } catch(PDOException $e) {
            // AGORA VAMOS VER O ERRO DETALHADO!
            $erros[] = "Erro DETALHADO do banco: " . $e->getMessage(); 
        } // Fim do bloco 'else' (senhas válidas)
    } // Fim do bloco 'if($_SERVER['REQUEST_METHOD'] == 'POST')'
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Criar Conta</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-black text-white min-h-screen flex items-center justify-center px-4">
    <div class="bg-gray-900 bg-opacity-80 p-8 rounded-2xl shadow-2xl w-full max-w-xl">
        <h1 class="text-2xl font-bold text-center mb-6"><i class="fas fa-user-shield"></i> Cadastro de Nova Conta</h1>

        <?php if(!empty($erros)): ?>
            <div class="bg-red-600 text-white p-4 rounded mb-4">
                <?php foreach($erros as $erro): ?>
                    <p><?= htmlspecialchars($erro) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block mb-1"><i class="fas fa-hashtag"></i> Número da Loja</label>
                <input type="text" name="numero_loja" value="<?= htmlspecialchars($_POST['numero_loja'] ?? '') ?>" required
                    class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block mb-1"><i class="fas fa-envelope"></i> E-mail</label>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required
                    class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block mb-1"><i class="fas fa-lock"></i> Senha</label>
                <input type="password" name="senha" required
                    class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block mb-1"><i class="fas fa-lock"></i> Confirmar Senha</label>
                <input type="password" name="confirmar_senha" required
                    class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block mb-1"><i class="fas fa-question-circle"></i> Pergunta de Segurança</label>
                <select name="pergunta" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded focus:ring-2 focus:ring-purple-500" required>
                    <option value="">Selecione uma pergunta</option>
                    <option value="Nome do seu primeiro pet?" <?= isset($_POST['pergunta']) && $_POST['pergunta'] == 'Nome do seu primeiro pet?' ? 'selected' : '' ?>>Nome do seu primeiro pet?</option>
                    <option value="Nome da sua mãe solteira?" <?= isset($_POST['pergunta']) && $_POST['pergunta'] == 'Nome da sua mãe solteira?' ? 'selected' : '' ?>>Nome da sua mãe solteira?</option>
                    <option value="Cidade onde nasceu?" <?= isset($_POST['pergunta']) && $_POST['pergunta'] == 'Cidade onde nasceu?' ? 'selected' : '' ?>>Cidade onde nasceu?</option>
                </select>
            </div>
            <div>
                <label class="block mb-1"><i class="fas fa-key"></i> Resposta de Segurança</label>
                <input type="text" name="resposta" value="<?= htmlspecialchars($_POST['resposta'] ?? '') ?>" required
                    class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded focus:ring-2 focus:ring-purple-500">
            </div>
            <button type="submit" class="w-full py-2 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-bold rounded">
                <i class="fas fa-user-plus mr-2"></i> Criar Conta
            </button>
            <div class="text-center mt-4">
                <a href="index.php" class="text-purple-300 hover:underline"><i class="fas fa-sign-in-alt"></i> Voltar para Login</a>
            </div>
        </form>
    </div>
</body>
</html>
