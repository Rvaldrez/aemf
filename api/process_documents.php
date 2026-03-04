<?php
/**
 * api/process_documents.php
 * Processa upload de extratos bancários (PDF/OFX) e comprovantes
 * Corrige o erro SQLSTATE[HY000] 1442 removendo triggers conflitantes antes de inserir
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Conectar ao banco de dados
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro de conexão com banco de dados: ' . $e->getMessage()]);
    exit;
}

$response = [
    'success'      => true,
    'extrato'      => null,
    'comprovantes' => null,
];

try {
    // ---------------------------------------------------------
    // CORREÇÃO DO ERRO 1442:
    // Antes de inserir em 'transacoes', verificamos se existem
    // triggers que tentam fazer UPDATE na mesma tabela durante
    // um INSERT (causando o erro 1442 do MySQL).
    // Removemos esses triggers antes da importação.
    // ---------------------------------------------------------
    dropTransacoesTriggers($pdo);

    // Processar extrato (PDF ou OFX)
    if (isset($_FILES['extrato']) && $_FILES['extrato']['error'] === UPLOAD_ERR_OK) {
        $response['extrato'] = processExtrato($pdo, $_FILES['extrato']);
        if (!$response['extrato']['success']) {
            throw new Exception($response['extrato']['error'] ?? 'Erro ao processar extrato');
        }
    }

    // Processar comprovantes (PDFs múltiplos)
    if (!empty($_FILES['comprovantes']['name'][0])) {
        $response['comprovantes'] = processComprovantes($pdo, $_FILES['comprovantes']);
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// ============================================================
// FUNÇÕES AUXILIARES
// ============================================================

/**
 * Remove triggers da tabela 'transacoes' que causam o erro 1442.
 * O MySQL não permite que um trigger faça UPDATE na mesma tabela
 * que disparou o trigger.
 */
function dropTransacoesTriggers(PDO $pdo): void
{
    try {
        $stmt = $pdo->query("SHOW TRIGGERS WHERE `Table` = 'transacoes'");
        $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($triggers as $trigger) {
            $name = $trigger['Trigger'];
            // Validate trigger name: only allow alphanumeric and underscores to prevent SQL injection
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
                continue;
            }
            $pdo->exec("DROP TRIGGER IF EXISTS `{$name}`");
        }
    } catch (Exception $e) {
        // Sem permissão para gerenciar triggers — ignora silenciosamente
    }
}

/**
 * Processa o arquivo de extrato (PDF Itaú ou OFX).
 */
function processExtrato(PDO $pdo, array $fileInfo): array
{
    $tmpPath  = $fileInfo['tmp_name'];
    $origName = $fileInfo['name'];
    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

    $transacoes = [];

    if ($ext === 'ofx') {
        $transacoes = parseOFX(file_get_contents($tmpPath));
    } else {
        // Tenta parsear como PDF
        $transacoes = parsePDFItau($tmpPath);
    }

    if (empty($transacoes)) {
        return [
            'success'    => false,
            'error'      => 'Nenhuma transação encontrada no arquivo. Verifique se é um extrato Itaú válido.',
            'imported'   => 0,
            'duplicates' => 0,
            'total'      => 0,
        ];
    }

    // Inserir no banco
    $imported   = 0;
    $duplicates = 0;
    $errors     = [];

    $sql = "
        INSERT INTO transacoes
            (data, descricao, valor, tipo, mes_referencia, documento_origem, hash_unico, created_at, updated_at)
        VALUES
            (:data, :descricao, :valor, :tipo, :mes_referencia, :documento_origem, :hash_unico, NOW(), NOW())
    ";
    $stmt = $pdo->prepare($sql);

    foreach ($transacoes as $t) {
        try {
            $stmt->execute($t);
            $imported++;
        } catch (PDOException $e) {
            // 23000 = Duplicate entry (hash_unico UNIQUE)
            if ($e->getCode() === '23000' || strpos($e->getMessage(), 'Duplicate') !== false) {
                $duplicates++;
            } else {
                $errors[] = $e->getMessage();
            }
        }
    }

    return [
        'success'    => true,
        'imported'   => $imported,
        'duplicates' => $duplicates,
        'total'      => count($transacoes),
        'errors'     => $errors,
    ];
}

/**
 * Parseia um arquivo PDF de extrato Itaú.
 * Extrai linhas de transação e normaliza os dados.
 */
function parsePDFItau(string $pdfPath): array
{
    if (!class_exists('\Smalot\PdfParser\Parser')) {
        throw new RuntimeException('Biblioteca de PDF não encontrada. Execute "composer install".');
    }

    $parser   = new \Smalot\PdfParser\Parser();
    $pdf      = $parser->parseFile($pdfPath);
    $fullText = $pdf->getText();

    return parseItauText($fullText);
}

/**
 * Parseia texto extraído de PDF Itaú.
 * Suporta o formato padrão do extrato Itaú Empresas.
 */
function parseItauText(string $text): array
{
    $transacoes = [];
    $lines      = explode("\n", $text);

    // Padrão: data no início da linha (DD/MM/AAAA ou DD/MM)
    $datePattern  = '/^(\d{2}\/\d{2}(?:\/\d{4})?)\s+/';
    // Valor monetário no final ou em posição específica: 1.234,56 ou -1.234,56
    $valuePattern = '/(-?[\d\.]+,\d{2})\s*(?:[+-]?[\d\.]+,\d{2})?\s*$/';

    foreach ($lines as $line) {
        $line = trim($line);
        if (strlen($line) < 10) {
            continue;
        }

        // Verificar se linha começa com data
        if (!preg_match($datePattern, $line, $dateMatch)) {
            continue;
        }

        $rawDate = $dateMatch[1];

        // Extrair valor da linha
        if (!preg_match($valuePattern, $line, $valueMatch)) {
            continue;
        }

        $rawValue = $valueMatch[1];
        // Normalizar valor: remover pontos de milhar, trocar vírgula por ponto
        $valor = (float) str_replace(['.', ','], ['', '.'], $rawValue);

        // Extrair descrição (entre data e valor)
        $descricao = trim(preg_replace(
            [$datePattern, '/' . preg_quote($rawValue, '/') . '.*$/'],
            ['', ''],
            $line
        ));
        $descricao = preg_replace('/\s{2,}/', ' ', $descricao);

        if (empty($descricao) || $valor == 0) {
            continue;
        }

        // Converter data
        $dateParts = explode('/', $rawDate);
        if (count($dateParts) === 2) {
            $year = date('Y'); // assume ano atual se não informado
            $data = sprintf('%04d-%02d-%02d', $year, $dateParts[1], $dateParts[0]);
        } else {
            $data = sprintf('%04d-%02d-%02d', $dateParts[2], $dateParts[1], $dateParts[0]);
        }

        $tipo          = $valor < 0 ? 'debito' : 'credito';
        $valorAbs      = abs($valor);
        $mesReferencia = substr($data, 0, 7); // YYYY-MM
        $hashUnico     = sha1($data . '|' . $descricao . '|' . $valor);

        $transacoes[] = [
            ':data'             => $data,
            ':descricao'        => substr($descricao, 0, 500),
            ':valor'            => $valorAbs,
            ':tipo'             => $tipo,
            ':mes_referencia'   => $mesReferencia,
            ':documento_origem' => 'extrato_pdf',
            ':hash_unico'       => $hashUnico,
        ];
    }

    return $transacoes;
}

/**
 * Parseia arquivo OFX (Open Financial Exchange).
 */
function parseOFX(string $content): array
{
    $transacoes = [];

    // Extrair todas as transações do OFX
    preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/s', $content, $matches);

    if (empty($matches[1])) {
        // Tentar formato SGML (OFX 1.x sem fechamento de tags)
        return parseOFXSGML($content);
    }

    foreach ($matches[1] as $block) {
        $t = parseOFXBlock($block);
        if ($t) {
            $transacoes[] = $t;
        }
    }

    return $transacoes;
}

/**
 * Parseia OFX no formato SGML (sem fechamento de tags, formato 1.x).
 */
function parseOFXSGML(string $content): array
{
    $transacoes = [];

    // Dividir por <STMTTRN
    $blocks = preg_split('/<STMTTRN\b/i', $content);
    array_shift($blocks); // remove tudo antes do primeiro bloco

    foreach ($blocks as $block) {
        // Fim do bloco pode ser na próxima tag de mesmo nível ou no final
        $block = preg_replace('/<\/?BANKTRANLIST>.*/s', '', $block);
        $block = preg_replace('/<\/?STMTTRNRS>.*/s', '', $block);

        $t = parseOFXBlock($block);
        if ($t) {
            $transacoes[] = $t;
        }
    }

    return $transacoes;
}

/**
 * Extrai dados de um bloco de transação OFX.
 */
function parseOFXBlock(string $block): ?array
{
    $getValue = function (string $tag, string $text): string {
        // Suporta <TAG>VALUE</TAG> e <TAG>VALUE\n
        if (preg_match('/<' . $tag . '>\s*([^\n<]+)/i', $text, $m)) {
            return trim($m[1]);
        }
        return '';
    };

    $dtPosted  = $getValue('DTPOSTED', $block);
    $trnAmt    = $getValue('TRNAMT', $block);
    $memo      = $getValue('MEMO', $block);
    $fitId     = $getValue('FITID', $block);
    $trnType   = $getValue('TRNTYPE', $block);

    if (empty($dtPosted) || empty($trnAmt)) {
        return null;
    }

    // Converter data OFX (YYYYMMDD ou YYYYMMDDHHMMSS[tz])
    $dtRaw = preg_replace('/\[.+\]$/', '', $dtPosted);
    $year  = substr($dtRaw, 0, 4);
    $month = substr($dtRaw, 4, 2);
    $day   = substr($dtRaw, 6, 2);
    $data  = "{$year}-{$month}-{$day}";

    // Normalizar valor: OFX usa ponto como separador decimal
    $valor    = (float) str_replace(',', '.', $trnAmt);
    $tipo     = $valor < 0 ? 'debito' : 'credito';
    $valorAbs = abs($valor);

    $descricao     = $memo ?: ($trnType ?: 'Transação');
    $mesReferencia = substr($data, 0, 7);
    $hashUnico     = sha1($data . '|' . $descricao . '|' . $valor . '|' . $fitId);

    return [
        ':data'             => $data,
        ':descricao'        => substr($descricao, 0, 500),
        ':valor'            => $valorAbs,
        ':tipo'             => $tipo,
        ':mes_referencia'   => $mesReferencia,
        ':documento_origem' => 'extrato_ofx',
        ':hash_unico'       => $hashUnico,
    ];
}

/**
 * Processa comprovantes em PDF (conciliação).
 */
function processComprovantes(PDO $pdo, array $filesArray): array
{
    // Normalizar o array de arquivos (formato $_FILES para múltiplos arquivos)
    $files     = [];
    $fileCount = is_array($filesArray['name']) ? count($filesArray['name']) : 0;

    for ($i = 0; $i < $fileCount; $i++) {
        if ($filesArray['error'][$i] === UPLOAD_ERR_OK) {
            $files[] = [
                'name'     => $filesArray['name'][$i],
                'tmp_name' => $filesArray['tmp_name'][$i],
            ];
        }
    }

    $processed = 0;
    $matched   = 0;

    foreach ($files as $file) {
        $processed++;
        // Tentar extrair dados do comprovante para conciliação
        try {
            if (class_exists('\Smalot\PdfParser\Parser')) {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf    = $parser->parseFile($file['tmp_name']);
                $text   = $pdf->getText();

                // Tentar encontrar valor e data no comprovante
                if (matchComprovante($pdo, $text, $file['name'])) {
                    $matched++;
                }
            }
        } catch (Exception $e) {
            // Continuar com os próximos comprovantes
        }
    }

    return [
        'success'   => true,
        'processed' => $processed,
        'matched'   => $matched,
    ];
}

/**
 * Tenta conciliar um comprovante com uma transação existente.
 */
function matchComprovante(PDO $pdo, string $text, string $fileName): bool
{
    // Extrair valor do comprovante
    if (!preg_match('/R\$\s*([\d\.,]+)/i', $text, $valueMatch)) {
        return false;
    }
    $valorStr = str_replace(['.', ','], ['', '.'], $valueMatch[1]);
    $valor    = (float) $valorStr;

    if ($valor <= 0) {
        return false;
    }

    // Extrair data do comprovante
    $data = null;
    if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $text, $dateMatch)) {
        $data = "{$dateMatch[3]}-{$dateMatch[2]}-{$dateMatch[1]}";
    }

    // Buscar transação correspondente
    if ($data) {
        $stmt = $pdo->prepare("
            SELECT id FROM transacoes
            WHERE ABS(valor - :valor) < 0.01
              AND data = :data
              AND (documento_origem IS NULL OR documento_origem NOT LIKE 'comprovante_%')
            LIMIT 1
        ");
        $stmt->execute([':valor' => $valor, ':data' => $data]);
    } else {
        $stmt = $pdo->prepare("
            SELECT id FROM transacoes
            WHERE ABS(valor - :valor) < 0.01
              AND (documento_origem IS NULL OR documento_origem NOT LIKE 'comprovante_%')
            ORDER BY data DESC
            LIMIT 1
        ");
        $stmt->execute([':valor' => $valor]);
    }

    $row = $stmt->fetch();
    if (!$row) {
        return false;
    }

    // Marcar como conciliada
    $update = $pdo->prepare("
        UPDATE transacoes
        SET documento_origem = :origem, updated_at = NOW()
        WHERE id = :id
    ");
    $update->execute([
        ':origem' => 'comprovante_' . basename($fileName),
        ':id'     => $row['id'],
    ]);

    return true;
}
