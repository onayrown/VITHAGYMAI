<?php
/**
 * SMARTBIOFIT - Painel Administrativo Premium
 */

require_once '../config.php';
require_once '../database.php';

// Header com verificação de autenticação e permissão admin
include '../includes/header.php';

if (!hasPermission('admin')) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$error = '';
$success = '';

// Estatísticas do sistema
$stats = [];
try {
    $db = Database::getInstance();
    $stats = [
        'total_usuarios' => $db->fetch("SELECT COUNT(*) as total FROM usuarios WHERE ativo = TRUE")['total'],
        'total_professores' => $db->fetch("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'professor' AND ativo = TRUE")['total'],
        'total_alunos_sistema' => $db->fetch("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'aluno' AND ativo = TRUE")['total'],
        'total_alunos_cadastrados' => $db->fetch("SELECT COUNT(*) as total FROM alunos WHERE ativo = TRUE")['total'],
        'logins_hoje' => $db->fetch("SELECT COUNT(*) as total FROM logs WHERE acao = 'login' AND DATE(created_at) = CURDATE()")['total'],
        'atividade_ultima_hora' => $db->fetch("SELECT COUNT(*) as total FROM logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)")['total']
    ];
} catch (Exception $e) {
    logError("Erro ao carregar estatísticas admin: " . $e->getMessage());
}

// Usuários recentes
$usuarios_recentes = [];
try {
    $usuarios_recentes = $db->fetchAll("SELECT * FROM usuarios ORDER BY created_at DESC LIMIT 10");
} catch (Exception $e) {
    logError("Erro ao carregar usuários recentes: " . $e->getMessage());
}

// Logs do sistema
$logs_sistema = [];
try {
    $logs_sistema = $db->fetchAll("
        SELECT l.*, u.nome as usuario_nome 
        FROM logs l 
        LEFT JOIN usuarios u ON l.usuario_id = u.id 
        ORDER BY l.created_at DESC 
        LIMIT 20
    ");
} catch (Exception $e) {
    logError("Erro ao carregar logs: " . $e->getMessage());
}
?>

<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
    <div class="container mx-auto px-4 py-6">
        <!-- Premium Header with Logo -->
        <div class="relative overflow-hidden bg-gradient-to-r from-cobalt-600 via-blue-600 to-purple-600 rounded-2xl p-8 mb-8 shadow-2xl">
            <div class="relative z-10 text-center">
                <img src="<?php echo APP_URL; ?>/assets/images/logo-smartbiofit.png" alt="SMARTBIOFIT" class="h-16 mx-auto mb-4 drop-shadow-lg">
                <h1 class="text-3xl font-bold text-white mb-2 tracking-tight">⚙️ Painel Administrativo Premium</h1>
                <p class="text-blue-100 text-lg font-medium">Gerencie usuários, configurações e monitore o sistema</p>
            </div>
            <div class="absolute top-0 right-0 w-full h-full opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                            <circle cx="10" cy="10" r="2" fill="white"/>
                        </pattern>
                    </defs>
                    <rect x="0" y="0" width="100%" height="100%" fill="url(#pattern)"/>
                </svg>
            </div>
        </div>

        <!-- Statistics Dashboard -->
        <div class="mb-8">
            <div class="flex items-center mb-6">                <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Estatísticas do Sistema</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Total Users -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Usuários Ativos</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo $stats['total_usuarios'] ?? 0; ?></p>
                        </div>                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full" style="width: 70%"></div>
                    </div>
                </div>
                
                <!-- Teachers -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div>                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Instrutores</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo $stats['total_professores'] ?? 0; ?></p>
                        </div>                        <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-gradient-to-r from-green-500 to-green-600 h-2 rounded-full" style="width: 85%"></div>
                    </div>
                </div>
                
                <!-- Students -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Alunos Cadastrados</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo $stats['total_alunos_cadastrados'] ?? 0; ?></p>
                        </div>                        <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-gradient-to-r from-purple-500 to-purple-600 h-2 rounded-full" style="width: 65%"></div>
                    </div>
                </div>
                
                <!-- Today's Logins -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Logins Hoje</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo $stats['logins_hoje'] ?? 0; ?></p>
                        </div>                        <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-gradient-to-r from-orange-500 to-orange-600 h-2 rounded-full" style="width: 45%"></div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Atividade 1h</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo $stats['atividade_ultima_hora'] ?? 0; ?></p>
                        </div>                        <div class="w-12 h-12 bg-gradient-to-r from-red-500 to-red-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-gradient-to-r from-red-500 to-red-600 h-2 rounded-full" style="width: 30%"></div>
                    </div>
                </div>
                
                <!-- Environment -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Ambiente</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white uppercase"><?php echo APP_ENV; ?></p>
                        </div>                        <div class="w-12 h-12 bg-gradient-to-r from-gray-500 to-gray-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo APP_ENV === 'production' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                            <?php echo APP_ENV === 'production' ? '🟢 Production' : '🟡 Development'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>        <!-- Quick Actions -->
        <div class="mb-8">
            <div class="flex items-center mb-6">                <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Ações Rápidas</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <a href="<?php echo APP_URL; ?>/register.php" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 hover:border-blue-500">
                    <div class="flex items-center">                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 transition-colors">Criar Usuário</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Novo instrutor ou admin</p>
                        </div>
                    </div>
                </a>
                
                <a href="<?php echo APP_URL; ?>/pages/alunos.php" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 hover:border-green-500">
                    <div class="flex items-center">                        <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-green-600 transition-colors">Ver Alunos</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Todos os alunos do sistema</p>
                        </div>
                    </div>
                </a>
                
                <button onclick="backupDatabase()" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 hover:border-indigo-500 text-left w-full">
                    <div class="flex items-center">                        <div class="w-12 h-12 bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 transition-colors">Backup</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Backup do banco</p>
                        </div>
                    </div>
                </button>
                
                <button onclick="clearLogs()" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 hover:border-orange-500 text-left w-full">
                    <div class="flex items-center">                        <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-orange-600 transition-colors">Limpar Logs</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Logs antigos</p>
                        </div>
                    </div>
                </button>
            </div>
        </div>        <!-- Users and Logs -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Recent Users -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-600 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                    <div class="flex items-center">                        <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h4 class="text-xl font-semibold text-gray-900 dark:text-white">Usuários Recentes</h4>
                    </div>
                </div>
                <div class="p-6 max-h-96 overflow-y-auto">
                    <?php if (!empty($usuarios_recentes)): ?>
                        <div class="space-y-4">
                            <?php foreach ($usuarios_recentes as $usuario): ?>
                                <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">                                    <div class="w-12 h-12 bg-gradient-to-r from-gray-400 to-gray-500 rounded-full flex items-center justify-center mr-4">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-lg font-medium text-gray-900 dark:text-white truncate"><?php echo htmlspecialchars($usuario['nome']); ?></p>
                                        <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                            <span class="truncate"><?php echo htmlspecialchars($usuario['email']); ?></span>
                                            <span>•</span>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $usuario['tipo'] === 'professor' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                                <?php echo ucfirst($usuario['tipo']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?></span>
                                        <div class="w-3 h-3 <?php echo $usuario['ativo'] ? 'bg-green-500' : 'bg-red-500'; ?> rounded-full mt-1"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">                            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400">Nenhum usuário encontrado</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- System Logs -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-gray-700 dark:to-gray-600 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                    <div class="flex items-center">                        <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h4 class="text-xl font-semibold text-gray-900 dark:text-white">Logs Recentes</h4>
                    </div>
                </div>
                <div class="p-6 max-h-96 overflow-y-auto">
                    <?php if (!empty($logs_sistema)): ?>
                        <div class="space-y-4">
                            <?php foreach ($logs_sistema as $log): ?>
                                <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center mr-4 <?php 
                                        $bg_class = [
                                            'login' => 'bg-green-100 text-green-600',
                                            'logout' => 'bg-red-100 text-red-600',
                                            'criar_aluno' => 'bg-blue-100 text-blue-600',
                                            'editar_aluno' => 'bg-yellow-100 text-yellow-600',
                                            'desativar_aluno' => 'bg-red-100 text-red-600',
                                            'perfil_atualizado' => 'bg-purple-100 text-purple-600'
                                        ];
                                        echo $bg_class[$log['acao']] ?? 'bg-gray-100 text-gray-600';
                                    ?>">                                        <?php 
                                        $icon_map = [
                                            'login' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>',
                                            'logout' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>',
                                            'criar_aluno' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>',
                                            'editar_aluno' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
                                            'desativar_aluno' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"></path></svg>',
                                            'perfil_atualizado' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>'
                                        ];
                                        $icon = $icon_map[$log['acao']] ?? '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>';
                                        ?>
                                        <?php echo $icon; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-lg font-medium text-gray-900 dark:text-white truncate"><?php echo htmlspecialchars($log['usuario_nome'] ?? 'Sistema'); ?></p>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            <?php 
                                            $acoes = [
                                                'login' => 'Login realizado',
                                                'logout' => 'Logout',
                                                'criar_aluno' => 'Aluno criado',
                                                'editar_aluno' => 'Aluno editado',
                                                'desativar_aluno' => 'Aluno desativado',
                                                'perfil_atualizado' => 'Perfil atualizado'
                                            ];
                                            echo $acoes[$log['acao']] ?? htmlspecialchars($log['acao']);
                                            ?>
                                            <?php if ($log['descricao']): ?>
                                                <br><span class="text-gray-500"><?php echo htmlspecialchars($log['descricao']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo date('d/m H:i', strtotime($log['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">                            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400">Nenhum log encontrado</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>        <!-- System Information -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-gray-700 dark:to-gray-600 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                <div class="flex items-center">                    <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <h4 class="text-xl font-semibold text-gray-900 dark:text-white">Informações do Sistema</h4>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Left Column -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-600">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Versão do Sistema</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">1.0.0 (Milestone 2)</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-600">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Ambiente</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo APP_ENV === 'production' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>"><?php echo APP_ENV; ?></span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-600">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">PHP</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800"><?php echo PHP_VERSION; ?></span>
                        </div>
                        <div class="flex items-center justify-between py-3">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Servidor</span>
                            <span class="text-sm text-gray-900 dark:text-white font-medium"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-600">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Banco de Dados</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">MySQL</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-600">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Uptime</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                <?php 
                                $uptime = time() - filemtime(__DIR__ . '/../config.php');
                                echo gmdate('H:i:s', $uptime);
                                ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-600">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Memória</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800"><?php echo number_format(memory_get_peak_usage(true) / 1024 / 1024, 2); ?> MB</span>
                        </div>
                        <div class="flex items-center justify-between py-3">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Debug Mode</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo APP_DEBUG ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'; ?>">
                                <?php echo APP_DEBUG ? '🟡 Ativado' : '🟢 Desativado'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toast notification system
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 z-50 max-w-sm p-4 mb-4 text-gray-500 bg-white rounded-lg shadow dark:text-gray-400 dark:bg-gray-800 transform transition-all duration-300 translate-x-full opacity-0`;
    
    const colors = {
        success: 'text-green-500 bg-green-100 dark:bg-green-800 dark:text-green-200',
        error: 'text-red-500 bg-red-100 dark:bg-red-800 dark:text-red-200',
        warning: 'text-orange-500 bg-orange-100 dark:bg-orange-700 dark:text-orange-200',
        info: 'text-blue-500 bg-blue-100 dark:bg-blue-800 dark:text-blue-200'
    };
      const icons = {
        success: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
        error: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
        warning: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
        info: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>'
    };
      toast.innerHTML = `
        <div class="flex items-center">
            <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 ${colors[type]} rounded-lg">
                ${icons[type]}
            </div>
            <div class="ml-3 text-sm font-normal">${message}</div>
            <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex h-8 w-8 dark:text-gray-500 dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700" onclick="this.parentElement.parentElement.remove()">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-x-full', 'opacity-0');
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'fixed top-4 right-4 z-50 space-y-4';
    document.body.appendChild(container);
    return container;
}

function backupDatabase() {
    if (confirm('🗄️ Tem certeza que deseja fazer backup do banco de dados?\n\nEsta operação pode levar alguns minutos dependendo do tamanho dos dados.')) {
        showToast('⏳ Funcionalidade de backup será implementada no próximo milestone.', 'warning');
        
        // Future implementation placeholder
        setTimeout(() => {
            showToast('🚀 Backup automático será disponível em breve!', 'info');
        }, 2000);
    }
}

function clearLogs() {
    if (confirm('🗑️ Tem certeza que deseja limpar os logs antigos?\n\n⚠️ Esta ação não pode ser desfeita e removerá todos os logs com mais de 30 dias.')) {
        showToast('⏳ Funcionalidade de limpeza de logs será implementada no próximo milestone.', 'warning');
        
        // Future implementation placeholder
        setTimeout(() => {
            showToast('🧹 Sistema de manutenção automática será disponível em breve!', 'info');
        }, 2000);
    }
}

// Auto-refresh functionality with user notification
let autoRefreshEnabled = true;
let refreshCountdown = 30;

function startAutoRefresh() {
    const refreshIndicator = document.createElement('div');
    refreshIndicator.id = 'refresh-indicator';
    refreshIndicator.className = 'fixed bottom-4 left-4 z-50 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-3 border border-gray-200 dark:border-gray-700';
    refreshIndicator.innerHTML = `
        <div class="flex items-center space-x-2">
            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
            <span class="text-sm text-gray-600 dark:text-gray-400">Auto-refresh em <span id="countdown">${refreshCountdown}</span>s</span>
            <button onclick="toggleAutoRefresh()" class="text-xs text-blue-600 hover:text-blue-800">Desativar</button>
        </div>
    `;
    document.body.appendChild(refreshIndicator);
    
    const countdown = setInterval(() => {
        if (!autoRefreshEnabled) {
            clearInterval(countdown);
            return;
        }
        
        refreshCountdown--;
        const countdownElement = document.getElementById('countdown');
        if (countdownElement) {
            countdownElement.textContent = refreshCountdown;
        }
        
        if (refreshCountdown <= 0) {
            if (autoRefreshEnabled) {
                showToast('🔄 Atualizando dados do painel...', 'info');
                setTimeout(() => location.reload(), 1000);
            }
            clearInterval(countdown);
        }
    }, 1000);
}

function toggleAutoRefresh() {
    autoRefreshEnabled = !autoRefreshEnabled;
    const indicator = document.getElementById('refresh-indicator');
    
    if (!autoRefreshEnabled) {
        indicator.innerHTML = `
            <div class="flex items-center space-x-2">
                <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                <span class="text-sm text-gray-600 dark:text-gray-400">Auto-refresh desativado</span>
                <button onclick="toggleAutoRefresh()" class="text-xs text-green-600 hover:text-green-800">Ativar</button>
            </div>
        `;
        showToast('⏸️ Auto-refresh desativado', 'info');
    } else {
        refreshCountdown = 30;
        startAutoRefresh();
        showToast('▶️ Auto-refresh ativado', 'success');
    }
}

// Initialize auto-refresh when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Show welcome message
    setTimeout(() => {
        showToast('👋 Bem-vindo ao Painel Administrativo Premium!', 'success');
    }, 500);
    
    // Start auto-refresh
    startAutoRefresh();
    
    // Add loading states to action cards
    const actionCards = document.querySelectorAll('[onclick]');
    actionCards.forEach(card => {
        card.addEventListener('click', function() {
            if (this.getAttribute('onclick').includes('Database') || this.getAttribute('onclick').includes('Logs')) {
                this.style.opacity = '0.7';
                this.style.pointerEvents = 'none';
                setTimeout(() => {
                    this.style.opacity = '1';
                    this.style.pointerEvents = 'auto';
                }, 2000);
            }
        });
    });
});

// Real-time stats updates (placeholder for WebSocket implementation)
function updateStats() {
    // This would be connected to a WebSocket or polling endpoint in production
    const statNumbers = document.querySelectorAll('.text-3xl');
    statNumbers.forEach(stat => {
        // Add subtle animation to indicate live data
        stat.style.transition = 'all 0.3s ease';
        stat.style.transform = 'scale(1.05)';
        setTimeout(() => {
            stat.style.transform = 'scale(1)';
        }, 300);
    });
}

// Call updateStats every 10 seconds to simulate real-time updates
setInterval(updateStats, 10000);
</script>

<!-- Barra de navegação removida - utilizando apenas a do header.php -->

<?php include '../includes/footer.php'; ?>
