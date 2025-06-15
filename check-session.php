<?php
/**
 * SMARTBIOFIT - Verificação de Sessão (AJAX)
 */

require_once 'config.php';

header('Content-Type: application/json');

$response = [
    'authenticated' => false,
    'user' => null,
    'timestamp' => time()
];

if (isset($_SESSION['user_id'])) {
    try {
        require_once 'database.php';
        $db = Database::getInstance();
        $user = $db->fetch("SELECT id, nome, email, tipo FROM usuarios WHERE id = ? AND ativo = TRUE", [$_SESSION['user_id']]);
        
        if ($user) {
            $response['authenticated'] = true;
            $response['user'] = $user;
        } else {
            // Usuário não encontrado ou inativo, limpa sessão
            session_destroy();
        }
    } catch (Exception $e) {
        logError("Erro na verificação de sessão: " . $e->getMessage());
    }
}

echo json_encode($response);
?>
