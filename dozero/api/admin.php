<?php
// dozero/api/admin.php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/utils.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$data   = array_merge($_GET, $_POST, $input);

try {
    $db = getDB();

    switch ($action) {

        // ═══════════════════════════════════════════════════════════════════
        // CATEGORIAS
        // ═══════════════════════════════════════════════════════════════════
        case 'getCategorias':
            $stmt = $db->query("SELECT * FROM categorias ORDER BY tipo, nome");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
            break;

        case 'saveCategoria':
            $stmt = $db->prepare("
                INSERT INTO categorias (nome, tipo, grupo, cor)
                VALUES (:nome, :tipo, :grupo, :cor)
            ");
            $stmt->execute([
                ':nome'  => $data['nome']  ?? '',
                ':tipo'  => $data['tipo']  ?? 'despesa_aemf',
                ':grupo' => $data['grupo'] ?? null,
                ':cor'   => $data['cor']   ?? '#17a2b8',
            ]);
            echo json_encode(['success' => true, 'id' => $db->lastInsertId()], JSON_UNESCAPED_UNICODE);
            break;

        case 'updateCategoria':
            $id = (int)($data['id'] ?? 0);
            $stmt = $db->prepare("
                UPDATE categorias SET nome=:nome, tipo=:tipo, grupo=:grupo, cor=:cor WHERE id=:id
            ");
            $stmt->execute([
                ':nome'  => $data['nome']  ?? '',
                ':tipo'  => $data['tipo']  ?? 'despesa_aemf',
                ':grupo' => $data['grupo'] ?? null,
                ':cor'   => $data['cor']   ?? '#17a2b8',
                ':id'    => $id,
            ]);
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            break;

        case 'deleteCategoria':
            $id = (int)($data['id'] ?? 0);
            $db->prepare("DELETE FROM categorias WHERE id=:id")->execute([':id' => $id]);
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            break;

        // ═══════════════════════════════════════════════════════════════════
        // REFERENCIAS_CATEGORIA
        // ═══════════════════════════════════════════════════════════════════
        case 'getReferencias':
            $stmt = $db->query("
                SELECT r.*, c.nome AS categoria_nome
                FROM referencias_categoria r
                LEFT JOIN categorias c ON c.id = r.categoria_id
                WHERE r.ativo = 1
                ORDER BY r.padrao
            ");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
            break;

        case 'saveReferencia':
            $stmt = $db->prepare("
                INSERT INTO referencias_categoria (padrao, descricao, categoria_id, tipo_transacao, observacoes)
                VALUES (:padrao, :desc, :cat_id, :tipo, :obs)
            ");
            $stmt->execute([
                ':padrao' => $data['padrao']          ?? '',
                ':desc'   => $data['descricao']       ?? null,
                ':cat_id' => (int)($data['categoria_id'] ?? 0) ?: null,
                ':tipo'   => $data['tipo_transacao']  ?? null,
                ':obs'    => $data['observacoes']     ?? null,
            ]);
            echo json_encode(['success' => true, 'id' => $db->lastInsertId()], JSON_UNESCAPED_UNICODE);
            break;

        case 'updateReferencia':
            $id = (int)($data['id'] ?? 0);
            $stmt = $db->prepare("
                UPDATE referencias_categoria
                SET padrao=:padrao, descricao=:desc, categoria_id=:cat_id, tipo_transacao=:tipo, observacoes=:obs
                WHERE id=:id
            ");
            $stmt->execute([
                ':padrao' => $data['padrao']          ?? '',
                ':desc'   => $data['descricao']       ?? null,
                ':cat_id' => (int)($data['categoria_id'] ?? 0) ?: null,
                ':tipo'   => $data['tipo_transacao']  ?? null,
                ':obs'    => $data['observacoes']     ?? null,
                ':id'     => $id,
            ]);
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            break;

        case 'deleteReferencia':
            // Soft-delete: mark as inactive
            $id = (int)($data['id'] ?? 0);
            $db->prepare("UPDATE referencias_categoria SET ativo=0 WHERE id=:id")->execute([':id' => $id]);
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            break;

        // ═══════════════════════════════════════════════════════════════════
        // TRANSAÇÕES — classificação
        // ═══════════════════════════════════════════════════════════════════
        case 'getTransacoesSemCategoria':
            $stmt = $db->query("
                SELECT t.id, t.data, t.descricao, t.valor, t.tipo, t.mes_referencia, t.classificacao
                FROM transacoes t
                WHERE t.categoria_id IS NULL
                ORDER BY t.data DESC
                LIMIT 300
            ");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
            break;

        case 'classificarTransacao':
            $id = (int)($data['id'] ?? 0);
            $newDesc = isset($data['descricao']) ? trim($data['descricao']) : null;

            // Build SET clause: always update categoria/classif/observacoes.
            // Also update descricao when the caller provides a non-empty value.
            if ($newDesc !== null && $newDesc !== '') {
                $stmt = $db->prepare("
                    UPDATE transacoes
                    SET descricao=:desc, categoria_id=:cat_id, classificacao=:classif, observacoes=:obs
                    WHERE id=:id
                ");
                $stmt->execute([
                    ':desc'    => $newDesc,
                    ':cat_id'  => (int)($data['categoria_id'] ?? 0) ?: null,
                    ':classif' => $data['classificacao'] ?? null,
                    ':obs'     => $data['observacoes']   ?? null,
                    ':id'      => $id,
                ]);

                // Upsert a referencia so future auto-classification can use this description
                $catId  = (int)($data['categoria_id'] ?? 0) ?: null;
                $tipo   = $data['tipo_transacao'] ?? null;
                $refDesc = isset($data['ref_descricao']) ? trim($data['ref_descricao']) : null;
                $refObs  = isset($data['ref_observacoes']) ? trim($data['ref_observacoes']) : null;
                if ($catId) {
                    // Check if a reference for this exact pattern already exists
                    $existsStmt = $db->prepare("SELECT id FROM referencias_categoria WHERE padrao = :padrao AND ativo = 1 LIMIT 1");
                    $existsStmt->execute([':padrao' => $newDesc]);
                    $existing = $existsStmt->fetchColumn();
                    if ($existing) {
                        $db->prepare("
                            UPDATE referencias_categoria
                            SET descricao=COALESCE(:desc, descricao),
                                categoria_id=:cat_id,
                                tipo_transacao=COALESCE(:tipo, tipo_transacao),
                                observacoes=COALESCE(:obs, observacoes)
                            WHERE id=:id
                        ")->execute([
                            ':desc'    => $refDesc ?: null,
                            ':cat_id'  => $catId,
                            ':tipo'    => $tipo ?: null,
                            ':obs'     => $refObs ?: null,
                            ':id'      => (int)$existing,
                        ]);
                    } else {
                        $db->prepare("
                            INSERT INTO referencias_categoria (padrao, descricao, categoria_id, tipo_transacao, observacoes)
                            VALUES (:padrao, :desc, :cat_id, :tipo, :obs)
                        ")->execute([
                            ':padrao'  => $newDesc,
                            ':desc'    => $refDesc ?: null,
                            ':cat_id'  => $catId,
                            ':tipo'    => $tipo ?: null,
                            ':obs'     => $refObs ?: null,
                        ]);
                    }
                }
            } else {
                $stmt = $db->prepare("
                    UPDATE transacoes
                    SET categoria_id=:cat_id, classificacao=:classif, observacoes=:obs
                    WHERE id=:id
                ");
                $stmt->execute([
                    ':cat_id'  => (int)($data['categoria_id'] ?? 0) ?: null,
                    ':classif' => $data['classificacao'] ?? null,
                    ':obs'     => $data['observacoes']   ?? null,
                    ':id'      => $id,
                ]);
            }
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            break;

        case 'aplicarRegras':
            $refs  = $db->query("
                SELECT padrao, categoria_id FROM referencias_categoria
                WHERE ativo = 1
                ORDER BY confianca DESC
            ")->fetchAll();

            $total = 0;
            $stmt  = $db->prepare("
                UPDATE transacoes SET categoria_id=:cat
                WHERE categoria_id IS NULL AND descricao LIKE :pat
            ");
            $updTs = $db->prepare("
                UPDATE referencias_categoria
                SET usos = usos + :cnt, ultima_aplicacao = NOW()
                WHERE padrao = :padrao
            ");

            foreach ($refs as $r) {
                $stmt->execute([':cat' => $r['categoria_id'], ':pat' => '%' . $r['padrao'] . '%']);
                $cnt = $stmt->rowCount();
                if ($cnt > 0) {
                    $total += $cnt;
                    $updTs->execute([':cnt' => $cnt, ':padrao' => $r['padrao']]);
                }
            }
            $sem = (int) $db->query("SELECT COUNT(*) FROM transacoes WHERE categoria_id IS NULL")->fetchColumn();
            echo json_encode(['success' => true, 'classificadas' => $total, 'sem_categoria' => $sem], JSON_UNESCAPED_UNICODE);
            break;

        case 'deleteTransacao':
            $id = (int)($data['id'] ?? 0);
            $db->prepare("DELETE FROM transacoes WHERE id=:id")->execute([':id' => $id]);
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            break;

        // ═══════════════════════════════════════════════════════════════════
        // COMPROVANTES
        // ═══════════════════════════════════════════════════════════════════
        case 'getComprovantes':
            $stmt = $db->query("
                SELECT comp.*, 
                       GROUP_CONCAT(t.descricao SEPARATOR ' | ') AS transacoes_vinculadas
                FROM comprovantes comp
                LEFT JOIN conciliacoes cc ON cc.comprovante_id = comp.id
                LEFT JOIN transacoes t    ON t.id = cc.transacao_id
                GROUP BY comp.id
                ORDER BY comp.created_at DESC
                LIMIT 100
            ");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
            break;

        // ═══════════════════════════════════════════════════════════════════
        // SALDOS MENSAIS
        // ═══════════════════════════════════════════════════════════════════
        case 'getSaldosMensais':
            $rows = $db->query("SELECT * FROM saldos_mensais ORDER BY mes_referencia")->fetchAll();
            echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
            break;

        case 'setSaldoInicial':
            $mes = trim($data['mes'] ?? '');
            $si  = (float)($data['saldo_inicial'] ?? 0);
            if (!preg_match('/^\d{4}-\d{2}$/', $mes) || (int)substr($mes, 5, 2) < 1 || (int)substr($mes, 5, 2) > 12) {
                echo json_encode(['success' => false, 'error' => 'Mês inválido (esperado YYYY-MM, ex: 2024-01)'], JSON_UNESCAPED_UNICODE);
                break;
            }
            $db->prepare("
                INSERT INTO saldos_mensais (mes_referencia, saldo_inicial, total_creditos, total_debitos, saldo_final)
                VALUES (:mes, :si, 0, 0, 0)
                ON DUPLICATE KEY UPDATE saldo_inicial = :si2, updated_at = NOW()
            ")->execute([':mes' => $mes, ':si' => $si, ':si2' => $si]);
            recalcularCascata($db);
            $rows = $db->query("SELECT * FROM saldos_mensais ORDER BY mes_referencia")->fetchAll();
            echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
            break;

        case 'recalcularSaldo':
            $mes = $data['mes'] ?? date('Y-m');
            recalcularSaldo($db, $mes);
            recalcularCascata($db);
            $row = $db->prepare("SELECT * FROM saldos_mensais WHERE mes_referencia=:mes LIMIT 1");
            $row->execute([':mes' => $mes]);
            echo json_encode(['success' => true, 'data' => $row->fetch()], JSON_UNESCAPED_UNICODE);
            break;

        case 'recalcularCascata':
            recalcularCascata($db);
            $rows = $db->query("SELECT * FROM saldos_mensais ORDER BY mes_referencia")->fetchAll();
            echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ação inválida: ' . htmlspecialchars($action)], JSON_UNESCAPED_UNICODE);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

// ── Helper ────────────────────────────────────────────────────────────────
function recalcularSaldo(PDO $db, string $mes): void {
    // Get transaction totals for this month
    $txStmt = $db->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN tipo='credito' THEN valor END), 0) AS creditos,
            COALESCE(SUM(CASE WHEN tipo='debito'  THEN valor END), 0) AS debitos
        FROM transacoes WHERE mes_referencia = :mes
    ");
    $txStmt->execute([':mes' => $mes]);
    $row = $txStmt->fetch();

    $creditos = (float)$row['creditos'];
    $debitos  = (float)$row['debitos'];

    // Read existing saldo_inicial (may have been manually set); fall back to 0
    $siStmt = $db->prepare("SELECT saldo_inicial FROM saldos_mensais WHERE mes_referencia = :mes LIMIT 1");
    $siStmt->execute([':mes' => $mes]);
    $saldoInicial = (float)($siStmt->fetchColumn() ?: 0.0);

    $db->prepare("
        INSERT INTO saldos_mensais (mes_referencia, total_creditos, total_debitos, saldo_final)
        VALUES (:mes, :cred, :deb, :saldo)
        ON DUPLICATE KEY UPDATE
            total_creditos = :cred2,
            total_debitos  = :deb2,
            saldo_final    = :saldo2,
            updated_at     = NOW()
    ")->execute([
        ':mes'    => $mes,
        ':cred'   => $creditos,
        ':deb'    => $debitos,
        ':saldo'  => $saldoInicial + $creditos - $debitos,
        ':cred2'  => $creditos,
        ':deb2'   => $debitos,
        ':saldo2' => $saldoInicial + $creditos - $debitos,
    ]);
}

