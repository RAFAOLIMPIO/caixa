<?php
// verificar_tabela.php
require_once __DIR__ . '/includes/config.php';

echo "<h3>Verificando estrutura da tabela VENDAS</h3>";

try {
    // Verificar se tabela existe
    $stmt = $pdo->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'vendas')");
    $existe = $stmt->fetchColumn();
    
    if (!$existe) {
        echo "ERRO: Tabela 'vendas' não existe!<br>";
        exit;
    }
    
    echo "✓ Tabela 'vendas' existe<br><br>";
    
    // Listar colunas
    $stmt = $pdo->query("
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns 
        WHERE table_name = 'vendas'
        ORDER BY ordinal_position
    ");
    
    $colunas = $stmt->fetchAll();
    
    echo "<h4>Colunas da tabela:</h4>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Coluna</th><th>Tipo</th><th>Nulo?</th><th>Default</th></tr>";
    
    foreach ($colunas as $col) {
        echo "<tr>";
        echo "<td>" . $col['column_name'] . "</td>";
        echo "<td>" . $col['data_type'] . "</td>";
        echo "<td>" . $col['is_nullable'] . "</td>";
        echo "<td>" . ($col['column_default'] ?: 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><h4>Testando consulta básica:</h4>";
    $stmt = $pdo->prepare("SELECT id, cliente, valor_total, status, valor_devolvido FROM vendas LIMIT 5");
    $stmt->execute();
    $vendas = $stmt->fetchAll();
    
    if (empty($vendas)) {
        echo "Nenhuma venda encontrada (isso é normal se a tabela estiver vazia)<br>";
    } else {
        echo "<pre>" . print_r($vendas, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}