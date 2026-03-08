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

        <!-- Cards -->
        <div class="cards">

            <a href="caixa.php" class="card">
                <div class="icon">💰</div>
                <h3>Controle de Caixa</h3>
                <p>Registre vendas e controle pagamentos</p>
            </a>

            <a href="relatorio.php" class="card">
                <div class="icon">📊</div>
                <h3>Relatórios</h3>
                <p>Analise vendas e desempenho</p>
            </a>

            <a href="funcionarios.php" class="card">
                <div class="icon">👥</div>
                <h3>Funcionários</h3>
                <p>Gerencie sua equipe</p>
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