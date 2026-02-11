<?php
// teste_erro.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testando API...<br>";

require_once __DIR__ . '/includes/config.php';

echo "Config carregada<br>";

if (!isset($_SESSION['usuario']['id'])) {
    echo "Usuário não logado<br>";
} else {
    echo "Usuário ID: " . $_SESSION['usuario']['id'] . "<br>";
    
    try {
        // Teste de conexão com o banco
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM vendas WHERE usuario_id = ?");
        $stmt->execute([$_SESSION['usuario']['id']]);
        $result = $stmt->fetch();
        echo "Total de vendas: " . $result['total'] . "<br>";
        
        // Teste estrutura da tabela vendas
        $stmt = $pdo->prepare("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'vendas'");
        $stmt->execute();
        $colunas = $stmt->fetchAll();
        
        echo "Colunas da tabela vendas:<br>";
        foreach ($colunas as $col) {
            echo "- " . $col['column_name'] . " (" . $col['data_type'] . ")<br>";
        }
    } catch (Exception $e) {
        echo "Erro no banco: " . $e->getMessage() . "<br>";
    }
}