<?php
/**
 * SMARTBIOFIT - Avaliações do Aluno
 * Histórico e progresso das avaliações do aluno
 */

require_once '../config.php';
require_once 'includes/auth-aluno.php';

// Buscar avaliações do aluno
try {
    // Avaliações físicas
    $stmt = $pdo->prepare("
        SELECT * FROM avaliacoes 
        WHERE aluno_id = ? 
        ORDER BY data_avaliacao DESC
    ");
    $stmt->execute([$_SESSION['aluno_id']]);
    $avaliacoes = $stmt->fetchAll();
    
    // Testes de VO2Max
    $stmt = $pdo->prepare("
        SELECT * FROM vo2max 
        WHERE aluno_id = ? 
        ORDER BY data_teste DESC
    ");
    $stmt->execute([$_SESSION['aluno_id']]);
    $vo2max_tests = $stmt->fetchAll();
    
    // Avaliações posturais
    $stmt = $pdo->prepare("
        SELECT * FROM avaliacao_postural 
        WHERE aluno_id = ? 
        ORDER BY data_avaliacao DESC
    ");
    $stmt->execute([$_SESSION['aluno_id']]);
    $avaliacoes_posturais = $stmt->fetchAll();
    
} catch (Exception $e) {
    $erro = "Erro ao carregar avaliações: " . $e->getMessage();
    error_log($erro);
}

// Incluir header padrão
include 'includes/header-aluno.php';
?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script><!-- Page Header -->
<div class="bg-white border-b border-gray-200 px-6 py-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Minhas Avaliações</h1>
            <p class="text-gray-600 mt-1">Acompanhe seu progresso e evolução física</p>
        </div>
        <div class="bg-cobalt-50 px-4 py-2 rounded-lg">
            <span class="text-cobalt-700 font-medium"><?= count($avaliacoes) + count($vo2max_tests) + count($avaliacoes_posturais) ?> avaliação(ões) total</span>
        </div>
    </div>
</div>

<!-- Content -->
<div class="p-6">
    <?php if (isset($erro)): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Erro</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p><?= htmlspecialchars($erro) ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-r from-cobalt-500 to-cobalt-600 rounded-xl p-6 text-white">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold"><?= count($avaliacoes) ?></h3>
                    <p class="text-cobalt-100">Avaliações Físicas</p>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 text-white">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold"><?= count($vo2max_tests) ?></h3>
                    <p class="text-green-100">Testes VO2Max</p>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-6 text-white">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold"><?= count($avaliacoes_posturais) ?></h3>
                    <p class="text-purple-100">Avaliações Posturais</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8">
            <button onclick="mostrarTab('fisicas')" id="tab-fisicas" class="tab-button border-b-2 border-cobalt-500 text-cobalt-600 py-2 px-1 text-sm font-medium">
                Avaliações Físicas
            </button>
            <button onclick="mostrarTab('vo2max')" id="tab-vo2max" class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-2 px-1 text-sm font-medium">
                Testes VO2Max
            </button>
            <button onclick="mostrarTab('posturais')" id="tab-posturais" class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-2 px-1 text-sm font-medium">
                Avaliações Posturais
            </button>
        </nav>
    </div>

    <!-- Avaliações Físicas -->
    <div id="content-fisicas" class="tab-content">
        <?php if (empty($avaliacoes)): ?>
            <div class="text-center py-16">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">Nenhuma avaliação física encontrada</h3>
                <p class="mt-2 text-gray-500">Aguarde seu professor realizar sua primeira avaliação física.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php foreach ($avaliacoes as $avaliacao): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Avaliação Física</h3>
                            <span class="text-sm text-gray-500"><?= date('d/m/Y', strtotime($avaliacao['data_avaliacao'])) ?></span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="bg-gray-50 rounded-lg p-3 text-center">
                                <p class="text-sm text-gray-600">Peso</p>
                                <p class="text-xl font-bold text-gray-900"><?= number_format($avaliacao['peso'], 1) ?> kg</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3 text-center">
                                <p class="text-sm text-gray-600">Altura</p>
                                <p class="text-xl font-bold text-gray-900"><?= number_format($avaliacao['altura'], 2) ?> m</p>
                            </div>
                            <?php if ($avaliacao['imc']): ?>
                                <div class="bg-gray-50 rounded-lg p-3 text-center">
                                    <p class="text-sm text-gray-600">IMC</p>
                                    <p class="text-xl font-bold text-gray-900"><?= number_format($avaliacao['imc'], 1) ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ($avaliacao['percentual_gordura']): ?>
                                <div class="bg-gray-50 rounded-lg p-3 text-center">
                                    <p class="text-sm text-gray-600">% Gordura</p>
                                    <p class="text-xl font-bold text-gray-900"><?= number_format($avaliacao['percentual_gordura'], 1) ?>%</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($avaliacao['observacoes'])): ?>
                            <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                                <p class="text-sm text-blue-700"><?= htmlspecialchars($avaliacao['observacoes']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Testes VO2Max -->
    <div id="content-vo2max" class="tab-content hidden">
        <?php if (empty($vo2max_tests)): ?>
            <div class="text-center py-16">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">Nenhum teste VO2Max encontrado</h3>
                <p class="mt-2 text-gray-500">Aguarde seu professor realizar seu primeiro teste VO2Max.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php foreach ($vo2max_tests as $teste): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Teste VO2Max</h3>
                            <span class="text-sm text-gray-500"><?= date('d/m/Y', strtotime($teste['data_teste'])) ?></span>
                        </div>
                        
                        <div class="text-center mb-4">
                            <div class="bg-green-50 rounded-lg p-4">
                                <p class="text-sm text-green-600">VO2Max</p>
                                <p class="text-3xl font-bold text-green-700"><?= number_format($teste['vo2max'], 1) ?></p>
                                <p class="text-sm text-green-600">ml/kg/min</p>
                            </div>
                        </div>
                        
                        <?php if (!empty($teste['observacoes'])): ?>
                            <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                                <p class="text-sm text-blue-700"><?= htmlspecialchars($teste['observacoes']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Avaliações Posturais -->
    <div id="content-posturais" class="tab-content hidden">
        <?php if (empty($avaliacoes_posturais)): ?>
            <div class="text-center py-16">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">Nenhuma avaliação postural encontrada</h3>
                <p class="mt-2 text-gray-500">Aguarde seu professor realizar sua primeira avaliação postural.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php foreach ($avaliacoes_posturais as $avaliacao): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Avaliação Postural</h3>
                            <span class="text-sm text-gray-500"><?= date('d/m/Y', strtotime($avaliacao['data_avaliacao'])) ?></span>
                        </div>
                        
                        <?php if (!empty($avaliacao['observacoes'])): ?>
                            <div class="p-3 bg-purple-50 rounded-lg">
                                <p class="text-sm text-purple-700"><?= htmlspecialchars($avaliacao['observacoes']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function mostrarTab(tab) {
    // Esconder todos os conteúdos
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remover classes ativas de todos os botões
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-cobalt-500', 'text-cobalt-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Mostrar conteúdo ativo
    document.getElementById(`content-${tab}`).classList.remove('hidden');
    
    // Ativar botão correspondente
    const activeButton = document.getElementById(`tab-${tab}`);
    activeButton.classList.remove('border-transparent', 'text-gray-500');
    activeButton.classList.add('border-cobalt-500', 'text-cobalt-600');
}
</script>

<?php include 'includes/footer-aluno.php'; ?>
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="text-center p-4">
                    <h4 class="text-white mb-0">SMARTBIOFIT</h4>
                    <small class="text-white-50">Área do Aluno</small>
                </div>
                
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        Dashboard
                    </a>
                    <a class="nav-link" href="treinos.php">
                        <i class="fas fa-dumbbell me-2"></i>
                        Meus Treinos
                    </a>
                    <a class="nav-link active" href="avaliacoes.php">
                        <i class="fas fa-chart-line me-2"></i>
                        Avaliações
                    </a>
                    <a class="nav-link" href="perfil.php">
                        <i class="fas fa-user me-2"></i>
                        Meu Perfil
                    </a>
                    <a class="nav-link" href="notificacoes.php">
                        <i class="fas fa-bell me-2"></i>
                        Notificações
                    </a>
                    <hr class="text-white-50">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>
                        Sair
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-chart-line me-2"></i>Minhas Avaliações</h2>
                        <p class="text-muted mb-0">Acompanhe seu progresso e evolução</p>
                    </div>
                    <div>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>
                            Voltar ao Dashboard
                        </a>
                    </div>
                </div>
                
                <?php if (isset($erro)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($erro) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Resumo de Métricas -->
                <?php if (!empty($avaliacoes)): ?>
                    <?php
                    $ultima_avaliacao = $avaliacoes[0];
                    $primeira_avaliacao = end($avaliacoes);
                    ?>
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="metric-card">
                                <div class="metric-value"><?= number_format($ultima_avaliacao['peso'], 1) ?></div>
                                <div>Peso Atual (kg)</div>
                                <?php if (count($avaliacoes) > 1): ?>
                                    <small class="opacity-75">
                                        <?php
                                        $diferenca = $ultima_avaliacao['peso'] - $primeira_avaliacao['peso'];
                                        $icone = $diferenca > 0 ? 'fa-arrow-up' : ($diferenca < 0 ? 'fa-arrow-down' : 'fa-minus');
                                        $cor = $diferenca > 0 ? 'text-warning' : ($diferenca < 0 ? 'text-info' : 'text-light');
                                        echo "<i class='fas $icone $cor'></i> " . number_format(abs($diferenca), 1) . "kg";
                                        ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="metric-card">
                                <div class="metric-value"><?= number_format($ultima_avaliacao['altura'], 2) ?></div>
                                <div>Altura (m)</div>
                                <small class="opacity-75">IMC: <?= number_format($ultima_avaliacao['peso'] / ($ultima_avaliacao['altura'] * $ultima_avaliacao['altura']), 1) ?></small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="metric-card">
                                <div class="metric-value"><?= count($avaliacoes) ?></div>
                                <div>Avaliações Feitas</div>
                                <small class="opacity-75">
                                    Última: <?= date('d/m/Y', strtotime($ultima_avaliacao['data_avaliacao'])) ?>
                                </small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="metric-card">
                                <div class="metric-value"><?= count($vo2max_tests) ?></div>
                                <div>Testes VO2Max</div>
                                <small class="opacity-75">
                                    <?= !empty($vo2max_tests) ? 'Último: ' . date('d/m/Y', strtotime($vo2max_tests[0]['data_teste'])) : 'Nenhum teste' ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Tabs de Conteúdo -->
                <ul class="nav nav-tabs mb-4" id="avaliacaoTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="fisicas-tab" data-bs-toggle="tab" data-bs-target="#fisicas" type="button">
                            <i class="fas fa-weight me-2"></i>Avaliações Físicas
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="vo2max-tab" data-bs-toggle="tab" data-bs-target="#vo2max" type="button">
                            <i class="fas fa-heartbeat me-2"></i>Testes VO2Max
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="postural-tab" data-bs-toggle="tab" data-bs-target="#postural" type="button">
                            <i class="fas fa-user-check me-2"></i>Avaliação Postural
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="graficos-tab" data-bs-toggle="tab" data-bs-target="#graficos" type="button">
                            <i class="fas fa-chart-bar me-2"></i>Gráficos
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="avaliacaoTabsContent">
                    <!-- Avaliações Físicas -->
                    <div class="tab-pane fade show active" id="fisicas" role="tabpanel">
                        <?php if (empty($avaliacoes)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-weight text-muted" style="font-size: 5rem;"></i>
                                <h3 class="mt-4 text-muted">Nenhuma avaliação física encontrada</h3>
                                <p class="text-muted">Seu professor ainda não realizou avaliações físicas para você.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($avaliacoes as $avaliacao): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card avaliacao-card h-100">
                                            <div class="card-header bg-primary text-white">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-weight me-2"></i>
                                                    Avaliação Física
                                                </h6>
                                                <small><?= date('d/m/Y', strtotime($avaliacao['data_avaliacao'])) ?></small>
                                            </div>
                                            <div class="card-body">
                                                <div class="row text-center mb-3">
                                                    <div class="col-6">
                                                        <strong class="d-block"><?= number_format($avaliacao['peso'], 1) ?>kg</strong>
                                                        <small class="text-muted">Peso</small>
                                                    </div>
                                                    <div class="col-6">
                                                        <strong class="d-block"><?= number_format($avaliacao['altura'], 2) ?>m</strong>
                                                        <small class="text-muted">Altura</small>
                                                    </div>
                                                </div>
                                                
                                                <?php $imc = $avaliacao['peso'] / ($avaliacao['altura'] * $avaliacao['altura']); ?>
                                                <div class="text-center mb-3">
                                                    <h5 class="text-primary"><?= number_format($imc, 1) ?></h5>
                                                    <small class="text-muted">IMC</small>
                                                    <div class="progress-indicator mt-2">
                                                        <div class="progress-bar-custom bg-primary" style="width: <?= min(($imc / 35) * 100, 100) ?>%;"></div>
                                                    </div>
                                                </div>
                                                
                                                <button class="btn btn-outline-primary btn-sm w-100" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modalAvaliacao<?= $avaliacao['id'] ?>">
                                                    <i class="fas fa-eye me-1"></i>
                                                    Ver Detalhes
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Modal Detalhes Avaliação -->
                                    <div class="modal fade" id="modalAvaliacao<?= $avaliacao['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title">
                                                        Avaliação Física - <?= date('d/m/Y', strtotime($avaliacao['data_avaliacao'])) ?>
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6>Medidas Básicas</h6>
                                                            <ul class="list-unstyled">
                                                                <li><strong>Peso:</strong> <?= number_format($avaliacao['peso'], 1) ?>kg</li>
                                                                <li><strong>Altura:</strong> <?= number_format($avaliacao['altura'], 2) ?>m</li>
                                                                <li><strong>IMC:</strong> <?= number_format($imc, 1) ?></li>
                                                                <?php if ($avaliacao['percentual_gordura']): ?>
                                                                    <li><strong>% Gordura:</strong> <?= number_format($avaliacao['percentual_gordura'], 1) ?>%</li>
                                                                <?php endif; ?>
                                                            </ul>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6>Circunferências (cm)</h6>
                                                            <ul class="list-unstyled">
                                                                <?php if ($avaliacao['circunferencia_braco']): ?>
                                                                    <li><strong>Braço:</strong> <?= $avaliacao['circunferencia_braco'] ?>cm</li>
                                                                <?php endif; ?>
                                                                <?php if ($avaliacao['circunferencia_cintura']): ?>
                                                                    <li><strong>Cintura:</strong> <?= $avaliacao['circunferencia_cintura'] ?>cm</li>
                                                                <?php endif; ?>
                                                                <?php if ($avaliacao['circunferencia_quadril']): ?>
                                                                    <li><strong>Quadril:</strong> <?= $avaliacao['circunferencia_quadril'] ?>cm</li>
                                                                <?php endif; ?>
                                                                <?php if ($avaliacao['circunferencia_coxa']): ?>
                                                                    <li><strong>Coxa:</strong> <?= $avaliacao['circunferencia_coxa'] ?>cm</li>
                                                                <?php endif; ?>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                    
                                                    <?php if ($avaliacao['observacoes']): ?>
                                                        <hr>
                                                        <h6>Observações</h6>
                                                        <p><?= nl2br(htmlspecialchars($avaliacao['observacoes'])) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Testes VO2Max -->
                    <div class="tab-pane fade" id="vo2max" role="tabpanel">
                        <?php if (empty($vo2max_tests)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-heartbeat text-muted" style="font-size: 5rem;"></i>
                                <h3 class="mt-4 text-muted">Nenhum teste VO2Max encontrado</h3>
                                <p class="text-muted">Seu professor ainda não realizou testes de VO2Max para você.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($vo2max_tests as $teste): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card avaliacao-card h-100">
                                            <div class="card-header bg-success text-white">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-heartbeat me-2"></i>
                                                    Teste VO2Max
                                                </h6>
                                                <small><?= date('d/m/Y', strtotime($teste['data_teste'])) ?></small>
                                            </div>
                                            <div class="card-body">
                                                <div class="text-center mb-3">
                                                    <h4 class="text-success"><?= number_format($teste['vo2max_resultado'], 1) ?></h4>
                                                    <small class="text-muted">ml/kg/min</small>
                                                </div>
                                                
                                                <div class="row text-center">
                                                    <div class="col-12">
                                                        <strong class="d-block"><?= ucfirst($teste['protocolo']) ?></strong>
                                                        <small class="text-muted">Protocolo</small>
                                                    </div>
                                                </div>
                                                
                                                <div class="progress-indicator mt-3">
                                                    <div class="progress-bar-custom bg-success" style="width: <?= min(($teste['vo2max_resultado'] / 60) * 100, 100) ?>%;"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Avaliação Postural -->
                    <div class="tab-pane fade" id="postural" role="tabpanel">
                        <?php if (empty($avaliacoes_posturais)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-user-check text-muted" style="font-size: 5rem;"></i>
                                <h3 class="mt-4 text-muted">Nenhuma avaliação postural encontrada</h3>
                                <p class="text-muted">Seu professor ainda não realizou avaliações posturais para você.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($avaliacoes_posturais as $postural): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card avaliacao-card h-100">
                                            <div class="card-header bg-info text-white">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-user-check me-2"></i>
                                                    Avaliação Postural
                                                </h6>
                                                <small><?= date('d/m/Y', strtotime($postural['data_avaliacao'])) ?></small>
                                            </div>
                                            <div class="card-body">
                                                <p class="text-muted">
                                                    Avaliação completa dos segmentos corporais e alinhamento postural.
                                                </p>
                                                <button class="btn btn-outline-info btn-sm w-100" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modalPostural<?= $postural['id'] ?>">
                                                    <i class="fas fa-eye me-1"></i>
                                                    Ver Detalhes
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Gráficos -->
                    <div class="tab-pane fade" id="graficos" role="tabpanel">
                        <?php if (count($avaliacoes) < 2): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-chart-bar text-muted" style="font-size: 5rem;"></i>
                                <h3 class="mt-4 text-muted">Gráficos indisponíveis</h3>
                                <p class="text-muted">São necessárias pelo menos 2 avaliações para gerar gráficos comparativos.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Evolução do Peso</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container">
                                                <canvas id="chartPeso"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Evolução do IMC</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container">
                                                <canvas id="chartIMC"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (count($avaliacoes) >= 2): ?>
    <script>
        // Dados para os gráficos
        const avaliacoes = <?= json_encode(array_reverse($avaliacoes)) ?>;
        
        // Preparar dados
        const labels = avaliacoes.map(a => new Date(a.data_avaliacao).toLocaleDateString('pt-BR'));
        const pesos = avaliacoes.map(a => parseFloat(a.peso));
        const imcs = avaliacoes.map(a => {
            const peso = parseFloat(a.peso);
            const altura = parseFloat(a.altura);
            return peso / (altura * altura);
        });
        
        // Configuração comum dos gráficos
        const chartConfig = {
            type: 'line',
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        };
        
        // Gráfico de Peso
        new Chart(document.getElementById('chartPeso'), {
            ...chartConfig,
            data: {
                labels: labels,
                datasets: [{
                    label: 'Peso (kg)',
                    data: pesos,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            }
        });
        
        // Gráfico de IMC
        new Chart(document.getElementById('chartIMC'), {
            ...chartConfig,
            data: {
                labels: labels,
                datasets: [{
                    label: 'IMC',
                    data: imcs,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
