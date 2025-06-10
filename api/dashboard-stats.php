<?php
/**
 * SmartBioFit Dashboard Stats API
 * Returns real-time statistics for dashboard updates
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Check if user is authenticated
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $db = Database::getInstance();
    $user_id = $_SESSION['user_id'];
    
    // Get user info
    $user = $db->fetch("SELECT tipo FROM usuarios WHERE id = ?", [$user_id]);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    $stats = [];
    
    if ($user['tipo'] === 'admin' || $user['tipo'] === 'professor') {
        // Statistics for professors/admins
        $stats['total_alunos'] = $db->fetch("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'aluno' AND ativo = TRUE")['total'];
        $stats['avaliacoes_mes'] = $db->fetch("SELECT COUNT(*) as total FROM logs WHERE acao LIKE '%avaliacao%' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)")['total'];
        $stats['treinos_ativos'] = 0; // Placeholder for Milestone 4
        $stats['usuarios_online'] = $db->fetch("SELECT COUNT(*) as total FROM usuarios WHERE ultimo_acesso >= DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND ativo = TRUE")['total'];
    } else {
        // Statistics for students
        $stats['minhas_avaliacoes'] = $db->fetch("SELECT COUNT(*) as total FROM logs WHERE usuario_id = ? AND acao LIKE '%avaliacao%'", [$user_id])['total'];
        $stats['meus_treinos'] = 0; // Placeholder for Milestone 4
        $stats['dias_cadastrado'] = $db->fetch("SELECT DATEDIFF(NOW(), created_at) as dias FROM usuarios WHERE id = ?", [$user_id])['dias'];
        $stats['ultimo_treino'] = 0; // Placeholder for Milestone 4
    }
    
    // Add timestamp for caching
    $stats['timestamp'] = time();
    $stats['success'] = true;
    
    echo json_encode($stats);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage(),
        'success' => false
    ]);
}
