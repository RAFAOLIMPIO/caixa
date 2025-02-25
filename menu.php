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
</head>
<body>
    <div class="container">
        <h1>Bem-vindo, <?= htmlspecialchars($_SESSION['usuario']['numero_loja']) ?></h1>
        
        <nav>
            <a href="caixa.php">Controle de Caixa</a>
            <a href="relatorio.php">Relatório</a>
            <a href="funcionarios.php">Cadastrar Funcionários</a>
            <a href="logout.php">Sair</a>
            
        </nav>
    </div>
</body>
</html>