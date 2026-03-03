<?php
/**
 * api/process_documents.php
 * Processa o upload do arquivo OFX (extrato) e dos comprovantes PDF,
 * importa as transações para o banco e realiza a conciliação automática.
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/database.php';

// Tolerância de comparação monetária (em reais)
define('RECONCILIATION_TOLERANCE', 0.02);

$result = [
    'success'      => false,
    'extrato'      => null,
    'comprovantes' => null,
    'stats'        => null,
    'error'        => null,
];

try {
    $db = Database::getInstance()->getConnection();

    // ---------------------------------------------------------------
    // 1. Processar extrato OFX
    // ---------------------------------------------------------------
    if (!empty($_FILES['extrato']['tmp_name'])) {
        $extratoResult = processarOFX($_FILES['extrato']['tmp_name'], $db);
        $result['extrato'] = $extratoResult;
    }

    // ---------------------------------------------------------------
    // 2. Processar comprovantes PDF
    // ---------------------------------------------------------------
    $comprovantesResult = ['processed' => 0, 'matched' => 0, 'detalhes' => []];
    if (!empty($_FILES['comprovantes']['name'][0])) {
        $comprovantesResult = processarComprovantes($_FILES['comprovantes'], $db);
    }
    $result['comprovantes'] = $comprovantesResult;

    // ---------------------------------------------------------------
    // 3. Aplicar regras automáticas de categorização
    // ---------------------------------------------------------------
    aplicarRegrasAutomaticas($db);

    // ---------------------------------------------------------------
    // 4. Estatísticas gerais
    // ---------------------------------------------------------------
    $stmt = $db->query("
        SELECT
            COUNT(*) AS total_transacoes,
            SUM(CASE WHEN categoria_id IS NULL THEN 1 ELSE 0 END) AS sem_categoria,
            SUM(CASE WHEN conciliado = 1 THEN 1 ELSE 0 END) AS conciliadas
        FROM transacoes
    ");
    $result['stats']   = $stmt->fetch();
    $result['success'] = true;

} catch (Exception $e) {
    $result['error'] = $e->getMessage();
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);

// ===================================================================
// Funções auxiliares
// ===================================================================

/**
 * Analisa um arquivo OFX e insere as transações no banco de dados.
 */
function processarOFX(string $tmpPath, PDO $db): array {
    $conteudo = file_get_contents($tmpPath);
    if ($conteudo === false) {
        return ['imported' => 0, 'duplicates' => 0, 'total' => 0, 'error' => 'Não foi possível ler o arquivo'];
    }

    // Normalizar quebras de linha
    $conteudo = str_replace(["\r\n", "\r"], "\n", $conteudo);

    $transacoes = [];
    $blocos = [];
    preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/s', $conteudo, $blocos);

    foreach ($blocos[1] as $bloco) {
        $trans = [];

        preg_match('/<TRNTYPE>\s*([^\n<]+)/i',  $bloco, $m); $trans['tipo']    = trim($m[1] ?? '');
        preg_match('/<DTPOSTED>\s*([^\n<]+)/i',  $bloco, $m); $trans['data']    = trim($m[1] ?? '');
        preg_match('/<TRNAMT>\s*([^\n<]+)/i',    $bloco, $m); $trans['valor']   = trim($m[1] ?? '');
        preg_match('/<FITID>\s*([^\n<]+)/i',     $bloco, $m); $trans['fitid']   = trim($m[1] ?? '');
        preg_match('/<MEMO>\s*([^\n<]+)/i',      $bloco, $m); $trans['memo']    = trim($m[1] ?? '');
        preg_match('/<CHECKNUM>\s*([^\n<]+)/i',  $bloco, $m); $trans['checknum']= trim($m[1] ?? '');

        if (empty($trans['fitid']) || empty($trans['data'])) continue;

        // Converter data OFX (YYYYMMDD...) para MySQL (YYYY-MM-DD)
        $dataStr = substr($trans['data'], 0, 8);
        $dataFormatada = sprintf(
            '%s-%s-%s',
            substr($dataStr, 0, 4),
            substr($dataStr, 4, 2),
            substr($dataStr, 6, 2)
        );

        $valor   = floatval(str_replace(',', '.', $trans['valor']));
        $tipoTx  = strtolower($trans['tipo']) === 'credit' ? 'credito' : 'debito';
        $mesRef  = substr($dataStr, 0, 4) . '-' . substr($dataStr, 4, 2);
        $hashUnico = md5($trans['fitid'] . $dataStr . $trans['valor']);

        $transacoes[] = [
            'data'          => $dataFormatada,
            'descricao'     => mb_substr($trans['memo'], 0, 500),
            'valor'         => abs($valor),
            'tipo'          => $tipoTx,
            'mes_referencia'=> $mesRef,
            'hash_unico'    => $hashUnico,
            'documento_origem' => 'OFX',
        ];
    }

    $imported   = 0;
    $duplicates = 0;

    $stmt = $db->prepare("
        INSERT IGNORE INTO transacoes
            (data, descricao, valor, tipo, mes_referencia, hash_unico, documento_origem)
        VALUES
            (:data, :descricao, :valor, :tipo, :mes_referencia, :hash_unico, :documento_origem)
    ");

    foreach ($transacoes as $t) {
        $stmt->execute($t);
        if ($stmt->rowCount() > 0) {
            $imported++;
        } else {
            $duplicates++;
        }
    }

    return [
        'imported'   => $imported,
        'duplicates' => $duplicates,
        'total'      => count($transacoes),
    ];
}

/**
 * Processa os arquivos PDF de comprovantes e tenta conciliar com as transações existentes.
 */
function processarComprovantes(array $filesArray, PDO $db): array {
    $vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';
    $pdfParserDisponivel = file_exists($vendorAutoload);

    $processed = 0;
    $matched   = 0;
    $detalhes  = [];

    $total = count($filesArray['name']);

    for ($i = 0; $i < $total; $i++) {
        if ($filesArray['error'][$i] !== UPLOAD_ERR_OK) continue;
        if (!in_array(strtolower(pathinfo($filesArray['name'][$i], PATHINFO_EXTENSION)), ['pdf'])) continue;

        $processed++;
        $tmpPath = $filesArray['tmp_name'][$i];

        $textoExtraido = '';

        if ($pdfParserDisponivel) {
            try {
                require_once $vendorAutoload;
                $parser = new \Smalot\PdfParser\Parser();
                $pdf    = $parser->parseFile($tmpPath);
                $textoExtraido = $pdf->getText();
            } catch (Exception $e) {
                // Falha silenciosa; texto ficará vazio
            }
        }

        // Tentar conciliar pelo valor e data extraídos do texto do PDF
        $conciliado = conciliarComTexto($textoExtraido, $filesArray['name'][$i], $db);
        if ($conciliado) {
            $matched++;
        }

        $detalhes[] = [
            'arquivo'    => $filesArray['name'][$i],
            'conciliado' => $conciliado,
        ];
    }

    return [
        'processed' => $processed,
        'matched'   => $matched,
        'detalhes'  => $detalhes,
    ];
}

/**
 * Tenta conciliar um comprovante com uma transação existente no banco de dados.
 * Extrai valor e data do texto do PDF e busca uma transação correspondente.
 */
function conciliarComTexto(string $texto, string $nomeArquivo, PDO $db): bool {
    if (empty(trim($texto))) return false;

    // Extrair valor (padrão R$ 1.234,56 ou 1234.56)
    $valor = null;
    if (preg_match('/R\$\s*([\d\.]+,\d{2})/u', $texto, $m)) {
        $valor = floatval(str_replace(['.', ','], ['', '.'], $m[1]));
    } elseif (preg_match('/(\d+\.\d{2})\b/', $texto, $m)) {
        $valor = floatval($m[1]);
    }

    // Extrair nome do beneficiário (linha após "Favorecido" ou "Beneficiário")
    $beneficiario = null;
    if (preg_match('/(?:Favorecido|Benefici[aá]rio)[:\s]+([^\n]+)/iu', $texto, $m)) {
        $beneficiario = trim($m[1]);
    }

    if ($valor === null || $valor <= 0) return false;

    // Buscar transação com mesmo valor ainda não conciliada
    $stmt = $db->prepare("
        SELECT id FROM transacoes
        WHERE ABS(valor - :valor) < :tolerance
          AND conciliado = 0
          AND tipo = 'debito'
        ORDER BY data DESC
        LIMIT 1
    ");
    $stmt->execute([':valor' => $valor, ':tolerance' => RECONCILIATION_TOLERANCE]);
    $row = $stmt->fetch();

    if (!$row) return false;

    // Atualizar registro com dados do comprovante
    $upd = $db->prepare("
        UPDATE transacoes
        SET conciliado = 1,
            beneficiario = :beneficiario,
            documento_origem = CONCAT(IFNULL(documento_origem,''), ' | Comprovante:', :arquivo)
        WHERE id = :id
    ");
    $upd->execute([
        ':beneficiario' => $beneficiario ?: null,
        ':arquivo'      => mb_substr($nomeArquivo, 0, 100),
        ':id'           => $row['id'],
    ]);

    return true;
}

/**
 * Aplica as regras de categorização automática cadastradas na tabela referencias.
 */
function aplicarRegrasAutomaticas(PDO $db): void {
    $refs = $db->query("SELECT padrao, categoria_id FROM referencias ORDER BY confianca DESC")->fetchAll();
    if (empty($refs)) return;

    $stmt = $db->prepare("
        UPDATE transacoes
        SET categoria_id = :cat_id
        WHERE categoria_id IS NULL
          AND descricao LIKE :padrao
    ");

    foreach ($refs as $ref) {
        $stmt->execute([
            ':cat_id' => $ref['categoria_id'],
            ':padrao' => '%' . $ref['padrao'] . '%',
        ]);
    }
}
