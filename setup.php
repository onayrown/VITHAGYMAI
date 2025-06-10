<?php
/**
 * SMARTBIOFIT - Inicialização do Banco de Dados
 * Execute este arquivo uma vez para criar as tabelas e dados iniciais
 */

require_once 'config.php';
require_once 'database.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - SMARTBIOFIT</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/styles.css">
</head>
<body style="background-color: var(--cinza-claro);">
    <div class="container" style="max-width: 600px; margin-top: 2rem;">        <div class="card">            <div class="card-header text-center">
                <img src="<?php echo APP_URL; ?>/assets/images/logo-smartbiofit.png" alt="SMARTBIOFIT" style="height: 60px;">
                <h1 class="h3 mt-2 text-primary">Setup do Sistema</h1>
                <p class="text-muted">Inicialização do Sistema</p>
            </div>
            <div class="card-body">
                <?php
                try {
                    echo "<div class='alert alert-info'>Iniciando setup do banco de dados...</div>";
                    
                    // Testa conexão
                    $db = Database::getInstance();
                    echo "<div class='alert alert-success'>✅ Conexão com banco estabelecida</div>";
                    
                    // Cria tabelas
                    echo "<div class='alert alert-info'>Criando tabelas...</div>";
                    $db->createTables();
                    echo "<div class='alert alert-success'>✅ Tabelas criadas/verificadas</div>";
                    
                    // Verifica se admin já existe
                    $admin = $db->fetch("SELECT * FROM usuarios WHERE email = 'admin@smartbiofit.com'");
                    if ($admin) {
                        echo "<div class='alert alert-warning'>⚠️ Usuário admin já existe</div>";
                    } else {
                        echo "<div class='alert alert-success'>✅ Usuário admin criado</div>";
                    }
                    
                    echo "<div class='alert alert-success'>";
                    echo "<h5>🎉 Setup concluído com sucesso!</h5>";
                    echo "<p><strong>Dados de acesso:</strong></p>";
                    echo "<ul>";
                    echo "<li><strong>Email:</strong> admin@smartbiofit.com</li>";
                    echo "<li><strong>Senha:</strong> admin123</li>";
                    echo "<li><strong>Tipo:</strong> Administrador</li>";
                    echo "</ul>";
                    echo "</div>";
                    
                    echo "<div class='text-center mt-4'>";
                    echo "<a href='" . APP_URL . "/login.php' class='btn btn-primary btn-lg'>Fazer Login</a>";
                    echo "</div>";
                    
                } catch (Exception $e) {
                    echo "<div class='alert alert-danger'>";
                    echo "<h5>❌ Erro no Setup</h5>";
                    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "<hr>";
                    echo "<h6>Verificações:</h6>";
                    echo "<ul>";
                    echo "<li>XAMPP está rodando?</li>";
                    echo "<li>MySQL está ativo?</li>";
                    echo "<li>Banco 'smartbiofit' foi criado?</li>";
                    echo "<li>Credenciais no .env estão corretas?</li>";
                    echo "</ul>";
                    echo "</div>";
                }
                ?>
            </div>
        </div>
        
        <!-- Informações de configuração -->
        <div class="card mt-4">
            <div class="card-header">
                <h5>📋 Informações de Configuração</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <strong>Banco de Dados:</strong><br>
                        Host: <?php echo DB_HOST; ?><br>
                        Porta: <?php echo DB_PORT; ?><br>
                        Database: <?php echo DB_NAME; ?><br>
                        Usuário: <?php echo DB_USER; ?>
                    </div>
                    <div class="col-6">
                        <strong>Aplicação:</strong><br>
                        URL: <?php echo APP_URL; ?><br>
                        Ambiente: <?php echo APP_ENV; ?><br>
                        Debug: <?php echo APP_DEBUG ? 'Ativo' : 'Inativo'; ?><br>
                        Timezone: America/Sao_Paulo
                    </div>
                </div>
                
                <?php if (APP_DEBUG): ?>
                <div class="mt-3 p-3" style="background-color: var(--amarelo-claro); border-radius: var(--border-radius);">
                    <strong>Modo Debug Ativo</strong><br>
                    <small>
                        • Logs detalhados habilitados<br>
                        • Informações de desenvolvimento visíveis<br>
                        • Para produção, altere APP_DEBUG para false no .env
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Próximos passos -->
        <div class="card mt-4">
            <div class="card-header">
                <h5>🚀 Próximos Passos</h5>
            </div>
            <div class="card-body">
                <ol>
                    <li>Faça login com as credenciais de admin</li>
                    <li>Acesse o painel administrativo</li>
                    <li>Crie usuários instrutores</li>
                    <li>Configure as preferências do sistema</li>
                    <li>Comece a cadastrar alunos (Milestone 2)</li>
                </ol>
                
                <div class="mt-3">
                    <strong>Milestone 1 - Concluído ✅</strong><br>
                    <small class="text-muted">
                        • Estrutura do projeto criada<br>
                        • Banco de dados configurado<br>
                        • Sistema de login funcional<br>
                        • Interface responsiva implementada
                    </small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
