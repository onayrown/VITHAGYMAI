<?php
/**
 * SMARTBIOFIT - Página de Treinos
 * Criação e gerenciamento de treinos
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
    if ($action === 'add' || $action === 'edit') {
        try {
            $nome = trim($_POST['nome']);
            $descricao = trim($_POST['descricao'] ?? '');
            $tipo_treino = $_POST['tipo_treino'];
            $nivel_dificuldade = $_POST['nivel_dificuldade'];
            $duracao_estimada = (int)($_POST['duracao_estimada'] ?? 60);
            $objetivo_principal = trim($_POST['objetivo_principal'] ?? '');
            $observacoes_gerais = trim($_POST['observacoes_gerais'] ?? '');
            $template = isset($_POST['template']) ? 1 : 0;

            if (empty($nome) || empty($tipo_treino)) {
                throw new Exception('Nome e tipo do treino são obrigatórios.');
            }

            if ($action === 'add') {
                $sql = "INSERT INTO treinos (nome, descricao, professor_id, tipo_treino, nivel_dificuldade, duracao_estimada, objetivo_principal, observacoes_gerais, template) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $params = [$nome, $descricao, $professor_id, $tipo_treino, $nivel_dificuldade, $duracao_estimada, $objetivo_principal, $observacoes_gerais, $template];
                $db->execute($sql, $params);
                $treino_id = $db->lastInsertId();
                $message = 'Treino criado com sucesso!';
            } else {
                $id = (int)$_POST['id'];
                $sql = "UPDATE treinos SET nome = ?, descricao = ?, tipo_treino = ?, nivel_dificuldade = ?, duracao_estimada = ?, objetivo_principal = ?, observacoes_gerais = ?, template = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND professor_id = ?";
                $params = [$nome, $descricao, $tipo_treino, $nivel_dificuldade, $duracao_estimada, $objetivo_principal, $observacoes_gerais, $template, $id, $professor_id];
                $db->execute($sql, $params);
                $treino_id = $id;
                $message = 'Treino atualizado com sucesso!';
            }

            // Processar exercícios se foram enviados
            if (isset($_POST['exercicios']) && is_array($_POST['exercicios'])) {
                // Remover exercícios existentes
                $db->execute("DELETE FROM treino_exercicios WHERE treino_id = ?", [$treino_id]);
                
                // Adicionar novos exercícios
                foreach ($_POST['exercicios'] as $ordem => $exercicio) {
                    if (!empty($exercicio['exercicio_id'])) {
                        $sql_ex = "INSERT INTO treino_exercicios (treino_id, exercicio_id, ordem_execucao, series, repeticoes, carga, tempo_descanso, tempo_execucao, distancia, observacoes_especificas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $params_ex = [
                            $treino_id,
                            (int)$exercicio['exercicio_id'],
                            $ordem + 1,
                            (int)($exercicio['series'] ?? 3),
                            $exercicio['repeticoes'] ?? '10-12',
                            $exercicio['carga'] ?? '',
                            (int)($exercicio['tempo_descanso'] ?? 60),
                            (int)($exercicio['tempo_execucao'] ?? 0),
                            !empty($exercicio['distancia']) ? (float)$exercicio['distancia'] : null,
                            $exercicio['observacoes_especificas'] ?? ''
                        ];
                        $db->execute($sql_ex, $params_ex);
                    }
                }
            }

            $message_type = 'success';
            
            // Redirecionar para evitar resubmissão
            header("Location: treinos.php?msg=" . urlencode($message));
            exit;
            
        } catch (Exception $e) {
            $message = 'Erro ao salvar treino: ' . $e->getMessage();
            $message_type = 'error';
        }
    } elseif ($action === 'delete') {
        try {
            $id = (int)$_POST['id'];
              // Verificar se o treino está associado a algum aluno
            $count_uso = $db->fetch("SELECT COUNT(*) as total FROM aluno_treinos WHERE treino_id = ?", [$id])['total'];
            
            if ($count_uso > 0) {
                // Desativar ao invés de deletar
                $sql = "UPDATE treinos SET ativo = FALSE WHERE id = ? AND professor_id = ?";
                $db->execute($sql, [$id, $professor_id]);
                $message = 'Treino desativado com sucesso (estava sendo usado por alunos).';
            } else {
                // Deletar completamente
                $db->execute("DELETE FROM treino_exercicios WHERE treino_id = ?", [$id]);
                $sql = "DELETE FROM treinos WHERE id = ? AND professor_id = ?";
                $db->execute($sql, [$id, $professor_id]);
                $message = 'Treino removido com sucesso!';
            }
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Erro ao remover treino: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Buscar treinos
$search = $_GET['search'] ?? '';
$tipo_filter = $_GET['tipo'] ?? '';
$nivel_filter = $_GET['nivel'] ?? '';

$where_conditions = ["professor_id = ?"];
$params = [$professor_id];

if (!empty($search)) {
    $where_conditions[] = "(nome LIKE ? OR descricao LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($tipo_filter)) {
    $where_conditions[] = "tipo_treino = ?";
    $params[] = $tipo_filter;
}

if (!empty($nivel_filter)) {
    $where_conditions[] = "nivel_dificuldade = ?";
    $params[] = $nivel_filter;
}

$where_clause = implode(" AND ", $where_conditions);
$sql = "SELECT t.*, 
        (SELECT COUNT(*) FROM treino_exercicios te WHERE te.treino_id = t.id) as total_exercicios,
        (SELECT COUNT(*) FROM aluno_treinos at WHERE at.treino_id = t.id AND at.status = 'ativo') as alunos_ativos
        FROM treinos t 
        WHERE $where_clause 
        ORDER BY t.template DESC, t.created_at DESC";
$treinos = $db->fetchAll($sql, $params);

// Buscar exercícios disponíveis
$exercicios_disponiveis = $db->fetchAll("SELECT id, nome, categoria, grupo_muscular FROM exercicios WHERE created_by = ? AND ativo = TRUE ORDER BY categoria, nome", [$professor_id]);

// Buscar treino específico para edição
$treino_edit = null;
$exercicios_treino = [];
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $treino_edit = $db->fetch("SELECT * FROM treinos WHERE id = ? AND professor_id = ?", [$id, $professor_id]);
    if ($treino_edit) {
        $exercicios_treino = $db->fetchAll("
            SELECT te.*, e.nome as exercicio_nome, e.categoria, e.grupo_muscular 
            FROM treino_exercicios te 
            JOIN exercicios e ON te.exercicio_id = e.id 
            WHERE te.treino_id = ? 
            ORDER BY te.ordem_execucao", [$id]);
    }
}

// Verificar mensagem de redirecionamento
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $message_type = 'success';
}

include '../includes/header.php';
?>

<!-- Premium Mobile Training Page -->
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
                            <span class="text-sm font-medium text-gray-500">Biblioteca de Treinos</span>
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
                        <h1 class="text-2xl font-bold text-gray-900">Biblioteca de Treinos</h1>
                        <p class="text-sm text-gray-500">Crie e gerencie treinos personalizados para seus alunos</p>
                    </div>
                </div>
                
                <?php if (count($exercicios_disponiveis) > 0): ?>
                <div class="mt-4 sm:mt-0">
                    <button type="button" onclick="showAddModal()" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-gradient-to-r from-cobalt-600 to-cobalt-700 hover:from-cobalt-700 hover:to-cobalt-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 transition-all duration-200">
                        <i class="fas fa-plus -ml-1 mr-2"></i>
                        Criar Treino
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>    <!-- Alert Messages -->
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
    <?php endif; ?>    <!-- Statistics Cards -->
    <div class="px-4 mt-6" data-aos="fade-up">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Treinos Ativos -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-dumbbell text-blue-600"></i>
                        </div>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-2xl font-bold text-gray-900">
                            <?php echo $db->fetch("SELECT COUNT(*) as total FROM treinos WHERE professor_id = ? AND ativo = TRUE", [$professor_id])['total']; ?>
                        </p>
                        <p class="text-xs text-gray-500 font-medium">Treinos Ativos</p>
                    </div>
                </div>
            </div>

            <!-- Templates -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-bookmark text-purple-600"></i>
                        </div>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-2xl font-bold text-gray-900">
                            <?php echo $db->fetch("SELECT COUNT(*) as total FROM treinos WHERE professor_id = ? AND template = TRUE", [$professor_id])['total']; ?>
                        </p>
                        <p class="text-xs text-gray-500 font-medium">Templates</p>
                    </div>
                </div>
            </div>

            <!-- Alunos Treinando -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-green-600"></i>
                        </div>
                    </div>                    <div class="ml-3 flex-1">
                        <p class="text-2xl font-bold text-gray-900">
                            <?php echo $db->fetch("SELECT COUNT(DISTINCT at.aluno_id) as total FROM aluno_treinos at JOIN treinos t ON at.treino_id = t.id WHERE t.professor_id = ? AND at.status = 'ativo'", [$professor_id])['total']; ?>
                        </p>
                        <p class="text-xs text-gray-500 font-medium">Alunos Treinando</p>
                    </div>
                </div>
            </div>

            <!-- Exercícios Disponíveis -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-list-ul text-orange-600"></i>
                        </div>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-2xl font-bold text-gray-900">
                            <?php echo count($exercicios_disponiveis); ?>
                        </p>
                        <p class="text-xs text-gray-500 font-medium">Exercícios Disponíveis</p>
                    </div>
                </div>
            </div>
        </div>
    </div>    <!-- Filters and Search -->
    <div class="px-4 mt-6" data-aos="fade-up" data-aos-delay="100">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-filter mr-2 text-cobalt-600"></i>
                    Filtros de Busca
                </h3>
            </div>
            <div class="p-6">
                <form method="GET" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Search Field -->
                        <div class="space-y-1">
                            <label for="search" class="block text-sm font-medium text-gray-700">Buscar</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>                                <input type="text" id="search" name="search"
                                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 sm:text-sm transition-colors text-gray-900"
                                       value="<?php echo htmlspecialchars($search); ?>"
                                       placeholder="Nome ou descrição...">
                            </div>
                        </div>
                        
                        <!-- Type Filter -->
                        <div class="space-y-1">
                            <label for="tipo" class="block text-sm font-medium text-gray-700">Tipo</label>                            <select id="tipo" name="tipo"
                                    class="block w-full py-2 pl-3 pr-10 border border-gray-300 bg-white rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 sm:text-sm transition-colors text-gray-900">
                                <option value="">Todos os tipos</option>
                                <option value="forca" <?php echo $tipo_filter === 'forca' ? 'selected' : ''; ?>>💪 Força</option>
                                <option value="cardio" <?php echo $tipo_filter === 'cardio' ? 'selected' : ''; ?>>❤️ Cardio</option>
                                <option value="funcional" <?php echo $tipo_filter === 'funcional' ? 'selected' : ''; ?>>⚡ Funcional</option>
                                <option value="hiit" <?php echo $tipo_filter === 'hiit' ? 'selected' : ''; ?>>🔥 HIIT</option>
                                <option value="flexibilidade" <?php echo $tipo_filter === 'flexibilidade' ? 'selected' : ''; ?>>🧘 Flexibilidade</option>
                                <option value="misto" <?php echo $tipo_filter === 'misto' ? 'selected' : ''; ?>>🎯 Misto</option>
                                <option value="reabilitacao" <?php echo $tipo_filter === 'reabilitacao' ? 'selected' : ''; ?>>🏥 Reabilitação</option>
                            </select>
                        </div>

                        <!-- Level Filter -->
                        <div class="space-y-1">
                            <label for="nivel" class="block text-sm font-medium text-gray-700">Nível</label>                            <select id="nivel" name="nivel"
                                    class="block w-full py-2 pl-3 pr-10 border border-gray-300 bg-white rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 sm:text-sm transition-colors text-gray-900">
                                <option value="">Todos os níveis</option>
                                <option value="iniciante" <?php echo $nivel_filter === 'iniciante' ? 'selected' : ''; ?>>🟢 Iniciante</option>
                                <option value="intermediario" <?php echo $nivel_filter === 'intermediario' ? 'selected' : ''; ?>>🟡 Intermediário</option>
                                <option value="avancado" <?php echo $nivel_filter === 'avancado' ? 'selected' : ''; ?>>🔴 Avançado</option>
                            </select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex space-x-2 items-end">
                            <button type="submit" 
                                    class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-cobalt-600 hover:bg-cobalt-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 transition-colors">
                                <i class="fas fa-search mr-2"></i>
                                Filtrar
                            </button>
                            <a href="treinos.php" 
                               class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 transition-colors">
                                <i class="fas fa-times mr-2"></i>
                                Limpar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>    <!-- Exercise Warning -->
    <?php if (count($exercicios_disponiveis) === 0): ?>
    <div class="px-4 mt-6" data-aos="fade-up" data-aos-delay="200">
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="h-12 w-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-info-circle text-blue-600 text-xl"></i>
                    </div>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-semibold text-blue-900 mb-2">Exercícios necessários</h3>
                    <p class="text-blue-700 mb-4">Para criar treinos, você precisa ter exercícios cadastrados primeiro. Cadastre alguns exercícios para começar a criar treinos personalizados.</p>
                    <a href="exercicios.php" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Cadastrar Exercícios
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>    <!-- Training List -->
    <div class="px-4 mt-6 pb-6" data-aos="fade-up" data-aos-delay="300">
        <div class="mb-6">
            <h3 class="text-xl font-bold text-gray-900 flex items-center">
                <i class="fas fa-dumbbell mr-3 text-cobalt-600"></i>
                Treinos Cadastrados
                <span class="ml-2 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                    <?php echo count($treinos); ?>
                </span>
            </h3>
        </div>
        
        <?php if (empty($treinos)): ?>
        <!-- Empty State -->
        <div class="text-center py-12 bg-white rounded-xl border border-gray-200">
            <div class="mx-auto h-24 w-24 text-gray-400 mb-6">
                <i class="fas fa-dumbbell text-6xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum treino encontrado</h3>
            <p class="text-gray-500 mb-6 max-w-md mx-auto">
                <?php if (count($exercicios_disponiveis) > 0): ?>
                    Comece criando seu primeiro treino personalizado para seus alunos.
                <?php else: ?>
                    Cadastre exercícios primeiro para poder criar treinos personalizados.
                <?php endif; ?>
            </p>
            <?php if (count($exercicios_disponiveis) > 0): ?>
            <button type="button" onclick="showAddModal()" 
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-cobalt-600 to-cobalt-700 hover:from-cobalt-700 hover:to-cobalt-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 transition-all duration-200">
                <i class="fas fa-plus mr-2"></i>
                Criar Primeiro Treino
            </button>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <!-- Training Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($treinos as $treino): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-200 <?php echo !$treino['ativo'] ? 'opacity-60' : ''; ?>" 
                 data-aos="zoom-in" data-aos-delay="<?php echo 100 + (array_search($treino, $treinos) * 50); ?>">
                
                <!-- Card Header -->
                <div class="p-6 pb-4">
                    <div class="flex items-start justify-between mb-4">
                        <h4 class="text-lg font-bold text-gray-900 line-clamp-2 flex-1 mr-2">
                            <?php echo htmlspecialchars($treino['nome']); ?>
                        </h4>
                        <?php if (!$treino['ativo']): ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                <i class="fas fa-pause mr-1"></i>
                                Inativo
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Badges -->
                    <div class="flex flex-wrap gap-2 mb-4">
                        <?php if ($treino['template']): ?>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                <i class="fas fa-bookmark mr-1"></i>
                                Template
                            </span>
                        <?php endif; ?>
                        
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-cobalt-100 text-cobalt-800">
                            <?php 
                            $tipo_icons = [
                                'forca' => '💪',
                                'cardio' => '❤️',
                                'funcional' => '⚡',
                                'hiit' => '🔥',
                                'flexibilidade' => '🧘',
                                'misto' => '🎯',
                                'reabilitacao' => '🏥'
                            ];
                            echo ($tipo_icons[$treino['tipo_treino']] ?? '') . ' ' . ucfirst($treino['tipo_treino']);
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
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?php echo $nivel_colors[$treino['nivel_dificuldade']] ?? 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo ($nivel_icons[$treino['nivel_dificuldade']] ?? '') . ' ' . ucfirst($treino['nivel_dificuldade']); ?>
                        </span>
                    </div>
                    
                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-4 py-4 border-t border-gray-100">
                        <div class="text-center">
                            <div class="flex items-center justify-center text-gray-400 mb-1">
                                <i class="fas fa-clock text-sm"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-900"><?php echo $treino['duracao_estimada']; ?> min</p>
                            <p class="text-xs text-gray-500">Duração</p>
                        </div>
                        <div class="text-center">
                            <div class="flex items-center justify-center text-gray-400 mb-1">
                                <i class="fas fa-list-ul text-sm"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-900"><?php echo $treino['total_exercicios']; ?></p>
                            <p class="text-xs text-gray-500">Exercícios</p>
                        </div>
                        <div class="text-center">
                            <div class="flex items-center justify-center text-gray-400 mb-1">
                                <i class="fas fa-users text-sm"></i>
                            </div>                            <p class="text-sm font-semibold text-gray-900"><?php echo $treino['alunos_ativos']; ?></p>
                            <p class="text-xs text-gray-500">Usos Ativos</p>
                        </div>
                    </div>

                    <?php if ($treino['objetivo_principal']): ?>
                    <!-- Objective -->
                    <div class="flex items-start bg-gray-50 rounded-lg p-3 mb-4">
                        <i class="fas fa-bullseye text-cobalt-600 mt-0.5 mr-2 flex-shrink-0"></i>
                        <p class="text-sm text-gray-700 line-clamp-2"><?php echo htmlspecialchars($treino['objetivo_principal']); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($treino['descricao']): ?>
                    <!-- Description -->
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 line-clamp-2"><?php echo htmlspecialchars($treino['descricao']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Card Actions -->
                <div class="px-6 py-4 bg-gray-50 rounded-b-xl border-t border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex space-x-2">
                            <button type="button" onclick="showDetailsModal(<?php echo $treino['id']; ?>)" 
                                    class="inline-flex items-center p-2 border border-gray-300 rounded-lg text-gray-400 hover:text-gray-600 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 transition-colors" 
                                    title="Ver Detalhes">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                            
                            <?php if ($treino['total_exercicios'] > 0): ?>
                            <a href="treino-alunos.php?id=<?php echo $treino['id']; ?>" 
                               class="inline-flex items-center p-2 border border-gray-300 rounded-lg text-gray-400 hover:text-blue-600 hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors" 
                               title="Associar Alunos">
                                <i class="fas fa-users text-sm"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex space-x-2">
                            <button type="button" onclick="showEditModal(<?php echo $treino['id']; ?>)" 
                                    class="inline-flex items-center p-2 border border-gray-300 rounded-lg text-gray-400 hover:text-orange-600 hover:border-orange-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors" 
                                    title="Editar">
                                <i class="fas fa-edit text-sm"></i>
                            </button>
                            
                            <button type="button" onclick="confirmDelete(<?php echo $treino['id']; ?>, '<?php echo addslashes($treino['nome']); ?>')" 
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

<!-- Modal Adicionar/Editar Treino -->
<div id="treinoModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative w-full max-w-6xl max-h-full mx-auto">
        <!-- Modal content -->
        <div class="relative bg-white rounded-2xl shadow-2xl border border-gray-200">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 rounded-t-2xl bg-gradient-to-r from-cobalt-50 to-cobalt-100">
                <h3 id="modalTitle" class="text-xl font-bold text-gray-900 flex items-center">
                    <div class="h-8 w-8 bg-gradient-to-r from-cobalt-500 to-cobalt-600 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-plus text-white text-sm"></i>
                    </div>
                    Criar Treino
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center transition-colors duration-200" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                    <span class="sr-only">Fechar modal</span>
                </button>
            </div>
            
            <!-- Modal body -->
            <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
                <form id="treinoForm" method="POST" class="space-y-6">
                    <input type="hidden" id="treinoId" name="id">
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
                                    Nome do Treino <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="nome" name="nome" 
                                       class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3 transition-colors duration-200" 
                                       placeholder="Digite o nome do treino..." required>
                            </div>
                            
                            <div>
                                <label for="tipo_treino" class="block mb-2 text-sm font-medium text-gray-900">
                                    Tipo <span class="text-red-500">*</span>
                                </label>
                                <select id="tipo_treino" name="tipo_treino" 
                                        class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3" required>
                                    <option value="">Selecione o tipo...</option>
                                    <option value="forca">💪 Força</option>
                                    <option value="cardio">❤️ Cardio</option>
                                    <option value="funcional">🏃 Funcional</option>
                                    <option value="hiit">⚡ HIIT</option>
                                    <option value="flexibilidade">🧘 Flexibilidade</option>
                                    <option value="misto">🔄 Misto</option>
                                    <option value="reabilitacao">🏥 Reabilitação</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div>
                                <label for="nivel_dificuldade" class="block mb-2 text-sm font-medium text-gray-900">
                                    Nível de Dificuldade
                                </label>
                                <select id="nivel_dificuldade" name="nivel_dificuldade" 
                                        class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3">
                                    <option value="iniciante">🟢 Iniciante</option>
                                    <option value="intermediario" selected>🟡 Intermediário</option>
                                    <option value="avancado">🔴 Avançado</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="duracao_estimada" class="block mb-2 text-sm font-medium text-gray-900">
                                    Duração Estimada (minutos)
                                </label>
                                <input type="number" id="duracao_estimada" name="duracao_estimada" 
                                       class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3" 
                                       value="60" min="10" max="180" placeholder="60">
                            </div>
                        </div>

                        <div class="mt-6">
                            <label for="objetivo_principal" class="block mb-2 text-sm font-medium text-gray-900">
                                Objetivo Principal
                            </label>
                            <input type="text" id="objetivo_principal" name="objetivo_principal" 
                                   class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3" 
                                   placeholder="Ex: Hipertrofia, Emagrecimento, Condicionamento...">
                        </div>

                        <div class="mt-6">
                            <label for="descricao" class="block mb-2 text-sm font-medium text-gray-900">
                                Descrição
                            </label>
                            <textarea id="descricao" name="descricao" rows="3" 
                                      class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3" 
                                      placeholder="Descrição detalhada do treino..."></textarea>
                        </div>

                        <div class="mt-6">
                            <label for="observacoes_gerais" class="block mb-2 text-sm font-medium text-gray-900">
                                Observações Gerais
                            </label>
                            <textarea id="observacoes_gerais" name="observacoes_gerais" rows="2" 
                                      class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3" 
                                      placeholder="Observações importantes sobre o treino..."></textarea>
                        </div>

                        <div class="mt-6">
                            <div class="flex items-center">
                                <input id="template" name="template" type="checkbox" value="1" 
                                       class="w-4 h-4 text-cobalt-600 bg-gray-100 border-gray-300 rounded focus:ring-cobalt-500 focus:ring-2">
                                <label for="template" class="ml-2 text-sm font-medium text-gray-900">
                                    📋 Salvar como template (disponível para reutilização)
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Exercícios do Treino -->
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6" data-aos="fade-up" data-aos-delay="100">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <div class="h-6 w-6 bg-green-100 rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-dumbbell text-green-600 text-sm"></i>
                            </div>
                            Exercícios do Treino
                        </h4>
                        
                        <div id="exerciciosContainer" class="space-y-4">
                            <!-- Exercícios serão adicionados aqui via JavaScript -->
                        </div>
                        
                        <button type="button" onclick="addExercicio()" 
                                class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200">
                            <i class="fas fa-plus mr-2"></i>
                            Adicionar Exercício
                        </button>
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
                <button type="submit" form="treinoForm" 
                        class="text-white bg-gradient-to-r from-cobalt-600 to-cobalt-700 hover:from-cobalt-700 hover:to-cobalt-800 focus:ring-4 focus:outline-none focus:ring-cobalt-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-all duration-200">
                    <i class="fas fa-save mr-2"></i>
                    Salvar Treino
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalhes -->
<div id="detailsModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative w-full max-w-4xl max-h-full mx-auto">
        <!-- Modal content -->
        <div class="relative bg-white rounded-2xl shadow-2xl border border-gray-200">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 rounded-t-2xl bg-gradient-to-r from-blue-50 to-indigo-50">
                <h3 class="text-xl font-bold text-gray-900 flex items-center">
                    <div class="h-8 w-8 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-eye text-white text-sm"></i>
                    </div>
                    Detalhes do Treino
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center transition-colors duration-200" onclick="closeDetailsModal()">
                    <i class="fas fa-times"></i>
                    <span class="sr-only">Fechar modal</span>
                </button>
            </div>
            
            <!-- Modal body -->
            <div id="detailsContent" class="p-6 max-h-[70vh] overflow-y-auto">
                <!-- Conteúdo carregado via JavaScript -->
                <div class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-cobalt-600"></div>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
// Dados para uso em JavaScript
const treinos = <?php echo json_encode($treinos); ?>;
const exerciciosDisponiveis = <?php echo json_encode($exercicios_disponiveis); ?>;
let exercicioCounter = 0;

function showAddModal() {
    document.getElementById('modalTitle').innerHTML = `
        <div class="h-8 w-8 bg-gradient-to-r from-cobalt-500 to-cobalt-600 rounded-lg flex items-center justify-center mr-3">
            <i class="fas fa-plus text-white text-sm"></i>
        </div>
        Criar Treino
    `;
    document.getElementById('formAction').value = 'add';
    document.getElementById('treinoForm').reset();
    document.getElementById('treinoId').value = '';
    document.getElementById('exerciciosContainer').innerHTML = '';
    exercicioCounter = 0;
    
    // Show modal with Tailwind
    const modal = document.getElementById('treinoModal');
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    
    // Add animation
    setTimeout(() => {
        modal.querySelector('.relative').classList.add('animate__animated', 'animate__fadeInUp');
    }, 10);
}

function showEditModal(id) {
    // Buscar dados do treino via AJAX
    fetch(`../api/treino-detalhes.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').innerHTML = `
                    <div class="h-8 w-8 bg-gradient-to-r from-amber-500 to-amber-600 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-edit text-white text-sm"></i>
                    </div>
                    Editar Treino
                `;
                document.getElementById('formAction').value = 'edit';
                document.getElementById('treinoId').value = data.treino.id;
                
                // Preencher formulário
                document.getElementById('nome').value = data.treino.nome;
                document.getElementById('tipo_treino').value = data.treino.tipo_treino;
                document.getElementById('nivel_dificuldade').value = data.treino.nivel_dificuldade;
                document.getElementById('duracao_estimada').value = data.treino.duracao_estimada;
                document.getElementById('objetivo_principal').value = data.treino.objetivo_principal || '';
                document.getElementById('descricao').value = data.treino.descricao || '';
                document.getElementById('observacoes_gerais').value = data.treino.observacoes_gerais || '';
                document.getElementById('template').checked = data.treino.template == 1;
                
                // Carregar exercícios
                document.getElementById('exerciciosContainer').innerHTML = '';
                exercicioCounter = 0;
                data.exercicios.forEach(exercicio => {
                    addExercicio(exercicio);
                });
                
                // Show modal
                const modal = document.getElementById('treinoModal');
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                
                setTimeout(() => {
                    modal.querySelector('.relative').classList.add('animate__animated', 'animate__fadeInUp');
                }, 10);
            } else {
                showToast('Erro ao carregar dados do treino: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showToast('Erro ao carregar dados do treino', 'error');
        });
}

function addExercicio(exercicioData = null) {
    exercicioCounter++;
    const container = document.getElementById('exerciciosContainer');
    
    const exercicioDiv = document.createElement('div');
    exercicioDiv.className = 'bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow duration-200';
    exercicioDiv.innerHTML = `
        <div class="flex items-center justify-between mb-4">
            <h5 class="text-lg font-semibold text-gray-900 flex items-center">
                <div class="h-6 w-6 bg-orange-100 rounded-lg flex items-center justify-center mr-2">
                    <span class="text-orange-600 text-sm font-bold">${exercicioCounter}</span>
                </div>
                Exercício ${exercicioCounter}
            </h5>
            <button type="button" onclick="removeExercicio(this)" 
                    class="text-red-400 hover:text-red-600 bg-red-50 hover:bg-red-100 rounded-lg p-2 transition-colors duration-200">
                <i class="fas fa-trash text-sm"></i>
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block mb-2 text-sm font-medium text-gray-900">
                    Exercício <span class="text-red-500">*</span>
                </label>
                <select name="exercicios[${exercicioCounter - 1}][exercicio_id]" 
                        class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3" required>
                    <option value="">Selecione um exercício...</option>
                    ${exerciciosDisponiveis.map(ex => `
                        <option value="${ex.id}" ${exercicioData && exercicioData.exercicio_id == ex.id ? 'selected' : ''}>
                            ${ex.nome} - ${ex.grupo_muscular}
                        </option>
                    `).join('')}
                </select>
            </div>
            
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900">Séries</label>
                <input type="number" name="exercicios[${exercicioCounter - 1}][series]" 
                       class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3" 
                       value="${exercicioData ? exercicioData.series : 3}" min="1" max="10" placeholder="3">
            </div>
            
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900">Repetições</label>
                <input type="text" name="exercicios[${exercicioCounter - 1}][repeticoes]" 
                       class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3" 
                       value="${exercicioData ? exercicioData.repeticoes : '10-12'}" placeholder="Ex: 10-12">
            </div>
            
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900">Carga</label>
                <input type="text" name="exercicios[${exercicioCounter - 1}][carga]" 
                       class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3" 
                       value="${exercicioData ? exercicioData.carga : ''}" placeholder="Ex: 20kg">
            </div>
            
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900">Descanso (seg)</label>
                <input type="number" name="exercicios[${exercicioCounter - 1}][tempo_descanso]" 
                       class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3" 
                       value="${exercicioData ? exercicioData.tempo_descanso : 60}" min="0" placeholder="60">
            </div>
            
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900">Tempo (seg)</label>
                <input type="number" name="exercicios[${exercicioCounter - 1}][tempo_execucao]" 
                       class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3" 
                       value="${exercicioData ? exercicioData.tempo_execucao : 0}" min="0" placeholder="Para cardio">
            </div>
            
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900">Distância (m)</label>
                <input type="number" name="exercicios[${exercicioCounter - 1}][distancia]" 
                       class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3" 
                       value="${exercicioData ? exercicioData.distancia : ''}" min="0" step="0.01" placeholder="Para corrida">
            </div>
            
            <div class="md:col-span-2">
                <label class="block mb-2 text-sm font-medium text-gray-900">Observações</label>
                <textarea name="exercicios[${exercicioCounter - 1}][observacoes_especificas]" rows="2" 
                          class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3" 
                          placeholder="Observações específicas para este exercício...">${exercicioData ? exercicioData.observacoes_especificas : ''}</textarea>
            </div>
        </div>
    `;
    
    container.appendChild(exercicioDiv);
    
    // Add fade-in animation
    setTimeout(() => {
        exercicioDiv.classList.add('animate__animated', 'animate__fadeInUp');
    }, 10);
}

function removeExercicio(button) {
    const exercicioDiv = button.closest('.bg-white');
    exercicioDiv.classList.add('animate__animated', 'animate__fadeOutUp');
    setTimeout(() => {
        exercicioDiv.remove();
    }, 300);
}

function showDetailsModal(id) {
    // Show modal first with loading
    const modal = document.getElementById('detailsModal');
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    
    // Buscar dados do treino via AJAX
    fetch(`../api/treino-detalhes.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const treino = data.treino;
                const exercicios = data.exercicios;
                
                let content = `
                    <div class="space-y-6">
                        <!-- Header com informações básicas -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6">
                            <h3 class="text-2xl font-bold text-gray-900 mb-4">${treino.nome}</h3>
                            <div class="flex flex-wrap gap-2 mb-4">
                                ${treino.template == 1 ? '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800"><i class="fas fa-bookmark mr-1"></i> Template</span>' : ''}
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-cobalt-100 text-cobalt-800">${treino.tipo_treino}</span>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-${treino.nivel_dificuldade === 'iniciante' ? 'green' : (treino.nivel_dificuldade === 'intermediario' ? 'yellow' : 'red')}-100 text-${treino.nivel_dificuldade === 'iniciante' ? 'green' : (treino.nivel_dificuldade === 'intermediario' ? 'yellow' : 'red')}-800">${treino.nivel_dificuldade}</span>
                            </div>
                            
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-cobalt-600">${treino.duracao_estimada}</div>
                                    <div class="text-sm text-gray-600">Minutos</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-600">${exercicios.length}</div>
                                    <div class="text-sm text-gray-600">Exercícios</div>
                                </div>
                                ${treino.objetivo_principal ? `
                                    <div class="md:col-span-1 col-span-2 text-center">
                                        <div class="text-sm font-medium text-gray-900">Objetivo</div>
                                        <div class="text-sm text-gray-600">${treino.objetivo_principal}</div>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                        
                        ${treino.descricao ? `
                            <div class="bg-gray-50 rounded-xl p-6">
                                <h4 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                                    Descrição
                                </h4>
                                <p class="text-gray-700">${treino.descricao}</p>
                            </div>
                        ` : ''}
                        
                        ${exercicios.length > 0 ? `
                            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6">
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <i class="fas fa-dumbbell text-green-600 mr-2"></i>
                                    Exercícios do Treino
                                </h4>
                                <div class="space-y-4">
                                    ${exercicios.map((ex, index) => `
                                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                                            <div class="flex items-center mb-2">
                                                <div class="h-8 w-8 bg-orange-100 rounded-lg flex items-center justify-center mr-3">
                                                    <span class="text-orange-600 text-sm font-bold">${index + 1}</span>
                                                </div>
                                                <h5 class="font-semibold text-gray-900">${ex.exercicio_nome}</h5>
                                            </div>
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm text-gray-600 ml-11">
                                                <div><strong>Séries:</strong> ${ex.series}</div>
                                                <div><strong>Reps:</strong> ${ex.repeticoes}</div>
                                                ${ex.carga ? `<div><strong>Carga:</strong> ${ex.carga}</div>` : '<div></div>'}
                                                ${ex.tempo_descanso ? `<div><strong>Descanso:</strong> ${ex.tempo_descanso}s</div>` : '<div></div>'}
                                                ${ex.tempo_execucao > 0 ? `<div class="md:col-span-2"><strong>Tempo:</strong> ${ex.tempo_execucao}s</div>` : ''}
                                                ${ex.distancia ? `<div class="md:col-span-2"><strong>Distância:</strong> ${ex.distancia}m</div>` : ''}
                                            </div>
                                            ${ex.observacoes_especificas ? `<div class="mt-3 ml-11 text-sm text-gray-600 bg-gray-50 rounded-lg p-3"><strong>Observações:</strong> ${ex.observacoes_especificas}</div>` : ''}
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        ` : ''}
                        
                        ${treino.observacoes_gerais ? `
                            <div class="bg-yellow-50 rounded-xl p-6">
                                <h4 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                                    <i class="fas fa-sticky-note text-yellow-600 mr-2"></i>
                                    Observações Gerais
                                </h4>
                                <p class="text-gray-700">${treino.observacoes_gerais}</p>
                            </div>
                        ` : ''}
                        
                        <div class="text-center pt-4">
                            <button type="button" onclick="closeDetailsModal()" 
                                    class="inline-flex items-center px-6 py-3 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 transition-colors duration-200">
                                <i class="fas fa-times mr-2"></i>
                                Fechar
                            </button>
                        </div>
                    </div>
                `;
                
                document.getElementById('detailsContent').innerHTML = content;
            } else {
                showToast('Erro ao carregar detalhes do treino: ' + data.message, 'error');
                closeDetailsModal();
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showToast('Erro ao carregar detalhes do treino', 'error');
            closeDetailsModal();
        });
}

function closeModal() {
    const modal = document.getElementById('treinoModal');
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

function closeDetailsModal() {
    const modal = document.getElementById('detailsModal');
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

function confirmDelete(id, nome) {
    if (confirm(`Tem certeza que deseja excluir o treino "${nome}"?\n\nSe o treino estiver sendo usado por alunos, ele será apenas desativado.`)) {
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

// Fechar modal clicando fora (backdrop)
document.addEventListener('click', function(event) {
    const treinoModal = document.getElementById('treinoModal');
    const detailsModal = document.getElementById('detailsModal');
    
    if (event.target === treinoModal) {
        closeModal();
    }
    if (event.target === detailsModal) {
        closeDetailsModal();
    }
});

// Fechar modal com ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const treinoModal = document.getElementById('treinoModal');
        const detailsModal = document.getElementById('detailsModal');
        
        if (!treinoModal.classList.contains('hidden')) {
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
