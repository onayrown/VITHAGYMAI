<?php
/**
 * VithaGymAI - Logout
 */

require_once 'config.php';

// Verifica se há sessão ativa
if (isset($_SESSION['user_id'])) {
    try {
        require_once 'database.php';
        $db = Database::getInstance();
        
        // Registra o logout no log
        $db->execute("INSERT INTO logs (usuario_id, acao, descricao, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)", [
            $_SESSION['user_id'],
            'logout',
            'Logout realizado',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
    } catch (Exception $e) {
        logError("Erro no logout: " . $e->getMessage());
    }
}

// Limpa todas as variáveis de sessão
$_SESSION = array();

// Destroi o cookie de sessão se existir
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroi a sessão
session_destroy();

// Resposta para requisições AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Logout realizado com sucesso']);
    exit;
}

// Redireciona para a página de login
header('Location: ' . APP_URL . '/login.php?message=logged_out');
exit;
?>
