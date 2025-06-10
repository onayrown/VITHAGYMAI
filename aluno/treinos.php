<?php
/**
 * SMARTBIOFIT - Treinos do Aluno
 * Visualização de treinos disponíveis para o aluno
 */

require_once '../config.php';
require_once 'includes/auth-aluno.php';

// Buscar treinos do aluno
try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.nome as professor_nome,
               (SELECT COUNT(*) FROM execucoes_treino et WHERE et.treino_id = t.id AND et.aluno_id = ?) as execucoes
        FROM treinos t 
        LEFT JOIN usuarios u ON t.usuario_id = u.id 
        WHERE t.aluno_id = ? AND t.ativo = 1 
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$_SESSION['aluno_id'], $_SESSION['aluno_id']]);
    $treinos = $stmt->fetchAll();
    
} catch (Exception $e) {
    $erro = "Erro ao carregar treinos: " . $e->getMessage();
    error_log($erro);
}

// Incluir header padrão
include 'includes/header-aluno.php';
?><!-- Page Header -->
<div class="bg-white border-b border-gray-200 px-6 py-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Meus Treinos</h1>
            <p class="text-gray-600 mt-1">Visualize e execute seus treinos personalizados</p>
        </div>
        <div class="bg-cobalt-50 px-4 py-2 rounded-lg">
            <span class="text-cobalt-700 font-medium"><?= count($treinos) ?> treino(s) disponível(is)</span>
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

    <?php if (empty($treinos)): ?>
        <!-- Empty State -->
        <div class="text-center py-16">
            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">Nenhum treino disponível</h3>
            <p class="mt-2 text-gray-500">Aguarde seu professor criar treinos para você.</p>
        </div>
    <?php else: ?>
        <!-- Treinos Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($treinos as $treino): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 truncate"><?= htmlspecialchars($treino['nome']) ?></h3>
                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">Ativo</span>
                        </div>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span><?= htmlspecialchars($treino['professor_nome']) ?></span>
                            </div>
                            
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span><?= date('d/m/Y', strtotime($treino['created_at'])) ?></span>
                            </div>
                            
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                <span><?= $treino['execucoes'] ?> execução(ões)</span>
                            </div>
                        </div>
                        
                        <?php if (!empty($treino['descricao'])): ?>
                            <div class="mb-6">
                                <p class="text-sm text-gray-600 line-clamp-3"><?= htmlspecialchars($treino['descricao']) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex space-x-3">
                            <a href="../treino.php?id=<?= $treino['id'] ?>" class="flex-1 bg-cobalt-600 hover:bg-cobalt-700 text-white text-center py-2 px-4 rounded-lg font-medium transition-colors">
                                Executar Treino
                            </a>
                            <button onclick="visualizarDetalhes(<?= $treino['id'] ?>)" class="bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-4 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Detalhes do Treino -->
<div id="modalDetalhes" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-2xl w-full max-h-[80vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Detalhes do Treino</h3>
            <button onclick="fecharModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="conteudoDetalhes" class="p-6">
            <!-- Conteúdo será carregado via JavaScript -->
        </div>
    </div>
</div>

<script>
function visualizarDetalhes(treinoId) {
    const modal = document.getElementById('modalDetalhes');
    const conteudo = document.getElementById('conteudoDetalhes');
    
    // Mostrar loading
    conteudo.innerHTML = `
        <div class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-cobalt-600"></div>
            <span class="ml-3 text-gray-600">Carregando...</span>
        </div>
    `;
    
    modal.classList.remove('hidden');
    
    // Carregar detalhes
    fetch(`../api/treino-detalhes.php?id=${treinoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                exibirDetalhes(data.treino);
            } else {
                conteudo.innerHTML = `
                    <div class="text-center py-8">
                        <div class="text-red-500 mb-2">
                            <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-600">${data.message || 'Erro ao carregar detalhes'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            conteudo.innerHTML = `
                <div class="text-center py-8">
                    <div class="text-red-500 mb-2">
                        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-600">Erro ao conectar com o servidor</p>
                </div>
            `;
        });
}

function exibirDetalhes(treino) {
    const conteudo = document.getElementById('conteudoDetalhes');
    
    let exerciciosHtml = '';
    if (treino.exercicios && treino.exercicios.length > 0) {
        exerciciosHtml = treino.exercicios.map(exercicio => `
            <div class="bg-gray-50 rounded-lg p-4 mb-3">
                <h4 class="font-medium text-gray-900 mb-2">${exercicio.nome}</h4>
                <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                    <div>
                        <span class="font-medium">Séries:</span> ${exercicio.series || 'N/A'}
                    </div>
                    <div>
                        <span class="font-medium">Repetições:</span> ${exercicio.repeticoes || 'N/A'}
                    </div>
                    <div>
                        <span class="font-medium">Peso:</span> ${exercicio.peso || 'N/A'}
                    </div>
                    <div>
                        <span class="font-medium">Descanso:</span> ${exercicio.descanso || 'N/A'}
                    </div>
                </div>
                ${exercicio.observacoes ? `<p class="mt-2 text-sm text-gray-600">${exercicio.observacoes}</p>` : ''}
            </div>
        `).join('');
    } else {
        exerciciosHtml = '<p class="text-gray-500 text-center py-4">Nenhum exercício cadastrado</p>';
    }
    
    conteudo.innerHTML = `
        <div class="space-y-6">
            <div>
                <h4 class="text-lg font-semibold text-gray-900 mb-2">${treino.nome}</h4>
                ${treino.descricao ? `<p class="text-gray-600">${treino.descricao}</p>` : ''}
            </div>
            
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="bg-gray-50 rounded-lg p-3">
                    <span class="font-medium text-gray-700">Professor:</span>
                    <p class="text-gray-900">${treino.professor_nome}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <span class="font-medium text-gray-700">Criado em:</span>
                    <p class="text-gray-900">${new Date(treino.created_at).toLocaleDateString('pt-BR')}</p>
                </div>
            </div>
            
            <div>
                <h5 class="font-semibold text-gray-900 mb-3">Exercícios</h5>
                ${exerciciosHtml}
            </div>
            
            <div class="flex space-x-3 pt-4 border-t border-gray-200">
                <a href="../treino.php?id=${treino.id}" class="flex-1 bg-cobalt-600 hover:bg-cobalt-700 text-white text-center py-2 px-4 rounded-lg font-medium transition-colors">
                    Executar Treino
                </a>
                <button onclick="fecharModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-4 rounded-lg transition-colors">
                    Fechar
                </button>
            </div>
        </div>
    `;
}

function fecharModal() {
    document.getElementById('modalDetalhes').classList.add('hidden');
}

// Fechar modal ao clicar fora
document.getElementById('modalDetalhes').addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModal();
    }
});
</script>

<?php include 'includes/footer-aluno.php'; ?>
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 0;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 15px 20px;
            border-radius: 0;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
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
                    <a class="nav-link active" href="treinos.php">
                        <i class="fas fa-dumbbell me-2"></i>
                        Meus Treinos
                    </a>
                    <a class="nav-link" href="avaliacoes.php">
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
                        <h2><i class="fas fa-dumbbell me-2"></i>Meus Treinos</h2>
                        <p class="text-muted mb-0">Seus treinos personalizados e disponíveis</p>
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
                
                <?php if (empty($treinos)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-dumbbell text-muted" style="font-size: 5rem;"></i>
                        <h3 class="mt-4 text-muted">Nenhum treino encontrado</h3>
                        <p class="text-muted">Seu professor ainda não criou treinos para você.</p>
                        <p class="text-muted">Entre em contato com seu professor para mais informações.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($treinos as $treino): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card treino-card h-100">
                                    <div class="treino-header">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h5 class="mb-1"><?= htmlspecialchars($treino['nome']) ?></h5>
                                                <small class="opacity-75">
                                                    Por: <?= htmlspecialchars($treino['professor_nome']) ?>
                                                </small>
                                            </div>
                                            <div class="execucoes-badge">
                                                <i class="fas fa-fire"></i>
                                                <?= $treino['execucoes'] ?> vezes
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card-body">
                                        <?php if ($treino['descricao']): ?>
                                            <p class="card-text text-muted">
                                                <?= htmlspecialchars(substr($treino['descricao'], 0, 100)) ?>
                                                <?= strlen($treino['descricao']) > 100 ? '...' : '' ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="row text-center mb-3">
                                            <div class="col-4">
                                                <small class="text-muted d-block">Criado em</small>
                                                <strong><?= date('d/m/Y', strtotime($treino['created_at'])) ?></strong>
                                            </div>
                                            <div class="col-4">
                                                <small class="text-muted d-block">Execuções</small>
                                                <strong><?= $treino['execucoes'] ?></strong>
                                            </div>
                                            <div class="col-4">
                                                <small class="text-muted d-block">Status</small>
                                                <span class="badge bg-success">Ativo</span>
                                            </div>
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <a href="../treino.php?hash=<?= htmlspecialchars($treino['share_hash']) ?>" 
                                               class="btn btn-executar" target="_blank">
                                                <i class="fas fa-play me-2"></i>
                                                Executar Treino
                                            </a>
                                            <button class="btn btn-outline-primary btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalDetalhes<?= $treino['id'] ?>">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Ver Detalhes
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Modal Detalhes -->
                            <div class="modal fade" id="modalDetalhes<?= $treino['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title">
                                                <i class="fas fa-dumbbell me-2"></i>
                                                <?= htmlspecialchars($treino['nome']) ?>
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6><i class="fas fa-user me-2"></i>Professor</h6>
                                                    <p><?= htmlspecialchars($treino['professor_nome']) ?></p>
                                                    
                                                    <h6><i class="fas fa-calendar me-2"></i>Criado em</h6>
                                                    <p><?= date('d/m/Y H:i', strtotime($treino['created_at'])) ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6><i class="fas fa-fire me-2"></i>Execuções</h6>
                                                    <p><?= $treino['execucoes'] ?> vezes executado</p>
                                                    
                                                    <h6><i class="fas fa-link me-2"></i>Compartilhar</h6>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" 
                                                               value="<?= APP_URL ?>/treino.php?hash=<?= htmlspecialchars($treino['share_hash']) ?>"
                                                               readonly id="linkTreino<?= $treino['id'] ?>">
                                                        <button class="btn btn-outline-secondary" type="button"
                                                                onclick="copiarLink('linkTreino<?= $treino['id'] ?>')">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <?php if ($treino['descricao']): ?>
                                                <hr>
                                                <h6><i class="fas fa-file-text me-2"></i>Descrição</h6>
                                                <p><?= nl2br(htmlspecialchars($treino['descricao'])) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                Fechar
                                            </button>
                                            <a href="../treino.php?hash=<?= htmlspecialchars($treino['share_hash']) ?>" 
                                               class="btn btn-primary" target="_blank">
                                                <i class="fas fa-play me-2"></i>
                                                Executar Treino
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Stats Summary -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Resumo dos Treinos</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <h3 class="text-primary"><?= count($treinos) ?></h3>
                                    <p class="text-muted">Treinos Disponíveis</p>
                                </div>
                                <div class="col-md-3">
                                    <h3 class="text-success"><?= array_sum(array_column($treinos, 'execucoes')) ?></h3>
                                    <p class="text-muted">Total de Execuções</p>
                                </div>
                                <div class="col-md-3">
                                    <h3 class="text-warning"><?= count($treinos) > 0 ? round(array_sum(array_column($treinos, 'execucoes')) / count($treinos), 1) : 0 ?></h3>
                                    <p class="text-muted">Média por Treino</p>
                                </div>
                                <div class="col-md-3">
                                    <h3 class="text-info"><?= count($treinos) ?></h3>
                                    <p class="text-muted">Treinos Ativos</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copiarLink(elementId) {
            const elemento = document.getElementById(elementId);
            elemento.select();
            elemento.setSelectionRange(0, 99999);
            document.execCommand('copy');
            
            // Feedback visual
            const button = elemento.nextElementSibling;
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.classList.add('btn-success');
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('btn-success');
            }, 2000);
        }
    </script>
</body>
</html>
