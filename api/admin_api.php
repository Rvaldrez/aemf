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
