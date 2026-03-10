<?php
// dozero/includes/utils.php — shared utility functions

/**
 * Recalcula a cascata de saldos mensais respeitando a lógica de fluxo de caixa:
 *   Saldo Final(m) = Saldo_Inicial(m) + Créditos(m) - Débitos(m)
 *   Saldo_Inicial(m+1) = Saldo_Final(m)
 *
 * O saldo_inicial do PRIMEIRO mês é lido do banco de dados (foi armazenado
 * durante a importação do OFX a partir do LEDGERBAL). Os meses seguintes
 * têm o saldo_inicial encadeado do saldo_final do mês anterior.
 *
 * Deve ser chamado sempre que transações forem importadas ou excluídas.
 */
function recalcularCascata(PDO $db): void {
    $meses = $db->query("
        SELECT DISTINCT mes_referencia FROM transacoes
        WHERE mes_referencia IS NOT NULL
        ORDER BY mes_referencia ASC
    ")->fetchAll(PDO::FETCH_COLUMN);

    if (empty($meses)) return;

    // Seed: use the saldo_inicial already stored for the first month (from OFX LEDGERBAL).
    // If none exists yet, fall back to 0.
    $siStmt = $db->prepare("SELECT saldo_inicial FROM saldos_mensais WHERE mes_referencia = :mes LIMIT 1");
    $siStmt->execute([':mes' => $meses[0]]);
    $saldoFinalAnterior = (float)($siStmt->fetchColumn() ?: 0.0);

    $updStmt = $db->prepare("
        INSERT INTO saldos_mensais (mes_referencia, saldo_inicial, total_creditos, total_debitos, saldo_final)
        VALUES (:mes, :si, :c, :d, :sf)
        ON DUPLICATE KEY UPDATE
            saldo_inicial  = :si2,
            total_creditos = :c2,
            total_debitos  = :d2,
            saldo_final    = :sf2,
            updated_at     = NOW()
    ");

    $txStmt = $db->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN tipo='credito' THEN valor END), 0) AS creditos,
            COALESCE(SUM(CASE WHEN tipo='debito'  THEN valor END), 0) AS debitos
        FROM transacoes WHERE mes_referencia = :mes
    ");

    foreach ($meses as $mes) {
        $txStmt->execute([':mes' => $mes]);
        $row = $txStmt->fetch();
        $c   = (float)$row['creditos'];
        $d   = (float)$row['debitos'];
        $sf  = $saldoFinalAnterior + $c - $d;

        $updStmt->execute([
            ':mes'  => $mes, ':si'  => $saldoFinalAnterior,
            ':c'    => $c,   ':d'   => $d,   ':sf'  => $sf,
            ':si2'  => $saldoFinalAnterior,
            ':c2'   => $c,   ':d2'  => $d,   ':sf2' => $sf,
        ]);

        $saldoFinalAnterior = $sf;
    }
}
