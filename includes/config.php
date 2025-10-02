<?php
$host = "dpg-d3eq5rumcj7s73dvr4sg-a.oregon-postgres.render.com"; // ATENÇÃO: Adicionei o domínio completo
$port = "5432";
$dbname = "cx7670_x1d7";
$user = "cx7670_x1d7_user";
$password = "uv26wxOj3EqtYfGCp8NJyAOEudNkxdUI";

try {
    // CORREÇÃO: Adicionando 'sslmode=require'
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Opcional: Para evitar problemas com certificados, adicione opções de SSL (se necessário)
    // $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require", $user, $password, [
    //     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    //     PDO::PGSQL_ATTR_SSL_MODE => PDO::PGSQL_SSLMODE_REQUIRE,
    // ]);
    
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
?>
