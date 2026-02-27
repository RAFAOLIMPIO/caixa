<?php
// ===============================
// AUTO GEST - INDEX LOGIN
// ===============================

session_start();

/*
|--------------------------------------------------------------------------
| CARREGA CONFIG
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/includes/config.php';


/*
|--------------------------------------------------------------------------
| REDIRECIONA SE JÁ LOGADO
|--------------------------------------------------------------------------
*/
if (isset($_SESSION['usuario'])) {
    header("Location: menu.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| LOGIN AUTOMÁTICO COOKIE
|--------------------------------------------------------------------------
*/
if (!empty($_COOKIE['remember_token'])) {

    try {

        $stmt = $pdo->prepare("
            SELECT * FROM usuarios
            WHERE remember_token IS NOT NULL
        ");

        $stmt->execute();
        $usuarios = $stmt->fetchAll();

        foreach ($usuarios as $user) {

            if (password_verify($_COOKIE['remember_token'], $user['remember_token'])) {

                $_SESSION['usuario'] = [
                    'id' => $user['id'],
                    'numero_loja' => $user['numero_loja'],
                    'email' => $user['email']
                ];

                header("Location: menu.php");
                exit;
            }
        }

    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}


$erro = '';

/*
|--------------------------------------------------------------------------
| LOGIN NORMAL
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $numero_loja = sanitiza($_POST['numero_loja'] ?? '');
    $senha        = $_POST['senha'] ?? '';

    if (!$numero_loja || !$senha) {

        $erro = "Preencha todos os campos.";

    } else {

        try {

            $stmt = $pdo->prepare("
                SELECT * FROM usuarios
                WHERE numero_loja = ?
            ");

            $stmt->execute([$numero_loja]);

            $usuario = $stmt->fetch();

            if ($usuario && password_verify($senha, $usuario['senha'])) {

                $_SESSION['usuario'] = [
                    'id' => $usuario['id'],
                    'numero_loja' => $usuario['numero_loja'],
                    'email' => $usuario['email']
                ];

                /*
                |--------------------------------------------------------------------------
                | REMEMBER LOGIN
                |--------------------------------------------------------------------------
                */
                if (!empty($_POST['remember'])) {

                    $token = bin2hex(random_bytes(32));

                    $stmt = $pdo->prepare("
                        UPDATE usuarios
                        SET remember_token = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        password_hash($token, PASSWORD_DEFAULT),
                        $usuario['id']
                    ]);

                    setcookie(
                        'remember_token',
                        $token,
                        time() + (60 * 60 * 24 * 30),
                        '/',
                        '',
                        isset($_SERVER['HTTPS']),
                        true
                    );
                }

                header("Location: menu.php");
                exit;

            } else {

                $erro = "Credenciais inválidas.";
            }

        } catch (PDOException $e) {

            error_log($e->getMessage());
            $erro = "Erro interno do sistema.";
        }
    }
}
?>