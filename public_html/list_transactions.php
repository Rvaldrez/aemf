<?php
require_once 'includes/config.php';

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS
    );
    
    $sql = "SELECT data, descricao, valor, tipo FROM transacoes ORDER BY data, id";
    $stmt = $db->query($sql);
    
    echo "TRANSAÇÕES IMPORTADAS:\n";
    echo "======================\n\n";
    
    $count = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count++;
        $sinal = $row['tipo'] == 'debito' ? '-' : '';
        printf("%2d. %s | %-30s | %sR$ %10s\n", 
            $count, 
            $row['data'], 
            substr($row['descricao'], 0, 30),
            $sinal,
            number_format($row['valor'], 2, ',', '.')
        );
    }
    
    echo "\nTotal: $count transações\n";
    
} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>
