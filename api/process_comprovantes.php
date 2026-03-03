<?php
/**
 * api/process_comprovantes.php
 * Processa arquivos PDF de comprovantes e tenta conciliá-los com transações existentes.
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/database.php';

$result = [
    'success'   => false,
    'processed' => 0,
    'matched'   => 0,
    'detalhes'  => [],
    'error'     => null,
];

if (empty($_FILES['comprovantes']['name'][0])) {
    $result['error'] = 'Nenhum comprovante enviado.';
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

$vendorAutoload      = dirname(__DIR__) . '/vendor/autoload.php';
$pdfParserDisponivel = file_exists($vendorAutoload);
if ($pdfParserDisponivel) {
    require_once $vendorAutoload;
}

// Tolerância de comparação monetária (em reais)
define('RECONCILIATION_TOLERANCE', 0.02);

try {
    $db    = Database::getInstance()->getConnection();
    $files = $_FILES['comprovantes'];
    $total = count($files['name']);

    for ($i = 0; $i < $total; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
        if (!in_array(strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION)), ['pdf'])) continue;

        $result['processed']++;
        $tmpPath = $files['tmp_name'][$i];
        $nome    = $files['name'][$i];

        $texto = '';
        if ($pdfParserDisponivel) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf    = $parser->parseFile($tmpPath);
                $texto  = $pdf->getText();
            } catch (Exception $e) {
                // Continua com texto vazio
            }
        }

        $conciliado = tentarConciliar($texto, $nome, $db);
        if ($conciliado) {
            $result['matched']++;
        }

        $result['detalhes'][] = [
            'arquivo'    => $nome,
            'conciliado' => $conciliado,
            'tem_texto'  => !empty(trim($texto)),
        ];
    }

    $result['success'] = true;

} catch (Exception $e) {
    $result['error'] = $e->getMessage();
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);

// ===================================================================

function tentarConciliar(string $texto, string $nomeArquivo, PDO $db): bool {
    if (empty(trim($texto))) return false;

    // Extrair valor monetário
    $valor = null;
    if (preg_match('/R\$\s*([\d\.]+,\d{2})/u', $texto, $m)) {
        $valor = floatval(str_replace(['.', ','], ['', '.'], $m[1]));
    } elseif (preg_match('/\b(\d{1,3}(?:\.\d{3})*,\d{2})\b/', $texto, $m)) {
        $valor = floatval(str_replace(['.', ','], ['', '.'], $m[1]));
    }

    // Extrair beneficiário
    $beneficiario = null;
    if (preg_match('/(?:Favorecido|Benefici[aá]rio|Recebedor)[:\s]+([^\n]+)/iu', $texto, $m)) {
        $beneficiario = trim($m[1]);
    }

    if ($valor === null || $valor <= 0) return false;

    // Tentar encontrar transação correspondente
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

    $upd = $db->prepare("
        UPDATE transacoes
        SET conciliado   = 1,
            beneficiario = :beneficiario,
            documento_origem = CONCAT(IFNULL(documento_origem,'OFX'), ' | Comprovante:', :arquivo)
        WHERE id = :id
    ");
    $upd->execute([
        ':beneficiario' => $beneficiario ? mb_substr($beneficiario, 0, 255) : null,
        ':arquivo'      => mb_substr($nomeArquivo, 0, 100),
        ':id'           => $row['id'],
    ]);

    return true;
}
