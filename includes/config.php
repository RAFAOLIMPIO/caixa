<?php
// =====================================================
// AutoGest - Configuração Principal (Render + PostgreSQL)
// =====================================================

// Inicia sessão com segurança
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔹 Verificação automática do cookie "remember_token"
if (!isset($_SESSION['usuario']) && !empty($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];

    // Configurações de conexão com o banco Render PostgreSQL
    define('DB_HOST', 'dpg-d5o45nmid0rc739178kg-a.oregon-postgres.render.com');
    define('DB_PORT', '5432');
    define('DB_NAME', 'autogest_db');
    define('DB_USER', 'autogest_db_user');
    define('DB_PASS', 'HCnQoffMQtvf0H1meJO4PyGgGKIw4edb');

    try {
        // Conexão temporária para verificar o token
        $tempPdo = new PDO(
            "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 10
            ]
        );

        // Buscar todos os usuários com token não expirado
        $stmt = $tempPdo->prepare("
            SELECT * FROM usuarios 
            WHERE remember_expires > NOW()
        ");
        $stmt->execute();
        $usuarios = $stmt->fetchAll();

        foreach ($usuarios as $u) {
            if (password_verify($token, $u['remember_token'])) {
                $_SESSION['usuario'] = [
                    'id' => $u['id'],
                    'numero_loja' => $u['numero_loja'],
                    'email' => $u['email']
                ];
                break;
            }
        }
        
        // Fechar conexão temporária
        $tempPdo = null;
    } catch (PDOException $e) {
        error_log('Erro na verificação do cookie: ' . $e->getMessage());
    }
}

ob_start();

// Configurações de conexão com o banco Render PostgreSQL (principal)
define('DB_HOST', 'dpg-d5o45nmid0rc739178kg-a.oregon-postgres.render.com');
define('DB_PORT', '5432');
define('DB_NAME', 'autogest_db');
define('DB_USER', 'autogest_db_user');
define('DB_PASS', 'HCnQoffMQtvf0H1meJO4PyGgGKIw4edb');

try {
    // Conexão PDO com SSL simplificado (compatível com Render)
    $pdo = new PDO(
        "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10
        ]
    );
} catch (PDOException $e) {
    error_log('Erro de conexão com o banco: ' . $e->getMessage());

    // Exibição amigável no navegador
    die("
    <div style='background:#7f1d1d;color:#fff;padding:20px;border-radius:8px;
                font-family:Arial,sans-serif;text-align:center;margin:50px auto;
                max-width:600px;box-shadow:0 0 10px rgba(0,0,0,0.3);'>
        <h2 style='color:#f87171;'>❌ Erro ao conectar ao banco de dados</h2>
        <p>Verifique se o Render está online e se as credenciais estão corretas.</p>
        <hr style='border:1px solid #b91c1c;margin:15px 0;'>
        <p style='font-size:13px;color:#fca5a5'>
            <b>Mensagem técnica:</b><br>" . htmlspecialchars($e->getMessage()) . "
        </p>
    </div>");
}

// =====================================================
// 🔹 Função utilitária de segurança
// =====================================================
function sanitizar($valor)
{
    if (is_array($valor)) {
        return array_map('sanitizar', $valor);
    }

    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}

// =====================================================
// 🔹 Configurações gerais do sistema
// =====================================================
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 0); // Oculta erros no ambiente de produção
error_reporting(E_ALL);

// =====================================================
// 🔹 Função auxiliar para verificar login
// =====================================================
function usuario_logado()
{
    return isset($_SESSION['usuario']) && !empty($_SESSION['usuario']['id']);
}

// =====================================================
// 🔹 Função para verificar se usuário está logado
// =====================================================
function verificar_login() {
    if (!usuario_logado()) {
        header("Location: index.php");
        exit();
    }
}

// =====================================================
// 🔹 Função para obter informações do usuário atual
// =====================================================
function usuario_atual() {
    if (usuario_logado()) {
        return $_SESSION['usuario'];
    }
    return null;
}

// =====================================================
// 🔹 Função para logout seguro
// =====================================================
function logout() {
    session_destroy();
    setcookie('remember_token', '', time() - 3600, '/');
    header("Location: index.php");
    exit();
}
