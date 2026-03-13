<?php
// analyze_structure.php
// Script para mapear toda a estrutura do projeto AEMFPAR
// Execute via SSH: php analyze_structure.php

echo "\n";
echo "=====================================\n";
echo "   ANÁLISE DA ESTRUTURA AEMFPAR     \n";
echo "=====================================\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n";
echo "=====================================\n\n";

// Detectar diretório atual
$baseDir = getcwd();
echo "📁 DIRETÓRIO BASE: $baseDir\n\n";

// Função para formatar bytes
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// 1. ESTRUTURA DE DIRETÓRIOS
echo "📂 ESTRUTURA DE DIRETÓRIOS:\n";
echo "----------------------------------------\n";

$directories = [
    '.' => 'Raiz',
    'api' => 'APIs',
    'includes' => 'Includes',
    'uploads' => 'Uploads',
    'assets' => 'Assets',
    'css' => 'CSS',
    'js' => 'JavaScript',
    'img' => 'Imagens',
    'vendor' => 'Vendor (Composer)',
    'config' => 'Configurações'
];

foreach ($directories as $dir => $desc) {
    if (is_dir($dir)) {
        $count = count(glob("$dir/*"));
        echo "✅ /$dir/ - $desc ($count itens)\n";
        
        // Listar subdiretórios importantes
        if ($dir == 'uploads') {
            $subdirs = glob("$dir/*", GLOB_ONLYDIR);
            foreach ($subdirs as $subdir) {
                $name = basename($subdir);
                $files = count(glob("$subdir/*"));
                echo "   └─ $name/ ($files arquivos)\n";
            }
        }
    } else {
        echo "❌ /$dir/ - Não encontrado\n";
    }
}

echo "\n";

// 2. ARQUIVOS PRINCIPAIS
echo "📄 ARQUIVOS PRINCIPAIS:\n";
echo "----------------------------------------\n";

$mainFiles = [
    'index.php' => 'Dashboard principal',
    'admin_categorias.php' => 'Painel administrativo',
    'upload_interface.php' => 'Interface de upload',
    'classify_manual.php' => 'Classificação manual',
    'clear_transactions.php' => 'Limpar transações',
    '.htaccess' => 'Configuração Apache',
    'composer.json' => 'Dependências PHP',
    'package.json' => 'Dependências NPM'
];

foreach ($mainFiles as $file => $desc) {
    if (file_exists($file)) {
        $size = formatBytes(filesize($file));
        $modified = date('Y-m-d H:i:s', filemtime($file));
        echo "✅ $file - $desc\n";
        echo "   Tamanho: $size | Modificado: $modified\n";
    } else {
        echo "❌ $file - Não encontrado\n";
    }
}

echo "\n";

// 3. ARQUIVOS DE API
echo "🔌 ARQUIVOS DE API:\n";
echo "----------------------------------------\n";

if (is_dir('api')) {
    $apiFiles = glob('api/*.php');
    foreach ($apiFiles as $file) {
        $name = basename($file);
        $size = formatBytes(filesize($file));
        echo "✅ api/$name ($size)\n";
    }
} else {
    echo "❌ Diretório /api/ não encontrado\n";
}

echo "\n";

// 4. CONFIGURAÇÃO DO BANCO DE DADOS
echo "🗄️ CONFIGURAÇÃO DO BANCO:\n";
echo "----------------------------------------\n";

$configFiles = [
    'includes/config.php',
    'config/config.php',
    'includes/database.php',
    '.env'
];

$configFound = false;
foreach ($configFiles as $configFile) {
    if (file_exists($configFile)) {
        echo "✅ $configFile encontrado\n";
        $configFound = true;
        
        // Tentar extrair informações do banco (sem mostrar senhas)
        $content = file_get_contents($configFile);
        
        if (preg_match('/DB_HOST.*?[\'"]([^\'"]+)/', $content, $match)) {
            echo "   Host: " . $match[1] . "\n";
        }
        if (preg_match('/DB_NAME.*?[\'"]([^\'"]+)/', $content, $match)) {
            echo "   Database: " . $match[1] . "\n";
        }
        if (preg_match('/DB_USER.*?[\'"]([^\'"]+)/', $content, $match)) {
            echo "   Usuário: " . $match[1] . "\n";
        }
        
        break;
    }
}

if (!$configFound) {
    echo "⚠️ Arquivo de configuração não encontrado\n";
}

echo "\n";

// 5. VERIFICAR BANCO DE DADOS
echo "🔍 TESTE DE CONEXÃO COM BANCO:\n";
echo "----------------------------------------\n";

if (file_exists('includes/config.php')) {
    @include_once 'includes/config.php';
    
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS
            );
            echo "✅ Conexão com banco estabelecida\n";
            
            // Listar tabelas
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "   Tabelas encontradas: " . count($tables) . "\n";
            
            foreach ($tables as $table) {
                $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                $columns = $pdo->query("SHOW COLUMNS FROM $table")->rowCount();
                echo "   - $table: $count registros, $columns colunas\n";
            }
            
        } catch (PDOException $e) {
            echo "❌ Erro na conexão: " . $e->getMessage() . "\n";
        }
    } else {
        echo "⚠️ Constantes de banco não definidas\n";
    }
} else {
    echo "⚠️ Arquivo de configuração não acessível\n";
}

echo "\n";

// 6. PERMISSÕES DE DIRETÓRIOS
echo "🔐 PERMISSÕES IMPORTANTES:\n";
echo "----------------------------------------\n";

$checkPermissions = [
    'uploads' => '755 ou 775',
    'api' => '755',
    'includes' => '755'
];

foreach ($checkPermissions as $dir => $recommended) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $writable = is_writable($dir) ? 'Sim' : 'Não';
        echo "$dir/: $perms (Gravável: $writable) - Recomendado: $recommended\n";
    }
}

echo "\n";

// 7. LISTAR TODOS OS ARQUIVOS PHP
echo "📝 TODOS OS ARQUIVOS PHP:\n";
echo "----------------------------------------\n";

$phpFiles = glob("*.php");
foreach ($phpFiles as $file) {
    $size = formatBytes(filesize($file));
    echo "- $file ($size)\n";
}

if (is_dir('api')) {
    echo "\nArquivos em /api/:\n";
    $apiPhpFiles = glob("api/*.php");
    foreach ($apiPhpFiles as $file) {
        $size = formatBytes(filesize($file));
        echo "- $file ($size)\n";
    }
}

if (is_dir('includes')) {
    echo "\nArquivos em /includes/:\n";
    $incPhpFiles = glob("includes/*.php");
    foreach ($incPhpFiles as $file) {
        $size = formatBytes(filesize($file));
        echo "- $file ($size)\n";
    }
}

echo "\n";
echo "=====================================\n";
echo "   ANÁLISE CONCLUÍDA COM SUCESSO!   \n";
echo "=====================================\n";
echo "\n";
