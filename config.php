<?php
/**
 * VithaGymAI - Configurações Principais
 * Aplicativo Web de Avaliação Física Profissional
 */

// **INÍCIO DO BUFFER DE SAÍDA**
// Isso captura qualquer saída (como espaços em branco acidentais) antes que os
// cabeçalhos da sessão sejam enviados. É uma solução robusta para erros "headers already sent".
ob_start();

// Carrega variáveis de ambiente
function loadEnv($file) {
    if (!file_exists($file)) {
        throw new Exception("Arquivo .env não encontrado: $file");
    }
    
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Carrega configurações do .env
loadEnv(__DIR__ . '/.env');

// Configurações de Timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo');

// IMPORTANTE: Configurações de Sessão ANTES de qualquer output/header
if (session_status() === PHP_SESSION_NONE) {
    // Define um nome de sessão único para evitar conflitos
    session_name(strtoupper(str_replace(' ', '_', $_ENV['APP_NAME'] ?? 'VITHAGYMAI')) . '_SESS');
    
    // Configurações de sessão mais robustas
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
    
    session_start();
}

// **FIM DO BUFFER DE SAÍDA**
// O buffer pode ser limpo e desativado no final do script (geralmente no footer.php)
// com `ob_end_flush();` se necessário, mas para este caso, o PHP o fará
// automaticamente no final da execução.

// Adicionar aliases para manter compatibilidade com código antigo
if (!empty($_SESSION['user_tipo']) && !isset($_SESSION['role'])) {
    $_SESSION['role'] = $_SESSION['user_tipo'];
}

if (!empty($_SESSION['user_tipo']) && !isset($_SESSION['user_type'])) {
    $_SESSION['user_type'] = $_SESSION['user_tipo'];
}

// Configurações de Erro
/* Original error settings:
if ($_ENV[\'APP_DEBUG\'] === \'true\') {
    error_reporting(E_ALL);
    ini_set(\'display_errors\', 1);
} else {
    error_reporting(0);
    ini_set(\'display_errors\', 0);
}
*/

// Ensure errors are logged but not displayed directly, especially for API calls
error_reporting(E_ALL); // Log all errors
ini_set('display_errors', 0); // Do not display errors to the output stream
ini_set('log_errors', 1); // Ensure errors are logged
if (defined('LOGS_PATH') && is_writable(LOGS_PATH)) {
    ini_set('error_log', LOGS_PATH . '/php_errors.log'); // Specify error log file
} else {
    // Fallback if LOGS_PATH is not defined or writable yet (e.g. error early in config)
    ini_set('error_log', __DIR__ . '/php_errors.log');
}

// Configurações do Banco de Dados
define('DB_HOST', $_ENV['DB_HOST']);
define('DB_PORT', $_ENV['DB_PORT']);
define('DB_NAME', $_ENV['DB_NAME']);
define('DB_USER', $_ENV['DB_USER']);
define('DB_PASS', $_ENV['DB_PASS']);

// Criar conexão PDO global para compatibilidade
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $pdo->exec("SET time_zone = '-03:00'");
    
} catch (PDOException $e) {
    error_log("Erro de conexão PDO: " . $e->getMessage());
    if (APP_DEBUG ?? true) {
        die("Erro de conexão com banco de dados: " . $e->getMessage());
    } else {
        die("Erro de conexão com banco de dados");
    }
}

// Configurações da Aplicação
define('APP_NAME', $_ENV['APP_NAME']);

// URL dinâmica baseada no ambiente
function getAppUrl() {
    // Se estiver definido no .env, usar
    if (!empty($_ENV['APP_URL'])) {
        return $_ENV['APP_URL'];
    }
    
    // Detectar automaticamente baseado na requisição
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname($_SERVER['SCRIPT_NAME']);
    
    // Remove /pages se estiver no path
    $path = str_replace('/pages', '', $path);
    
    return $protocol . $host . $path;
}

define('APP_URL', getAppUrl());
define('APP_ENV', $_ENV['APP_ENV']);
define('APP_DEBUG', $_ENV['APP_DEBUG'] === 'true');

// Configurações de Segurança
define('JWT_SECRET', $_ENV['JWT_SECRET']);
define('SESSION_LIFETIME', (int)$_ENV['SESSION_LIFETIME']);
define('HASH_ALGO', $_ENV['HASH_ALGO']);

// Configurações de Upload
define('UPLOAD_MAX_SIZE', (int)$_ENV['UPLOAD_MAX_SIZE']);
define('UPLOAD_ALLOWED_TYPES', explode(',', $_ENV['UPLOAD_ALLOWED_TYPES']));

// Configurações de Paths
define('ROOT_PATH', __DIR__);
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('LOGS_PATH', ROOT_PATH . '/' . $_ENV['LOG_PATH']);

// Cria diretórios necessários se não existirem
$directories = [UPLOADS_PATH, LOGS_PATH, ASSETS_PATH];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Função para log de erros
function logError($message, $level = 'error') {
    $logFile = LOGS_PATH . '/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// Função para resposta JSON
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Função para sanitizar dados
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Função para validar email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Função para gerar hash de senha
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Função para verificar senha
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Função para gerar token aleatório
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// IMPORTANTE: Headers de segurança APÓS configuração de sessão
// Só enviar headers se não estivermos em linha de comando
if (php_sapi_name() !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    if (!APP_DEBUG) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Incluir classe Database para compatibilidade
require_once __DIR__ . '/database.php';
?>