<?php
$url = parse_url(getenv("DATABASE_URL"));

$host = $url["host"];
$port = $url["port"];
$dbname = ltrim($url["path"], "/");
$user = $url["user"];
$pass = $url["pass"];

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS teste_conexao (
            id SERIAL PRIMARY KEY,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");

    echo "✅ Conectou no banco e criou tabela de teste!";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}