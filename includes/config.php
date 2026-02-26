<?php

define('DB_HOST', 'dpg-d6fojjh5pdvs73fli0ig-a.oregon-postgres.render.com');
define('DB_NAME', 'autogest_db_q243');
define('DB_USER', 'autogest_db_q243_user');
define('DB_PASS', 'SUA_SENHA_AQUI');

try {

    $pdo = new PDO(
        "pgsql:host=".DB_HOST.";port=5432;dbname=".DB_NAME.";sslmode=require",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

} catch (PDOException $e) {

    die("Erro conexão banco: ".$e->getMessage());

}
