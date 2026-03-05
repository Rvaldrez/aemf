<?php
/**
 * api/dashboard_api.php
 * API do Dashboard Financeiro — retorna resumo, transações, meses disponíveis e saldo inicial.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once dirname(__DIR__) . '/includes/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro de conexão com banco de dados']);
    exit;
}

// Garante que a tabela de saldo inicial existe
$pdo->exec("
    CREATE TABLE IF NOT EXISTS saldo_inicial (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        mes_referencia VARCHAR(7)      NOT NULL,
        saldo         DECIMAL(15,2)   NOT NULL DEFAULT 0,
        tipo          ENUM('manual','ledgerbal','calculado') NOT NULL DEFAULT 'manual',
        data_referencia DATE           NULL,
        observacoes   VARCHAR(255)    NULL,
        created_at    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
        updated_at    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_mes_tipo (mes_referencia, tipo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'summary':
            getSummary($pdo);
            break;
        case 'transactions':
            getTransactions($pdo);
            break;
        case 'months':
            getMonths($pdo);
            break;
        case 'balance':
            getBalance($pdo);
            break;
        case 'setSaldoInicial':
            setSaldoInicial($pdo, $_POST);
            break;
        case 'expensesGrouped':
            getExpensesGrouped($pdo);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Ação desconhecida: ' . htmlspecialchars($action)]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// ============================================================
// FUNÇÕES
// ============================================================

/**
 * Resumo financeiro do mês: entradas, saídas, saldo, saldo inicial, saldo final.
 */
function getSummary(PDO $pdo): void
{
    $month = validateMonth($_GET['month'] ?? '');

    $stmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN tipo = 'credito' THEN valor ELSE 0 END)                                   AS total_credito,
            SUM(CASE WHEN tipo = 'debito'  THEN valor ELSE 0 END)                                   AS total_debito,
            SUM(CASE WHEN tipo = 'debito'  AND classificacao = 'aemf' THEN valor ELSE 0 END)        AS despesas_aemf,
            SUM(CASE WHEN tipo = 'debito'  AND classificacao = 'pf'   THEN valor ELSE 0 END)        AS despesas_pf,
            COUNT(*)                                                                                 AS total_transacoes
        FROM transacoes
        WHERE mes_referencia = :mes
    ");
    $stmt->execute([':mes' => $month]);
    $row = $stmt->fetch();

    $entradas    = (float)($row['total_credito']  ?? 0);
    $saidas      = (float)($row['total_debito']   ?? 0);
    $despAemf    = (float)($row['despesas_aemf']  ?? 0);
    $despPf      = (float)($row['despesas_pf']    ?? 0);
    $totalTrans  = (int)  ($row['total_transacoes'] ?? 0);

    // Se não há classificação definida, apresenta todo o débito como "AEMF" por padrão
    if ($despAemf == 0 && $despPf == 0 && $saidas > 0) {
        $despAemf = $saidas;
    }

    $saldoInicial = getSaldoInicialValue($pdo, $month);
    $saldoFinal   = $saldoInicial !== null ? $saldoInicial + $entradas - $saidas : null;

    echo json_encode([
        'success'           => true,
        'month'             => $month,
        'month_label'       => formatMonthPt($month),
        'entradas'          => $entradas,
        'saidas'            => $saidas,
        'aportes'           => $entradas,
        'despesas_aemf'     => $despAemf,
        'despesas_pf'       => $despPf,
        'saldo'             => $entradas - $saidas,
        'saldo_inicial'     => $saldoInicial,
        'saldo_final'       => $saldoFinal,
        'total_transacoes'  => $totalTrans,
    ]);
}

/**
 * Lista paginada de transações do mês.
 */
function getTransactions(PDO $pdo): void
{
    $month   = validateMonth($_GET['month'] ?? '');
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 20;
    $offset  = ($page - 1) * $perPage;

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM transacoes WHERE mes_referencia = :mes");
    $countStmt->execute([':mes' => $month]);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT  t.id, t.data, t.descricao, t.valor, t.tipo, t.classificacao,
                c.nome AS categoria_nome, c.tipo AS categoria_tipo
        FROM    transacoes t
        LEFT JOIN categorias c ON t.categoria_id = c.id
        WHERE   t.mes_referencia = :mes
        ORDER BY t.data ASC, t.id ASC
        LIMIT   :lim OFFSET :off
    ");
    $stmt->bindValue(':mes', $month);
    $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset,  PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll();

    echo json_encode([
        'success'  => true,
        'data'     => $data,
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
    ]);
}

/**
 * Lista de meses disponíveis na tabela transacoes.
 */
function getMonths(PDO $pdo): void
{
    $stmt = $pdo->query("
        SELECT DISTINCT mes_referencia
        FROM   transacoes
        WHERE  mes_referencia IS NOT NULL
        ORDER  BY mes_referencia DESC
        LIMIT  36
    ");
    $rows   = $stmt->fetchAll();
    $months = array_map(fn($r) => [
        'value' => $r['mes_referencia'],
        'label' => formatMonthPt($r['mes_referencia']),
    ], $rows);

    echo json_encode(['success' => true, 'months' => $months]);
}

/**
 * Retorna informações de saldo inicial para o mês.
 */
function getBalance(PDO $pdo): void
{
    $month = validateMonth($_GET['month'] ?? '');

    $stmt = $pdo->prepare("
        SELECT saldo, tipo, data_referencia, observacoes
        FROM   saldo_inicial
        WHERE  mes_referencia = :mes
        ORDER  BY created_at DESC
    ");
    $stmt->execute([':mes' => $month]);
    $records = $stmt->fetchAll();

    echo json_encode([
        'success'       => true,
        'month'         => $month,
        'saldo_inicial' => getSaldoInicialValue($pdo, $month),
        'records'       => $records,
    ]);
}

/**
 * Salva (ou atualiza) o saldo inicial manual para um mês.
 */
function setSaldoInicial(PDO $pdo, array $post): void
{
    $mes    = $post['mes_referencia'] ?? '';
    $saldo  = (float)($post['saldo'] ?? 0);
    $obs    = substr($post['observacoes'] ?? '', 0, 255) ?: null;

    if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
        echo json_encode(['success' => false, 'error' => 'Mês inválido']);
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO saldo_inicial (mes_referencia, saldo, tipo, observacoes)
        VALUES (:mes, :saldo, 'manual', :obs)
        ON DUPLICATE KEY UPDATE saldo = VALUES(saldo), observacoes = VALUES(observacoes), updated_at = NOW()
    ");
    $stmt->execute([':mes' => $mes, ':saldo' => $saldo, ':obs' => $obs]);

    // Recalcula meses subsequentes (propagação do saldo final → saldo inicial)
    propagateSaldo($pdo, $mes);

    echo json_encode(['success' => true]);
}

// ============================================================
// HELPERS
// ============================================================

/**
 * Retorna o saldo inicial para o mês: prioridade manual > calculado > ledgerbal.
 */
function getSaldoInicialValue(PDO $pdo, string $month): ?float
{
    // Ordem de prioridade: manual > calculado > ledgerbal
    $stmt = $pdo->prepare("
        SELECT saldo FROM saldo_inicial
        WHERE  mes_referencia = :mes
        ORDER  BY FIELD(tipo,'manual','calculado','ledgerbal'), created_at DESC
        LIMIT  1
    ");
    $stmt->execute([':mes' => $month]);
    $row = $stmt->fetch();
    return $row ? (float)$row['saldo'] : null;
}

/**
 * Propaga saldo inicial → saldo final do mês → saldo inicial do mês seguinte (tipo='calculado').
 * Avança até 12 meses para frente enquanto há transações.
 */
function propagateSaldo(PDO $pdo, string $startMonth): void
{
    $current = $startMonth;
    for ($i = 0; $i < 12; $i++) {
        $si = getSaldoInicialValue($pdo, $current);
        if ($si === null) break;

        // Saldo final = saldo inicial + entradas - saídas do mês
        $stmt = $pdo->prepare("
            SELECT SUM(CASE WHEN tipo='credito' THEN valor ELSE 0 END) AS e,
                   SUM(CASE WHEN tipo='debito'  THEN valor ELSE 0 END) AS s
            FROM   transacoes WHERE mes_referencia = :mes
        ");
        $stmt->execute([':mes' => $current]);
        $row = $stmt->fetch();
        if (!$row || ($row['e'] === null && $row['s'] === null)) break;

        $saldoFinal = $si + (float)($row['e'] ?? 0) - (float)($row['s'] ?? 0);

        // Mês seguinte
        [$y, $m] = explode('-', $current);
        $nextMonth = date('Y-m', mktime(0, 0, 0, (int)$m + 1, 1, (int)$y));

        // Salvar como 'calculado' para o mês seguinte (não sobrescreve manual)
        $upsert = $pdo->prepare("
            INSERT INTO saldo_inicial (mes_referencia, saldo, tipo)
            VALUES (:mes, :saldo, 'calculado')
            ON DUPLICATE KEY UPDATE
                saldo      = IF(tipo = 'calculado', VALUES(saldo), saldo),
                updated_at = IF(tipo = 'calculado', NOW(), updated_at)
        ");
        $upsert->execute([':mes' => $nextMonth, ':saldo' => $saldoFinal]);

        $current = $nextMonth;
    }
}

/**
 * Valida e normaliza parâmetro de mês (YYYY-MM). Retorna mês mais recente da DB se inválido.
 */
function validateMonth(string $raw): string
{
    global $pdo;
    if (preg_match('/^\d{4}-\d{2}$/', $raw)) {
        return $raw;
    }
    // Fallback: mês mais recente na DB
    $stmt = $pdo->query("SELECT MAX(mes_referencia) FROM transacoes WHERE mes_referencia IS NOT NULL");
    $max  = $stmt->fetchColumn();
    return $max ?: date('Y-m');
}

/**
 * Retorna o nome do mês em português. Ex: "2026-01" → "Janeiro 2026".
 */
function formatMonthPt(string $ym): string
{
    static $names = [
        '01' => 'Janeiro',  '02' => 'Fevereiro', '03' => 'Março',
        '04' => 'Abril',    '05' => 'Maio',       '06' => 'Junho',
        '07' => 'Julho',    '08' => 'Agosto',     '09' => 'Setembro',
        '10' => 'Outubro',  '11' => 'Novembro',   '12' => 'Dezembro',
    ];
    $parts = explode('-', $ym, 2);
    if (count($parts) !== 2 || strlen($parts[0]) !== 4 || strlen($parts[1]) !== 2) {
        return $ym;
    }
    [$year, $month] = $parts;
    return ($names[$month] ?? $month) . ' ' . $year;
}

/**
 * Retorna despesas agrupadas por categoria (AEMF e PF) para o mês informado.
 */
function getExpensesGrouped(PDO $pdo): void
{
    $mes = $_GET['month'] ?? $_GET['mes'] ?? '';
    $mes = validateMonth($mes);

    $stmtAemf = $pdo->prepare("
        SELECT  c.nome, c.cor, SUM(t.valor) AS total
        FROM    transacoes t
        JOIN    categorias c ON t.categoria_id = c.id
        WHERE   t.mes_referencia = :mes
          AND   t.tipo = 'debito'
          AND   c.tipo = 'despesa_aemf'
        GROUP BY c.id, c.nome, c.cor
        ORDER BY total DESC
    ");
    $stmtAemf->execute([':mes' => $mes]);
    $despesasAemf = $stmtAemf->fetchAll();

    $stmtPf = $pdo->prepare("
        SELECT  c.nome, c.cor, SUM(t.valor) AS total
        FROM    transacoes t
        JOIN    categorias c ON t.categoria_id = c.id
        WHERE   t.mes_referencia = :mes
          AND   t.tipo = 'debito'
          AND   c.tipo = 'despesa_pf'
        GROUP BY c.id, c.nome, c.cor
        ORDER BY total DESC
    ");
    $stmtPf->execute([':mes' => $mes]);
    $despesasPf = $stmtPf->fetchAll();

    $stmtSem = $pdo->prepare("
        SELECT COUNT(*) AS qtd, COALESCE(SUM(valor),0) AS total
        FROM   transacoes
        WHERE  mes_referencia = :mes AND tipo = 'debito' AND categoria_id IS NULL
    ");
    $stmtSem->execute([':mes' => $mes]);
    $semCategoria = $stmtSem->fetch();

    $totalAemf = array_sum(array_column($despesasAemf, 'total'));
    $totalPf   = array_sum(array_column($despesasPf,   'total'));

    echo json_encode([
        'success'       => true,
        'month'         => $mes,
        'despesas_aemf' => $despesasAemf,
        'despesas_pf'   => $despesasPf,
        'total_aemf'    => $totalAemf,
        'total_pf'      => $totalPf,
        'sem_categoria' => $semCategoria,
    ]);
}
