<?php
/**
 * SMARTBIOFIT - Gerador de QR Code
 * Sistema simples para gerar QR codes para compartilhamento de treinos
 */

class QRCodeGenerator {
    
    /**
     * Gera QR code usando API externa (Google Charts)
     */
    public static function generateQRCode($data, $size = 200) {
        // Encode dos dados
        $encodedData = urlencode($data);
        
        // URL da API do Google Charts para QR Code
        $url = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$encodedData}&choe=UTF-8";
        
        return $url;
    }
    
    /**
     * Salva QR code como imagem local
     */
    public static function saveQRCode($data, $filename, $size = 200) {
        $url = self::generateQRCode($data, $size);
        
        // Criar diretório se não existir
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Baixar e salvar a imagem
        $imageData = file_get_contents($url);
        if ($imageData !== false) {
            file_put_contents($filename, $imageData);
            return true;
        }
        
        return false;
    }
    
    /**
     * Gera QR code inline (base64) para usar em HTML
     */
    public static function generateInlineQRCode($data, $size = 200) {
        $url = self::generateQRCode($data, $size);
        
        $imageData = file_get_contents($url);
        if ($imageData !== false) {
            $base64 = base64_encode($imageData);
            return "data:image/png;base64,{$base64}";
        }
        
        return null;
    }
    
    /**
     * Gera QR code usando biblioteca JavaScript (para uso no frontend)
     */
    public static function generateJSQRCode() {
        return '
        <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
        <script>
        function gerarQRCode(elementId, texto, opcoes = {}) {
            const defaultOpcoes = {
                width: 200,
                margin: 2,
                color: {
                    dark: "#000000",
                    light: "#FFFFFF"
                }
            };
            
            const config = { ...defaultOpcoes, ...opcoes };
            
            QRCode.toCanvas(document.getElementById(elementId), texto, config, function(error) {
                if (error) {
                    console.error("Erro ao gerar QR Code:", error);
                }
            });
        }
        
        function gerarQRCodeSVG(elementId, texto, opcoes = {}) {
            const defaultOpcoes = {
                width: 200,
                margin: 2
            };
            
            const config = { ...defaultOpcoes, ...opcoes };
            
            QRCode.toString(texto, { type: "svg", ...config }, function(error, string) {
                if (error) {
                    console.error("Erro ao gerar QR Code SVG:", error);
                } else {
                    document.getElementById(elementId).innerHTML = string;
                }
            });
        }
        </script>';
    }
}

/**
 * SMARTBIOFIT - Gerador de PDF Simples
 * Sistema básico para gerar PDFs de treinos
 */

class SimplePDFGenerator {
    
    /**
     * Gera PDF básico usando HTML/CSS para impressão
     */
    public static function generateWorkoutPDF($treino_data, $hash) {
        $html = self::generatePDFHTML($treino_data, $hash);
        
        // Nome do arquivo
        $filename = "treino_" . preg_replace('/[^a-zA-Z0-9]/', '_', $treino_data['treino']['nome']) . "_" . date('Y-m-d') . ".pdf";
        $filepath = "../uploads/pdfs/" . $filename;
        
        // Criar diretório se não existir
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Para uma implementação básica, vamos usar wkhtmltopdf se disponível
        // Ou retornar HTML preparado para impressão
        if (self::isWkhtmltopdfAvailable()) {
            return self::generateWithWkhtmltopdf($html, $filepath);
        } else {
            // Fallback: salvar HTML para impressão
            file_put_contents(str_replace('.pdf', '.html', $filepath), $html);
            return str_replace('.pdf', '.html', $filename);
        }
    }
    
    /**
     * Verifica se wkhtmltopdf está disponível
     */
    private static function isWkhtmltopdfAvailable() {
        $output = shell_exec('wkhtmltopdf --version 2>&1');
        return !empty($output) && strpos($output, 'wkhtmltopdf') !== false;
    }
    
    /**
     * Gera PDF usando wkhtmltopdf
     */
    private static function generateWithWkhtmltopdf($html, $filepath) {
        $tempHtml = tempnam(sys_get_temp_dir(), 'smartbiofit_');
        file_put_contents($tempHtml, $html);
        
        $command = "wkhtmltopdf --page-size A4 --margin-top 0.75in --margin-right 0.75in --margin-bottom 0.75in --margin-left 0.75in {$tempHtml} {$filepath}";
        
        exec($command, $output, $return_var);
        
        unlink($tempHtml);
        
        if ($return_var === 0 && file_exists($filepath)) {
            return basename($filepath);
        }
        
        return false;
    }
    
    /**
     * Gera HTML formatado para PDF
     */
    private static function generatePDFHTML($treino_data, $hash) {
        $treino = $treino_data['treino'];
        $exercicios = $treino_data['exercicios'];
        
        // QR Code para o treino
        $qr_url = "http://" . $_SERVER['HTTP_HOST'] . "/smartbiofit/treino.php?hash=" . $hash;
        $qr_code = QRCodeGenerator::generateInlineQRCode($qr_url, 150);
        
        $html = '
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>' . htmlspecialchars($treino['nome']) . ' - SMARTBIOFIT</title>
            <style>
                @page {
                    size: A4;
                    margin: 2cm;
                }
                
                body {
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                    line-height: 1.4;
                    color: #333;
                    margin: 0;
                    padding: 0;
                }
                
                .header {
                    text-align: center;
                    border-bottom: 3px solid #FF6B35;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                
                .logo {
                    font-size: 24px;
                    font-weight: bold;
                    color: #FF6B35;
                    margin-bottom: 10px;
                }
                
                .workout-title {
                    font-size: 20px;
                    font-weight: bold;
                    margin: 10px 0;
                }
                
                .workout-info {
                    display: flex;
                    justify-content: space-between;
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                }
                
                .info-item {
                    text-align: center;
                }
                
                .info-label {
                    font-weight: bold;
                    color: #666;
                    font-size: 10px;
                    text-transform: uppercase;
                }
                
                .info-value {
                    font-size: 14px;
                    font-weight: bold;
                    color: #FF6B35;
                }
                
                .exercise-category {
                    background: #4285F4;
                    color: white;
                    padding: 8px 15px;
                    margin: 20px 0 10px 0;
                    font-weight: bold;
                    text-transform: uppercase;
                    font-size: 11px;
                }
                
                .exercise-item {
                    border: 1px solid #dee2e6;
                    border-radius: 8px;
                    margin-bottom: 15px;
                    overflow: hidden;
                    page-break-inside: avoid;
                }
                
                .exercise-header {
                    background: #f8f9fa;
                    padding: 10px 15px;
                    border-bottom: 1px solid #dee2e6;
                }
                
                .exercise-name {
                    font-weight: bold;
                    font-size: 14px;
                    margin-bottom: 3px;
                }
                
                .exercise-target {
                    color: #666;
                    font-size: 10px;
                    text-transform: uppercase;
                }
                
                .exercise-params {
                    padding: 15px;
                    display: flex;
                    justify-content: space-around;
                    flex-wrap: wrap;
                }
                
                .param-item {
                    text-align: center;
                    min-width: 80px;
                    margin: 5px;
                }
                
                .param-value {
                    font-weight: bold;
                    font-size: 14px;
                    color: #FF6B35;
                }
                
                .param-label {
                    font-size: 9px;
                    color: #666;
                    text-transform: uppercase;
                }
                
                .exercise-notes {
                    padding: 0 15px 15px 15px;
                    font-size: 11px;
                    color: #666;
                    border-top: 1px solid #f0f0f0;
                    margin-top: 10px;
                    padding-top: 10px;
                }
                
                .qr-section {
                    text-align: center;
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 2px solid #e0e0e0;
                }
                
                .qr-code {
                    margin: 15px 0;
                }
                
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #e0e0e0;
                    font-size: 10px;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="logo">🏋️‍♂️ SMARTBIOFIT</div>
                <div class="workout-title">' . htmlspecialchars($treino['nome']) . '</div>
                <div style="font-size: 12px; color: #666;">
                    Professor: ' . htmlspecialchars($treino['professor']['nome']) . ' | 
                    Gerado em: ' . date('d/m/Y H:i') . '
                </div>
            </div>
            
            <div class="workout-info">
                <div class="info-item">
                    <div class="info-label">Exercícios</div>
                    <div class="info-value">' . $treino_data['estatisticas']['total_exercicios'] . '</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Duração</div>
                    <div class="info-value">' . $treino['duracao_estimada'] . ' min</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Tipo</div>
                    <div class="info-value">' . ucfirst($treino['tipo_treino']) . '</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Nível</div>
                    <div class="info-value">' . ucfirst($treino['nivel_dificuldade']) . '</div>
                </div>
            </div>';
        
        if ($treino['objetivo_principal']) {
            $html .= '
            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong>Objetivo:</strong> ' . htmlspecialchars($treino['objetivo_principal']) . '
            </div>';
        }
        
        // Exercícios agrupados por categoria
        foreach ($treino_data['exercicios_agrupados'] as $categoria => $exercicios_categoria) {
            $html .= '<div class="exercise-category">' . ucfirst($categoria) . '</div>';
            
            foreach ($exercicios_categoria as $exercicio) {
                $html .= '
                <div class="exercise-item">
                    <div class="exercise-header">
                        <div class="exercise-name">' . $exercicio['ordem_execucao'] . '. ' . htmlspecialchars($exercicio['nome']) . '</div>
                        <div class="exercise-target">' . ucfirst($exercicio['grupo_muscular']);
                        
                if ($exercicio['equipamento_necessario']) {
                    $html .= ' • ' . htmlspecialchars($exercicio['equipamento_necessario']);
                }
                
                $html .= '</div>
                    </div>
                    
                    <div class="exercise-params">';
                
                if ($exercicio['series']) {
                    $html .= '
                        <div class="param-item">
                            <div class="param-value">' . $exercicio['series'] . '</div>
                            <div class="param-label">Séries</div>
                        </div>';
                }
                
                if ($exercicio['repeticoes']) {
                    $html .= '
                        <div class="param-item">
                            <div class="param-value">' . htmlspecialchars($exercicio['repeticoes']) . '</div>
                            <div class="param-label">Reps</div>
                        </div>';
                }
                
                if ($exercicio['carga']) {
                    $html .= '
                        <div class="param-item">
                            <div class="param-value">' . htmlspecialchars($exercicio['carga']) . '</div>
                            <div class="param-label">Carga</div>
                        </div>';
                }
                
                if ($exercicio['tempo_descanso']) {
                    $html .= '
                        <div class="param-item">
                            <div class="param-value">' . $exercicio['tempo_descanso'] . 's</div>
                            <div class="param-label">Descanso</div>
                        </div>';
                }
                
                if ($exercicio['tempo_execucao']) {
                    $html .= '
                        <div class="param-item">
                            <div class="param-value">' . gmdate("i:s", $exercicio['tempo_execucao']) . '</div>
                            <div class="param-label">Tempo</div>
                        </div>';
                }
                
                $html .= '</div>';
                
                // Observações do exercício
                if ($exercicio['dicas_execucao'] || $exercicio['observacoes_especificas']) {
                    $html .= '<div class="exercise-notes">';
                    
                    if ($exercicio['dicas_execucao']) {
                        $html .= '<strong>Dicas:</strong> ' . htmlspecialchars($exercicio['dicas_execucao']) . '<br>';
                    }
                    
                    if ($exercicio['observacoes_especificas']) {
                        $html .= '<strong>Observações:</strong> ' . htmlspecialchars($exercicio['observacoes_especificas']);
                    }
                    
                    $html .= '</div>';
                }
                
                $html .= '</div>';
            }
        }
        
        // QR Code para acesso mobile
        if ($qr_code) {
            $html .= '
            <div class="qr-section">
                <h3>Acesso Mobile</h3>
                <p>Escaneie o QR Code para acessar este treino no seu celular:</p>
                <div class="qr-code">
                    <img src="' . $qr_code . '" alt="QR Code do Treino" />
                </div>
                <p style="font-size: 10px; color: #666;">' . $qr_url . '</p>
            </div>';
        }
        
        $html .= '
            <div class="footer">
                <p>SMARTBIOFIT - Sistema de Avaliação Física Digital</p>
                <p>Documento gerado automaticamente em ' . date('d/m/Y \à\s H:i') . '</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
}
?>
