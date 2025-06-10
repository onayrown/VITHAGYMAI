<?php
/**
 * SMARTBIOFIT - Logout do Aluno
 * Encerra a sessão do aluno
 */

session_start();

// Limpar todas as variáveis de sessão
$_SESSION = array();

// Destruir a sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirecionar para a página de login principal
header('Location: ../login.php?sucesso=logout');
exit;
?>
