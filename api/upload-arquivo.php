<?php
/**
 * SMARTBIOFIT - API de Upload de Arquivos
 * Endpoint para upload de vídeos e imagens de exercícios
 */

require_once '../config.php';
require_once '../database.php';

// Verificar se é um POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Verificar autenticação
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

try {
    $db = Database::getInstance();
    $professor_id = $_SESSION['user_id'];
    
    // Verificar se há arquivo enviado
    if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Nenhum arquivo válido foi enviado');
    }
    
    $arquivo = $_FILES['arquivo'];
    $exercicio_id = (int)($_POST['exercicio_id'] ?? 0);
    $tipo_arquivo = $_POST['tipo'] ?? 'imagem'; // 'imagem' ou 'video'
    
    // Verificar se o exercício pertence ao professor
    if ($exercicio_id > 0) {
        $exercicio = $db->fetch("SELECT id FROM exercicios WHERE id = ? AND created_by = ?", [$exercicio_id, $professor_id]);
        if (!$exercicio) {
            throw new Exception('Exercício não encontrado ou sem permissão');
        }
    }
    
    // Validar tipo de arquivo
    $extensoesPermitidas = [
        'imagem' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'video' => ['mp4', 'webm', 'ogg', 'mov', 'avi']
    ];
    
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    if (!in_array($extensao, $extensoesPermitidas[$tipo_arquivo])) {
        throw new Exception('Tipo de arquivo não permitido para ' . $tipo_arquivo);
    }
    
    // Validar tamanho do arquivo
    $tamanhoMaximo = [
        'imagem' => 5 * 1024 * 1024, // 5MB
        'video' => 50 * 1024 * 1024   // 50MB
    ];
    
    if ($arquivo['size'] > $tamanhoMaximo[$tipo_arquivo]) {
        $tamanhoMB = round($tamanhoMaximo[$tipo_arquivo] / 1024 / 1024);
        throw new Exception("Arquivo muito grande. Máximo permitido: {$tamanhoMB}MB para {$tipo_arquivo}");
    }
    
    // Criar diretório de destino se não existir
    $diretorioBase = '../uploads/';
    $diretorioTipo = $tipo_arquivo === 'video' ? 'videos/' : 'fotos/';
    $diretorioCompleto = $diretorioBase . $diretorioTipo;
    
    if (!is_dir($diretorioCompleto)) {
        mkdir($diretorioCompleto, 0755, true);
    }
    
    // Gerar nome único para o arquivo
    $nomeOriginal = pathinfo($arquivo['name'], PATHINFO_FILENAME);
    $nomeSeguro = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nomeOriginal);
    $nomeSeguro = substr($nomeSeguro, 0, 50); // Limitar tamanho
    $nomeArquivo = $nomeSeguro . '_' . time() . '_' . uniqid() . '.' . $extensao;
    $caminhoCompleto = $diretorioCompleto . $nomeArquivo;
    
    // Fazer upload do arquivo
    if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
        throw new Exception('Erro ao salvar arquivo no servidor');
    }
    
    // URL pública do arquivo
    $urlPublica = APP_URL . '/uploads/' . $diretorioTipo . $nomeArquivo;
    
    // Se for para um exercício específico, atualizar no banco
    if ($exercicio_id > 0) {
        $campo = $tipo_arquivo === 'video' ? 'video_url' : 'imagem_url';
        $sql = "UPDATE exercicios SET {$campo} = ? WHERE id = ? AND created_by = ?";
        $db->execute($sql, [$urlPublica, $exercicio_id, $professor_id]);
    }
    
    // Registrar upload no banco de dados (para controle)
    $sql_log = "INSERT INTO uploads_log (professor_id, exercicio_id, tipo_arquivo, nome_original, nome_arquivo, tamanho, url_publica) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $db->execute($sql_log, [
        $professor_id,
        $exercicio_id > 0 ? $exercicio_id : null,
        $tipo_arquivo,
        $arquivo['name'],
        $nomeArquivo,
        $arquivo['size'],
        $urlPublica
    ]);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'url' => $urlPublica,
            'nome_arquivo' => $nomeArquivo,
            'tamanho' => $arquivo['size'],
            'tipo' => $tipo_arquivo,
            'exercicio_id' => $exercicio_id > 0 ? $exercicio_id : null
        ],
        'message' => ucfirst($tipo_arquivo) . ' enviado com sucesso!'
    ]);
    
} catch (Exception $e) {
    // Limpar arquivo se foi criado mas houve erro
    if (isset($caminhoCompleto) && file_exists($caminhoCompleto)) {
        unlink($caminhoCompleto);
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
