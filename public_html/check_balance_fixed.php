<?php
require_once "includes/config.php";

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
    );
    
    echo "╔══════════════════════════════════════════════════╗\n";
    echo "║        CONCILIAÇÃO BANCÁRIA - AGOSTO/2025       ║\n";
    echo "╚══════════════════════════════════════════════════╝\n\n";
    
    // SALDOS REAIS DO EXTRATO (conforme PDF)
    $saldoInicial = 538.93;
    $saldoFinal = 42163.42; // Conforme última linha: "SALDO TOTAL DISPONÍVEL DIA 42.163,42"
    
    echo "📄 DADOS DO EXTRATO (VALORES REAIS)\n";
    echo "────────────────────────────────────\n";
    echo "Saldo Inicial (31/jul):     R$ " . number_format($saldoInicial, 2, ',', '.') . "\n";
    echo "Saldo Final (29/ago):       R$ " . number_format($saldoFinal, 2, ',', '.') . "\n";
    echo "Variação no Período:        R$ " . number_format($saldoFinal - $saldoInicial, 2, ',', '.') . "\n\n";
    
    // MOVIMENTAÇÕES IMPORTADAS
    $stats = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN tipo = 'credito' THEN valor ELSE 0 END) as entradas,
            SUM(CASE WHEN tipo = 'debito' THEN valor ELSE 0 END) as saidas,
            SUM(CASE WHEN classificacao LIKE '%investimento%' THEN 1 ELSE 0 END) as qtd_invest
        FROM transacoes 
        WHERE mes_referencia = '2025-08'
    ")->fetch();
    
    echo "💰 MOVIMENTAÇÕES IMPORTADAS\n";
    echo "────────────────────────────────────\n";
    echo "Total de Transações:        " . $stats['total'] . "\n";
    echo "Transações de Investimento: " . $stats['qtd_invest'] . "\n";
    echo "Total de Entradas:          R$ " . number_format($stats['entradas'], 2, ',', '.') . "\n";
    echo "Total de Saídas:            R$ " . number_format($stats['saidas'], 2, ',', '.') . "\n";
    echo "Movimento Líquido:          R$ " . number_format($stats['entradas'] - $stats['saidas'], 2, ',', '.') . "\n\n";
    
    // CÁLCULO DA CONCILIAÇÃO
    $saldoCalculado = $saldoInicial + $stats['entradas'] - $stats['saidas'];
    
    echo "✅ CONCILIAÇÃO BANCÁRIA\n";
    echo "────────────────────────────────────\n";
    echo "Saldo Inicial:              R$ " . number_format($saldoInicial, 2, ',', '.') . "\n";
    echo "(+) Total de Entradas:      R$ " . number_format($stats['entradas'], 2, ',', '.') . "\n";
    echo "(-) Total de Saídas:        R$ " . number_format($stats['saidas'], 2, ',', '.') . "\n";
    echo "════════════════════════════════════\n";
    echo "Saldo Calculado:            R$ " . number_format($saldoCalculado, 2, ',', '.') . "\n";
    echo "Saldo Real (Extrato):       R$ " . number_format($saldoFinal, 2, ',', '.') . "\n";
    echo "────────────────────────────────────\n";
    
    $diferenca = abs($saldoFinal - $saldoCalculado);
    
    if ($diferenca < 1.00) {
        echo "✅ CONCILIAÇÃO PERFEITA! ";
        if ($diferenca > 0) {
            echo "Diferença de apenas R$ " . number_format($diferenca, 2, ',', '.') . " (centavos)\n";
        } else {
            echo "Valores batem exatamente!\n";
        }
    } else {
        echo "⚠️  DIFERENÇA: R$ " . number_format($diferenca, 2, ',', '.') . "\n";
        echo "\nVerifique se todas as transações foram importadas corretamente.\n";
    }
    
    // RESUMO POR TIPO
    echo "\n📊 RESUMO POR CLASSIFICAÇÃO\n";
    echo "────────────────────────────────────\n";
    
    $classificacoes = $db->query("
        SELECT 
            classificacao,
            COUNT(*) as qtd,
            SUM(CASE WHEN tipo = 'credito' THEN valor ELSE 0 END) as creditos,
            SUM(CASE WHEN tipo = 'debito' THEN valor ELSE 0 END) as debitos
        FROM transacoes 
        WHERE mes_referencia = '2025-08'
        GROUP BY classificacao
        ORDER BY classificacao
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($classificacoes as $c) {
        $label = str_replace('_', ' ', ucfirst($c['classificacao']));
        echo str_pad($label, 25) . ": ";
        if ($c['creditos'] > 0) {
            echo "+R$ " . str_pad(number_format($c['creditos'], 2, ',', '.'), 12, ' ', STR_PAD_LEFT);
        }
        if ($c['debitos'] > 0) {
            echo " -R$ " . str_pad(number_format($c['debitos'], 2, ',', '.'), 12, ' ', STR_PAD_LEFT);
        }
        echo " ({$c['qtd']} trans.)\n";
    }
    
    echo "\n────────────────────────────────────\n";
    echo "Relatório gerado em: " . date('d/m/Y H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
?>
