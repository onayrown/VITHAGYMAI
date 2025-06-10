<?php
/**
 * SMARTBIOFIT - Notificações do Aluno
 * Sistema de notificações internas
 */

require_once '../config.php';
require_once 'includes/auth-aluno.php';

// Buscar notificações do aluno
try {
    $stmt = $pdo->prepare("
        SELECT * FROM notificacoes_aluno 
        WHERE aluno_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$_SESSION['aluno_id']]);
    $notificacoes = $stmt->fetchAll();
    
    // Marcar como lidas
    $stmt = $pdo->prepare("UPDATE notificacoes_aluno SET lida = 1, lida_em = NOW() WHERE aluno_id = ? AND lida = 0");
    $stmt->execute([$_SESSION['aluno_id']]);
    
} catch (Exception $e) {
    $erro = "Erro ao carregar notificações: " . $e->getMessage();
    error_log($erro);
}

// Incluir header padrão
include 'includes/header-aluno.php';
?>
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        .notification-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
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
                    <a class="nav-link" href="avaliacoes.php">
                        <i class="fas fa-chart-line me-2"></i>
                        Avaliações
                    </a>
                    <a class="nav-link" href="perfil.php">
                        <i class="fas fa-user me-2"></i>
                        Meu Perfil
                    </a>
                    <a class="nav-link active" href="notificacoes.php">
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
                        <h2><i class="fas fa-bell me-2"></i>Notificações</h2>
                        <p class="text-muted mb-0">Suas mensagens e atualizações importantes</p>
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
                
                <?php if (empty($notificacoes)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bell-slash text-muted" style="font-size: 5rem;"></i>
                        <h3 class="mt-4 text-muted">Nenhuma notificação</h3>
                        <p class="text-muted">Você não possui notificações no momento.</p>
                        <p class="text-muted">As notificações aparecerão aqui quando houver atualizações importantes.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <div class="col-12">
                            <?php foreach ($notificacoes as $notificacao): ?>
                                <div class="card notification-card">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <div class="notification-icon 
                                                    <?php 
                                                    switch($notificacao['tipo']) {
                                                        case 'treino':
                                                            echo 'bg-primary text-white';
                                                            break;
                                                        case 'avaliacao':
                                                            echo 'bg-success text-white';
                                                            break;
                                                        case 'sistema':
                                                            echo 'bg-info text-white';
                                                            break;
                                                        default:
                                                            echo 'bg-secondary text-white';
                                                    }
                                                    ?>">
                                                    <i class="fas 
                                                        <?php 
                                                        switch($notificacao['tipo']) {
                                                            case 'treino':
                                                                echo 'fa-dumbbell';
                                                                break;
                                                            case 'avaliacao':
                                                                echo 'fa-chart-line';
                                                                break;
                                                            case 'sistema':
                                                                echo 'fa-cog';
                                                                break;
                                                            default:
                                                                echo 'fa-bell';
                                                        }
                                                        ?>"></i>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <h6 class="mb-1"><?= htmlspecialchars($notificacao['titulo']) ?></h6>
                                                <p class="mb-2 text-muted"><?= htmlspecialchars($notificacao['mensagem']) ?></p>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?= date('d/m/Y H:i', strtotime($notificacao['created_at'])) ?>                            <?php if ($notificacao['lida']): ?>
                                                        <span class="badge bg-light text-dark ms-2">Lida</span>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<!-- Page Header -->
<div class="bg-white border-b border-gray-200 px-6 py-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Notificações</h1>
            <p class="text-gray-600 mt-1">Acompanhe suas mensagens e atualizações</p>
        </div>
        <div class="bg-cobalt-50 px-4 py-2 rounded-lg">
            <span class="text-cobalt-700 font-medium"><?= count($notificacoes) ?> notificação(ões)</span>
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

    <?php if (empty($notificacoes)): ?>
        <!-- Empty State -->
        <div class="text-center py-16">
            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 17h5l-5 5v-5z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19c-5 0-8-4-8-9s4-9 9-9 9 4 9 9c0 .285-.011.568-.033.848"></path>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">Nenhuma notificação</h3>
            <p class="mt-2 text-gray-500">Você não possui notificações no momento.</p>
        </div>
    <?php else: ?>
        <!-- Notificações List -->
        <div class="space-y-4">
            <?php foreach ($notificacoes as $notificacao): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-start space-x-4">
                        <!-- Icon -->
                        <div class="flex-shrink-0">
                            <?php
                            $iconClass = 'bg-blue-100 text-blue-600';
                            $icon = 'bell';
                            
                            if (stripos($notificacao['titulo'], 'treino') !== false) {
                                $iconClass = 'bg-green-100 text-green-600';
                                $icon = 'dumbbell';
                            } elseif (stripos($notificacao['titulo'], 'avaliação') !== false) {
                                $iconClass = 'bg-purple-100 text-purple-600';
                                $icon = 'chart-line';
                            } elseif (stripos($notificacao['titulo'], 'perfil') !== false) {
                                $iconClass = 'bg-yellow-100 text-yellow-600';
                                $icon = 'user';
                            }
                            ?>
                            <div class="w-10 h-10 rounded-full <?= $iconClass ?> flex items-center justify-center">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <?php if ($icon === 'dumbbell'): ?>
                                        <path d="M2 10a2 2 0 012-2h1a2 2 0 012 2v0a2 2 0 01-2 2H4a2 2 0 01-2-2v0zM14 10a2 2 0 012-2h1a2 2 0 012 2v0a2 2 0 01-2 2h-1a2 2 0 01-2-2v0zM8 6a2 2 0 00-2 2v4a2 2 0 002 2h4a2 2 0 002-2V8a2 2 0 00-2-2H8z"></path>
                                    <?php elseif ($icon === 'chart-line'): ?>
                                        <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"></path>
                                        <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"></path>
                                    <?php elseif ($icon === 'user'): ?>
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                    <?php else: ?>
                                        <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"></path>
                                    <?php endif; ?>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-900 truncate">
                                    <?= htmlspecialchars($notificacao['titulo']) ?>
                                </h3>
                                <div class="flex items-center space-x-2">
                                    <time class="text-xs text-gray-500">
                                        <?= date('d/m/Y H:i', strtotime($notificacao['created_at'])) ?>
                                    </time>
                                    <?php if (!$notificacao['lida']): ?>
                                        <span class="inline-block w-2 h-2 bg-blue-500 rounded-full"></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mt-2">
                                <p class="text-sm text-gray-600">
                                    <?= htmlspecialchars($notificacao['mensagem']) ?>
                                </p>
                            </div>
                            
                            <?php if ($notificacao['lida']): ?>
                                <div class="mt-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Lida em <?= date('d/m/Y H:i', strtotime($notificacao['lida_em'])) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer-aluno.php'; ?>
