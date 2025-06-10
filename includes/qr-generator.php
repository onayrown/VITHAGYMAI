<?php
/**
 * SMARTBIOFIT - Gerador de QR Code
 * Sistema para gerar QR codes para compartilhamento de treinos
 */

class QRCodeGenerator {
      /**
     * Gera QR code usando API externa (QR Server)
     */
    public static function generateQRCode($data, $size = 200) {
        // Encode dos dados
        $encodedData = urlencode($data);
        
        // URL da API do QR Server (mais confiável que Google Charts)
        $url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encodedData}";
        
        return $url;
    }
    
    /**
     * Gera QR code inline (base64) para usar em HTML - Versão melhorada
     */
    public static function generateInlineQRCodeImproved($data, $size = 200) {
        $url = self::generateQRCode($data, $size);
        
        // Tentar primeiro com cURL
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_USERAGENT, 'SMARTBIOFIT/1.0');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($imageData !== false && $httpCode == 200) {
                $base64 = base64_encode($imageData);
                return "data:image/png;base64,{$base64}";
            }
        }
        
        // Fallback para file_get_contents
        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'SMARTBIOFIT/1.0'
            ]
        ]);
        
        $imageData = @file_get_contents($url, false, $context);
        if ($imageData !== false) {
            $base64 = base64_encode($imageData);
            return "data:image/png;base64,{$base64}";
        }
        
        return null;
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
    
    /**
     * Gera link de compartilhamento para treino
     */
    public static function generateWorkoutShareLink($hash, $baseUrl) {
        return $baseUrl . '/treino.php?hash=' . $hash;
    }
    
    /**
     * Gera QR code para treino específico
     */
    public static function generateWorkoutQRCode($hash, $baseUrl, $size = 200) {
        $shareLink = self::generateWorkoutShareLink($hash, $baseUrl);
        return self::generateQRCode($shareLink, $size);
    }
    
    /**
     * Gera WhatsApp share link
     */
    public static function generateWhatsAppLink($hash, $baseUrl, $treinoNome = '', $professorNome = '') {
        $shareLink = self::generateWorkoutShareLink($hash, $baseUrl);
        
        $message = "🏋️‍♂️ *SMARTBIOFIT - Novo Treino*\n\n";
        
        if ($treinoNome) {
            $message .= "📋 *Treino:* {$treinoNome}\n";
        }
        
        if ($professorNome) {
            $message .= "👨‍🏫 *Professor:* {$professorNome}\n";
        }
        
        $message .= "\n🔗 *Acesse seu treino:*\n{$shareLink}\n\n";
        $message .= "💡 _Dica: Salve este link nos seus favoritos para acesso rápido!_";
        
        return 'https://wa.me/?text=' . urlencode($message);
    }
      /**
     * Verifica se uma URL de QR code está acessível
     */
    public static function isQRCodeAccessible($url) {
        // Tentar com cURL primeiro
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $httpCode == 200;
        }
        
        // Fallback para get_headers
        $headers = @get_headers($url);
        return $headers && strpos($headers[0], '200 OK') !== false;
    }
    
    /**
     * Testa geração de QR Code e retorna diagnóstico
     */
    public static function testQRCodeGeneration($data = 'SMARTBIOFIT_TEST', $size = 150) {
        $result = [
            'success' => false,
            'url' => '',
            'base64' => '',
            'method' => '',
            'error' => '',
            'size' => 0
        ];
        
        try {
            $url = self::generateQRCode($data, $size);
            $result['url'] = $url;
            
            // Testar cURL
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_USERAGENT, 'SMARTBIOFIT/1.0');
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                
                $imageData = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                if ($imageData !== false && $httpCode == 200) {
                    $result['success'] = true;
                    $result['method'] = 'cURL';
                    $result['base64'] = base64_encode($imageData);
                    $result['size'] = strlen($imageData);
                    return $result;
                } else {
                    $result['error'] = "cURL failed: HTTP $httpCode, Error: $curlError";
                }
            }
            
            // Testar file_get_contents
            $context = stream_context_create([
                'http' => [
                    'timeout' => 15,
                    'user_agent' => 'SMARTBIOFIT/1.0'
                ]
            ]);
            
            $imageData = @file_get_contents($url, false, $context);
            if ($imageData !== false) {
                $result['success'] = true;
                $result['method'] = 'file_get_contents';
                $result['base64'] = base64_encode($imageData);
                $result['size'] = strlen($imageData);
                return $result;
            } else {
                $lastError = error_get_last();
                $result['error'] .= " | file_get_contents failed: " . ($lastError['message'] ?? 'Unknown error');
            }
            
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }
        
        return $result;
    }
}
?>
