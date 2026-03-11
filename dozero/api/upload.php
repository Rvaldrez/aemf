<?php
// dozero/api/upload.php — importa OFX e PDFs de comprovante
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/utils.php';

const TOLERANCE = 0.02;

// Padrões de movimentação na conta de aplicação que devem ser ignorados
const PADROES_EXCLUIDOS = [
    'RES APLIC AUT MAIS',
    'APL APLIC AUT MAIS',
];

$result = ['success' => false, 'extrato' => null, 'comprovantes' => null, 'stats' => null, 'error' => null];

try {
    $db = getDB();

    // 0. Excluir lançamentos de aplicação que possam já ter sido importados
    excluirLancamentosAplicacao($db);

    // 1. OFX ─────────────────────────────────────────────────────────────────
    if (!empty($_FILES['extrato']['tmp_name'])) {
        $result['extrato'] = importarOFX($_FILES['extrato']['tmp_name'], $db);
        foreach ($result['extrato']['meses_afetados'] ?? [] as $mes) {
            recalcularSaldo($db, $mes);
        }
        recalcularCascata($db);
        // Conciliação: verify calculated balance matches OFX closing balance
        $result['extrato']['conciliacao'] = verificarConciliacao($db, $result['extrato']);
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

/**
 * Retorna true se o memo corresponde a um padrão de movimentação de aplicação.
 */
function ehLancamentoAplicacao(string $memo): bool {
    $upper = strtoupper($memo);
    foreach (PADROES_EXCLUIDOS as $padrao) {
        if (strpos($upper, $padrao) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Remove da base quaisquer transações de aplicação que já foram importadas.
 */
function excluirLancamentosAplicacao(PDO $db): void {
    foreach (PADROES_EXCLUIDOS as $padrao) {
        $db->prepare("DELETE FROM transacoes WHERE descricao LIKE :pat")
           ->execute([':pat' => '%' . $padrao . '%']);
    }
}

function importarOFX(string $tmp, PDO $db): array {
    $conteudo = file_get_contents($tmp);
    if ($conteudo === false) {
        return ['imported' => 0, 'duplicates' => 0, 'total' => 0, 'meses_afetados' => [],
                'saldo_inicial_ofx' => null, 'saldo_final_ofx' => null, 'error' => 'Leitura falhou'];
    }

    $conteudo = str_replace(["\r\n", "\r"], "\n", $conteudo);
    preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/s', $conteudo, $m);

    $transacoes  = [];
    $mesAfetados = [];

    foreach ($m[1] as $bloco) {
        $g = static fn(string $tag): string =>
            trim(preg_match("/<{$tag}>\s*([^\n<]+)/i", $bloco, $x) ? $x[1] : '');

        $fitid = $g('FITID');
        $data  = $g('DTPOSTED');
        if (!$fitid || !$data) continue;

        $memo = mb_substr($g('MEMO'), 0, 500);

        // Req 7 — ignorar movimentações de conta de aplicação
        if (ehLancamentoAplicacao($memo)) continue;

        $dataStr = substr($data, 0, 8);
        $dataFmt = substr($dataStr, 0, 4) . '-' . substr($dataStr, 4, 2) . '-' . substr($dataStr, 6, 2);
        $mesRef  = substr($dataStr, 0, 4) . '-' . substr($dataStr, 4, 2);
        $valor   = abs((float) str_replace(',', '.', $g('TRNAMT')));
        $tipo    = strtolower($g('TRNTYPE')) === 'credit' ? 'credito' : 'debito';
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

    // ── Extract OFX closing balance (LEDGERBAL) ────────────────────────────
    // LEDGERBAL in OFX = account balance at the end of the statement period.
    // We derive the opening balance as: closing - (sum_credits - sum_debits).
    $saldosOFX      = extrairSaldosOFX($conteudo);
    $saldoFinalOFX  = !empty($saldosOFX) ? end($saldosOFX)    : null;
    $saldoInicialOFX = null;

    if (!empty($saldosOFX)) {
        if (count($saldosOFX) >= 2) {
            // Multiple LEDGERBAL: first = opening, last = closing
            reset($saldosOFX);
            $saldoInicialOFX = current($saldosOFX);
        } else {
            // Single LEDGERBAL = closing; derive opening from net transactions
            $net = 0.0;
            foreach ($transacoes as $t) {
                $net += $t['tipo'] === 'credito' ? $t['valor'] : -$t['valor'];
            }
            $saldoInicialOFX = $saldoFinalOFX - $net;
        }
    }

    // Store the OFX-derived opening balance for the earliest affected month
    // so that recalcularCascata can use it as the starting point.
    // saldo_final will be recomputed by recalcularCascata; set to 0 here.
    if ($saldoInicialOFX !== null && !empty($mesAfetados)) {
        $primeiroMes = min(array_keys($mesAfetados));
        $db->prepare("
            INSERT INTO saldos_mensais (mes_referencia, saldo_inicial, total_creditos, total_debitos, saldo_final)
            VALUES (:mes, :si, 0, 0, 0)
            ON DUPLICATE KEY UPDATE saldo_inicial = :si2, updated_at = NOW()
        ")->execute([':mes' => $primeiroMes, ':si' => $saldoInicialOFX, ':si2' => $saldoInicialOFX]);
    }

    return [
        'imported'         => $imported,
        'duplicates'       => $duplicates,
        'total'            => count($transacoes),
        'meses_afetados'   => array_keys($mesAfetados),
        'saldo_inicial_ofx' => $saldoInicialOFX,
        'saldo_final_ofx'   => $saldoFinalOFX,
    ];
}

/**
 * Extrai todos os blocos <LEDGERBAL> do OFX, retornando [date => amount] ordenado por data.
 */
function extrairSaldosOFX(string $conteudo): array {
    $saldos = [];

    // Try tag-pair format first: <LEDGERBAL>...</LEDGERBAL>
    if (preg_match_all('/<LEDGERBAL>(.*?)<\/LEDGERBAL>/si', $conteudo, $blocks)) {
        foreach ($blocks[1] as $bloco) {
            $g = static fn(string $tag): string =>
                trim(preg_match("/<{$tag}>\s*([^\n<]+)/i", $bloco, $x) ? $x[1] : '');
            $balamt = $g('BALAMT');
            $dtasof = $g('DTASOF');
            if ($balamt === '') continue;
            $date = $dtasof !== '' ? substr($dtasof, 0, 8) : '';
            $key  = $date !== '' ? substr($date,0,4).'-'.substr($date,4,2).'-'.substr($date,6,2) : 'unknown';
            $saldos[$key] = (float) str_replace(',', '.', $balamt);
        }
    }

    // Fallback: SGML open-tag format (no closing tag)
    if (empty($saldos)) {
        // Find BALAMT right after LEDGERBAL section
        if (preg_match('/<LEDGERBAL>.*?<BALAMT>\s*([^\n<\[]+)/si', $conteudo, $x)) {
            $saldos['end'] = (float) str_replace(',', '.', trim($x[1]));
        }
    }

    ksort($saldos);
    return $saldos;
}

/**
 * Verifica se o saldo final calculado bate com o saldo final do extrato OFX.
 * Retorna array com status da conciliação.
 */
function verificarConciliacao(PDO $db, array $extratoResult): array {
    $saldoFinalOFX = $extratoResult['saldo_final_ofx'] ?? null;
    if ($saldoFinalOFX === null || empty($extratoResult['meses_afetados'])) {
        return ['ok' => null, 'mensagem' => 'Saldo do extrato OFX não disponível para verificação'];
    }

    $ultimoMes = max($extratoResult['meses_afetados']);
    $stmt = $db->prepare("SELECT saldo_final FROM saldos_mensais WHERE mes_referencia = :mes LIMIT 1");
    $stmt->execute([':mes' => $ultimoMes]);
    $row = $stmt->fetch();

    if (!$row) {
        return ['ok' => null, 'mensagem' => 'Saldo calculado não encontrado para o período'];
    }

    $saldoCalculado = (float) $row['saldo_final'];
    $diferenca = abs($saldoCalculado - $saldoFinalOFX);
    $ok = $diferenca <= TOLERANCE;

    return [
        'ok'                => $ok,
        'saldo_final_ofx'   => $saldoFinalOFX,
        'saldo_calculado'   => $saldoCalculado,
        'diferenca'         => round($diferenca, 2),
        'mensagem'          => $ok
            ? 'Conciliação OK: saldo calculado confere com o extrato'
            : sprintf('Divergência de R$ %.2f entre saldo calculado (%.2f) e extrato (%.2f)',
                       $diferenca, $saldoCalculado, $saldoFinalOFX),
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

        // Build a clean description from the filename for display in dashboard
        $descComp = descricaoFromFilename($nomeOriginal);

        $texto = '';
        if ($ok && $hasPdf) {
            try {
                require_once $autoload;
                $texto = (new \Smalot\PdfParser\Parser())->parseFile($dest)->getText();
                // Try to extract a richer description from the PDF text
                $extracted = extrairDescricaoPDF($texto);
                if ($extracted !== '') {
                    $descComp = $extracted;
                }
            } catch (Throwable $e) {
                error_log('PDF parse error: ' . $e->getMessage());
            }
        }

        $compId = salvarComprovante($db, $nomeOriginal, $descComp, $nomeSalvo, $hash, $dest);

        $conciliado = ($ok && $compId) ? conciliar($texto, $compId, $db) : false;
        if ($conciliado) $matched++;

        $detalhes[] = ['arquivo' => $nomeOriginal, 'salvo' => $ok, 'conciliado' => $conciliado];
    }

    return ['processed' => $processed, 'matched' => $matched, 'detalhes' => $detalhes];
}

/**
 * Constrói uma descrição amigável a partir do nome do arquivo.
 * Ex: "Comprovante_Pagamento_ELETROPAULO_jan2024.pdf" → "Comprovante Pagamento ELETROPAULO jan2024"
 */
function descricaoFromFilename(string $nome): string {
    $base = pathinfo($nome, PATHINFO_FILENAME);
    $base = preg_replace('/[_\-]+/', ' ', $base);
    $base = preg_replace('/\s+/', ' ', $base);
    return trim(mb_substr($base, 0, 200));
}

/**
 * Tenta extrair uma descrição do texto do PDF (beneficiário, empresa, etc.).
 */
function extrairDescricaoPDF(string $texto): string {
    if (trim($texto) === '') return '';

    // Try to find a payee / company name on common patterns
    $patterns = [
        '/(?:Favorecido|Benefici[aá]rio|Empresa|Destino)\s*[:\-]?\s*([A-ZÀÁÂÃÇÉÊÍÓÔÕÚ][^\n]{3,80})/iu',
        '/(?:Pagamento\s+a|Pago\s+a|Para)\s*[:\-]?\s*([A-ZÀÁÂÃÇÉÊÍÓÔÕÚ][^\n]{3,80})/iu',
    ];

    foreach ($patterns as $p) {
        if (preg_match($p, $texto, $x)) {
            $desc = trim(preg_replace('/\s+/', ' ', $x[1]));
            if (strlen($desc) >= 4) {
                return mb_substr($desc, 0, 200);
            }
        }
    }
    return '';
}

function salvarComprovante(PDO $db, string $nomeOriginal, string $descricao, string $nomeSalvo, string $hash, string $caminho): ?int {
    try {
        // Use ON DUPLICATE KEY UPDATE so that if a prior upload had no description,
        // the new (better) description is stored. COALESCE(descricao, :desc2) keeps
        // the existing description when it is already set (intentional: first write wins
        // so a good description is never overwritten by a worse one). A NULL existing
        // description is replaced by the new value.
        // LAST_INSERT_ID(id) makes lastInsertId() return the row's id on UPDATE too.
        $stmt = $db->prepare("
            INSERT INTO comprovantes (nome_arquivo, descricao, hash_arquivo, caminho_arquivo)
            VALUES (:nome, :desc, :hash, :caminho)
            ON DUPLICATE KEY UPDATE
                descricao = COALESCE(descricao, :desc2),
                id        = LAST_INSERT_ID(id)
        ");
        $stmt->execute([':nome' => $nomeOriginal, ':desc' => $descricao ?: null,
                        ':desc2' => $descricao ?: null, ':hash' => $hash, ':caminho' => $caminho]);
        $id = (int) $db->lastInsertId();
        if ($id > 0) return $id;
        // Fallback: fetch by hash (covers edge cases where LAST_INSERT_ID returns 0)
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
    // Note: :cnt must not appear twice in the same statement when emulate_prepares=false
    $updRef = $db->prepare("
        UPDATE referencias_categoria
        SET usos = usos + :cnt, ultima_aplicacao = NOW()
        WHERE padrao = :padrao
    ");

    foreach ($refs as $r) {
        $stmt->execute([':cat' => $r['categoria_id'], ':pat' => '%' . $r['padrao'] . '%']);
        $cnt = $stmt->rowCount();
        if ($cnt > 0) {
            $updRef->execute([':cnt' => $cnt, ':padrao' => $r['padrao']]);
        }
    }
}

/**
 * Recalcula um único mês (totais de crédito/débito) sem encadear meses anteriores.
 */
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

    // Preserve saldo_inicial set by cascata; only update totals here
    $db->prepare("
        INSERT INTO saldos_mensais (mes_referencia, total_creditos, total_debitos, saldo_final)
        VALUES (:mes, :c, :d, :s)
        ON DUPLICATE KEY UPDATE
            total_creditos = :c2,
            total_debitos  = :d2,
            updated_at     = NOW()
    ")->execute([':mes' => $mes, ':c' => $c, ':d' => $d, ':s' => $c - $d,
                 ':c2' => $c, ':d2' => $d]);
}
