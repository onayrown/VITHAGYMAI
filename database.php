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
            $error_message = "Erro na execucao da query: " . $e->getMessage();
            logError($error_message . " | SQL: " . $sql . " | Params: " . json_encode($params));
            throw new Exception($error_message);
        }
    }
    
    public function fetch($sql, $params = []) {
        try {
            return $this->query($sql, $params)->fetch();
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function fetchAll($sql, $params = []) {
        try {
            return $this->query($sql, $params)->fetchAll();
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function execute($sql, $params = []) {
        try {
            return $this->query($sql, $params);
        } catch (Exception $e) {
            throw $e;
        }
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
            ",
            
            // Tabela de agendamentos de avaliações
            'agendamentos_avaliacoes' => "
                CREATE TABLE IF NOT EXISTS agendamentos_avaliacoes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    aluno_id INT NOT NULL,
                    professor_id INT NOT NULL,
                    data_agendamento DATETIME NOT NULL,
                    tipo_avaliacao VARCHAR(50),
                    status ENUM('agendada', 'confirmada', 'cancelada', 'realizada') DEFAULT 'agendada',
                    observacoes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
                    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            // Tabela de treinos
            'treinos' => "
                CREATE TABLE IF NOT EXISTS treinos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(100) NOT NULL,
                    descricao TEXT,
                    aluno_id INT NOT NULL,
                    usuario_id INT NOT NULL,
                    share_hash VARCHAR(100) UNIQUE,
                    ativo BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            // Tabela de exercícios
            'exercicios' => "
                CREATE TABLE IF NOT EXISTS exercicios (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(100) NOT NULL,
                    descricao TEXT,
                    grupo_muscular VARCHAR(100),
                    categoria VARCHAR(50),
                    equipamento VARCHAR(100),
                    dificuldade ENUM('iniciante', 'intermediario', 'avancado') DEFAULT 'iniciante',
                    instrucoes TEXT,
                    video_url VARCHAR(255),
                    imagem_url VARCHAR(255),
                    ativo BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            // Tabela de exercícios do treino
            'treino_exercicios' => "
                CREATE TABLE IF NOT EXISTS treino_exercicios (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    treino_id INT NOT NULL,
                    exercicio_id INT NOT NULL,
                    ordem INT NOT NULL DEFAULT 1,
                    series INT,
                    repeticoes VARCHAR(50),
                    peso VARCHAR(50),
                    descanso VARCHAR(50),
                    observacoes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (treino_id) REFERENCES treinos(id) ON DELETE CASCADE,
                    FOREIGN KEY (exercicio_id) REFERENCES exercicios(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            // Tabela de execuções de treino
            'execucoes_treino' => "
                CREATE TABLE IF NOT EXISTS execucoes_treino (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    treino_id INT NOT NULL,
                    aluno_id INT NOT NULL,
                    data_execucao DATETIME NOT NULL,
                    tempo_total INT, -- em minutos
                    observacoes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (treino_id) REFERENCES treinos(id) ON DELETE CASCADE,
                    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            // Tabela de avaliações físicas
            'avaliacoes_fisicas' => "
                CREATE TABLE IF NOT EXISTS avaliacoes_fisicas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    aluno_id INT NOT NULL,
                    professor_id INT NOT NULL,
                    data_avaliacao DATE NOT NULL,
                    peso DECIMAL(5,2),
                    altura DECIMAL(3,2),
                    imc DECIMAL(4,2),
                    percentual_gordura DECIMAL(4,2),
                    massa_muscular DECIMAL(5,2),
                    pressao_arterial VARCHAR(20),
                    frequencia_cardiaca INT,
                    observacoes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
                    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            // Tabela de medidas corporais
            'medidas_corporais' => "
                CREATE TABLE IF NOT EXISTS medidas_corporais (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    avaliacao_id INT NOT NULL,
                    pescoco DECIMAL(5,2),
                    ombro DECIMAL(5,2),
                    torax DECIMAL(5,2),
                    braco_direito DECIMAL(5,2),
                    braco_esquerdo DECIMAL(5,2),
                    antebraco_direito DECIMAL(5,2),
                    antebraco_esquerdo DECIMAL(5,2),
                    cintura DECIMAL(5,2),
                    quadril DECIMAL(5,2),
                    coxa_direita DECIMAL(5,2),
                    coxa_esquerda DECIMAL(5,2),
                    panturrilha_direita DECIMAL(5,2),
                    panturrilha_esquerda DECIMAL(5,2),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (avaliacao_id) REFERENCES avaliacoes_fisicas(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            // Tabela de notificações
            'notificacoes' => "
                CREATE TABLE IF NOT EXISTS notificacoes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    aluno_id INT NOT NULL,
                    titulo VARCHAR(255) NOT NULL,
                    mensagem TEXT NOT NULL,
                    tipo ENUM('info', 'aviso', 'sucesso', 'erro') DEFAULT 'info',
                    visualizada BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            // Tabela de VO2 Max
            'vo2max' => "
                CREATE TABLE IF NOT EXISTS vo2max (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    avaliacao_id INT NOT NULL,
                    protocolo VARCHAR(100),
                    carga VARCHAR(50),
                    tempo VARCHAR(50),
                    vo2_max_resultado DECIMAL(5,2),
                    classificacao VARCHAR(50),
                    observacoes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (avaliacao_id) REFERENCES avaliacoes_fisicas(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",

            // Tabela de Avaliação Postural
            'avaliacao_postural' => "
                CREATE TABLE IF NOT EXISTS avaliacao_postural (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    avaliacao_id INT NOT NULL,
                    vista_anterior TEXT,
                    vista_lateral TEXT,
                    vista_posterior TEXT,
                    observacoes_gerais TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (avaliacao_id) REFERENCES avaliacoes_fisicas(id) ON DELETE CASCADE
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
        
        // Criar usuário admin padrão se não existir
        $adminExists = $this->fetch("SELECT id FROM usuarios WHERE email = ? AND tipo = 'admin'", ['admin@vithagymai.com']);
        
        if (!$adminExists) {
            $senha = password_hash('admin123', PASSWORD_DEFAULT);
            $this->query("
                INSERT INTO usuarios (nome, email, senha, tipo) 
                VALUES (?, ?, ?, 'admin')
            ", ['Administrador', 'admin@vithagymai.com', $senha]);
            
            logError("Usuário admin padrão criado: admin@vithagymai.com / admin123", 'info');
        }
    }
}
?>
