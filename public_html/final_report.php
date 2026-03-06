<?php
require_once "includes/config.php";

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
    );
    
    echo "╔══════════════════════════════════════════════╗\n";
    echo "║   RELATÓRIO FINANCEIRO - AGOSTO/2025        ║\n";
    echo "║         AEMF I PARTICIPAÇÕES LTDA           ║\n";
    echo "╚══════════════════════════════════════════════╝\n\n";
    
    // RESUMO GERAL
    $stats = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN tipo = 'credito' THEN valor ELSE 0 END) as receitas,
            SUM(CASE WHEN tipo = 'debito' THEN valor ELSE 0 END) as despesas,
            SUM(CASE WHEN tipo = 'credito' THEN valor ELSE -valor END) as saldo
        FROM transacoes 
        WHERE mes_referencia = '2025-08'
    ")->fetch();
    
    echo "📊 RESUMO DO MÊS\n";
    echo "────────────────────────────────────\n";
    echo "Total de Transações: {$stats['total']}\n";
    echo "Total de Receitas:   R$ " . number_format($stats['receitas'], 2, ',', '.') . "\n";
    echo "Total de Despesas:   R$ " . number_format($stats['despesas'], 2, ',', '.') . "\n";
    echo "Saldo do Período:    R$ " . number_format($stats['saldo'], 2, ',', '.') . "\n\n";
    
    // RECEITAS DETALHADAS
    echo "💰 RECEITAS (ENTRADAS REAIS)\n";
    echo "────────────────────────────────────\n";
    $receitas = $db->query("
        SELECT data, descricao, valor 
        FROM transacoes 
        WHERE tipo = 'credito' 
        AND mes_referencia = '2025-08'
        ORDER BY data, valor DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($receitas) > 0) {
        foreach ($receitas as $r) {
            $data = date('d/m', strtotime($r['data']));
            echo "{$data} | " . str_pad(substr($r['descricao'], 0, 30), 30) . " | R$ " . 
                 str_pad(number_format($r['valor'], 2, ',', '.'), 12, ' ', STR_PAD_LEFT) . "\n";
        }
    } else {
        echo "Nenhuma receita no período\n";
    }
    
    echo "\n";
    
    // DESPESAS DETALHADAS
    echo "💸 DESPESAS (SAÍDAS REAIS)\n";
    echo "────────────────────────────────────\n";
    $despesas = $db->query("
        SELECT data, descricao, valor, 
               CASE 
                   WHEN categoria_id IS NOT NULL THEN 
                       (SELECT nome FROM categorias WHERE id = categoria_id)
                   ELSE classificacao
               END as categoria
        FROM transacoes 
        WHERE tipo = 'debito' 
        AND mes_referencia = '2025-08'
        ORDER BY data, valor DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($despesas as $d) {
        $data = date('d/m', strtotime($d['data']));
        $cat = $d['categoria'] ?? 'Sem categoria';
        echo "{$data} | " . str_pad(substr($d['descricao'], 0, 25), 25) . " | R$ " . 
             str_pad(number_format($d['valor'], 2, ',', '.'), 12, ' ', STR_PAD_LEFT) . 
             " | {$cat}\n";
    }
    
    echo "\n";
    
    // CLASSIFICAÇÃO DAS DESPESAS
    echo "📈 DESPESAS POR CATEGORIA\n";
    echo "────────────────────────────────────\n";
    $categorias = $db->query("
        SELECT 
            classificacao,
            COUNT(*) as qtd,
            SUM(valor) as total
        FROM transacoes 
        WHERE tipo = 'debito'
        AND mes_referencia = '2025-08'
        GROUP BY classificacao
        ORDER BY total DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($categorias as $c) {
        $label = $c['classificacao'] == 'aemf' ? 'Despesas AEMF' : 
                ($c['classificacao'] == 'pf' ? 'Despesas PF' : 'Outras');
        echo str_pad($label, 20) . ": R$ " . 
             str_pad(number_format($c['total'], 2, ',', '.'), 12, ' ', STR_PAD_LEFT) . 
             " ({$c['qtd']} transações)\n";
    }
    
    echo "\n";
    
    // TRANSAÇÕES NÃO CLASSIFICADAS
    $naoClass = $db->query("
        SELECT COUNT(*) as total 
        FROM transacoes 
        WHERE (categoria_id IS NULL OR categoria_id = 0)
        AND mes_referencia = '2025-08'
    ")->fetchColumn();
    
    if ($naoClass > 0) {
        echo "⚠️  ATENÇÃO: {$naoClass} transações precisam de classificação manual\n";
        echo "   Acesse o sistema para classificá-las\n";
    } else {
        echo "✅ Todas as transações estão classificadas\n";
    }
    
    echo "\n────────────────────────────────────\n";
    echo "Relatório gerado em: " . date('d/m/Y H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
?>
