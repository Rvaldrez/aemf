<?php
// dozero/includes/utils.php — shared utility functions

/**
 * Recalcula a cascata de saldos mensais respeitando a lógica de fluxo de caixa:
 *   Saldo Final(m) = Saldo_Inicial(m) + Créditos(m) - Débitos(m)
 *   Saldo_Inicial(m+1) = Saldo_Final(m)
 *
 * O saldo_inicial do PRIMEIRO mês é semeado a partir da tabela `saldo_inicial`
 * (posição de abertura definida pelo administrador). Se a tabela não existir ou
 * estiver vazia, cai de volta ao valor já armazenado em saldos_mensais. Os meses
 * seguintes têm o saldo_inicial encadeado automaticamente do saldo_final anterior.
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

    // Seed: try the global saldo_inicial table first (authoritative opening balance).
    // Fall back to whatever saldo_inicial is stored in saldos_mensais for the first month.
    // Fall back to 0 if neither is available.
    $saldoFinalAnterior = 0.0;
    try {
        $siGlobal = $db->query("SELECT valor FROM saldo_inicial ORDER BY data_ref ASC LIMIT 1")->fetchColumn();
        if ($siGlobal !== false && $siGlobal !== null) {
            $saldoFinalAnterior = (float)$siGlobal;
        }
    } catch (Throwable $e) {
        // saldo_inicial table not yet created; fall through to saldos_mensais
    }
    if ($saldoFinalAnterior == 0.0) {
        $siStmt = $db->prepare("SELECT saldo_inicial FROM saldos_mensais WHERE mes_referencia = :mes LIMIT 1");
        $siStmt->execute([':mes' => $meses[0]]);
        $saldoFinalAnterior = (float)($siStmt->fetchColumn() ?: 0.0);
    }

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
