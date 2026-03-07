<?php
/**
 * api/dashboard_api.php
 * Fornece dados para o dashboard financeiro via JSON.
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/database.php';

$action = $_GET['action'] ?? 'summary';
$period = $_GET['period'] ?? 'mensal';
$month  = $_GET['month']  ?? date('Y-m');
$page   = max(1, intval($_GET['page'] ?? 1));
$limit  = 8;

try {
    $db = Database::getInstance()->getConnection();

    switch ($action) {
        case 'summary':
            echo json_encode(getSummary($db, $period, $month), JSON_UNESCAPED_UNICODE);
            break;

        case 'transactions':
            echo json_encode(getTransactions($db, $page, $limit, $month), JSON_UNESCAPED_UNICODE);
            break;

        case 'byCategory':
            echo json_encode(getByCategory($db, $month), JSON_UNESCAPED_UNICODE);
            break;

        default:
            echo json_encode(['error' => 'Ação inválida'], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

// ===================================================================

function getSummary(PDO $db, string $period, string $month): array {
    $where = '';
    $params = [];

    if ($period === 'mensal') {
        $where = "WHERE mes_referencia = :mes";
        $params[':mes'] = $month;
    } elseif ($period === 'anual') {
        $where = "WHERE YEAR(data) = :ano";
        $params[':ano'] = substr($month, 0, 4);
    }

    $stmt = $db->prepare("
        SELECT
            SUM(CASE WHEN tipo = 'credito' THEN valor ELSE 0 END) AS aportes,
            -- Transações sem classificação são tratadas como despesas AEMF por padrão
            SUM(CASE WHEN tipo = 'debito' AND (classificacao = 'aemf' OR classificacao IS NULL) THEN valor ELSE 0 END) AS despesas_aemf,
            SUM(CASE WHEN tipo = 'debito' AND classificacao = 'pf' THEN valor ELSE 0 END) AS despesas_pf,
            SUM(CASE WHEN tipo = 'credito' THEN valor ELSE -valor END) AS saldo
        FROM transacoes
        $where
    ");
    $stmt->execute($params);
    return $stmt->fetch() ?: ['aportes' => 0, 'despesas_aemf' => 0, 'despesas_pf' => 0, 'saldo' => 0];
}

function getTransactions(PDO $db, int $page, int $limit, string $month): array {
    $offset = ($page - 1) * $limit;

    $countStmt = $db->prepare("SELECT COUNT(*) FROM transacoes WHERE mes_referencia = :mes");
    $countStmt->execute([':mes' => $month]);
    $total = (int) $countStmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT t.id, t.data, t.descricao, t.valor, t.tipo, t.conciliado, t.beneficiario,
               c.nome AS categoria_nome, t.classificacao
        FROM transacoes t
        LEFT JOIN categorias c ON t.categoria_id = c.id
        WHERE t.mes_referencia = :mes
        ORDER BY t.data DESC, t.id DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':mes',    $month,  PDO::PARAM_STR);
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return [
        'data'  => $stmt->fetchAll(),
        'total' => $total,
        'page'  => $page,
        'pages' => (int) ceil($total / $limit),
    ];
}

function getByCategory(PDO $db, string $month): array {
    $stmt = $db->prepare("
        SELECT c.nome, c.tipo, c.cor,
               COUNT(t.id) AS qtd,
               SUM(t.valor) AS total
        FROM transacoes t
        JOIN categorias c ON t.categoria_id = c.id
        WHERE t.mes_referencia = :mes
        GROUP BY c.id, c.nome, c.tipo, c.cor
        ORDER BY total DESC
    ");
    $stmt->execute([':mes' => $month]);
    return $stmt->fetchAll();
}
