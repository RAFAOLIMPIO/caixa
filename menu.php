<?php
// Inicia a sessão
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php"); // Redireciona para a página de login
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Principal</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <style>
        /* Centraliza o conteúdo */
        .container {
            text-align: center;
            margin-top: 50px;
        }

        /* Estilo dos botões */
        .menu-button {
            display: block; /* Faz os botões ficarem um abaixo do outro */
            width: 200px; /* Define uma largura fixa */
            padding: 10px;
            margin: 10px auto; /* Espaçamento e centralização */
            font-size: 16px;
            text-align: center;
            text-decoration: none;
            color: white;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }

        /* Efeito ao passar o mouse */
        .menu-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bem-vindo, <?= htmlspecialchars($_SESSION['usuario']['numero_loja']) ?></h1>
        
        <nav>
            <a href="caixa.php" class="menu-button">Controle de Caixa</a>
            <a href="relatorio.php" class="menu-button">Relatório</a>
            <a href="funcionarios.php" class="menu-button">Cadastrar Funcionários</a>
            <a href="logout.php" class="menu-button">Sair</a>
        </nav>
    </div>
</body>
</html>