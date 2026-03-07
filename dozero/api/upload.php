<?php
// dozero/api/upload.php — importa OFX e PDFs de comprovante
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/database.php';

const TOLERANCE = 0.02;

$result = ['success' => false, 'extrato' => null, 'comprovantes' => null, 'stats' => null, 'error' => null];

try {
    $db = getDB();

    // 1. OFX ─────────────────────────────────────────────────────────────────
    if (!empty($_FILES['extrato']['tmp_name'])) {
        $result['extrato'] = importarOFX($_FILES['extrato']['tmp_name'], $db);
        // Recalculate monthly totals for affected months
        foreach ($result['extrato']['meses_afetados'] ?? [] as $mes) {
            recalcularSaldo($db, $mes);
        }
    }

    // 2. PDFs ────────────────────────────────────────────────────────────────
    $result['comprovantes'] = ['processed' => 0, 'matched' => 0, 'detalhes' => []];
    if (!empty($_FILES['comprovantes']['name'][0])) {
        $result['comprovantes'] = importarComprovantes($_FILES['comprovantes'], $db);
    }

    // 3. Auto-categorizar ────────────────────────────────────────────────────
    aplicarRegras($db);

    // 4. Estatísticas ────────────────────────────────────────────────────────
    $result['stats'] = $db->query("
        SELECT
            COUNT(*)                           AS total,
            SUM(categoria_id IS NULL)          AS sem_categoria,
            (SELECT COUNT(*) FROM conciliacoes) AS conciliadas
        FROM transacoes
    ")->fetch();

    $result['success'] = true;

} catch (Throwable $e) {
    $result['error'] = $e->getMessage();
    error_log('upload.php error: ' . $e->getMessage());
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);

// ═══════════════════════════════════════════════════════════════════════════

function importarOFX(string $tmp, PDO $db): array {
    $conteudo = file_get_contents($tmp);
    if ($conteudo === false) {
        return ['imported' => 0, 'duplicates' => 0, 'total' => 0, 'meses_afetados' => [], 'error' => 'Leitura falhou'];
    }

    $conteudo = str_replace(["\r\n", "\r"], "\n", $conteudo);
    preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/s', $conteudo, $m);

    $transacoes   = [];
    $mesAfetados  = [];

    foreach ($m[1] as $bloco) {
        $g = static fn(string $tag): string =>
            trim(preg_match("/<{$tag}>\s*([^\n<]+)/i", $bloco, $x) ? $x[1] : '');

        $fitid = $g('FITID');
        $data  = $g('DTPOSTED');
        if (!$fitid || !$data) continue;

        $dataStr = substr($data, 0, 8);
        $dataFmt = substr($dataStr, 0, 4) . '-' . substr($dataStr, 4, 2) . '-' . substr($dataStr, 6, 2);
        $mesRef  = substr($dataStr, 0, 4) . '-' . substr($dataStr, 4, 2);
        $valor   = abs((float) str_replace(',', '.', $g('TRNAMT')));
        $tipo    = strtolower($g('TRNTYPE')) === 'credit' ? 'credito' : 'debito';
        $memo    = mb_substr($g('MEMO'), 0, 500);
        $hash    = md5($fitid . $dataStr . $g('TRNAMT'));

        $mesAfetados[$mesRef] = true;
        $transacoes[] = compact('dataFmt', 'memo', 'valor', 'tipo', 'mesRef', 'hash');
    }

    $imported = $duplicates = 0;
    $stmt = $db->prepare("
        INSERT IGNORE INTO transacoes
            (data, descricao, valor, tipo, mes_referencia, hash_unico, documento_origem)
        VALUES
            (:data, :desc, :valor, :tipo, :mes, :hash, 'OFX')
    ");

    foreach ($transacoes as $t) {
        $stmt->execute([
            ':data'  => $t['dataFmt'],
            ':desc'  => $t['memo'],
            ':valor' => $t['valor'],
            ':tipo'  => $t['tipo'],
            ':mes'   => $t['mesRef'],
            ':hash'  => $t['hash'],
        ]);
        $stmt->rowCount() > 0 ? $imported++ : $duplicates++;
    }

    return [
        'imported'       => $imported,
        'duplicates'     => $duplicates,
        'total'          => count($transacoes),
        'meses_afetados' => array_keys($mesAfetados),
    ];
}

function importarComprovantes(array $files, PDO $db): array {
    $dir = (defined('UPLOAD_PATH') ? UPLOAD_PATH : dirname(__DIR__) . '/uploads/') . 'comprovantes/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (!file_exists($autoload)) {
        $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    }
    $hasPdf = file_exists($autoload);

    $processed = $matched = 0;
    $detalhes  = [];

    for ($i = 0, $n = count($files['name']); $i < $n; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
        if (strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION)) !== 'pdf') continue;

        $processed++;
        $nomeOriginal = $files['name'][$i];
        $nomeSalvo    = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($nomeOriginal));
        $nomeSalvo    = uniqid() . '_' . $nomeSalvo;
        $dest         = $dir . $nomeSalvo;

        $ok   = move_uploaded_file($files['tmp_name'][$i], $dest);
        $hash = $ok ? md5_file($dest) : md5($nomeOriginal . time());

        // Store in comprovantes table
        $compId = salvarComprovante($db, $nomeOriginal, $nomeSalvo, $hash, $dest);

        $texto = '';
        if ($ok && $hasPdf) {
            try {
                require_once $autoload;
                $texto = (new \Smalot\PdfParser\Parser())->parseFile($dest)->getText();
            } catch (Throwable $e) {
                error_log('PDF parse error: ' . $e->getMessage());
            }
        }

        $conciliado = ($ok && $compId) ? conciliar($texto, $compId, $db) : false;
        if ($conciliado) $matched++;

        $detalhes[] = ['arquivo' => $nomeOriginal, 'salvo' => $ok, 'conciliado' => $conciliado];
    }

    return ['processed' => $processed, 'matched' => $matched, 'detalhes' => $detalhes];
}

function salvarComprovante(PDO $db, string $nomeOriginal, string $nomeSalvo, string $hash, string $caminho): ?int {
    try {
        $stmt = $db->prepare("
            INSERT IGNORE INTO comprovantes (nome_arquivo, hash_arquivo, caminho_arquivo)
            VALUES (:nome, :hash, :caminho)
        ");
        $stmt->execute([':nome' => $nomeOriginal, ':hash' => $hash, ':caminho' => $caminho]);
        if ($stmt->rowCount() > 0) {
            return (int) $db->lastInsertId();
        }
        // Already exists — get the id
        $sel = $db->prepare("SELECT id FROM comprovantes WHERE hash_arquivo=:hash LIMIT 1");
        $sel->execute([':hash' => $hash]);
        $id = $sel->fetchColumn();
        return $id ? (int) $id : null;
    } catch (Throwable $e) {
        error_log('salvarComprovante error: ' . $e->getMessage());
        return null;
    }
}

function conciliar(string $texto, int $compId, PDO $db): bool {
    if (trim($texto) === '') return false;

    $valor = null;
    if (preg_match('/R\$\s*([\d.]+,\d{2})/u', $texto, $x)) {
        $valor = (float) str_replace(['.', ','], ['', '.'], $x[1]);
    } elseif (preg_match('/(\d+\.\d{2})\b/', $texto, $x)) {
        $valor = (float) $x[1];
    }

    if (!$valor || $valor <= 0) return false;

    // Try to find the matching transaction
    $stmt = $db->prepare("
        SELECT t.id FROM transacoes t
        WHERE ABS(t.valor - :v) < :tol
          AND t.tipo = 'debito'
          AND NOT EXISTS (SELECT 1 FROM conciliacoes cc WHERE cc.transacao_id = t.id)
        ORDER BY t.data DESC
        LIMIT 1
    ");
    $stmt->execute([':v' => $valor, ':tol' => TOLERANCE]);
    $row = $stmt->fetch();
    if (!$row) return false;

    try {
        $db->prepare("
            INSERT IGNORE INTO conciliacoes (transacao_id, comprovante_id, status, confianca)
            VALUES (:tx, :comp, 'automatica', 0.80)
        ")->execute([':tx' => $row['id'], ':comp' => $compId]);

        // Mark comprovante as processed
        $db->prepare("UPDATE comprovantes SET processado=1 WHERE id=:id")->execute([':id' => $compId]);
        return true;
    } catch (Throwable $e) {
        error_log('conciliar insert error: ' . $e->getMessage());
        return false;
    }
}

function aplicarRegras(PDO $db): void {
    $refs = $db->query("
        SELECT padrao, categoria_id FROM referencias_categoria
        WHERE ativo = 1
        ORDER BY confianca DESC
    ")->fetchAll();

    $stmt   = $db->prepare("
        UPDATE transacoes SET categoria_id=:cat
        WHERE categoria_id IS NULL AND descricao LIKE :pat
    ");
    $updRef = $db->prepare("
        UPDATE referencias_categoria
        SET usos = usos + :cnt, ultima_aplicacao = NOW()
        WHERE padrao = :padrao AND :cnt > 0
    ");

    foreach ($refs as $r) {
        $stmt->execute([':cat' => $r['categoria_id'], ':pat' => '%' . $r['padrao'] . '%']);
        $cnt = $stmt->rowCount();
        if ($cnt > 0) {
            $updRef->execute([':cnt' => $cnt, ':padrao' => $r['padrao']]);
        }
    }
}

function recalcularSaldo(PDO $db, string $mes): void {
    $stmt = $db->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN tipo='credito' THEN valor END), 0) AS creditos,
            COALESCE(SUM(CASE WHEN tipo='debito'  THEN valor END), 0) AS debitos
        FROM transacoes WHERE mes_referencia = :mes
    ");
    $stmt->execute([':mes' => $mes]);
    $row = $stmt->fetch();

    $c = (float)$row['creditos'];
    $d = (float)$row['debitos'];

    $db->prepare("
        INSERT INTO saldos_mensais (mes_referencia, total_creditos, total_debitos, saldo_final)
        VALUES (:mes, :c, :d, :s)
        ON DUPLICATE KEY UPDATE
            total_creditos = :c2, total_debitos = :d2, saldo_final = :s2, updated_at = NOW()
    ")->execute([':mes' => $mes, ':c' => $c, ':d' => $d, ':s' => $c - $d,
                 ':c2' => $c, ':d2' => $d, ':s2' => $c - $d]);
}
