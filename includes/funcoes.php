<?php

function verificar_login() {

    if (!isset($_SESSION['usuario'])) {
        header("Location: index.php");
        exit();
    }

}

?>