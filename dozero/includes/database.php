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
        // ── usuarios ──────────────────────────────────────────────────────
        $db->exec("
            CREATE TABLE IF NOT EXISTS usuarios (
                id         INT          AUTO_INCREMENT PRIMARY KEY,
                username   VARCHAR(50)  NOT NULL UNIQUE,
                password   VARCHAR(255) NOT NULL,
                role       ENUM('admin','user') DEFAULT 'user',
                ativo      TINYINT(1)   DEFAULT 1,
                created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Seed default users (idempotent)
        $cnt = (int) $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        if ($cnt === 0) {
            $hashAdmin = password_hash('R_valdrez23', PASSWORD_BCRYPT);
            $hashUser  = password_hash('moraes123',   PASSWORD_BCRYPT);
            $db->prepare("
                INSERT IGNORE INTO usuarios (username, password, role) VALUES
                (:u1, :p1, 'admin'),
                (:u2, :p2, 'user')
            ")->execute([':u1' => 'admin', ':p1' => $hashAdmin, ':u2' => 'antonio', ':p2' => $hashUser]);
        }

        // Add email column to usuarios if absent (migration guard)
        $colsEmail = $db->query("SHOW COLUMNS FROM usuarios LIKE 'email'")->fetchAll();
        if (empty($colsEmail)) {
            $db->exec("ALTER TABLE usuarios ADD COLUMN email VARCHAR(180) DEFAULT NULL AFTER username");
        }

        // ── password_resets ───────────────────────────────────────────────
        $db->exec("
            CREATE TABLE IF NOT EXISTS password_resets (
                id         INT          AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT          NOT NULL,
                token      VARCHAR(64)  NOT NULL UNIQUE,
                expires_at DATETIME     NOT NULL,
                usado      TINYINT(1)   DEFAULT 0,
                created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                INDEX idx_token (token)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── categorias ────────────────────────────────────────────────────
        $db->exec("
            CREATE TABLE IF NOT EXISTS categorias (
                id         INT          AUTO_INCREMENT PRIMARY KEY,
                nome       VARCHAR(100) NOT NULL,
                tipo       ENUM('receita','despesa_aemf','despesa_pf') NOT NULL,
                grupo      VARCHAR(50)  DEFAULT NULL,
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

        // ── transacoes ───────────────────────────────────────────────────
        $db->exec("
            CREATE TABLE IF NOT EXISTS transacoes (
                id               INT          AUTO_INCREMENT PRIMARY KEY,
                data             DATE         NOT NULL,
                descricao        VARCHAR(500) NOT NULL,
                valor            DECIMAL(15,2) NOT NULL,
                tipo             ENUM('credito','debito') NOT NULL,
                categoria_id     INT          DEFAULT NULL,
                classificacao    ENUM('aemf','pf','receita') DEFAULT NULL,
                mes_referencia   VARCHAR(7)   NOT NULL,
                documento_origem VARCHAR(255) DEFAULT NULL,
                hash_unico       VARCHAR(64)  NOT NULL UNIQUE,
                observacoes      TEXT         DEFAULT NULL,
                created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                updated_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
                INDEX idx_data (data),
                INDEX idx_mes  (mes_referencia)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── comprovantes ────────────────────────────────────────────────
        $db->exec("
            CREATE TABLE IF NOT EXISTS comprovantes (
                id               INT          AUTO_INCREMENT PRIMARY KEY,
                nome_arquivo     VARCHAR(255) NOT NULL,
                descricao        VARCHAR(500) DEFAULT NULL,
                tipo_documento   VARCHAR(50)  DEFAULT NULL,
                data_documento   DATE         DEFAULT NULL,
                valor_documento  DECIMAL(12,2) DEFAULT NULL,
                hash_arquivo     VARCHAR(64)  NOT NULL,
                caminho_arquivo  VARCHAR(500) DEFAULT NULL,
                processado       TINYINT(1)   DEFAULT 0,
                created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_hash (hash_arquivo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Add descricao column to existing comprovantes table if absent
        $cols = $db->query("SHOW COLUMNS FROM comprovantes LIKE 'descricao'")->fetchAll();
        if (empty($cols)) {
            $db->exec("ALTER TABLE comprovantes ADD COLUMN descricao VARCHAR(500) DEFAULT NULL AFTER nome_arquivo");
        }

        // Add beneficiario column — stores the payee/recipient extracted from the PDF
        $colsBen = $db->query("SHOW COLUMNS FROM comprovantes LIKE 'beneficiario'")->fetchAll();
        if (empty($colsBen)) {
            $db->exec("ALTER TABLE comprovantes ADD COLUMN beneficiario VARCHAR(300) DEFAULT NULL AFTER descricao");
        }

        // ── conciliacoes ─────────────────────────────────────────────────
        $db->exec("
            CREATE TABLE IF NOT EXISTS conciliacoes (
                id             INT          AUTO_INCREMENT PRIMARY KEY,
                transacao_id   INT          NOT NULL,
                comprovante_id INT          NOT NULL,
                status         ENUM('automatica','manual','revisao') DEFAULT 'revisao',
                confianca      DECIMAL(3,2) DEFAULT 0.00,
                observacoes    TEXT         DEFAULT NULL,
                created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (transacao_id)   REFERENCES transacoes(id)    ON DELETE CASCADE,
                FOREIGN KEY (comprovante_id) REFERENCES comprovantes(id)  ON DELETE CASCADE,
                UNIQUE KEY uq_tx_comp (transacao_id, comprovante_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── referencias_categoria ────────────────────────────────────────
        $db->exec("
            CREATE TABLE IF NOT EXISTS referencias_categoria (
                id               INT          AUTO_INCREMENT PRIMARY KEY,
                padrao           VARCHAR(255) NOT NULL,
                categoria_id     INT          DEFAULT NULL,
                tipo_transacao   VARCHAR(50)  DEFAULT NULL,
                confianca        DECIMAL(3,2) DEFAULT 1.00,
                usos             INT          DEFAULT 0,
                ultima_aplicacao DATETIME     DEFAULT NULL,
                observacoes      TEXT         DEFAULT NULL,
                ativo            TINYINT(1)   DEFAULT 1,
                created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                updated_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
                INDEX idx_padrao (padrao)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $cnt = (int) $db->query("SELECT COUNT(*) FROM referencias_categoria")->fetchColumn();
        if ($cnt === 0) {
            $db->exec("
                INSERT INTO referencias_categoria (padrao, categoria_id, tipo_transacao) VALUES
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

        // Add descricao column to referencias_categoria if absent
        $colsRefDesc = $db->query("SHOW COLUMNS FROM referencias_categoria LIKE 'descricao'")->fetchAll();
        if (empty($colsRefDesc)) {
            $db->exec("ALTER TABLE referencias_categoria ADD COLUMN descricao VARCHAR(500) DEFAULT NULL AFTER padrao");
        }

        // ── saldos_mensais ───────────────────────────────────────────────
        $db->exec("
            CREATE TABLE IF NOT EXISTS saldos_mensais (
                id             INT          AUTO_INCREMENT PRIMARY KEY,
                mes_referencia VARCHAR(7)   NOT NULL,
                saldo_inicial  DECIMAL(15,2) DEFAULT 0.00,
                total_creditos DECIMAL(15,2) DEFAULT 0.00,
                total_debitos  DECIMAL(15,2) DEFAULT 0.00,
                saldo_final    DECIMAL(15,2) DEFAULT 0.00,
                created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                updated_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_mes (mes_referencia)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── saldo_inicial ─────────────────────────────────────────────────
        // Stores the global opening balance from which the entire cascade starts.
        // One row per "epoch"; the row with the earliest data_ref is the seed.
        $db->exec("
            CREATE TABLE IF NOT EXISTS saldo_inicial (
                id         INT           AUTO_INCREMENT PRIMARY KEY,
                data_ref   DATE          NOT NULL DEFAULT '2026-01-01',
                valor      DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                descricao  VARCHAR(255)  DEFAULT NULL,
                created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Add required columns to saldo_inicial if the table already existed with different structure
        $colValor  = $db->query("SHOW COLUMNS FROM saldo_inicial LIKE 'valor'")->fetchAll();
        if (empty($colValor)) {
            $db->exec("ALTER TABLE saldo_inicial ADD COLUMN valor DECIMAL(15,2) NOT NULL DEFAULT 0.00");
        }
        $colDataRef = $db->query("SHOW COLUMNS FROM saldo_inicial LIKE 'data_ref'")->fetchAll();
        if (empty($colDataRef)) {
            $db->exec("ALTER TABLE saldo_inicial ADD COLUMN data_ref DATE NOT NULL DEFAULT '2026-01-01'");
        }
        $colDesc = $db->query("SHOW COLUMNS FROM saldo_inicial LIKE 'descricao'")->fetchAll();
        if (empty($colDesc)) {
            $db->exec("ALTER TABLE saldo_inicial ADD COLUMN descricao VARCHAR(255) DEFAULT NULL");
        }

        // Seed with the known opening balance if no row has valor set yet
        $cntSI = (int) $db->query("SELECT COUNT(*) FROM saldo_inicial WHERE valor > 0")->fetchColumn();
        if ($cntSI === 0) {
            $db->exec("
                INSERT INTO saldo_inicial (data_ref, valor, descricao)
                VALUES ('2026-01-01', 109612.63, 'Posição inicial em 01/01/2026')
            ");
        }

    } catch (PDOException $e) {
        error_log('setupSchema error: ' . $e->getMessage());
    }
}

// ── Bootstrap ────────────────────────────────────────────────────────────
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
