<?php
/**
 * SMARTBIOFIT - API: Detalhes do Treino
 * Endpoint para retornar detalhes completos de um treino
 * Usado para visualização mobile e compartilhamento
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';
require_once '../database.php';

try {
    $db = Database::getInstance();
    
    // Verificar parâmetros
    $treino_id = $_GET['id'] ?? null;
    $hash = $_GET['hash'] ?? null;
    
    if (!$treino_id && !$hash) {
        throw new Exception('ID do treino ou hash de compartilhamento é obrigatório');
    }
    
    // Buscar treino
    if ($hash) {
        // Buscar por hash de compartilhamento (para acesso público via QR code)
        $sql_treino = "
            SELECT t.*, u.nome as professor_nome, u.email as professor_email,
                   a.nome as aluno_nome, at.data_inicio, at.data_fim, at.status,
                   at.observacoes_individuais, at.url_compartilhamento
            FROM treinos t
            JOIN usuarios u ON t.professor_id = u.id
            LEFT JOIN aluno_treinos at ON t.id = at.treino_id AND at.url_compartilhamento = ?
            LEFT JOIN alunos a ON at.aluno_id = a.id
            WHERE t.ativo = 1 AND (at.url_compartilhamento = ? OR t.template = 1)
            LIMIT 1
        ";
        $treino = $db->fetchRow($sql_treino, [$hash, $hash]);
    } else {
        // Buscar por ID (para acesso interno)
        $sql_treino = "
            SELECT t.*, u.nome as professor_nome, u.email as professor_email
            FROM treinos t
            JOIN usuarios u ON t.professor_id = u.id
            WHERE t.id = ? AND t.ativo = 1
            LIMIT 1
        ";
        $treino = $db->fetchRow($sql_treino, [$treino_id]);
    }
    
    if (!$treino) {
        throw new Exception('Treino não encontrado ou não disponível');
    }
    
    // Buscar exercícios do treino
    $sql_exercicios = "
        SELECT e.*, te.ordem_execucao, te.series, te.repeticoes, te.carga,
               te.tempo_descanso, te.tempo_execucao, te.distancia, 
               te.observacoes_especificas
        FROM treino_exercicios te
        JOIN exercicios e ON te.exercicio_id = e.id
        WHERE te.treino_id = ? AND e.ativo = 1
        ORDER BY te.ordem_execucao ASC
    ";
    $exercicios = $db->fetchAll($sql_exercicios, [$treino['id']]);
    
    // Agrupar exercícios por categoria
    $exercicios_agrupados = [];
    foreach ($exercicios as $exercicio) {
        $categoria = $exercicio['categoria'];
        if (!isset($exercicios_agrupados[$categoria])) {
            $exercicios_agrupados[$categoria] = [];
        }
        $exercicios_agrupados[$categoria][] = $exercicio;
    }
    
    // Estatísticas do treino
    $total_exercicios = count($exercicios);
    $tempo_total_estimado = $treino['duracao_estimada'];
    
    // Calcular tempo baseado nos exercícios se não especificado
    if ($tempo_total_estimado == 0) {
        $tempo_exercicios = 0;
        $tempo_descanso_total = 0;
        
        foreach ($exercicios as $exercicio) {
            // Tempo de execução do exercício
            if ($exercicio['tempo_execucao'] > 0) {
                $tempo_exercicios += $exercicio['tempo_execucao'];
            } elseif ($exercicio['tempo_execucao_sugerido'] > 0) {
                $tempo_exercicios += $exercicio['tempo_execucao_sugerido'];
            } else {
                // Estimar 30 segundos por série se não especificado
                $tempo_exercicios += ($exercicio['series'] * 30);
            }
            
            // Tempo de descanso
            $tempo_descanso_total += ($exercicio['tempo_descanso'] * $exercicio['series']);
        }
        
        $tempo_total_estimado = ceil(($tempo_exercicios + $tempo_descanso_total) / 60); // Converter para minutos
    }
    
    // Buscar estatísticas de execução (se disponível)
    $estatisticas = null;
    if ($hash) {
        $sql_stats = "
            SELECT COUNT(*) as total_execucoes,
                   AVG(tempo_total) as tempo_medio,
                   AVG(CASE 
                       WHEN nivel_dificuldade_percebida = 'muito_facil' THEN 1
                       WHEN nivel_dificuldade_percebida = 'facil' THEN 2
                       WHEN nivel_dificuldade_percebida = 'moderado' THEN 3
                       WHEN nivel_dificuldade_percebida = 'dificil' THEN 4
                       WHEN nivel_dificuldade_percebida = 'muito_dificil' THEN 5
                       ELSE 3
                   END) as dificuldade_media,
                   MAX(data_execucao) as ultima_execucao
            FROM treino_execucoes te
            JOIN aluno_treinos at ON te.aluno_treino_id = at.id
            WHERE at.url_compartilhamento = ?
        ";
        $estatisticas = $db->fetchRow($sql_stats, [$hash]);
    }
    
    // Preparar resposta
    $response = [
        'success' => true,
        'treino' => [
            'id' => $treino['id'],
            'nome' => $treino['nome'],
            'descricao' => $treino['descricao'],
            'tipo_treino' => $treino['tipo_treino'],
            'nivel_dificuldade' => $treino['nivel_dificuldade'],
            'duracao_estimada' => $tempo_total_estimado,
            'objetivo_principal' => $treino['objetivo_principal'],
            'observacoes_gerais' => $treino['observacoes_gerais'],
            'template' => (bool)$treino['template'],
            'created_at' => $treino['created_at'],
            'professor' => [
                'nome' => $treino['professor_nome'],
                'email' => $treino['professor_email']
            ]
        ],
        'exercicios' => $exercicios,
        'exercicios_agrupados' => $exercicios_agrupados,
        'estatisticas' => [
            'total_exercicios' => $total_exercicios,
            'tempo_total_estimado' => $tempo_total_estimado,
            'categorias' => array_keys($exercicios_agrupados)
        ]
    ];
    
    // Adicionar dados específicos se for acesso via hash (aluno)
    if ($hash && isset($treino['aluno_nome'])) {
        $response['aluno'] = [
            'nome' => $treino['aluno_nome'],
            'data_inicio' => $treino['data_inicio'],
            'data_fim' => $treino['data_fim'],
            'status' => $treino['status'],
            'observacoes_individuais' => $treino['observacoes_individuais']
        ];
        
        if ($estatisticas) {
            $response['estatisticas']['execucoes'] = [
                'total' => (int)$estatisticas['total_execucoes'],
                'tempo_medio' => round($estatisticas['tempo_medio'], 1),
                'dificuldade_media' => round($estatisticas['dificuldade_media'], 1),
                'ultima_execucao' => $estatisticas['ultima_execucao']
            ];
        }
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
