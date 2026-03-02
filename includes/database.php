<?php
/**
 * Configuração de Banco de Dados
 * Arquivo: /public_html/includes/database.php
 */

// Configurações do banco
$db_config = [
    'host' => 'localhost',
    'dbname' => 'u999392040_aemfpar',
    'username' => 'u999392040_aemfpar',
    'password' => 'R_valdrez23',
    'charset' => 'utf8mb4'
];

// Variável global $pdo
$pdo = null;

try {
    // Criar conexão PDO
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
    
    // Configurar PDO para lançar exceções em caso de erro
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Usar arrays associativos por padrão
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Desabilitar emulação de prepared statements
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch (PDOException $e) {
    // Em produção, não expor detalhes do erro
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        // Se estiver em uma API, retornar JSON
        header('Content-Type: application/json');
        die(json_encode([
            'success' => false,
            'error' => 'Erro de conexão com banco de dados'
        ]));
    } else {
        // Erro genérico para páginas web
        die('Erro de conexão com banco de dados. Por favor, tente novamente.');
    }
}

// Verificar se a conexão foi estabelecida
if (!$pdo instanceof PDO) {
    die('Erro: Conexão PDO não estabelecida');
}
?>