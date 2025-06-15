<?php
/**
 * VithaGymAI - Página de Login
 * Sistema de autenticação principal
 */

// CRÍTICO: Iniciar output buffering ANTES de qualquer coisa
ob_start();

require_once 'config.php';
require_once 'database.php';

// Headers para evitar cache da página de login
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('ETag: "' . md5(time()) . '"');

// Debug: Verificar se há sessão ativa
if (APP_DEBUG) {
    error_log("LOGIN DEBUG - Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));
    error_log("LOGIN DEBUG - Session data: " . print_r($_SESSION, true));
}

// IMPORTANTE: Verificar redirecionamento ANTES de qualquer output
if (isset($_SESSION['user_id'])) {
    if (APP_DEBUG) {
        error_log("LOGIN DEBUG - User already logged in, redirecting to dashboard");
    }
    
    // Limpar qualquer output buffer antes do redirecionamento
    ob_end_clean();
    
    // Redirecionamento direto sem qualquer output
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$error = '';
$success = '';

// Processa o login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (APP_DEBUG) {
        error_log("LOGIN DEBUG - POST recebido");
        error_log("LOGIN DEBUG - POST data: " . print_r($_POST, true));
    }
    
    // CORREÇÃO: Parse manual dos dados POST se necessário
    if (empty($_POST)) {
        $rawInput = file_get_contents('php://input');
        if (!empty($rawInput)) {
            parse_str($rawInput, $parsedData);
            $_POST = $parsedData;
            
            if (APP_DEBUG) {
                error_log("LOGIN DEBUG - Raw input: " . $rawInput);
                error_log("LOGIN DEBUG - Parsed data: " . print_r($parsedData, true));
            }
        }
    }
    
    $email = sanitizeInput($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (APP_DEBUG) {
        error_log("LOGIN DEBUG - Email: $email");
        error_log("LOGIN DEBUG - Senha length: " . strlen($senha));
    }
    
    if (empty($email) || empty($senha)) {
        $error = 'Por favor, preencha todos os campos.';
        if (APP_DEBUG) {
            error_log("LOGIN DEBUG - Campos vazios");
        }
    } elseif (!validateEmail($email)) {
        $error = 'Email inválido.';
        if (APP_DEBUG) {
            error_log("LOGIN DEBUG - Email inválido: $email");
        }
    } else {
        try {
            if (APP_DEBUG) {
                error_log("LOGIN DEBUG - Iniciando consulta ao banco");
            }
            
            $db = Database::getInstance();
            $user = $db->fetch("SELECT * FROM usuarios WHERE email = ? AND ativo = TRUE", [$email]);
            
            if (APP_DEBUG) {
                error_log("LOGIN DEBUG - Usuário encontrado: " . ($user ? 'SIM' : 'NÃO'));
                if ($user) {
                    error_log("LOGIN DEBUG - User ID: " . $user['id'] . ", Nome: " . $user['nome']);
                }
            }
            
            if ($user && verifyPassword($senha, $user['senha'])) {
                if (APP_DEBUG) {
                    error_log("LOGIN DEBUG - Senha correta, criando sessão");
                }
                
                // Login bem-sucedido
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_type'] = $user['tipo'];
                $_SESSION['login_time'] = time();
                
                if (APP_DEBUG) {
                    error_log("LOGIN DEBUG - Sessão criada: " . print_r($_SESSION, true));
                }
                
                // Log do login
                $db->execute("INSERT INTO logs (usuario_id, acao, descricao, ip_address) VALUES (?, ?, ?, ?)", [
                    $user['id'],
                    'login',
                    'Login realizado com sucesso',
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
                // Atualiza último acesso
                $db->execute("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?", [$user['id']]);
                
                if (APP_DEBUG) {
                    error_log("LOGIN DEBUG - Preparando redirecionamento");
                }
                
                // Limpar output buffer antes do redirecionamento
                ob_end_clean();
                
                // Redireciona para o dashboard principal para TODOS os usuários
                $redirectUrl = APP_URL . '/index.php';
                
                if (APP_DEBUG) {
                    error_log("LOGIN DEBUG - Redirecionando para: $redirectUrl");
                }
                
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                $error = 'Email ou senha incorretos.';
                
                if (APP_DEBUG) {
                    if (!$user) {
                        error_log("LOGIN DEBUG - Usuário não encontrado para email: $email");
                    } else {
                        error_log("LOGIN DEBUG - Senha incorreta para usuário: " . $user['nome']);
                        error_log("LOGIN DEBUG - Hash no banco: " . substr($user['senha'], 0, 20) . "...");
                        error_log("LOGIN DEBUG - Verificação: " . (verifyPassword($senha, $user['senha']) ? 'OK' : 'FALHOU'));
                    }
                }
                
                // Log da tentativa de login falhada
                if ($user) {
                    $db->execute("INSERT INTO logs (usuario_id, acao, descricao, ip_address) VALUES (?, ?, ?, ?)", [
                        $user['id'],
                        'login_failed',
                        'Tentativa de login com senha incorreta',
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                }
            }
        } catch (Exception $e) {
            logError("Erro no login: " . $e->getMessage());
            $error = 'Erro interno. Tente novamente.';
            
            if (APP_DEBUG) {
                error_log("LOGIN DEBUG - Exceção: " . $e->getMessage());
                error_log("LOGIN DEBUG - Stack trace: " . $e->getTraceAsString());
            }
        }
    }
}

// Verifica se há mensagem de sucesso (ex: após registro)
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Só inclui o header se não estiver logado (evita output desnecessário)
include 'includes/header.php';
?>

<!-- Login Container -->
<div class="min-h-screen w-full bg-gradient-to-br from-cobalt-50 via-white to-health-50">
    <div class="flex flex-col min-h-screen justify-center py-6 px-4 sm:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-md">
            <!-- Logo e Título -->
            <div class="text-center mb-6">
                <img src="<?php echo APP_URL; ?>/assets/images/logo-vithagymai.png" alt="VithaGymAI" class="mx-auto h-16 w-auto mb-4">
                <h2 class="text-center text-3xl font-extrabold text-gray-900">Entrar</h2>
                <p class="mt-2 text-sm text-gray-600">Acesse o VithaGymAI</p>
            </div>

            <!-- Card Principal -->
            <div class="bg-white rounded-xl shadow-lg p-6 space-y-4 border border-gray-100">                <!-- Alertas -->
                <?php if ($error): ?>
                    <div class="rounded-lg bg-red-50 border border-red-200 p-3">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-4 w-4 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-2">
                                <p class="text-sm font-medium text-red-800"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="rounded-lg bg-green-50 border border-green-200 p-3">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-4 w-4 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-2">
                                <p class="text-sm font-medium text-green-800"><?php echo htmlspecialchars($success); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>                <!-- Formulário de Login -->
                <form method="POST" action="<?php echo APP_URL; ?>/login.php" id="loginForm" class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input 
                            type="email" 
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cobalt-500 focus:border-cobalt-500 transition-colors bg-white text-gray-900 placeholder-gray-500 text-sm" 
                            id="email" 
                            name="email" 
                            value="<?php echo htmlspecialchars($email ?? ''); ?>"
                            placeholder="seu@email.com"
                            required
                            autocomplete="email"
                            autofocus
                        >
                    </div>
                    
                    <div>
                        <label for="senha" class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                        <input 
                            type="password" 
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cobalt-500 focus:border-cobalt-500 transition-colors bg-white text-gray-900 placeholder-gray-500 text-sm" 
                            id="senha" 
                            name="senha" 
                            placeholder="Sua senha"
                            required
                            autocomplete="current-password"
                        >
                    </div>
                    
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center">
                            <input type="checkbox" name="remember" id="remember" class="h-4 w-4 text-cobalt-600 focus:ring-cobalt-500 border-gray-300 rounded">
                            <label for="remember" class="ml-2 text-gray-700">Lembrar</label>
                        </div>
                        <div>
                            <a href="<?php echo APP_URL; ?>/reset-password.php" class="text-cobalt-600 hover:text-cobalt-800 font-medium">Esqueceu a senha?</a>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg text-sm font-medium text-white bg-gradient-to-r from-cobalt-600 to-cobalt-700 hover:from-cobalt-700 hover:to-cobalt-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 transition-all duration-200">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            Entrar
                        </span>
                    </button>                </form>
                
                <!-- Links adicionais -->
                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        Não tem uma conta? 
                        <a href="<?php echo APP_URL; ?>/register.php" class="font-medium text-cobalt-600 hover:text-cobalt-800 transition-colors">Cadastre-se aqui</a>
                    </p>
                </div>
                
                <!-- Informações de acesso padrão (apenas em desenvolvimento) -->
                <?php if (APP_DEBUG): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-4 w-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-2">
                            <h3 class="text-sm font-medium text-yellow-800">Modo Desenvolvimento</h3>
                            <div class="mt-1 text-xs text-yellow-700">
                                <p><strong>Admin:</strong> admin@vithagymai.com / admin123</p>
                                <p>Crie usuários via painel admin após login</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Informações do sistema -->
            <div class="text-center mt-6">
                <div class="inline-flex items-center space-x-2 text-xs text-gray-500">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>VithaGymAI v1.0</span>
                </div>
                <p class="text-xs text-gray-400 mt-1">Aplicativo de Avaliação Física Profissional</p>
            </div>
        </div>
    </div>
</div>

<script>
// SCRIPT DE LIMPEZA AGRESSIVA DE CACHE E SERVICE WORKER
function aggressiveCleanup() {
    console.log('VithaGymAI: Iniciando limpeza agressiva...');
    let swUnregistered = false;
    let cachesCleared = false;

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then(registrations => {
            if (registrations.length > 0) {
                registrations.forEach(registration => {
                    registration.unregister().then(unregistered => {
                        if (unregistered) {
                            console.log('VithaGymAI: Service Worker desregistrado com sucesso.');
                            swUnregistered = true;
                            // Se ambos terminaram, recarregue
                            if (swUnregistered && cachesCleared) window.location.reload(true);
                        }
                    });
                });
            } else {
                // Nenhum SW para desregistrar, prossiga
                swUnregistered = true;
            }
        });
    } else {
        swUnregistered = true;
    }

    if (window.caches) {
        caches.keys().then(cacheNames => {
            if (cacheNames.length > 0) {
                Promise.all(cacheNames.map(cacheName => {
                    console.log(`VithaGymAI: Deletando cache: ${cacheName}`);
                    return caches.delete(cacheName);
                })).then(() => {
                    console.log('VithaGymAI: Todos os caches foram limpos.');
                    cachesCleared = true;
                    // Se ambos terminaram, recarregue
                    if (swUnregistered && cachesCleared) window.location.reload(true);
                });
            } else {
                 // Nenhum cache para limpar, prossiga
                cachesCleared = true;
            }
        });
    } else {
        cachesCleared = true;
    }

    // Se não houve nada para limpar, a página não irá recarregar, então o login pode prosseguir normalmente.
}

// Executar a limpeza uma única vez usando sessionStorage para controle
if (!sessionStorage.getItem('vithagym_cleaned')) {
    aggressiveCleanup();
    sessionStorage.setItem('vithagym_cleaned', 'true');
}

document.addEventListener('DOMContentLoaded', function() {
    // Foco automático no email se estiver vazio
    const emailInput = document.getElementById('email');
    if (!emailInput.value) {
        emailInput.focus();
    }
    
    // Validação do formulário
    const loginForm = document.getElementById('loginForm');
    const submitBtn = loginForm.querySelector('button[type="submit"]');
    
    loginForm.addEventListener('submit', function(e) {
        const email = document.getElementById('email').value;
        const senha = document.getElementById('senha').value;
        
        if (!email || !senha) {
            e.preventDefault();
            showAlert('Por favor, preencha todos os campos.', 'error');
            return;
        }
        
        // Validação básica de email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            showAlert('Por favor, insira um email válido.', 'error');
            return;
        }
        
        // Mostra loading no botão
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <span class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Entrando...
            </span>
        `;
        
        // O redirecionamento agora será puramente pelo lado do servidor,
        // removendo o timeout do javascript que agia como band-aid
    });
});

function showAlert(message, type) {
    // Remove alertas existentes
    const existingAlerts = document.querySelectorAll('.alert-temp');
    existingAlerts.forEach(alert => alert.remove());
    
    const colorClasses = type === 'error' 
        ? 'bg-red-50 border-red-200 text-red-800' 
        : 'bg-green-50 border-green-200 text-green-800';
    
    const iconPath = type === 'error' 
        ? 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z'
        : 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z';
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert-temp rounded-lg border p-4 mb-4 ${colorClasses}`;
    alertDiv.innerHTML = `
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="${iconPath}" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium">${message}</p>
            </div>
        </div>
    `;
    
    const form = document.getElementById('loginForm');
    form.parentNode.insertBefore(alertDiv, form);
    
    // Remove o alerta após 5 segundos
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>

<?php include 'includes/footer.php'; ?>
