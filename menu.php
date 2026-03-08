<?php
// menu.php
session_start();

// Verificação mais robusta da sessão
if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario']['id'])) {
    header("Location: index.php");
    exit();
}

// Verificar se os dados mínimos da sessão existem
if (empty($_SESSION['usuario']['numero_loja'])) {
    // Sessão corrompida, destruir e redirecionar
    session_destroy();
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoGest | Menu Principal</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .menu-bg {
            background: linear-gradient(135deg, #000000 0%, #1a1a2e 50%, #16213e 100%);
        }
    </style>
</head>
<body class="menu-bg min-h-screen flex items-center justify-center px-4 py-8">
    <div class="w-full max-w-4xl">
        <!-- Header -->
        <div class="text-center mb-12 fade-in">
            <img src="logo.png" alt="AutoGest" class="mx-auto w-24 h-24 mb-6 rounded-full shadow-2xl">
            <h1 class="text-4xl font-bold text-white mb-4">Auto<span class="gradient-text">Gest</span></h1>
            <p class="text-xl text-gray-300 mb-2">Sistema de Gestão Automotiva</p>
            <p class="text-gray-400">Bem-vindo, <span class="text-purple-400 font-semibold"><?= htmlspecialchars($_SESSION['usuario']['numero_loja']) ?></span> 👋</p>
        </div>

        <!-- Menu Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <a href="caixa.php" class="glass-effect p-6 rounded-2xl card-hover group fade-in">
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                        <i class="fas fa-cash-register text-2xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Controle de Caixa</h3>
                    <p class="text-gray-400 text-sm">Registre vendas e controle pagamentos</p>
                </div>
            </a>

            <a href="relatorio.php" class="glass-effect p-6 rounded-2xl card-hover group fade-in" style="animation-delay: 0.1s">
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-blue-500 to-teal-500 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                        <i class="fas fa-chart-line text-2xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Relatórios</h3>
                    <p class="text-gray-400 text-sm">Analise vendas e desempenho</p>
                </div>
            </a>

            <a href="funcionarios.php" class="glass-effect p-6 rounded-2xl card-hover group fade-in" style="animation-delay: 0.2s">
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                        <i class="fas fa-users-cog text-2xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Funcionários</h3>
                    <p class="text-gray-400 text-sm">Gerencie sua equipe</p>
                </div>
            </a>
        </div>

        <!-- Logout -->
        <div class="text-center fade-in" style="animation-delay: 0.3s">
            <a href="logout.php" class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition-all duration-200 transform hover:-translate-y-0.5 shadow-lg">
                <i class="fas fa-sign-out-alt mr-2"></i>
                Sair do Sistema
            </a>
        </div>
    </div>
</body>
</html>
