
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// index.php - Versão corrigida

// Função de sanitização (adicionada no início)
function sanitiza($dado) {
    if (is_string($dado)) {
        $dado = trim($dado);
        $dado = stripslashes($dado);
        $dado = htmlspecialchars($dado, ENT_QUOTES, 'UTF-8');
    }
    return $dado;
}

session_start();
include 'includes/config.php';

// Verificar se já está logado ANTES de qualquer output
if (isset($_SESSION['usuario'])) {
    header("Location: menu.php");
    exit();
}

// Login automático via cookie
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
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
            setcookie('remember_token', '', time() - 3600, "/");
        }
    } catch (PDOException $e) {
        error_log("Erro login automático: " . $e->getMessage());
    }
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitizando o input
    $numero_loja = isset($_POST['numero_loja']) ? sanitiza($_POST['numero_loja']) : '';
    $senha = isset($_POST['senha']) ? $_POST['senha'] : '';

    // Validação básica
    if (empty($numero_loja) || empty($senha)) {
        $erro = "Preencha todos os campos!";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE numero_loja = ?");
            $stmt->execute([$numero_loja]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                // após validar número da loja e senha corretamente
                $_SESSION['usuario'] = [
                    'id' => $usuario['id'],
                    'numero_loja' => $usuario['numero_loja'],
                    'email' => $usuario['email']
                ];

                // SE marcou "Lembrar minha conta"
                if (!empty($_POST['remember'])) {
                    $token = bin2hex(random_bytes(32));
                    $expira = date('Y-m-d H:i:s', strtotime('+30 days')); // Aumentei para 30 dias

                    // Verificar se as colunas existem na tabela
                    try {
                        $stmt = $pdo->prepare("
                            UPDATE usuarios 
                            SET remember_token = :token, remember_expires = :expira
                            WHERE id = :id
                        ");
                        $stmt->execute([
                            ':token' => password_hash($token, PASSWORD_DEFAULT),
                            ':expira' => $expira,
                            ':id' => $usuario['id']
                        ]);

                        setcookie(
                            'remember_token',
                            $token,
                            [
                                'expires' => time() + (60 * 60 * 24 * 30),
                                'path' => '/',
                                'domain' => '',
                                'secure' => isset($_SERVER['HTTPS']),
                                'httponly' => true,
                                'samesite' => 'Strict'
                            ]
                        );
                    } catch (PDOException $e) {
                        error_log("Erro ao salvar remember_token: " . $e->getMessage());
                        // Se as colunas não existirem, continua sem o remember token
                    }
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
        .glass-effect {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-text-fill-color: white;
            -webkit-box-shadow: 0 0 0px 1000px #1a1a2e inset;
            transition: background-color 5000s ease-in-out 0s;
        }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center px-4">
    <div class="glass-effect rounded-2xl w-full max-w-4xl overflow-hidden flex flex-col md:flex-row shadow-2xl fade-in">
        <!-- Lado da imagem -->
        <div class="hidden md:block md:w-1/2 bg-cover bg-center" style="background-image: url('login.jpeg');">
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
                <img src="logo.png" alt="AutoGest" class="mx-auto w-20 h-20 mb-4 rounded-full shadow-lg border-2 border-purple-500" onerror="this.src='https://via.placeholder.com/80?text=AutoGest'">
                <h2 class="text-3xl font-bold text-white">Bem-vindo de volta</h2>
                <p class="text-gray-300 mt-2">Faça login para acessar sua conta</p>
            </div>

            <?php if($erro): ?>
                <div class="bg-red-500 bg-opacity-20 border border-red-500 text-red-200 p-4 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-exclamation-circle mr-2 text-red-400"></i>
                    <span><?= htmlspecialchars($erro) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-white text-sm font-medium mb-2">
                        <i class="fas fa-store mr-1 text-purple-400"></i> Número da Loja
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fas fa-store"></i>
                        </span>
                        <input type="text" 
                               name="numero_loja" 
                               required 
                               autofocus
                               value="<?= isset($_POST['numero_loja']) ? htmlspecialchars($_POST['numero_loja']) : '' ?>"
                               class="pl-10 w-full py-3 bg-gray-800 bg-opacity-50 rounded-lg border border-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-white placeholder-gray-400 transition duration-200"
                               placeholder="Digite o número da loja">
                    </div>
                </div>
                
                <div>
                    <label class="block text-white text-sm font-medium mb-2">
                        <i class="fas fa-lock mr-1 text-purple-400"></i> Senha
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" 
                               name="senha" 
                               id="senha" 
                               required
                               class="pl-10 pr-10 w-full py-3 bg-gray-800 bg-opacity-50 rounded-lg border border-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-white placeholder-gray-400 transition duration-200"
                               placeholder="Digite sua senha">
                        <button type="button" 
                                onclick="togglePassword()" 
                                class="absolute right-3 top-3 text-gray-400 hover:text-white transition duration-200"
                                aria-label="Mostrar/ocultar senha">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <label class="flex items-center text-sm text-gray-300 cursor-pointer group">
                        <input type="checkbox" 
                               name="remember" 
                               class="w-4 h-4 mr-2 rounded bg-gray-700 border-gray-600 text-purple-500 focus:ring-purple-500 focus:ring-offset-gray-800 cursor-pointer"
                               <?= isset($_POST['remember']) ? 'checked' : '' ?>>
                        <span class="group-hover:text-white transition duration-200">Lembrar minha conta</span>
                    </label>
                    
                    <a href="recuperar_senha.php" class="text-sm text-purple-400 hover:text-purple-300 transition duration-200">
                        Esqueceu a senha?
                    </a>
                </div>
                
                <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-lg shadow-lg transition-all duration-200 transform hover:-translate-y-0.5 hover:shadow-xl active:translate-y-0">
                    <i class="fas fa-sign-in-alt mr-2"></i> Entrar no Sistema
                </button>
                
                <div class="text-center pt-4 border-t border-gray-700">
                    <p class="text-gray-400">
                        Não tem uma conta? 
                        <a href="criar_conta.php" class="text-purple-400 hover:text-purple-300 font-medium transition duration-200 hover:underline">
                            Criar conta
                        </a>
                    </p>
                </div>
            </form>
            
            <!-- Versão do sistema -->
            <div class="text-center mt-6 text-gray-500 text-xs">
                <span>AutoGest v1.0 &copy; <?= date('Y') ?></span>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const senha = document.getElementById('senha');
            const icon = document.getElementById('toggleIcon');
            
            if (senha.type === 'password') {
                senha.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                senha.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Prevenir reenvio do formulário ao atualizar a página
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Adicionar classe de erro visual nos campos
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('invalid', function(e) {
                e.preventDefault();
                this.classList.add('border-red-500');
            });
            
            input.addEventListener('input', function() {
                this.classList.remove('border-red-500');
            });
        });
    </script>
</body>
</html>