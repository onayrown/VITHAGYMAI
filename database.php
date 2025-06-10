<?php
/**
 * SMARTBIOFIT - Classe de Conexão com Banco de Dados
 */

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->connection->exec("SET time_zone = '-03:00'");
            
        } catch (PDOException $e) {
            logError("Erro de conexão com banco de dados: " . $e->getMessage());
            throw new Exception("Erro de conexão com banco de dados");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            logError("Erro na query: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Erro na execução da query");
        }
    }
    
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    // Método para criar tabelas automaticamente
    public function createTables() {
        $tables = [
            // Tabela de usuários
            'usuarios' => "
                CREATE TABLE IF NOT EXISTS usuarios (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(100) NOT NULL,
                    email VARCHAR(100) UNIQUE NOT NULL,
                    senha VARCHAR(255) NOT NULL,
                    tipo ENUM('admin', 'professor', 'aluno') DEFAULT 'professor',
                    telefone VARCHAR(20),
                    foto VARCHAR(255),
                    ativo BOOLEAN DEFAULT TRUE,
                    token_reset VARCHAR(100),
                    token_reset_expira DATETIME,
                    ultimo_acesso DATETIME,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            // Tabela de sessões
            'sessoes' => "
                CREATE TABLE IF NOT EXISTS sessoes (
                    id VARCHAR(128) PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    payload TEXT,
                    ultima_atividade INT,
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
              // Tabela de logs
            'logs' => "
                CREATE TABLE IF NOT EXISTS logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT,
                    acao VARCHAR(100) NOT NULL,
                    descricao TEXT,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            // Tabela de alunos
            'alunos' => "
                CREATE TABLE IF NOT EXISTS alunos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(100) NOT NULL,
                    email VARCHAR(100) UNIQUE,
                    telefone VARCHAR(20),
                    data_nascimento DATE,
                    sexo ENUM('M', 'F') NOT NULL,
                    endereco TEXT,
                    cep VARCHAR(10),
                    cidade VARCHAR(50),
                    estado VARCHAR(2),
                    profissao VARCHAR(100),
                    objetivo TEXT,
                    restricoes_medicas TEXT,
                    medicamentos TEXT,
                    historico_lesoes TEXT,
                    atividade_fisica_atual TEXT,
                    foto VARCHAR(255),
                    professor_id INT NOT NULL,
                    ativo BOOLEAN DEFAULT TRUE,
                    observacoes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ];
        
        foreach ($tables as $tableName => $sql) {
            try {
                $this->connection->exec($sql);
                logError("Tabela '$tableName' criada/verificada com sucesso", 'info');
            } catch (PDOException $e) {
                logError("Erro ao criar tabela '$tableName': " . $e->getMessage());
                throw new Exception("Erro ao criar tabela $tableName");
            }
        }
        
        // Insere usuário admin padrão se não existir
        $this->createDefaultAdmin();
    }
    
    private function createDefaultAdmin() {
        $adminExists = $this->fetch("SELECT id FROM usuarios WHERE email = ? AND tipo = 'admin'", ['admin@smartbiofit.com']);
        
        if (!$adminExists) {
            $senha = hashPassword('admin123');
            $this->execute("
                INSERT INTO usuarios (nome, email, senha, tipo, ativo) 
                VALUES (?, ?, ?, 'admin', TRUE)
            ", ['Administrador', 'admin@smartbiofit.com', $senha]);
            
            logError("Usuário admin padrão criado: admin@smartbiofit.com / admin123", 'info');
        }
    }
}
?>
