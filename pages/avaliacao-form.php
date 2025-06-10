<?php
/**
 * SMARTBIOFIT - Formulário de Avaliação Física
 * Formulários específicos para cada tipo de avaliação
 */

require_once '../config.php';
require_once '../database.php';

// Função para registrar atividades
function logActivity($user_id, $action, $description) {
    $db = Database::getInstance();
    $db->execute("INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)", 
                [$user_id, $action, $description]);
}

// Função para calcular composição corporal
function calcularComposicaoCorporal($protocolo, $dobras, $idade, $sexo) {
    $soma_dobras = 0;
    $densidade_corporal = 0;
    $percentual_gordura = 0;
    
    switch ($protocolo) {
        case 'pollock3':
            if ($sexo === 'M') {
                // Homens: Peitoral, Abdomen, Coxa
                $soma_dobras = $dobras['peitoral'] + $dobras['abdomen'] + $dobras['coxa'];
                $densidade_corporal = 1.10938 - (0.0008267 * $soma_dobras) + (0.0000016 * pow($soma_dobras, 2)) - (0.0002574 * $idade);
            } else {
                // Mulheres: Triceps, Supra-iliaca, Coxa
                $soma_dobras = $dobras['triceps'] + $dobras['supra_iliaca'] + $dobras['coxa'];
                $densidade_corporal = 1.0994921 - (0.0009929 * $soma_dobras) + (0.0000023 * pow($soma_dobras, 2)) - (0.0001392 * $idade);
            }
            break;
            
        case 'pollock7':
            // 7 dobras: Peitoral, Axilar, Triceps, Subescapular, Abdomen, Supra-iliaca, Coxa
            $soma_dobras = array_sum($dobras);
            if ($sexo === 'M') {
                $densidade_corporal = 1.112 - (0.00043499 * $soma_dobras) + (0.00000055 * pow($soma_dobras, 2)) - (0.00028826 * $idade);
            } else {
                $densidade_corporal = 1.097 - (0.00046971 * $soma_dobras) + (0.00000056 * pow($soma_dobras, 2)) - (0.00012828 * $idade);
            }
            break;
            
        case 'guedes':
            if ($sexo === 'M') {
                // Homens: Peitoral, Abdomen, Coxa
                $soma_dobras = $dobras['peitoral'] + $dobras['abdomen'] + $dobras['coxa'];
                $percentual_gordura = 0.11077 * $soma_dobras - 0.00105 * pow($soma_dobras, 2) + 0.0000055 * pow($soma_dobras, 3) - 0.0011 * $idade + 0.4;
            } else {
                // Mulheres: Triceps, Supra-iliaca, Coxa
                $soma_dobras = $dobras['triceps'] + $dobras['supra_iliaca'] + $dobras['coxa'];
                $percentual_gordura = 0.11187 * $soma_dobras - 0.00154 * pow($soma_dobras, 2) + 0.0000071 * pow($soma_dobras, 3) - 0.0002 * $idade + 0.8;
            }
            break;
    }
    
    // Se não foi calculado diretamente (Pollock), usar fórmula de Siri
    if ($percentual_gordura == 0 && $densidade_corporal > 0) {
        $percentual_gordura = ((4.95 / $densidade_corporal) - 4.5) * 100;
    }
    
    return [
        'percentual_gordura' => round($percentual_gordura, 2),
        'densidade_corporal' => round($densidade_corporal, 4),
        'soma_dobras' => round($soma_dobras, 1)
    ];
}

// Função para classificar percentual de gordura
function classificarPercentualGordura($percentual, $sexo, $idade) {
    if ($sexo === 'M') {
        // Homens
        if ($idade <= 29) {
            if ($percentual < 11) return 'Muito Baixo';
            if ($percentual <= 13) return 'Baixo';
            if ($percentual <= 18) return 'Ótimo';
            if ($percentual <= 22) return 'Bom';
            if ($percentual <= 27) return 'Acima da Média';
            return 'Alto';
        } elseif ($idade <= 39) {
            if ($percentual < 11) return 'Muito Baixo';
            if ($percentual <= 14) return 'Baixo';
            if ($percentual <= 19) return 'Ótimo';
            if ($percentual <= 23) return 'Bom';
            if ($percentual <= 28) return 'Acima da Média';
            return 'Alto';
        } else {
            if ($percentual < 11) return 'Muito Baixo';
            if ($percentual <= 16) return 'Baixo';
            if ($percentual <= 21) return 'Ótimo';
            if ($percentual <= 25) return 'Bom';
            if ($percentual <= 30) return 'Acima da Média';
            return 'Alto';
        }
    } else {
        // Mulheres
        if ($idade <= 29) {
            if ($percentual < 16) return 'Muito Baixo';
            if ($percentual <= 19) return 'Baixo';
            if ($percentual <= 22) return 'Ótimo';
            if ($percentual <= 25) return 'Bom';
            if ($percentual <= 29) return 'Acima da Média';
            return 'Alto';
        } elseif ($idade <= 39) {
            if ($percentual < 16) return 'Muito Baixo';
            if ($percentual <= 20) return 'Baixo';
            if ($percentual <= 23) return 'Ótimo';
            if ($percentual <= 26) return 'Bom';
            if ($percentual <= 30) return 'Acima da Média';
            return 'Alto';
        } else {
            if ($percentual < 16) return 'Muito Baixo';
            if ($percentual <= 21) return 'Baixo';
            if ($percentual <= 25) return 'Ótimo';
            if ($percentual <= 28) return 'Bom';
            if ($percentual <= 32) return 'Acima da Média';
            return 'Alto';
        }
    }
}

// Verificar autenticação
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'professor')) {
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

$db = Database::getInstance();
$message = '';
$messageType = '';

// Verificar se a avaliação existe e pertence ao professor
$avaliacao_id = $_GET['id'] ?? 0;
$tipo = $_GET['tipo'] ?? '';

$avaliacao = $db->fetch("
    SELECT a.*, al.nome as aluno_nome, al.data_nascimento, al.sexo 
    FROM avaliacoes a 
    JOIN alunos al ON a.aluno_id = al.id 
    WHERE a.id = ? AND a.professor_id = ?
", [$avaliacao_id, $_SESSION['user_id']]);

if (!$avaliacao) {
    header('Location: avaliacoes.php');
    exit;
}

// Verificar se já existe dados salvos para este tipo de avaliação
$dados_existentes = null;
switch ($tipo) {
    case 'anamnese':
        $dados_existentes = $db->fetch("SELECT * FROM anamnese WHERE avaliacao_id = ?", [$avaliacao_id]);
        break;
    case 'composicao':
        $dados_existentes = $db->fetch("SELECT * FROM composicao_corporal WHERE avaliacao_id = ?", [$avaliacao_id]);
        break;
    case 'perimetria':
        $dados_existentes = $db->fetch("SELECT * FROM perimetria WHERE avaliacao_id = ?", [$avaliacao_id]);
        break;
    case 'postural':
        $dados_existentes = $db->fetch("SELECT * FROM avaliacao_postural WHERE avaliacao_id = ?", [$avaliacao_id]);
        break;
    case 'cardio':
        $dados_existentes = $db->fetch("SELECT * FROM testes_cardio WHERE avaliacao_id = ?", [$avaliacao_id]);
        break;
}

// Processar salvamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'salvar_anamnese':
                if ($dados_existentes) {
                    // Atualizar
                    $sql = "UPDATE anamnese SET historico_familiar=?, historico_patologico=?, medicamentos_uso=?, pratica_atividade=?, frequencia_atividade=?, lesoes_anteriores=?, cirurgias=?, nivel_stress=?, qualidade_sono=?, habitos_alimentares=?, objetivo_principal=?, nivel_atividade_atual=?, tempo_disponivel_treino=?, preferencias_exercicio=?, limitacoes_fisicas=?, updated_at=NOW() WHERE avaliacao_id=?";
                    $params = [
                        $_POST['historico_familiar'] ?: null,
                        $_POST['historico_patologico'] ?: null,
                        $_POST['medicamentos_uso'] ?: null,
                        $_POST['pratica_atividade'],
                        $_POST['frequencia_atividade'] ?: null,
                        $_POST['lesoes_anteriores'] ?: null,
                        $_POST['cirurgias'] ?: null,
                        $_POST['nivel_stress'],
                        $_POST['qualidade_sono'],
                        $_POST['habitos_alimentares'] ?: null,
                        $_POST['objetivo_principal'] ?: null,
                        $_POST['nivel_atividade_atual'],
                        $_POST['tempo_disponivel_treino'] ?: null,
                        $_POST['preferencias_exercicio'] ?: null,
                        $_POST['limitacoes_fisicas'] ?: null,
                        $avaliacao_id
                    ];
                } else {
                    // Inserir
                    $sql = "INSERT INTO anamnese (avaliacao_id, historico_familiar, historico_patologico, medicamentos_uso, pratica_atividade, frequencia_atividade, lesoes_anteriores, cirurgias, nivel_stress, qualidade_sono, habitos_alimentares, objetivo_principal, nivel_atividade_atual, tempo_disponivel_treino, preferencias_exercicio, limitacoes_fisicas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $params = [
                        $avaliacao_id,
                        $_POST['historico_familiar'] ?: null,
                        $_POST['historico_patologico'] ?: null,
                        $_POST['medicamentos_uso'] ?: null,
                        $_POST['pratica_atividade'],
                        $_POST['frequencia_atividade'] ?: null,
                        $_POST['lesoes_anteriores'] ?: null,
                        $_POST['cirurgias'] ?: null,
                        $_POST['nivel_stress'],
                        $_POST['qualidade_sono'],
                        $_POST['habitos_alimentares'] ?: null,
                        $_POST['objetivo_principal'] ?: null,
                        $_POST['nivel_atividade_atual'],
                        $_POST['tempo_disponivel_treino'] ?: null,
                        $_POST['preferencias_exercicio'] ?: null,
                        $_POST['limitacoes_fisicas'] ?: null
                    ];
                }
                
                $db->execute($sql, $params);
                
                // Atualizar status da avaliação
                $db->execute("UPDATE avaliacoes SET status = 'completa', updated_at = NOW() WHERE id = ?", [$avaliacao_id]);
                
                logActivity($_SESSION['user_id'], 'completar_anamnese', "Anamnese completada - Avaliação ID: $avaliacao_id");
                
                $message = 'Anamnese salva com sucesso!';
                $messageType = 'success';
                  // Recarregar dados
                $dados_existentes = $db->fetch("SELECT * FROM anamnese WHERE avaliacao_id = ?", [$avaliacao_id]);
                break;
                
            case 'salvar_composicao':
                // Calcular idade baseada na data de nascimento
                $idade = floor((time() - strtotime($avaliacao['data_nascimento'])) / (365.25 * 24 * 3600));
                $sexo = $avaliacao['sexo'];
                
                // Dados básicos
                $peso = floatval($_POST['peso']);
                $altura = floatval($_POST['altura']);
                $protocolo = $_POST['protocolo_usado'];
                
                // Dobras cutâneas (em mm)
                $dobras = [
                    'peitoral' => floatval($_POST['dobra_peitoral'] ?? 0),
                    'axilar' => floatval($_POST['dobra_axilar'] ?? 0),
                    'triceps' => floatval($_POST['dobra_triceps'] ?? 0),
                    'subescapular' => floatval($_POST['dobra_subescapular'] ?? 0),
                    'abdomen' => floatval($_POST['dobra_abdomen'] ?? 0),
                    'supra_iliaca' => floatval($_POST['dobra_supra_iliaca'] ?? 0),
                    'coxa' => floatval($_POST['dobra_coxa'] ?? 0)
                ];
                
                // Calcular percentual de gordura baseado no protocolo
                $resultado = calcularComposicaoCorporal($protocolo, $dobras, $idade, $sexo);
                
                // Calcular massa magra e massa gorda
                $massa_gorda = ($peso * $resultado['percentual_gordura']) / 100;
                $massa_magra = $peso - $massa_gorda;
                
                // Classificação do percentual de gordura
                $classificacao = classificarPercentualGordura($resultado['percentual_gordura'], $sexo, $idade);
                
                if ($dados_existentes) {
                    // Atualizar
                    $sql = "UPDATE composicao_corporal SET 
                            protocolo_usado = ?, peso = ?, altura = ?, percentual_gordura = ?, 
                            massa_magra = ?, massa_gorda = ?, densidade_corporal = ?, classificacao_gordura = ?,
                            dobra_peitoral = ?, dobra_axilar = ?, dobra_triceps = ?, dobra_subescapular = ?,
                            dobra_abdomen = ?, dobra_supra_iliaca = ?, dobra_coxa = ?, soma_dobras = ?, 
                            idade_protocolo = ?, updated_at = NOW()
                            WHERE avaliacao_id = ?";
                    $params = [
                        $protocolo, $peso, $altura, $resultado['percentual_gordura'],
                        $massa_magra, $massa_gorda, $resultado['densidade_corporal'], $classificacao,
                        $dobras['peitoral'], $dobras['axilar'], $dobras['triceps'], $dobras['subescapular'],
                        $dobras['abdomen'], $dobras['supra_iliaca'], $dobras['coxa'], $resultado['soma_dobras'],
                        $idade, $avaliacao_id
                    ];
                } else {
                    // Inserir
                    $sql = "INSERT INTO composicao_corporal (
                            avaliacao_id, protocolo_usado, peso, altura, percentual_gordura, 
                            massa_magra, massa_gorda, densidade_corporal, classificacao_gordura,
                            dobra_peitoral, dobra_axilar, dobra_triceps, dobra_subescapular,
                            dobra_abdomen, dobra_supra_iliaca, dobra_coxa, soma_dobras, idade_protocolo
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $params = [
                        $avaliacao_id, $protocolo, $peso, $altura, $resultado['percentual_gordura'],
                        $massa_magra, $massa_gorda, $resultado['densidade_corporal'], $classificacao,
                        $dobras['peitoral'], $dobras['axilar'], $dobras['triceps'], $dobras['subescapular'],
                        $dobras['abdomen'], $dobras['supra_iliaca'], $dobras['coxa'], $resultado['soma_dobras'], $idade
                    ];
                }
                
                $db->execute($sql, $params);
                
                // Atualizar status da avaliação
                $db->execute("UPDATE avaliacoes SET status = 'completa', updated_at = NOW() WHERE id = ?", [$avaliacao_id]);
                
                logActivity($_SESSION['user_id'], 'completar_composicao', "Composição corporal completada - Avaliação ID: $avaliacao_id");
                
                $message = 'Composição corporal salva com sucesso!';
                $messageType = 'success';
                  // Recarregar dados
                $dados_existentes = $db->fetch("SELECT * FROM composicao_corporal WHERE avaliacao_id = ?", [$avaliacao_id]);
                break;
                
            case 'salvar_perimetria':
                // Validação das medidas
                $medidas = [
                    'pescoco' => $_POST['pescoco'] ? floatval($_POST['pescoco']) : null,
                    'torax' => $_POST['torax'] ? floatval($_POST['torax']) : null,
                    'cintura' => $_POST['cintura'] ? floatval($_POST['cintura']) : null,
                    'abdomen' => $_POST['abdomen'] ? floatval($_POST['abdomen']) : null,
                    'quadril' => $_POST['quadril'] ? floatval($_POST['quadril']) : null,
                    'coxa_direita' => $_POST['coxa_direita'] ? floatval($_POST['coxa_direita']) : null,
                    'coxa_esquerda' => $_POST['coxa_esquerda'] ? floatval($_POST['coxa_esquerda']) : null,
                    'panturrilha_direita' => $_POST['panturrilha_direita'] ? floatval($_POST['panturrilha_direita']) : null,
                    'panturrilha_esquerda' => $_POST['panturrilha_esquerda'] ? floatval($_POST['panturrilha_esquerda']) : null,
                    'braco_direito' => $_POST['braco_direito'] ? floatval($_POST['braco_direito']) : null,
                    'braco_esquerdo' => $_POST['braco_esquerdo'] ? floatval($_POST['braco_esquerdo']) : null,
                    'antebraco_direito' => $_POST['antebraco_direito'] ? floatval($_POST['antebraco_direito']) : null,
                    'antebraco_esquerdo' => $_POST['antebraco_esquerdo'] ? floatval($_POST['antebraco_esquerdo']) : null,
                    'punho' => $_POST['punho'] ? floatval($_POST['punho']) : null
                ];
                
                if ($dados_existentes) {
                    // Atualizar
                    $sql = "UPDATE perimetria SET 
                            pescoco = ?, torax = ?, cintura = ?, abdomen = ?, quadril = ?,
                            coxa_direita = ?, coxa_esquerda = ?, panturrilha_direita = ?, panturrilha_esquerda = ?,
                            braco_direito = ?, braco_esquerdo = ?, antebraco_direito = ?, antebraco_esquerdo = ?,
                            punho = ?, updated_at = NOW()
                            WHERE avaliacao_id = ?";
                    $params = array_values($medidas);
                    $params[] = $avaliacao_id;
                } else {
                    // Inserir
                    $sql = "INSERT INTO perimetria (
                            avaliacao_id, pescoco, torax, cintura, abdomen, quadril,
                            coxa_direita, coxa_esquerda, panturrilha_direita, panturrilha_esquerda,
                            braco_direito, braco_esquerdo, antebraco_direito, antebraco_esquerdo, punho
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $params = [$avaliacao_id];
                    $params = array_merge($params, array_values($medidas));
                }
                
                $db->execute($sql, $params);
                
                // Atualizar status da avaliação
                $db->execute("UPDATE avaliacoes SET status = 'completa', updated_at = NOW() WHERE id = ?", [$avaliacao_id]);
                
                logActivity($_SESSION['user_id'], 'completar_perimetria', "Perimetria completada - Avaliação ID: $avaliacao_id");
                
                $message = 'Perimetria salva com sucesso!';
                $messageType = 'success';
                  // Recarregar dados
                $dados_existentes = $db->fetch("SELECT * FROM perimetria WHERE avaliacao_id = ?", [$avaliacao_id]);
                break;
                
            case 'salvar_postural':
                $dados_postural = [
                    'cabeca_posicao' => $_POST['cabeca_posicao'] ?? 'normal',
                    'ombros_posicao' => $_POST['ombros_posicao'] ?? 'normais',
                    'coluna_cervical' => $_POST['coluna_cervical'] ?? 'normal',
                    'coluna_toracica' => $_POST['coluna_toracica'] ?? 'normal',
                    'coluna_lombar' => $_POST['coluna_lombar'] ?? 'normal',
                    'pelve_posicao' => $_POST['pelve_posicao'] ?? 'normal',
                    'joelhos_posicao' => $_POST['joelhos_posicao'] ?? 'normais',
                    'pes_posicao' => $_POST['pes_posicao'] ?? 'normais',
                    'observacoes_posturais' => $_POST['observacoes_posturais'] ?: null,
                    'recomendacoes' => $_POST['recomendacoes'] ?: null
                ];
                
                if ($dados_existentes) {
                    // Atualizar
                    $sql = "UPDATE avaliacao_postural SET 
                            cabeca_posicao = ?, ombros_posicao = ?, coluna_cervical = ?, coluna_toracica = ?,
                            coluna_lombar = ?, pelve_posicao = ?, joelhos_posicao = ?, pes_posicao = ?,
                            observacoes_posturais = ?, recomendacoes = ?, updated_at = NOW()
                            WHERE avaliacao_id = ?";
                    $params = array_values($dados_postural);
                    $params[] = $avaliacao_id;
                } else {
                    // Inserir
                    $sql = "INSERT INTO avaliacao_postural (
                            avaliacao_id, cabeca_posicao, ombros_posicao, coluna_cervical, coluna_toracica,
                            coluna_lombar, pelve_posicao, joelhos_posicao, pes_posicao,
                            observacoes_posturais, recomendacoes
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $params = [$avaliacao_id];
                    $params = array_merge($params, array_values($dados_postural));
                }
                
                $db->execute($sql, $params);
                
                // Atualizar status da avaliação
                $db->execute("UPDATE avaliacoes SET status = 'completa', updated_at = NOW() WHERE id = ?", [$avaliacao_id]);
                
                logActivity($_SESSION['user_id'], 'completar_postural', "Avaliação postural completada - Avaliação ID: $avaliacao_id");
                
                $message = 'Avaliação postural salva com sucesso!';
                $messageType = 'success';
                
                // Recarregar dados
                $dados_existentes = $db->fetch("SELECT * FROM avaliacao_postural WHERE avaliacao_id = ?", [$avaliacao_id]);
                break;
                
            case 'salvar_vo2max':
                // Função para calcular VO2Max baseado no tipo de teste
                function calcularVO2Max($tipo_teste, $dados, $peso, $idade, $sexo) {
                    switch ($tipo_teste) {
                        case 'cooper':
                            // Fórmula de Cooper: VO2max = (distância em metros - 504.9) / 44.73
                            $distancia = floatval($dados['distancia_cooper']);
                            return ($distancia - 504.9) / 44.73;
                            
                        case 'step':
                            // Fórmula do Step Test: VO2max = 111.33 - (0.42 × FC1min) - (0.21 × FC2min) - (0.06 × FC3min)
                            $fc1 = intval($dados['frequencia_1min']);
                            $fc2 = intval($dados['frequencia_2min']); 
                            $fc3 = intval($dados['frequencia_3min']);
                            return 111.33 - (0.42 * $fc1) - (0.21 * $fc2) - (0.06 * $fc3);
                            
                        case 'caminhada':
                            // Fórmula da Caminhada (1 milha): VO2max = 132.853 - (0.0769 × peso) - (0.3877 × idade) + (6.315 × sexo) - (3.2649 × tempo) - (0.1565 × FC)
                            $tempo_seg = strtotime($dados['tempo_caminhada']) - strtotime('00:00:00');
                            $tempo_min = $tempo_seg / 60;
                            $fc_final = intval($dados['fc_final_caminhada']);
                            $sexo_num = ($sexo === 'M') ? 1 : 0;
                            return 132.853 - (0.0769 * $peso) - (0.3877 * $idade) + (6.315 * $sexo_num) - (3.2649 * $tempo_min) - (0.1565 * $fc_final);
                            
                        default:
                            return null;
                    }
                }
                
                function classificarVO2Max($vo2max, $idade, $sexo) {
                    if ($sexo === 'M') {
                        // Homens
                        if ($idade <= 29) {
                            if ($vo2max >= 55) return 'Excelente';
                            if ($vo2max >= 50) return 'Muito Bom';
                            if ($vo2max >= 43) return 'Bom';
                            if ($vo2max >= 37) return 'Regular';
                            return 'Fraco';
                        } elseif ($idade <= 39) {
                            if ($vo2max >= 52) return 'Excelente';
                            if ($vo2max >= 46) return 'Muito Bom';
                            if ($vo2max >= 40) return 'Bom';
                            if ($vo2max >= 35) return 'Regular';
                            return 'Fraco';
                        } else {
                            if ($vo2max >= 48) return 'Excelente';
                            if ($vo2max >= 42) return 'Muito Bom';
                            if ($vo2max >= 36) return 'Bom';
                            if ($vo2max >= 32) return 'Regular';
                            return 'Fraco';
                        }
                    } else {
                        // Mulheres
                        if ($idade <= 29) {
                            if ($vo2max >= 49) return 'Excelente';
                            if ($vo2max >= 43) return 'Muito Bom';
                            if ($vo2max >= 36) return 'Bom';
                            if ($vo2max >= 30) return 'Regular';
                            return 'Fraco';
                        } elseif ($idade <= 39) {
                            if ($vo2max >= 45) return 'Excelente';
                            if ($vo2max >= 39) return 'Muito Bom';
                            if ($vo2max >= 33) return 'Bom';
                            if ($vo2max >= 27) return 'Regular';
                            return 'Fraco';
                        } else {
                            if ($vo2max >= 42) return 'Excelente';
                            if ($vo2max >= 36) return 'Muito Bom';
                            if ($vo2max >= 30) return 'Bom';
                            if ($vo2max >= 24) return 'Regular';
                            return 'Fraco';
                        }
                    }
                }
                
                $tipo_teste = $_POST['tipo_teste'];
                $peso = floatval($_POST['peso_corporal'] ?: $avaliacao['peso'] ?: 70);
                $idade = intval($_POST['idade'] ?: (date('Y') - date('Y', strtotime($avaliacao['data_nascimento']))));
                $sexo = $_POST['sexo'] ?: $avaliacao['sexo'];
                
                $dados_teste = [];
                switch ($tipo_teste) {
                    case 'cooper':
                        $dados_teste['distancia_cooper'] = $_POST['distancia_cooper'];
                        break;
                    case 'step':
                        $dados_teste['frequencia_repouso'] = $_POST['frequencia_repouso'];
                        $dados_teste['frequencia_1min'] = $_POST['frequencia_1min'];
                        $dados_teste['frequencia_2min'] = $_POST['frequencia_2min'];
                        $dados_teste['frequencia_3min'] = $_POST['frequencia_3min'];
                        break;
                    case 'caminhada':
                        $dados_teste['tempo_caminhada'] = $_POST['tempo_caminhada'];
                        $dados_teste['fc_final_caminhada'] = $_POST['fc_final_caminhada'];
                        break;
                }
                
                $vo2max_calc = calcularVO2Max($tipo_teste, $dados_teste, $peso, $idade, $sexo);
                $classificacao = $vo2max_calc ? classificarVO2Max($vo2max_calc, $idade, $sexo) : null;
                
                // Primeiro, criar a tabela se não existir
                $db->execute("
                    CREATE TABLE IF NOT EXISTS vo2max (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        avaliacao_id INT NOT NULL,
                        tipo_teste ENUM('cooper', 'step', 'caminhada', 'esteira', 'bike') NOT NULL,
                        distancia_cooper DECIMAL(8,2) NULL,
                        frequencia_repouso INT NULL,
                        frequencia_1min INT NULL,
                        frequencia_2min INT NULL,
                        frequencia_3min INT NULL,
                        tempo_caminhada TIME NULL,
                        fc_final_caminhada INT NULL,
                        peso_corporal DECIMAL(5,2) NULL,
                        idade INT NULL,
                        sexo ENUM('M', 'F') NULL,
                        vo2max_calculado DECIMAL(6,2) NULL,
                        classificacao VARCHAR(50) NULL,
                        condicoes_teste TEXT NULL,
                        medicamentos_uso TEXT NULL,
                        observacoes TEXT NULL,
                        recomendacoes TEXT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (avaliacao_id) REFERENCES avaliacoes(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                // Verificar se já existe registro
                $dados_existentes_vo2 = $db->fetch("SELECT * FROM vo2max WHERE avaliacao_id = ?", [$avaliacao_id]);
                
                if ($dados_existentes_vo2) {
                    // Atualizar
                    $sql = "UPDATE vo2max SET 
                            tipo_teste = ?, distancia_cooper = ?, frequencia_repouso = ?, frequencia_1min = ?,
                            frequencia_2min = ?, frequencia_3min = ?, tempo_caminhada = ?, fc_final_caminhada = ?,
                            peso_corporal = ?, idade = ?, sexo = ?, vo2max_calculado = ?, classificacao = ?,
                            condicoes_teste = ?, medicamentos_uso = ?, observacoes = ?, recomendacoes = ?,
                            updated_at = NOW()
                            WHERE avaliacao_id = ?";
                    $params = [
                        $tipo_teste,
                        $dados_teste['distancia_cooper'] ?? null,
                        $dados_teste['frequencia_repouso'] ?? null,
                        $dados_teste['frequencia_1min'] ?? null,
                        $dados_teste['frequencia_2min'] ?? null,
                        $dados_teste['frequencia_3min'] ?? null,
                        $dados_teste['tempo_caminhada'] ?? null,
                        $dados_teste['fc_final_caminhada'] ?? null,
                        $peso,
                        $idade,
                        $sexo,
                        $vo2max_calc,
                        $classificacao,
                        $_POST['condicoes_teste'] ?: null,
                        $_POST['medicamentos_uso'] ?: null,
                        $_POST['observacoes'] ?: null,
                        $_POST['recomendacoes'] ?: null,
                        $avaliacao_id
                    ];
                } else {
                    // Inserir
                    $sql = "INSERT INTO vo2max (
                            avaliacao_id, tipo_teste, distancia_cooper, frequencia_repouso, frequencia_1min,
                            frequencia_2min, frequencia_3min, tempo_caminhada, fc_final_caminhada,
                            peso_corporal, idade, sexo, vo2max_calculado, classificacao,
                            condicoes_teste, medicamentos_uso, observacoes, recomendacoes
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $params = [
                        $avaliacao_id,
                        $tipo_teste,
                        $dados_teste['distancia_cooper'] ?? null,
                        $dados_teste['frequencia_repouso'] ?? null,
                        $dados_teste['frequencia_1min'] ?? null,
                        $dados_teste['frequencia_2min'] ?? null,
                        $dados_teste['frequencia_3min'] ?? null,
                        $dados_teste['tempo_caminhada'] ?? null,
                        $dados_teste['fc_final_caminhada'] ?? null,
                        $peso,
                        $idade,
                        $sexo,
                        $vo2max_calc,
                        $classificacao,
                        $_POST['condicoes_teste'] ?: null,
                        $_POST['medicamentos_uso'] ?: null,
                        $_POST['observacoes'] ?: null,
                        $_POST['recomendacoes'] ?: null
                    ];
                }
                
                $db->execute($sql, $params);
                
                // Atualizar status da avaliação
                $db->execute("UPDATE avaliacoes SET status = 'completa', updated_at = NOW() WHERE id = ?", [$avaliacao_id]);
                
                logActivity($_SESSION['user_id'], 'completar_vo2max', "Teste VO2Max completado - Avaliação ID: $avaliacao_id");
                
                $message = 'Teste VO2Max salvo com sucesso!';
                $messageType = 'success';
                
                // Recarregar dados
                $dados_existentes = $db->fetch("SELECT * FROM vo2max WHERE avaliacao_id = ?", [$avaliacao_id]);
                break;
        }
    } catch (Exception $e) {
        $message = 'Erro ao salvar: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Títulos e ícones por tipo
$tipos_info = [
    'anamnese' => ['titulo' => 'Anamnese', 'icone' => '📝', 'descricao' => 'Questionário de saúde e histórico'],
    'composicao' => ['titulo' => 'Composição Corporal', 'icone' => '📏', 'descricao' => 'Análise de percentual de gordura'],
    'perimetria' => ['titulo' => 'Perimetria', 'icone' => '📐', 'descricao' => 'Medidas corporais'],
    'postural' => ['titulo' => 'Avaliação Postural', 'icone' => '📸', 'descricao' => 'Análise postural'],
    'cardio' => ['titulo' => 'Teste Cardiorrespiratório', 'icone' => '❤️', 'descricao' => 'Capacidade cardiovascular']
];

$info_atual = $tipos_info[$tipo] ?? ['titulo' => 'Avaliação', 'icone' => '📋', 'descricao' => ''];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $info_atual['titulo']; ?> - SMARTBIOFIT</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/styles.css">    <style>
        .patient-info {
            background: linear-gradient(135deg, #2F80ED 0%, #1976D2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .patient-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .patient-detail {
            background: rgba(255,255,255,0.1);
            padding: 0.75rem;
            border-radius: 5px;
        }
        
        .patient-detail strong {
            display: block;
            font-size: 0.8rem;
            opacity: 0.8;
            margin-bottom: 0.25rem;
        }
        
        .form-section {
            background: white;
            margin-bottom: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .form-section-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
            font-weight: bold;
            color: var(--azul-cobalto);
        }
        
        .form-section-body {
            padding: 1.5rem;
        }
        
        .form-grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }
        
        .form-grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .radio-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .radio-option input[type="radio"] {
            margin: 0;
        }
        
        .form-actions {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 1rem;
            border-top: 1px solid #e9ecef;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }
        
        .form-control-readonly {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            color: #6c757d;
        }
        
        .dobra-field {
            transition: all 0.3s ease;
        }
        
        .dobra-field.hidden {
            display: none;
        }
        
        .dobra-field.required label::after {
            content: ' *';
            color: #EB5757;
        }
        
        .protocol-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .protocol-info.pollock3 {
            background: #f3e5f5;
            border-color: #9c27b0;
        }
        
        .protocol-info.pollock7 {
            background: #e8f5e8;
            border-color: #4caf50;
        }
        
        .protocol-info.guedes {
            background: #fff3e0;
            border-color: #ff9800;
        }
        
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .result-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.2s;
        }
        
        .result-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .result-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        
        .result-value {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--azul-cobalto);
            margin-bottom: 0.5rem;
        }
        
        .result-value.primary {
            font-size: 2.2rem;
            color: var(--verde-esmeralda);
        }
        
        .result-classification {
            font-size: 0.9rem;
            font-weight: 500;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            display: inline-block;
        }
          .result-classification.muito-baixo { background: #ffebee; color: #c62828; }
        .result-classification.baixo { background: #fff3e0; color: #f57c00; }
        .result-classification.otimo { background: #e8f5e8; color: #2e7d32; }
        .result-classification.bom { background: #e3f2fd; color: #1976d2; }
        .result-classification.acima-da-media { background: #fff8e1; color: #f9a825; }
        .result-classification.alto { background: #fce4ec; color: #ad1457; }
        .result-classification.excelente { background: #e8f5e8; color: #2e7d32; }
        .result-classification.muito-bom { background: #e3f2fd; color: #1976d2; }
        .result-classification.regular { background: #fff8e1; color: #f9a825; }
        .result-classification.fraco { background: #ffebee; color: #c62828; }
        
        /* Estilos específicos para avaliação postural */
        .postural-guide {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .guide-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .guide-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
        }
        
        .guide-card strong {
            color: var(--azul-cobalto);
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .guide-card ul {
            margin: 0;
            padding-left: 1.2rem;
        }
        
        .guide-card li {
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }
        
        /* Estilos para testes cardio */
        .teste-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .teste-info.cooper {
            background: #f3e5f5;
            border-color: #9c27b0;
        }
        
        .teste-info.step {
            background: #e8f5e8;
            border-color: #4caf50;
        }
        
        .teste-info.caminhada {
            background: #fff3e0;
            border-color: #ff9800;
        }
        
        .teste-section {
            transition: all 0.3s ease;
        }
        
        .teste-instructions {
            background: #f8f9fa;
            border-left: 4px solid var(--azul-cobalto);
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .teste-instructions h5 {
            color: var(--azul-cobalto);
            margin-bottom: 0.5rem;
        }
        
        .teste-instructions ol {
            margin: 0;
            padding-left: 1.2rem;
        }
          .teste-instructions li {
            margin-bottom: 0.5rem;
        }
        
        /* Estilos para destacar campos de postural */
        select.normal {
            border-color: var(--verde-saude) !important;
            background-color: #f0fff4;
        }
        
        select.alterado {
            border-color: var(--vermelho-suave) !important;
            background-color: #fff5f5;
        }
        
        /* Responsividade adicional */
        
        @media (max-width: 768px) {
            .patient-details {
                grid-template-columns: 1fr;
            }
            
            .form-grid-2,
            .form-grid-3 {
                grid-template-columns: 1fr;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="<?php echo APP_URL; ?>">Dashboard</a> > 
            <a href="avaliacoes.php">Avaliações</a> > 
            <span><?php echo $info_atual['titulo']; ?></span>
        </nav>
        
        <!-- Informações do Paciente -->
        <div class="patient-info">
            <h1><?php echo $info_atual['icone']; ?> <?php echo $info_atual['titulo']; ?></h1>
            <p><?php echo $info_atual['descricao']; ?></p>
            
            <div class="patient-details">
                <div class="patient-detail">
                    <strong>Aluno</strong>
                    <?php echo htmlspecialchars($avaliacao['aluno_nome']); ?>
                </div>
                <div class="patient-detail">
                    <strong>Data da Avaliação</strong>
                    <?php echo date('d/m/Y', strtotime($avaliacao['data_avaliacao'])); ?>
                </div>
                <div class="patient-detail">
                    <strong>Idade</strong>
                    <?php 
                    if ($avaliacao['data_nascimento']) {
                        $idade = date_diff(date_create($avaliacao['data_nascimento']), date_create('now'))->y;
                        echo $idade . ' anos';
                    } else {
                        echo 'Não informado';
                    }
                    ?>
                </div>
                <div class="patient-detail">
                    <strong>Sexo</strong>
                    <?php echo $avaliacao['sexo'] === 'M' ? 'Masculino' : 'Feminino'; ?>
                </div>
                <div class="patient-detail">
                    <strong>Status</strong>
                    <?php echo ucfirst(str_replace('_', ' ', $avaliacao['status'])); ?>
                </div>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" id="alert-message">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <!-- Formulário específico por tipo -->
        <?php if ($tipo === 'anamnese'): ?>
        
        <form method="POST" id="anamneseForm">
            <input type="hidden" name="action" value="salvar_anamnese">
            
            <!-- Histórico de Saúde -->
            <div class="form-section">
                <div class="form-section-header">
                    🏥 Histórico de Saúde
                </div>
                <div class="form-section-body">
                    <div class="form-grid-2">                        <div class="form-group">
                            <label for="historico_familiar">Histórico Familiar</label>
                            <textarea id="historico_familiar" name="historico_familiar" rows="4" class="text-gray-900" 
                                      placeholder="Histórico de doenças na família..."><?php echo $dados_existentes['historico_familiar'] ?? ''; ?></textarea>
                        </div>
                          <div class="form-group">
                            <label for="historico_patologico">Histórico Patológico</label>
                            <textarea id="historico_patologico" name="historico_patologico" rows="4" class="text-gray-900" 
                                      placeholder="Doenças, cirurgias, lesões anteriores..."><?php echo $dados_existentes['historico_patologico'] ?? ''; ?></textarea>
                        </div>
                    </div>
                      <div class="form-group">
                        <label for="medicamentos_uso">Medicamentos em Uso</label>
                        <textarea id="medicamentos_uso" name="medicamentos_uso" rows="3" class="text-gray-900" 
                                  placeholder="Liste todos os medicamentos que utiliza atualmente..."><?php echo $dados_existentes['medicamentos_uso'] ?? ''; ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Atividade Física Atual -->
            <div class="form-section">
                <div class="form-section-header">
                    🏃‍♂️ Atividade Física Atual
                </div>
                <div class="form-section-body">
                    <div class="form-group">
                        <label>Pratica atividade física atualmente?</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="pratica_sim" name="pratica_atividade" value="sim" 
                                       <?php echo ($dados_existentes['pratica_atividade'] ?? '') === 'sim' ? 'checked' : ''; ?>>
                                <label for="pratica_sim">Sim, regularmente</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="pratica_eventualmente" name="pratica_atividade" value="eventualmente" 
                                       <?php echo ($dados_existentes['pratica_atividade'] ?? '') === 'eventualmente' ? 'checked' : ''; ?>>
                                <label for="pratica_eventualmente">Eventualmente</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="pratica_nao" name="pratica_atividade" value="nao" 
                                       <?php echo ($dados_existentes['pratica_atividade'] ?? 'nao') === 'nao' ? 'checked' : ''; ?>>
                                <label for="pratica_nao">Não pratico</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-grid-2">                        <div class="form-group">
                            <label for="frequencia_atividade">Frequência Semanal</label>
                            <input type="text" id="frequencia_atividade" name="frequencia_atividade" class="text-gray-900" 
                                   placeholder="Ex: 3x por semana, 30min por dia..."
                                   value="<?php echo htmlspecialchars($dados_existentes['frequencia_atividade'] ?? ''); ?>">
                        </div>
                          <div class="form-group">
                            <label for="nivel_atividade_atual">Nível de Atividade</label>
                            <select id="nivel_atividade_atual" name="nivel_atividade_atual" class="text-gray-900" required>
                                <option value="sedentario" <?php echo ($dados_existentes['nivel_atividade_atual'] ?? 'sedentario') === 'sedentario' ? 'selected' : ''; ?>>Sedentário</option>
                                <option value="pouco_ativo" <?php echo ($dados_existentes['nivel_atividade_atual'] ?? '') === 'pouco_ativo' ? 'selected' : ''; ?>>Pouco Ativo</option>
                                <option value="ativo" <?php echo ($dados_existentes['nivel_atividade_atual'] ?? '') === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                <option value="muito_ativo" <?php echo ($dados_existentes['nivel_atividade_atual'] ?? '') === 'muito_ativo' ? 'selected' : ''; ?>>Muito Ativo</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Estilo de Vida -->
            <div class="form-section">
                <div class="form-section-header">
                    🌱 Estilo de Vida
                </div>
                <div class="form-section-body">
                    <div class="form-grid-3">                        <div class="form-group">
                            <label for="nivel_stress">Nível de Stress</label>
                            <select id="nivel_stress" name="nivel_stress" class="text-gray-900" required>
                                <option value="baixo" <?php echo ($dados_existentes['nivel_stress'] ?? 'moderado') === 'baixo' ? 'selected' : ''; ?>>Baixo</option>
                                <option value="moderado" <?php echo ($dados_existentes['nivel_stress'] ?? 'moderado') === 'moderado' ? 'selected' : ''; ?>>Moderado</option>
                                <option value="alto" <?php echo ($dados_existentes['nivel_stress'] ?? '') === 'alto' ? 'selected' : ''; ?>>Alto</option>
                            </select>
                        </div>
                          <div class="form-group">
                            <label for="qualidade_sono">Qualidade do Sono</label>
                            <select id="qualidade_sono" name="qualidade_sono" class="text-gray-900" required>
                                <option value="excelente" <?php echo ($dados_existentes['qualidade_sono'] ?? '') === 'excelente' ? 'selected' : ''; ?>>Excelente</option>
                                <option value="boa" <?php echo ($dados_existentes['qualidade_sono'] ?? '') === 'boa' ? 'selected' : ''; ?>>Boa</option>
                                <option value="regular" <?php echo ($dados_existentes['qualidade_sono'] ?? 'regular') === 'regular' ? 'selected' : ''; ?>>Regular</option>
                                <option value="ruim" <?php echo ($dados_existentes['qualidade_sono'] ?? '') === 'ruim' ? 'selected' : ''; ?>>Ruim</option>
                            </select>
                        </div>
                          <div class="form-group">
                            <label for="tempo_disponivel_treino">Tempo Disponível para Treino</label>
                            <input type="text" id="tempo_disponivel_treino" name="tempo_disponivel_treino" class="text-gray-900" 
                                   placeholder="Ex: 1h por dia, 3x semana..."
                                   value="<?php echo htmlspecialchars($dados_existentes['tempo_disponivel_treino'] ?? ''); ?>">
                        </div>
                    </div>
                      <div class="form-group">
                        <label for="habitos_alimentares">Hábitos Alimentares</label>
                        <textarea id="habitos_alimentares" name="habitos_alimentares" rows="3" class="text-gray-900" 
                                  placeholder="Descreva seus hábitos alimentares atuais..."><?php echo $dados_existentes['habitos_alimentares'] ?? ''; ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Objetivos e Limitações -->
            <div class="form-section">
                <div class="form-section-header">
                    🎯 Objetivos e Limitações
                </div>
                <div class="form-section-body">                    <div class="form-group">
                        <label for="objetivo_principal">Objetivo Principal</label>
                        <textarea id="objetivo_principal" name="objetivo_principal" rows="3" class="text-gray-900" 
                                  placeholder="Qual seu principal objetivo com o treinamento?"><?php echo $dados_existentes['objetivo_principal'] ?? ''; ?></textarea>
                    </div>
                      <div class="form-group">
                        <label for="preferencias_exercicio">Preferências de Exercício</label>
                        <textarea id="preferencias_exercicio" name="preferencias_exercicio" rows="3" class="text-gray-900" 
                                  placeholder="Que tipos de exercício você gosta ou tem preferência?"><?php echo $dados_existentes['preferencias_exercicio'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-grid-2">                        <div class="form-group">
                            <label for="lesoes_anteriores">Lesões Anteriores</label>
                            <textarea id="lesoes_anteriores" name="lesoes_anteriores" rows="4" class="text-gray-900" 
                                      placeholder="Descreva lesões anteriores relevantes..."><?php echo $dados_existentes['lesoes_anteriores'] ?? ''; ?></textarea>
                        </div>
                          <div class="form-group">
                            <label for="limitacoes_fisicas">Limitações Físicas Atuais</label>
                            <textarea id="limitacoes_fisicas" name="limitacoes_fisicas" rows="4" class="text-gray-900" 
                                      placeholder="Limitações ou restrições médicas atuais..."><?php echo $dados_existentes['limitacoes_fisicas'] ?? ''; ?></textarea>
                        </div>
                    </div>
                      <div class="form-group">
                        <label for="cirurgias">Cirurgias Realizadas</label>
                        <textarea id="cirurgias" name="cirurgias" rows="3" class="text-gray-900" 
                                  placeholder="Cirurgias realizadas e datas aproximadas..."><?php echo $dados_existentes['cirurgias'] ?? ''; ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">💾 Salvar Anamnese</button>
                <a href="avaliacoes.php" class="btn btn-secondary">← Voltar</a>
            </div>        </form>
        
        <?php elseif ($tipo === 'composicao'): ?>
        
        <form method="POST" id="composicaoForm">
            <input type="hidden" name="action" value="salvar_composicao">
            
            <!-- Dados Básicos -->
            <div class="form-section">
                <div class="form-section-header">
                    ⚖️ Dados Básicos
                </div>
                <div class="form-section-body">
                    <div class="form-grid-3">                        <div class="form-group">
                            <label for="peso">Peso (kg) *</label>
                            <input type="number" id="peso" name="peso" step="0.1" min="20" max="300" class="text-gray-900" required
                                   value="<?php echo $dados_existentes['peso'] ?? ''; ?>"
                                   placeholder="Ex: 70.5">
                        </div>
                          <div class="form-group">
                            <label for="altura">Altura (m) *</label>
                            <input type="number" id="altura" name="altura" step="0.01" min="1.0" max="2.5" class="text-gray-900" required
                                   value="<?php echo $dados_existentes['altura'] ?? ''; ?>"
                                   placeholder="Ex: 1.75">
                        </div>
                        
                        <div class="form-group">
                            <label for="imc_calc">IMC</label>
                            <input type="text" id="imc_calc" readonly class="form-control-readonly"
                                   placeholder="Calculado automaticamente">
                        </div>
                    </div>
                      <div class="form-group">
                        <label for="protocolo_usado">Protocolo de Cálculo *</label>
                        <select id="protocolo_usado" name="protocolo_usado" class="text-gray-900" required>
                            <option value="">Selecione o protocolo...</option>
                            <option value="pollock3" <?php echo ($dados_existentes['protocolo_usado'] ?? '') === 'pollock3' ? 'selected' : ''; ?>>
                                Pollock 3 Dobras (Peitoral, Abdomen, Coxa)
                            </option>
                            <option value="pollock7" <?php echo ($dados_existentes['protocolo_usado'] ?? '') === 'pollock7' ? 'selected' : ''; ?>>
                                Pollock 7 Dobras (Completo)
                            </option>
                            <option value="guedes" <?php echo ($dados_existentes['protocolo_usado'] ?? '') === 'guedes' ? 'selected' : ''; ?>>
                                Guedes & Guedes
                            </option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Dobras Cutâneas -->
            <div class="form-section">
                <div class="form-section-header">
                    📏 Dobras Cutâneas (mm)
                </div>
                <div class="form-section-body">
                    <div class="protocol-info" id="protocol-info">
                        <p>💡 Selecione um protocolo acima para ver quais dobras são necessárias.</p>
                    </div>
                    
                    <div class="form-grid-3" id="dobras-container">                        <div class="form-group dobra-field" data-protocols="pollock3,pollock7" data-required="pollock3">
                            <label for="dobra_peitoral">Peitoral</label>
                            <input type="number" id="dobra_peitoral" name="dobra_peitoral" step="0.1" min="0" max="50" class="text-gray-900"
                                   value="<?php echo $dados_existentes['dobra_peitoral'] ?? ''; ?>"
                                   placeholder="mm">
                        </div>
                          <div class="form-group dobra-field" data-protocols="pollock7">
                            <label for="dobra_axilar">Axilar</label>
                            <input type="number" id="dobra_axilar" name="dobra_axilar" step="0.1" min="0" max="50" class="text-gray-900"
                                   value="<?php echo $dados_existentes['dobra_axilar'] ?? ''; ?>"
                                   placeholder="mm">
                        </div>
                          <div class="form-group dobra-field" data-protocols="pollock7,guedes">
                            <label for="dobra_triceps">Tríceps</label>
                            <input type="number" id="dobra_triceps" name="dobra_triceps" step="0.1" min="0" max="50" class="text-gray-900"
                                   value="<?php echo $dados_existentes['dobra_triceps'] ?? ''; ?>"
                                   placeholder="mm">
                        </div>
                          <div class="form-group dobra-field" data-protocols="pollock7">
                            <label for="dobra_subescapular">Subescapular</label>
                            <input type="number" id="dobra_subescapular" name="dobra_subescapular" step="0.1" min="0" max="50" class="text-gray-900"
                                   value="<?php echo $dados_existentes['dobra_subescapular'] ?? ''; ?>"
                                   placeholder="mm">
                        </div>
                          <div class="form-group dobra-field" data-protocols="pollock3,pollock7" data-required="pollock3">
                            <label for="dobra_abdomen">Abdomen</label>
                            <input type="number" id="dobra_abdomen" name="dobra_abdomen" step="0.1" min="0" max="50" class="text-gray-900"
                                   value="<?php echo $dados_existentes['dobra_abdomen'] ?? ''; ?>"
                                   placeholder="mm">
                        </div>
                          <div class="form-group dobra-field" data-protocols="pollock7,guedes" data-required="guedes">
                            <label for="dobra_supra_iliaca">Supra-ilíaca</label>
                            <input type="number" id="dobra_supra_iliaca" name="dobra_supra_iliaca" step="0.1" min="0" max="50" class="text-gray-900"
                                   value="<?php echo $dados_existentes['dobra_supra_iliaca'] ?? ''; ?>"
                                   placeholder="mm">
                        </div>
                          <div class="form-group dobra-field" data-protocols="pollock3,pollock7,guedes" data-required="pollock3,guedes">
                            <label for="dobra_coxa">Coxa</label>
                            <input type="number" id="dobra_coxa" name="dobra_coxa" step="0.1" min="0" max="50" class="text-gray-900"
                                   value="<?php echo $dados_existentes['dobra_coxa'] ?? ''; ?>"
                                   placeholder="mm">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Resultados Calculados -->
            <?php if ($dados_existentes): ?>
            <div class="form-section">
                <div class="form-section-header">
                    📊 Resultados
                </div>
                <div class="form-section-body">
                    <div class="results-grid">
                        <div class="result-card">
                            <div class="result-label">Percentual de Gordura</div>
                            <div class="result-value primary"><?php echo number_format($dados_existentes['percentual_gordura'], 2); ?>%</div>
                            <div class="result-classification <?php echo strtolower(str_replace(' ', '-', $dados_existentes['classificacao_gordura'])); ?>">
                                <?php echo $dados_existentes['classificacao_gordura']; ?>
                            </div>
                        </div>
                        
                        <div class="result-card">
                            <div class="result-label">Massa Magra</div>
                            <div class="result-value"><?php echo number_format($dados_existentes['massa_magra'], 2); ?> kg</div>
                        </div>
                        
                        <div class="result-card">
                            <div class="result-label">Massa Gorda</div>
                            <div class="result-value"><?php echo number_format($dados_existentes['massa_gorda'], 2); ?> kg</div>
                        </div>
                        
                        <div class="result-card">
                            <div class="result-label">IMC</div>
                            <div class="result-value"><?php echo number_format($dados_existentes['imc'], 2); ?></div>
                        </div>
                        
                        <div class="result-card">
                            <div class="result-label">Densidade Corporal</div>
                            <div class="result-value"><?php echo number_format($dados_existentes['densidade_corporal'], 4); ?></div>
                        </div>
                        
                        <div class="result-card">
                            <div class="result-label">Soma das Dobras</div>
                            <div class="result-value"><?php echo number_format($dados_existentes['soma_dobras'], 1); ?> mm</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">📊 Calcular e Salvar</button>
                <a href="avaliacoes.php" class="btn btn-secondary">← Voltar</a>            </div>
        </form>
        
        <?php elseif ($tipo === 'perimetria'): ?>
        
        <form method="POST" id="perimetriaForm">
            <input type="hidden" name="action" value="salvar_perimetria">
            
            <!-- Medidas do Tronco -->
            <div class="form-section">
                <div class="form-section-header">
                    👕 Medidas do Tronco
                </div>
                <div class="form-section-body">
                    <div class="form-grid-3">                        <div class="form-group">
                            <label for="pescoco">Pescoço (cm)</label>
                            <input type="number" id="pescoco" name="pescoco" step="0.1" min="20" max="60" class="text-gray-900"
                                   value="<?php echo $dados_existentes['pescoco'] ?? ''; ?>"
                                   placeholder="Ex: 35.5">
                            <small>Medida na base do pescoço</small>
                        </div>
                          <div class="form-group">
                            <label for="torax">Tórax (cm)</label>
                            <input type="number" id="torax" name="torax" step="0.1" min="60" max="150" class="text-gray-900"
                                   value="<?php echo $dados_existentes['torax'] ?? ''; ?>"
                                   placeholder="Ex: 95.0">
                            <small>Passa pela linha dos mamilos</small>
                        </div>
                          <div class="form-group">
                            <label for="cintura">Cintura (cm) *</label>
                            <input type="number" id="cintura" name="cintura" step="0.1" min="50" max="150" class="text-gray-900" required
                                   value="<?php echo $dados_existentes['cintura'] ?? ''; ?>"
                                   placeholder="Ex: 80.5">
                            <small>Menor circunferência do tronco</small>
                        </div>
                          <div class="form-group">
                            <label for="abdomen">Abdômen (cm)</label>
                            <input type="number" id="abdomen" name="abdomen" step="0.1" min="50" max="150" class="text-gray-900"
                                   value="<?php echo $dados_existentes['abdomen'] ?? ''; ?>"
                                   placeholder="Ex: 85.0">
                            <small>Ao nível do umbigo</small>
                        </div>
                        
                        <div class="form-group">                            <label for="quadril">Quadril (cm) *</label>
                            <input type="number" class="text-gray-900" id="quadril" name="quadril" step="0.1" min="60" max="160" required
                                   value="<?php echo $dados_existentes['quadril'] ?? ''; ?>"
                                   placeholder="Ex: 95.0">
                            <small>Maior circunferência dos glúteos</small>
                        </div>
                          <div class="form-group">
                            <label for="relacao_calc">Relação Cintura/Quadril</label>
                            <input type="text" id="relacao_calc" readonly class="form-control-readonly"
                                   placeholder="Calculado automaticamente">
                            <small id="relacao_classificacao"></small>
                        </div>
                    </div>
                    
                    <!-- Real-time WHR Interpretation -->
                    <div id="whr-interpretation" class="calculation-tip" style="display: none; margin-top: var(--spacing-md);"></div>
                </div>
            </div>
            
            <!-- Medidas dos Membros Inferiores -->
            <div class="form-section">
                <div class="form-section-header">
                    🦵 Membros Inferiores
                </div>
                <div class="form-section-body">
                    <div class="form-grid-2">
                        <div class="form-group">                            <label for="coxa_direita">Coxa Direita (cm)</label>
                            <input type="number" class="text-gray-900" id="coxa_direita" name="coxa_direita" step="0.1" min="30" max="80"
                                   value="<?php echo $dados_existentes['coxa_direita'] ?? ''; ?>"
                                   placeholder="Ex: 55.0">
                            <small>Maior circunferência da coxa</small>
                        </div>
                        
                        <div class="form-group">                            <label for="coxa_esquerda">Coxa Esquerda (cm)</label>
                            <input type="number" class="text-gray-900" id="coxa_esquerda" name="coxa_esquerda" step="0.1" min="30" max="80"
                                   value="<?php echo $dados_existentes['coxa_esquerda'] ?? ''; ?>"
                                   placeholder="Ex: 55.2">
                            <small>Maior circunferência da coxa</small>
                        </div>
                        
                        <div class="form-group">                            <label for="panturrilha_direita">Panturrilha Direita (cm)</label>
                            <input type="number" class="text-gray-900" id="panturrilha_direita" name="panturrilha_direita" step="0.1" min="20" max="50"
                                   value="<?php echo $dados_existentes['panturrilha_direita'] ?? ''; ?>"
                                   placeholder="Ex: 35.0">
                            <small>Maior circunferência da panturrilha</small>
                        </div>
                        
                        <div class="form-group">                            <label for="panturrilha_esquerda">Panturrilha Esquerda (cm)</label>
                            <input type="number" class="text-gray-900" id="panturrilha_esquerda" name="panturrilha_esquerda" step="0.1" min="20" max="50"
                                   value="<?php echo $dados_existentes['panturrilha_esquerda'] ?? ''; ?>"
                                   placeholder="Ex: 35.2">
                            <small>Maior circunferência da panturrilha</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Medidas dos Membros Superiores -->
            <div class="form-section">
                <div class="form-section-header">
                    💪 Membros Superiores
                </div>
                <div class="form-section-body">
                    <div class="form-grid-3">
                        <div class="form-group">                            <label for="braco_direito">Braço Direito (cm)</label>
                            <input type="number" class="text-gray-900" id="braco_direito" name="braco_direito" step="0.1" min="20" max="50"
                                   value="<?php echo $dados_existentes['braco_direito'] ?? ''; ?>"
                                   placeholder="Ex: 32.0">
                            <small>Contraído, maior circunferência</small>
                        </div>
                        
                        <div class="form-group">                            <label for="braco_esquerdo">Braço Esquerdo (cm)</label>
                            <input type="number" class="text-gray-900" id="braco_esquerdo" name="braco_esquerdo" step="0.1" min="20" max="50"
                                   value="<?php echo $dados_existentes['braco_esquerdo'] ?? ''; ?>"
                                   placeholder="Ex: 31.8">
                            <small>Contraído, maior circunferência</small>
                        </div>
                        
                        <div class="form-group">                            <label for="antebraco_direito">Antebraço Direito (cm)</label>
                            <input type="number" class="text-gray-900" id="antebraco_direito" name="antebraco_direito" step="0.1" min="15" max="40"
                                   value="<?php echo $dados_existentes['antebraco_direito'] ?? ''; ?>"
                                   placeholder="Ex: 26.5">
                            <small>Maior circunferência do antebraço</small>
                        </div>
                        
                        <div class="form-group">                            <label for="antebraco_esquerdo">Antebraço Esquerdo (cm)</label>
                            <input type="number" class="text-gray-900" id="antebraco_esquerdo" name="antebraco_esquerdo" step="0.1" min="15" max="40"
                                   value="<?php echo $dados_existentes['antebraco_esquerdo'] ?? ''; ?>"
                                   placeholder="Ex: 26.3">
                            <small>Maior circunferência do antebraço</small>
                        </div>
                        
                        <div class="form-group">                            <label for="punho">Punho (cm)</label>
                            <input type="number" class="text-gray-900" id="punho" name="punho" step="0.1" min="10" max="25"
                                   value="<?php echo $dados_existentes['punho'] ?? ''; ?>"
                                   placeholder="Ex: 16.5">
                            <small>Circunferência do punho</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Resultados Calculados -->
            <?php if ($dados_existentes && $dados_existentes['relacao_cintura_quadril']): ?>
            <div class="form-section">
                <div class="form-section-header">
                    📊 Análise de Resultados
                </div>
                <div class="form-section-body">
                    <div class="results-grid">
                        <div class="result-card">
                            <div class="result-label">Relação Cintura/Quadril</div>
                            <div class="result-value primary"><?php echo number_format($dados_existentes['relacao_cintura_quadril'], 3); ?></div>
                            <div class="result-classification <?php 
                                $sexo = $avaliacao['sexo'];
                                $rcq = $dados_existentes['relacao_cintura_quadril'];
                                if ($sexo === 'M') {
                                    if ($rcq < 0.90) echo 'excelente';
                                    elseif ($rcq < 0.95) echo 'bom';
                                    elseif ($rcq < 1.00) echo 'moderado';
                                    else echo 'alto';
                                    
                                    if ($rcq < 0.90) echo '">Excelente';
                                    elseif ($rcq < 0.95) echo '">Bom';
                                    elseif ($rcq < 1.00) echo '">Moderado';
                                    else echo '">Alto Risco';
                                } else {
                                    if ($rcq < 0.80) echo 'excelente';
                                    elseif ($rcq < 0.85) echo 'bom';
                                    elseif ($rcq < 0.90) echo 'moderado';
                                    else echo 'alto';
                                    
                                    if ($rcq < 0.80) echo '">Excelente';
                                    elseif ($rcq < 0.85) echo '">Bom';
                                    elseif ($rcq < 0.90) echo '">Moderado';
                                    else echo '">Alto Risco';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <?php if ($dados_existentes['coxa_direita'] && $dados_existentes['coxa_esquerda']): ?>
                        <div class="result-card">
                            <div class="result-label">Diferença entre Coxas</div>
                            <div class="result-value"><?php echo number_format(abs($dados_existentes['coxa_direita'] - $dados_existentes['coxa_esquerda']), 1); ?> cm</div>
                            <div class="result-classification <?php 
                                $diff = abs($dados_existentes['coxa_direita'] - $dados_existentes['coxa_esquerda']);
                                if ($diff <= 1.0) echo 'excelente">Normal';
                                elseif ($diff <= 2.0) echo 'bom">Leve';
                                else echo 'moderado">Significativa';
                            ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($dados_existentes['braco_direito'] && $dados_existentes['braco_esquerdo']): ?>
                        <div class="result-card">
                            <div class="result-label">Diferença entre Braços</div>
                            <div class="result-value"><?php echo number_format(abs($dados_existentes['braco_direito'] - $dados_existentes['braco_esquerdo']), 1); ?> cm</div>
                            <div class="result-classification <?php 
                                $diff = abs($dados_existentes['braco_direito'] - $dados_existentes['braco_esquerdo']);
                                if ($diff <= 0.5) echo 'excelente">Normal';
                                elseif ($diff <= 1.0) echo 'bom">Leve';
                                else echo 'moderado">Significativa';
                            ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="perimetry-tips">
                        <h4>💡 Interpretação dos Resultados</h4>
                        <div class="tips-grid">
                            <div class="tip-card">
                                <strong>Relação Cintura/Quadril:</strong>
                                <p>Indicador de distribuição de gordura e risco cardiovascular.</p>
                                <ul>
                                    <li><strong>Homens:</strong> &lt; 0.90 (Baixo), 0.90-1.00 (Moderado), &gt; 1.00 (Alto)</li>
                                    <li><strong>Mulheres:</strong> &lt; 0.80 (Baixo), 0.80-0.90 (Moderado), &gt; 0.90 (Alto)</li>
                                </ul>
                            </div>
                            
                            <div class="tip-card">
                                <strong>Assimetrias:</strong>
                                <p>Diferenças entre membros podem indicar desequilíbrios musculares.</p>
                                <ul>
                                    <li><strong>Normal:</strong> Diferenças &lt; 1cm para membros</li>
                                    <li><strong>Atenção:</strong> Diferenças &gt; 2cm podem necessitar correção</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">📏 Salvar Medidas</button>
                <a href="avaliacoes.php" class="btn btn-secondary">← Voltar</a>
            </div>
        </form>
          <?php elseif ($tipo === 'postural'): ?>
        
        <form method="POST" id="posturalForm">
            <input type="hidden" name="action" value="salvar_postural">
            
            <!-- Análise por Segmento -->
            <div class="form-section">
                <div class="form-section-header">
                    🧍 Análise Postural por Segmentos
                </div>
                <div class="form-section-body">
                    
                    <!-- Cabeça -->
                    <div class="form-group">
                        <label for="cabeca_posicao">Posição da Cabeça</label>
                        <select id="cabeca_posicao" name="cabeca_posicao" class="text-gray-900" required>
                            <option value="normal" <?php echo ($dados_existentes['cabeca_posicao'] ?? 'normal') === 'normal' ? 'selected' : ''; ?>>Normal</option>
                            <option value="anteriorizada" <?php echo ($dados_existentes['cabeca_posicao'] ?? '') === 'anteriorizada' ? 'selected' : ''; ?>>Anteriorizada</option>
                            <option value="lateralizada_direita" <?php echo ($dados_existentes['cabeca_posicao'] ?? '') === 'lateralizada_direita' ? 'selected' : ''; ?>>Lateralizada à Direita</option>
                            <option value="lateralizada_esquerda" <?php echo ($dados_existentes['cabeca_posicao'] ?? '') === 'lateralizada_esquerda' ? 'selected' : ''; ?>>Lateralizada à Esquerda</option>
                        </select>
                    </div>
                    
                    <!-- Ombros -->
                    <div class="form-group">
                        <label for="ombros_posicao">Posição dos Ombros</label>
                        <select id="ombros_posicao" name="ombros_posicao" class="text-gray-900" required>
                            <option value="normais" <?php echo ($dados_existentes['ombros_posicao'] ?? 'normais') === 'normais' ? 'selected' : ''; ?>>Normais</option>
                            <option value="elevado_direito" <?php echo ($dados_existentes['ombros_posicao'] ?? '') === 'elevado_direito' ? 'selected' : ''; ?>>Ombro Direito Elevado</option>
                            <option value="elevado_esquerdo" <?php echo ($dados_existentes['ombros_posicao'] ?? '') === 'elevado_esquerdo' ? 'selected' : ''; ?>>Ombro Esquerdo Elevado</option>
                            <option value="protraidos" <?php echo ($dados_existentes['ombros_posicao'] ?? '') === 'protraidos' ? 'selected' : ''; ?>>Protraídos (para frente)</option>
                            <option value="retraidos" <?php echo ($dados_existentes['ombros_posicao'] ?? '') === 'retraidos' ? 'selected' : ''; ?>>Retraídos (para trás)</option>
                        </select>
                    </div>
                    
                    <!-- Coluna -->
                    <div class="form-grid-3">
                        <div class="form-group">
                            <label for="coluna_cervical">Coluna Cervical</label>
                            <select id="coluna_cervical" name="coluna_cervical" class="text-gray-900" required>
                                <option value="normal" <?php echo ($dados_existentes['coluna_cervical'] ?? 'normal') === 'normal' ? 'selected' : ''; ?>>Normal</option>
                                <option value="hiperlordose" <?php echo ($dados_existentes['coluna_cervical'] ?? '') === 'hiperlordose' ? 'selected' : ''; ?>>Hiperlordose</option>
                                <option value="retificada" <?php echo ($dados_existentes['coluna_cervical'] ?? '') === 'retificada' ? 'selected' : ''; ?>>Retificada</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="coluna_toracica">Coluna Torácica</label>
                            <select id="coluna_toracica" name="coluna_toracica" class="text-gray-900" required>
                                <option value="normal" <?php echo ($dados_existentes['coluna_toracica'] ?? 'normal') === 'normal' ? 'selected' : ''; ?>>Normal</option>
                                <option value="hipercifose" <?php echo ($dados_existentes['coluna_toracica'] ?? '') === 'hipercifose' ? 'selected' : ''; ?>>Hipercifose</option>
                                <option value="retificada" <?php echo ($dados_existentes['coluna_toracica'] ?? '') === 'retificada' ? 'selected' : ''; ?>>Retificada</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="coluna_lombar">Coluna Lombar</label>
                            <select id="coluna_lombar" name="coluna_lombar" class="text-gray-900" required>
                                <option value="normal" <?php echo ($dados_existentes['coluna_lombar'] ?? 'normal') === 'normal' ? 'selected' : ''; ?>>Normal</option>
                                <option value="hiperlordose" <?php echo ($dados_existentes['coluna_lombar'] ?? '') === 'hiperlordose' ? 'selected' : ''; ?>>Hiperlordose</option>
                                <option value="retificada" <?php echo ($dados_existentes['coluna_lombar'] ?? '') === 'retificada' ? 'selected' : ''; ?>>Retificada</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Pelve -->
                    <div class="form-group">
                        <label for="pelve_posicao">Posição da Pelve</label>
                        <select id="pelve_posicao" name="pelve_posicao" class="text-gray-900" required>
                            <option value="normal" <?php echo ($dados_existentes['pelve_posicao'] ?? 'normal') === 'normal' ? 'selected' : ''; ?>>Normal</option>
                            <option value="anteroversao" <?php echo ($dados_existentes['pelve_posicao'] ?? '') === 'anteroversao' ? 'selected' : ''; ?>>Anteroversão</option>
                            <option value="retroversao" <?php echo ($dados_existentes['pelve_posicao'] ?? '') === 'retroversao' ? 'selected' : ''; ?>>Retroversão</option>
                            <option value="lateralizacao" <?php echo ($dados_existentes['pelve_posicao'] ?? '') === 'lateralizacao' ? 'selected' : ''; ?>>Lateralização</option>
                        </select>
                    </div>
                    
                    <!-- Joelhos -->
                    <div class="form-group">
                        <label for="joelhos_posicao">Posição dos Joelhos</label>
                        <select id="joelhos_posicao" name="joelhos_posicao" class="text-gray-900" required>
                            <option value="normais" <?php echo ($dados_existentes['joelhos_posicao'] ?? 'normais') === 'normais' ? 'selected' : ''; ?>>Normais</option>
                            <option value="valgos" <?php echo ($dados_existentes['joelhos_posicao'] ?? '') === 'valgos' ? 'selected' : ''; ?>>Valgos (joelhos para dentro)</option>
                            <option value="varos" <?php echo ($dados_existentes['joelhos_posicao'] ?? '') === 'varos' ? 'selected' : ''; ?>>Varos (joelhos para fora)</option>
                            <option value="recurvatos" <?php echo ($dados_existentes['joelhos_posicao'] ?? '') === 'recurvatos' ? 'selected' : ''; ?>>Recurvatos (hiperextensão)</option>
                        </select>
                    </div>
                    
                    <!-- Pés -->
                    <div class="form-group">
                        <label for="pes_posicao">Posição dos Pés</label>
                        <select id="pes_posicao" name="pes_posicao" class="text-gray-900" required>
                            <option value="normais" <?php echo ($dados_existentes['pes_posicao'] ?? 'normais') === 'normais' ? 'selected' : ''; ?>>Normais</option>
                            <option value="pronados" <?php echo ($dados_existentes['pes_posicao'] ?? '') === 'pronados' ? 'selected' : ''; ?>>Pronados (pés para dentro)</option>
                            <option value="supinados" <?php echo ($dados_existentes['pes_posicao'] ?? '') === 'supinados' ? 'selected' : ''; ?>>Supinados (pés para fora)</option>
                            <option value="cavos" <?php echo ($dados_existentes['pes_posicao'] ?? '') === 'cavos' ? 'selected' : ''; ?>>Pés Cavos (arco alto)</option>
                            <option value="planos" <?php echo ($dados_existentes['pes_posicao'] ?? '') === 'planos' ? 'selected' : ''; ?>>Pés Planos (arco baixo)</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Observações e Recomendações -->
            <div class="form-section">
                <div class="form-section-header">
                    📝 Observações e Recomendações
                </div>
                <div class="form-section-body">
                    <div class="form-group">
                        <label for="observacoes_posturais">Observações Posturais</label>
                        <textarea id="observacoes_posturais" name="observacoes_posturais" rows="4" class="text-gray-900" 
                                  placeholder="Observações detalhadas sobre a postura do avaliado..."><?php echo $dados_existentes['observacoes_posturais'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="recomendacoes">Recomendações</label>
                        <textarea id="recomendacoes" name="recomendacoes" rows="4" class="text-gray-900" 
                                  placeholder="Recomendações de exercícios e cuidados posturais..."><?php echo $dados_existentes['recomendacoes'] ?? ''; ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Guia de Avaliação -->
            <div class="postural-guide">
                <h4>💡 Guia de Avaliação Postural</h4>
                <div class="guide-grid">
                    <div class="guide-card">
                        <strong>Vista Anterior:</strong>
                        <ul>
                            <li>Simetria da cabeça e pescoço</li>
                            <li>Altura e posição dos ombros</li>
                            <li>Alinhamento das mãos</li>
                            <li>Posição dos joelhos (valgos/varos)</li>
                            <li>Posição dos pés</li>
                        </ul>
                    </div>
                    
                    <div class="guide-card">
                        <strong>Vista Lateral:</strong>
                        <ul>
                            <li>Projeção da cabeça</li>
                            <li>Curvatura cervical</li>
                            <li>Curvatura torácica (cifose)</li>
                            <li>Curvatura lombar (lordose)</li>
                            <li>Posição da pelve</li>
                        </ul>
                    </div>
                    
                    <div class="guide-card">
                        <strong>Vista Posterior:</strong>
                        <ul>
                            <li>Simetria dos ombros</li>
                            <li>Alinhamento da coluna</li>
                            <li>Simetria da pelve</li>
                            <li>Altura das mãos</li>
                            <li>Simetria dos membros inferiores</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">📸 Salvar Avaliação Postural</button>
                <a href="avaliacoes.php" class="btn btn-secondary">← Voltar</a>
            </div>
        </form>
        
        <?php elseif ($tipo === 'cardio'): ?>
        
        <form method="POST" id="cardioForm">
            <input type="hidden" name="action" value="salvar_vo2max">
            
            <!-- Seleção do Tipo de Teste -->
            <div class="form-section">
                <div class="form-section-header">
                    ❤️ Tipo de Teste Cardiorrespiratório
                </div>
                <div class="form-section-body">
                    <div class="form-group">
                        <label for="tipo_teste">Selecione o Teste</label>
                        <select id="tipo_teste" name="tipo_teste" class="text-gray-900" required>
                            <option value="">Selecione um teste...</option>
                            <option value="cooper" <?php echo ($dados_existentes['tipo_teste'] ?? '') === 'cooper' ? 'selected' : ''; ?>>Teste de Cooper (12 minutos)</option>
                            <option value="step" <?php echo ($dados_existentes['tipo_teste'] ?? '') === 'step' ? 'selected' : ''; ?>>Teste de Step</option>
                            <option value="caminhada" <?php echo ($dados_existentes['tipo_teste'] ?? '') === 'caminhada' ? 'selected' : ''; ?>>Caminhada de 1 Milha</option>
                        </select>
                    </div>
                    
                    <div id="teste-info" class="teste-info">
                        <p>💡 Selecione um teste acima para ver as instruções.</p>
                    </div>
                </div>
            </div>
            
            <!-- Dados Básicos -->
            <div class="form-section">
                <div class="form-section-header">
                    📊 Dados Básicos
                </div>
                <div class="form-section-body">
                    <div class="form-grid-3">
                        <div class="form-group">
                            <label for="peso_corporal">Peso (kg)</label>
                            <input type="number" id="peso_corporal" name="peso_corporal" step="0.1" class="text-gray-900" 
                                   value="<?php echo $dados_existentes['peso_corporal'] ?? ''; ?>" placeholder="70.0">
                        </div>
                        
                        <div class="form-group">
                            <label for="idade">Idade</label>
                            <input type="number" id="idade" name="idade" class="text-gray-900" 
                                   value="<?php echo $dados_existentes['idade'] ?? (date('Y') - date('Y', strtotime($avaliacao['data_nascimento']))); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="sexo">Sexo</label>
                            <select id="sexo" name="sexo" class="text-gray-900" required>
                                <option value="M" <?php echo ($dados_existentes['sexo'] ?? $avaliacao['sexo']) === 'M' ? 'selected' : ''; ?>>Masculino</option>
                                <option value="F" <?php echo ($dados_existentes['sexo'] ?? $avaliacao['sexo']) === 'F' ? 'selected' : ''; ?>>Feminino</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Teste de Cooper -->
            <div class="form-section teste-section" id="cooper-section" style="display: none;">
                <div class="form-section-header">
                    🏃 Teste de Cooper (12 minutos)
                </div>
                <div class="form-section-body">
                    <div class="form-group">
                        <label for="distancia_cooper">Distância Percorrida (metros)</label>
                        <input type="number" id="distancia_cooper" name="distancia_cooper" class="text-gray-900" 
                               value="<?php echo $dados_existentes['distancia_cooper'] ?? ''; ?>" placeholder="2400">
                        <small class="text-muted">Distância total percorrida em 12 minutos</small>
                    </div>
                </div>
            </div>
            
            <!-- Teste de Step -->
            <div class="form-section teste-section" id="step-section" style="display: none;">
                <div class="form-section-header">
                    📶 Teste de Step (3 minutos)
                </div>
                <div class="form-section-body">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="frequencia_repouso">FC de Repouso (bpm)</label>
                            <input type="number" id="frequencia_repouso" name="frequencia_repouso" class="text-gray-900" 
                                   value="<?php echo $dados_existentes['frequencia_repouso'] ?? ''; ?>" placeholder="70">
                        </div>
                        
                        <div class="form-group">
                            <label for="frequencia_1min">FC 1º minuto (bpm)</label>
                            <input type="number" id="frequencia_1min" name="frequencia_1min" class="text-gray-900" 
                                   value="<?php echo $dados_existentes['frequencia_1min'] ?? ''; ?>" placeholder="120">
                        </div>
                        
                        <div class="form-group">
                            <label for="frequencia_2min">FC 2º minuto (bpm)</label>
                            <input type="number" id="frequencia_2min" name="frequencia_2min" class="text-gray-900" 
                                   value="<?php echo $dados_existentes['frequencia_2min'] ?? ''; ?>" placeholder="110">
                        </div>
                        
                        <div class="form-group">
                            <label for="frequencia_3min">FC 3º minuto (bpm)</label>
                            <input type="number" id="frequencia_3min" name="frequencia_3min" class="text-gray-900" 
                                   value="<?php echo $dados_existentes['frequencia_3min'] ?? ''; ?>" placeholder="100">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Teste de Caminhada -->
            <div class="form-section teste-section" id="caminhada-section" style="display: none;">
                <div class="form-section-header">
                    🚶 Caminhada de 1 Milha
                </div>
                <div class="form-section-body">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="tempo_caminhada">Tempo para Completar (mm:ss)</label>
                            <input type="time" id="tempo_caminhada" name="tempo_caminhada" class="text-gray-900" 
                                   value="<?php echo $dados_existentes['tempo_caminhada'] ?? ''; ?>">
                            <small class="text-muted">Tempo total para percorrer 1 milha caminhando</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="fc_final_caminhada">FC Final (bpm)</label>
                            <input type="number" id="fc_final_caminhada" name="fc_final_caminhada" class="text-gray-900" 
                                   value="<?php echo $dados_existentes['fc_final_caminhada'] ?? ''; ?>" placeholder="130">
                            <small class="text-muted">Frequência cardíaca ao final da caminhada</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Condições e Observações -->
            <div class="form-section">
                <div class="form-section-header">
                    📝 Condições do Teste e Observações
                </div>
                <div class="form-section-body">
                    <div class="form-group">
                        <label for="condicoes_teste">Condições do Teste</label>
                        <textarea id="condicoes_teste" name="condicoes_teste" rows="3" class="text-gray-900" 
                                  placeholder="Ex: Temperatura ambiente, hora do dia, local do teste..."><?php echo $dados_existentes['condicoes_teste'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="medicamentos_uso">Medicamentos em Uso</label>
                        <textarea id="medicamentos_uso" name="medicamentos_uso" rows="2" class="text-gray-900" 
                                  placeholder="Medicamentos que podem influenciar no resultado..."><?php echo $dados_existentes['medicamentos_uso'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="observacoes">Observações</label>
                        <textarea id="observacoes" name="observacoes" rows="3" class="text-gray-900" 
                                  placeholder="Observações sobre o desempenho durante o teste..."><?php echo $dados_existentes['observacoes'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="recomendacoes">Recomendações</label>
                        <textarea id="recomendacoes" name="recomendacoes" rows="3" class="text-gray-900" 
                                  placeholder="Recomendações baseadas no resultado do teste..."><?php echo $dados_existentes['recomendacoes'] ?? ''; ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Resultados (se existirem) -->
            <?php if ($dados_existentes && $dados_existentes['vo2max_calculado']): ?>
            <div class="form-section">
                <div class="form-section-header">
                    📊 Resultados do Teste
                </div>
                <div class="form-section-body">
                    <div class="results-grid">
                        <div class="result-card">
                            <div class="result-label">VO2 Máximo</div>
                            <div class="result-value primary"><?php echo number_format($dados_existentes['vo2max_calculado'], 1); ?></div>
                            <small>ml/kg/min</small>
                        </div>
                        
                        <div class="result-card">
                            <div class="result-label">Classificação</div>
                            <div class="result-classification <?php echo strtolower(str_replace(' ', '-', $dados_existentes['classificacao'])); ?>">
                                <?php echo $dados_existentes['classificacao']; ?>
                            </div>
                        </div>
                        
                        <div class="result-card">
                            <div class="result-label">Tipo de Teste</div>
                            <div class="result-value"><?php echo ucfirst($dados_existentes['tipo_teste']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">❤️ Salvar Teste VO2Max</button>
                <a href="avaliacoes.php" class="btn btn-secondary">← Voltar</a>
            </div>
        </form>
        
        <?php else: ?>
        
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 3rem;">
                <h3>🚧 Em Desenvolvimento</h3>
                <p>O formulário para <strong><?php echo $info_atual['titulo']; ?></strong> está sendo desenvolvido.</p>
                <p>Em breve estará disponível com todas as funcionalidades.</p>
                <a href="avaliacoes.php" class="btn btn-primary">← Voltar para Avaliações</a>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
    // Auto-save functionality
    let autoSaveTimeout;
    
    function autoSave() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(() => {
            // Implementar auto-save aqui se necessário
            console.log('Auto-save seria executado aqui');
        }, 30000); // 30 segundos
    }
    
    // Add event listeners to form fields for auto-save
    document.querySelectorAll('input, textarea, select').forEach(field => {
        field.addEventListener('change', autoSave);
        field.addEventListener('input', autoSave);
    });
    
    // Form validation
    document.getElementById('anamneseForm')?.addEventListener('submit', function(e) {
        const requiredFields = this.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.style.borderColor = '#EB5757';
            } else {
                field.style.borderColor = '';
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Por favor, preencha todos os campos obrigatórios.');
            return;
        }
    });
      // Auto-hide alerts
    setTimeout(() => {
        const alert = document.getElementById('alert-message');
        if (alert) {
            alert.style.display = 'none';
        }
    }, 5000);
    
    // Body Composition Form specific functionality
    const composicaoForm = document.getElementById('composicaoForm');
    if (composicaoForm) {
        const protocoloSelect = document.getElementById('protocolo_usado');
        const protocolInfo = document.getElementById('protocol-info');
        const pesoInput = document.getElementById('peso');
        const alturaInput = document.getElementById('altura');
        const imcInput = document.getElementById('imc_calc');
        
        // Protocol information
        const protocolInfos = {
            'pollock3': {
                title: '📋 Protocolo Pollock 3 Dobras',
                description: 'Utiliza 3 dobras cutâneas. Para homens: Peitoral, Abdomen e Coxa. Para mulheres: Tríceps, Supra-ilíaca e Coxa.',
                class: 'pollock3'
            },
            'pollock7': {
                title: '📋 Protocolo Pollock 7 Dobras',
                description: 'Utiliza todas as 7 dobras cutâneas para maior precisão: Peitoral, Axilar, Tríceps, Subescapular, Abdomen, Supra-ilíaca e Coxa.',
                class: 'pollock7'
            },
            'guedes': {
                title: '📋 Protocolo Guedes & Guedes',
                description: 'Protocolo brasileiro adaptado. Para homens: Peitoral, Abdomen e Coxa. Para mulheres: Tríceps, Supra-ilíaca e Coxa.',
                class: 'guedes'
            }
        };
        
        // Update protocol info and show/hide fields
        function updateProtocolFields() {
            const selectedProtocol = protocoloSelect.value;
            const dobraFields = document.querySelectorAll('.dobra-field');
            
            // Reset all fields
            dobraFields.forEach(field => {
                field.classList.add('hidden');
                field.classList.remove('required');
                const input = field.querySelector('input');
                if (input) input.removeAttribute('required');
            });
            
            if (selectedProtocol && protocolInfos[selectedProtocol]) {
                // Update info box
                const info = protocolInfos[selectedProtocol];
                protocolInfo.innerHTML = `
                    <h4>${info.title}</h4>
                    <p>${info.description}</p>
                `;
                protocolInfo.className = `protocol-info ${info.class}`;
                
                // Show relevant fields
                dobraFields.forEach(field => {
                    const protocols = field.dataset.protocols || '';
                    const required = field.dataset.required || '';
                    
                    if (protocols.includes(selectedProtocol)) {
                        field.classList.remove('hidden');
                        
                        if (required.includes(selectedProtocol)) {
                            field.classList.add('required');
                            const input = field.querySelector('input');
                            if (input) input.setAttribute('required', 'required');
                        }
                    }
                });
            } else {
                protocolInfo.innerHTML = '<p>💡 Selecione um protocolo acima para ver quais dobras são necessárias.</p>';
                protocolInfo.className = 'protocol-info';
            }
        }
        
        // Calculate IMC
        function calculateIMC() {
            const peso = parseFloat(pesoInput.value);
            const altura = parseFloat(alturaInput.value);
            
            if (peso && altura && altura > 0) {
                const imc = peso / (altura * altura);
                imcInput.value = imc.toFixed(2);
            } else {
                imcInput.value = '';
            }
        }
        
        // Event listeners
        protocoloSelect.addEventListener('change', updateProtocolFields);
        pesoInput.addEventListener('input', calculateIMC);
        alturaInput.addEventListener('input', calculateIMC);
        
        // Initialize on load
        updateProtocolFields();
        calculateIMC();
        
        // Form validation for body composition
        composicaoForm.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#EB5757';
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios para o protocolo selecionado.');
                return;
            }
        });    }
    
    // Cardio Form specific functionality
    const cardioForm = document.getElementById('cardioForm');
    if (cardioForm) {
        const tipoTesteSelect = document.getElementById('tipo_teste');
        const testeInfo = document.getElementById('teste-info');
        const testeSections = document.querySelectorAll('.teste-section');
        
        // Informações dos testes
        const testeInfos = {
            'cooper': {
                title: '🏃 Teste de Cooper',
                description: 'O teste de Cooper avalia a capacidade cardiorrespiratória através da distância máxima percorrida em 12 minutos.',
                instructions: [
                    'Realize um aquecimento de 5-10 minutos',
                    'Corra ou caminhe a maior distância possível em 12 minutos',
                    'Mantenha um ritmo constante durante todo o teste',
                    'Registre a distância total percorrida em metros'
                ],
                class: 'cooper'
            },
            'step': {
                title: '📶 Teste de Step',
                description: 'Teste submáximo que avalia a capacidade cardiorrespiratória através da resposta da frequência cardíaca.',
                instructions: [
                    'Use um step de 30cm de altura para mulheres, 40cm para homens',
                    'Mantenha ritmo de 30 subidas por minuto durante 3 minutos',
                    'Meça a FC de repouso antes do teste',
                    'Meça a FC ao final de cada minuto durante o teste'
                ],
                class: 'step'
            },
            'caminhada': {
                title: '🚶 Caminhada de 1 Milha',
                description: 'Teste de caminhada que estima o VO2max através do tempo para percorrer 1 milha e FC final.',
                instructions: [
                    'Percorra 1 milha (1.609 metros) caminhando',
                    'Mantenha um ritmo constante e confortável',
                    'Registre o tempo total para completar a distância',
                    'Meça a frequência cardíaca imediatamente ao final'
                ],
                class: 'caminhada'
            }
        };
        
        // Atualizar informações e mostrar/ocultar seções
        function updateTesteFields() {
            const selectedTeste = tipoTesteSelect.value;
            
            // Ocultar todas as seções
            testeSections.forEach(section => {
                section.style.display = 'none';
                // Remover required dos campos não visíveis
                section.querySelectorAll('input[required]').forEach(input => {
                    input.removeAttribute('required');
                });
            });
            
            if (selectedTeste && testeInfos[selectedTeste]) {
                // Atualizar info box
                const info = testeInfos[selectedTeste];
                testeInfo.innerHTML = `
                    <h4>${info.title}</h4>
                    <p>${info.description}</p>
                    <div class="teste-instructions">
                        <h5>📋 Instruções:</h5>
                        <ol>
                            ${info.instructions.map(instruction => `<li>${instruction}</li>`).join('')}
                        </ol>
                    </div>
                `;
                testeInfo.className = `teste-info ${info.class}`;
                
                // Mostrar seção correspondente
                const targetSection = document.getElementById(`${selectedTeste}-section`);
                if (targetSection) {
                    targetSection.style.display = 'block';
                    // Adicionar required aos campos visíveis
                    targetSection.querySelectorAll('input[data-required="true"]').forEach(input => {
                        input.setAttribute('required', 'required');
                    });
                }
            } else {
                testeInfo.innerHTML = '<p>💡 Selecione um teste acima para ver as instruções.</p>';
                testeInfo.className = 'teste-info';
            }
        }
        
        // Event listener
        tipoTesteSelect.addEventListener('change', updateTesteFields);
        
        // Inicializar na carga
        updateTesteFields();
        
        // Validação do formulário
        cardioForm.addEventListener('submit', function(e) {
            const selectedTeste = tipoTesteSelect.value;
            if (!selectedTeste) {
                e.preventDefault();
                alert('Por favor, selecione um tipo de teste.');
                return;
            }
            
            // Validar campos específicos baseado no teste selecionado
            const targetSection = document.getElementById(`${selectedTeste}-section`);
            if (targetSection) {
                const requiredFields = targetSection.querySelectorAll('input[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.style.borderColor = '#EB5757';
                    } else {
                        field.style.borderColor = '';
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Por favor, preencha todos os campos obrigatórios do teste selecionado.');
                    return;
                }
            }
        });
    }
    
    // Postural Form specific functionality
    const posturalForm = document.getElementById('posturalForm');
    if (posturalForm) {
        // Adicionar classes de destaque visual baseado na seleção
        const selects = posturalForm.querySelectorAll('select');
        
        selects.forEach(select => {
            select.addEventListener('change', function() {
                // Resetar classes
                this.classList.remove('normal', 'alterado');
                
                // Adicionar classe baseada na seleção
                if (this.value === 'normal' || this.value === 'normais') {
                    this.classList.add('normal');
                } else if (this.value && this.value !== 'normal' && this.value !== 'normais') {
                    this.classList.add('alterado');
                }
            });
            
            // Disparar evento para estado inicial
            select.dispatchEvent(new Event('change'));
        });
    }
    </script>

    <!-- Barra de navegação removida - utilizando apenas a do header.php -->
</body>
</html>
