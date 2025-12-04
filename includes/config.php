<?php
// === Configuração de Ambiente e Erros ===
define('ENVIRONMENT', getenv('APP_ENV') ?: 'development'); // Usa variável de ambiente ou default

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Configurações seguras para produção
    error_reporting(0);
    ini_set('display_errors', 0);
    // Inicia o log para capturar erros em produção
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/error.log');
}

// === Configurações de Conexão com o Banco de Dados (PostgreSQL no Render por padrão) ===
$db_type = getenv('DB_TYPE') ?: 'pgsql';

if ($db_type === 'pgsql') {
    // Dados do seu banco de dados no Render (usados como fallback se a variável de ambiente não estiver definida)
    define('DB_HOST', getenv('DB_HOST') ?: 'dpg-d1c3qummcj7s73a60q30-a.oregon-postgres.render.com');
    define('DB_PORT', getenv('DB_PORT') ?: '5432');
    define('DB_NAME', getenv('DB_NAME') ?: 'banco7670_4bf6');
    define('DB_USER', getenv('DB_USER') ?: 'banco7670_4bf6_user');
    define('DB_PASS', getenv('DB_PASS') ?: 'lu9ziOuXCSFAh3j8au0S8O5lqwz6b1kP');
    
    // O SSLMODE é obrigatório para o Render
    $sslmode = getenv('DB_SSLMODE') ?: 'require'; 
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=" . $sslmode;

} else {
    // Configurações para MySQL (ou outro SGBD)
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_NAME', getenv('DB_NAME') ?: 'autogest');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
    
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
}

// === Conexão PDO ===
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log("Erro de conexão: " . $e->getMessage());
    if (ENVIRONMENT === 'development') {
        die("Erro de conexão com o banco de dados: " . $e->getMessage());
    } else {
        die("Erro no sistema. Tente novamente mais tarde. (Ref: DB_ERROR)");
    }
}

// === Inicialização da Sessão ===
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// === Funções de Ajuda ===

// Função de sanitização
function sanitizar($dados) {
    if (is_array($dados)) {
        return array_map('sanitizar', $dados);
    }
    // Remove tags, espaços em branco e aplica a codificação de caracteres
    return htmlspecialchars(trim($dados), ENT_QUOTES, 'UTF-8');
}

// Função para formatar moeda
function formatar_moeda($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

// Função para validar CPF (caso necessário no futuro)
function validar_cpf($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}
?>
