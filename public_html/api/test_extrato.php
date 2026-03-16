<?php
/**
 * api/test_extrato.php
 * Processa o upload de um extrato OFX (ou PDF legado) e importa as transações.
 * Retorna estatísticas de importação em JSON.
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/database.php';

$result = [
    'success'    => false,
    'imported'   => 0,
    'duplicates' => 0,
    'total'      => 0,
    'error'      => null,
    'debug'      => [],
];

// Aceitar campo "pdf" (legado) ou "extrato"
$fileKey = isset($_FILES['extrato']) ? 'extrato' : (isset($_FILES['pdf']) ? 'pdf' : null);

if ($fileKey === null || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
    $result['error'] = 'Nenhum arquivo enviado ou erro no upload.';
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

$tmpPath  = $_FILES[$fileKey]['tmp_name'];
$fileName = $_FILES[$fileKey]['name'];
$ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

try {
    $db = Database::getInstance()->getConnection();

    if ($ext === 'ofx') {
        $conteudo = file_get_contents($tmpPath);
        $parsed   = parseOFX($conteudo);
    } else {
        // Tentativa via smalot/pdfparser para PDFs legados
        $vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (!file_exists($vendorAutoload)) {
            throw new RuntimeException('Suporte a PDF não disponível. Envie um arquivo .ofx');
        }
        require_once $vendorAutoload;
        $parser  = new \Smalot\PdfParser\Parser();
        $pdf     = $parser->parseFile($tmpPath);
        $texto   = $pdf->getText();
        $parsed  = parseTextoExtrato($texto);
    }

    $result['total']  = count($parsed);
    $result['debug']  = ['arquivo' => $fileName, 'transacoes_encontradas' => count($parsed)];

    $stmt = $db->prepare("
        INSERT IGNORE INTO transacoes
            (data, descricao, valor, tipo, mes_referencia, hash_unico, documento_origem)
        VALUES
            (:data, :descricao, :valor, :tipo, :mes_referencia, :hash_unico, :documento_origem)
    ");

    foreach ($parsed as $t) {
        $stmt->execute($t);
        if ($stmt->rowCount() > 0) {
            $result['imported']++;
        } else {
            $result['duplicates']++;
        }
    }

    $result['success'] = true;

} catch (Exception $e) {
    $result['error'] = $e->getMessage();
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);

// ===================================================================

function parseOFX(string $conteudo): array {
    $conteudo   = str_replace(["\r\n", "\r"], "\n", $conteudo);
    $transacoes = [];
    $blocos     = [];

    preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/s', $conteudo, $blocos);

    foreach ($blocos[1] as $bloco) {
        preg_match('/<TRNTYPE>\s*([^\n<]+)/i', $bloco, $m); $tipo    = trim($m[1] ?? '');
        preg_match('/<DTPOSTED>\s*([^\n<]+)/i', $bloco, $m); $data    = trim($m[1] ?? '');
        preg_match('/<TRNAMT>\s*([^\n<]+)/i',   $bloco, $m); $valor   = trim($m[1] ?? '');
        preg_match('/<FITID>\s*([^\n<]+)/i',    $bloco, $m); $fitid   = trim($m[1] ?? '');
        preg_match('/<MEMO>\s*([^\n<]+)/i',     $bloco, $m); $memo    = trim($m[1] ?? '');

        if (empty($fitid) || empty($data)) continue;

        $dataStr      = substr($data, 0, 8);
        $dataFormatada = sprintf('%s-%s-%s', substr($dataStr,0,4), substr($dataStr,4,2), substr($dataStr,6,2));
        $valorFloat   = abs(floatval(str_replace(',', '.', $valor)));
        $tipoTx       = strtolower($tipo) === 'credit' ? 'credito' : 'debito';
        $mesRef       = substr($dataStr, 0, 4) . '-' . substr($dataStr, 4, 2);

        $transacoes[] = [
            'data'             => $dataFormatada,
            'descricao'        => mb_substr($memo, 0, 500),
            'valor'            => $valorFloat,
            'tipo'             => $tipoTx,
            'mes_referencia'   => $mesRef,
            'hash_unico'       => md5($fitid . $dataStr . $valor),
            'documento_origem' => 'OFX',
        ];
    }

    return $transacoes;
}

function parseTextoExtrato(string $texto): array {
    // Fallback: tenta extrair linhas com padrão data + valor
    $linhas     = explode("\n", $texto);
    $transacoes = [];

    foreach ($linhas as $linha) {
        // Padrão: DD/MM/YYYY <descrição> <valor>
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})\s+(.+?)\s+([\-\+]?\d+[,\.]\d{2})\s*$/', trim($linha), $m)) {
            $data   = $m[3] . '-' . $m[2] . '-' . $m[1];
            $desc   = trim($m[4]);
            $valor  = floatval(str_replace(['.', ','], ['', '.'], $m[5]));
            $tipo   = $valor < 0 ? 'debito' : 'credito';
            $mesRef = $m[3] . '-' . $m[2];

            $transacoes[] = [
                'data'             => $data,
                'descricao'        => mb_substr($desc, 0, 500),
                'valor'            => abs($valor),
                'tipo'             => $tipo,
                'mes_referencia'   => $mesRef,
                'hash_unico'       => md5($data . $desc . $valor),
                'documento_origem' => 'PDF',
            ];
        }
    }

    return $transacoes;
}
