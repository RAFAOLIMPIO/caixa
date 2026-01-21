<?php
$databaseUrl =
    $_SERVER['DATABASE_URL']
    ?? $_ENV['DATABASE_URL']
    ?? null;

if (!$databaseUrl) {
    die("❌ DATABASE_URL não encontrada (Render ainda não injetou)");
}

// remove ?sslmode=require se existir
$databaseUrl = explode('?', $databaseUrl)[0];

$url = parse_url($databaseUrl);

$host = $url['host'];
$port = $url['port'] ?? 5432;
$dbname = ltrim($url['path'], '/');
$user = $url['user'];
$pass = $url['pass'];

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

    echo "✅ CONECTADO AO POSTGRESQL DO RENDER COM SUCESSO!";
} catch (Throwable $e) {
    echo "❌ Erro: " . $e->getMessage();
}