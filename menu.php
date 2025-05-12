<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoGest | Menu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-black text-white min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-xl bg-gray-900 bg-opacity-80 p-8 rounded-2xl shadow-2xl">
        <div class="text-center mb-8">
            <img src="logo.png" alt="AutoGest" class="mx-auto w-20 h-20 mb-4">
            <h1 class="text-3xl font-bold">Bem-vindo, <?= htmlspecialchars($_SESSION['usuario']['numero_loja']) ?> 👋</h1>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-center">
            <a href="caixa.php" class="bg-purple-600 hover:bg-purple-700 p-4 rounded-xl shadow transition-all">
                <i class="fas fa-cash-register text-2xl mb-2 block"></i>
                <span class="font-semibold">Controle de Caixa</span>
            </a>
            <a href="relatorio.php" class="bg-purple-600 hover:bg-purple-700 p-4 rounded-xl shadow transition-all">
                <i class="fas fa-chart-line text-2xl mb-2 block"></i>
                <span class="font-semibold">Relatórios</span>
            </a>
            <a href="funcionarios.php" class="bg-purple-600 hover:bg-purple-700 p-4 rounded-xl shadow transition-all">
                <i class="fas fa-users-cog text-2xl mb-2 block"></i>
                <span class="font-semibold">Funcionários</span>
            </a>
            <a href="logout.php" class="bg-red-600 hover:bg-red-700 p-4 rounded-xl shadow transition-all">
                <i class="fas fa-sign-out-alt text-2xl mb-2 block"></i>
                <span class="font-semibold">Sair do Sistema</span>
            </a>
        </div>
    </div>
</body>
</html>
