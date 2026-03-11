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

        // ── latestMonth — returns the most recent month with transactions ─
        case 'latestMonth':
            $row = $db->query("
                SELECT mes_referencia AS mes
                FROM transacoes
                WHERE mes_referencia IS NOT NULL
                ORDER BY mes_referencia DESC
                LIMIT 1
            ")->fetchColumn();
            echo json_encode(['success' => true, 'mes' => $row ?: date('Y-m')], JSON_UNESCAPED_UNICODE);
            break;

        // ── summary ──────────────────────────────────────────────────────
        case 'summary':
            // Get totals from transactions
            $stmt = $db->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN tipo='credito' THEN valor END), 0)  AS total_creditos,
                    COALESCE(SUM(CASE WHEN tipo='debito'  THEN valor END), 0)  AS total_debitos,
                    COUNT(*) AS total_transacoes
                FROM transacoes
                WHERE mes_referencia = :mes
            ");
            $stmt->execute([':mes' => $month]);
            $tx = $stmt->fetch();

            // Get saldo_mensal row for cash-flow balance
            $smStmt = $db->prepare("SELECT * FROM saldos_mensais WHERE mes_referencia = :mes LIMIT 1");
            $smStmt->execute([':mes' => $month]);
            $sm = $smStmt->fetch() ?: [];

            $saldoInicial  = (float)($sm['saldo_inicial']  ?? 0);
            $totalCreditos = (float)($tx['total_creditos'] ?? 0);
            $totalDebitos  = (float)($tx['total_debitos']  ?? 0);
            $saldoFinal    = $saldoInicial + $totalCreditos - $totalDebitos;

            echo json_encode([
                'success' => true,
                'data'    => [
                    'saldo_inicial'    => $saldoInicial,
                    'total_creditos'   => $totalCreditos,
                    'total_debitos'    => $totalDebitos,
                    'saldo_final'      => $saldoFinal,
                    // kept for back-compat
                    'aportes'          => $totalCreditos,
                    'despesas_aemf'    => (float)($sm['total_debitos'] ?? $totalDebitos),
                    'despesas_pf'      => 0,
                    'saldo'            => $saldoFinal,
                    'total_transacoes' => (int)($tx['total_transacoes'] ?? 0),
                    'saldo_mensal'     => $sm,
                ],
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ── annualSummary — year-wide cash flow for annual view ───────────
        case 'annualSummary':
            $stmt = $db->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN tipo='credito' THEN valor END), 0)  AS total_creditos,
                    COALESCE(SUM(CASE WHEN tipo='debito'  THEN valor END), 0)  AS total_debitos,
                    COUNT(*) AS total_transacoes
                FROM transacoes
                WHERE YEAR(data) = :ano
            ");
            $stmt->execute([':ano' => $year]);
            $tx = $stmt->fetch();

            // saldo_inicial of first month of year
            $siStmt = $db->prepare("
                SELECT saldo_inicial FROM saldos_mensais
                WHERE mes_referencia LIKE :prefix
                ORDER BY mes_referencia ASC LIMIT 1
            ");
            $siStmt->execute([':prefix' => $year . '-%']);
            $si = (float)($siStmt->fetchColumn() ?: 0);

            $c  = (float)($tx['total_creditos'] ?? 0);
            $d  = (float)($tx['total_debitos']  ?? 0);

            echo json_encode([
                'success' => true,
                'data'    => [
                    'saldo_inicial'    => $si,
                    'total_creditos'   => $c,
                    'total_debitos'    => $d,
                    'saldo_final'      => $si + $c - $d,
                    'aportes'          => $c,
                    'total_transacoes' => (int)($tx['total_transacoes'] ?? 0),
                ],
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ── transactions ─────────────────────────────────────────────────
        case 'transactions':
            $where  = ['t.mes_referencia = :mes'];
            $params = [':mes' => $month];

            if ($search !== '') {
                $where[]           = '(t.descricao LIKE :search OR comp.beneficiario LIKE :search2 OR comp.descricao LIKE :search3)';
                $params[':search']  = '%' . $search . '%';
                $params[':search2'] = '%' . $search . '%';
                $params[':search3'] = '%' . $search . '%';
            }
            if ($tipo !== '') {
                $where[]         = 't.tipo = :tipo';
                $params[':tipo'] = $tipo;
            }

            $whereSQL = 'WHERE ' . implode(' AND ', $where);
            $offset   = ($page - 1) * $limit;

            // Count — needs join for search to work correctly
            $cntStmt = $db->prepare("
                SELECT COUNT(DISTINCT t.id)
                FROM transacoes t
                LEFT JOIN conciliacoes cc  ON cc.transacao_id   = t.id
                LEFT JOIN comprovantes comp ON comp.id = cc.comprovante_id
                $whereSQL
            ");
            $cntStmt->execute($params);
            $total = (int) $cntStmt->fetchColumn();

            $stmt = $db->prepare("
                SELECT t.id, t.data, t.valor, t.tipo, t.classificacao, t.observacoes,
                       COALESCE(
                           NULLIF(comp.beneficiario, ''),
                           NULLIF(comp.descricao, ''),
                           CASE WHEN cc.id IS NOT NULL THEN comp.nome_arquivo ELSE NULL END,
                           t.descricao
                       ) AS descricao,
                       t.descricao AS descricao_extrato,
                       COALESCE(NULLIF(comp.beneficiario, ''), NULLIF(comp.descricao, ''), comp.nome_arquivo) AS descricao_comprovante,
                       c.nome AS categoria, c.cor AS categoria_cor,
                       (SELECT COUNT(*) FROM conciliacoes cc2 WHERE cc2.transacao_id = t.id) AS conciliado
                FROM transacoes t
                LEFT JOIN categorias   c    ON c.id    = t.categoria_id
                LEFT JOIN conciliacoes cc   ON cc.transacao_id   = t.id
                LEFT JOIN comprovantes comp ON comp.id = cc.comprovante_id
                $whereSQL
                GROUP BY t.id
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

        // ── annualTransactions — all transactions for a year ──────────────
        case 'annualTransactions':
            $where  = ['YEAR(t.data) = :ano'];
            $params = [':ano' => $year];

            if ($search !== '') {
                $where[]           = '(t.descricao LIKE :search OR comp.beneficiario LIKE :search2 OR comp.descricao LIKE :search3)';
                $params[':search']  = '%' . $search . '%';
                $params[':search2'] = '%' . $search . '%';
                $params[':search3'] = '%' . $search . '%';
            }
            if ($tipo !== '') {
                $where[]         = 't.tipo = :tipo';
                $params[':tipo'] = $tipo;
            }

            $whereSQL = 'WHERE ' . implode(' AND ', $where);
            $offset   = ($page - 1) * $limit;

            $cntStmt = $db->prepare("
                SELECT COUNT(DISTINCT t.id)
                FROM transacoes t
                LEFT JOIN conciliacoes cc   ON cc.transacao_id   = t.id
                LEFT JOIN comprovantes comp ON comp.id = cc.comprovante_id
                $whereSQL
            ");
            $cntStmt->execute($params);
            $total = (int) $cntStmt->fetchColumn();

            $stmt = $db->prepare("
                SELECT t.id, t.data, t.valor, t.tipo, t.classificacao, t.observacoes, t.mes_referencia,
                       COALESCE(
                           NULLIF(comp.beneficiario, ''),
                           NULLIF(comp.descricao, ''),
                           CASE WHEN cc.id IS NOT NULL THEN comp.nome_arquivo ELSE NULL END,
                           t.descricao
                       ) AS descricao,
                       t.descricao AS descricao_extrato,
                       COALESCE(NULLIF(comp.beneficiario, ''), NULLIF(comp.descricao, ''), comp.nome_arquivo) AS descricao_comprovante,
                       c.nome AS categoria, c.cor AS categoria_cor,
                       (SELECT COUNT(*) FROM conciliacoes cc2 WHERE cc2.transacao_id = t.id) AS conciliado
                FROM transacoes t
                LEFT JOIN categorias   c    ON c.id    = t.categoria_id
                LEFT JOIN conciliacoes cc   ON cc.transacao_id   = t.id
                LEFT JOIN comprovantes comp ON comp.id = cc.comprovante_id
                $whereSQL
                GROUP BY t.id
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

        // ── chart — monthly bar for a year ───────────────────────────────
        case 'chart':
            $stmt = $db->prepare("
                SELECT
                    mes_referencia AS mes,
                    COALESCE(SUM(CASE WHEN tipo='credito' THEN valor END), 0) AS creditos,
                    COALESCE(SUM(CASE WHEN tipo='debito'  THEN valor END), 0) AS debitos
                FROM transacoes
                WHERE YEAR(data) = :ano AND mes_referencia IS NOT NULL
                GROUP BY mes_referencia
                ORDER BY mes_referencia
            ");
            $stmt->execute([':ano' => $year]);
            $rows = $stmt->fetchAll();

            // Also get saldo_final per month from saldos_mensais for balance line
            $smStmt = $db->prepare("
                SELECT mes_referencia, saldo_inicial, saldo_final
                FROM saldos_mensais
                WHERE mes_referencia LIKE :prefix
                ORDER BY mes_referencia
            ");
            $smStmt->execute([':prefix' => $year . '-%']);
            $smMap = [];
            foreach ($smStmt->fetchAll() as $r) {
                $smMap[$r['mes_referencia']] = $r;
            }

            $map = [];
            foreach ($rows as $r) { $map[$r['mes']] = $r; }

            $meses = $creditos = $debitos = $saldos = [];
            for ($mm = 1; $mm <= 12; $mm++) {
                $key = $year . '-' . str_pad($mm, 2, '0', STR_PAD_LEFT);
                $meses[]   = date('M', mktime(0, 0, 0, $mm, 1));
                $creditos[] = (float)($map[$key]['creditos'] ?? 0);
                $debitos[]  = (float)($map[$key]['debitos']  ?? 0);
                $saldos[]   = isset($smMap[$key]) ? (float)$smMap[$key]['saldo_final'] : null;
            }

            echo json_encode([
                'success'  => true,
                'labels'   => $meses,
                'datasets' => [
                    ['label' => 'Entradas',   'data' => $creditos, 'color' => '#28a745'],
                    ['label' => 'Saídas',     'data' => $debitos,  'color' => '#dc3545'],
                    ['label' => 'Saldo Final', 'data' => $saldos,  'color' => '#2d7dd2', 'type' => 'line'],
                ],
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ── by-category (pie chart data) ──────────────────────────────────
        case 'byCategory':
            if ($year && !$month) {
                // Annual
                $stmt = $db->prepare("
                    SELECT c.nome, c.tipo, c.cor,
                           COUNT(t.id) AS qtd,
                           COALESCE(SUM(t.valor), 0) AS total
                    FROM transacoes t
                    JOIN categorias c ON c.id = t.categoria_id
                    WHERE YEAR(t.data) = :ano
                    GROUP BY c.id
                    ORDER BY total DESC
                ");
                $stmt->execute([':ano' => $year]);
            } else {
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
            }
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
            break;

        // ── available months ─────────────────────────────────────────────
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

        // ── saldos mensais ────────────────────────────────────────────────
        case 'saldosMensais':
            $stmt = $db->prepare("
                SELECT * FROM saldos_mensais
                WHERE mes_referencia LIKE :prefix
                ORDER BY mes_referencia
            ");
            $stmt->execute([':prefix' => $year . '-%']);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ação inválida'], JSON_UNESCAPED_UNICODE);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
