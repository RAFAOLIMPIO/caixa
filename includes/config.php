<?php
// =====================================================
// AutoGest - Configuração Principal (Render + PostgreSQL)
// =====================================================

// Inicia sessão com segurança
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();

// Configurações de conexão com o banco Render PostgreSQL
define('DB_HOST', 'dpg-d4no0k24d50c739ok92g-a');
define('DB_PORT', '5432');
define('DB_NAME', 'banco7670_4bf6');
define('DB_USER', 'banco7670_4bf6_user');
define('DB_PASS', 'lu9ziOuXCSFAh3j8au0S8O5lqwz6b1kP');

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