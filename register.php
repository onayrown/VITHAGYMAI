<?php
/**
 * SMARTBIOFIT - Página de Registro
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

// Processa o registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefone = sanitizeInput($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $tipo = sanitizeInput($_POST['tipo'] ?? 'professor'); // Por padrão, professor
    
    // Validações
    if (empty($nome) || empty($email) || empty($senha)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!validateEmail($email)) {
        $error = 'Email inválido.';
    } elseif (strlen($senha) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($senha !== $confirmar_senha) {
        $error = 'As senhas não conferem.';
    } else {
        try {
            $db = Database::getInstance();
            
            // Verifica se email já existe
            $existingUser = $db->fetch("SELECT id FROM usuarios WHERE email = ?", [$email]);
            if ($existingUser) {
                $error = 'Este email já está cadastrado.';
            } else {
                // Cria o usuário
                $senhaHash = hashPassword($senha);
                
                $userId = $db->execute("
                    INSERT INTO usuarios (nome, email, senha, telefone, tipo, ativo) 
                    VALUES (?, ?, ?, ?, ?, TRUE)
                ", [$nome, $email, $senhaHash, $telefone, $tipo]);
                
                if ($userId) {
                    // Log de criação
                    $db->execute("INSERT INTO logs (usuario_id, acao, descricao, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)", [
                        $db->lastInsertId(),
                        'user_created',
                        'Usuário registrado no sistema',
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                    
                    $_SESSION['success_message'] = 'Conta criada com sucesso! Faça login para continuar.';
                    header('Location: ' . APP_URL . '/login.php');
                    exit;
                } else {
                    $error = 'Erro ao criar conta. Tente novamente.';
                }
            }
        } catch (Exception $e) {
            logError("Erro no registro: " . $e->getMessage());
            $error = 'Erro interno. Tente novamente.';
        }
    }
}

include 'includes/header.php';
?>

<div class="container" style="max-width: 500px; margin-top: 3vh;">
    <div class="card">
        <div class="card-body">            <!-- Logo centralizada -->            <div class="text-center mb-4">
                <img src="<?php echo APP_URL; ?>/assets/images/logo-smartbiofit.png" alt="SMARTBIOFIT" style="height: 60px;">
                <h1 class="h3 mt-3 text-primary">Criar Conta</h1>
                <p class="text-muted">Cadastre-se na plataforma</p>
            </div>
            
            <!-- Alertas -->
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Formulário de Registro -->
            <form method="POST" action="<?php echo APP_URL; ?>/register.php" id="registerForm">
                <div class="form-group">
                    <label for="nome" class="form-label">Nome Completo *</label>                    <input 
                        type="text" 
                        class="form-control text-gray-900" 
                        id="nome" 
                        name="nome" 
                        value="<?php echo htmlspecialchars($nome ?? ''); ?>"
                        placeholder="Seu nome completo"
                        required
                        autocomplete="name"
                        autofocus
                    >
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email *</label>                    <input 
                        type="email" 
                        class="form-control text-gray-900" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($email ?? ''); ?>"
                        placeholder="seu@email.com"
                        required
                        autocomplete="email"
                    >
                </div>
                
                <div class="form-group">
                    <label for="telefone" class="form-label">Telefone</label>                    <input 
                        type="tel" 
                        class="form-control text-gray-900" 
                        id="telefone" 
                        name="telefone" 
                        value="<?php echo htmlspecialchars($telefone ?? ''); ?>"
                        placeholder="(11) 99999-9999"
                        data-format="phone"
                        autocomplete="tel"
                    >
                </div>
                
                <div class="form-group">
                    <label for="tipo" class="form-label">Tipo de Conta</label>
                    <select class="form-control form-select text-gray-900" id="tipo" name="tipo" required>
                        <option value="professor" <?php echo ($tipo ?? 'professor') === 'professor' ? 'selected' : ''; ?>>
                            Professor de Educação Física
                        </option>
                        <option value="aluno" <?php echo ($tipo ?? '') === 'aluno' ? 'selected' : ''; ?>>
                            Aluno
                        </option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="senha" class="form-label">Senha *</label>                    <input 
                        type="password" 
                        class="form-control text-gray-900" 
                        id="senha" 
                        name="senha" 
                        placeholder="Mínimo 6 caracteres"
                        required
                        autocomplete="new-password"
                        minlength="6"
                    >
                    <small class="text-muted">A senha deve ter pelo menos 6 caracteres</small>
                </div>
                
                <div class="form-group">
                    <label for="confirmar_senha" class="form-label">Confirmar Senha *</label>                    <input 
                        type="password" 
                        class="form-control text-gray-900" 
                        id="confirmar_senha" 
                        name="confirmar_senha" 
                        placeholder="Digite a senha novamente"
                        required
                        autocomplete="new-password"
                        minlength="6"
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" name="termos" required style="margin-right: var(--spacing-xs);">
                        Aceito os <a href="#" class="text-primary">termos de uso</a> e 
                        <a href="#" class="text-primary">política de privacidade</a>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg btn-block">
                    Criar Conta
                </button>
            </form>
            
            <!-- Links adicionais -->
            <div class="text-center mt-4">
                <p class="text-muted">
                    Já tem uma conta? 
                    <a href="<?php echo APP_URL; ?>/login.php" class="text-primary">Entre aqui</a>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Informações sobre tipos de conta -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">ℹ️ Tipos de Conta</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-12 col-md-6 mb-3">
                    <h6 class="text-primary">👨‍🏫 Professor</h6>
                    <ul class="mb-0">
                        <li>Gerenciar alunos</li>
                        <li>Realizar avaliações físicas</li>
                        <li>Criar treinos personalizados</li>
                        <li>Gerar relatórios</li>
                    </ul>
                </div>
                <div class="col-12 col-md-6 mb-3">
                    <h6 class="text-primary">👤 Aluno</h6>
                    <ul class="mb-0">
                        <li>Visualizar avaliações</li>
                        <li>Acessar treinos</li>
                        <li>Acompanhar progresso</li>
                        <li>Histórico de evolução</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const senha = document.getElementById('senha');
    const confirmarSenha = document.getElementById('confirmar_senha');
    
    // Validação em tempo real das senhas
    function validatePasswords() {
        if (senha.value && confirmarSenha.value) {
            if (senha.value !== confirmarSenha.value) {
                confirmarSenha.setCustomValidity('As senhas não conferem');
                confirmarSenha.classList.add('is-invalid');
            } else {
                confirmarSenha.setCustomValidity('');
                confirmarSenha.classList.remove('is-invalid');
            }
        }
    }
    
    senha.addEventListener('input', validatePasswords);
    confirmarSenha.addEventListener('input', validatePasswords);
    
    // Validação do formulário
    registerForm.addEventListener('submit', function(e) {
        const nome = document.getElementById('nome').value;
        const email = document.getElementById('email').value;
        const senhaValue = senha.value;
        const confirmarSenhaValue = confirmarSenha.value;
        const termos = document.querySelector('input[name="termos"]').checked;
        
        if (!nome || !email || !senhaValue || !confirmarSenhaValue) {
            e.preventDefault();
            Utils.showAlert('Por favor, preencha todos os campos obrigatórios.', 'danger');
            return;
        }
        
        if (!Utils.validateEmail(email)) {
            e.preventDefault();
            Utils.showAlert('Por favor, insira um email válido.', 'danger');
            return;
        }
        
        if (senhaValue.length < 6) {
            e.preventDefault();
            Utils.showAlert('A senha deve ter pelo menos 6 caracteres.', 'danger');
            return;
        }
        
        if (senhaValue !== confirmarSenhaValue) {
            e.preventDefault();
            Utils.showAlert('As senhas não conferem.', 'danger');
            return;
        }
        
        if (!termos) {
            e.preventDefault();
            Utils.showAlert('Você deve aceitar os termos de uso.', 'danger');
            return;
        }
        
        // Mostra loading no botão
        const submitBtn = registerForm.querySelector('button[type="submit"]');
        Utils.showLoading(submitBtn);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
