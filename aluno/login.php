<?php
/**
 * SMARTBIOFIT - Sistema de Login para Alunos
 * Login específico para alunos acessarem seus dados
 */

require_once '../config.php';

$erro = '';
$sucesso = '';

// Verificar se aluno já está logado
if (isset($_SESSION['aluno_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Processar login
if ($_POST && isset($_POST['email']) && isset($_POST['senha'])) {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    
    if (empty($email) || empty($senha)) {
        $erro = 'Por favor, preencha todos os campos.';
    } else {
        try {
            // Buscar aluno pelo email
            $stmt = $pdo->prepare("
                SELECT id, nome, email, senha, primeiro_acesso, ativo, professor_id 
                FROM alunos 
                WHERE email = ? AND ativo = 1
            ");
            $stmt->execute([$email]);
            $aluno = $stmt->fetch();
            
            if ($aluno) {
                // Verificar se é primeiro acesso (sem senha definida)
                if ($aluno['primeiro_acesso'] == 1 && empty($aluno['senha'])) {
                    // Primeiro acesso - criar senha
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    $update_stmt = $pdo->prepare("
                        UPDATE alunos 
                        SET senha = ?, primeiro_acesso = 0, ultimo_login = NOW() 
                        WHERE id = ?
                    ");
                    $update_stmt->execute([$senha_hash, $aluno['id']]);
                    
                    // Login automático após definir senha
                    $_SESSION['aluno_id'] = $aluno['id'];
                    $_SESSION['aluno_nome'] = $aluno['nome'];
                    $_SESSION['aluno_email'] = $aluno['email'];
                    $_SESSION['professor_id'] = $aluno['professor_id'];
                    $_SESSION['user_type'] = 'aluno';
                    
                    header('Location: dashboard.php');
                    exit;
                } else {
                    // Verificar senha
                    if (password_verify($senha, $aluno['senha'])) {
                        // Atualizar último login
                        $update_stmt = $pdo->prepare("UPDATE alunos SET ultimo_login = NOW() WHERE id = ?");
                        $update_stmt->execute([$aluno['id']]);
                        
                        // Criar sessão
                        $_SESSION['aluno_id'] = $aluno['id'];
                        $_SESSION['aluno_nome'] = $aluno['nome'];
                        $_SESSION['aluno_email'] = $aluno['email'];
                        $_SESSION['professor_id'] = $aluno['professor_id'];
                        $_SESSION['user_type'] = 'aluno';
                        
                        header('Location: dashboard.php');
                        exit;
                    } else {
                        $erro = 'Email ou senha incorretos.';
                    }
                }
            } else {
                $erro = 'Email ou senha incorretos.';
            }
        } catch (Exception $e) {
            $erro = 'Erro interno. Tente novamente.';
            error_log("Erro login aluno: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Aluno - VITHA GYM</title>
    <link href="../assets/css/ios-premium.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        cobalt: {
                            50: '#eff6ff',
                            100: '#dbeafe', 
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2F80ED',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a'
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Login Container -->
    <div class="min-h-screen w-full bg-gradient-to-br from-cobalt-50 via-white to-gray-50">
        <div class="flex flex-col min-h-screen justify-center py-6 px-4 sm:px-6 lg:px-8">
            <div class="mx-auto w-full max-w-md">                <!-- Logo e Título -->
                <div class="text-center mb-8">
                    <img src="../assets/images/logo-vithagym.png" alt="VITHA GYM" class="mx-auto h-[15rem] w-auto mb-4">
                    <h2 class="text-2xl font-bold text-gray-900">Área do Aluno</h2>
                    
                </div>

                <!-- Card Principal -->
                <div class="bg-white rounded-xl shadow-lg p-6 space-y-4 border border-gray-100">
                    <!-- Alertas -->
                    <?php if ($erro): ?>
                        <div class="rounded-lg bg-red-50 border border-red-200 p-3">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-4 w-4 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-2">
                                    <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($erro) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($sucesso): ?>
                        <div class="rounded-lg bg-green-50 border border-green-200 p-3">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-4 w-4 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-2">
                                    <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($sucesso) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Formulário de Login -->
                    <form method="POST" action="" id="loginForm" class="space-y-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input 
                                type="email" 
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cobalt-500 focus:border-cobalt-500 transition-colors bg-white text-gray-900 placeholder-gray-500 text-sm" 
                                id="email" 
                                name="email" 
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
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
                            <small class="text-gray-500 mt-2 block text-xs">
                                <i class="fas fa-info-circle me-1"></i>
                                Primeiro acesso? Digite qualquer senha para criar sua conta.
                            </small>
                        </div>
                        
                        <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg text-sm font-medium text-white bg-gradient-to-r from-cobalt-600 to-cobalt-700 hover:from-cobalt-700 hover:to-cobalt-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 transition-all duration-200">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                </svg>
                                Entrar na Área do Aluno
                            </span>
                        </button>
                    </form>
                    
                    <!-- Links adicionais -->
                    <div class="text-center">
                        <a href="recuperar-senha.php" class="text-cobalt-600 hover:text-cobalt-800 font-medium text-sm transition-colors">
                            <i class="fas fa-key me-1"></i>
                            Esqueci minha senha
                        </a>
                    </div>
                    
                    <div class="text-center">
                        <a href="../index.php" class="text-gray-500 hover:text-gray-700 text-sm transition-colors">
                            <i class="fas fa-arrow-left me-1"></i>
                            Voltar ao início
                        </a>
                    </div>
                </div>
                
                <!-- Informações do sistema -->
                <div class="text-center mt-6">
                    <div class="inline-flex items-center space-x-2 text-xs text-gray-500">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span>VITHA GYM v1.0 - Área do Aluno</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Aplicativo de Avaliação Física Profissional</p>
                </div>
            </div>
        </div>
    </div>    <script>
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
</body>
</html>
