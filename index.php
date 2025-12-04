<?php
// index.php
session_start();
include 'includes/config.php';

// Verificar se já está logado ANTES de qualquer output
if (isset($_SESSION['usuario'])) {
    header("Location: menu.php");
    exit();
}

// Login automático via cookie - MOVER PARA DEPOIS da verificação de sessão
if (isset($_COOKIE['lembrar_token'])) {
    $token = $_COOKIE['lembrar_token'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE lembrar_token = ?");
        $stmt->execute([$token]);
        $usuario = $stmt->fetch();
        if ($usuario) {
            $_SESSION['usuario'] = [
                'id' => $usuario['id'],
                'numero_loja' => $usuario['numero_loja'],
                'email' => $usuario['email']
            ];
            header("Location: menu.php");
            exit();
        } else {
            setcookie('lembrar_token', '', time() - 3600, "/");
        }
    } catch (PDOException $e) {
        error_log("Erro login automático: " . $e->getMessage());
    }
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $numero_loja = sanitizar($_POST['numero_loja']);
    $senha = $_POST['senha'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE numero_loja = ?");
        $stmt->execute([$numero_loja]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario'] = [
                'id' => $usuario['id'],
                'numero_loja' => $usuario['numero_loja'],
                'email' => $usuario['email']
            ];

            if (isset($_POST['lembrar'])) {
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
    } catch (PDOException $e) {
        $erro = "Erro no sistema. Tente novamente.";
        error_log("Erro login: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoGest - Login</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-bg {
            background: linear-gradient(135deg, #000000 0%, #1a1a2e 50%, #16213e 100%);
        }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center px-4">
    <div class="glass-effect rounded-2xl w-full max-w-4xl overflow-hidden flex flex-col md:flex-row shadow-2xl fade-in">
        <!-- Lado da imagem -->
        <div class="hidden md:block md:w-1/2 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1486312338219-ce68d2c6f44d?auto=format&fit=crop&w=800&q=80');">
            <div class="h-full bg-black bg-opacity-50 flex items-center justify-center p-8">
                <div class="text-center text-white">
                    <h1 class="text-4xl font-bold mb-4 gradient-text">AutoGest</h1>
                    <p class="text-xl opacity-90">Sistema de Gestão Automotiva</p>
                    <p class="mt-4 opacity-80">Controle de vendas, funcionários e relatórios em um só lugar</p>
                </div>
            </div>
        </div>
        
        <!-- Lado do formulário -->
        <div class="w-full md:w-1/2 p-8 md:p-12">
            <div class="text-center mb-8">
                <img src="logo.png" alt="AutoGest" class="mx-auto w-20 h-20 mb-4 rounded-full shadow-lg">
                <h2 class="text-3xl font-bold text-white">Bem-vindo de volta</h2>
                <p class="text-gray-300 mt-2">Faça login para acessar sua conta</p>
            </div>

            <?php if($erro): ?>
                <div class="bg-red-500 bg-opacity-20 border border-red-500 text-red-200 p-4 rounded-lg mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-white text-sm font-medium mb-2">Número da Loja</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fas fa-store"></i>
                        </span>
                        <input type="text" name="numero_loja" required autofocus
                            class="pl-10 w-full py-3 bg-gray-800 bg-opacity-50 rounded-lg border border-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-white placeholder-gray-400 transition duration-200"
                            placeholder="Digite o número da loja">
                    </div>
                </div>
                
                <div>
                    <label class="block text-white text-sm font-medium mb-2">Senha</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="senha" id="senha" required
                            class="pl-10 pr-10 w-full py-3 bg-gray-800 bg-opacity-50 rounded-lg border border-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-white placeholder-gray-400 transition duration-200"
                            placeholder="Digite sua senha">
                        <button type="button" onclick="togglePassword()" class="absolute right-2 top-2 text-gray-400 hover:text-white transition duration-200">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <label class="flex items-center text-sm text-gray-300">
                        <input type="checkbox" name="lembrar" class="mr-2 rounded bg-gray-700 border-gray-600 text-purple-500 focus:ring-purple-500">
                        Lembrar minha conta
                    </label>
                    
                    <a href="recuperar_senha.php" class="text-sm text-purple-400 hover:text-purple-300 transition duration-200">
                        Esqueceu a senha?
                    </a>
                </div>
                
                <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-lg shadow-lg transition-all duration-200 transform hover:-translate-y-0.5">
                    <i class="fas fa-sign-in-alt mr-2"></i> Entrar no Sistema
                </button>
                
                <div class="text-center pt-4 border-t border-gray-700">
                    <p class="text-gray-400">
                        Não tem uma conta? 
                        <a href="criar_conta.php" class="text-purple-400 hover:text-purple-300 font-medium transition duration-200">
                            Criar conta
                        </a>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePassword() {
            const senha = document.getElementById('senha');
            const icon = document.getElementById('toggleIcon');
            if (senha.type === 'password') {
                senha.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                senha.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>
