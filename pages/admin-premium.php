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

<div class="ios-container mt-4">
    <!-- Header Premium com Logo -->    <div class="ios-admin-header">
        <img src="<?php echo APP_URL; ?>/assets/images/logo-smartbiofit.png" alt="SMARTBIOFIT" class="h-16 w-auto object-contain">
        <h1>⚙️ Painel Administrativo Premium</h1>
        <p>Gerencie usuários, configurações e monitore o sistema</p>
    </div>
    
    <!-- Estatísticas do Sistema em Cards Premium -->    <div class="ios-section-title">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
        </svg>
        <h3>Estatísticas do Sistema</h3>
    </div>
    
    <div class="ios-row mb-4">
        <div class="ios-col-md-6 ios-col-lg-4 ios-col-xl-2 mb-3">
            <div class="ios-stat-card" style="border-top: 3px solid var(--ios-primary);">                <div class="stat-icon">
                    <svg class="w-6 h-6 text-primary" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="stat-value"><?php echo $stats['total_usuarios'] ?? 0; ?></div>
                <div class="stat-label">Usuários Ativos</div>
            </div>
        </div>
        
        <div class="ios-col-md-6 ios-col-lg-4 ios-col-xl-2 mb-3">
            <div class="ios-stat-card" style="border-top: 3px solid var(--ios-success);">                <div class="stat-icon">
                    <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>                <div class="stat-value"><?php echo $stats['total_professores'] ?? 0; ?></div>
                <div class="stat-label">Instrutores</div>
            </div>
        </div>
        
        <div class="ios-col-md-6 ios-col-lg-4 ios-col-xl-2 mb-3">
            <div class="ios-stat-card" style="border-top: 3px solid var(--ios-info);">                <div class="stat-icon">
                    <svg class="w-6 h-6 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                    </svg>
                </div>
                <div class="stat-value"><?php echo $stats['total_alunos_cadastrados'] ?? 0; ?></div>
                <div class="stat-label">Alunos Cadastrados</div>
            </div>
        </div>
        
        <div class="ios-col-md-6 ios-col-lg-4 ios-col-xl-2 mb-3">
            <div class="ios-stat-card" style="border-top: 3px solid var(--ios-warning);">                <div class="stat-icon">
                    <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                </div>
                <div class="stat-value"><?php echo $stats['logins_hoje'] ?? 0; ?></div>
                <div class="stat-label">Logins Hoje</div>
            </div>
        </div>
        
        <div class="ios-col-md-6 ios-col-lg-4 ios-col-xl-2 mb-3">
            <div class="ios-stat-card" style="border-top: 3px solid var(--ios-secondary);">                <div class="stat-icon">
                    <svg class="w-6 h-6 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="stat-value"><?php echo $stats['atividade_ultima_hora'] ?? 0; ?></div>
                <div class="stat-label">Atividades (1h)</div>
            </div>
        </div>
        
        <div class="ios-col-md-6 ios-col-lg-4 ios-col-xl-2 mb-3">
            <div class="ios-stat-card" style="border-top: 3px solid var(--ios-danger);">                <div class="stat-icon">
                    <svg class="w-6 h-6 text-danger" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="stat-value">100%</div>
                <div class="stat-label">Saúde do Sistema</div>
            </div>
        </div>
    </div>
    
    <!-- Ações Rápidas -->
    <div class="ios-card mb-5">
        <div class="ios-card-header">
            <h4 class="mb-0"><i class="fas fa-bolt mr-2 text-warning"></i> Ações Rápidas</h4>
        </div>
        <div class="ios-card-body">
            <div class="ios-row">
                <div class="ios-col-6 ios-col-md-3 mb-3">
                    <a href="<?php echo APP_URL; ?>/register.php" class="ios-btn ios-btn-primary w-100 d-flex align-center justify-center">
                        <i class="fas fa-user-plus mr-2"></i> Novo Usuário
                    </a>
                </div>
                <div class="ios-col-6 ios-col-md-3 mb-3">
                    <a href="#" onclick="backupDatabase()" class="ios-btn ios-btn-success w-100 d-flex align-center justify-center">
                        <i class="fas fa-database mr-2"></i> Backup
                    </a>
                </div>
                <div class="ios-col-6 ios-col-md-3 mb-3">
                    <a href="#" class="ios-btn ios-btn-warning w-100 d-flex align-center justify-center">
                        <i class="fas fa-cog mr-2"></i> Configurações
                    </a>
                </div>
                <div class="ios-col-6 ios-col-md-3 mb-3">
                    <a href="#" onclick="clearLogs()" class="ios-btn ios-btn-danger w-100 d-flex align-center justify-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i> Logs de Erro
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Usuários Recentes e Logs do Sistema -->
    <div class="ios-row">
        <!-- Usuários Recentes -->
        <div class="ios-col-12 ios-col-lg-6 mb-4">
            <div class="ios-card h-100">
                <div class="ios-card-header">
                    <h4 class="mb-0"><i class="fas fa-user-clock mr-2 text-primary"></i> Usuários Recentes</h4>
                    <a href="#" class="ios-btn ios-btn-sm ios-btn-outline">Ver Todos</a>
                </div>
                <div class="ios-table-wrapper">
                    <table class="ios-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>Criado em</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios_recentes as $usuario): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-center">
                                        <div class="ios-avatar mr-2">
                                            <?php echo strtoupper(substr($usuario['nome'], 0, 1)); ?>
                                        </div>
                                        <?php echo $usuario['nome']; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $badge_class = '';
                                    switch($usuario['tipo']) {
                                        case 'admin': $badge_class = 'ios-badge-danger'; break;
                                        case 'professor': $badge_class = 'ios-badge-success'; break;
                                        case 'aluno': $badge_class = 'ios-badge-info'; break;
                                    }
                                    ?>
                                    <span class="ios-badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($usuario['tipo']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?></td>
                                <td>
                                    <?php if($usuario['ativo']): ?>
                                    <span class="ios-badge ios-badge-success">Ativo</span>
                                    <?php else: ?>
                                    <span class="ios-badge ios-badge-danger">Inativo</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($usuarios_recentes)): ?>
                            <tr>
                                <td colspan="4">
                                    <div class="ios-empty-state p-4">
                                        <div class="ios-empty-state-icon">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="ios-empty-state-title">Nenhum usuário encontrado</div>
                                        <div class="ios-empty-state-text">Não há usuários cadastrados no sistema ainda.</div>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Logs do Sistema -->
        <div class="ios-col-12 ios-col-lg-6 mb-4">
            <div class="ios-card h-100">
                <div class="ios-card-header">
                    <h4 class="mb-0"><i class="fas fa-history mr-2 text-info"></i> Atividades Recentes</h4>
                    <a href="#" class="ios-btn ios-btn-sm ios-btn-outline">Ver Todas</a>
                </div>
                <div class="ios-table-wrapper">
                    <table class="ios-table">
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Ação</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs_sistema as $log): ?>
                            <tr>
                                <td>
                                    <?php echo $log['usuario_nome'] ?? 'Sistema'; ?>
                                </td>
                                <td>
                                    <?php 
                                    $badge_class = 'ios-badge-primary';
                                    if (strpos($log['acao'], 'erro') !== false) {
                                        $badge_class = 'ios-badge-danger';
                                    } elseif (strpos($log['acao'], 'login') !== false) {
                                        $badge_class = 'ios-badge-success';
                                    } elseif (strpos($log['acao'], 'deletar') !== false || strpos($log['acao'], 'remover') !== false) {
                                        $badge_class = 'ios-badge-warning';
                                    }
                                    ?>
                                    <span class="ios-badge <?php echo $badge_class; ?>">
                                        <?php echo $log['acao']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($logs_sistema)): ?>
                            <tr>
                                <td colspan="3">
                                    <div class="ios-empty-state p-4">
                                        <div class="ios-empty-state-icon">
                                            <i class="fas fa-clipboard-list"></i>
                                        </div>
                                        <div class="ios-empty-state-title">Nenhum log encontrado</div>
                                        <div class="ios-empty-state-text">Não há registros de atividades no sistema ainda.</div>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gerenciamento de Sistema -->
    <div class="ios-section-title mt-4">
        <i class="fas fa-server"></i>
        <h3>Gerenciamento do Sistema</h3>
    </div>
    
    <div class="ios-row">
        <!-- Configurações do Sistema -->
        <div class="ios-col-12 ios-col-lg-6 mb-4">
            <div class="ios-card">
                <div class="ios-card-header">
                    <h4 class="mb-0"><i class="fas fa-cogs mr-2 text-warning"></i> Configurações</h4>
                </div>
                <div class="ios-card-body">
                    <div class="ios-form-group d-flex justify-between align-center">
                        <div>
                            <label class="ios-form-label mb-0">Registro público de usuários</label>
                            <small class="text-medium d-block">Permitir que novos usuários se registrem</small>
                        </div>
                        <label class="ios-switch">
                            <input type="checkbox" checked>
                            <span class="ios-switch-slider"></span>
                        </label>
                    </div>
                    
                    <div class="ios-form-group d-flex justify-between align-center">
                        <div>
                            <label class="ios-form-label mb-0">Backups automáticos</label>
                            <small class="text-medium d-block">Realizar backups diários do banco de dados</small>
                        </div>
                        <label class="ios-switch">
                            <input type="checkbox" checked>
                            <span class="ios-switch-slider"></span>
                        </label>
                    </div>
                    
                    <div class="ios-form-group d-flex justify-between align-center">
                        <div>
                            <label class="ios-form-label mb-0">Modo manutenção</label>
                            <small class="text-medium d-block">Ativar modo de manutenção (site indisponível)</small>
                        </div>
                        <label class="ios-switch">
                            <input type="checkbox">
                            <span class="ios-switch-slider"></span>
                        </label>
                    </div>
                    
                    <div class="ios-form-group d-flex justify-between align-center">
                        <div>
                            <label class="ios-form-label mb-0">Logs detalhados</label>
                            <small class="text-medium d-block">Registrar logs detalhados do sistema</small>
                        </div>
                        <label class="ios-switch">
                            <input type="checkbox" checked>
                            <span class="ios-switch-slider"></span>
                        </label>
                    </div>
                    
                    <div class="ios-form-group mb-0">
                        <button class="ios-btn ios-btn-primary">
                            <i class="fas fa-save mr-2"></i> Salvar Configurações
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Status do Sistema -->
        <div class="ios-col-12 ios-col-lg-6 mb-4">
            <div class="ios-card">
                <div class="ios-card-header">
                    <h4 class="mb-0"><i class="fas fa-heartbeat mr-2 text-danger"></i> Status do Sistema</h4>
                </div>
                <div class="ios-card-body">
                    <div class="ios-row">
                        <div class="ios-col-6 mb-3">
                            <div class="d-flex align-center">
                                <i class="fas fa-server mr-2 text-primary"></i>
                                <div class="flex-1">
                                    <small class="text-medium">Servidor</small>
                                    <div class="d-flex align-center justify-between">
                                        <strong>Web Server</strong>
                                        <span class="ios-badge ios-badge-success">Online</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="ios-col-6 mb-3">
                            <div class="d-flex align-center">
                                <i class="fas fa-database mr-2 text-primary"></i>
                                <div class="flex-1">
                                    <small class="text-medium">Banco de Dados</small>
                                    <div class="d-flex align-center justify-between">
                                        <strong>MySQL</strong>
                                        <span class="ios-badge ios-badge-success">Online</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="ios-col-6 mb-3">
                            <div class="d-flex align-center">
                                <i class="fas fa-memory mr-2 text-primary"></i>
                                <div class="flex-1">
                                    <small class="text-medium">Memória</small>
                                    <div class="d-flex align-center justify-between">
                                        <strong>Uso: 35%</strong>
                                        <span class="ios-badge ios-badge-success">Normal</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="ios-col-6 mb-3">
                            <div class="d-flex align-center">
                                <i class="fas fa-microchip mr-2 text-primary"></i>
                                <div class="flex-1">
                                    <small class="text-medium">CPU</small>
                                    <div class="d-flex align-center justify-between">
                                        <strong>Uso: 22%</strong>
                                        <span class="ios-badge ios-badge-success">Normal</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="ios-col-6 mb-3">
                            <div class="d-flex align-center">
                                <i class="fas fa-hdd mr-2 text-primary"></i>
                                <div class="flex-1">
                                    <small class="text-medium">Disco</small>
                                    <div class="d-flex align-center justify-between">
                                        <strong>Uso: 62%</strong>
                                        <span class="ios-badge ios-badge-warning">Atenção</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="ios-col-6 mb-3">
                            <div class="d-flex align-center">
                                <i class="fas fa-tachometer-alt mr-2 text-primary"></i>
                                <div class="flex-1">
                                    <small class="text-medium">Performance</small>
                                    <div class="d-flex align-center justify-between">
                                        <strong>Resposta: 120ms</strong>
                                        <span class="ios-badge ios-badge-success">Ótima</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button class="ios-btn ios-btn-primary">
                            <i class="fas fa-sync-alt mr-2"></i> Verificar Novamente
                        </button>
                        <button class="ios-btn ios-btn-outline ml-2">
                            <i class="fas fa-file-download mr-2"></i> Relatório Completo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function backupDatabase() {
    if (confirm('Tem certeza que deseja fazer backup do banco de dados?')) {
        // Implementar backup nos próximos milestones
        alert('Funcionalidade de backup será implementada no próximo milestone.');
    }
}

function clearLogs() {
    if (confirm('Tem certeza que deseja limpar os logs antigos?\n\nEsta ação não pode ser desfeita.')) {
        // Implementar limpeza de logs nos próximos milestones
        alert('Funcionalidade de limpeza de logs será implementada no próximo milestone.');
    }
}

// Auto-refresh a cada 30 segundos
setTimeout(function() {
    location.reload();
}, 30000);
</script>

<!-- Barra de navegação removida - utilizando apenas a do header.php -->

<?php include '../includes/footer.php'; ?>
