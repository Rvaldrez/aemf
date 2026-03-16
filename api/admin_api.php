<?php
/**
 * api/admin_api.php
 * API administrativa para gerenciamento de categorias, referências e transações.
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/database.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = Database::getInstance()->getConnection();

    switch ($action) {
        // ----- Categorias -----
        case 'getCategorias':
            $stmt = $db->query("SELECT * FROM categorias ORDER BY tipo, nome");
            echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
            break;

        case 'getCategoria':
            $id   = intval($_GET['id'] ?? 0);
            $stmt = $db->prepare("SELECT * FROM categorias WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode($stmt->fetch(), JSON_UNESCAPED_UNICODE);
            break;

        case 'saveCategoria':
            $data = array_merge($input, $_POST);
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
            $data = array_merge($input, $_POST);
            $id   = intval($data['id'] ?? $_GET['id'] ?? 0);
            $stmt = $db->prepare("
                UPDATE categorias
                SET nome = :nome, tipo = :tipo, grupo = :grupo, cor = :cor
                WHERE id = :id
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
            $id   = intval($_GET['id'] ?? $input['id'] ?? 0);
            $stmt = $db->prepare("DELETE FROM categorias WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            break;

        // ----- Referências -----
        case 'getReferencias':
            $stmt = $db->query("
                SELECT r.*, c.nome AS categoria_nome
                FROM referencias r
                LEFT JOIN categorias c ON r.categoria_id = c.id
                ORDER BY r.padrao
            ");
            echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
            break;

        case 'getReferencia':
            $id   = intval($_GET['id'] ?? 0);
            $stmt = $db->prepare("SELECT * FROM referencias WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode($stmt->fetch(), JSON_UNESCAPED_UNICODE);
            break;

        case 'saveReferencia':
            $data = array_merge($input, $_POST);
            $stmt = $db->prepare("
                INSERT INTO referencias (padrao, categoria_id, tipo_transacao, observacoes)
                VALUES (:padrao, :categoria_id, :tipo_transacao, :observacoes)
            ");
            $stmt->execute([
                ':padrao'          => $data['padrao']          ?? '',
                ':categoria_id'    => intval($data['categoria_id'] ?? 0) ?: null,
                ':tipo_transacao'  => $data['tipo_transacao']  ?? null,
                ':observacoes'     => $data['observacoes']     ?? null,
            ]);
            echo json_encode(['success' => true, 'id' => $db->lastInsertId()], JSON_UNESCAPED_UNICODE);
            break;

        case 'updateReferencia':
            $data = array_merge($input, $_POST);
            $id   = intval($data['id'] ?? $_GET['id'] ?? 0);
            $stmt = $db->prepare("
                UPDATE referencias
                SET padrao = :padrao, categoria_id = :categoria_id,
                    tipo_transacao = :tipo_transacao, observacoes = :observacoes
                WHERE id = :id
            ");
            $stmt->execute([
                ':padrao'          => $data['padrao']          ?? '',
                ':categoria_id'    => intval($data['categoria_id'] ?? 0) ?: null,
                ':tipo_transacao'  => $data['tipo_transacao']  ?? null,
                ':observacoes'     => $data['observacoes']     ?? null,
                ':id'              => $id,
            ]);
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            break;

        case 'deleteReferencia':
            $id   = intval($_GET['id'] ?? $input['id'] ?? 0);
            $stmt = $db->prepare("DELETE FROM referencias WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            break;

        // ----- Transações -----
        case 'getTransacoesSemCategoria':
            $stmt = $db->query("
                SELECT t.id, t.data, t.descricao, t.valor, t.tipo, t.conciliado, t.beneficiario,
                       t.classificacao, t.mes_referencia
                FROM transacoes t
                WHERE t.categoria_id IS NULL
                ORDER BY t.data DESC
                LIMIT 200
            ");
            echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
            break;

        case 'getTransacoes':
            $mes  = $_GET['mes'] ?? null;
            $sql  = "
                SELECT t.id, t.data, t.descricao, t.valor, t.tipo, t.conciliado, t.beneficiario,
                       t.classificacao, t.mes_referencia, c.nome AS categoria_nome
                FROM transacoes t
                LEFT JOIN categorias c ON t.categoria_id = c.id
            ";
            $params = [];
            if ($mes) {
                $sql .= " WHERE t.mes_referencia = :mes";
                $params[':mes'] = $mes;
            }
            $sql .= " ORDER BY t.data DESC LIMIT 500";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
            break;

        case 'categorizarTransacao':
            $data = array_merge($input, $_POST);
            $id   = intval($data['id'] ?? 0);
            $stmt = $db->prepare("
                UPDATE transacoes
                SET categoria_id = :cat_id, classificacao = :classif, observacoes = :obs
                WHERE id = :id
            ");
            $stmt->execute([
                ':cat_id' => intval($data['categoria_id'] ?? 0) ?: null,
                ':classif'=> $data['classificacao'] ?? null,
                ':obs'    => $data['observacoes']   ?? null,
                ':id'     => $id,
            ]);
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            break;

        case 'categorizarLote':
            $data  = array_merge($input, $_POST);
            $ids   = array_map('intval', (array) ($data['ids'] ?? []));
            $catId = intval($data['categoria_id'] ?? 0) ?: null;
            $classif = $data['classificacao'] ?? null;

            if (empty($ids)) {
                echo json_encode(['success' => false, 'error' => 'Nenhuma transação selecionada'], JSON_UNESCAPED_UNICODE);
                break;
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge([$catId, $classif], $ids);
            $stmt = $db->prepare("
                UPDATE transacoes
                SET categoria_id = ?, classificacao = ?
                WHERE id IN ($placeholders)
            ");
            $stmt->execute($params);
            echo json_encode(['success' => true, 'updated' => $stmt->rowCount()], JSON_UNESCAPED_UNICODE);
            break;

        case 'aplicarRegrasAutomaticas':
            $refs = $db->query("SELECT padrao, categoria_id FROM referencias ORDER BY confianca DESC")->fetchAll();
            $total = 0;
            $regrasAplicadas = [];

            $stmt = $db->prepare("
                UPDATE transacoes
                SET categoria_id = :cat_id
                WHERE categoria_id IS NULL AND descricao LIKE :padrao
            ");

            foreach ($refs as $ref) {
                $stmt->execute([
                    ':cat_id' => $ref['categoria_id'],
                    ':padrao' => '%' . $ref['padrao'] . '%',
                ]);
                $count = $stmt->rowCount();
                if ($count > 0) {
                    $regrasAplicadas[$ref['padrao']] = $count;
                    $total += $count;
                }
            }

            $semCategoria = (int) $db->query("SELECT COUNT(*) FROM transacoes WHERE categoria_id IS NULL")->fetchColumn();
            $total_trans  = (int) $db->query("SELECT COUNT(*) FROM transacoes")->fetchColumn();

            echo json_encode([
                'success' => true,
                'stats'   => [
                    'transacoes_analisadas'   => $total_trans,
                    'transacoes_classificadas' => $total,
                    'sem_categoria'            => $semCategoria,
                    'regras_aplicadas'         => $regrasAplicadas,
                ],
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            echo json_encode(['error' => 'Ação inválida: ' . htmlspecialchars($action)], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
