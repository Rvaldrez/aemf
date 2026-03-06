<?php
// includes/config.php

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'u999392040_aemfpar');
define('DB_USER', 'u999392040_aemfpar');
define('DB_PASS', 'R_valdrez23');
define('DB_CHARSET', 'utf8mb4');

// Configurações do sistema
define('SITE_URL', 'https://seu-dominio.com');
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');
define('TEMP_PATH', dirname(__DIR__) . '/temp/');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Error reporting — erros são logados mas não exibidos em produção
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
?>