<?php
require_once 'includes/config.php';

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS
    );
    
    // Limpar transações
    $db->exec("DELETE FROM transacoes");
    
    // Verificar
    $count = $db->query("SELECT COUNT(*) as total FROM transacoes")->fetch();
    
    echo "✓ Tabela limpa!\n";
    echo "Total de transações: " . $count['total'] . "\n";
    
} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>
