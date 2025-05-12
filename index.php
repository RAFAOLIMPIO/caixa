<?php
include 'includes/config.php';

if(isset($_SESSION['usuario'])) {
    header("Location: menu.php");
    exit();
}

$erro = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $numero_loja = sanitizar($_POST['numero_loja']);
    $senha = sanitizar($_POST['senha']);

    try {  
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE numero_loja = ?");  
        $stmt->execute([$numero_loja]);  
        $usuario = $stmt->fetch();  

        if($usuario && password_verify($senha, $usuario['senha'])) {  
            $_SESSION['usuario'] = [  
                'id' => $usuario['id'],  
                'numero_loja' => $usuario['numero_loja'],  
                'email' => $usuario['email']  
            ];  
            
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
    <title>AutoGest - Login</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="logo.png">

    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>
<body class="bg-black text-white min-h-screen flex items-center justify-center px-4">
    <div class="bg-gray-900 bg-opacity-80 backdrop-blur-lg shadow-2xl rounded-2xl w-full max-w-4xl overflow-hidden flex flex-col md:flex-row">
        <!-- Lado da imagem -->
        <div class="hidden md:block md:w-1/2 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1503264116251-35a269479413?auto=format&fit=crop&w=800&q=80');">
        </div>
        <!-- Lado do formulário -->
        <div class="w-full md:w-1/2 p-8">
            <div class="text-center mb-6">
                <img src="logo.png" alt="AutoGest" class="mx-auto w-16 h-16 mb-2">
                <h2 class="text-2xl font-bold">Faça seu login <span class="text-pink-400">.</span></h2>
            </div>

            <?php if($erro): ?>
                <div class="bg-red-600 text-white p-2 rounded mb-4 text-center">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm mb-1">Número da Loja</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400"><i class="fas fa-store"></i></span>
                        <input type="text" name="numero_loja" required autofocus
                            class="pl-10 w-full py-2 bg-gray-800 rounded-lg border border-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm mb-1">Senha</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400"><i class="fas fa-lock"></i></span>
                        <input type="password" name="senha" id="senha" required
                            class="pl-10 w-full py-2 bg-gray-800 rounded-lg border border-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <button type="button" onclick="togglePassword()" class="absolute right-2 top-2 text-gray-400">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                <div class="flex items-center text-sm">
                    <input type="checkbox" name="lembrar" id="lembrar" class="mr-2">
                    <label for="lembrar">Lembrar minha conta</label>
                </div>
                <button type="submit" class="w-full py-2 rounded-lg bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 font-bold text-white">
                    <i class="fas fa-sign-in-alt mr-2"></i> Entrar
                </button>
                <div class="flex justify-between mt-4 text-sm">
                    <a href="recuperar_senha.php" class="text-purple-300 hover:underline"><i class="fas fa-key"></i> Recuperar Senha</a>
                    <a href="criar_conta.php" class="text-purple-300 hover:underline"><i class="fas fa-user-plus"></i> Criar Conta</a>
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
