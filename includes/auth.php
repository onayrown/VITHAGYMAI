<?php
/**
 * VithaGymAI - Script de Autenticação e Autorização
 *
 * Este script centraliza a verificação de sessão, carregamento de dados do usuário
 * e funções de controle de acesso. Deve ser incluído no início de todas as
 * páginas protegidas.
 */

// Evita o acesso direto ao arquivo
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    die('Acesso direto não permitido.');
}

// Garante que as configurações sejam carregadas uma única vez
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config.php';
}

// Garante que o manipulador de banco de dados esteja disponível uma única vez
if (!class_exists('Database')) {
    require_once __DIR__ . '/../database.php';
}

// ---- VERIFICAÇÃO DE SESSÃO ----
$currentPage = basename($_SERVER['PHP_SELF']);
$publicPages = ['login.php', 'register.php', 'reset-password.php'];

if (!in_array($currentPage, $publicPages) && !isset($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// ---- CARREGAMENTO DO USUÁRIO LOGADO ----
$user = null;
if (isset($_SESSION['user_id'])) {
    try {
        $db = Database::getInstance();
        $user = $db->fetch("SELECT * FROM usuarios WHERE id = ? AND ativo = TRUE", [$_SESSION['user_id']]);
        
        if ($user) {
            // Atualiza o último acesso do usuário a cada requisição
            $db->execute("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?", [$_SESSION['user_id']]);
        } else {
            // Se o usuário não for encontrado na DB ou estiver inativo, encerra a sessão
            session_unset();
            session_destroy();
            if (!in_array($currentPage, $publicPages)) {
                header('Location: ' . APP_URL . '/login.php?error=session_expired');
                exit;
            }
        }
    } catch (Exception $e) {
        error_log("Erro de banco de dados ao carregar usuário em auth.php: " . $e->getMessage());
        $user = null; // Garante que o usuário não seja considerado logado em caso de erro
        if (!in_array($currentPage, $publicPages)) {
            // Mostra uma página de erro amigável se o banco de dados falhar
            die('Erro crítico ao conectar com o serviço. Tente novamente mais tarde.');
        }
    }
}

// ---- FUNÇÃO DE CONTROLE DE PERMISSÃO ----
if (!function_exists('hasPermission')) {
    /**
     * Verifica se o usuário logado tem um nível de permissão requerido.
     * A hierarquia é: admin > professor > aluno.
     *
     * @param string $requiredType O tipo de perfil mínimo requerido ('admin', 'professor', 'aluno').
     * @return bool True se o usuário tiver a permissão, false caso contrário.
     */
    function hasPermission($requiredType) {
        global $user;
        if (!$user) {
            return false;
        }
        
        $hierarchy = ['admin' => 3, 'professor' => 2, 'aluno' => 1];
        $userLevel = $hierarchy[$user['tipo']] ?? 0;
        $requiredLevel = $hierarchy[$requiredType] ?? 0;
        
        return $userLevel >= $requiredLevel;
    }
} 