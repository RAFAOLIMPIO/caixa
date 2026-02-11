<?php
// =====================================================
// AutoGest - Configuração Principal (Render + PostgreSQL)
// =====================================================

// Inicia sessão com segurança
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações de conexão com o banco Render PostgreSQL
define('DB_HOST', 'dpg-d5o45nmid0rc739178kg-a.oregon-postgres.render.com');
define('DB_PORT', '5432');
define('DB_NAME', 'autogest_db');
define('DB_USER', 'autogest_db_user');
define('DB_PASS', 'HCnQoffMQtvf0H1meJO4PyGgGKIw4edb');

// 🔹 Função utilitária de segurança (declarada apenas uma vez)
if (!function_exists('sanitizar')) {
    function sanitizar($valor) {
        if (is_array($valor)) {
            return array_map('sanitizar', $valor);
        }
        return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
    }
}

try {
    $pdo = new PDO(
        "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // Log do erro
    error_log('[' . date('Y-m-d H:i:s') . '] Erro de conexão PDO: ' . $e->getMessage());
    
    // Mensagem amigável para o usuário
    die("
    <!DOCTYPE html>
    <html>
    <head>
        <title>Sistema Indisponível</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #000000 0%, #1a1a2e 50%, #16213e 100%);
                color: white;
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                text-align: center;
                padding: 20px;
            }
            .container {
                background: rgba(255,255,255,0.1);
                padding: 40px;
                border-radius: 15px;
                backdrop-filter: blur(10px);
                max-width: 500px;
            }
            h1 {
                color: #ef4444;
                margin-bottom: 20px;
            }
            .error-details {
                margin-top: 20px;
                padding: 15px;
                background: rgba(255,0,0,0.1);
                border-radius: 8px;
                font-size: 12px;
                text-align: left;
                color: #ff9999;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>⚠️ Sistema Temporariamente Indisponível</h1>
            <p>Estamos realizando manutenção no banco de dados.</p>
            <p>Por favor, tente novamente em alguns minutos.</p>
            
            <div class='error-details'>
                <strong>Detalhes técnicos:</strong><br>
                " . htmlspecialchars($e->getMessage()) . "
            </div>
            
            <p style='margin-top: 30px; color: #ccc; font-size: 12px;'>
                AutoGest - Sistema de Gestão<br>
                " . date('d/m/Y H:i:s') . "
            </p>
        </div>
    </body>
    </html>");
}

// 🔹 Verificação automática do cookie "remember_token"
if (!isset($_SESSION['usuario']) && !empty($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, numero_loja, email 
            FROM usuarios 
            WHERE remember_token = :token 
            AND remember_expires > NOW()
        ");
        $stmt->execute([':token' => $token]);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            $_SESSION['usuario'] = [
                'id' => $usuario['id'],
                'numero_loja' => $usuario['numero_loja'],
                'email' => $usuario['email']
            ];
        } else {
            // Token inválido ou expirado, limpa o cookie
            setcookie('remember_token', '', time() - 3600, '/');
        }
    } catch (PDOException $e) {
        error_log('Erro na verificação do cookie: ' . $e->getMessage());
    }
}

// =====================================================
// 🔹 Configurações gerais do sistema
// =====================================================
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 0); // Desativar erros em produção
error_reporting(0); // Desativar relatório de erros em produção

// Para desenvolvimento, você pode ativar com:
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// =====================================================
// 🔹 Funções auxiliares
// =====================================================

function usuario_logado() {
    return isset($_SESSION['usuario']) && !empty($_SESSION['usuario']['id']);
}

function verificar_login() {
    if (!usuario_logado()) {
        header("Location: index.php");
        exit();
    }
}

function usuario_atual() {
    if (usuario_logado()) {
        return $_SESSION['usuario'];
    }
    return null;
}

function logout() {
    // Limpar token do banco se existir
    if (isset($_SESSION['usuario']['id'])) {
        try {
            global $pdo;
            $stmt = $pdo->prepare("UPDATE usuarios SET remember_token = NULL, remember_expires = NULL WHERE id = ?");
            $stmt->execute([$_SESSION['usuario']['id']]);
        } catch (PDOException $e) {
            error_log('Erro ao limpar token: ' . $e->getMessage());
        }
    }
    
    // Destruir sessão
    $_SESSION = [];
    session_destroy();
    
    // Limpar cookie
    setcookie('remember_token', '', time() - 3600, '/');
    
    header("Location: index.php");
    exit();
}

// =====================================================
// 🔹 Funções para formatar valores
// =====================================================

if (!function_exists('formatar_moeda')) {
    function formatar_moeda($valor, $com_simbolo = true) {
        $valor = (float)$valor;
        $formatado = number_format($valor, 2, ',', '.');
        return $com_simbolo ? 'R$ ' . $formatado : $formatado;
    }
}

if (!function_exists('formatar_data')) {
    function formatar_data($data, $formato = 'd/m/Y H:i') {
        if (empty($data) || $data == '0000-00-00 00:00:00') {
            return '-';
        }
        return date($formato, strtotime($data));
    }
}

// =====================================================
// 🔹 Inicializa o buffer de saída
// =====================================================
ob_start();
?>