<?php
// dozero/api/dashboard.php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/database.php';

$action = $_GET['action'] ?? 'summary';
$month  = $_GET['month']  ?? date('Y-m');   // YYYY-MM
$year   = $_GET['year']   ?? date('Y');
$page   = max(1, (int)($_GET['page']  ?? 1));
$limit  = (int)($_GET['limit'] ?? 10);
$search = trim($_GET['search'] ?? '');
$tipo   = $_GET['tipo'] ?? '';

try {
    $db = getDB();

    switch ($action) {

        // ── summary ──────────────────────────────────────────────────────────
        case 'summary':
            $stmt = $db->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN tipo='credito' THEN valor END), 0)                                              AS aportes,
                    COALESCE(SUM(CASE WHEN tipo='debito' AND (classificacao='aemf' OR classificacao IS NULL) THEN valor END), 0) AS despesas_aemf,
                    COALESCE(SUM(CASE WHEN tipo='debito' AND classificacao='pf'   THEN valor END), 0)                      AS despesas_pf,
                    COALESCE(SUM(CASE WHEN tipo='credito' THEN valor ELSE -valor END), 0)                                  AS saldo,
                    COUNT(*) AS total_transacoes
                FROM transacoes
                WHERE mes_referencia = :mes
            ");
            $stmt->execute([':mes' => $month]);
            echo json_encode(['success' => true, 'data' => $stmt->fetch()], JSON_UNESCAPED_UNICODE);
            break;

        // ── transactions ─────────────────────────────────────────────────────
        case 'transactions':
            $where  = ['t.mes_referencia = :mes'];
            $params = [':mes' => $month];

            if ($search !== '') {
                $where[]            = 't.descricao LIKE :search';
                $params[':search']  = '%' . $search . '%';
            }
            if ($tipo !== '') {
                $where[]           = 't.tipo = :tipo';
                $params[':tipo']   = $tipo;
            }

            $whereSQL = 'WHERE ' . implode(' AND ', $where);
            $offset   = ($page - 1) * $limit;

            $cntStmt = $db->prepare("SELECT COUNT(*) FROM transacoes t $whereSQL");
            $cntStmt->execute($params);
            $total = (int) $cntStmt->fetchColumn();

            $stmt = $db->prepare("
                SELECT t.id, t.data, t.descricao, t.valor, t.tipo,
                       t.classificacao, t.conciliado, t.beneficiario,
                       c.nome AS categoria, c.cor AS categoria_cor
                FROM transacoes t
                LEFT JOIN categorias c ON c.id = t.categoria_id
                $whereSQL
                ORDER BY t.data DESC, t.id DESC
                LIMIT :lim OFFSET :off
            ");
            $stmt->bindValue(':lim',  $limit,  PDO::PARAM_INT);
            $stmt->bindValue(':off',  $offset, PDO::PARAM_INT);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->execute();

            echo json_encode([
                'success' => true,
                'data'    => $stmt->fetchAll(),
                'total'   => $total,
                'pages'   => (int) ceil($total / max(1, $limit)),
                'page'    => $page,
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ── chart — monthly bar for a year ───────────────────────────────────
        case 'chart':
            $stmt = $db->prepare("
                SELECT
                    mes_referencia AS mes,
                    COALESCE(SUM(CASE WHEN tipo='credito' THEN valor END), 0)                                                   AS aportes,
                    COALESCE(SUM(CASE WHEN tipo='debito' AND (classificacao='aemf' OR classificacao IS NULL) THEN valor END), 0) AS despesas_aemf,
                    COALESCE(SUM(CASE WHEN tipo='debito' AND classificacao='pf' THEN valor END), 0)                             AS despesas_pf
                FROM transacoes
                WHERE YEAR(data) = :ano AND mes_referencia IS NOT NULL
                GROUP BY mes_referencia
                ORDER BY mes_referencia
            ");
            $stmt->execute([':ano' => $year]);
            $rows = $stmt->fetchAll();

            // Fill all 12 months even if empty
            $meses    = [];
            $aportes  = [];
            $despAemf = [];
            $despPf   = [];
            $map      = [];
            foreach ($rows as $r) { $map[$r['mes']] = $r; }
            for ($m = 1; $m <= 12; $m++) {
                $key = $year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
                $meses[]    = date('M', mktime(0,0,0,$m,1));
                $aportes[]  = (float)($map[$key]['aportes']      ?? 0);
                $despAemf[] = (float)($map[$key]['despesas_aemf'] ?? 0);
                $despPf[]   = (float)($map[$key]['despesas_pf']   ?? 0);
            }

            echo json_encode([
                'success' => true,
                'labels'  => $meses,
                'datasets' => [
                    ['label' => 'Aportes',        'data' => $aportes,  'color' => '#28a745'],
                    ['label' => 'Despesas AEMF',  'data' => $despAemf, 'color' => '#17a2b8'],
                    ['label' => 'Despesas PF',    'data' => $despPf,   'color' => '#dc3545'],
                ],
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ── by-category ──────────────────────────────────────────────────────
        case 'byCategory':
            $stmt = $db->prepare("
                SELECT c.nome, c.tipo, c.cor,
                       COUNT(t.id) AS qtd,
                       COALESCE(SUM(t.valor), 0) AS total
                FROM transacoes t
                JOIN categorias c ON c.id = t.categoria_id
                WHERE t.mes_referencia = :mes
                GROUP BY c.id
                ORDER BY total DESC
            ");
            $stmt->execute([':mes' => $month]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
            break;

        // ── available months ─────────────────────────────────────────────────
        case 'months':
            $stmt = $db->query("
                SELECT DISTINCT mes_referencia AS mes
                FROM transacoes
                WHERE mes_referencia IS NOT NULL
                ORDER BY mes_referencia DESC
                LIMIT 36
            ");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_COLUMN)], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ação inválida'], JSON_UNESCAPED_UNICODE);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
