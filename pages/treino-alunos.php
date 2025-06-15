<?php
/**
 * SMARTBIOFIT - Associação de Treinos a Alunos
 * Página para vincular treinos específicos aos alunos
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/qr-generator.php';

// A verificação de login já é feita em auth.php.
// Agora, verificamos a permissão específica para esta página.
if (!hasPermission('professor')) {
    // Redireciona se não for professor ou admin
    header('Location: ' . APP_URL . '/index.php?error=permission_denied');
    exit;
}

$db = Database::getInstance();
$professor_id = $_SESSION['user_id'];

// Processar ações
$action = $_GET['action'] ?? '';
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'associar') {
        try {
            $aluno_id = (int)$_POST['aluno_id'];
            $treino_id = (int)$_POST['treino_id'];
            $data_inicio = $_POST['data_inicio'] ?? date('Y-m-d');
            $data_fim = $_POST['data_fim'] ?? null;
            $observacoes = trim($_POST['observacoes_individuais'] ?? '');
            
            if (!$aluno_id || !$treino_id) {
                throw new Exception('Aluno e treino são obrigatórios.');
            }
              // Verificar se o treino pertence ao professor
            $treino_check = $db->fetch("SELECT id FROM treinos WHERE id = ? AND professor_id = ?", [$treino_id, $professor_id]);
            if (!$treino_check) {
                throw new Exception('Treino não encontrado ou sem permissão.');
            }
            
            // Verificar se já existe associação ativa
            $associacao_existente = $db->fetch("SELECT id FROM aluno_treinos WHERE aluno_id = ? AND treino_id = ? AND status = 'ativo'", [$aluno_id, $treino_id]);
            if ($associacao_existente) {
                throw new Exception('Este treino já está ativo para este aluno.');
            }
            
            // Gerar hash único para compartilhamento
            $hash = bin2hex(random_bytes(16));
            
            // Inserir associação
            $sql = "INSERT INTO aluno_treinos (aluno_id, treino_id, professor_id, data_inicio, data_fim, observacoes_individuais, url_compartilhamento) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $params = [$aluno_id, $treino_id, $professor_id, $data_inicio, $data_fim, $observacoes, $hash];
            
            $db->execute($sql, $params);
            $message = 'Treino associado ao aluno com sucesso!';
            $message_type = 'success';
            
        } catch (Exception $e) {
            $message = 'Erro ao associar treino: ' . $e->getMessage();
            $message_type = 'error';
        }
    } elseif ($action === 'alterar_status') {
        try {
            $associacao_id = (int)$_POST['associacao_id'];
            $status = $_POST['status'];
            
            $sql = "UPDATE aluno_treinos SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND professor_id = ?";
            $db->execute($sql, [$status, $associacao_id, $professor_id]);
            
            $message = 'Status do treino atualizado com sucesso!';
            $message_type = 'success';
            
        } catch (Exception $e) {
            $message = 'Erro ao alterar status: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Buscar alunos do professor
$sql_alunos = "SELECT id, nome, email, telefone FROM alunos WHERE ativo = 1 ORDER BY nome";
$alunos = $db->fetchAll($sql_alunos);

// Buscar treinos do professor
$sql_treinos = "SELECT id, nome, tipo_treino, nivel_dificuldade, duracao_estimada FROM treinos WHERE professor_id = ? AND ativo = 1 ORDER BY nome";
$treinos = $db->fetchAll($sql_treinos, [$professor_id]);

// Filtros
$filtro_status = $_GET['status'] ?? '';
$filtro_aluno = $_GET['aluno'] ?? '';
$filtro_treino = $_GET['treino'] ?? '';

// Buscar associações existentes com filtros dinâmicos
$sql_associacoes = "
    SELECT at.*, a.nome as aluno_nome, a.email as aluno_email,
           t.nome as treino_nome, t.tipo_treino, t.nivel_dificuldade,
           (SELECT COUNT(*) FROM execucoes_treino te WHERE te.aluno_treino_id = at.id) as total_execucoes
    FROM aluno_treinos at
    JOIN alunos a ON at.aluno_id = a.id
    JOIN treinos t ON at.treino_id = t.id
    WHERE at.professor_id = ?
";
$params_assoc = [$professor_id];

if ($filtro_status) {
    $sql_associacoes .= " AND at.status = ?";
    $params_assoc[] = $filtro_status;
}

if ($filtro_aluno) {
    $sql_associacoes .= " AND at.aluno_id = ?";
    $params_assoc[] = $filtro_aluno;
}

if ($filtro_treino) {
    $sql_associacoes .= " AND at.treino_id = ?";
    $params_assoc[] = $filtro_treino;
}

$sql_associacoes .= " ORDER BY at.created_at DESC";
$associacoes = $db->fetchAll($sql_associacoes, $params_assoc);

// Estatísticas
$stats = [
    'total_associacoes' => count($associacoes),
    'treinos_ativos' => count(array_filter($associacoes, function($a) { return $a['status'] === 'ativo'; })),
    'treinos_concluidos' => count(array_filter($associacoes, function($a) { return $a['status'] === 'concluido'; })),
    'alunos_com_treino' => count(array_unique(array_column($associacoes, 'aluno_id')))
];

include '../includes/header.php';
?>

<!-- Premium Mobile Student Training Page -->
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
                            <span class="text-sm font-medium text-gray-500">Treinos dos Alunos</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- Header Content -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <div class="h-12 w-12 bg-gradient-to-r from-cobalt-500 to-cobalt-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-users text-white text-xl"></i>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Treinos dos Alunos</h1>
                        <p class="text-sm text-gray-500">Associe treinos personalizados aos seus alunos</p>
                    </div>
                </div>
                
                <div class="mt-4 sm:mt-0">
                    <button type="button" onclick="openAssociarModal()" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-gradient-to-r from-cobalt-600 to-cobalt-700 hover:from-cobalt-700 hover:to-cobalt-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 transition-all duration-200">
                        <i class="fas fa-plus -ml-1 mr-2"></i>
                        Associar Treino
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <?php if ($message): ?>
    <div class="mx-4 mt-4" data-aos="fade-in">
        <div class="rounded-xl p-4 <?php echo $message_type === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="<?php echo $message_type === 'success' ? 'fas fa-check-circle text-green-400' : 'fas fa-exclamation-triangle text-red-400'; ?>"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium <?php echo $message_type === 'success' ? 'text-green-800' : 'text-red-800'; ?>">
                        <?= htmlspecialchars($message) ?>
                    </p>
                </div>
                <button type="button" class="ml-auto -mx-1.5 -my-1.5 <?php echo $message_type === 'success' ? 'bg-green-50 text-green-500 hover:bg-green-200' : 'bg-red-50 text-red-500 hover:bg-red-200'; ?> rounded-lg focus:ring-2 focus:ring-<?php echo $message_type === 'success' ? 'green' : 'red'; ?>-400 p-1.5 inline-flex h-8 w-8" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>    <!-- Statistics Cards -->
    <div class="px-4 mt-6" data-aos="fade-up" data-aos-delay="100">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Associations -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clipboard-list text-white text-lg"></i>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-2xl font-bold text-gray-900"><?= $stats['total_associacoes'] ?></p>
                        <p class="text-sm text-gray-500">Total Associações</p>
                    </div>
                </div>
            </div>

            <!-- Active Trainings -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-play-circle text-white text-lg"></i>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-2xl font-bold text-gray-900"><?= $stats['treinos_ativos'] ?></p>
                        <p class="text-sm text-gray-500">Treinos Ativos</p>
                    </div>
                </div>
            </div>

            <!-- Completed Trainings -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 bg-gradient-to-r from-cyan-500 to-cyan-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check-circle text-white text-lg"></i>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-2xl font-bold text-gray-900"><?= $stats['treinos_concluidos'] ?></p>
                        <p class="text-sm text-gray-500">Concluídos</p>
                    </div>
                </div>
            </div>

            <!-- Active Students -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user-friends text-white text-lg"></i>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-2xl font-bold text-gray-900"><?= $stats['alunos_com_treino'] ?></p>
                        <p class="text-sm text-gray-500">Alunos Ativos</p>
                    </div>
                </div>
            </div>
        </div>
    </div>    <!-- Filters Section -->
    <div class="px-4 mt-6" data-aos="fade-up" data-aos-delay="200">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-filter text-cobalt-600 mr-2"></i>
                    Filtros de Busca
                </h3>
            </div>
            <div class="p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-2">
                            📊 Status
                        </label>
                        <select name="status" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3 transition-colors duration-200">
                            <option value="">Todos os Status</option>
                            <option value="ativo" <?= $filtro_status === 'ativo' ? 'selected' : '' ?>>🟢 Ativo</option>
                            <option value="pausado" <?= $filtro_status === 'pausado' ? 'selected' : '' ?>>⏸️ Pausado</option>
                            <option value="concluido" <?= $filtro_status === 'concluido' ? 'selected' : '' ?>>✅ Concluído</option>
                            <option value="cancelado" <?= $filtro_status === 'cancelado' ? 'selected' : '' ?>>❌ Cancelado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-2">
                            👤 Aluno
                        </label>
                        <select name="aluno" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3 transition-colors duration-200">
                            <option value="">Todos os Alunos</option>
                            <?php foreach ($alunos as $aluno): ?>
                            <option value="<?= $aluno['id'] ?>" <?= $filtro_aluno == $aluno['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($aluno['nome']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-2">
                            🏋️ Treino
                        </label>
                        <select name="treino" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3 transition-colors duration-200">
                            <option value="">Todos os Treinos</option>
                            <?php foreach ($treinos as $treino): ?>
                            <option value="<?= $treino['id'] ?>" <?= $filtro_treino == $treino['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($treino['nome']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end space-x-2">
                        <button type="submit" 
                                class="flex-1 inline-flex justify-center items-center px-4 py-3 border border-transparent text-sm font-medium rounded-lg text-white bg-cobalt-600 hover:bg-cobalt-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 transition-colors">
                            <i class="fas fa-search mr-2"></i>
                            Filtrar
                        </button>
                        <a href="treino-alunos.php" 
                           class="inline-flex items-center px-4 py-3 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 transition-colors">
                            <i class="fas fa-times mr-2"></i>
                            Limpar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>    <!-- Training Associations List -->
    <div class="px-4 mt-6 pb-6" data-aos="fade-up" data-aos-delay="300">
        <div class="mb-6">
            <h3 class="text-xl font-bold text-gray-900 flex items-center">
                <i class="fas fa-link mr-3 text-cobalt-600"></i>
                Treinos Associados
                <span class="ml-2 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                    <?php echo count($associacoes); ?>
                </span>
            </h3>
        </div>
        
        <?php if (empty($associacoes)): ?>
        <!-- Empty State -->
        <div class="text-center py-12 bg-white rounded-xl border border-gray-200">
            <div class="mx-auto h-24 w-24 text-gray-400 mb-6">
                <i class="fas fa-link text-6xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma associação encontrada</h3>
            <p class="text-gray-500 mb-6 max-w-md mx-auto">
                Comece associando treinos aos seus alunos para acompanhar o progresso deles.
            </p>
            <button type="button" onclick="openAssociarModal()" 
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-cobalt-600 to-cobalt-700 hover:from-cobalt-700 hover:to-cobalt-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 transition-all duration-200">
                <i class="fas fa-plus mr-2"></i>
                Associar Primeiro Treino
            </button>
        </div>
        <?php else: ?>
        <!-- Associations Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($associacoes as $assoc): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-200" 
                 data-aos="zoom-in" data-aos-delay="<?php echo 100 + (array_search($assoc, $associacoes) * 50); ?>">
                
                <!-- Card Header -->
                <div class="p-6 pb-4">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h4 class="text-lg font-bold text-gray-900 mb-1">
                                <?= htmlspecialchars($assoc['aluno_nome']) ?>
                            </h4>
                            <p class="text-sm text-gray-500">
                                <?= htmlspecialchars($assoc['aluno_email']) ?>
                            </p>
                        </div>
                        <?php
                        $status_classes = [
                            'ativo' => 'bg-green-100 text-green-800',
                            'pausado' => 'bg-yellow-100 text-yellow-800',
                            'concluido' => 'bg-blue-100 text-blue-800',
                            'cancelado' => 'bg-red-100 text-red-800'
                        ];
                        $status_icons = [
                            'ativo' => '🟢',
                            'pausado' => '⏸️',
                            'concluido' => '✅',
                            'cancelado' => '❌'
                        ];
                        $status_class = $status_classes[$assoc['status']] ?? 'bg-gray-100 text-gray-800';
                        $status_icon = $status_icons[$assoc['status']] ?? '📋';
                        ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $status_class ?>">
                            <?= $status_icon ?> <?= ucfirst($assoc['status']) ?>
                        </span>
                    </div>
                    
                    <!-- Training Info -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                        <h5 class="font-semibold text-gray-900 mb-2">
                            <?= htmlspecialchars($assoc['treino_nome']) ?>
                        </h5>
                        <div class="flex flex-wrap gap-2">
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
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-cobalt-100 text-cobalt-800">
                                <?= ($tipo_icons[$assoc['tipo_treino']] ?? '📋') . ' ' . ucfirst($assoc['tipo_treino']) ?>
                            </span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $nivel_colors[$assoc['nivel_dificuldade']] ?? 'bg-gray-100 text-gray-800' ?>">
                                <?= ($nivel_icons[$assoc['nivel_dificuldade']] ?? '') . ' ' . ucfirst($assoc['nivel_dificuldade']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Period Info -->
                    <div class="space-y-2 text-sm text-gray-600">
                        <div class="flex items-center">
                            <i class="fas fa-calendar-plus text-green-600 mr-2"></i>
                            <span><strong>Início:</strong> <?= date('d/m/Y', strtotime($assoc['data_inicio'])) ?></span>
                        </div>
                        <?php if ($assoc['data_fim']): ?>
                        <div class="flex items-center">
                            <i class="fas fa-calendar-times text-red-600 mr-2"></i>
                            <span><strong>Fim:</strong> <?= date('d/m/Y', strtotime($assoc['data_fim'])) ?></span>
                        </div>
                        <?php else: ?>
                        <div class="flex items-center">
                            <i class="fas fa-infinity text-gray-400 mr-2"></i>
                            <span class="text-gray-500">Sem data limite</span>
                        </div>
                        <?php endif; ?>
                        <div class="flex items-center">
                            <i class="fas fa-chart-line text-blue-600 mr-2"></i>
                            <span><strong>Execuções:</strong> <?= $assoc['total_execucoes'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- Card Actions -->
                <div class="px-6 py-4 bg-gray-50 rounded-b-xl border-t border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex space-x-2">
                            <button type="button" onclick="verDetalhes(<?= $assoc['id'] ?>)" 
                                    class="inline-flex items-center p-2 border border-gray-300 rounded-lg text-gray-400 hover:text-gray-600 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 transition-colors" 
                                    title="Ver Detalhes">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                            
                            <button type="button" onclick="compartilhar('<?= $assoc['url_compartilhamento'] ?>')" 
                                    class="inline-flex items-center p-2 border border-gray-300 rounded-lg text-gray-400 hover:text-green-600 hover:border-green-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors" 
                                    title="Compartilhar">
                                <i class="fas fa-share-alt text-sm"></i>
                            </button>
                        </div>
                        
                        <div class="relative">
                            <button type="button" onclick="toggleStatusMenu(<?= $assoc['id'] ?>)" 
                                    class="inline-flex items-center p-2 border border-gray-300 rounded-lg text-gray-400 hover:text-gray-600 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 transition-colors" 
                                    title="Alterar Status">
                                <i class="fas fa-cog text-sm"></i>
                            </button>
                            
                            <div id="statusMenu<?= $assoc['id'] ?>" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-200 z-10">
                                <div class="py-1">
                                    <a href="#" onclick="alterarStatus(<?= $assoc['id'] ?>, 'ativo')" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-play text-green-600 mr-2"></i> Ativar
                                    </a>
                                    <a href="#" onclick="alterarStatus(<?= $assoc['id'] ?>, 'pausado')" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-pause text-yellow-600 mr-2"></i> Pausar
                                    </a>
                                    <a href="#" onclick="alterarStatus(<?= $assoc['id'] ?>, 'concluido')" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-check text-blue-600 mr-2"></i> Concluir
                                    </a>
                                    <hr class="my-1 border-gray-200">
                                    <a href="#" onclick="alterarStatus(<?= $assoc['id'] ?>, 'cancelado')" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                        <i class="fas fa-times mr-2"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para Associar Treino -->
<div id="modalAssociar" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative w-full max-w-4xl max-h-full mx-auto">
        <!-- Modal content -->
        <div class="relative bg-white rounded-2xl shadow-2xl border border-gray-200">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 rounded-t-2xl bg-gradient-to-r from-cobalt-50 to-cobalt-100">
                <h3 class="text-xl font-bold text-gray-900 flex items-center">
                    <div class="h-8 w-8 bg-gradient-to-r from-cobalt-500 to-cobalt-600 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-link text-white text-sm"></i>
                    </div>
                    Associar Treino ao Aluno
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center transition-colors duration-200" onclick="closeAssociarModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Modal body -->
            <form method="POST" action="?action=associar" id="associarForm">
                <div class="p-6 space-y-6">
                    <!-- Student and Training Selection -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="aluno_id" class="block mb-2 text-sm font-medium text-gray-900">
                                👤 Aluno <span class="text-red-500">*</span>
                            </label>
                            <select name="aluno_id" id="aluno_id" 
                                    class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3 transition-colors duration-200" 
                                    required>
                                <option value="">Selecione um aluno</option>
                                <?php foreach ($alunos as $aluno): ?>
                                <option value="<?= $aluno['id'] ?>">
                                    <?= htmlspecialchars($aluno['nome']) ?> - <?= htmlspecialchars($aluno['email']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="treino_id" class="block mb-2 text-sm font-medium text-gray-900">
                                🏋️ Treino <span class="text-red-500">*</span>
                            </label>
                            <select name="treino_id" id="treino_id" 
                                    class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3 transition-colors duration-200" 
                                    required>
                                <option value="">Selecione um treino</option>
                                <?php foreach ($treinos as $treino): ?>
                                <option value="<?= $treino['id'] ?>">
                                    <?= htmlspecialchars($treino['nome']) ?> 
                                    (<?= ucfirst($treino['tipo_treino']) ?> - <?= ucfirst($treino['nivel_dificuldade']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Date Period -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="data_inicio" class="block mb-2 text-sm font-medium text-gray-900">
                                📅 Data de Início
                            </label>
                            <input type="date" name="data_inicio" id="data_inicio" 
                                   class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3 transition-colors duration-200" 
                                   value="<?= date('Y-m-d') ?>">
                        </div>
                        <div>
                            <label for="data_fim" class="block mb-2 text-sm font-medium text-gray-900">
                                📅 Data de Fim (opcional)
                            </label>
                            <input type="date" name="data_fim" id="data_fim" 
                                   class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3 transition-colors duration-200">
                        </div>
                    </div>
                    
                    <!-- Individual Observations -->
                    <div>
                        <label for="observacoes_individuais" class="block mb-2 text-sm font-medium text-gray-900">
                            📝 Observações Individuais
                        </label>
                        <textarea name="observacoes_individuais" id="observacoes_individuais" rows="4" 
                                  class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-cobalt-500 focus:border-cobalt-500 block w-full p-3 transition-colors duration-200" 
                                  placeholder="Adaptações específicas para este aluno, orientações especiais, etc..."></textarea>
                    </div>
                </div>
                
                <!-- Modal footer -->
                <div class="flex items-center justify-end p-6 space-x-2 border-t border-gray-200 rounded-b-2xl bg-gray-50">
                    <button type="button" onclick="closeAssociarModal()" 
                            class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 transition-colors duration-200">
                        <i class="fas fa-times mr-2"></i>
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="text-white bg-gradient-to-r from-cobalt-600 to-cobalt-700 hover:from-cobalt-700 hover:to-cobalt-800 focus:ring-4 focus:outline-none focus:ring-cobalt-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-all duration-200">
                        <i class="fas fa-link mr-2"></i>
                        Associar Treino
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Compartilhamento -->
<div id="modalCompartilhar" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative w-full max-w-2xl max-h-full mx-auto">
        <!-- Modal content -->
        <div class="relative bg-white rounded-2xl shadow-2xl border border-gray-200">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 rounded-t-2xl bg-gradient-to-r from-green-50 to-emerald-50">
                <h3 class="text-xl font-bold text-gray-900 flex items-center">
                    <div class="h-8 w-8 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-share-alt text-white text-sm"></i>
                    </div>
                    Compartilhar Treino
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center transition-colors duration-200" onclick="closeCompartilharModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Modal body -->
            <div class="p-6 text-center space-y-6">
                <!-- QR Code Container -->
                <div class="bg-gray-50 rounded-xl p-6">
                    <div id="qrcode" class="flex justify-center mb-4"></div>
                    <p class="text-sm text-gray-600">Escaneie o QR Code para acesso rápido</p>
                </div>
                
                <!-- Link Sharing -->
                <div class="space-y-4">
                    <div class="flex rounded-lg border border-gray-300 overflow-hidden">
                        <input type="text" id="linkCompartilhamento" 
                               class="flex-1 px-4 py-3 text-sm text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-cobalt-500" 
                               readonly>
                        <button type="button" onclick="copiarLink()" 
                                class="px-4 py-3 bg-gray-100 text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-cobalt-500 transition-colors duration-200 border-l border-gray-300">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    
                    <!-- WhatsApp Share Button -->
                    <button type="button" onclick="compartilharWhatsApp()" 
                            class="w-full inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200">
                        <i class="fab fa-whatsapp mr-2 text-lg"></i>
                        Compartilhar no WhatsApp
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
// Modal Control Functions
function openAssociarModal() {
    const modal = document.getElementById('modalAssociar');
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeAssociarModal() {
    const modal = document.getElementById('modalAssociar');
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    
    // Reset form
    document.getElementById('associarForm').reset();
}

function closeCompartilharModal() {
    const modal = document.getElementById('modalCompartilhar');
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

// Status Menu Toggle
function toggleStatusMenu(associacaoId) {
    // Close all other status menus
    document.querySelectorAll('[id^="statusMenu"]').forEach(menu => {
        if (menu.id !== `statusMenu${associacaoId}`) {
            menu.classList.add('hidden');
        }
    });
    
    // Toggle current menu
    const menu = document.getElementById(`statusMenu${associacaoId}`);
    menu.classList.toggle('hidden');
}

// Close status menus when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('[onclick*="toggleStatusMenu"]') && !event.target.closest('[id^="statusMenu"]')) {
        document.querySelectorAll('[id^="statusMenu"]').forEach(menu => {
            menu.classList.add('hidden');
        });
    }
});

// Status Change Function
function alterarStatus(associacaoId, novoStatus) {
    const statusNames = {
        'ativo': 'ativar',
        'pausado': 'pausar',
        'concluido': 'concluir',
        'cancelado': 'cancelar'
    };
    
    if (confirm(`Deseja ${statusNames[novoStatus]} este treino?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?action=alterar_status';
        
        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'associacao_id';
        inputId.value = associacaoId;
        
        const inputStatus = document.createElement('input');
        inputStatus.type = 'hidden';
        inputStatus.name = 'status';
        inputStatus.value = novoStatus;
        
        form.appendChild(inputId);
        form.appendChild(inputStatus);
        document.body.appendChild(form);
        form.submit();
    }
    
    // Hide menu after action
    document.getElementById(`statusMenu${associacaoId}`).classList.add('hidden');
}

// Share Functions
function compartilhar(hash) {
    const baseUrl = window.location.origin + window.location.pathname.replace('pages/treino-alunos.php', '');
    const link = baseUrl + 'treino.php?hash=' + hash;
    
    document.getElementById('linkCompartilhamento').value = link;
    
    // Generate QR Code
    const qrCodeDiv = document.getElementById('qrcode');
    qrCodeDiv.innerHTML = '';
    QRCode.toCanvas(qrCodeDiv, link, {
        width: 200,
        height: 200,
        margin: 2,
        color: {
            dark: '#1F2937',
            light: '#FFFFFF'
        },
        errorCorrectionLevel: 'M'
    });
    
    // Store link for later use
    window.currentShareLink = link;
    
    // Show modal
    const modal = document.getElementById('modalCompartilhar');
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function copiarLink() {
    const linkInput = document.getElementById('linkCompartilhamento');
    linkInput.select();
    linkInput.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        document.execCommand('copy');
        showToast('Link copiado para a área de transferência!', 'success');
    } catch (err) {
        // Fallback for modern browsers
        navigator.clipboard.writeText(linkInput.value).then(() => {
            showToast('Link copiado para a área de transferência!', 'success');
        }).catch(() => {
            showToast('Erro ao copiar link', 'error');
        });
    }
}

function compartilharWhatsApp() {
    const link = window.currentShareLink;
    const mensagem = encodeURIComponent(`Olá! Aqui está seu treino personalizado: ${link}`);
    const whatsappUrl = `https://wa.me/?text=${mensagem}`;
    window.open(whatsappUrl, '_blank');
}

function verDetalhes(associacaoId) {
    // Open training details in a new tab
    window.open('../api/treino-detalhes.php?id=' + associacaoId, '_blank');
}

// Toast Notification System
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    const bgColor = {
        success: 'bg-green-500',
        error: 'bg-red-500', 
        warning: 'bg-yellow-500',
        info: 'bg-cobalt-500'
    }[type];
    
    const icon = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-triangle',
        warning: 'fas fa-exclamation-circle', 
        info: 'fas fa-info-circle'
    }[type];
    
    toast.className = `${bgColor} text-white px-6 py-4 rounded-xl shadow-lg transform transition-all duration-300 translate-x-full fixed top-4 right-4 z-50 max-w-sm`;
    toast.innerHTML = `
        <div class="flex items-center space-x-3">
            <i class="${icon}"></i>
            <span class="font-medium">${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white/80 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 5000);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const associarModal = document.getElementById('modalAssociar');
        const compartilharModal = document.getElementById('modalCompartilhar');
        
        if (!associarModal.classList.contains('hidden')) {
            closeAssociarModal();
        }
        if (!compartilharModal.classList.contains('hidden')) {
            closeCompartilharModal();
        }
        
        // Close all status menus
        document.querySelectorAll('[id^="statusMenu"]').forEach(menu => {
            menu.classList.add('hidden');
        });
    }
});

// Initialize AOS animations if available
document.addEventListener('DOMContentLoaded', function() {
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 600,
            easing: 'ease-out-cubic',
            once: true,
            offset: 50
        });
    }
});
</script>

<!-- Barra de navegação removida - utilizando apenas a do header.php -->

<?php include '../includes/footer.php'; ?>
