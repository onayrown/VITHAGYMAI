<?php
/**
 * SMARTBIOFIT - Gerador de PDF para Treinos
 * Classe para gerar PDFs profissionais dos treinos
 */

require_once __DIR__ . '/../config.php';

class TreinoPDFGenerator {
    private $html;
    
    public function gerarPDF($treino_data, $aluno_data = null) {
        $this->html = $this->gerarHTML($treino_data, $aluno_data);
        return $this->converterParaPDF();
    }
    
    private function gerarHTML($treino, $aluno) {
        $html = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Treino - ' . htmlspecialchars($treino['nome']) . '</title>
    <style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            font-size: 12px;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #FF6B35;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .logo {
            color: #FF6B35;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .treino-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin: 10px 0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #FF6B35;
        }
        
        .info-title {
            font-weight: bold;
            color: #FF6B35;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .meta-info {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 20px 0;
            text-align: center;
        }
        
        .meta-item {
            background: #e3f2fd;
            padding: 10px;
            border-radius: 8px;
        }
        
        .meta-value {
            font-size: 16px;
            font-weight: bold;
            color: #1976d2;
            display: block;
        }
        
        .meta-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }
        
        .exercise {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .exercise-header {
            background: #f5f5f5;
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .exercise-number {
            background: #FF6B35;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 12px;
            margin-right: 10px;
        }
        
        .exercise-name {
            font-weight: bold;
            font-size: 14px;
            flex: 1;
        }
        
        .exercise-category {
            background: #4285f4;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            text-transform: uppercase;
        }
        
        .exercise-body {
            padding: 15px;
        }
        
        .exercise-params {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .param {
            text-align: center;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 6px;
        }
        
        .param-value {
            font-weight: bold;
            color: #FF6B35;
            display: block;
        }
        
        .param-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }
        
        .exercise-description {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 10px;
            margin: 10px 0;
            border-radius: 0 6px 6px 0;
        }
        
        .exercise-tips {
            background: #f3e5f5;
            border-left: 4px solid #9c27b0;
            padding: 10px;
            margin: 10px 0;
            border-radius: 0 6px 6px 0;
        }
        
        .checkbox-area {
            border: 1px solid #ddd;
            width: 15px;
            height: 15px;
            display: inline-block;
            margin-right: 8px;
            vertical-align: middle;
        }
        
        .tracking-section {
            background: #fff3e0;
            border: 1px solid #ffb74d;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
        }
        
        .tracking-title {
            font-weight: bold;
            color: #f57c00;
            margin-bottom: 10px;
        }
        
        .tracking-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 10px;
            border-bottom: 1px solid #ddd;
            padding: 8px 0;
        }
        
        .tracking-row:last-child {
            border-bottom: none;
        }
        
        .notes-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }
        
        .notes-title {
            font-weight: bold;
            color: #FF6B35;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .notes-area {
            border: 1px solid #ddd;
            height: 100px;
            padding: 10px;
            border-radius: 6px;
            background: #fafafa;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        
        @media print {
            body { font-size: 11px; }
            .exercise { page-break-inside: avoid; }
        }
    </style>
</head>
<body>';

        // Header
        $html .= '
    <div class="header">
        <div class="logo">🏋️‍♂️ SMARTBIOFIT</div>
        <div class="treino-title">' . htmlspecialchars($treino['nome']) . '</div>
        <div>Plano de Treino Personalizado</div>
    </div>';

        // Informações do treino e aluno
        $html .= '<div class="info-grid">';
        
        if ($aluno) {
            $html .= '
        <div class="info-section">
            <div class="info-title">👤 INFORMAÇÕES DO ALUNO</div>
            <strong>Nome:</strong> ' . htmlspecialchars($aluno['nome']) . '<br>
            <strong>Data de Início:</strong> ' . date('d/m/Y', strtotime($aluno['data_inicio'])) . '<br>';
            if ($aluno['data_fim']) {
                $html .= '<strong>Data de Fim:</strong> ' . date('d/m/Y', strtotime($aluno['data_fim'])) . '<br>';
            }
            $html .= '<strong>Status:</strong> ' . ucfirst($aluno['status']) . '
        </div>';
        }
        
        $html .= '
        <div class="info-section">
            <div class="info-title">🏋️ INFORMAÇÕES DO TREINO</div>
            <strong>Professor:</strong> ' . htmlspecialchars($treino['professor']['nome']) . '<br>
            <strong>Tipo:</strong> ' . ucfirst($treino['tipo_treino']) . '<br>
            <strong>Nível:</strong> ' . ucfirst($treino['nivel_dificuldade']) . '<br>
            <strong>Criado em:</strong> ' . date('d/m/Y', strtotime($treino['created_at'])) . '
        </div>
    </div>';

        // Meta informações
        $html .= '
    <div class="meta-info">
        <div class="meta-item">
            <span class="meta-value">' . $treino['estatisticas']['total_exercicios'] . '</span>
            <span class="meta-label">Exercícios</span>
        </div>
        <div class="meta-item">
            <span class="meta-value">' . $treino['duracao_estimada'] . ' min</span>
            <span class="meta-label">Duração</span>
        </div>
        <div class="meta-item">
            <span class="meta-value">' . ucfirst($treino['tipo_treino']) . '</span>
            <span class="meta-label">Modalidade</span>
        </div>
        <div class="meta-item">
            <span class="meta-value">' . ucfirst($treino['nivel_dificuldade']) . '</span>
            <span class="meta-label">Dificuldade</span>
        </div>
    </div>';

        // Descrição do treino
        if ($treino['descricao']) {
            $html .= '
    <div class="info-section" style="margin: 20px 0;">
        <div class="info-title">📋 DESCRIÇÃO E OBJETIVOS</div>
        ' . htmlspecialchars($treino['descricao']) . '<br><br>
        <strong>Objetivo Principal:</strong> ' . htmlspecialchars($treino['objetivo_principal']) . '
    </div>';
        }

        // Observações individuais
        if ($aluno && $aluno['observacoes_individuais']) {
            $html .= '
    <div class="info-section" style="margin: 20px 0; border-left-color: #f57c00;">
        <div class="info-title">⚠️ OBSERVAÇÕES ESPECÍFICAS</div>
        ' . htmlspecialchars($aluno['observacoes_individuais']) . '
    </div>';
        }

        // Lista de exercícios
        $exercicios = $treino['exercicios'];
        foreach ($exercicios as $index => $exercicio) {
            $html .= '
    <div class="exercise">
        <div class="exercise-header">
            <div style="display: flex; align-items: center;">
                <div class="exercise-number">' . ($index + 1) . '</div>
                <div class="exercise-name">' . htmlspecialchars($exercicio['nome']) . '</div>
            </div>
            <div class="exercise-category">' . ucfirst($exercicio['categoria']) . '</div>
        </div>
        
        <div class="exercise-body">
            <div class="exercise-params">';
            
            if ($exercicio['series']) {
                $html .= '
                <div class="param">
                    <span class="param-value">' . $exercicio['series'] . '</span>
                    <span class="param-label">Séries</span>
                </div>';
            }
            
            if ($exercicio['repeticoes']) {
                $html .= '
                <div class="param">
                    <span class="param-value">' . htmlspecialchars($exercicio['repeticoes']) . '</span>
                    <span class="param-label">Repetições</span>
                </div>';
            }
            
            if ($exercicio['carga']) {
                $html .= '
                <div class="param">
                    <span class="param-value">' . htmlspecialchars($exercicio['carga']) . '</span>
                    <span class="param-label">Carga</span>
                </div>';
            }
            
            if ($exercicio['tempo_descanso']) {
                $html .= '
                <div class="param">
                    <span class="param-value">' . $exercicio['tempo_descanso'] . 's</span>
                    <span class="param-label">Descanso</span>
                </div>';
            }
            
            $html .= '</div>';
            
            if ($exercicio['equipamento_necessario']) {
                $html .= '<p><strong>🏃 Equipamento:</strong> ' . htmlspecialchars($exercicio['equipamento_necessario']) . '</p>';
            }
            
            if ($exercicio['descricao_tecnica']) {
                $html .= '
            <div class="exercise-description">
                <strong>📖 Técnica de Execução:</strong><br>
                ' . htmlspecialchars($exercicio['descricao_tecnica']) . '
            </div>';
            }
            
            if ($exercicio['dicas_execucao']) {
                $html .= '
            <div class="exercise-tips">
                <strong>💡 Dicas Importantes:</strong><br>
                ' . htmlspecialchars($exercicio['dicas_execucao']) . '
            </div>';
            }
            
            // Área de acompanhamento
            $html .= '
            <div class="tracking-section">
                <div class="tracking-title">📊 REGISTRO DE EXECUÇÃO</div>
                <div class="tracking-row" style="font-weight: bold; background: #f5f5f5;">
                    <div>Data</div>
                    <div>Carga Utilizada</div>
                    <div>Repetições</div>
                    <div>Observações</div>
                </div>';
                
            for ($i = 1; $i <= 4; $i++) {
                $html .= '
                <div class="tracking-row">
                    <div>___/___/___</div>
                    <div>_____________</div>
                    <div>_____________</div>
                    <div>_____________</div>
                </div>';
            }
            
            $html .= '</div>';
            $html .= '</div></div>';
        }

        // Observações gerais
        if ($treino['observacoes_gerais']) {
            $html .= '
    <div class="info-section" style="margin: 30px 0;">
        <div class="info-title">📝 OBSERVAÇÕES GERAIS DO PROFESSOR</div>
        ' . htmlspecialchars($treino['observacoes_gerais']) . '
    </div>';
        }

        // Área de anotações
        $html .= '
    <div class="notes-section">
        <div class="notes-title">📝 ANOTAÇÕES PESSOAIS</div>
        <div class="notes-area">
            Use este espaço para suas anotações pessoais, evolução, sensações durante o treino, etc.
        </div>
    </div>';

        // Footer
        $html .= '
    <div class="footer">
        <p><strong>SMARTBIOFIT</strong> - Sistema de Prescrição de Treinos</p>
        <p>Professor: ' . htmlspecialchars($treino['professor']['nome']) . ' | ' . htmlspecialchars($treino['professor']['email']) . '</p>
        <p>Documento gerado em: ' . date('d/m/Y H:i') . '</p>
    </div>

</body>
</html>';

        return $html;
    }
    
    private function converterParaPDF() {
        // Usar DOMPdf ou biblioteca similar
        // Por simplicidade, retornamos o HTML que pode ser convertido pelo navegador
        return $this->html;
    }
    
    public function gerarHTMLParaPrint($treino_data, $aluno_data = null) {
        return $this->gerarHTML($treino_data, $aluno_data);
    }
}
?>
