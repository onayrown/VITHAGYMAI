<?php
/**
 * SMARTBIOFIT - Página de Recuperação de Senha
 */

require_once 'config.php';
require_once 'database.php';

// Se já estiver logado, redireciona para dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$error = '';
$success = '';
$step = 'email'; // email, token, success

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'])) {
        // Etapa 1: Solicitar reset
        $email = sanitizeInput($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error = 'Por favor, digite seu email.';
        } elseif (!validateEmail($email)) {
            $error = 'Email inválido.';
        } else {
            try {
                $db = Database::getInstance();
                $user = $db->fetch("SELECT id, nome FROM usuarios WHERE email = ? AND ativo = TRUE", [$email]);
                
                if ($user) {
                    // Gerar token de reset
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Salvar token no banco
                    $db->execute("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = ?, expires_at = ?", [
                        $user['id'], $token, $expires, $token, $expires
                    ]);
                    
                    // Em um sistema real, enviaria email
                    // Por ora, vamos simular mostrando o token
                    $_SESSION['reset_token'] = $token;
                    $_SESSION['reset_email'] = $email;
                    $step = 'token';
                    $success = 'Um código de recuperação foi enviado para seu email. Por favor, verifique sua caixa de entrada.';
                } else {
                    $error = 'Email não encontrado em nosso sistema.';
                }
            } catch (Exception $e) {
                logError("Erro no reset de senha: " . $e->getMessage());
                $error = 'Erro interno. Tente novamente.';
            }
        }
    } elseif (isset($_POST['token']) && isset($_POST['new_password'])) {
        // Etapa 2: Validar token e alterar senha
        $token = $_POST['token'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (empty($token) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'Por favor, preencha todos os campos.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'As senhas não coincidem.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'A senha deve ter pelo menos 6 caracteres.';
        } else {
            try {
                $db = Database::getInstance();
                $reset = $db->fetch("SELECT pr.*, u.email FROM password_resets pr JOIN usuarios u ON pr.user_id = u.id WHERE pr.token = ? AND pr.expires_at > NOW()", [$token]);
                
                if ($reset) {
                    // Atualizar senha
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $db->execute("UPDATE usuarios SET senha = ? WHERE id = ?", [$hashedPassword, $reset['user_id']]);
                    
                    // Remover token usado
                    $db->execute("DELETE FROM password_resets WHERE token = ?", [$token]);
                    
                    $step = 'success';
                    $success = 'Senha alterada com sucesso! Você já pode fazer login.';
                } else {
                    $error = 'Token inválido ou expirado. Solicite um novo código.';
                }
            } catch (Exception $e) {
                logError("Erro ao alterar senha: " . $e->getMessage());
                $error = 'Erro interno. Tente novamente.';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container" style="max-width: 500px; margin-top: 5vh;">
    <div class="card">
        <div class="card-body">
            <!-- Logo centralizada -->
            <div class="text-center mb-4">
                <img src="<?php echo APP_URL; ?>/assets/images/logo-smartbiofit.png" alt="SMARTBIOFIT" style="height: 60px;">
                <h1 class="h3 mt-3 text-primary">Recuperar Senha</h1>
                <p class="text-muted">
                    <?php if ($step === 'email'): ?>
                        Digite seu email para receber um código de recuperação
                    <?php elseif ($step === 'token'): ?>
                        Digite o código recebido e sua nova senha
                    <?php else: ?>
                        Senha recuperada com sucesso!
                    <?php endif; ?>
                </p>
            </div>
            
            <!-- Alertas -->
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($step === 'email'): ?>
                <!-- Formulário para solicitar reset -->
                <form method="POST" action="<?php echo APP_URL; ?>/reset-password.php">
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>                        <input 
                            type="email" 
                            class="form-control text-gray-900" 
                            id="email" 
                            name="email" 
                            placeholder="seu@email.com"
                            required
                            autofocus
                        >
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        Enviar Código de Recuperação
                    </button>
                </form>
                
            <?php elseif ($step === 'token'): ?>
                <!-- Formulário para nova senha -->
                <form method="POST" action="<?php echo APP_URL; ?>/reset-password.php">
                    <div class="form-group">
                        <label for="token" class="form-label">Código de Recuperação</label>                        <input 
                            type="text" 
                            class="form-control text-gray-900" 
                            id="token" 
                            name="token" 
                            placeholder="Digite o código recebido"
                            value="<?php echo htmlspecialchars($_SESSION['reset_token'] ?? ''); ?>"
                            required
                        >
                        <small class="form-text text-muted">
                            Código enviado para: <?php echo htmlspecialchars($_SESSION['reset_email'] ?? ''); ?>
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password" class="form-label">Nova Senha</label>                        <input 
                            type="password" 
                            class="form-control text-gray-900" 
                            id="new_password" 
                            name="new_password" 
                            placeholder="Digite sua nova senha"
                            required
                            minlength="6"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>                        <input 
                            type="password" 
                            class="form-control text-gray-900" 
                            id="confirm_password" 
                            name="confirm_password" 
                            placeholder="Digite novamente sua nova senha"
                            required
                            minlength="6"
                        >
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        Alterar Senha
                    </button>
                </form>
                
            <?php else: ?>
                <!-- Sucesso -->
                <div class="text-center">
                    <div class="mb-3" style="font-size: 3rem; color: #27AE60;">✓</div>
                    <a href="<?php echo APP_URL; ?>/login.php" class="btn btn-primary btn-block">
                        Fazer Login
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="text-center mt-3">
                <a href="<?php echo APP_URL; ?>/login.php" class="text-muted">
                    ← Voltar ao Login
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
