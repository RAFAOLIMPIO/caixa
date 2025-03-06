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
    <title>Menu Principal</title>
    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="main-container">
        <main class="content-area">
            <div class="card" style="max-width: 600px; margin: 2rem auto;">
                <div class="brand-header">
                    <h1 class="brand-title">
                        <i class="fas fa-home"></i>
                        Bem-vindo, <?= htmlspecialchars($_SESSION['usuario']['numero_loja']) ?>
                    </h1>
                </div>

                <nav class="grid-links">
                    <a href="caixa.php" class="btn btn-primary hover-scale">
                        <i class="fas fa-cash-register"></i>
                        Controle de Caixa
                    </a>
                    
                    <a href="relatorio.php" class="btn btn-primary hover-scale">
                        <i class="fas fa-chart-line"></i>
                        Relatórios
                    </a>
                    
                    <a href="funcionarios.php" class="btn btn-primary hover-scale">
                        <i class="fas fa-users-cog"></i>
                        Funcionários
                    </a>
                    
                    <a href="logout.php" class="btn btn-danger hover-scale">
                        <i class="fas fa-sign-out-alt"></i>
                        Sair do Sistema
                    </a>
                </nav>
            </div>
        </main>
    </div>
</body>
</html>