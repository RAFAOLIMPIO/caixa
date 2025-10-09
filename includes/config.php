<?php
$host = "dpg-d3eq5rumcj7s73dvr4sg-a.oregon-postgres.render.com";
$port = "5432";
$dbname = "cx7670_x1d7";
$user = "cx7670_x1d7_user";
$password = "uv26wxOj3EqtYfGCp8NJyAOEudNkxdUI";

try {
    // Conexão PDO com PostgreSQL e requisito de SSL para o Render
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// =======================================================
// ADIÇÃO CRUCIAL: FUNÇÃO DE SEGURANÇA (SANITIZAÇÃO)
// =======================================================
/**
 * Limpa e sanitiza dados de entrada para prevenir ataques XSS.
 *
 * @param mixed $dado O dado a ser sanitizado.
 * @return mixed O dado sanitizado.
 */
function sanitizar($dado) {
    if (is_array($dado)) {
        return array_map('sanitizar', $dado);
    }
    // Usa filter_var com FILTER_SANITIZE_FULL_SPECIAL_CHARS 
    // e remove espaços desnecessários.
    return trim(filter_var($dado, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
}

?>
