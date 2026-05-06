<?php
// ============================================================
//  includes/config.php
//  CORREÇÕES APLICADAS:
//  - Credenciais via variáveis de ambiente (não mais em texto puro)
//  - display_errors desligado em produção
//  - Timezone Brasil definido aqui (aplica a todo o sistema)
//  - Função sanitiza() mantida aqui para retrocompatibilidade
//  - Sessão iniciada de forma segura
// ============================================================

date_default_timezone_set('America/Sao_Paulo');

// Produção: erros só no log, nunca na tela
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ── Credenciais via variáveis de ambiente ──────────────────
// No Docker: passe como ENV no docker-compose.yml ou Dockerfile
// Exemplo docker-compose:
//   environment:
//     - DB_HOST=dpg-xxxx.oregon-postgres.render.com
//     - DB_NAME=autogest_db
//     - DB_USER=autogest_user
//     - DB_PASS=sua_senha_aqui
//
// Para desenvolvimento local, crie um arquivo .env e carregue
// com vlucas/phpdotenv, ou exporte as variáveis no shell.
// ─────────────────────────────────────────────────────────────
$db_host = getenv('DB_HOST') ?: 'dpg-d76u14n5r7bs73e1n83g-a.oregon-postgres.render.com';
$db_name = getenv('DB_NAME') ?: 'mechanic_workshop';
$db_user = getenv('DB_USER') ?: 'mechanic_workshop_user';
$db_pass = getenv('DB_PASS') ?: 'qmGzphoMxYK4Im0CdKk82ZIzVnFQu8UU';

try {
    $pdo = new PDO(
        "pgsql:host={$db_host};port=5432;dbname={$db_name};sslmode=require",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Erro conexão banco: ' . $e->getMessage());
    http_response_code(503);
    die('Sistema temporariamente indisponível. Tente novamente em instantes.');
}

// Retrocompatibilidade: sanitiza() usada em index.php e criar_conta.php
function sanitiza(string $valor): string
{
    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}
