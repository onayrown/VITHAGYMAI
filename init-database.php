<?php
/**
 * SMARTBIOFIT - Script de Inicialização do Banco de Dados
 * Este script cria as tabelas automaticamente quando o container é iniciado
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

echo "🚀 SMARTBIOFIT - Inicializando banco de dados...\n";

try {
    // Aguarda o MySQL estar pronto (máximo 30 segundos)
    $maxAttempts = 30;
    $attempt = 0;
    
    while ($attempt < $maxAttempts) {
        try {
            // Tenta conexão simples
            $testPdo = new PDO(
                "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            echo "✅ Conexão com MySQL estabelecida!\n";
            break;
        } catch (PDOException $e) {
            $attempt++;
            echo "⏳ Aguardando MySQL... (tentativa $attempt/$maxAttempts)\n";
            sleep(1);
        }
    }
    
    if ($attempt >= $maxAttempts) {
        throw new Exception("❌ Não foi possível conectar ao MySQL após $maxAttempts tentativas");
    }
    
    // Agora cria as tabelas usando a classe Database
    echo "📊 Criando/verificando tabelas...\n";
    $db = Database::getInstance();
    $db->createTables();
    
    echo "✅ Banco de dados inicializado com sucesso!\n";
    echo "🔑 Usuário admin padrão: admin@smartbiofit.com / admin123\n";
    echo "🌐 Acesse: http://localhost:8080\n";
    
} catch (Exception $e) {
    echo "❌ Erro na inicialização: " . $e->getMessage() . "\n";
    exit(1);
}
?>