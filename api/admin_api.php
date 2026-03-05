<?php
/**
 * api/admin_api.php
 * API administrativa para gerenciamento de categorias, referências e transações
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once dirname(__DIR__) . '/includes/config.php';

// Conectar ao banco de dados
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro de conexão com banco de dados']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        // ---- CATEGORIAS ----
        case 'getCategorias':
            getCategorias($pdo);
            break;

        case 'getCategoria':
            getCategoria($pdo, (int) ($_GET['id'] ?? 0));
            break;

        case 'saveCategoria':
            saveCategoria($pdo, $_POST);
            break;

        case 'updateCategoria':
            updateCategoria($pdo, $_POST);
            break;

        case 'deleteCategoria':
            deleteCategoria($pdo, (int) ($_GET['id'] ?? $_POST['id'] ?? 0));
            break;

        // ---- REFERÊNCIAS ----
        case 'getReferencias':
            getReferencias($pdo);
            break;

        case 'getReferencia':
            getReferencia($pdo, (int) ($_GET['id'] ?? 0));
            break;

        case 'saveReferencia':
            saveReferencia($pdo, $_POST);
            break;

        case 'updateReferencia':
            updateReferencia($pdo, $_POST);
            break;

        case 'deleteReferencia':
            deleteReferencia($pdo, (int) ($_GET['id'] ?? $_POST['id'] ?? 0));
            break;

        // ---- TRANSAÇÕES ----
        case 'getTransacoesSemCategoria':
            getTransacoesSemCategoria($pdo);
            break;

        case 'getTransacoes':
            getTransacoes($pdo);
            break;

        case 'categorizarTransacao':
            categorizarTransacao($pdo, $_POST);
            break;

        case 'categorizarLote':
            categorizarLote($pdo, $_POST);
            break;

        case 'aplicarRegrasAutomaticas':
            aplicarRegrasAutomaticas($pdo);
            break;

        // ---- SALDO INICIAL ----
        case 'getSaldoInicial':
            getSaldoInicial($pdo);
            break;

        case 'saveSaldoInicial':
            saveSaldoInicial($pdo, $_POST);
            break;

        case 'deleteSaldoInicial':
            deleteSaldoInicial($pdo, (int) ($_GET['id'] ?? $_POST['id'] ?? 0));
            break;

        // ---- DESPESAS AGRUPADAS (dashboard) ----
        case 'getExpensesGrouped':
            getExpensesGrouped($pdo);
            break;

        default:
            echo json_encode(['success' => false, 'error' => "Ação desconhecida: {$action}"]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// ============================================================
// CATEGORIAS
// ============================================================

function getCategorias(PDO $pdo): void
{
    $stmt = $pdo->query("SELECT * FROM categorias ORDER BY tipo, nome");
    echo json_encode($stmt->fetchAll());
}

function getCategoria(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if ($row) {
        echo json_encode($row);
    } else {
        echo json_encode(['success' => false, 'error' => 'Categoria não encontrada']);
    }
}

function saveCategoria(PDO $pdo, array $data): void
{
    $stmt = $pdo->prepare("
        INSERT INTO categorias (nome, tipo, grupo, cor, ativo)
        VALUES (:nome, :tipo, :grupo, :cor, :ativo)
    ");
    $stmt->execute([
        ':nome'  => trim($data['nome'] ?? ''),
        ':tipo'  => $data['tipo']  ?? 'despesa_aemf',
        ':grupo' => $data['grupo'] ?? null,
        ':cor'   => $data['cor']   ?? '#17a2b8',
        ':ativo' => isset($data['ativo']) ? 1 : 0,
    ]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
}

function updateCategoria(PDO $pdo, array $data): void
{
    $stmt = $pdo->prepare("
        UPDATE categorias
        SET nome = :nome, tipo = :tipo, grupo = :grupo, cor = :cor, ativo = :ativo
        WHERE id = :id
    ");
    $stmt->execute([
        ':id'    => (int) ($data['id'] ?? 0),
        ':nome'  => trim($data['nome'] ?? ''),
        ':tipo'  => $data['tipo']  ?? 'despesa_aemf',
        ':grupo' => $data['grupo'] ?? null,
        ':cor'   => $data['cor']   ?? '#17a2b8',
        ':ativo' => isset($data['ativo']) ? 1 : 0,
    ]);
    echo json_encode(['success' => true]);
}

function deleteCategoria(PDO $pdo, int $id): void
{
    // Desassociar transações antes de excluir
    $pdo->prepare("UPDATE transacoes SET categoria_id = NULL WHERE categoria_id = :id")
        ->execute([':id' => $id]);

    $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = :id");
    $stmt->execute([':id' => $id]);
    echo json_encode(['success' => true]);
}

// ============================================================
// REFERÊNCIAS
// ============================================================

function getReferencias(PDO $pdo): void
{
    $stmt = $pdo->query("
        SELECT r.*, c.nome AS categoria_nome
        FROM referencias r
        LEFT JOIN categorias c ON r.categoria_id = c.id
        ORDER BY r.padrao
    ");
    echo json_encode($stmt->fetchAll());
}

function getReferencia(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare("SELECT * FROM referencias WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if ($row) {
        echo json_encode($row);
    } else {
        echo json_encode(['success' => false, 'error' => 'Referência não encontrada']);
    }
}

function saveReferencia(PDO $pdo, array $data): void
{
    $stmt = $pdo->prepare("
        INSERT INTO referencias (padrao, categoria_id, tipo_transacao, observacoes, confianca)
        VALUES (:padrao, :categoria_id, :tipo_transacao, :observacoes, 1.00)
    ");
    $stmt->execute([
        ':padrao'          => strtoupper(trim($data['padrao'] ?? '')),
        ':categoria_id'    => !empty($data['categoria_id']) ? (int) $data['categoria_id'] : null,
        ':tipo_transacao'  => $data['tipo_transacao']  ?? null,
        ':observacoes'     => $data['observacoes']     ?? null,
    ]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
}

function updateReferencia(PDO $pdo, array $data): void
{
    $stmt = $pdo->prepare("
        UPDATE referencias
        SET padrao = :padrao, categoria_id = :categoria_id,
            tipo_transacao = :tipo_transacao, observacoes = :observacoes
        WHERE id = :id
    ");
    $stmt->execute([
        ':id'             => (int) ($data['id'] ?? 0),
        ':padrao'         => strtoupper(trim($data['padrao'] ?? '')),
        ':categoria_id'   => !empty($data['categoria_id']) ? (int) $data['categoria_id'] : null,
        ':tipo_transacao' => $data['tipo_transacao'] ?? null,
        ':observacoes'    => $data['observacoes']    ?? null,
    ]);
    echo json_encode(['success' => true]);
}

function deleteReferencia(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare("DELETE FROM referencias WHERE id = :id");
    $stmt->execute([':id' => $id]);
    echo json_encode(['success' => true]);
}

// ============================================================
// TRANSAÇÕES
// ============================================================

function getTransacoesSemCategoria(PDO $pdo): void
{
    $stmt = $pdo->query("
        SELECT t.id, t.data, t.descricao, t.valor, t.tipo,
               COALESCE(t.documento_origem, '') AS beneficiario,
               CASE WHEN t.documento_origem LIKE 'comprovante_%' THEN 1 ELSE 0 END AS conciliado
        FROM transacoes t
        WHERE t.categoria_id IS NULL
        ORDER BY t.data DESC
        LIMIT 200
    ");
    echo json_encode($stmt->fetchAll());
}

function getTransacoes(PDO $pdo): void
{
    $mes = $_GET['mes'] ?? date('Y-m');
    $stmt = $pdo->prepare("
        SELECT t.id, t.data, t.descricao, t.valor, t.tipo, t.mes_referencia,
               c.nome AS categoria_nome, t.categoria_id
        FROM transacoes t
        LEFT JOIN categorias c ON t.categoria_id = c.id
        WHERE t.mes_referencia = :mes
        ORDER BY t.data DESC
    ");
    $stmt->execute([':mes' => $mes]);
    echo json_encode($stmt->fetchAll());
}

function categorizarTransacao(PDO $pdo, array $data): void
{
    $id        = (int) ($data['transacao_id'] ?? 0);
    $categoria = $data['categoria'] ?? '';

    if (!$id || !$categoria) {
        echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
        return;
    }

    // Aceita ID numérico ou slug de texto
    if (is_numeric($categoria)) {
        $stmt = $pdo->prepare("
            UPDATE transacoes SET categoria_id = :cat, updated_at = NOW() WHERE id = :id
        ");
        $stmt->execute([':cat' => (int) $categoria, ':id' => $id]);
    } else {
        // Buscar categoria pelo slug/nome
        $stmtCat = $pdo->prepare("SELECT id FROM categorias WHERE LOWER(REPLACE(nome, ' ', '_')) = :slug LIMIT 1");
        $stmtCat->execute([':slug' => strtolower($categoria)]);
        $cat = $stmtCat->fetch();

        if ($cat) {
            $stmt = $pdo->prepare("
                UPDATE transacoes SET categoria_id = :cat, updated_at = NOW() WHERE id = :id
            ");
            $stmt->execute([':cat' => $cat['id'], ':id' => $id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Categoria não encontrada']);
            return;
        }
    }

    echo json_encode(['success' => true]);
}

function categorizarLote(PDO $pdo, array $data): void
{
    $categoriaId = (int) ($data['categoria'] ?? 0);
    $transacoes  = json_decode($data['transacoes'] ?? '[]', true);
    $criarRegra  = ($data['criar_regra'] ?? '0') === '1';

    if (!$categoriaId || empty($transacoes)) {
        echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
        return;
    }

    // Limit batch size to prevent excessively large queries
    $transacoes  = array_slice($transacoes, 0, 500);
    $placeholders = implode(',', array_fill(0, count($transacoes), '?'));
    $stmt = $pdo->prepare("
        UPDATE transacoes SET categoria_id = ?, updated_at = NOW()
        WHERE id IN ({$placeholders})
    ");
    $params = array_merge([$categoriaId], array_map('intval', $transacoes));
    $stmt->execute($params);

    echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
}

function aplicarRegrasAutomaticas(PDO $pdo): void
{
    // Buscar todas as referências com categoria associada
    $refs = $pdo->query("
        SELECT r.padrao, r.categoria_id, r.id AS ref_id
        FROM referencias r
        WHERE r.categoria_id IS NOT NULL
        ORDER BY LENGTH(r.padrao) DESC
    ")->fetchAll();

    $totalAnalisadas    = 0;
    $totalClassificadas = 0;
    $regrasAplicadas    = [];

    // Buscar transações sem categoria
    $transacoes = $pdo->query("
        SELECT id, descricao FROM transacoes
        WHERE categoria_id IS NULL
        LIMIT 500
    ")->fetchAll();

    $totalAnalisadas = count($transacoes);

    $stmtUpdate = $pdo->prepare("
        UPDATE transacoes SET categoria_id = :cat, updated_at = NOW() WHERE id = :id
    ");
    $stmtUso = $pdo->prepare("
        UPDATE referencias SET uso_count = uso_count + 1 WHERE id = :id
    ");

    foreach ($transacoes as $t) {
        $descUpper = strtoupper($t['descricao']);
        $matched   = false;

        foreach ($refs as $ref) {
            if (strpos($descUpper, strtoupper($ref['padrao'])) !== false) {
                $stmtUpdate->execute([':cat' => $ref['categoria_id'], ':id' => $t['id']]);
                $stmtUso->execute([':id' => $ref['ref_id']]);

                $padrao = $ref['padrao'];
                $regrasAplicadas[$padrao] = ($regrasAplicadas[$padrao] ?? 0) + 1;
                $totalClassificadas++;
                $matched = true;
                break;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'stats'   => [
            'transacoes_analisadas'    => $totalAnalisadas,
            'transacoes_classificadas' => $totalClassificadas,
            'regras_aplicadas'         => $regrasAplicadas,
        ],
    ]);
}

// ============================================================
// SALDO INICIAL
// ============================================================

/**
 * Garante que a tabela saldo_inicial existe.
 */
function ensureSaldoInicialTable(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS saldo_inicial (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            mes_referencia  VARCHAR(7)     NOT NULL,
            saldo           DECIMAL(15,2)  NOT NULL DEFAULT 0,
            tipo            ENUM('manual','ledgerbal','calculado') NOT NULL DEFAULT 'manual',
            data_referencia DATE           NULL,
            observacoes     VARCHAR(255)   NULL,
            created_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_mes_tipo (mes_referencia, tipo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function getSaldoInicial(PDO $pdo): void
{
    ensureSaldoInicialTable($pdo);
    $stmt = $pdo->query("
        SELECT id, mes_referencia, saldo, tipo, data_referencia, observacoes, created_at
        FROM   saldo_inicial
        ORDER  BY mes_referencia DESC, FIELD(tipo,'manual','calculado','ledgerbal')
    ");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
}

function saveSaldoInicial(PDO $pdo, array $post): void
{
    ensureSaldoInicialTable($pdo);

    $mes    = trim($post['mes_referencia'] ?? '');
    $saldo  = (float) ($post['saldo'] ?? 0);
    $dtRef  = !empty($post['data_referencia']) ? $post['data_referencia'] : null;
    $obs    = substr($post['observacoes'] ?? '', 0, 255) ?: null;

    if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
        echo json_encode(['success' => false, 'error' => 'Mês inválido. Use formato YYYY-MM.']);
        return;
    }

    // Validate date_referencia if provided
    if ($dtRef !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dtRef)) {
        echo json_encode(['success' => false, 'error' => 'Data de referência inválida.']);
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO saldo_inicial (mes_referencia, saldo, tipo, data_referencia, observacoes)
        VALUES (:mes, :saldo, 'manual', :dt, :obs)
        ON DUPLICATE KEY UPDATE
            saldo           = VALUES(saldo),
            data_referencia = VALUES(data_referencia),
            observacoes     = VALUES(observacoes),
            updated_at      = NOW()
    ");
    $stmt->execute([':mes' => $mes, ':saldo' => $saldo, ':dt' => $dtRef, ':obs' => $obs]);
    echo json_encode(['success' => true]);
}

function deleteSaldoInicial(PDO $pdo, int $id): void
{
    ensureSaldoInicialTable($pdo);
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'ID inválido']);
        return;
    }

    // Check if the record exists and its type before deleting
    $check = $pdo->prepare("SELECT tipo FROM saldo_inicial WHERE id = :id");
    $check->execute([':id' => $id]);
    $row = $check->fetch();

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Registro não encontrado.']);
        return;
    }
    if ($row['tipo'] !== 'manual') {
        echo json_encode(['success' => false, 'error' => 'Apenas saldos do tipo "manual" podem ser excluídos. Este registro é do tipo "' . $row['tipo'] . '".']);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM saldo_inicial WHERE id = :id AND tipo = 'manual'");
    $stmt->execute([':id' => $id]);
    echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
}

// ============================================================
// DESPESAS AGRUPADAS POR CATEGORIA (para o Dashboard)
// ============================================================

function getExpensesGrouped(PDO $pdo): void
{
    $mes = $_GET['month'] ?? $_GET['mes'] ?? date('Y-m');
    if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
        $mes = date('Y-m');
    }

    // Despesas AEMF agrupadas por categoria
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

    // Despesas PF agrupadas por categoria
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

    // Sem categoria (não classificados)
    $stmtSem = $pdo->prepare("
        SELECT COUNT(*) AS qtd, SUM(valor) AS total
        FROM   transacoes
        WHERE  mes_referencia = :mes AND tipo = 'debito' AND categoria_id IS NULL
    ");
    $stmtSem->execute([':mes' => $mes]);
    $semCategoria = $stmtSem->fetch();

    $totalAemf = array_sum(array_column($despesasAemf, 'total'));
    $totalPf   = array_sum(array_column($despesasPf,   'total'));

    echo json_encode([
        'success'        => true,
        'month'          => $mes,
        'despesas_aemf'  => $despesasAemf,
        'despesas_pf'    => $despesasPf,
        'total_aemf'     => $totalAemf,
        'total_pf'       => $totalPf,
        'sem_categoria'  => $semCategoria,
    ]);
}
