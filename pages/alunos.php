<?php
/**
 * SMARTBIOFIT - Gerenciamento de Alunos
 * Milestone 2: CRUD completo de alunos
 */

require_once '../config.php';
require_once '../database.php';

// Verificar autenticação (session_start já foi chamado em config.php)
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'professor')) {
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

$db = Database::getInstance();
$message = '';
$messageType = '';

// Função para registrar atividades no sistema
function logActivity($user_id, $action, $description) {
    global $db;
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $db->execute(
            "INSERT INTO logs (usuario_id, acao, descricao, ip_address) VALUES (?, ?, ?, ?)", 
            [$user_id, $action, $description, $ip_address]
        );
    } catch (Exception $e) {
        error_log("Erro ao registrar log: " . $e->getMessage());
    }
}

// Processar ações CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            // Gerar uma senha temporária segura
            $temporary_password = bin2hex(random_bytes(8)); // 16 caracteres hex
            $hashed_password = password_hash($temporary_password, PASSWORD_DEFAULT);
            
            $db->beginTransaction();
            try {
                // 1. Criar a conta de usuário
                $user_sql = "INSERT INTO usuarios (nome, email, senha, tipo, telefone, ativo) VALUES (?, ?, ?, 'aluno', ?, TRUE)";
                $user_params = [
                    $_POST['nome'],
                    $_POST['email'],
                    $hashed_password,
                    $_POST['telefone'] ?: null
                ];
                $db->execute($user_sql, $user_params);
                $new_user_id = $db->lastInsertId();

                // 2. Criar o registro do aluno, vinculando ao usuário
                $aluno_sql = "INSERT INTO alunos (usuario_id, nome, email, telefone, data_nascimento, sexo, endereco, cep, cidade, estado, profissao, objetivo, professor_id, ativo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)";
                
                $aluno_params = [
                    $new_user_id,
                    $_POST['nome'],
                    $_POST['email'],
                    $_POST['telefone'] ?: null,
                    $_POST['data_nascimento'] ?: null,
                    $_POST['sexo'],
                    $_POST['endereco'] ?: null,
                    $_POST['cep'] ?: null,
                    $_POST['cidade'] ?: null,
                    $_POST['estado'] ?: null,
                    $_POST['profissao'] ?: null,
                    $_POST['objetivo'] ?: null,
                    $_SESSION['user_type'] === 'professor' ? $_SESSION['user_id'] : $_POST['professor_id'] // Se admin, pega do form
                ];
                
                $db->execute($aluno_sql, $aluno_params);
                $new_aluno_id = $db->lastInsertId();
                
                // 3. Confirmar a transação
                $db->commit();
                
                // Log da ação
                logActivity($_SESSION['user_id'], 'criar_aluno_usuario', "Novo aluno '{$_POST['nome']}' (Aluno ID: {$new_aluno_id}, Usuário ID: {$new_user_id}) cadastrado");
                $message = 'Aluno cadastrado com sucesso! <strong>Senha temporária:</strong> ' . htmlspecialchars($temporary_password);
                $messageType = 'success';
                
            } catch (Exception $e) {
                // Se algo der errado, reverte a transação
                $db->rollBack();
                $message = 'Erro ao cadastrar aluno: ' . $e->getMessage();
                $messageType = 'error';
                error_log("Erro no cadastro de aluno: " . $e->getMessage());
            }
            break;
            
        case 'update':
            try {
                // Administradores podem editar qualquer aluno, instrutores só os seus
                if ($_SESSION['user_type'] === 'admin') {
                    $sql = "UPDATE alunos SET nome=?, email=?, telefone=?, data_nascimento=?, sexo=?, endereco=?, cep=?, cidade=?, estado=?, profissao=?, objetivo=?, restricoes_medicas=?, medicamentos=?, historico_lesoes=?, atividade_fisica_atual=?, observacoes=? WHERE id=?";
                    $params = [
                        $_POST['nome'],
                        $_POST['email'] ?: null,
                        $_POST['telefone'] ?: null,
                        $_POST['data_nascimento'] ?: null,
                        $_POST['sexo'],
                        $_POST['endereco'] ?: null,
                        $_POST['cep'] ?: null,
                        $_POST['cidade'] ?: null,
                        $_POST['estado'] ?: null,
                        $_POST['profissao'] ?: null,
                        $_POST['objetivo'] ?: null,
                        $_POST['restricoes_medicas'] ?: null,
                        $_POST['medicamentos'] ?: null,
                        $_POST['historico_lesoes'] ?: null,
                        $_POST['atividade_fisica_atual'] ?: null,
                        $_POST['observacoes'] ?: null,
                        $_POST['id']
                    ];
                } else {
                    $sql = "UPDATE alunos SET nome=?, email=?, telefone=?, data_nascimento=?, sexo=?, endereco=?, cep=?, cidade=?, estado=?, profissao=?, objetivo=?, restricoes_medicas=?, medicamentos=?, historico_lesoes=?, atividade_fisica_atual=?, observacoes=? WHERE id=? AND professor_id=?";
                    $params = [
                        $_POST['nome'],
                        $_POST['email'] ?: null,
                        $_POST['telefone'] ?: null,
                        $_POST['data_nascimento'] ?: null,
                        $_POST['sexo'],
                        $_POST['endereco'] ?: null,
                        $_POST['cep'] ?: null,
                        $_POST['cidade'] ?: null,
                        $_POST['estado'] ?: null,
                        $_POST['profissao'] ?: null,
                        $_POST['objetivo'] ?: null,
                        $_POST['restricoes_medicas'] ?: null,
                        $_POST['medicamentos'] ?: null,
                        $_POST['historico_lesoes'] ?: null,
                        $_POST['atividade_fisica_atual'] ?: null,
                        $_POST['observacoes'] ?: null,
                        $_POST['id'],
                        $_SESSION['user_id']
                    ];
                }
                
                $affected = $db->execute($sql, $params);
                
                if ($affected > 0) {
                    logActivity($_SESSION['user_id'], 'editar_aluno', "Aluno ID:{$_POST['id']} atualizado");
                    $message = 'Aluno atualizado com sucesso!';
                    $messageType = 'success';
                } else {
                    $message = 'Nenhuma alteração foi feita ou aluno não encontrado.';
                    $messageType = 'warning';
                }
                
            } catch (Exception $e) {
                $message = 'Erro ao atualizar aluno: ' . $e->getMessage();
                $messageType = 'error';
            }
            break;
            
        case 'delete':
            try {
                $id = $_POST['id'];
                
                // Administradores podem desativar qualquer aluno, professores só os seus
                if ($_SESSION['user_type'] === 'admin') {
                    $aluno = $db->fetch("SELECT nome FROM alunos WHERE id=?", [$id]);
                    if ($aluno) {
                        $db->execute("UPDATE alunos SET ativo=FALSE WHERE id=?", [$id]);
                        logActivity($_SESSION['user_id'], 'desativar_aluno', "Aluno '{$aluno['nome']}' desativado");
                        $message = 'Aluno desativado com sucesso!';
                        $messageType = 'success';
                    } else {
                        $message = 'Aluno não encontrado.';
                        $messageType = 'error';
                    }
                } else {
                    $aluno = $db->fetch("SELECT nome FROM alunos WHERE id=? AND professor_id=?", [$id, $_SESSION['user_id']]);
                    if ($aluno) {
                        $db->execute("UPDATE alunos SET ativo=FALSE WHERE id=? AND professor_id=?", [$id, $_SESSION['user_id']]);
                        logActivity($_SESSION['user_id'], 'desativar_aluno', "Aluno '{$aluno['nome']}' desativado");
                        $message = 'Aluno desativado com sucesso!';
                        $messageType = 'success';
                    } else {
                        $message = 'Aluno não encontrado.';
                        $messageType = 'error';
                    }
                }
                  } catch (Exception $e) {
                $message = 'Erro ao desativar aluno: ' . $e->getMessage();
                $messageType = 'error';
            }
            break;
            
        case 'activate':
            try {
                $id = $_POST['id'];
                
                // Administradores podem reativar qualquer aluno, professores só os seus
                if ($_SESSION['user_type'] === 'admin') {
                    $aluno = $db->fetch("SELECT nome FROM alunos WHERE id=?", [$id]);
                    if ($aluno) {
                        $db->execute("UPDATE alunos SET ativo=TRUE WHERE id=?", [$id]);
                        logActivity($_SESSION['user_id'], 'reativar_aluno', "Aluno '{$aluno['nome']}' reativado");
                        $message = 'Aluno reativado com sucesso!';
                        $messageType = 'success';
                    } else {
                        $message = 'Aluno não encontrado.';
                        $messageType = 'error';
                    }
                } else {
                    $aluno = $db->fetch("SELECT nome FROM alunos WHERE id=? AND professor_id=?", [$id, $_SESSION['user_id']]);
                    if ($aluno) {
                        $db->execute("UPDATE alunos SET ativo=TRUE WHERE id=? AND professor_id=?", [$id, $_SESSION['user_id']]);
                        logActivity($_SESSION['user_id'], 'reativar_aluno', "Aluno '{$aluno['nome']}' reativado");
                        $message = 'Aluno reativado com sucesso!';
                        $messageType = 'success';
                    } else {
                        $message = 'Aluno não encontrado.';
                        $messageType = 'error';
                    }
                }
                
            } catch (Exception $e) {
                $message = 'Erro ao reativar aluno: ' . $e->getMessage();
                $messageType = 'error';
            }
            break;
    }
}

// Buscar e filtrar alunos
$search = $_GET['search'] ?? '';
$filtro_sexo = $_GET['sexo'] ?? '';
$filtro_ativo = $_GET['ativo'] ?? '1';

// Administradores podem ver todos os alunos, professores só os seus
$where_conditions = [];
$params = [];

if ($_SESSION['user_type'] !== 'admin') {
    $where_conditions[] = "professor_id = ?";
    $params[] = $_SESSION['user_id'];
}

if ($search) {
    $where_conditions[] = "(nome LIKE ? OR email LIKE ? OR telefone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($filtro_sexo) {
    $where_conditions[] = "sexo = ?";
    $params[] = $filtro_sexo;
}

if ($filtro_ativo !== '') {
    $where_conditions[] = "ativo = ?";
    $params[] = (bool)$filtro_ativo;
}

$where_clause = empty($where_conditions) ? '1=1' : implode(' AND ', $where_conditions);
$alunos = $db->fetchAll("SELECT * FROM alunos WHERE $where_clause ORDER BY nome ASC", $params);

// Calcular estatísticas
$total_alunos = 0;
$ativos = 0;
$masculino = 0;
$feminino = 0;

foreach ($alunos as $aluno) {
    $total_alunos++;
    if ($aluno['ativo']) $ativos++;
    if ($aluno['sexo'] === 'M') $masculino++;
    if ($aluno['sexo'] === 'F') $feminino++;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Alunos - SMARTBIOFIT</title>
    
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- CSS Premium do iOS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/ios-premium.css">
</head>
<body class="bg-gray-50 font-sans">
    <?php include '../includes/header.php'; ?>
    
    <!-- Clean Header with Breadcrumb -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Breadcrumb -->
            <nav class="flex items-center space-x-2 text-gray-500 text-sm mb-4" data-aos="fade-right">
                <a href="<?php echo APP_URL; ?>" class="hover:text-cobalt-600 transition-colors duration-200 flex items-center">
                    <i class="fas fa-home mr-1"></i> Dashboard
                </a>
                <i class="fas fa-chevron-right text-gray-400"></i>
                <span class="text-gray-900 font-medium">Gerenciar Alunos</span>
            </nav>
            
            <!-- Header Content -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-center space-x-4" data-aos="fade-up">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-cobalt-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-users text-xl text-cobalt-600"></i>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-1">Gerenciar Alunos</h1>
                        <p class="text-gray-600">Cadastre e gerencie seus alunos de forma eficiente</p>
                    </div>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-3" data-aos="fade-left">
                    <button onclick="toggleForm()" 
                            class="inline-flex items-center px-6 py-3 bg-cobalt-600 text-white rounded-xl font-semibold hover:bg-cobalt-700 transform hover:scale-105 transition-all duration-200 shadow-lg">
                        <i class="fas fa-plus mr-2"></i>
                        Novo Aluno
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Success/Error Messages -->
        <?php if ($message): ?>
        <div class="bg-<?php echo $messageType === 'success' ? 'green' : ($messageType === 'error' ? 'red' : 'blue'); ?>-50 border border-<?php echo $messageType === 'success' ? 'green' : ($messageType === 'error' ? 'red' : 'blue'); ?>-200 rounded-xl p-4 mb-6" data-aos="fade-down">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle text-green-500' : ($messageType === 'error' ? 'exclamation-triangle text-red-500' : 'info-circle text-blue-500'); ?> text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-<?php echo $messageType === 'success' ? 'green' : ($messageType === 'error' ? 'red' : 'blue'); ?>-800">
                        <?php echo htmlspecialchars($message); ?>
                    </p>
                </div>
                <div class="ml-auto pl-3">
                    <button class="inline-flex text-<?php echo $messageType === 'success' ? 'green' : ($messageType === 'error' ? 'red' : 'blue'); ?>-400 hover:text-<?php echo $messageType === 'success' ? 'green' : ($messageType === 'error' ? 'red' : 'blue'); ?>-600 transition-colors" onclick="this.parentElement.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics Dashboard -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8" data-aos="fade-up">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Total de Alunos</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $total_alunos; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-cobalt-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-cobalt-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Ativos</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo $ativos; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-user-check text-green-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Masculino</p>
                        <p class="text-2xl font-bold text-blue-600"><?php echo $masculino; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-mars text-blue-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Feminino</p>
                        <p class="text-2xl font-bold text-pink-600"><?php echo $feminino; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-venus text-pink-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8" data-aos="fade-up">
            <form method="GET" class="flex flex-col lg:flex-row gap-4">
                <div class="flex-1">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                    <div class="relative">                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>"
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent text-gray-900"
                               placeholder="Nome, email ou telefone...">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>
                </div>
                
                <div class="lg:w-48">
                    <label for="sexo" class="block text-sm font-medium text-gray-700 mb-2">Sexo</label>
                    <select id="sexo" name="sexo" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent text-gray-900">
                        <option value="">Todos</option>
                        <option value="M" <?php echo $filtro_sexo === 'M' ? 'selected' : ''; ?>>Masculino</option>
                        <option value="F" <?php echo $filtro_sexo === 'F' ? 'selected' : ''; ?>>Feminino</option>
                    </select>
                </div>
                
                <div class="lg:w-48">
                    <label for="ativo" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="ativo" name="ativo" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent text-gray-900">
                        <option value="1" <?php echo $filtro_ativo === '1' ? 'selected' : ''; ?>>Ativos</option>
                        <option value="0" <?php echo $filtro_ativo === '0' ? 'selected' : ''; ?>>Inativos</option>
                        <option value="" <?php echo $filtro_ativo === '' ? 'selected' : ''; ?>>Todos</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="px-6 py-3 bg-cobalt-600 text-white rounded-xl font-semibold hover:bg-cobalt-700 transition-colors">
                        <i class="fas fa-filter mr-2"></i>
                        Filtrar
                    </button>
                </div>
            </form>
        </div>        <!-- Student Registration/Edit Form -->
        <div id="student-form" class="hidden mb-8" data-aos="fade-up">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-cobalt-600 px-6 py-4">
                    <div class="flex items-center justify-between">                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-user-plus text-white text-sm"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-white">
                                Cadastrar Novo Aluno
                            </h3>
                        </div>
                        <button onclick="toggleForm()" class="text-white hover:text-gray-200 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                
                <div class="p-6">                    <form method="POST" id="alunoForm" class="space-y-8">
                        <input type="hidden" name="action" value="create">
                        
                        <!-- Basic Information Section -->
                        <div class="space-y-6">
                            <div class="flex items-center space-x-3 pb-3 border-b border-gray-200">
                                <div class="w-8 h-8 bg-cobalt-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-user text-cobalt-600 text-sm"></i>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-900">Informações Básicas</h4>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label for="nome" class="block text-sm font-medium text-gray-700">
                                        Nome Completo <span class="text-red-500">*</span>
                                    </label>                                <input type="text" id="nome" name="nome" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900"
                                           placeholder="Digite o nome completo">
                                </div>
                                
                                <div class="space-y-2">
                                    <label for="email" class="block text-sm font-medium text-gray-700">E-mail</label>                                <input type="email" id="email" name="email"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900"
                                           placeholder="Digite o e-mail">
                                </div>
                                
                                <div class="space-y-2">
                                    <label for="telefone" class="block text-sm font-medium text-gray-700">Telefone</label>                                <input type="tel" id="telefone" name="telefone"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900"
                                           placeholder="(00) 00000-0000">
                                </div>
                                
                                <div class="space-y-2">
                                    <label for="data_nascimento" class="block text-sm font-medium text-gray-700">Data de Nascimento</label>                                <input type="date" id="data_nascimento" name="data_nascimento"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900">
                                </div>
                                
                                <div class="space-y-2">
                                    <label for="sexo" class="block text-sm font-medium text-gray-700">
                                        Sexo <span class="text-red-500">*</span>
                                    </label>                                <select id="sexo" name="sexo" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900">
                                    <option value="">Selecione</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Feminino</option>
                                </select>
                                </div>
                                  <div class="space-y-2">
                                    <label for="profissao" class="block text-sm font-medium text-gray-700">Profissão</label>
                                    <input type="text" id="profissao" name="profissao"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900"
                                           placeholder="Digite a profissão">
                                </div>
                            </div>
                        </div>

                        <!-- Address Section -->
                        <div class="space-y-6">
                            <div class="flex items-center space-x-3 pb-3 border-b border-gray-200">
                                <div class="w-8 h-8 bg-cobalt-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-map-marker-alt text-cobalt-600 text-sm"></i>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-900">Endereço</h4>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <label for="endereco" class="block text-sm font-medium text-gray-700">Endereço</label>                                    <textarea id="endereco" name="endereco" rows="2"
                                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 resize-none text-gray-900"
                                              placeholder="Digite o endereço completo"></textarea>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="space-y-2">
                                        <label for="cep" class="block text-sm font-medium text-gray-700">CEP</label>                                        <input type="text" id="cep" name="cep"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900"
                                               placeholder="00000-000">
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <label for="cidade" class="block text-sm font-medium text-gray-700">Cidade</label>                                        <input type="text" id="cidade" name="cidade"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900"
                                               placeholder="Digite a cidade">
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <label for="estado" class="block text-sm font-medium text-gray-700">Estado</label>                                        <input type="text" id="estado" name="estado"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900"
                                               placeholder="SP">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Health Information Section -->
                        <div class="space-y-6">
                            <div class="flex items-center space-x-3 pb-3 border-b border-gray-200">
                                <div class="w-8 h-8 bg-cobalt-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-heartbeat text-cobalt-600 text-sm"></i>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-900">Informações de Saúde</h4>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <label for="objetivo" class="block text-sm font-medium text-gray-700">Objetivo</label>                                    <textarea id="objetivo" name="objetivo" rows="2"
                                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 resize-none text-gray-900"
                                              placeholder="Descreva os objetivos do aluno"></textarea>
                                </div>
                                
                                <div class="space-y-2">
                                    <label for="restricoes_medicas" class="block text-sm font-medium text-gray-700">Restrições Médicas</label>                                    <textarea id="restricoes_medicas" name="restricoes_medicas" rows="2"
                                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 resize-none text-gray-900"
                                              placeholder="Descreva possíveis restrições médicas"></textarea>
                                </div>
                                
                                <div class="space-y-2">
                                    <label for="medicamentos" class="block text-sm font-medium text-gray-700">Medicamentos</label>                                    <textarea id="medicamentos" name="medicamentos" rows="2"
                                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 resize-none text-gray-900"
                                              placeholder="Liste os medicamentos em uso"></textarea>
                                </div>
                                
                                <div class="space-y-2">
                                    <label for="historico_lesoes" class="block text-sm font-medium text-gray-700">Histórico de Lesões</label>                                    <textarea id="historico_lesoes" name="historico_lesoes" rows="2"
                                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 resize-none text-gray-900"
                                              placeholder="Descreva lesões anteriores"></textarea>
                                </div>
                                
                                <div class="space-y-2">
                                    <label for="atividade_fisica_atual" class="block text-sm font-medium text-gray-700">Atividade Física Atual</label>                                    <textarea id="atividade_fisica_atual" name="atividade_fisica_atual" rows="2"
                                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 resize-none text-gray-900"
                                              placeholder="Descreva atividades físicas atuais"></textarea>
                                </div>
                                
                                <div class="space-y-2">
                                    <label for="observacoes" class="block text-sm font-medium text-gray-700">Observações</label>                                    <textarea id="observacoes" name="observacoes" rows="3"
                                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 resize-none text-gray-900"
                                              placeholder="Observações adicionais"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200">                            <button type="submit" 
                                    class="flex-1 sm:flex-none px-8 py-3 bg-cobalt-600 text-white rounded-xl font-semibold hover:bg-cobalt-700 transform hover:scale-105 transition-all duration-200 shadow-lg">
                                <i class="fas fa-save mr-2"></i>
                                Cadastrar Aluno
                            </button>
                            <button type="button" onclick="toggleForm()" 
                                    class="flex-1 sm:flex-none px-8 py-3 bg-gray-200 text-gray-800 rounded-xl font-semibold hover:bg-gray-300 transition-all duration-200">
                                <i class="fas fa-times mr-2"></i>
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Students Grid -->
        <?php if (empty($alunos)): ?>
        <div class="text-center py-12" data-aos="fade-up">
            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-users text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Nenhum aluno encontrado</h3>
            <p class="text-gray-600 mb-6">Comece cadastrando seu primeiro aluno clicando no botão acima.</p>
            <button onclick="toggleForm()" 
                    class="inline-flex items-center px-6 py-3 bg-cobalt-600 text-white rounded-xl font-semibold hover:bg-cobalt-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Cadastrar Primeiro Aluno
            </button>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" data-aos="fade-up">
            <?php foreach ($alunos as $aluno): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-200">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-cobalt-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-cobalt-600"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($aluno['nome']); ?></h3>
                                <p class="text-sm text-gray-500"><?php echo $aluno['sexo'] === 'M' ? 'Masculino' : 'Feminino'; ?></p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $aluno['ativo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $aluno['ativo'] ? 'Ativo' : 'Inativo'; ?>
                        </span>
                    </div>
                    
                    <div class="space-y-2 mb-4">
                        <?php if ($aluno['email']): ?>
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-envelope w-4 mr-2"></i>
                            <?php echo htmlspecialchars($aluno['email']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($aluno['telefone']): ?>
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-phone w-4 mr-2"></i>
                            <?php echo htmlspecialchars($aluno['telefone']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($aluno['data_nascimento']): ?>
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-calendar w-4 mr-2"></i>
                            <?php echo date('d/m/Y', strtotime($aluno['data_nascimento'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                      <div class="flex space-x-2">
                        <button onclick="viewStudent(<?php echo $aluno['id']; ?>)" 
                                class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                            <i class="fas fa-eye mr-1"></i>
                            Ver
                        </button>                        <a href="javascript:void(0)" onclick="editStudent(<?php echo $aluno['id']; ?>)" 
                           class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-cobalt-600 text-white rounded-lg text-sm font-medium hover:bg-cobalt-700 transition-colors">
                            <i class="fas fa-edit mr-1"></i>
                            Editar
                        </a>                        <?php if ($aluno['ativo']): ?>
                        <button onclick="deleteStudent(<?php echo $aluno['id']; ?>, '<?php echo htmlspecialchars($aluno['nome']); ?>')" 
                                class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors">
                            <i class="fas fa-user-slash mr-1"></i>
                            Desativar
                        </button>
                        <?php else: ?>
                        <button onclick="activateStudent(<?php echo $aluno['id']; ?>, '<?php echo htmlspecialchars($aluno['nome']); ?>')" 
                                class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                            <i class="fas fa-user-check mr-1"></i>
                            Ativar
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Confirmar Desativação</h3>
                        <p class="text-sm text-gray-600">O aluno será desativado, mas pode ser reativado posteriormente</p>
                    </div>
                </div>
                
                <p class="text-gray-700 mb-6">
                    Tem certeza que deseja desativar o aluno <strong id="studentName"></strong>?
                </p>
                  <div class="flex space-x-3">
                    <button onclick="confirmDeleteAluno()" 
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 transition-colors">
                        Sim, Desativar
                    </button><button onclick="closeDeleteModal()" 
                            class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>    </div>

    <!-- Activate Confirmation Modal -->
    <div id="activateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-check text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Confirmar Ativação</h3>
                        <p class="text-sm text-gray-600">O aluno será reativado e poderá voltar a usar o sistema</p>
                    </div>
                </div>
                
                <p class="text-gray-700 mb-6">
                    Tem certeza que deseja reativar o aluno <strong id="activateStudentName"></strong>?
                </p>
                
                <div class="flex space-x-3">
                    <button onclick="confirmActivateAluno()" 
                            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition-colors">
                        <i class="fas fa-check mr-1"></i> Sim, Ativar
                    </button>
                    <button onclick="closeActivateModal()" 
                            class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Details Modal -->
    <div id="detailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Detalhes do Aluno</h3>
                            <p class="text-sm text-gray-600">Informações completas</p>
                        </div>
                    </div>
                    <button onclick="closeDetailsModal()" 
                            class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                
                <div id="studentDetails" class="space-y-6">
                    <!-- Conteúdo será carregado via JavaScript -->
                </div>
                  <div class="flex justify-end mt-6">
                    <button onclick="closeDetailsModal()" 
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Edit Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-cobalt-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-edit text-cobalt-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Editar Aluno</h3>
                            <p class="text-sm text-gray-600">Atualizar informações</p>
                        </div>
                    </div>
                    <button onclick="closeEditModal()" 
                            class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                
                <form id="editForm" method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="editStudentId" name="id" value="">
                    
                    <!-- Basic Information Section -->
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3 pb-3 border-b border-gray-200">
                            <div class="w-8 h-8 bg-cobalt-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-user text-cobalt-600 text-sm"></i>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900">Informações Básicas</h4>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label for="editNome" class="block text-sm font-medium text-gray-700">
                                    Nome Completo <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="editNome" name="nome" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900">
                            </div>
                            
                            <div class="space-y-2">
                                <label for="editEmail" class="block text-sm font-medium text-gray-700">E-mail</label>
                                <input type="email" id="editEmail" name="email"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900">
                            </div>
                            
                            <div class="space-y-2">
                                <label for="editTelefone" class="block text-sm font-medium text-gray-700">Telefone</label>
                                <input type="tel" id="editTelefone" name="telefone"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900">
                            </div>
                            
                            <div class="space-y-2">
                                <label for="editDataNascimento" class="block text-sm font-medium text-gray-700">Data de Nascimento</label>
                                <input type="date" id="editDataNascimento" name="data_nascimento"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900">
                            </div>
                            
                            <div class="space-y-2">
                                <label for="editSexo" class="block text-sm font-medium text-gray-700">
                                    Sexo <span class="text-red-500">*</span>
                                </label>
                                <select id="editSexo" name="sexo" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900">
                                    <option value="">Selecione</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Feminino</option>
                                </select>
                            </div>
                            
                            <div class="space-y-2">
                                <label for="editProfissao" class="block text-sm font-medium text-gray-700">Profissão</label>
                                <input type="text" id="editProfissao" name="profissao"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900">
                            </div>
                        </div>
                    </div>

                    <!-- Address Section -->
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3 pb-3 border-b border-gray-200">
                            <div class="w-8 h-8 bg-cobalt-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-map-marker-alt text-cobalt-600 text-sm"></i>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900">Endereço</h4>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <label for="editEndereco" class="block text-sm font-medium text-gray-700">Endereço</label>
                                <textarea id="editEndereco" name="endereco" rows="2"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 resize-none text-gray-900"></textarea>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="space-y-2">
                                    <label for="editCep" class="block text-sm font-medium text-gray-700">CEP</label>
                                    <input type="text" id="editCep" name="cep"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900">
                                </div>
                                
                                <div class="space-y-2">
                                    <label for="editCidade" class="block text-sm font-medium text-gray-700">Cidade</label>
                                    <input type="text" id="editCidade" name="cidade"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900">
                                </div>
                                
                                <div class="space-y-2">
                                    <label for="editEstado" class="block text-sm font-medium text-gray-700">Estado</label>
                                    <input type="text" id="editEstado" name="estado"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 text-gray-900">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Health Information Section -->
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3 pb-3 border-b border-gray-200">
                            <div class="w-8 h-8 bg-cobalt-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-heartbeat text-cobalt-600 text-sm"></i>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900">Informações de Saúde</h4>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <label for="editObjetivo" class="block text-sm font-medium text-gray-700">Objetivo</label>
                                <textarea id="editObjetivo" name="objetivo" rows="2"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 resize-none text-gray-900"></textarea>
                            </div>
                            
                            <div class="space-y-2">
                                <label for="editRestricoesMedicas" class="block text-sm font-medium text-gray-700">Restrições Médicas</label>
                                <textarea id="editRestricoesMedicas" name="restricoes_medicas" rows="2"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 resize-none text-gray-900"></textarea>
                            </div>
                            
                            <div class="space-y-2">
                                <label for="editMedicamentos" class="block text-sm font-medium text-gray-700">Medicamentos</label>
                                <textarea id="editMedicamentos" name="medicamentos" rows="2"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 resize-none text-gray-900"></textarea>
                            </div>
                            
                            <div class="space-y-2">
                                <label for="editHistoricoLesoes" class="block text-sm font-medium text-gray-700">Histórico de Lesões</label>
                                <textarea id="editHistoricoLesoes" name="historico_lesoes" rows="2"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 resize-none text-gray-900"></textarea>
                            </div>
                            
                            <div class="space-y-2">
                                <label for="editAtividadeFisicaAtual" class="block text-sm font-medium text-gray-700">Atividade Física Atual</label>
                                <textarea id="editAtividadeFisicaAtual" name="atividade_fisica_atual" rows="2"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 resize-none text-gray-900"></textarea>
                            </div>
                            
                            <div class="space-y-2">
                                <label for="editObservacoes" class="block text-sm font-medium text-gray-700">Observações</label>
                                <textarea id="editObservacoes" name="observacoes" rows="3"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cobalt-500 focus:border-transparent transition-all duration-200 resize-none text-gray-900"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex space-x-3 pt-4 border-t border-gray-200">
                        <button type="submit" 
                                class="flex-1 px-4 py-2 bg-cobalt-600 text-white rounded-lg font-semibold hover:bg-cobalt-700 transition-colors">
                            <i class="fas fa-save mr-2"></i>
                            Atualizar Aluno
                        </button>
                        <button type="button" onclick="closeEditModal()" 
                                class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });

        let studentToDelete = null;        function toggleForm() {
            const form = document.getElementById('student-form');
            if (form.classList.contains('hidden')) {
                form.classList.remove('hidden');
                form.scrollIntoView({ behavior: 'smooth' });
            } else {
                form.classList.add('hidden');
                document.getElementById('alunoForm').reset();
            }
        }

        function deleteStudent(id, name) {
            studentToDelete = id;
            document.getElementById('studentName').textContent = name;        document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            studentToDelete = null;
        }        function confirmDeleteAluno() {
            if (studentToDelete) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${studentToDelete}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Variável para armazenar ID do aluno a ser ativado
        let studentToActivate = null;

        // Função para abrir modal de ativação
        function activateStudent(id, name) {
            studentToActivate = id;
            document.getElementById('activateStudentName').textContent = name;
            document.getElementById('activateModal').classList.remove('hidden');
        }

        // Função para fechar modal de ativação
        function closeActivateModal() {
            document.getElementById('activateModal').classList.add('hidden');
            studentToActivate = null;
        }

        // Função para confirmar ativação
        function confirmActivateAluno() {
            if (studentToActivate) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="activate">
                    <input type="hidden" name="id" value="${studentToActivate}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Função para visualizar detalhes do aluno
        function viewStudent(id) {
            document.getElementById('detailsModal').classList.remove('hidden');
            document.getElementById('studentDetails').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i><p class="text-gray-500 mt-2">Carregando...</p></div>';
              fetch(`../api/aluno-detalhes.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayStudentDetails(data.aluno);
                    } else {
                        document.getElementById('studentDetails').innerHTML = '<div class="text-center py-8 text-red-500"><i class="fas fa-exclamation-triangle text-2xl mb-2"></i><p>Erro ao carregar dados do aluno</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    document.getElementById('studentDetails').innerHTML = '<div class="text-center py-8 text-red-500"><i class="fas fa-exclamation-triangle text-2xl mb-2"></i><p>Erro ao carregar dados do aluno</p></div>';
                });
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').classList.add('hidden');
        }

        function displayStudentDetails(aluno) {
            const detailsHtml = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Informações Pessoais -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-user mr-2 text-cobalt-600"></i>
                            Informações Pessoais
                        </h4>
                        <div class="space-y-2">
                            <p><span class="font-medium text-gray-700">Nome:</span> ${aluno.nome || 'N/A'}</p>
                            <p><span class="font-medium text-gray-700">Email:</span> ${aluno.email || 'N/A'}</p>
                            <p><span class="font-medium text-gray-700">Telefone:</span> ${aluno.telefone || 'N/A'}</p>
                            <p><span class="font-medium text-gray-700">Data de Nascimento:</span> ${aluno.data_nascimento ? new Date(aluno.data_nascimento).toLocaleDateString('pt-BR') : 'N/A'}</p>
                            <p><span class="font-medium text-gray-700">Sexo:</span> ${aluno.sexo === 'M' ? 'Masculino' : aluno.sexo === 'F' ? 'Feminino' : 'N/A'}</p>
                            <p><span class="font-medium text-gray-700">Status:</span> 
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${aluno.ativo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${aluno.ativo ? 'Ativo' : 'Inativo'}
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Endereço -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-map-marker-alt mr-2 text-cobalt-600"></i>
                            Endereço
                        </h4>
                        <div class="space-y-2">
                            <p><span class="font-medium text-gray-700">Endereço:</span> ${aluno.endereco || 'N/A'}</p>
                            <p><span class="font-medium text-gray-700">CEP:</span> ${aluno.cep || 'N/A'}</p>
                            <p><span class="font-medium text-gray-700">Cidade:</span> ${aluno.cidade || 'N/A'}</p>
                            <p><span class="font-medium text-gray-700">Estado:</span> ${aluno.estado || 'N/A'}</p>
                        </div>
                    </div>

                    <!-- Informações Profissionais -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-briefcase mr-2 text-cobalt-600"></i>
                            Profissão e Objetivos
                        </h4>
                        <div class="space-y-2">
                            <p><span class="font-medium text-gray-700">Profissão:</span> ${aluno.profissao || 'N/A'}</p>
                            <p><span class="font-medium text-gray-700">Objetivo:</span> ${aluno.objetivo || 'N/A'}</p>
                        </div>
                    </div>

                    <!-- Informações Médicas -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-heartbeat mr-2 text-red-600"></i>
                            Informações Médicas
                        </h4>
                        <div class="space-y-2">
                            <p><span class="font-medium text-gray-700">Restrições Médicas:</span> ${aluno.restricoes_medicas || 'N/A'}</p>
                            <p><span class="font-medium text-gray-700">Medicamentos:</span> ${aluno.medicamentos || 'N/A'}</p>
                            <p><span class="font-medium text-gray-700">Histórico de Lesões:</span> ${aluno.historico_lesoes || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <!-- Atividade Física e Observações -->
                <div class="grid grid-cols-1 gap-6 mt-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-dumbbell mr-2 text-green-600"></i>
                            Atividade Física Atual
                        </h4>
                        <p class="text-gray-700">${aluno.atividade_fisica_atual || 'N/A'}</p>
                    </div>

                    ${aluno.observacoes ? `
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-sticky-note mr-2 text-yellow-600"></i>
                            Observações
                        </h4>
                        <p class="text-gray-700">${aluno.observacoes}</p>
                    </div>
                    ` : ''}
                </div>

                <!-- Datas de Registro -->
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>Cadastrado em: ${aluno.created_at ? new Date(aluno.created_at).toLocaleDateString('pt-BR') : 'N/A'}</span>
                        ${aluno.updated_at ? `<span>Última atualização: ${new Date(aluno.updated_at).toLocaleDateString('pt-BR')}</span>` : ''}
                    </div>
                </div>
            `;
              document.getElementById('studentDetails').innerHTML = detailsHtml;
        }

        // Função para editar aluno
        function editStudent(id) {
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editStudentId').value = id;
            
            // Carregar dados do aluno
            fetch(`../api/aluno-detalhes.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        populateEditForm(data.aluno);
                    } else {
                        alert('Erro ao carregar dados do aluno');
                        closeEditModal();
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar dados do aluno');
                    closeEditModal();
                });
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editForm').reset();
        }

        function populateEditForm(aluno) {
            document.getElementById('editNome').value = aluno.nome || '';
            document.getElementById('editEmail').value = aluno.email || '';
            document.getElementById('editTelefone').value = aluno.telefone || '';
            document.getElementById('editDataNascimento').value = aluno.data_nascimento || '';
            document.getElementById('editSexo').value = aluno.sexo || '';
            document.getElementById('editProfissao').value = aluno.profissao || '';
            document.getElementById('editEndereco').value = aluno.endereco || '';
            document.getElementById('editCep').value = aluno.cep || '';
            document.getElementById('editCidade').value = aluno.cidade || '';
            document.getElementById('editEstado').value = aluno.estado || '';
            document.getElementById('editObjetivo').value = aluno.objetivo || '';
            document.getElementById('editRestricoesMedicas').value = aluno.restricoes_medicas || '';
            document.getElementById('editMedicamentos').value = aluno.medicamentos || '';
            document.getElementById('editHistoricoLesoes').value = aluno.historico_lesoes || '';
            document.getElementById('editAtividadeFisicaAtual').value = aluno.atividade_fisica_atual || '';
            document.getElementById('editObservacoes').value = aluno.observacoes || '';
        }

        // Phone mask
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 11) {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (value.length >= 7) {
                value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
            } else if (value.length >= 3) {
                value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
            }
            e.target.value = value;
        });        // CEP mask
        document.getElementById('cep').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{5})(\d{3})/, '$1-$2');
            e.target.value = value;
        });

        // Phone mask for edit modal
        document.getElementById('editTelefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 11) {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (value.length >= 7) {
                value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
            } else if (value.length >= 3) {
                value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
            }
            e.target.value = value;
        });

        // CEP mask for edit modal
        document.getElementById('editCep').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{5})(\d{3})/, '$1-$2');
            e.target.value = value;
        });
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
