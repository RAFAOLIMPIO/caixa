<?php
// logout.php
session_start();

// Limpar todas as variáveis de sessão
$_SESSION = array();

// Se for destruir o cookie de sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir a sessão
session_destroy();

// Limpar cookie de lembrar
setcookie('lembrar_token', '', time() - 3600, "/");

// Redirecionar para login
header("Location: index.php");
exit();
?>
