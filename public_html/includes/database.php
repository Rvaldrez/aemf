<?php
/**
 * Configuração de Banco de Dados
 * Arquivo: /public_html/includes/database.php
 */

// Garantir que as constantes de configuração estejam disponíveis
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

/**
 * Classe Singleton para gerenciamento da conexão com o banco de dados
 */
class Database {
    private static $instance = null;
    private $connection = null;

    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $this->connection = new PDO($dsn, DB_USER, DB_PASS);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}

// Variável global $pdo para compatibilidade com arquivos que usam $pdo diretamente
$pdo = null;

try {
    $pdo = Database::getInstance()->getConnection();
    garantirEsquemaBD($pdo);
} catch (PDOException $e) {
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        header('Content-Type: application/json');
        die(json_encode([
            'success' => false,
            'error' => 'Erro de conexão com banco de dados'
        ]));
    } else {
        die('Erro de conexão com banco de dados. Por favor, tente novamente.');
    }
}

/**
 * Garante que todas as tabelas e colunas necessárias existem no banco de dados.
 * Cria tabelas ausentes (com dados iniciais) e adiciona colunas que estejam
 * faltando em tabelas já existentes — permite que um banco criado com um
 * esquema mais antigo continue funcionando sem re-instalação.
 * A verificação é executada no máximo uma vez por processo PHP.
 */
function garantirEsquemaBD(PDO $db): void {
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        // ------------------------------------------------------------------
        // 1. Descobrir quais tabelas já existem
        // ------------------------------------------------------------------
        $tabelasExistentes = $db->query("
            SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME IN ('categorias','transacoes','referencias')
        ")->fetchAll(PDO::FETCH_COLUMN);

        // ------------------------------------------------------------------
        // 2. Criar tabela categorias (deve vir antes de transacoes/referencias)
        //    por causa das foreign keys
        // ------------------------------------------------------------------
        if (!in_array('categorias', $tabelasExistentes, true)) {
            $db->exec("
                CREATE TABLE categorias (
                    id         INT AUTO_INCREMENT PRIMARY KEY,
                    nome       VARCHAR(100) NOT NULL,
                    tipo       ENUM('receita','despesa_aemf','despesa_pf') NOT NULL,
                    grupo      VARCHAR(50),
                    cor        VARCHAR(7) DEFAULT '#17a2b8',
                    ativo      BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            // Inserir categorias padrão
            $db->exec("
                INSERT INTO categorias (nome, tipo, grupo, cor) VALUES
                ('Aportes de Capital',            'receita',      'Receitas',       '#28a745'),
                ('Rendimentos',                   'receita',      'Receitas',       '#28a745'),
                ('Despesas com Escritório',        'despesa_aemf', 'Operacional',    '#17a2b8'),
                ('Despesas com Pessoal',           'despesa_aemf', 'Operacional',    '#17a2b8'),
                ('Contabilidade',                 'despesa_aemf', 'Operacional',    '#17a2b8'),
                ('Assessoria Jurídica',            'despesa_aemf', 'Operacional',    '#dc3545'),
                ('Taxas e Despesas Financeiras',   'despesa_aemf', 'Operacional',    '#6c757d'),
                ('Impostos',                      'despesa_aemf', 'Operacional',    '#dc3545'),
                ('Energia Elétrica',              'despesa_aemf', 'Operacional',    '#ffc107'),
                ('Telefonia/Internet',             'despesa_aemf', 'Operacional',    '#17a2b8'),
                ('Aluguel/Armazenamento',          'despesa_aemf', 'Operacional',    '#6c757d'),
                ('Administração',                 'despesa_aemf', 'Operacional',    '#17a2b8'),
                ('Serviços',                      'despesa_aemf', 'Operacional',    '#6c757d'),
                ('Estacionamento',                'despesa_aemf', 'Operacional',    '#6c757d'),
                ('Gastos com Veículos',            'despesa_pf',   'Pessoa Física',  '#ffc107'),
                ('Cartão Corporativo Wagner SP',   'despesa_pf',   'Pessoa Física',  '#fd7e14'),
                ('Cartão Corporativo Wagner MG',   'despesa_pf',   'Pessoa Física',  '#dc3545'),
                ('Outras Despesas PF',             'despesa_pf',   'Pessoa Física',  '#6c757d')
            ");
        }

        // ------------------------------------------------------------------
        // 3. Criar tabela transacoes se ausente
        // ------------------------------------------------------------------
        if (!in_array('transacoes', $tabelasExistentes, true)) {
            $db->exec("
                CREATE TABLE transacoes (
                    id               INT AUTO_INCREMENT PRIMARY KEY,
                    data             DATE NOT NULL,
                    descricao        VARCHAR(500) NOT NULL,
                    valor            DECIMAL(15,2) NOT NULL,
                    tipo             ENUM('credito','debito') NOT NULL,
                    categoria_id     INT,
                    classificacao    ENUM('aemf','pf','receita') DEFAULT NULL,
                    mes_referencia   VARCHAR(7),
                    documento_origem VARCHAR(255),
                    hash_unico       VARCHAR(64) UNIQUE,
                    beneficiario     VARCHAR(255) DEFAULT NULL,
                    conciliado       TINYINT(1) DEFAULT 0,
                    observacoes      TEXT,
                    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
                    INDEX idx_data (data),
                    INDEX idx_mes  (mes_referencia),
                    INDEX idx_hash (hash_unico)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } else {
            // Tabela já existe — garantir colunas adicionadas posteriormente
            $colunasExistentes = $db->query("
                SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME   = 'transacoes'
            ")->fetchAll(PDO::FETCH_COLUMN);

            $migracoes = [
                'beneficiario' => "ALTER TABLE transacoes ADD COLUMN beneficiario VARCHAR(255) DEFAULT NULL",
                'conciliado'   => "ALTER TABLE transacoes ADD COLUMN conciliado   TINYINT(1)   DEFAULT 0",
                'observacoes'  => "ALTER TABLE transacoes ADD COLUMN observacoes  TEXT",
            ];

            foreach ($migracoes as $coluna => $sql) {
                if (!in_array($coluna, $colunasExistentes, true)) {
                    try {
                        $db->exec($sql);
                    } catch (PDOException $e) {
                        error_log("garantirEsquemaBD: falha ao adicionar coluna 'transacoes.{$coluna}': " . $e->getMessage());
                    }
                }
            }
        }

        // ------------------------------------------------------------------
        // 4. Criar tabela referencias se ausente
        // ------------------------------------------------------------------
        if (!in_array('referencias', $tabelasExistentes, true)) {
            $db->exec("
                CREATE TABLE referencias (
                    id              INT AUTO_INCREMENT PRIMARY KEY,
                    padrao          VARCHAR(255) NOT NULL,
                    categoria_id    INT,
                    tipo_transacao  VARCHAR(50),
                    observacoes     TEXT,
                    confianca       DECIMAL(3,2) DEFAULT 1.00,
                    uso_count       INT DEFAULT 0,
                    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
                    INDEX idx_padrao (padrao)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            // Inserir referências padrão
            $db->exec("
                INSERT INTO referencias (padrao, categoria_id, tipo_transacao) VALUES
                ('SYLVIA MARIA MARTORELLI',    4,  'PIX'),
                ('ACG ADMINISTRADORA',         12, 'PIX'),
                ('MAFRA SOCIEDADE',            6,  'PIX'),
                ('MAFRA ADVOGADOS',            6,  'PIX'),
                ('PERSONAL',                   13, 'BOLETO'),
                ('GOODSTORAGE',                11, 'BOLETO'),
                ('ELETROPAULO',                9,  'BOLETO'),
                ('PAVANELLO CONTABILIDADE',    5,  'BOLETO'),
                ('BRASIL ADMINISTRACAO',       12, 'BOLETO'),
                ('INFORM IMOVEIS',             11, 'BOLETO'),
                ('CERSAN PARK',                14, 'PIX'),
                ('NELSON GONSALVES',           13, 'PIX'),
                ('EMBRATEL',                   10, 'CONCESSIONARIA'),
                ('SAMM TECNOLOGIA',            13, 'BOLETO'),
                ('ROBERTO VALDREZ',            13, 'PIX'),
                ('TED 237.1233.ANTONIO E D',   1,  'TED'),
                ('ANTONIO E D',                1,  'TED'),
                ('DARF',                       8,  'TRIBUTO'),
                ('TAR CONTA CERTA',            7,  'TARIFA'),
                ('TAR CARTAO SERVICO',         7,  'TARIFA'),
                ('DEB AUTOR SEM PARAR',        14, 'DEBITO'),
                ('SEM PARAR',                  14, 'DEBITO'),
                ('VIVO FIXO',                  10, 'DEBITO'),
                ('SISPAG PIX QR-CODE',         13, 'PIX'),
                ('REND PAGO APLIC',            2,  'RENDIMENTO'),
                ('VB-SERVICOS',                13, 'PIX'),
                ('VB SERVICOS',                13, 'PIX'),
                ('CEF MATRIZ',                 7,  'PIX'),
                ('SECRETARIA FAZENDA',         8,  'PIX')
            ");
        }

    } catch (PDOException $e) {
        error_log('garantirEsquemaBD: erro na migração do esquema: ' . $e->getMessage());
    }
}
?>