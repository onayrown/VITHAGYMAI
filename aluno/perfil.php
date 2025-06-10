<?php
/**
 * SMARTBIOFIT - Perfil do Aluno
 * Visualização e edição limitada do perfil do aluno
 */

require_once '../config.php';
require_once 'includes/auth-aluno.php';

$sucesso = '';
$erro = '';

// Buscar dados completos do aluno
try {
    $stmt = $pdo->prepare("
        SELECT a.*, u.nome as professor_nome, u.email as professor_email 
        FROM alunos a 
        LEFT JOIN usuarios u ON a.professor_id = u.id 
        WHERE a.id = ?
    ");
    $stmt->execute([$_SESSION['aluno_id']]);
    $aluno = $stmt->fetch();
    
    if (!$aluno) {
        throw new Exception("Aluno não encontrado");
    }
    
} catch (Exception $e) {
    $erro = "Erro ao carregar perfil: " . $e->getMessage();
    error_log($erro);
}

// Processar atualização de dados permitidos
if ($_POST && isset($_POST['acao']) && $_POST['acao'] === 'atualizar_dados') {
    try {
        $telefone = trim($_POST['telefone']);
        $endereco = trim($_POST['endereco']);
        $cep = trim($_POST['cep']);
        $cidade = trim($_POST['cidade']);
        $estado = trim($_POST['estado']);
        
        $stmt = $pdo->prepare("
            UPDATE alunos 
            SET telefone = ?, endereco = ?, cep = ?, cidade = ?, estado = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$telefone, $endereco, $cep, $cidade, $estado, $_SESSION['aluno_id']]);
        
        $sucesso = "Dados atualizados com sucesso!";
          $stmt = $pdo->prepare("
            SELECT a.*, u.nome as professor_nome, u.email as professor_email 
            FROM alunos a 
            LEFT JOIN usuarios u ON a.professor_id = u.id 
            WHERE a.id = ?
        ");
        $stmt->execute([$_SESSION['aluno_id']]);
        $aluno = $stmt->fetch();
        
    } catch (Exception $e) {
        $erro = "Erro ao atualizar dados: " . $e->getMessage();
        error_log($erro);
    }
}

// Processar alteração de senha
if ($_POST && isset($_POST['acao']) && $_POST['acao'] === 'alterar_senha') {
    try {
        $senha_atual = $_POST['senha_atual'];
        $nova_senha = $_POST['nova_senha'];
        $confirma_senha = $_POST['confirma_senha'];
        
        if (empty($senha_atual) || empty($nova_senha) || empty($confirma_senha)) {
            throw new Exception("Todos os campos de senha são obrigatórios");
        }
        
        if ($nova_senha !== $confirma_senha) {
            throw new Exception("A nova senha e confirmação não coincidem");
        }
        
        if (strlen($nova_senha) < 6) {
            throw new Exception("A nova senha deve ter pelo menos 6 caracteres");
        }
        
        // Verificar senha atual
        if (!password_verify($senha_atual, $aluno['senha'])) {
            throw new Exception("Senha atual incorreta");
        }
        
        // Atualizar senha
        $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE alunos SET senha = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$nova_senha_hash, $_SESSION['aluno_id']]);
        
        $sucesso = "Senha alterada com sucesso!";
        
    } catch (Exception $e) {
        $erro = "Erro ao alterar senha: " . $e->getMessage();
        error_log($erro);
    }
}

// Incluir header padrão
include 'includes/header-aluno.php';
?>

<!-- Page Header -->
<div class="bg-white border-b border-gray-200 px-6 py-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Meu Perfil</h1>
            <p class="text-gray-600 mt-1">Gerencie suas informações pessoais e configurações</p>
        </div>
    </div>
</div>

<!-- Content -->
<div class="p-6">
    <?php if ($sucesso): ?>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800">Sucesso</h3>
                    <div class="mt-2 text-sm text-green-700">
                        <p><?= htmlspecialchars($sucesso) ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($erro): ?>
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Informações Pessoais -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Informações Pessoais</h2>
                <p class="text-sm text-gray-600">Dados básicos do seu perfil</p>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                    <div class="bg-gray-50 rounded-lg px-3 py-2 text-gray-900"><?= htmlspecialchars($aluno['nome']) ?></div>
                    <p class="text-xs text-gray-500 mt-1">Este campo só pode ser alterado pelo professor</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <div class="bg-gray-50 rounded-lg px-3 py-2 text-gray-900"><?= htmlspecialchars($aluno['email']) ?></div>
                    <p class="text-xs text-gray-500 mt-1">Este campo só pode ser alterado pelo professor</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data de Nascimento</label>
                    <div class="bg-gray-50 rounded-lg px-3 py-2 text-gray-900">
                        <?= $aluno['data_nascimento'] ? date('d/m/Y', strtotime($aluno['data_nascimento'])) : 'Não informado' ?>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sexo</label>
                    <div class="bg-gray-50 rounded-lg px-3 py-2 text-gray-900">
                        <?= $aluno['sexo'] === 'M' ? 'Masculino' : ($aluno['sexo'] === 'F' ? 'Feminino' : 'Não informado') ?>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Professor Responsável</label>
                    <div class="bg-gray-50 rounded-lg px-3 py-2 text-gray-900"><?= htmlspecialchars($aluno['professor_nome']) ?></div>
                </div>
            </div>
        </div>

        <!-- Dados de Contato (Editáveis) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Dados de Contato</h2>
                <p class="text-sm text-gray-600">Você pode atualizar estas informações</p>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="acao" value="atualizar_dados">
                
                <div class="space-y-4">
                    <div>
                        <label for="telefone" class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                        <input type="tel" id="telefone" name="telefone" 
                               value="<?= htmlspecialchars($aluno['telefone'] ?? '') ?>"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cobalt-500 focus:border-cobalt-500">
                    </div>
                    
                    <div>
                        <label for="endereco" class="block text-sm font-medium text-gray-700 mb-1">Endereço</label>
                        <input type="text" id="endereco" name="endereco" 
                               value="<?= htmlspecialchars($aluno['endereco'] ?? '') ?>"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cobalt-500 focus:border-cobalt-500">
                    </div>
                    
                    <div>
                        <label for="cep" class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                        <input type="text" id="cep" name="cep" 
                               value="<?= htmlspecialchars($aluno['cep'] ?? '') ?>"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cobalt-500 focus:border-cobalt-500">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="cidade" class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                            <input type="text" id="cidade" name="cidade" 
                                   value="<?= htmlspecialchars($aluno['cidade'] ?? '') ?>"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cobalt-500 focus:border-cobalt-500">
                        </div>
                        
                        <div>
                            <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                            <input type="text" id="estado" name="estado" 
                                   value="<?= htmlspecialchars($aluno['estado'] ?? '') ?>"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cobalt-500 focus:border-cobalt-500">
                        </div>
                    </div>
                </div>
                
                <div class="mt-6">
                    <button type="submit" class="w-full bg-cobalt-600 hover:bg-cobalt-700 text-white py-2 px-4 rounded-lg font-medium transition-colors">
                        Atualizar Dados de Contato
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Alteração de Senha -->
    <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Alterar Senha</h2>
            <p class="text-sm text-gray-600">Mantenha sua conta segura alterando sua senha regularmente</p>
        </div>
        <form method="POST" class="p-6">
            <input type="hidden" name="acao" value="alterar_senha">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="senha_atual" class="block text-sm font-medium text-gray-700 mb-1">Senha Atual</label>
                    <input type="password" id="senha_atual" name="senha_atual" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cobalt-500 focus:border-cobalt-500">
                </div>
                
                <div>
                    <label for="nova_senha" class="block text-sm font-medium text-gray-700 mb-1">Nova Senha</label>
                    <input type="password" id="nova_senha" name="nova_senha" required minlength="6"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cobalt-500 focus:border-cobalt-500">
                </div>
                
                <div>
                    <label for="confirma_senha" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Nova Senha</label>
                    <input type="password" id="confirma_senha" name="confirma_senha" required minlength="6"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cobalt-500 focus:border-cobalt-500">
                </div>
            </div>
            
            <div class="mt-6">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white py-2 px-6 rounded-lg font-medium transition-colors">
                    Alterar Senha
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Validação de confirmação de senha
document.getElementById('confirma_senha').addEventListener('input', function() {
    const novaSenha = document.getElementById('nova_senha').value;
    const confirmaSenha = this.value;
    
    if (novaSenha !== confirmaSenha) {
        this.setCustomValidity('As senhas não coincidem');
    } else {
        this.setCustomValidity('');
    }
});

// Máscara para CEP
document.getElementById('cep').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = value.replace(/^(\d{5})(\d)/, '$1-$2');
    e.target.value = value;
});
</script>

<?php include 'includes/footer-aluno.php'; ?>
        
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - SMARTBIOFIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
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
        .profile-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            color: #667eea;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 1rem;
        }
        .info-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        .info-card:hover {
            transform: translateY(-5px);
        }
        .readonly-field {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
        }
        .editable-section {
            background: #fff;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
                    <a class="nav-link active" href="perfil.php">
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
                        <h2><i class="fas fa-user me-2"></i>Meu Perfil</h2>
                        <p class="text-muted mb-0">Visualize e atualize suas informações pessoais</p>
                    </div>
                    <div>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>
                            Voltar ao Dashboard
                        </a>
                    </div>
                </div>
                
                <?php if ($sucesso): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($sucesso) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($erro): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($erro) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Header do Perfil -->
                <div class="profile-header">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="avatar-large">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <div class="col">
                            <h3 class="mb-1"><?= htmlspecialchars($aluno['nome']) ?></h3>
                            <p class="mb-1 opacity-75"><?= htmlspecialchars($aluno['email']) ?></p>
                            <small class="opacity-75">
                                Professor: <?= htmlspecialchars($aluno['professor_nome']) ?>
                            </small>
                        </div>
                        <div class="col-auto">
                            <div class="text-end">
                                <p class="mb-1"><small>Cadastrado em</small></p>
                                <small><?= date('d/m/Y', strtotime($aluno['created_at'])) ?></small>
                                <p class="mb-0 mt-2"><small>Último login</small></p>
                                <small><?= $aluno['ultimo_login'] ? date('d/m/Y H:i', strtotime($aluno['ultimo_login'])) : 'N/A' ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Informações Básicas (Somente Leitura) -->
                    <div class="col-lg-6 mb-4">
                        <div class="card info-card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-id-card me-2"></i>
                                    Informações Básicas
                                </h5>
                                <small>Estes dados são controlados pelo seu professor</small>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Nome Completo</strong></label>
                                    <input type="text" class="form-control readonly-field" 
                                           value="<?= htmlspecialchars($aluno['nome']) ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>E-mail</strong></label>
                                    <input type="email" class="form-control readonly-field" 
                                           value="<?= htmlspecialchars($aluno['email']) ?>" readonly>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><strong>Data de Nascimento</strong></label>
                                        <input type="text" class="form-control readonly-field" 
                                               value="<?= $aluno['data_nascimento'] ? date('d/m/Y', strtotime($aluno['data_nascimento'])) : 'Não informado' ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><strong>Sexo</strong></label>
                                        <input type="text" class="form-control readonly-field" 
                                               value="<?= $aluno['sexo'] == 'M' ? 'Masculino' : 'Feminino' ?>" readonly>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Profissão</strong></label>
                                    <input type="text" class="form-control readonly-field" 
                                           value="<?= htmlspecialchars($aluno['profissao'] ?: 'Não informado') ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dados Editáveis -->
                    <div class="col-lg-6 mb-4">
                        <div class="card info-card h-100">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-edit me-2"></i>
                                    Dados de Contato
                                </h5>
                                <small>Você pode atualizar estes dados</small>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="acao" value="atualizar_dados">
                                    
                                    <div class="mb-3">
                                        <label class="form-label"><strong>Telefone</strong></label>
                                        <input type="tel" class="form-control" name="telefone" 
                                               value="<?= htmlspecialchars($aluno['telefone'] ?: '') ?>" 
                                               placeholder="(00) 00000-0000">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label"><strong>Endereço</strong></label>
                                        <textarea class="form-control" name="endereco" rows="2" 
                                                  placeholder="Rua, número, bairro"><?= htmlspecialchars($aluno['endereco'] ?: '') ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label"><strong>CEP</strong></label>
                                            <input type="text" class="form-control" name="cep" 
                                                   value="<?= htmlspecialchars($aluno['cep'] ?: '') ?>" 
                                                   placeholder="00000-000">
                                        </div>
                                        <div class="col-md-5 mb-3">
                                            <label class="form-label"><strong>Cidade</strong></label>
                                            <input type="text" class="form-control" name="cidade" 
                                                   value="<?= htmlspecialchars($aluno['cidade'] ?: '') ?>">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label"><strong>Estado</strong></label>
                                            <input type="text" class="form-control" name="estado" 
                                                   value="<?= htmlspecialchars($aluno['estado'] ?: '') ?>" 
                                                   placeholder="SP" maxlength="2">
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-save me-2"></i>
                                        Salvar Alterações
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Informações de Saúde -->
                    <div class="col-lg-6 mb-4">
                        <div class="card info-card h-100">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="fas fa-heartbeat me-2"></i>
                                    Informações de Saúde
                                </h5>
                                <small>Para alterações, consulte seu professor</small>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Objetivo</strong></label>
                                    <textarea class="form-control readonly-field" rows="2" readonly><?= htmlspecialchars($aluno['objetivo'] ?: 'Não informado') ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Restrições Médicas</strong></label>
                                    <textarea class="form-control readonly-field" rows="2" readonly><?= htmlspecialchars($aluno['restricoes_medicas'] ?: 'Nenhuma') ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Medicamentos</strong></label>
                                    <textarea class="form-control readonly-field" rows="2" readonly><?= htmlspecialchars($aluno['medicamentos'] ?: 'Nenhum') ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Histórico de Lesões</strong></label>
                                    <textarea class="form-control readonly-field" rows="2" readonly><?= htmlspecialchars($aluno['historico_lesoes'] ?: 'Nenhum') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Segurança -->
                    <div class="col-lg-6 mb-4">
                        <div class="card info-card h-100">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-lock me-2"></i>
                                    Segurança
                                </h5>
                                <small>Altere sua senha de acesso</small>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="acao" value="alterar_senha">
                                    
                                    <div class="mb-3">
                                        <label class="form-label"><strong>Senha Atual</strong></label>
                                        <input type="password" class="form-control" name="senha_atual" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label"><strong>Nova Senha</strong></label>
                                        <input type="password" class="form-control" name="nova_senha" 
                                               minlength="6" required>
                                        <small class="form-text text-muted">Mínimo 6 caracteres</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label"><strong>Confirmar Nova Senha</strong></label>
                                        <input type="password" class="form-control" name="confirma_senha" 
                                               minlength="6" required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="fas fa-key me-2"></i>
                                        Alterar Senha
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Informações do Professor -->
                <div class="card info-card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-user-tie me-2"></i>
                            Seu Professor
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="avatar" style="width: 60px; height: 60px; background: #17a2b8; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                            </div>
                            <div class="col">
                                <h6 class="mb-1"><?= htmlspecialchars($aluno['professor_nome']) ?></h6>
                                <p class="mb-0 text-muted"><?= htmlspecialchars($aluno['professor_email']) ?></p>
                                <small class="text-muted">Professor responsável</small>
                            </div>
                            <div class="col-auto">
                                <a href="mailto:<?= htmlspecialchars($aluno['professor_email']) ?>" 
                                   class="btn btn-outline-info">
                                    <i class="fas fa-envelope me-2"></i>
                                    Entrar em Contato
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
