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
    garantirColunasTransacoes($pdo);
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
 * Garante que a tabela transacoes possui todas as colunas necessárias.
 * Adiciona automaticamente colunas ausentes — permite que um banco de dados
 * criado com um esquema mais antigo continue funcionando sem re-instalação.
 * A verificação é executada no máximo uma vez por processo PHP.
 */
function garantirColunasTransacoes(PDO $db): void {
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        $existentes = $db->query("
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = 'transacoes'
        ")->fetchAll(PDO::FETCH_COLUMN);

        $migracoes = [
            'conciliado'   => "ALTER TABLE transacoes ADD COLUMN conciliado   TINYINT(1)   DEFAULT 0",
            'beneficiario' => "ALTER TABLE transacoes ADD COLUMN beneficiario  VARCHAR(255) DEFAULT NULL",
            'observacoes'  => "ALTER TABLE transacoes ADD COLUMN observacoes   TEXT",
        ];

        foreach ($migracoes as $coluna => $sql) {
            if (!in_array($coluna, $existentes, true)) {
                try {
                    $db->exec($sql);
                } catch (PDOException $e) {
                    error_log("garantirColunasTransacoes: falha ao adicionar coluna '{$coluna}': " . $e->getMessage());
                }
            }
        }
    } catch (PDOException $e) {
        error_log('garantirColunasTransacoes: erro ao ler INFORMATION_SCHEMA: ' . $e->getMessage());
    }
}
?>