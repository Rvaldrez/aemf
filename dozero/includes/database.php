<?php
// dozero/includes/database.php

if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

class Database {
    private static ?Database $instance = null;
    private PDO $conn;

    private function __construct() {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $this->conn = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(): PDO {
        return $this->conn;
    }
}

function getDB(): PDO {
    return Database::getInstance()->get();
}

function setupSchema(PDO $db): void {
    static $done = false;
    if ($done) return;
    $done = true;

    try {
        // ── categorias ─────────────────────────────────────────────────────
        $db->exec("
            CREATE TABLE IF NOT EXISTS categorias (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                nome       VARCHAR(100) NOT NULL,
                tipo       ENUM('receita','despesa_aemf','despesa_pf') NOT NULL,
                grupo      VARCHAR(50)  DEFAULT '',
                cor        VARCHAR(7)   DEFAULT '#17a2b8',
                ativo      TINYINT(1)   DEFAULT 1,
                created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $cnt = (int) $db->query("SELECT COUNT(*) FROM categorias")->fetchColumn();
        if ($cnt === 0) {
            $db->exec("
                INSERT INTO categorias (nome, tipo, grupo, cor) VALUES
                ('Aportes de Capital',           'receita',      'Receitas',      '#28a745'),
                ('Rendimentos',                  'receita',      'Receitas',      '#28a745'),
                ('Despesas com Escritório',       'despesa_aemf', 'Operacional',   '#17a2b8'),
                ('Despesas com Pessoal',          'despesa_aemf', 'Operacional',   '#17a2b8'),
                ('Contabilidade',                'despesa_aemf', 'Operacional',   '#17a2b8'),
                ('Assessoria Jurídica',           'despesa_aemf', 'Operacional',   '#dc3545'),
                ('Taxas e Despesas Financeiras',  'despesa_aemf', 'Operacional',   '#6c757d'),
                ('Impostos',                     'despesa_aemf', 'Operacional',   '#dc3545'),
                ('Energia Elétrica',             'despesa_aemf', 'Operacional',   '#ffc107'),
                ('Telefonia/Internet',            'despesa_aemf', 'Operacional',   '#17a2b8'),
                ('Aluguel/Armazenamento',         'despesa_aemf', 'Operacional',   '#6c757d'),
                ('Administração',                'despesa_aemf', 'Operacional',   '#17a2b8'),
                ('Serviços',                     'despesa_aemf', 'Operacional',   '#6c757d'),
                ('Estacionamento',               'despesa_aemf', 'Operacional',   '#6c757d'),
                ('Gastos com Veículos',           'despesa_pf',   'Pessoa Física', '#ffc107'),
                ('Cartão Corporativo Wagner SP',  'despesa_pf',   'Pessoa Física', '#fd7e14'),
                ('Cartão Corporativo Wagner MG',  'despesa_pf',   'Pessoa Física', '#dc3545'),
                ('Outras Despesas PF',            'despesa_pf',   'Pessoa Física', '#6c757d')
            ");
        }

        // ── transacoes ──────────────────────────────────────────────────────
        $db->exec("
            CREATE TABLE IF NOT EXISTS transacoes (
                id               INT AUTO_INCREMENT PRIMARY KEY,
                data             DATE         NOT NULL,
                descricao        VARCHAR(500) NOT NULL,
                valor            DECIMAL(15,2) NOT NULL,
                tipo             ENUM('credito','debito') NOT NULL,
                categoria_id     INT          DEFAULT NULL,
                classificacao    ENUM('aemf','pf','receita') DEFAULT NULL,
                mes_referencia   VARCHAR(7)   DEFAULT NULL,
                documento_origem VARCHAR(255) DEFAULT NULL,
                hash_unico       VARCHAR(64)  UNIQUE,
                beneficiario     VARCHAR(255) DEFAULT NULL,
                conciliado       TINYINT(1)   DEFAULT 0,
                observacoes      TEXT,
                created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                updated_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
                INDEX idx_data (data),
                INDEX idx_mes  (mes_referencia)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── referencias ─────────────────────────────────────────────────────
        $db->exec("
            CREATE TABLE IF NOT EXISTS referencias (
                id             INT AUTO_INCREMENT PRIMARY KEY,
                padrao         VARCHAR(255) NOT NULL,
                categoria_id   INT          DEFAULT NULL,
                tipo_transacao VARCHAR(50)  DEFAULT NULL,
                observacoes    TEXT,
                confianca      DECIMAL(3,2) DEFAULT 1.00,
                uso_count      INT          DEFAULT 0,
                created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
                INDEX idx_padrao (padrao)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $cnt = (int) $db->query("SELECT COUNT(*) FROM referencias")->fetchColumn();
        if ($cnt === 0) {
            $db->exec("
                INSERT INTO referencias (padrao, categoria_id, tipo_transacao) VALUES
                ('SYLVIA MARIA MARTORELLI',   4,  'PIX'),
                ('ACG ADMINISTRADORA',        12, 'PIX'),
                ('MAFRA SOCIEDADE',           6,  'PIX'),
                ('MAFRA ADVOGADOS',           6,  'PIX'),
                ('PERSONAL',                  13, 'BOLETO'),
                ('GOODSTORAGE',               11, 'BOLETO'),
                ('ELETROPAULO',               9,  'BOLETO'),
                ('PAVANELLO CONTABILIDADE',   5,  'BOLETO'),
                ('BRASIL ADMINISTRACAO',      12, 'BOLETO'),
                ('INFORM IMOVEIS',            11, 'BOLETO'),
                ('CERSAN PARK',               14, 'PIX'),
                ('NELSON GONSALVES',          13, 'PIX'),
                ('EMBRATEL',                  10, 'CONCESSIONARIA'),
                ('SAMM TECNOLOGIA',           13, 'BOLETO'),
                ('ROBERTO VALDREZ',           13, 'PIX'),
                ('ANTONIO E D',               1,  'TED'),
                ('DARF',                      8,  'TRIBUTO'),
                ('TAR CONTA CERTA',           7,  'TARIFA'),
                ('TAR CARTAO SERVICO',        7,  'TARIFA'),
                ('DEB AUTOR SEM PARAR',       14, 'DEBITO'),
                ('SEM PARAR',                 14, 'DEBITO'),
                ('VIVO FIXO',                 10, 'DEBITO'),
                ('REND PAGO APLIC',           2,  'RENDIMENTO'),
                ('VB-SERVICOS',               13, 'PIX'),
                ('VB SERVICOS',               13, 'PIX'),
                ('SECRETARIA FAZENDA',        8,  'PIX')
            ");
        }

    } catch (PDOException $e) {
        error_log('setupSchema error: ' . $e->getMessage());
    }
}

// ── Bootstrap ────────────────────────────────────────────────────────────────
try {
    $pdo = getDB();
    setupSchema($pdo);
} catch (PDOException $e) {
    if (!empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'error' => 'Erro de conexão com o banco de dados']));
    }
    die('Erro de conexão com o banco de dados. Verifique as configurações.');
}
