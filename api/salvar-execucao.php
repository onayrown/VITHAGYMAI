<?php
/**
 * SMARTBIOFIT - API: Salvar Execução do Treino
 * Endpoint para registrar a conclusão de um treino pelo aluno
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';
require_once '../database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Dados inválidos');
    }
    
    $hash = $input['hash'] ?? null;
    $exercicios_realizados = $input['exercicios_realizados'] ?? [];
    $tempo_total = (int)($input['tempo_total'] ?? 0);
    $observacoes_aluno = trim($input['observacoes_aluno'] ?? '');
    $nivel_dificuldade = $input['nivel_dificuldade_percebida'] ?? 'moderado';
    
    if (!$hash) {
        throw new Exception('Hash de compartilhamento é obrigatório');
    }
    
    $db = Database::getInstance();
    
    // Buscar associação do treino
    $sql_associacao = "
        SELECT at.id, at.aluno_id, at.treino_id, t.nome as treino_nome
        FROM aluno_treinos at
        JOIN treinos t ON at.treino_id = t.id
        WHERE at.url_compartilhamento = ? AND at.status = 'ativo'
        LIMIT 1
    ";
    $associacao = $db->fetchRow($sql_associacao, [$hash]);
    
    if (!$associacao) {
        throw new Exception('Treino não encontrado ou não está ativo');
    }
    
    // Preparar dados dos exercícios realizados
    $exercicios_data = [];
    if (!empty($exercicios_realizados)) {
        foreach ($exercicios_realizados as $exercicio_id) {
            // Buscar detalhes do exercício
            $exercicio = $db->fetchRow("
                SELECT e.nome, te.series, te.repeticoes, te.carga, te.tempo_descanso
                FROM exercicios e
                JOIN treino_exercicios te ON e.id = te.exercicio_id
                WHERE e.id = ? AND te.treino_id = ?
            ", [$exercicio_id, $associacao['treino_id']]);
            
            if ($exercicio) {
                $exercicios_data[] = [
                    'exercicio_id' => $exercicio_id,
                    'nome' => $exercicio['nome'],
                    'series_realizadas' => $exercicio['series'],
                    'repeticoes_realizadas' => $exercicio['repeticoes'],
                    'carga_utilizada' => $exercicio['carga'],
                    'concluido' => true
                ];
            }
        }
    }
    
    // Inserir execução do treino
    $sql_execucao = "
        INSERT INTO treino_execucoes (
            aluno_treino_id, 
            data_execucao, 
            exercicios_realizados, 
            tempo_total, 
            observacoes_aluno, 
            nivel_dificuldade_percebida
        ) VALUES (?, NOW(), ?, ?, ?, ?)
    ";
    
    $params = [
        $associacao['id'],
        json_encode($exercicios_data, JSON_UNESCAPED_UNICODE),
        $tempo_total,
        $observacoes_aluno,
        $nivel_dificuldade
    ];
    
    $db->execute($sql_execucao, $params);
    $execucao_id = $db->lastInsertId();
      // Calcular estatísticas da execução
    $total_exercicios = $db->fetch("
        SELECT COUNT(*) as total FROM treino_exercicios WHERE treino_id = ?
    ", [$associacao['treino_id']])['total'];
    
    $exercicios_concluidos = count($exercicios_realizados);
    $percentual_conclusao = $total_exercicios > 0 ? round(($exercicios_concluidos / $total_exercicios) * 100) : 0;
    
    // Resposta de sucesso
    $response = [
        'success' => true,
        'message' => 'Execução do treino salva com sucesso!',
        'execucao_id' => $execucao_id,
        'estatisticas' => [
            'total_exercicios' => $total_exercicios,
            'exercicios_concluidos' => $exercicios_concluidos,
            'percentual_conclusao' => $percentual_conclusao,
            'tempo_total' => $tempo_total,
            'nivel_dificuldade_percebida' => $nivel_dificuldade
        ],
        'treino' => [
            'nome' => $associacao['treino_nome']
        ]
    ];
    
    // Log da atividade (opcional)
    $log_message = sprintf(
        "Treino '%s' executado. Exercícios: %d/%d (%d%%). Tempo: %d min.",
        $associacao['treino_nome'],
        $exercicios_concluidos,
        $total_exercicios,
        $percentual_conclusao,
        $tempo_total
    );
    
    error_log($log_message, 3, '../logs/app.log');
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
