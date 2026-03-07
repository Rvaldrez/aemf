<?php
echo "=== RELATÓRIO COMPLETO DO SISTEMA ===\n";
echo "Data: " . date("Y-m-d H:i:s") . "\n\n";

// 1. ESTRUTURA DE ARQUIVOS
echo "1. ESTRUTURA DE ARQUIVOS:\n";
echo "-------------------------\n";
$dirs = ['api/', 'includes/', 'uploads/2025-09/', 'vendor/'];
foreach($dirs as $dir) {
    if(is_dir($dir)) {
        echo "\n[$dir]\n";
        $files = glob($dir . '*');
        foreach($files as $file) {
            if(!is_dir($file)) {
                echo "  - " . basename($file) . " (" . filesize($file) . " bytes)\n";
            }
        }
    }
}

// 2. BANCO DE DADOS - ESTRUTURA
echo "\n\n2. ESTRUTURA DO BANCO DE DADOS:\n";
echo "--------------------------------\n";
require_once 'includes/config.php';

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS
    );
    
    // Tabelas
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach($tables as $table) {
        echo "\nTabela: $table\n";
        $cols = $db->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        foreach($cols as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
        $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "  Total de registros: $count\n";
    }
    
    // 3. DADOS IMPORTADOS
    echo "\n\n3. TRANSAÇÕES IMPORTADAS:\n";
    echo "-------------------------\n";
    $stats = $db->query("
        SELECT 
            mes_referencia,
            COUNT(*) as total,
            SUM(CASE WHEN tipo='credito' THEN 1 ELSE 0 END) as creditos,
            SUM(CASE WHEN tipo='debito' THEN 1 ELSE 0 END) as debitos,
            COUNT(DISTINCT documento_origem) as docs
        FROM transacoes 
        GROUP BY mes_referencia
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($stats as $stat) {
        echo "Mês {$stat['mes_referencia']}: {$stat['total']} transações ";
        echo "({$stat['creditos']} créditos, {$stat['debitos']} débitos) ";
        echo "de {$stat['docs']} documentos\n";
    }
    
    // 4. REFERÊNCIAS CADASTRADAS
    echo "\n\n4. PADRÕES DE REFERÊNCIA:\n";
    echo "-------------------------\n";
    $refs = $db->query("
        SELECT r.padrao, c.nome as categoria, r.uso_count 
        FROM referencias r 
        LEFT JOIN categorias c ON r.categoria_id = c.id 
        ORDER BY r.uso_count DESC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($refs as $ref) {
        echo "- {$ref['padrao']} → {$ref['categoria']} (usado {$ref['uso_count']}x)\n";
    }
    
    // 5. ÚLTIMAS TRANSAÇÕES NÃO CLASSIFICADAS
    echo "\n\n5. TRANSAÇÕES NÃO CLASSIFICADAS:\n";
    echo "---------------------------------\n";
    $naoClass = $db->query("
        SELECT data, descricao, valor, tipo 
        FROM transacoes 
        WHERE categoria_id IS NULL OR categoria_id = 0
        ORDER BY data DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($naoClass as $t) {
        echo "{$t['data']} | {$t['descricao']} | R$ " . number_format($t['valor'], 2, ',', '.') . "\n";
    }
    
    // 6. VERIFICAR ARQUIVO IMPORT_HANDLER
    echo "\n\n6. CONTEÚDO DO IMPORT_HANDLER.PHP:\n";
    echo "-----------------------------------\n";
    if(file_exists('api/import_handler.php')) {
        $content = file_get_contents('api/import_handler.php');
        // Mostrar apenas as partes importantes
        if(preg_match('/foreach.*?lines.*?\{(.*?)\}.*?\}/s', $content, $match)) {
            echo "Loop principal de processamento encontrado\n";
            echo "Procurando por TAB: " . (strpos($content, 'strpos($line, "\t")') ? "SIM" : "NÃO") . "\n";
            echo "Regex para data: " . (strpos($content, '/ ago') ? "SIM" : "NÃO") . "\n";
        }
    }
    
} catch(Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO RELATÓRIO ===\n";
?>
