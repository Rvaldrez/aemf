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
        SELECT COUNT(*) AS total,
               SUM(categoria_id IS NULL) AS sem_categoria,
               SUM(conciliado = 1)       AS conciliadas
        FROM transacoes
    ")->fetch();

    $result['success'] = true;

} catch (Throwable $e) {
    $result['error'] = $e->getMessage();
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);

// ═══════════════════════════════════════════════════════════════════════════

function importarOFX(string $tmp, PDO $db): array {
    $conteudo = file_get_contents($tmp);
    if ($conteudo === false) return ['imported' => 0, 'duplicates' => 0, 'total' => 0, 'error' => 'Leitura falhou'];

    $conteudo = str_replace(["\r\n", "\r"], "\n", $conteudo);
    preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/s', $conteudo, $m);

    $transacoes = [];
    foreach ($m[1] as $bloco) {
        $g = fn(string $tag) => trim(preg_match("/<{$tag}>\s*([^\n<]+)/i", $bloco, $x) ? $x[1] : '');

        $fitid = $g('FITID');
        $data  = $g('DTPOSTED');
        if (!$fitid || !$data) continue;

        $dataStr = substr($data, 0, 8);
        $dataFmt = substr($dataStr,0,4) . '-' . substr($dataStr,4,2) . '-' . substr($dataStr,6,2);
        $mesRef  = substr($dataStr,0,4) . '-' . substr($dataStr,4,2);
        $valor   = abs((float) str_replace(',', '.', $g('TRNAMT')));
        $tipo    = strtolower($g('TRNTYPE')) === 'credit' ? 'credito' : 'debito';
        $memo    = mb_substr($g('MEMO'), 0, 500);
        $hash    = md5($fitid . $dataStr . $g('TRNAMT'));

        $transacoes[] = compact('dataFmt','memo','valor','tipo','mesRef','hash');
    }

    $imported = $duplicates = 0;
    $stmt = $db->prepare("
        INSERT IGNORE INTO transacoes (data, descricao, valor, tipo, mes_referencia, hash_unico, documento_origem)
        VALUES (:data, :desc, :valor, :tipo, :mes, :hash, 'OFX')
    ");
    foreach ($transacoes as $t) {
        $stmt->execute([':data'=>$t['dataFmt'],':desc'=>$t['memo'],':valor'=>$t['valor'],
                        ':tipo'=>$t['tipo'],':mes'=>$t['mesRef'],':hash'=>$t['hash']]);
        $stmt->rowCount() > 0 ? $imported++ : $duplicates++;
    }

    return ['imported' => $imported, 'duplicates' => $duplicates, 'total' => count($transacoes)];
}

function importarComprovantes(array $files, PDO $db): array {
    $dir = UPLOAD_PATH . 'comprovantes/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    $hasPdf   = file_exists($autoload);

    $processed = $matched = 0;
    $detalhes  = [];

    for ($i = 0, $n = count($files['name']); $i < $n; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
        if (strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION)) !== 'pdf') continue;

        $processed++;
        $nome  = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($files['name'][$i]));
        $dest  = $dir . uniqid() . '_' . $nome;
        $ok    = move_uploaded_file($files['tmp_name'][$i], $dest);

        $texto = '';
        if ($ok && $hasPdf) {
            try {
                require_once $autoload;
                $texto = (new \Smalot\PdfParser\Parser())->parseFile($dest)->getText();
            } catch (Throwable $e) { /* silently ignore */ }
        }

        $conciliado = $ok ? conciliar($texto, basename($dest), $db) : false;
        if ($conciliado) $matched++;

        $detalhes[] = ['arquivo' => $files['name'][$i], 'salvo' => $ok, 'conciliado' => $conciliado];
    }

    return ['processed' => $processed, 'matched' => $matched, 'detalhes' => $detalhes];
}

function conciliar(string $texto, string $arquivo, PDO $db): bool {
    if (trim($texto) === '') return false;

    $valor = null;
    if (preg_match('/R\$\s*([\d.]+,\d{2})/u', $texto, $m))
        $valor = (float) str_replace(['.', ','], ['', '.'], $m[1]);
    elseif (preg_match('/(\d+\.\d{2})\b/', $texto, $m))
        $valor = (float) $m[1];

    if (!$valor || $valor <= 0) return false;

    $beneficiario = null;
    if (preg_match('/(?:Favorecido|Benefici[aá]rio)[:\s]+([^\n]+)/iu', $texto, $m))
        $beneficiario = trim($m[1]);

    $stmt = $db->prepare("
        SELECT id FROM transacoes
        WHERE ABS(valor - :v) < :tol AND conciliado=0 AND tipo='debito'
        ORDER BY data DESC LIMIT 1
    ");
    $stmt->execute([':v' => $valor, ':tol' => TOLERANCE]);
    $row = $stmt->fetch();
    if (!$row) return false;

    $db->prepare("
        UPDATE transacoes
        SET conciliado=1, beneficiario=:ben,
            documento_origem=CONCAT(IFNULL(documento_origem,''), ' | Comp:', :arq)
        WHERE id=:id
    ")->execute([':ben' => $beneficiario, ':arq' => mb_substr($arquivo,0,100), ':id' => $row['id']]);

    return true;
}

function aplicarRegras(PDO $db): void {
    $refs = $db->query("SELECT padrao, categoria_id FROM referencias ORDER BY confianca DESC")->fetchAll();
    $stmt = $db->prepare("UPDATE transacoes SET categoria_id=:cat WHERE categoria_id IS NULL AND descricao LIKE :pat");
    foreach ($refs as $r) {
        $stmt->execute([':cat' => $r['categoria_id'], ':pat' => '%' . $r['padrao'] . '%']);
    }
}
