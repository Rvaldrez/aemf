<?php
// dozero/api/admin.php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/database.php';

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
                ':grupo' => $data['grupo'] ?? '',
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
                ':grupo' => $data['grupo'] ?? '',
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
        // REFERÊNCIAS
        // ═══════════════════════════════════════════════════════════════════
        case 'getReferencias':
            $stmt = $db->query("
                SELECT r.*, c.nome AS categoria_nome
                FROM referencias r
                LEFT JOIN categorias c ON c.id = r.categoria_id
                ORDER BY r.padrao
            ");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
            break;

        case 'saveReferencia':
            $stmt = $db->prepare("
                INSERT INTO referencias (padrao, categoria_id, tipo_transacao, observacoes)
                VALUES (:padrao, :cat_id, :tipo, :obs)
            ");
            $stmt->execute([
                ':padrao' => $data['padrao']       ?? '',
                ':cat_id' => (int)($data['categoria_id'] ?? 0) ?: null,
                ':tipo'   => $data['tipo_transacao'] ?? null,
                ':obs'    => $data['observacoes']  ?? null,
            ]);
            echo json_encode(['success' => true, 'id' => $db->lastInsertId()], JSON_UNESCAPED_UNICODE);
            break;

        case 'updateReferencia':
            $id = (int)($data['id'] ?? 0);
            $stmt = $db->prepare("
                UPDATE referencias SET padrao=:padrao, categoria_id=:cat_id, tipo_transacao=:tipo, observacoes=:obs WHERE id=:id
            ");
            $stmt->execute([
                ':padrao' => $data['padrao']       ?? '',
                ':cat_id' => (int)($data['categoria_id'] ?? 0) ?: null,
                ':tipo'   => $data['tipo_transacao'] ?? null,
                ':obs'    => $data['observacoes']  ?? null,
                ':id'     => $id,
            ]);
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            break;

        case 'deleteReferencia':
            $id = (int)($data['id'] ?? 0);
            $db->prepare("DELETE FROM referencias WHERE id=:id")->execute([':id' => $id]);
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            break;

        // ═══════════════════════════════════════════════════════════════════
        // TRANSAÇÕES — classificação
        // ═══════════════════════════════════════════════════════════════════
        case 'getTransacoesSemCategoria':
            $stmt = $db->query("
                SELECT t.id, t.data, t.descricao, t.valor, t.tipo, t.mes_referencia
                FROM transacoes t
                WHERE t.categoria_id IS NULL
                ORDER BY t.data DESC
                LIMIT 300
            ");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
            break;

        case 'classificarTransacao':
            $id = (int)($data['id'] ?? 0);
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
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            break;

        case 'aplicarRegras':
            $refs  = $db->query("SELECT padrao, categoria_id FROM referencias ORDER BY confianca DESC")->fetchAll();
            $total = 0;
            $stmt  = $db->prepare("
                UPDATE transacoes SET categoria_id=:cat_id
                WHERE categoria_id IS NULL AND descricao LIKE :pat
            ");
            foreach ($refs as $r) {
                $stmt->execute([':cat_id' => $r['categoria_id'], ':pat' => '%' . $r['padrao'] . '%']);
                $total += $stmt->rowCount();
            }
            $sem = (int) $db->query("SELECT COUNT(*) FROM transacoes WHERE categoria_id IS NULL")->fetchColumn();
            echo json_encode(['success' => true, 'classificadas' => $total, 'sem_categoria' => $sem], JSON_UNESCAPED_UNICODE);
            break;

        case 'deleteTransacao':
            $id = (int)($data['id'] ?? 0);
            $db->prepare("DELETE FROM transacoes WHERE id=:id")->execute([':id' => $id]);
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ação inválida: ' . htmlspecialchars($action)], JSON_UNESCAPED_UNICODE);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
