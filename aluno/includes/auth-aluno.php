<?php
/**
 * SMARTBIOFIT - Autenticação de Alunos
 * Verificação de sessão para área do aluno
 */

// Verificar se há sessão ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o aluno está logado
if (!isset($_SESSION['aluno_id']) || (isset($_SESSION['user_type']) && $_SESSION['user_type'] !== 'aluno')) {
    // Redirecionar para login
    header('Location: login.php');
    exit;
}

// Verificar se a sessão não expirou (24 horas)
$session_lifetime = 24 * 60 * 60; // 24 horas em segundos
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_lifetime) {
    // Sessão expirada
    session_destroy();
    header('Location: login.php?erro=sessao_expirada');
    exit;
}

// Atualizar última atividade
$_SESSION['last_activity'] = time();

// Função para logout seguro
function logout_aluno() {
    session_start();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Verificar se o aluno ainda está ativo no banco
try {
    $stmt = $pdo->prepare("SELECT ativo FROM alunos WHERE id = ?");
    $stmt->execute([$_SESSION['aluno_id']]);
    $aluno = $stmt->fetch();
    
    if (!$aluno || !$aluno['ativo']) {
        // Aluno desativado
        session_destroy();
        header('Location: login.php?erro=conta_desativada');
        exit;
    }
} catch (Exception $e) {
    error_log("Erro verificação aluno: " . $e->getMessage());
}
?>
