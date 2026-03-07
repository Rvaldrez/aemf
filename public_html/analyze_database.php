<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once "includes/config.php";

echo "<!DOCTYPE html>
<html>
<head>
    <title>Análise do Banco de Dados - AEMF</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        h2 { color: #667eea; margin-top: 30px; }
        h3 { color: #764ba2; }
        .table-info { 
            background: white; 
            padding: 20px; 
            margin: 20px 0; 
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 15px 0;
        }
        th { 
            background: #667eea; 
            color: white; 
            padding: 10px; 
            text-align: left;
        }
        td { 
            padding: 8px; 
            border-bottom: 1px solid #ddd;
        }
        .structure { background: #f0f4ff; }
        .data { background: #fff8e1; }
        .stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-value { 
            font-size: 24px; 
            font-weight: bold; 
            color: #667eea;
        }
        .stat-label { 
            color: #666; 
            font-size: 14px;
        }
        code { 
            background: #f4f4f4; 
            padding: 2px 5px; 
            border-radius: 3px;
        }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔍 Análise Completa do Banco de Dados</h1>
";

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. LISTAR TODAS AS TABELAS
    echo "<h2>📊 Estrutura do Banco de Dados</h2>";
    
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div class='stats'>";
    echo "<div class='stat-card'>
            <div class='stat-value'>" . count($tables) . "</div>
            <div class='stat-label'>Tabelas no BD</div>
          </div>";
    
    // Estatísticas gerais
    $totalRecords = 0;
    foreach ($tables as $table) {
        $count = $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        $totalRecords += $count;
    }
    
    echo "<div class='stat-card'>
            <div class='stat-value'>" . number_format($totalRecords, 0, ',', '.') . "</div>
            <div class='stat-label'>Total de Registros</div>
          </div>";
    
    // Tamanho do BD
    $dbSize = $db->query("
        SELECT 
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size
        FROM information_schema.tables 
        WHERE table_schema = '" . DB_NAME . "'"
    )->fetchColumn();
    
    echo "<div class='stat-card'>
            <div class='stat-value'>" . $dbSize . " MB</div>
            <div class='stat-label'>Tamanho do BD</div>
          </div>";
    echo "</div>";
    
    // 2. ANALISAR CADA TABELA
    foreach ($tables as $table) {
        echo "<div class='table-info'>";
        echo "<h3>📁 Tabela: <code>$table</code></h3>";
        
        // Estrutura da tabela
        echo "<h4>Estrutura:</h4>";
        echo "<table class='structure'>";
        echo "<tr>
                <th>Campo</th>
                <th>Tipo</th>
                <th>Null</th>
                <th>Key</th>
                <th>Default</th>
                <th>Extra</th>
              </tr>";
        
        $structure = $db->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($structure as $field) {
            echo "<tr>";
            echo "<td><strong>{$field['Field']}</strong></td>";
            echo "<td>{$field['Type']}</td>";
            echo "<td>{$field['Null']}</td>";
            echo "<td>{$field['Key']}</td>";
            echo "<td>{$field['Default']}</td>";
            echo "<td>{$field['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Contar registros
        $count = $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "<p><strong>Total de registros:</strong> $count</p>";
        
        // Amostra de dados (primeiros 5 registros)
        if ($count > 0) {
            echo "<h4>Amostra de Dados (5 primeiros registros):</h4>";
            echo "<div style='overflow-x: auto;'>";
            echo "<table class='data'>";
            
            $sample = $db->query("SELECT * FROM `$table` LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            
            // Cabeçalho
            echo "<tr>";
            foreach (array_keys($sample[0]) as $col) {
                echo "<th>$col</th>";
            }
            echo "</tr>";
            
            // Dados
            foreach ($sample as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    // Limitar tamanho do texto exibido
                    $display = htmlspecialchars(substr($value ?? '', 0, 100));
                    if (strlen($value ?? '') > 100) $display .= "...";
                    echo "<td>$display</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
            echo "</div>";
            
            // Estatísticas específicas por tabela
            if ($table == 'transacoes') {
                echo "<h4>📈 Estatísticas de Transações:</h4>";
                
                // Total por tipo
                $stats = $db->query("
                    SELECT 
                        tipo,
                        COUNT(*) as qtd,
                        SUM(ABS(valor)) as total
                    FROM transacoes
                    GROUP BY tipo
                ")->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<table>";
                echo "<tr><th>Tipo</th><th>Quantidade</th><th>Total (R$)</th></tr>";
                foreach ($stats as $stat) {
                    echo "<tr>";
                    echo "<td>{$stat['tipo']}</td>";
                    echo "<td>{$stat['qtd']}</td>";
                    echo "<td>" . number_format($stat['total'], 2, ',', '.') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // Por mês
                $monthly = $db->query("
                    SELECT 
                        DATE_FORMAT(data, '%Y-%m') as mes,
                        COUNT(*) as qtd,
                        SUM(CASE WHEN valor > 0 THEN valor ELSE 0 END) as entradas,
                        SUM(CASE WHEN valor < 0 THEN ABS(valor) ELSE 0 END) as saidas
                    FROM transacoes
                    GROUP BY mes
                    ORDER BY mes DESC
                    LIMIT 6
                ")->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<h4>📅 Movimentação Mensal:</h4>";
                echo "<table>";
                echo "<tr><th>Mês</th><th>Qtd</th><th>Entradas</th><th>Saídas</th><th>Saldo</th></tr>";
                foreach ($monthly as $month) {
                    $saldo = $month['entradas'] - $month['saidas'];
                    echo "<tr>";
                    echo "<td>{$month['mes']}</td>";
                    echo "<td>{$month['qtd']}</td>";
                    echo "<td style='color:green'>+" . number_format($month['entradas'], 2, ',', '.') . "</td>";
                    echo "<td style='color:red'>-" . number_format($month['saidas'], 2, ',', '.') . "</td>";
                    echo "<td style='color:" . ($saldo > 0 ? 'green' : 'red') . "'>" . number_format($saldo, 2, ',', '.') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        
        echo "</div>";
    }
    
    // 3. CONSULTAS ÚTEIS
    echo "<h2>🔧 Consultas SQL Úteis</h2>";
    echo "<div class='table-info'>";
    
    $queries = [
        "Transações não conciliadas" => "
            SELECT COUNT(*) as total 
            FROM transacoes 
            WHERE conciliado = 0
        ",
        "Transações sem categoria" => "
            SELECT COUNT(*) as total 
            FROM transacoes 
            WHERE categoria_id IS NULL
        ",
        "Maior transação de entrada" => "
            SELECT descricao, valor, data 
            FROM transacoes 
            WHERE valor > 0 
            ORDER BY valor DESC 
            LIMIT 1
        ",
        "Maior transação de saída" => "
            SELECT descricao, ABS(valor) as valor, data 
            FROM transacoes 
            WHERE valor < 0 
            ORDER BY valor ASC 
            LIMIT 1
        "
    ];
    
    foreach ($queries as $label => $query) {
        echo "<h4>$label:</h4>";
        echo "<code>" . htmlspecialchars($query) . "</code>";
        
        try {
            $result = $db->query($query)->fetch(PDO::FETCH_ASSOC);
            echo "<pre>" . print_r($result, true) . "</pre>";
        } catch (Exception $e) {
            echo "<p style='color:red'>Erro: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background:#ffebee; color:#c62828; padding:20px; border-radius:5px;'>";
    echo "<h3>❌ Erro de Conexão</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div></body></html>";
?>
