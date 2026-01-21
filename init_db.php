<?php
$databaseUrl = getenv("DATABASE_URL");

if (!$databaseUrl) {
    die("❌ DATABASE_URL não encontrada");
}

// Remove parâmetros extras (?sslmode=...)
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

    echo "✅ Conectado ao PostgreSQL do Render com sucesso!";
} catch (Throwable $e) {
    echo "❌ Erro: " . $e->getMessage();
}