<?php
/**
 * SMARTBIOFIT - Dashboard Principal
 * Premium Mobile-First Dashboard
 */

require_once 'config.php';
require_once 'database.php';

// Header com verificação de autenticação
include 'includes/header.php';

// Estatísticas do dashboard
$stats = [
    'total_alunos' => 0,
    'avaliacoes_mes' => 0,
    'treinos_ativos' => 0,
    'usuarios_online' => 0
];

try {
    $db = Database::getInstance();
    
    // Adiciona um log para verificar o ID do usuário e tipo
    error_log("User ID: " . ($_SESSION['user_id'] ?? 'N/A') . ", User Type: " . ($user['tipo'] ?? 'N/A'));

    if (hasPermission('professor')) {
        // Para professores e admins
        $stats['total_alunos'] = $db->fetch("SELECT COUNT(*) as total FROM usuarios WHERE tipo = \'aluno\' AND ativo = TRUE")['total'];
        error_log("Total de Alunos Query Result: " . $stats['total_alunos']);

        $stats['avaliacoes_mes'] = $db->fetch("SELECT COUNT(*) as total FROM logs WHERE acao LIKE \'%avaliacao%\' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)")['total'];
        error_log("Avaliações no Mês Query Result: " . $stats['avaliacoes_mes']);

        $stats['treinos_ativos'] = 0; // Será implementado no Milestone 4
        $stats['usuarios_online'] = $db->fetch("SELECT COUNT(*) as total FROM usuarios WHERE ultimo_acesso >= DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND ativo = TRUE")['total'];
    } else {
        // Para alunos - estatísticas pessoais
        $stats['minhas_avaliacoes'] = $db->fetch("SELECT COUNT(*) as total FROM logs WHERE usuario_id = ? AND acao LIKE '%avaliacao%'", [$user['id']])['total'];
        $stats['meus_treinos'] = 0; // Será implementado no Milestone 4
        $stats['dias_cadastrado'] = $db->fetch("SELECT DATEDIFF(NOW(), created_at) as dias FROM usuarios WHERE id = ?", [$user['id']])['dias'];
        $stats['ultimo_treino'] = 0; // Será implementado no Milestone 4
    }
    
} catch (Exception $e) {
    logError("Erro ao carregar estatísticas do dashboard: " . $e->getMessage());
}

// Atividades recentes
$atividades_recentes = [];
try {
    if (hasPermission('professor')) {
        // Para instrutores: mostrar apenas as últimas 5 atividades dos alunos
        $atividades_recentes = $db->fetchAll("
            SELECT l.*, u.nome as usuario_nome 
            FROM logs l 
            LEFT JOIN usuarios u ON l.usuario_id = u.id 
            WHERE u.tipo = 'aluno'
            ORDER BY l.created_at DESC 
            LIMIT 5
        ");
    } else {
        $atividades_recentes = $db->fetchAll("
            SELECT * FROM logs 
            WHERE usuario_id = ? 
            ORDER BY created_at DESC 
            LIMIT 10
        ", [$user['id']]);
    }
} catch (Exception $e) {
    logError("Erro ao carregar atividades recentes: " . $e->getMessage());
}
?>

<!-- Premium Mobile-First Dashboard -->
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">

    <!-- Desktop Hero Section -->
    <div class="hidden lg:block bg-gradient-to-r from-blue-600 via-blue-700 to-green-600 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="text-center" data-aos="fade-up">
                <img src="<?php echo APP_URL; ?>/assets/images/logo-smartbiofit.png" alt="SMARTBIOFIT" class="h-16 w-auto mx-auto mb-6">
                <h1 class="text-4xl font-bold mb-4">
                    Bem-vindo, <?php echo htmlspecialchars($user['nome']); ?>! 👋
                </h1>
                <p class="text-xl text-blue-100 max-w-2xl mx-auto">
                    <?php if (hasPermission('professor')): ?>
                        Gerencie seus alunos, avaliações e treinos com eficiência e praticidade.
                    <?php else: ?>
                        Acompanhe seu progresso e evolução física de forma inteligente.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
        
        <!-- Mobile Hero Card -->
        <div class="lg:hidden mb-6" data-aos="fade-up">
            <div class="bg-gradient-to-r from-blue-600 to-green-600 rounded-2xl p-6 text-white shadow-lg">
                <div class="text-center">
                    <h2 class="text-2xl font-bold mb-2">Dashboard</h2>
                    <p class="text-blue-100">
                        <?php if (hasPermission('professor')): ?>
                            Painel de controle profissional
                        <?php else: ?>
                            Seu progresso fitness
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Statistics Cards Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8" data-aos="fade-up" data-aos-delay="100">
            <?php if (hasPermission('professor')): ?>
                <!-- Total Students Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 rounded-xl bg-blue-50 text-blue-600 group-hover:bg-blue-100 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-3xl font-bold text-gray-900"><?php echo $stats['total_alunos']; ?></h3>
                        <p class="text-sm text-gray-500">Total de Alunos</p>
                    </div>
                </div>

                <!-- Monthly Evaluations Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 rounded-xl bg-green-50 text-green-600 group-hover:bg-green-100 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-3xl font-bold text-gray-900"><?php echo $stats['avaliacoes_mes']; ?></h3>
                        <p class="text-sm text-gray-500">Avaliações/Mês</p>
                    </div>
                </div>

                <!-- Active Workouts Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 rounded-xl bg-yellow-50 text-yellow-600 group-hover:bg-yellow-100 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-3xl font-bold text-gray-900"><?php echo $stats['treinos_ativos']; ?></h3>
                        <p class="text-sm text-gray-500">Treinos Ativos</p>
                    </div>
                </div>

                <!-- Online Users Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 rounded-xl bg-purple-50 text-purple-600 group-hover:bg-purple-100 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-3xl font-bold text-gray-900"><?php echo $stats['usuarios_online']; ?></h3>
                        <p class="text-sm text-gray-500">Online Agora</p>
                    </div>
                </div>
            <?php else: ?>
                <!-- My Evaluations Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 rounded-xl bg-blue-50 text-blue-600 group-hover:bg-blue-100 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-3xl font-bold text-gray-900"><?php echo $stats['minhas_avaliacoes']; ?></h3>
                        <p class="text-sm text-gray-500">Minhas Avaliações</p>
                    </div>
                </div>

                <!-- My Workouts Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 rounded-xl bg-green-50 text-green-600 group-hover:bg-green-100 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-3xl font-bold text-gray-900"><?php echo $stats['meus_treinos']; ?></h3>
                        <p class="text-sm text-gray-500">Meus Treinos</p>
                    </div>
                </div>

                <!-- Days Registered Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 rounded-xl bg-yellow-50 text-yellow-600 group-hover:bg-yellow-100 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-3xl font-bold text-gray-900"><?php echo $stats['dias_cadastrado']; ?></h3>
                        <p class="text-sm text-gray-500">Dias Cadastrado</p>
                    </div>
                </div>

                <!-- Last Workout Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 rounded-xl bg-purple-50 text-purple-600 group-hover:bg-purple-100 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-3xl font-bold text-gray-900">-</h3>
                        <p class="text-sm text-gray-500">Último Treino</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions Section -->
        <div class="mb-8" data-aos="fade-up" data-aos-delay="200">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">🚀 Ações Rápidas</h2>
                <div class="hidden lg:block">
                    <span class="text-sm text-gray-500">Acesso rápido às principais funcionalidades</span>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                <?php if (hasPermission('professor')): ?>
                    <!-- Add New Student -->
                    <a href="<?php echo APP_URL; ?>/pages/alunos.php?action=add" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-lg hover:border-blue-200 transition-all duration-300 text-decoration-none">
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-50 rounded-xl text-blue-600 group-hover:bg-blue-100 group-hover:scale-110 transition-all duration-300 mb-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Novo Aluno</h3>
                            <p class="text-sm text-gray-500">Cadastrar novo aluno no sistema</p>
                        </div>
                    </a>

                    <!-- New Evaluation -->
                    <a href="<?php echo APP_URL; ?>/pages/avaliacoes.php?action=add" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-lg hover:border-green-200 transition-all duration-300 text-decoration-none">
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 bg-green-50 rounded-xl text-green-600 group-hover:bg-green-100 group-hover:scale-110 transition-all duration-300 mb-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Nova Avaliação</h3>
                            <p class="text-sm text-gray-500">Realizar avaliação física completa</p>
                        </div>
                    </a>

                    <!-- New Workout -->
                    <a href="<?php echo APP_URL; ?>/pages/treinos.php?action=add" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-lg hover:border-yellow-200 transition-all duration-300 text-decoration-none">
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 bg-yellow-50 rounded-xl text-yellow-600 group-hover:bg-yellow-100 group-hover:scale-110 transition-all duration-300 mb-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Novo Treino</h3>
                            <p class="text-sm text-gray-500">Criar treino personalizado</p>
                        </div>
                    </a>

                    <!-- Reports -->
                    <a href="<?php echo APP_URL; ?>/pages/relatorios.php" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-lg hover:border-purple-200 transition-all duration-300 text-decoration-none">
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 bg-purple-50 rounded-xl text-purple-600 group-hover:bg-purple-100 group-hover:scale-110 transition-all duration-300 mb-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Relatórios</h3>
                            <p class="text-sm text-gray-500">Visualizar relatórios detalhados</p>
                        </div>
                    </a>
                <?php else: ?>
                    <!-- My Evaluations -->
                    <a href="<?php echo APP_URL; ?>/pages/minhas-avaliacoes.php" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-lg hover:border-blue-200 transition-all duration-300 text-decoration-none">
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-50 rounded-xl text-blue-600 group-hover:bg-blue-100 group-hover:scale-110 transition-all duration-300 mb-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Minhas Avaliações</h3>
                            <p class="text-sm text-gray-500">Ver histórico de avaliações</p>
                        </div>
                    </a>

                    <!-- My Workouts -->
                    <a href="<?php echo APP_URL; ?>/pages/meus-treinos.php" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-lg hover:border-green-200 transition-all duration-300 text-decoration-none">
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 bg-green-50 rounded-xl text-green-600 group-hover:bg-green-100 group-hover:scale-110 transition-all duration-300 mb-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Meus Treinos</h3>
                            <p class="text-sm text-gray-500">Acessar treinos ativos</p>
                        </div>
                    </a>

                    <!-- My Profile -->
                    <a href="<?php echo APP_URL; ?>/pages/profile.php" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-lg hover:border-yellow-200 transition-all duration-300 text-decoration-none">
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 bg-yellow-50 rounded-xl text-yellow-600 group-hover:bg-yellow-100 group-hover:scale-110 transition-all duration-300 mb-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Meu Perfil</h3>
                            <p class="text-sm text-gray-500">Atualizar dados pessoais</p>
                        </div>
                    </a>

                    <!-- Progress -->
                    <a href="<?php echo APP_URL; ?>/pages/progresso.php" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-lg hover:border-purple-200 transition-all duration-300 text-decoration-none">
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 bg-purple-50 rounded-xl text-purple-600 group-hover:bg-purple-100 group-hover:scale-110 transition-all duration-300 mb-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Meu Progresso</h3>
                            <p class="text-sm text-gray-500">Acompanhar evolução</p>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" data-aos="fade-up" data-aos-delay="300">
            <div class="px-6 py-4 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Atividades Recentes
                    </h2>
                    <span class="text-sm text-gray-500 hidden sm:block">Últimas ações no sistema</span>
                </div>
            </div>
            
            <div class="p-6">
                <?php if (!empty($atividades_recentes)): ?>
                    <div class="space-y-4">
                        <?php foreach ($atividades_recentes as $index => $atividade): ?>
                            <div class="flex items-start space-x-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors duration-200" data-aos="fade-up" data-aos-delay="<?php echo 400 + ($index * 50); ?>">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900">
                                                <?php if (hasPermission('professor') && isset($atividade['usuario_nome'])): ?>
                                                    <?php echo htmlspecialchars($atividade['usuario_nome']); ?>
                                                <?php else: ?>
                                                    Você
                                                <?php endif; ?>
                                            </p>
                                            <p class="text-sm text-gray-600 mt-1">
                                                <?php 
                                                $acoes = [
                                                    'login' => 'fez login no sistema',
                                                    'logout' => 'saiu do sistema',
                                                    'page_access' => str_replace('Acessou página: ', 'acessou ', $atividade['descricao']),
                                                    'login_failed' => 'tentou fazer login (falhou)',
                                                    'user_created' => 'foi criado no sistema',
                                                    'user_updated' => 'atualizou o perfil'
                                                ];
                                                echo $acoes[$atividade['acao']] ?? $atividade['descricao'];
                                                ?>
                                            </p>
                                        </div>
                                        <div class="flex-shrink-0 ml-4">
                                            <span class="text-xs text-gray-500 bg-white px-2 py-1 rounded-full">
                                                <?php echo date('d/m H:i', strtotime($atividade['created_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhuma atividade ainda</h3>
                        <p class="text-gray-500 max-w-sm mx-auto">
                            Suas atividades no sistema aparecerão aqui. Comece explorando as funcionalidades disponíveis!
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Barra de navegação removida - utilizando apenas a do header.php -->

<!-- Premium Scripts -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
// Initialize AOS animations
AOS.init({
    duration: 800,
    easing: 'ease-in-out',
    once: true,
    mirror: false
});

// Mobile menu functionality
function toggleMobileMenu() {
    // Implementation for mobile menu toggle
    console.log('Mobile menu toggled');
}

// Dashboard functionality
document.addEventListener('DOMContentLoaded', function() {
    // Real-time stats update (every 30 seconds)
    setInterval(updateStats, 30000);
    
    // Statistics animation counters
    animateCounters();
    
    // Card hover effects
    initializeCardEffects();
    
    // Add mobile padding for fixed bottom navigation
    if (window.innerWidth < 1024) {
        document.body.style.paddingBottom = '4rem';
    }
});

function updateStats() {
    // AJAX call to update statistics
    fetch('<?php echo APP_URL; ?>/api/dashboard-stats.php')
        .then(response => response.json())
        .then(data => {
            // Update statistics with smooth animation
            Object.keys(data).forEach(key => {
                const element = document.getElementById(`stat-${key}`);
                if (element) {
                    animateValue(element, parseInt(element.textContent), data[key], 1000);
                }
            });
        })
        .catch(error => console.log('Stats update:', error));
}

function animateCounters() {
    const counters = document.querySelectorAll('[data-counter]');
    counters.forEach(counter => {
        const target = parseInt(counter.textContent);
        animateValue(counter, 0, target, 2000);
    });
}

function animateValue(element, start, end, duration) {
    const range = end - start;
    const minTimer = 50;
    let stepTime = Math.abs(Math.floor(duration / range));
    stepTime = Math.max(stepTime, minTimer);
    
    const startTime = new Date().getTime();
    const endTime = startTime + duration;
    
    function run() {
        const now = new Date().getTime();
        const remaining = Math.max((endTime - now) / duration, 0);
        const value = Math.round(end - (remaining * range));
        element.textContent = value;
        
        if (value == end) {
            clearInterval(timer);
        }
    }
    
    const timer = setInterval(run, stepTime);
    run();
}

function initializeCardEffects() {
    const cards = document.querySelectorAll('.group');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

// Service Worker for offline functionality
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('<?php echo APP_URL; ?>/sw.js')
            .then(function(registration) {
                console.log('SW registered: ', registration);
            }, function(registrationError) {
                console.log('SW registration failed: ', registrationError);
            });
    });
}
</script>

<?php include 'includes/footer.php'; ?>
