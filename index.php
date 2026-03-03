<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'includes/config.php';

$erro = "";

/* ===============================
   SE BANCO FALHAR, NÃO QUEBRA SITE
================================== */
if (!isset($pdo) || !$pdo) {
    $erro = "Sistema temporariamente indisponível.";
}

/* ===============================
   SE JÁ ESTIVER LOGADO
================================== */
if (isset($_SESSION['usuario'])) {
    header("Location: menu.php");
    exit();
}

/* ===============================
   LOGIN NORMAL
================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$erro) {

    $numero_loja = sanitiza($_POST['numero_loja'] ?? '');
    $senha       = $_POST['senha'] ?? '';

    if (empty($numero_loja) || empty($senha)) {
        $erro = "Preencha todos os campos!";
    } else {

        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE numero_loja = :numero_loja LIMIT 1");
            $stmt->bindParam(':numero_loja', $numero_loja);
            $stmt->execute();
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($senha, $usuario['senha'])) {

                $_SESSION['usuario'] = [
                    'id' => $usuario['id'],
                    'numero_loja' => $usuario['numero_loja'],
                    'email' => $usuario['email']
                ];

                header("Location: menu.php");
                exit();

            } else {
                $erro = "Credenciais inválidas!";
            }

        } catch (Exception $e) {
            $erro = "Erro interno. Tente novamente.";
            error_log($e->getMessage());
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
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-900 flex items-center justify-center min-h-screen">

<div class="bg-gray-800 p-8 rounded-xl shadow-lg w-full max-w-md">

    <h1 class="text-2xl text-white text-center mb-6 font-bold">AutoGest</h1>

    <?php if($erro): ?>
        <div class="bg-red-600 text-white p-3 rounded mb-4 text-sm">
            <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">

        <div>
            <label class="text-gray-300 text-sm">Número da Loja</label>
            <input type="text" name="numero_loja" required
                class="w-full mt-1 p-2 rounded bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

        <div>
            <label class="text-gray-300 text-sm">Senha</label>
            <input type="password" name="senha" required
                class="w-full mt-1 p-2 rounded bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

        <button type="submit"
            class="w-full bg-purple-600 hover:bg-purple-700 text-white p-2 rounded font-semibold transition">
            Entrar
        </button>

    </form>

</div>

</body>
</html>