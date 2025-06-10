<?php
/**
 * SMARTBIOFIT - Página de Perfil do Usuário
 */

require_once '../config.php';
require_once '../database.php';

// Header com verificação de autenticação
include '../includes/header.php';

$error = '';
$success = '';

// Processa atualização do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefone = sanitizeInput($_POST['telefone'] ?? '');
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    if (empty($nome) || empty($email)) {
        $error = 'Nome e email são obrigatórios.';
    } elseif (!validateEmail($email)) {
        $error = 'Email inválido.';
    } else {
        try {
            $db = Database::getInstance();
            
            // Verificar se email já está em uso por outro usuário
            $emailCheck = $db->fetch("SELECT id FROM usuarios WHERE email = ? AND id != ?", [$email, $user['id']]);
            if ($emailCheck) {
                $error = 'Este email já está sendo usado por outro usuário.';
            } else {
                // Atualizar dados básicos
                $db->execute("UPDATE usuarios SET nome = ?, email = ?, telefone = ? WHERE id = ?", [
                    $nome, $email, $telefone, $user['id']
                ]);
                
                // Verificar se quer alterar senha
                if (!empty($nova_senha)) {
                    if (empty($senha_atual)) {
                        $error = 'Digite sua senha atual para alterar a senha.';
                    } elseif (!verifyPassword($senha_atual, $user['senha'])) {
                        $error = 'Senha atual incorreta.';
                    } elseif (strlen($nova_senha) < 6) {
                        $error = 'A nova senha deve ter pelo menos 6 caracteres.';
                    } elseif ($nova_senha !== $confirmar_senha) {
                        $error = 'As senhas não coincidem.';
                    } else {
                        // Alterar senha
                        $novaHash = password_hash($nova_senha, PASSWORD_DEFAULT);
                        $db->execute("UPDATE usuarios SET senha = ? WHERE id = ?", [$novaHash, $user['id']]);
                    }
                }
                
                if (!$error) {
                    // Atualizar dados da sessão
                    $_SESSION['user_name'] = $nome;
                    
                    // Log da ação
                    $db->execute("INSERT INTO logs (usuario_id, acao, descricao, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)", [
                        $user['id'],
                        'perfil_atualizado',
                        'Perfil atualizado pelo usuário',
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                    
                    $success = 'Perfil atualizado com sucesso!';
                    
                    // Recarregar dados do usuário
                    $user = $db->fetch("SELECT * FROM usuarios WHERE id = ?", [$user['id']]);
                }
            }
        } catch (Exception $e) {
            logError("Erro ao atualizar perfil: " . $e->getMessage());
            $error = 'Erro interno. Tente novamente.';
        }
    }
}
?>

<!-- Premium Mobile Profile Page -->
<div class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <div class="bg-white border-b border-gray-200 lg:rounded-t-xl lg:mx-4 lg:mt-4" data-aos="fade-down">
        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="<?php echo APP_URL; ?>" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-cobalt-600 transition-colors">
                            <i class="fas fa-home mr-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <span class="text-sm font-medium text-gray-500">Meu Perfil</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- Header Content -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <div class="h-12 w-12 bg-gradient-to-r from-cobalt-500 to-cobalt-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-user-circle text-white text-xl"></i>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Meu Perfil</h1>
                        <p class="text-sm text-gray-500">Gerencie suas informações pessoais e configurações de conta</p>
                    </div>
                </div>
            </div>
        </div>
    </div>    <!-- Alertas -->
    <?php if ($error): ?>
        <div class="mx-4 mt-4" data-aos="fade-in">
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg shadow-sm">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-3"></i>
                    <span class="font-medium"><?php echo htmlspecialchars($error); ?></span>
                    <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-red-50 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex h-8 w-8" onclick="this.parentElement.parentElement.parentElement.remove()">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="mx-4 mt-4" data-aos="fade-in">
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg shadow-sm">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    <span class="font-medium"><?php echo htmlspecialchars($success); ?></span>
                    <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex h-8 w-8" onclick="this.parentElement.parentElement.parentElement.remove()">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>    <!-- Main Content -->
    <div class="px-4 mt-6 pb-6" data-aos="fade-up">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Profile Information Card -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="text-center">
                        <div class="w-24 h-24 bg-gradient-to-br from-<?php echo strtolower($user['tipo']) === 'admin' ? 'blue-400 to-blue-600' : (strtolower($user['tipo']) === 'professor' ? 'green-400 to-green-600' : 'purple-400 to-purple-600'); ?> rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                            <i class="fas fa-user text-white text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($user['nome']); ?></h3>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-<?php echo $user['tipo'] === 'admin' ? 'blue-100 text-blue-800' : ($user['tipo'] === 'professor' ? 'green-100 text-green-800' : 'purple-100 text-purple-800'); ?>">
                            <i class="fas fa-<?php echo $user['tipo'] === 'admin' ? 'crown' : ($user['tipo'] === 'professor' ? 'chalkboard-teacher' : 'user'); ?> mr-1"></i>
                            <?php echo ucfirst($user['tipo']); ?>
                        </span>
                        
                        <div class="mt-6 space-y-4">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-600">Membro desde</span>
                                    <span class="text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                                </div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-600">Último acesso</span>
                                    <span class="text-sm text-gray-900">
                                        <?php echo $user['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($user['ultimo_acesso'])) : 'Nunca'; ?>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600">Status da conta</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-<?php echo $user['ativo'] ? 'green-100 text-green-800' : 'red-100 text-red-800'; ?>">
                                        <i class="fas fa-<?php echo $user['ativo'] ? 'check-circle' : 'times-circle'; ?> mr-1"></i>
                                        <?php echo $user['ativo'] ? 'Ativa' : 'Inativa'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>            
            <!-- Profile Edit Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <div class="w-8 h-8 bg-cobalt-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-edit text-cobalt-600 text-sm"></i>
                            </div>
                            Editar Informações
                        </h3>
                    </div>
                    <div class="p-6">
                        <form method="POST" class="space-y-6">
                            <!-- Informações Pessoais -->
                            <div class="space-y-6">
                                <div class="flex items-center space-x-3 pb-3 border-b border-gray-200">
                                    <div class="w-8 h-8 bg-cobalt-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-user text-cobalt-600 text-sm"></i>
                                    </div>
                                    <h4 class="text-lg font-semibold text-gray-900">Informações Pessoais</h4>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label for="nome" class="block text-sm font-medium text-gray-700">
                                            Nome Completo <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900" 
                                               id="nome" 
                                               name="nome" 
                                               value="<?php echo htmlspecialchars($user['nome']); ?>"
                                               required>
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <label for="email" class="block text-sm font-medium text-gray-700">
                                            Email <span class="text-red-500">*</span>
                                        </label>
                                        <input type="email" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900" 
                                               id="email" 
                                               name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>"
                                               required>
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <label for="telefone" class="block text-sm font-medium text-gray-700">
                                        Telefone
                                    </label>
                                    <input type="tel" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900" 
                                           id="telefone" 
                                           name="telefone" 
                                           value="<?php echo htmlspecialchars($user['telefone'] ?? ''); ?>"
                                           placeholder="(11) 99999-9999">
                                    <p class="text-sm text-gray-500 mt-1">Número opcional para contato</p>
                                </div>
                            </div>
                            
                            <!-- Alterar Senha -->
                            <div class="space-y-6">
                                <div class="flex items-center space-x-3 pb-3 border-b border-gray-200">
                                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-lock text-red-600 text-sm"></i>
                                    </div>
                                    <h4 class="text-lg font-semibold text-gray-900">Alterar Senha (opcional)</h4>
                                </div>
                                
                                <div class="space-y-4">
                                    <div class="space-y-2">
                                        <label for="senha_atual" class="block text-sm font-medium text-gray-700">
                                            Senha Atual
                                        </label>
                                        <input type="password" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900" 
                                               id="senha_atual" 
                                               name="senha_atual" 
                                               placeholder="Digite sua senha atual">
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <label for="nova_senha" class="block text-sm font-medium text-gray-700">
                                                Nova Senha
                                            </label>
                                            <input type="password" 
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900" 
                                                   id="nova_senha" 
                                                   name="nova_senha" 
                                                   placeholder="Digite uma nova senha"
                                                   minlength="6">
                                            <p class="text-sm text-gray-500 mt-1">Deixe em branco para manter a senha atual</p>
                                        </div>
                                        
                                        <div class="space-y-2">
                                            <label for="confirmar_senha" class="block text-sm font-medium text-gray-700">
                                                Confirmar Nova Senha
                                            </label>
                                            <input type="password" 
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900" 
                                                   id="confirmar_senha" 
                                                   name="confirmar_senha" 
                                                   placeholder="Digite novamente a nova senha"
                                                   minlength="6">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Form Actions -->
                            <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200">
                                <button type="submit" class="flex-1 sm:flex-none inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-cobalt-600 to-cobalt-700 hover:from-cobalt-700 hover:to-cobalt-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 transition-all duration-200 shadow-lg hover:shadow-xl">
                                    <i class="fas fa-save mr-2"></i>
                                    Salvar Alterações
                                </button>
                                <a href="<?php echo APP_URL; ?>" class="flex-1 sm:flex-none inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 transition-colors">
                                    <i class="fas fa-arrow-left mr-2"></i>
                                    Voltar ao Dashboard
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// AOS Animation
if (typeof AOS !== 'undefined') {
    AOS.init({
        duration: 600,
        easing: 'ease-out-cubic',
        once: true,
        offset: 50
    });
}

// Auto-hide alerts
setTimeout(() => {
    const alerts = document.querySelectorAll('[data-aos="fade-in"]');
    alerts.forEach(alert => {
        if (alert) {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }
    });
}, 5000);

// Validação de senhas
document.addEventListener('DOMContentLoaded', function() {
    const novaSenha = document.getElementById('nova_senha');
    const confirmarSenha = document.getElementById('confirmar_senha');
    const senhaAtual = document.getElementById('senha_atual');
    
    function validarSenhas() {
        if (novaSenha.value && novaSenha.value !== confirmarSenha.value) {
            confirmarSenha.setCustomValidity('As senhas não coincidem');
        } else {
            confirmarSenha.setCustomValidity('');
        }
        
        if (novaSenha.value && !senhaAtual.value) {
            senhaAtual.setCustomValidity('Digite sua senha atual para alterar a senha');
        } else {
            senhaAtual.setCustomValidity('');
        }
    }
    
    novaSenha.addEventListener('input', validarSenhas);
    confirmarSenha.addEventListener('input', validarSenhas);
    senhaAtual.addEventListener('input', validarSenhas);
    
    // Máscara de telefone
    const telefone = document.getElementById('telefone');
    if (telefone) {
        telefone.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                if (value.length < 14) {
                    value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
                }
                e.target.value = value;
            }
        });
    }
    
    // Form submission enhancement
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Salvando...';
                submitBtn.disabled = true;
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
