<?php
/**
 * SMARTBIOFIT - Módulo de Avaliações Físicas
 * Milestone 3: Sistema completo de avaliação física
 */

require_once '../config.php';
require_once '../database.php';

// Verificar autenticação
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'professor')) {
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

$db = Database::getInstance();
$message = '';
$messageType = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'nova_avaliacao':
            try {
                $sql = "INSERT INTO avaliacoes (aluno_id, professor_id, data_avaliacao, tipo_avaliacao, observacoes) VALUES (?, ?, ?, ?, ?)";
                $params = [
                    $_POST['aluno_id'],
                    $_SESSION['user_id'],
                    $_POST['data_avaliacao'],
                    $_POST['tipo_avaliacao'],
                    $_POST['observacoes'] ?: null
                ];
                
                $db->execute($sql, $params);
                $avaliacao_id = $db->lastInsertId();
                
                logActivity($_SESSION['user_id'], 'criar_avaliacao', "Nova avaliação criada - ID: $avaliacao_id");
                
                $message = 'Avaliação criada com sucesso!';
                $messageType = 'success';
                
                // Redirecionar para o formulário específico
                header("Location: avaliacao-form.php?id=$avaliacao_id&tipo=" . $_POST['tipo_avaliacao']);
                exit;
                
            } catch (Exception $e) {
                $message = 'Erro ao criar avaliação: ' . $e->getMessage();
                $messageType = 'error';
            }
            break;
    }
}

// Buscar alunos do professor
$alunos = $db->fetchAll("SELECT id, nome FROM alunos WHERE professor_id = ? AND ativo = 1 ORDER BY nome", [$_SESSION['user_id']]);

// Buscar avaliações recentes
$avaliacoes_recentes = $db->fetchAll("
    SELECT a.*, al.nome as aluno_nome, 
           CASE 
               WHEN a.tipo_avaliacao = 'anamnese' THEN 'fas fa-clipboard-list'
               WHEN a.tipo_avaliacao = 'composicao' THEN 'fas fa-weight'
               WHEN a.tipo_avaliacao = 'perimetria' THEN 'fas fa-ruler'
               WHEN a.tipo_avaliacao = 'postural' THEN 'fas fa-camera'
               WHEN a.tipo_avaliacao = 'cardio' THEN 'fas fa-heartbeat'
           END as icone
    FROM avaliacoes a 
    JOIN alunos al ON a.aluno_id = al.id 
    WHERE a.professor_id = ? 
    ORDER BY a.created_at DESC 
    LIMIT 10
", [$_SESSION['user_id']]);

// Estatísticas rápidas
$stats = $db->fetch("
    SELECT 
        COUNT(*) as total_avaliacoes,
        COUNT(CASE WHEN status = 'completa' THEN 1 END) as completas,
        COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pendentes,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as hoje
    FROM avaliacoes 
    WHERE professor_id = ?
", [$_SESSION['user_id']]);

include '../includes/header.php';
?>

<!-- Premium Mobile Evaluations Page -->
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
                            <span class="text-sm font-medium text-gray-500">Avaliações Físicas</span>
                        </div>
                    </li>
                </ol>
            </nav>            <!-- Header Content -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <div class="h-12 w-12 bg-gradient-to-r from-cobalt-500 to-cobalt-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-clipboard-check text-white text-xl"></i>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Avaliações Físicas</h1>
                        <p class="text-sm text-gray-500">Sistema completo de avaliação física e composição corporal</p>
                    </div>
                </div>
                <!-- Action Buttons -->
                <div class="flex items-center space-x-3 mt-4 sm:mt-0">
                    <a href="graficos-comparativos.php" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 shadow-sm transition-all duration-200 transform hover:scale-105">
                        <i class="fas fa-chart-line mr-2"></i>
                        Gráficos Comparativos
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($message): ?>
    <div class="mx-4 mt-4" data-aos="fade-in">
        <div class="bg-<?php echo $messageType === 'success' ? 'green' : ($messageType === 'error' ? 'red' : 'blue'); ?>-50 border border-<?php echo $messageType === 'success' ? 'green' : ($messageType === 'error' ? 'red' : 'blue'); ?>-200 text-<?php echo $messageType === 'success' ? 'green' : ($messageType === 'error' ? 'red' : 'blue'); ?>-800 px-4 py-3 rounded-lg shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-triangle' : 'info-circle'); ?> mr-3"></i>
                <span class="font-medium"><?php echo htmlspecialchars($message); ?></span>
                <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-<?php echo $messageType === 'success' ? 'green' : ($messageType === 'error' ? 'red' : 'blue'); ?>-50 text-<?php echo $messageType === 'success' ? 'green' : ($messageType === 'error' ? 'red' : 'blue'); ?>-500 rounded-lg focus:ring-2 focus:ring-<?php echo $messageType === 'success' ? 'green' : ($messageType === 'error' ? 'red' : 'blue'); ?>-400 p-1.5 hover:bg-<?php echo $messageType === 'success' ? 'green' : ($messageType === 'error' ? 'red' : 'blue'); ?>-200 inline-flex h-8 w-8" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="px-4 mt-6" data-aos="fade-up">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Evaluations -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clipboard-list text-blue-600"></i>
                        </div>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-2xl font-bold text-gray-900">
                            <?php echo $stats['total_avaliacoes'] ?? 0; ?>
                        </p>
                        <p class="text-xs text-gray-500 font-medium">Total</p>
                    </div>
                </div>
            </div>

            <!-- Complete Evaluations -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600"></i>
                        </div>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-2xl font-bold text-gray-900">
                            <?php echo $stats['completas'] ?? 0; ?>
                        </p>
                        <p class="text-xs text-gray-500 font-medium">Completas</p>
                    </div>
                </div>
            </div>

            <!-- Pending Evaluations -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-600"></i>
                        </div>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-2xl font-bold text-gray-900">
                            <?php echo $stats['pendentes'] ?? 0; ?>
                        </p>
                        <p class="text-xs text-gray-500 font-medium">Pendentes</p>
                    </div>
                </div>
            </div>

            <!-- Today's Evaluations -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-day text-purple-600"></i>
                        </div>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-2xl font-bold text-gray-900">
                            <?php echo $stats['hoje'] ?? 0; ?>
                        </p>
                        <p class="text-xs text-gray-500 font-medium">Hoje</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Evaluation Types -->
    <div class="px-4 mt-6" data-aos="fade-up" data-aos-delay="100">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-plus-circle mr-2 text-cobalt-600"></i>
                    Nova Avaliação Física
                </h3>
                <p class="text-gray-600 mt-1">Selecione o tipo de avaliação que deseja realizar:</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Anamnese -->
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 hover:bg-gray-100 transition-all duration-300 cursor-pointer transform hover:scale-105 hover:shadow-lg" onclick="openNewEvaluation('anamnese')">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg flex items-center justify-center mb-3">
                                <i class="fas fa-clipboard-list text-white text-lg"></i>
                            </div>
                            <h4 class="text-base font-bold text-gray-900 mb-2">Anamnese</h4>
                            <p class="text-xs text-gray-600">Questionário inicial de saúde e histórico médico</p>
                        </div>
                    </div>

                    <!-- Composição Corporal -->
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 hover:bg-gray-100 transition-all duration-300 cursor-pointer transform hover:scale-105 hover:shadow-lg" onclick="openNewEvaluation('composicao')">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center mb-3">
                                <i class="fas fa-weight text-white text-lg"></i>
                            </div>
                            <h4 class="text-base font-bold text-gray-900 mb-2">Composição Corporal</h4>
                            <p class="text-xs text-gray-600">Análise de percentual de gordura e massa magra</p>
                        </div>
                    </div>

                    <!-- Perimetria -->
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 hover:bg-gray-100 transition-all duration-300 cursor-pointer transform hover:scale-105 hover:shadow-lg" onclick="openNewEvaluation('perimetria')">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg flex items-center justify-center mb-3">
                                <i class="fas fa-ruler text-white text-lg"></i>
                            </div>
                            <h4 class="text-base font-bold text-gray-900 mb-2">Perimetria</h4>
                            <p class="text-xs text-gray-600">Medidas corporais padronizadas</p>
                        </div>
                    </div>

                    <!-- Avaliação Postural -->
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 hover:bg-gray-100 transition-all duration-300 cursor-pointer transform hover:scale-105 hover:shadow-lg" onclick="openNewEvaluation('postural')">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-lg flex items-center justify-center mb-3">
                                <i class="fas fa-camera text-white text-lg"></i>
                            </div>
                            <h4 class="text-base font-bold text-gray-900 mb-2">Avaliação Postural</h4>
                            <p class="text-xs text-gray-600">Análise fotográfica da postura</p>
                        </div>
                    </div>

                    <!-- Teste Cardiorrespiratório -->
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 hover:bg-gray-100 transition-all duration-300 cursor-pointer transform hover:scale-105 hover:shadow-lg" onclick="openNewEvaluation('cardio')">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-gradient-to-r from-red-500 to-red-600 rounded-lg flex items-center justify-center mb-3">
                                <i class="fas fa-heartbeat text-white text-lg"></i>
                            </div>
                            <h4 class="text-base font-bold text-gray-900 mb-2">Teste Cardiorrespiratório</h4>
                            <p class="text-xs text-gray-600">Testes de VO2Max e capacidade cardiovascular</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Evaluations -->
    <div class="px-4 mt-6 pb-6" data-aos="fade-up" data-aos-delay="200">
        <div class="mb-6">
            <h3 class="text-xl font-bold text-gray-900 flex items-center">
                <i class="fas fa-history mr-3 text-cobalt-600"></i>
                Avaliações Recentes
                <span class="ml-2 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                    <?php echo count($avaliacoes_recentes); ?>
                </span>
            </h3>
        </div>

        <?php if (empty($avaliacoes_recentes)): ?>
        <!-- Empty State -->
        <div class="text-center py-12 bg-white rounded-xl border border-gray-200">
            <div class="mx-auto h-24 w-24 text-gray-400 mb-6">
                <i class="fas fa-clipboard-list text-6xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma avaliação encontrada</h3>
            <p class="text-gray-500 mb-6 max-w-md mx-auto">
                Comece criando sua primeira avaliação física para seus alunos.
            </p>
        </div>
        <?php else: ?>
        <!-- Evaluations List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="divide-y divide-gray-200">
                <?php foreach ($avaliacoes_recentes as $avaliacao): ?>
                <div class="p-4 hover:bg-gray-50 transition-colors cursor-pointer">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 mr-4">
                            <div class="w-10 h-10 bg-gradient-to-r from-cobalt-500 to-cobalt-600 rounded-lg flex items-center justify-center">
                                <i class="<?php echo $avaliacao['icone']; ?> text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-base font-semibold text-gray-900 truncate">
                                <?php echo htmlspecialchars($avaliacao['aluno_nome']); ?>
                            </h4>
                            <div class="flex items-center text-xs text-gray-600 mt-1">
                                <span class="mr-2">
                                    <?php 
                                    $tipos = [
                                        'anamnese' => 'Anamnese',
                                        'composicao' => 'Composição Corporal',
                                        'perimetria' => 'Perimetria',
                                        'postural' => 'Avaliação Postural',
                                        'cardio' => 'Teste Cardiorrespiratório'
                                    ];
                                    echo $tipos[$avaliacao['tipo_avaliacao']];
                                    ?>
                                </span>
                                <span class="mx-1">•</span>
                                <span><?php echo date('d/m/Y', strtotime($avaliacao['data_avaliacao'])); ?></span>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?php 
                                echo match($avaliacao['status']) {
                                    'completa' => 'bg-green-100 text-green-800',
                                    'pendente' => 'bg-yellow-100 text-yellow-800',
                                    'em_andamento' => 'bg-blue-100 text-blue-800',
                                    'revisao' => 'bg-orange-100 text-orange-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            ?>">
                                <?php echo str_replace('_', ' ', ucfirst($avaliacao['status'])); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Nova Avaliação -->
<div id="newEvaluationModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Background overlay -->
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        
        <!-- This element is to trick the browser into centering the modal contents. -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">
                                Nova Avaliação
                            </h3>
                            <button type="button" class="bg-white rounded-md text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500" onclick="closeModal()">
                                <span class="sr-only">Fechar</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        
                        <form method="POST" id="newEvaluationForm">
                            <input type="hidden" name="action" value="nova_avaliacao">
                            <input type="hidden" name="tipo_avaliacao" id="tipoAvaliacao">
                            
                            <div class="space-y-4">
                                <div>                                    <label for="aluno_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-user mr-2"></i>
                                        Selecionar Aluno *
                                    </label>
                                    <select id="aluno_id" name="aluno_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-cobalt-500 focus:border-cobalt-500 text-gray-900" required>
                                        <option value="">Escolha um aluno...</option>
                                        <?php foreach ($alunos as $aluno): ?>
                                        <option value="<?php echo $aluno['id']; ?>"><?php echo htmlspecialchars($aluno['nome']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>                                    <label for="data_avaliacao" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar mr-2"></i>
                                        Data da Avaliação *
                                    </label>
                                    <input type="date" id="data_avaliacao" name="data_avaliacao" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-cobalt-500 focus:border-cobalt-500 text-gray-900" 
                                           required value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                
                                <div>                                    <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-sticky-note mr-2"></i>
                                        Observações Iniciais
                                    </label>
                                    <textarea id="observacoes" name="observacoes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-cobalt-500 focus:border-cobalt-500 text-gray-900" 
                                              placeholder="Observações gerais sobre a avaliação..."></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="submit" form="newEvaluationForm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-cobalt-600 text-base font-medium text-white hover:bg-cobalt-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalt-500 sm:ml-3 sm:w-auto sm:text-sm">
                    <i class="fas fa-plus mr-2"></i>
                    Criar Avaliação
                </button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal()">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// SCRIPT IMEDIATO PARA GARANTIR QUE O MODAL ESTÁ OCULTO
(function() {
    // Garantir que modal está oculto
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('newEvaluationModal');
        if (modal) {
            modal.classList.add('hidden');
        }
        
        // Remover qualquer overflow hidden do body
        document.body.style.overflow = '';
    });
})();

function openNewEvaluation(tipo) {
    const tipos = {
        'anamnese': { nome: 'Anamnese', icone: 'fas fa-clipboard-list' },
        'composicao': { nome: 'Composição Corporal', icone: 'fas fa-weight-hanging' },
        'perimetria': { nome: 'Perimetria', icone: 'fas fa-ruler' },
        'postural': { nome: 'Avaliação Postural', icone: 'fas fa-camera' },
        'cardio': { nome: 'Teste Cardiorrespiratório', icone: 'fas fa-heartbeat' }
    };
    
    document.getElementById('tipoAvaliacao').value = tipo;
    document.getElementById('modalTitle').innerHTML = `
        <i class="${tipos[tipo].icone} mr-2"></i>
        Nova ${tipos[tipo].nome}
    `;
    
    // Show modal with Tailwind CSS
    const modal = document.getElementById('newEvaluationModal');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Focus no primeiro campo após animação
    setTimeout(() => {
        document.getElementById('aluno_id').focus();
    }, 100);
}

function closeModal() {
    const modal = document.getElementById('newEvaluationModal');
    modal.classList.add('hidden');
    document.body.style.overflow = '';
    
    // Reset form
    document.getElementById('newEvaluationForm').reset();
}

// Fechar modal clicando no backdrop
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('bg-gray-500') && event.target.classList.contains('bg-opacity-75')) {
        closeModal();
    }
});

// Fechar modal com ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('newEvaluationModal');
        if (!modal.classList.contains('hidden')) {
            closeModal();
        }
    }
});

// Auto-hide alerts
setTimeout(() => {
    const alert = document.getElementById('alert-message');
    if (alert) {
        alert.style.opacity = '0';
        setTimeout(() => {
            alert.style.display = 'none';
        }, 300);
    }
}, 5000);

// Validação do formulário
document.getElementById('newEvaluationForm').addEventListener('submit', function(e) {
    const aluno = document.getElementById('aluno_id').value;
    const data = document.getElementById('data_avaliacao').value;
    
    if (!aluno) {
        e.preventDefault();
        showAlert('Por favor, selecione um aluno.', 'warning');
        return;
    }
    
    if (!data) {
        e.preventDefault();
        showAlert('Por favor, informe a data da avaliação.', 'warning');
        return;
    }
    
    // Verificar se a data não é futura
    const hoje = new Date();
    const dataAvaliacao = new Date(data);
    
    if (dataAvaliacao > hoje) {
        e.preventDefault();
        showAlert('A data da avaliação não pode ser futura.', 'error');
        return;
    }
});

// Função para mostrar alertas
function showAlert(message, type = 'info') {
    // Remove existing alerts
    const existingAlert = document.querySelector('.alert-notification');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // Define alert styles based on type
    const alertStyles = {
        error: 'bg-red-50 border-red-200 text-red-800',
        warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
        info: 'bg-blue-50 border-blue-200 text-blue-800',
        success: 'bg-green-50 border-green-200 text-green-800'
    };
    
    // Create new alert
    const alert = document.createElement('div');
    alert.className = `alert-notification mx-4 mt-4 ${alertStyles[type]} px-4 py-3 rounded-lg shadow-sm border`;
    alert.innerHTML = `
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="${type === 'success' ? 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' : type === 'error' ? 'M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z' : 'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z'}" clip-rule="evenodd"></path>
            </svg>
            <span>${message}</span>
            <button class="ml-auto text-current hover:text-opacity-70" onclick="this.parentElement.parentElement.remove()">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    `;
    
    // Insert at top of container
    const container = document.querySelector('div.min-h-screen');
    const header = container.querySelector('div.bg-white.border-b');
    container.insertBefore(alert, header.nextSibling);
    
    // Auto hide
    setTimeout(() => {
        if (alert.parentElement) {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }
    }, 5000);
}
</script>

<?php include '../includes/footer.php'; ?>
