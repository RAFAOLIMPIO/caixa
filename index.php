<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | AutoGest</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-black via-gray-900 to-black flex items-center justify-center">
    <div class="bg-gray-800 bg-opacity-70 backdrop-blur-lg p-8 rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex justify-center mb-6">
            <img src="logo.png" alt="Logo AutoGest" class="w-20 h-20">
        </div>
        <h2 class="text-white text-2xl font-bold text-center mb-6">Acesse o <span class="text-purple-400">AutoGest</span></h2>

        <form method="POST" action="autenticar.php" class="space-y-4">
            <div>
                <label for="email" class="text-white block mb-1">Email</label>
                <input type="email" name="email" id="email" required
                    class="w-full px-4 py-2 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label for="senha" class="text-white block mb-1">Senha</label>
                <input type="password" name="senha" id="senha" required
                    class="w-full px-4 py-2 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div class="flex justify-end">
                <a href="#" class="text-sm text-purple-300 hover:underline">Esqueci minha senha</a>
            </div>
            <button type="submit"
                class="w-full py-2 rounded-lg bg-gradient-to-r from-purple-500 to-pink-500 text-white font-semibold hover:from-purple-600 hover:to-pink-600 transition-all">
                Entrar
            </button>
        </form>

        <p class="text-sm text-gray-400 text-center mt-6">
            Ainda não tem conta? <a href="#" class="text-purple-300 hover:underline">Crie agora</a>
        </p>
    </div>
</body>
</html>
