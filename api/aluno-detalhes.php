<?php
ob_start(); // Start output buffering at the very beginning

/**
 * SMARTBIOFIT - API para detalhes do aluno
 * Retorna dados completos de um aluno específico
 */

require_once '../config.php';
require_once '../database.php';

// Verificar autenticação (session_start já foi chamado em config.php)
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'professor')) {
    ob_end_clean(); // Clean buffer before sending error
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

try {
    $id = $_GET['id'] ?? '';
    
    if (!$id || !is_numeric($id)) {
        throw new Exception('ID do aluno inválido');
    }
    
    $db = Database::getInstance();
    
    // Buscar aluno (verificando se pertence ao professor logado ou se é admin)
    $where_clause = "id = ?";
    $params = [$id];
    
    if ($_SESSION['user_type'] !== 'admin') {
        $where_clause .= " AND professor_id = ?";
        $params[] = $_SESSION['user_id'];
    }
    
    $aluno = $db->fetch("SELECT * FROM alunos WHERE $where_clause", $params);
    
    if (!$aluno) {
        throw new Exception('Aluno não encontrado');
    }
    
    // Log da consulta (still commented out)
    /*
    if (function_exists('logActivity')) {
        try {
            logActivity($_SESSION['user_id'], 'visualizar_aluno', "Detalhes do aluno '{$aluno['nome']}' visualizados");
        } catch (Throwable $t) {
            // Log to PHP error log if logging activity fails, but don\'t break the response
            error_log("Error in logActivity within aluno-detalhes.php: " . $t->getMessage());
        }
    } else {
        error_log("logActivity function not found when trying to log in aluno-detalhes.php");
    }
    */
    
    ob_end_clean(); // Clean any previous output (notices, warnings)
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'aluno' => $aluno
    ]);
    exit;
    
} catch (Exception $e) {
    ob_end_clean(); // Clean any previous output
    header('Content-Type: application/json');
    http_response_code(400); // Or appropriate error code based on exception type
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}

// Fallback for any case not caught above
if (ob_get_length()) {
    ob_end_clean(); // Clean any unexpected output
}
header('Content-Type: application/json');
http_response_code(500); // Internal Server Error
echo json_encode(['success' => false, 'message' => 'Erro inesperado no servidor.']);
exit;
?>
