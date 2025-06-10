<?php
/**
 * SMARTBIOFIT - Página de Exercícios
 * Gerenciamento da biblioteca de exercícios
 */

require_once '../config.php';
require_once '../database.php';
// Check session is handled by config.php

// Verificar se o usuário é professor ou admin
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'professor' && $_SESSION['user_type'] !== 'admin')) {
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

$db = Database::getInstance();
$professor_id = $_SESSION['user_id'];

// Processar ações
$action = $_GET['action'] ?? '';
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        try {
            $nome = trim($_POST['nome']);
            $categoria = $_POST['categoria'];
            $grupo_muscular = $_POST['grupo_muscular'];
            $equipamento = trim($_POST['equipamento_necessario'] ?? '');
            $descricao = trim($_POST['descricao_tecnica'] ?? '');
            $dicas = trim($_POST['dicas_execucao'] ?? '');
            $contraindicacoes = trim($_POST['contraindicacoes'] ?? '');
            $nivel = $_POST['nivel_dificuldade'];
            $video_url = trim($_POST['video_url'] ?? '');
            $tempo_execucao = (int)($_POST['tempo_execucao_sugerido'] ?? 0);

            if (empty($nome) || empty($categoria) || empty($grupo_muscular)) {
                throw new Exception('Nome, categoria e grupo muscular são obrigatórios.');
            }

            $sql = "INSERT INTO exercicios (nome, categoria, grupo_muscular, equipamento_necessario, descricao_tecnica, dicas_execucao, contraindicacoes, nivel_dificuldade, video_url, tempo_execucao_sugerido, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [$nome, $categoria, $grupo_muscular, $equipamento, $descricao, $dicas, $contraindicacoes, $nivel, $video_url, $tempo_execucao, $professor_id];
            
            $db->execute($sql, $params);
            $message = 'Exercício adicionado com sucesso!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Erro ao adicionar exercício: ' . $e->getMessage();
            $message_type = 'error';
        }
    } elseif ($action === 'edit') {
        try {
            $id = (int)$_POST['id'];
            $nome = trim($_POST['nome']);
            $categoria = $_POST['categoria'];
            $grupo_muscular = $_POST['grupo_muscular'];
            $equipamento = trim($_POST['equipamento_necessario'] ?? '');
            $descricao = trim($_POST['descricao_tecnica'] ?? '');
            $dicas = trim($_POST['dicas_execucao'] ?? '');
            $contraindicacoes = trim($_POST['contraindicacoes'] ?? '');
            $nivel = $_POST['nivel_dificuldade'];
            $video_url = trim($_POST['video_url'] ?? '');
            $tempo_execucao = (int)($_POST['tempo_execucao_sugerido'] ?? 0);

            if (empty($nome) || empty($categoria) || empty($grupo_muscular)) {
                throw new Exception('Nome, categoria e grupo muscular são obrigatórios.');
            }

            $sql = "UPDATE exercicios SET nome = ?, categoria = ?, grupo_muscular = ?, equipamento_necessario = ?, descricao_tecnica = ?, dicas_execucao = ?, contraindicacoes = ?, nivel_dificuldade = ?, video_url = ?, tempo_execucao_sugerido = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND created_by = ?";
            $params = [$nome, $categoria, $grupo_muscular, $equipamento, $descricao, $dicas, $contraindicacoes, $nivel, $video_url, $tempo_execucao, $id, $professor_id];
            
            $db->execute($sql, $params);
            $message = 'Exercício atualizado com sucesso!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Erro ao atualizar exercício: ' . $e->getMessage();
            $message_type = 'error';
        }
    } elseif ($action === 'delete') {
        try {
            $id = (int)$_POST['id'];
              // Verificar se o exercício está sendo usado em algum treino
            $count_uso = $db->fetch("SELECT COUNT(*) as total FROM treino_exercicios WHERE exercicio_id = ?", [$id])['total'];
            
            if ($count_uso > 0) {
                // Desativar ao invés de deletar
                $sql = "UPDATE exercicios SET ativo = FALSE WHERE id = ? AND created_by = ?";
                $db->execute($sql, [$id, $professor_id]);
                $message = 'Exercício desativado com sucesso (estava sendo usado em treinos).';
            } else {
                // Deletar completamente
                $sql = "DELETE FROM exercicios WHERE id = ? AND created_by = ?";
                $db->execute($sql, [$id, $professor_id]);
                $message = 'Exercício removido com sucesso!';
            }
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Erro ao remover exercício: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Buscar exercícios
$search = $_GET['search'] ?? '';
$categoria_filter = $_GET['categoria'] ?? '';
$grupo_filter = $_GET['grupo'] ?? '';
$nivel_filter = $_GET['nivel'] ?? '';

$where_conditions = ["created_by = ?"];
$params = [$professor_id];

if (!empty($search)) {
    $where_conditions[] = "(nome LIKE ? OR descricao_tecnica LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($categoria_filter)) {
    $where_conditions[] = "categoria = ?";
    $params[] = $categoria_filter;
}

if (!empty($grupo_filter)) {
    $where_conditions[] = "grupo_muscular = ?";
    $params[] = $grupo_filter;
}

if (!empty($nivel_filter)) {
    $where_conditions[] = "nivel_dificuldade = ?";
    $params[] = $nivel_filter;
}

$where_clause = implode(" AND ", $where_conditions);
$sql = "SELECT * FROM exercicios WHERE $where_clause ORDER BY categoria, grupo_muscular, nome";
$exercicios = $db->fetchAll($sql, $params);

// Buscar exercício específico para edição
$exercicio_edit = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $exercicio_edit = $db->fetch("SELECT * FROM exercicios WHERE id = ? AND created_by = ?", [$id, $professor_id]);
}

include '../includes/header.php';
?>

<!-- Premium Mobile Exercise Page -->
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
                            <span class="text-sm font-medium text-gray-500">Biblioteca de Exercícios</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- Header Content -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <div class="h-12 w-12 bg-gradient-to-r from-cobalt-500 to-cobalt-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-dumbbell text-white text-xl"></i>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Biblioteca de Exercícios</h1>
                        <p class="text-sm text-gray-500">Crie e gerencie exercícios personalizados para seus alunos</p>
                    </div>
                </div>
                
                <div class="mt-4 sm:mt-0">
                    <button type="button" onclick="showAddModal()" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-gradient-to-r from-cobalt-600 to-cobalt-700 hover:from-cobalt-700 hover:to-cobalt-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 transition-all duration-200">
                        <i class="fas fa-plus -ml-1 mr-2"></i>
                        Adicionar Exercício
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($message): ?>
    <div class="mx-4 mt-4" data-aos="fade-in">
        <div class="bg-<?php echo $message_type === 'success' ? 'green' : ($message_type === 'error' ? 'red' : 'blue'); ?>-50 border border-<?php echo $message_type === 'success' ? 'green' : ($message_type === 'error' ? 'red' : 'blue'); ?>-200 text-<?php echo $message_type === 'success' ? 'green' : ($message_type === 'error' ? 'red' : 'blue'); ?>-800 px-4 py-3 rounded-lg shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'error' ? 'exclamation-triangle' : 'info-circle'); ?> mr-3"></i>
                <span class="font-medium"><?php echo htmlspecialchars($message); ?></span>
                <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-<?php echo $message_type === 'success' ? 'green' : ($message_type === 'error' ? 'red' : 'blue'); ?>-50 text-<?php echo $message_type === 'success' ? 'green' : ($message_type === 'error' ? 'red' : 'blue'); ?>-500 rounded-lg focus:ring-2 focus:ring-<?php echo $message_type === 'success' ? 'green' : ($message_type === 'error' ? 'red' : 'blue'); ?>-400 p-1.5 hover:bg-<?php echo $message_type === 'success' ? 'green' : ($message_type === 'error' ? 'red' : 'blue'); ?>-200 inline-flex h-8 w-8" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="px-4 mt-6" data-aos="fade-up">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Exercícios Ativos -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-dumbbell text-blue-600"></i>
                        </div>
                    </div>                    <div class="ml-3 flex-1">
                        <p class="text-2xl font-bold text-gray-900"><?php echo $db->fetch("SELECT COUNT(*) as total FROM exercicios WHERE created_by = ? AND ativo = TRUE", [$professor_id])['total']; ?></p>
                        <p class="text-xs text-gray-500 font-medium">Exercícios Ativos</p>
                    </div>
                </div>
            </div>

            <!-- Categorias -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-tags text-green-600"></i>
                        </div>
                    </div>                    <div class="ml-3 flex-1">
                        <p class="text-2xl font-bold text-gray-900"><?php echo $db->fetch("SELECT COUNT(DISTINCT categoria) as total FROM exercicios WHERE created_by = ? AND ativo = TRUE", [$professor_id])['total']; ?></p>
                        <p class="text-xs text-gray-500 font-medium">Categorias</p>
                    </div>
                </div>
            </div>

            <!-- Usos em Treinos -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-yellow-600"></i>
                        </div>
                    </div>                    <div class="ml-3 flex-1">
                        <p class="text-2xl font-bold text-gray-900"><?php echo $db->fetch("SELECT COUNT(*) as total FROM treino_exercicios te JOIN exercicios e ON te.exercicio_id = e.id WHERE e.created_by = ?", [$professor_id])['total']; ?></p>
                        <p class="text-xs text-gray-500 font-medium">Usos em Treinos</p>
                    </div>
                </div>
            </div>

            <!-- Desativados -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-pause-circle text-red-600"></i>
                        </div>
                    </div>                    <div class="ml-3 flex-1">
                        <p class="text-2xl font-bold text-gray-900"><?php echo $db->fetch("SELECT COUNT(*) as total FROM exercicios WHERE created_by = ? AND ativo = FALSE", [$professor_id])['total']; ?></p>
                        <p class="text-xs text-gray-500 font-medium">Desativados</p>
                    </div>
                </div>
            </div>    </div>

    <!-- Filters and Search -->
    <div class="px-4 mt-6" data-aos="fade-up" data-aos-delay="100">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-filter mr-2 text-cobalt-600"></i>
                    Filtros de Busca
                </h3>
            </div>            <div class="p-6">
                <form method="GET" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Search Field -->
                        <div class="space-y-1">
                            <label for="search" class="block text-sm font-medium text-gray-700">Buscar</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" id="search" name="search"
                                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 sm:text-sm transition-colors text-gray-900"
                                       value="<?php echo htmlspecialchars($search); ?>"
                                       placeholder="Nome ou descrição...">
                            </div>
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="space-y-1">
                            <label for="categoria" class="block text-sm font-medium text-gray-700">Categoria</label>
                            <select id="categoria" name="categoria"
                                    class="block w-full py-2 pl-3 pr-10 border border-gray-300 bg-white rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 sm:text-sm transition-colors text-gray-900">
                                <option value="">Todas as categorias</option>
                                <option value="cardio" <?php echo $categoria_filter === 'cardio' ? 'selected' : ''; ?>>❤️ Cardio</option>
                                <option value="musculacao" <?php echo $categoria_filter === 'musculacao' ? 'selected' : ''; ?>>💪 Musculação</option>
                                <option value="funcional" <?php echo $categoria_filter === 'funcional' ? 'selected' : ''; ?>>⚡ Funcional</option>
                                <option value="flexibilidade" <?php echo $categoria_filter === 'flexibilidade' ? 'selected' : ''; ?>>🧘 Flexibilidade</option>
                                <option value="hiit" <?php echo $categoria_filter === 'hiit' ? 'selected' : ''; ?>>🔥 HIIT</option>
                                <option value="pilates" <?php echo $categoria_filter === 'pilates' ? 'selected' : ''; ?>>🎯 Pilates</option>
                                <option value="yoga" <?php echo $categoria_filter === 'yoga' ? 'selected' : ''; ?>>🕉️ Yoga</option>
                                <option value="crossfit" <?php echo $categoria_filter === 'crossfit' ? 'selected' : ''; ?>>🏋️ CrossFit</option>
                            </select>
                        </div>

                        <!-- Muscle Group Filter -->
                        <div class="space-y-1">
                            <label for="grupo" class="block text-sm font-medium text-gray-700">Grupo Muscular</label>
                            <select id="grupo" name="grupo"
                                    class="block w-full py-2 pl-3 pr-10 border border-gray-300 bg-white rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 sm:text-sm transition-colors text-gray-900">
                                <option value="">Todos os grupos</option>
                                <option value="peito" <?php echo $grupo_filter === 'peito' ? 'selected' : ''; ?>>💚 Peito</option>
                                <option value="costas" <?php echo $grupo_filter === 'costas' ? 'selected' : ''; ?>>🟢 Costas</option>
                                <option value="ombros" <?php echo $grupo_filter === 'ombros' ? 'selected' : ''; ?>>🔵 Ombros</option>
                                <option value="biceps" <?php echo $grupo_filter === 'biceps' ? 'selected' : ''; ?>>🟡 Bíceps</option>
                                <option value="triceps" <?php echo $grupo_filter === 'triceps' ? 'selected' : ''; ?>>🟠 Tríceps</option>
                                <option value="antebraco" <?php echo $grupo_filter === 'antebraco' ? 'selected' : ''; ?>>⚫ Antebraço</option>
                                <option value="quadriceps" <?php echo $grupo_filter === 'quadriceps' ? 'selected' : ''; ?>>🔴 Quadríceps</option>
                                <option value="isquiotibiais" <?php echo $grupo_filter === 'isquiotibiais' ? 'selected' : ''; ?>>🟤 Isquiotibiais</option>
                                <option value="gluteos" <?php echo $grupo_filter === 'gluteos' ? 'selected' : ''; ?>>🟣 Glúteos</option>
                                <option value="panturrilha" <?php echo $grupo_filter === 'panturrilha' ? 'selected' : ''; ?>>⚪ Panturrilha</option>
                                <option value="core" <?php echo $grupo_filter === 'core' ? 'selected' : ''; ?>>🟨 Core</option>
                                <option value="corpo_inteiro" <?php echo $grupo_filter === 'corpo_inteiro' ? 'selected' : ''; ?>>🌈 Corpo Inteiro</option>
                            </select>
                        </div>

                        <!-- Level Filter -->
                        <div class="space-y-1">
                            <label for="nivel" class="block text-sm font-medium text-gray-700">Nível</label>
                            <select id="nivel" name="nivel"
                                    class="block w-full py-2 pl-3 pr-10 border border-gray-300 bg-white rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 sm:text-sm transition-colors text-gray-900">
                                <option value="">Todos os níveis</option>
                                <option value="iniciante" <?php echo $nivel_filter === 'iniciante' ? 'selected' : ''; ?>>🟢 Iniciante</option>
                                <option value="intermediario" <?php echo $nivel_filter === 'intermediario' ? 'selected' : ''; ?>>🟡 Intermediário</option>
                                <option value="avancado" <?php echo $nivel_filter === 'avancado' ? 'selected' : ''; ?>>🔴 Avançado</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Filter Actions -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-gradient-to-r from-cobalt-600 to-cobalt-700 hover:from-cobalt-700 hover:to-cobalt-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 transition-all duration-200">
                            <i class="fas fa-search -ml-1 mr-2"></i>
                            Filtrar
                        </button>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>"
                           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 transition-all duration-200">
                            <i class="fas fa-times -ml-1 mr-2"></i>
                            Limpar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>    <!-- Exercise List -->
    <div class="px-4 mt-6 pb-6" data-aos="fade-up" data-aos-delay="300">
        <div class="mb-6">
            <h3 class="text-xl font-bold text-gray-900 flex items-center">
                <i class="fas fa-list-ul mr-3 text-cobalt-600"></i>
                Exercícios Cadastrados
                <span class="ml-2 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                    <?php echo count($exercicios); ?>
                </span>
            </h3>
        </div>
        
        <?php if (empty($exercicios)): ?>
        <!-- Empty State -->
        <div class="text-center py-12 bg-white rounded-xl border border-gray-200">
            <div class="mx-auto h-24 w-24 text-gray-400 mb-6">
                <i class="fas fa-dumbbell text-6xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum exercício cadastrado</h3>
            <p class="text-gray-500 mb-6 max-w-md mx-auto">
                Comece criando seus primeiros exercícios para a biblioteca.
            </p>
            <button type="button" onclick="showAddModal()" 
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-cobalt-600 to-cobalt-700 hover:from-cobalt-700 hover:to-cobalt-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 transition-all duration-200">
                <i class="fas fa-plus mr-2"></i>
                Criar Primeiro Exercício
            </button>
        </div>
        <?php else: ?>
        <!-- Exercise Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($exercicios as $exercicio): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-200 <?php echo !$exercicio['ativo'] ? 'opacity-60' : ''; ?>" 
                 data-aos="zoom-in" data-aos-delay="<?php echo 100 + (array_search($exercicio, $exercicios) * 50); ?>">
                
                <!-- Card Header -->
                <div class="p-6 pb-4">
                    <div class="flex items-start justify-between mb-4">
                        <h4 class="text-lg font-bold text-gray-900 line-clamp-2 flex-1 mr-2">
                            <?php echo htmlspecialchars($exercicio['nome']); ?>
                        </h4>
                        <?php if (!$exercicio['ativo']): ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                <i class="fas fa-pause mr-1"></i>
                                Inativo
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Badges -->
                    <div class="flex flex-wrap gap-2 mb-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-cobalt-100 text-cobalt-800">
                            <?php 
                            $categoria_icons = [
                                'cardio' => '❤️',
                                'musculacao' => '💪',
                                'funcional' => '⚡',
                                'flexibilidade' => '🧘',
                                'hiit' => '🔥',
                                'pilates' => '🎯',
                                'yoga' => '🕉️',
                                'crossfit' => '🏋️'
                            ];
                            echo ($categoria_icons[$exercicio['categoria']] ?? '🏋️') . ' ' . ucfirst($exercicio['categoria']);
                            ?>
                        </span>
                        
                        <?php 
                        $nivel_colors = [
                            'iniciante' => 'bg-green-100 text-green-800',
                            'intermediario' => 'bg-yellow-100 text-yellow-800',
                            'avancado' => 'bg-red-100 text-red-800'
                        ];
                        $nivel_icons = [
                            'iniciante' => '🟢',
                            'intermediario' => '🟡',
                            'avancado' => '🔴'
                        ];
                        ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?php echo $nivel_colors[$exercicio['nivel_dificuldade']] ?? 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo ($nivel_icons[$exercicio['nivel_dificuldade']] ?? '') . ' ' . ucfirst($exercicio['nivel_dificuldade']); ?>
                        </span>
                    </div>
                    
                    <!-- Info Items -->
                    <div class="space-y-2 mb-4">
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-bullseye text-cobalt-600 mr-2 w-4"></i>
                            <span><strong>Grupo:</strong> <?php echo ucfirst(str_replace('_', ' ', $exercicio['grupo_muscular'])); ?></span>
                        </div>
                        
                        <?php if ($exercicio['equipamento_necessario']): ?>
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-dumbbell text-gray-400 mr-2 w-4"></i>
                            <span><strong>Equipamento:</strong> <?php echo htmlspecialchars($exercicio['equipamento_necessario']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($exercicio['tempo_execucao_sugerido'] > 0): ?>
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-clock text-gray-400 mr-2 w-4"></i>
                            <span><strong>Tempo:</strong> <?php echo gmdate('i:s', $exercicio['tempo_execucao_sugerido']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($exercicio['descricao_tecnica']): ?>
                    <!-- Description -->
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 line-clamp-2"><?php echo htmlspecialchars(substr($exercicio['descricao_tecnica'], 0, 100)); ?><?php echo strlen($exercicio['descricao_tecnica']) > 100 ? '...' : ''; ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($exercicio['video_url']): ?>
                    <!-- Video Link -->
                    <div class="mb-4">
                        <a href="<?php echo htmlspecialchars($exercicio['video_url']); ?>" target="_blank" 
                           class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 focus:ring-4 focus:outline-none focus:ring-blue-300 transition-colors">
                            <i class="fas fa-play mr-2"></i>
                            Ver Vídeo
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Card Actions -->
                <div class="px-6 py-4 bg-gray-50 rounded-b-xl border-t border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex space-x-2">
                            <button type="button" onclick="showDetailsModal(<?php echo $exercicio['id']; ?>)" 
                                    class="inline-flex items-center p-2 border border-gray-300 rounded-lg text-gray-400 hover:text-gray-600 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 transition-colors" 
                                    title="Ver Detalhes">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                        </div>
                        
                        <div class="flex space-x-2">
                            <button type="button" onclick="showEditModal(<?php echo $exercicio['id']; ?>)" 
                                    class="inline-flex items-center p-2 border border-gray-300 rounded-lg text-gray-400 hover:text-orange-600 hover:border-orange-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors" 
                                    title="Editar">
                                <i class="fas fa-edit text-sm"></i>
                            </button>
                            
                            <button type="button" onclick="confirmDelete(<?php echo $exercicio['id']; ?>, '<?php echo addslashes($exercicio['nome']); ?>')" 
                                    class="inline-flex items-center p-2 border border-gray-300 rounded-lg text-gray-400 hover:text-red-600 hover:border-red-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors" 
                                    title="Excluir">
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Exercise Modal -->
<div id="exercicioModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative w-full max-w-6xl max-h-full mx-auto">
        <!-- Modal content -->
        <div class="relative bg-white rounded-2xl shadow-2xl border border-gray-200">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 rounded-t-2xl bg-gradient-to-r from-cobalt-50 to-cobalt-100">
                <h3 id="modalTitle" class="text-xl font-bold text-gray-900 flex items-center">
                    <div class="h-8 w-8 bg-gradient-to-r from-cobalt-500 to-cobalt-600 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-plus text-white text-sm"></i>
                    </div>
                    Adicionar Exercício
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center transition-colors duration-200" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                    <span class="sr-only">Fechar modal</span>
                </button>
            </div>
            
            <!-- Modal body -->
            <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
                <form id="exercicioForm" method="POST" class="space-y-6">
                    <input type="hidden" id="exercicioId" name="id">
                    <input type="hidden" id="formAction" name="action" value="add">
                    
                    <!-- Informações Básicas -->
                    <div class="bg-gray-50 rounded-xl p-6" data-aos="fade-up">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <div class="h-6 w-6 bg-blue-100 rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-info-circle text-blue-600 text-sm"></i>
                            </div>
                            Informações Básicas
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="nome" class="block mb-2 text-sm font-medium text-gray-900">
                                    Nome do Exercício <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="nome" name="nome" required
                                       class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3 transition-colors duration-200" 
                                       placeholder="Digite o nome do exercício...">
                            </div>
                            
                            <div>
                                <label for="categoria" class="block mb-2 text-sm font-medium text-gray-900">
                                    Categoria <span class="text-red-500">*</span>
                                </label>
                                <select id="categoria" name="categoria" required
                                        class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3 transition-colors duration-200">
                                    <option value="">Selecione...</option>
                                    <option value="cardio">Cardio</option>
                                    <option value="musculacao">Musculação</option>
                                    <option value="funcional">Funcional</option>
                                    <option value="flexibilidade">Flexibilidade</option>
                                    <option value="hiit">HIIT</option>
                                    <option value="pilates">Pilates</option>
                                    <option value="yoga">Yoga</option>
                                    <option value="crossfit">CrossFit</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="grupo_muscular" class="block mb-2 text-sm font-medium text-gray-900">
                                    Grupo Muscular <span class="text-red-500">*</span>
                                </label>
                                <select id="grupo_muscular" name="grupo_muscular" required
                                        class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3 transition-colors duration-200">
                                    <option value="">Selecione...</option>
                                    <option value="peito">Peito</option>
                                    <option value="costas">Costas</option>
                                    <option value="ombros">Ombros</option>
                                    <option value="biceps">Bíceps</option>
                                    <option value="triceps">Tríceps</option>
                                    <option value="antebraco">Antebraço</option>
                                    <option value="quadriceps">Quadríceps</option>
                                    <option value="isquiotibiais">Isquiotibiais</option>
                                    <option value="gluteos">Glúteos</option>
                                    <option value="panturrilha">Panturrilha</option>
                                    <option value="core">Core</option>
                                    <option value="corpo_inteiro">Corpo Inteiro</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="nivel_dificuldade" class="block mb-2 text-sm font-medium text-gray-900">
                                    Nível de Dificuldade
                                </label>
                                <select id="nivel_dificuldade" name="nivel_dificuldade"
                                        class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3 transition-colors duration-200">
                                    <option value="iniciante">Iniciante</option>
                                    <option value="intermediario" selected>Intermediário</option>
                                    <option value="avancado">Avançado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Equipamento e Tempo -->
                    <div class="bg-gray-50 rounded-xl p-6" data-aos="fade-up" data-aos-delay="100">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <div class="h-6 w-6 bg-green-100 rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-cogs text-green-600 text-sm"></i>
                            </div>
                            Equipamento e Tempo
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="equipamento_necessario" class="block mb-2 text-sm font-medium text-gray-900">
                                    Equipamento Necessário
                                </label>
                                <input type="text" id="equipamento_necessario" name="equipamento_necessario"
                                       class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3 transition-colors duration-200" 
                                       placeholder="Ex: Halteres, barra, peso corporal...">
                            </div>
                            
                            <div>
                                <label for="tempo_execucao_sugerido" class="block mb-2 text-sm font-medium text-gray-900">
                                    Tempo Sugerido (segundos)
                                </label>
                                <input type="number" id="tempo_execucao_sugerido" name="tempo_execucao_sugerido" min="0"
                                       class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3 transition-colors duration-200" 
                                       placeholder="0 = não aplicável">
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <label for="video_url" class="block mb-2 text-sm font-medium text-gray-900">
                                URL do Vídeo
                            </label>
                            <input type="url" id="video_url" name="video_url"
                                   class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3 transition-colors duration-200" 
                                   placeholder="https://youtube.com/watch?v=...">
                        </div>
                    </div>
                    
                    <!-- Informações Detalhadas -->
                    <div class="bg-gray-50 rounded-xl p-6" data-aos="fade-up" data-aos-delay="200">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <div class="h-6 w-6 bg-purple-100 rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-file-alt text-purple-600 text-sm"></i>
                            </div>
                            Informações Detalhadas
                        </h4>
                        
                        <div class="space-y-6">
                            <div>
                                <label for="descricao_tecnica" class="block mb-2 text-sm font-medium text-gray-900">
                                    Descrição Técnica
                                </label>
                                <textarea id="descricao_tecnica" name="descricao_tecnica" rows="3"
                                          class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3 transition-colors duration-200" 
                                          placeholder="Descrição detalhada da execução do exercício..."></textarea>
                            </div>
                            
                            <div>
                                <label for="dicas_execucao" class="block mb-2 text-sm font-medium text-gray-900">
                                    Dicas de Execução
                                </label>
                                <textarea id="dicas_execucao" name="dicas_execucao" rows="3"
                                          class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3 transition-colors duration-200" 
                                          placeholder="Dicas importantes para execução correta..."></textarea>
                            </div>
                            
                            <div>
                                <label for="contraindicacoes" class="block mb-2 text-sm font-medium text-gray-900">
                                    Contraindicações
                                </label>
                                <textarea id="contraindicacoes" name="contraindicacoes" rows="2"
                                          class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3 transition-colors duration-200" 
                                          placeholder="Situações em que o exercício deve ser evitado..."></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Modal footer -->
            <div class="flex items-center justify-end p-6 space-x-3 border-t border-gray-200 rounded-b-2xl bg-gray-50">
                <button type="button" onclick="closeModal()"
                        class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 transition-colors duration-200">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </button>
                <button type="submit" form="exercicioForm"
                        class="text-white bg-gradient-to-r from-cobalt-500 to-cobalt-600 hover:from-cobalt-600 hover:to-cobalt-700 focus:ring-4 focus:outline-none focus:ring-cobalt-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-all duration-200 shadow-lg hover:shadow-xl">
                    <i class="fas fa-save mr-2"></i>
                    Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Exercise Details Modal -->
<div id="detailsModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative w-full max-w-4xl max-h-full mx-auto">
        <!-- Modal content -->
        <div class="relative bg-white rounded-2xl shadow-2xl border border-gray-200">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 rounded-t-2xl bg-gradient-to-r from-blue-50 to-blue-100">
                <h3 class="text-xl font-bold text-gray-900 flex items-center">
                    <div class="h-8 w-8 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-eye text-white text-sm"></i>
                    </div>
                    Detalhes do Exercício
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center transition-colors duration-200" onclick="closeDetailsModal()">
                    <i class="fas fa-times"></i>
                    <span class="sr-only">Fechar modal</span>
                </button>
            </div>
            
            <!-- Modal body -->
            <div id="detailsContent" class="p-6 max-h-[70vh] overflow-y-auto">
                <!-- Content loaded via JavaScript -->
            </div>
        </div>
    </div>
</div>

<script>
// Exercise data for JavaScript use
const exercicios = <?php echo json_encode($exercicios); ?>;

function showAddModal() {
    const modal = document.getElementById('exercicioModal');
    const modalTitle = document.getElementById('modalTitle');
    const iconDiv = modalTitle.querySelector('div');
    
    modalTitle.innerHTML = `
        <div class="h-8 w-8 bg-gradient-to-r from-cobalt-500 to-cobalt-600 rounded-lg flex items-center justify-center mr-3">
            <i class="fas fa-plus text-white text-sm"></i>
        </div>
        Adicionar Exercício
    `;
    
    document.getElementById('formAction').value = 'add';
    document.getElementById('exercicioForm').reset();
    document.getElementById('exercicioId').value = '';
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Add animation
    setTimeout(() => {
        modal.querySelector('.relative').classList.add('animate__animated', 'animate__fadeInUp');
    }, 10);
}

function showEditModal(id) {
    const exercicio = exercicios.find(e => e.id == id);
    if (!exercicio) return;
    
    const modal = document.getElementById('exercicioModal');
    const modalTitle = document.getElementById('modalTitle');
    
    modalTitle.innerHTML = `
        <div class="h-8 w-8 bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg flex items-center justify-center mr-3">
            <i class="fas fa-edit text-white text-sm"></i>
        </div>
        Editar Exercício
    `;
    
    document.getElementById('formAction').value = 'edit';
    document.getElementById('exercicioId').value = exercicio.id;
    
    // Fill form
    document.getElementById('nome').value = exercicio.nome;
    document.getElementById('categoria').value = exercicio.categoria;
    document.getElementById('grupo_muscular').value = exercicio.grupo_muscular;
    document.getElementById('nivel_dificuldade').value = exercicio.nivel_dificuldade;
    document.getElementById('equipamento_necessario').value = exercicio.equipamento_necessario || '';
    document.getElementById('tempo_execucao_sugerido').value = exercicio.tempo_execucao_sugerido || '';
    document.getElementById('video_url').value = exercicio.video_url || '';
    document.getElementById('descricao_tecnica').value = exercicio.descricao_tecnica || '';
    document.getElementById('dicas_execucao').value = exercicio.dicas_execucao || '';
    document.getElementById('contraindicacoes').value = exercicio.contraindicacoes || '';
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Add animation
    setTimeout(() => {
        modal.querySelector('.relative').classList.add('animate__animated', 'animate__fadeInUp');
    }, 10);
}

function showDetailsModal(id) {
    const exercicio = exercicios.find(e => e.id == id);
    if (!exercicio) return;
    
    const content = `
        <div class="space-y-6">
            <!-- Header with badges -->
            <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl p-6">
                <h3 class="text-2xl font-bold text-gray-900 mb-4">${exercicio.nome}</h3>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        ${exercicio.categoria}
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        ${exercicio.nivel_dificuldade}
                    </span>
                    ${!exercicio.ativo ? '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">Desativado</span>' : ''}
                </div>
            </div>
            
            <!-- Basic info -->
            <div class="bg-gray-50 rounded-xl p-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <div class="h-6 w-6 bg-blue-100 rounded-lg flex items-center justify-center mr-2">
                        <i class="fas fa-info-circle text-blue-600 text-sm"></i>
                    </div>
                    Informações Básicas
                </h4>
                <div class="space-y-3">
                    <div class="flex items-center text-gray-700">
                        <i class="fas fa-bullseye w-5 h-5 mr-3 text-blue-500"></i>
                        <span><strong>Grupo Muscular:</strong> ${exercicio.grupo_muscular.replace('_', ' ')}</span>
                    </div>
                    ${exercicio.equipamento_necessario ? `
                        <div class="flex items-center text-gray-700">
                            <i class="fas fa-dumbbell w-5 h-5 mr-3 text-green-500"></i>
                            <span><strong>Equipamento:</strong> ${exercicio.equipamento_necessario}</span>
                        </div>
                    ` : ''}
                    ${exercicio.tempo_execucao_sugerido > 0 ? `
                        <div class="flex items-center text-gray-700">
                            <i class="fas fa-clock w-5 h-5 mr-3 text-orange-500"></i>
                            <span><strong>Tempo Sugerido:</strong> ${Math.floor(exercicio.tempo_execucao_sugerido / 60)}:${(exercicio.tempo_execucao_sugerido % 60).toString().padStart(2, '0')}</span>
                        </div>
                    ` : ''}
                </div>
            </div>
            
            ${exercicio.descricao_tecnica ? `
                <div class="bg-gray-50 rounded-xl p-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <div class="h-6 w-6 bg-purple-100 rounded-lg flex items-center justify-center mr-2">
                            <i class="fas fa-clipboard-list text-purple-600 text-sm"></i>
                        </div>
                        Descrição Técnica
                    </h4>
                    <div class="text-gray-700 leading-relaxed">
                        <p>${exercicio.descricao_tecnica}</p>
                    </div>
                </div>
            ` : ''}
            
            ${exercicio.dicas_execucao ? `
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <div class="h-6 w-6 bg-yellow-100 rounded-lg flex items-center justify-center mr-2">
                            <i class="fas fa-lightbulb text-yellow-600 text-sm"></i>
                        </div>
                        Dicas de Execução
                    </h4>
                    <div class="text-gray-700 leading-relaxed">
                        <p>${exercicio.dicas_execucao}</p>
                    </div>
                </div>
            ` : ''}
            
            ${exercicio.contraindicacoes ? `
                <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <div class="h-6 w-6 bg-red-100 rounded-lg flex items-center justify-center mr-2">
                            <i class="fas fa-exclamation-triangle text-red-600 text-sm"></i>
                        </div>
                        Contraindicações
                    </h4>
                    <div class="text-gray-700 leading-relaxed">
                        <p>${exercicio.contraindicacoes}</p>
                    </div>
                </div>
            ` : ''}
            
            ${exercicio.video_url ? `
                <div class="bg-gray-50 rounded-xl p-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <div class="h-6 w-6 bg-red-100 rounded-lg flex items-center justify-center mr-2">
                            <i class="fas fa-video text-red-600 text-sm"></i>
                        </div>
                        Vídeo Demonstrativo
                    </h4>
                    <div>
                        <a href="${exercicio.video_url}" target="_blank" 
                           class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-medium rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl">
                            <i class="fas fa-play mr-2"></i>
                            Assistir Vídeo
                        </a>
                    </div>
                </div>
            ` : ''}
        </div>
        
        <!-- Modal footer -->
        <div class="flex items-center justify-end p-6 space-x-3 border-t border-gray-200 rounded-b-2xl bg-gray-50 mt-6">
            <button type="button" onclick="closeDetailsModal()"
                    class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 transition-colors duration-200">
                <i class="fas fa-times mr-2"></i>
                Fechar
            </button>
        </div>
    `;
    
    document.getElementById('detailsContent').innerHTML = content;
    const modal = document.getElementById('detailsModal');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Add animation
    setTimeout(() => {
        modal.querySelector('.relative').classList.add('animate__animated', 'animate__fadeInUp');
    }, 10);
}

function closeModal() {
    const modal = document.getElementById('exercicioModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
    
    // Remove animation classes
    const relative = modal.querySelector('.relative');
    if (relative) {
        relative.classList.remove('animate__animated', 'animate__fadeInUp');
    }
}

function closeDetailsModal() {
    const modal = document.getElementById('detailsModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
    
    // Remove animation classes
    const relative = modal.querySelector('.relative');
    if (relative) {
        relative.classList.remove('animate__animated', 'animate__fadeInUp');
    }
}

function confirmDelete(id, nome) {
    if (confirm(`Tem certeza que deseja excluir o exercício "${nome}"?\n\nSe o exercício estiver sendo usado em treinos, ele será apenas desativado.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('exercicioModal');
    const detailsModal = document.getElementById('detailsModal');
    
    if (event.target === modal) {
        closeModal();
    }
    if (event.target === detailsModal) {
        closeDetailsModal();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('exercicioModal');
        const detailsModal = document.getElementById('detailsModal');
        
        if (!modal.classList.contains('hidden')) {
            closeModal();
        }
        if (!detailsModal.classList.contains('hidden')) {
            closeDetailsModal();
        }
    }
});
</script>

<!-- Barra de navegação removida - utilizando apenas a do header.php -->

<?php include '../includes/footer.php'; ?>
