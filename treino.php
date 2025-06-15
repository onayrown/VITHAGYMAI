<?php
/**
 * VithaGymAI - Visualização Mobile do Treino
 * Interface otimizada para visualização de treinos em dispositivos móveis
 */

require_once 'config.php';
require_once 'database.php';

$hash = $_GET['hash'] ?? null;
$treino_data = null;
$error = null;

if (!$hash) {
    $error = 'Link de acesso inválido';
} else {
    try {
        $db = Database::getInstance();
        
        // Buscar dados do treino via API
        $api_url = "api/treino-detalhes.php?hash=" . urlencode($hash);
        $api_response = file_get_contents($api_url);
        $treino_data = json_decode($api_response, true);
        
        if (!$treino_data || !$treino_data['success']) {
            $error = $treino_data['error'] ?? 'Treino não encontrado';
            $treino_data = null;
        }
    } catch (Exception $e) {
        $error = 'Erro ao carregar treino: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $treino_data ? htmlspecialchars($treino_data['treino']['nome']) . ' - VithaGymAI' : 'VithaGymAI - Treino' ?></title>
      <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#FF6B35">
    
    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    
    <!-- Estilos específicos para mobile -->
    <style>
        :root {
            --primary-color: #FF6B35;
            --secondary-color: #4285F4;
            --success-color: #34A853;
            --warning-color: #FBBC05;
            --danger-color: #EA4335;
            --dark-color: #1a1a1a;
            --light-color: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        .workout-container {
            background: white;
            min-height: 100vh;
        }
        
        .workout-header {
            background: var(--primary-color);
            color: white;
            padding: 1.5rem 1rem;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .workout-info {
            padding: 1rem;
            background: var(--light-color);
            border-bottom: 1px solid #dee2e6;
        }
        
        .exercise-card {
            background: white;
            margin: 0.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            border-left: 4px solid var(--primary-color);
        }
        
        .exercise-header {
            padding: 1rem;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .exercise-body {
            padding: 1rem;
        }
        
        .exercise-number {
            background: var(--primary-color);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        
        .exercise-params {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .param-item {
            text-align: center;
            padding: 0.5rem;
            background: var(--light-color);
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .param-value {
            font-weight: bold;
            color: var(--primary-color);
            display: block;
        }
        
        .param-label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
        }
        
        .category-header {
            background: var(--secondary-color);
            color: white;
            padding: 0.75rem 1rem;
            margin: 1rem 0 0.5rem 0;
            border-radius: 8px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }
        
        .floating-actions {
            position: fixed;
            bottom: 1rem;
            right: 1rem;
            z-index: 1000;
        }
        
        .fab-button {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 56px;
            height: 56px;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            margin-left: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .fab-button:hover {
            transform: scale(1.1);
            background: #e55a2b;
        }
        
        .workout-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 1rem;
            padding: 1rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
        }
        
        .exercise-completed {
            opacity: 0.7;
            background: #e8f5e8;
        }
        
        .exercise-completed .exercise-number {
            background: var(--success-color);
        }
        
        .timer-display {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            text-align: center;
            padding: 1rem;
            background: var(--light-color);
            border-radius: 12px;
            margin: 1rem;
        }
        
        .error-container {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
        
        .error-icon {
            font-size: 4rem;
            color: var(--danger-color);
            margin-bottom: 1rem;
        }
        
        @media (max-width: 576px) {
            .exercise-card {
                margin: 0.25rem;
            }
            
            .workout-header {
                padding: 1rem 0.5rem;
            }
            
            .exercise-params {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php if ($error): ?>
    <!-- Página de Erro -->
    <div class="workout-container">
        <div class="workout-header">
            <h1><i class="fas fa-dumbbell"></i> VithaGymAI</h1>
        </div>
        <div class="error-container">
            <i class="fas fa-exclamation-triangle error-icon"></i>
            <h3>Oops! Algo deu errado</h3>
            <p><?= htmlspecialchars($error) ?></p>
            <p class="text-muted">Verifique o link ou entre em contato com seu instrutor.</p>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Página do Treino -->
    <div class="workout-container">
        <!-- Header do Treino -->
        <div class="workout-header">
            <h1><i class="fas fa-dumbbell"></i> <?= htmlspecialchars($treino_data['treino']['nome']) ?></h1>
            <?php if (isset($treino_data['aluno'])): ?>
            <p class="mb-0">Para: <strong><?= htmlspecialchars($treino_data['aluno']['nome']) ?></strong></p>
            <?php endif; ?>
        </div>
        
        <!-- Informações do Treino -->
        <div class="workout-info">
            <div class="row text-center">
                <div class="col-4">
                    <div class="stat-item">
                        <div class="stat-value"><?= $treino_data['estatisticas']['total_exercicios'] ?></div>
                        <div class="stat-label">Exercícios</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="stat-item">
                        <div class="stat-value"><?= $treino_data['treino']['duracao_estimada'] ?>'</div>
                        <div class="stat-label">Duração</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="stat-item">
                        <div class="stat-value"><?= ucfirst($treino_data['treino']['nivel_dificuldade']) ?></div>
                        <div class="stat-label">Nível</div>
                    </div>
                </div>
            </div>
            
            <?php if ($treino_data['treino']['objetivo_principal']): ?>
            <div class="mt-3">
                <strong>Objetivo:</strong> <?= htmlspecialchars($treino_data['treino']['objetivo_principal']) ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($treino_data['aluno']['observacoes_individuais']) && $treino_data['aluno']['observacoes_individuais']): ?>
            <div class="mt-2">
                <strong>Observações:</strong> <?= htmlspecialchars($treino_data['aluno']['observacoes_individuais']) ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Timer de Descanso (inicialmente oculto) -->
        <div id="timer-container" class="d-none">
            <div class="timer-display">
                <div>Descanso</div>
                <div id="timer-display">00:00</div>
                <button class="btn btn-sm btn-outline-primary" onclick="pararTimer()">Parar</button>
            </div>
        </div>
        
        <!-- Lista de Exercícios -->
        <div class="exercises-container">
            <?php foreach ($treino_data['exercicios_agrupados'] as $categoria => $exercicios): ?>
            <div class="category-header">
                <i class="fas fa-tag"></i> <?= ucfirst($categoria) ?>
            </div>
            
            <?php foreach ($exercicios as $exercicio): ?>
            <div class="exercise-card" data-exercise-id="<?= $exercicio['id'] ?>">
                <div class="exercise-header">
                    <div class="d-flex align-items-center">
                        <div class="exercise-number"><?= $exercicio['ordem_execucao'] ?></div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1"><?= htmlspecialchars($exercicio['nome']) ?></h6>
                            <small class="text-muted">
                                <?= ucfirst($exercicio['grupo_muscular']) ?>
                                <?php if ($exercicio['equipamento_necessario']): ?>
                                • <?= htmlspecialchars($exercicio['equipamento_necessario']) ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        <button class="btn btn-sm btn-link" onclick="toggleExercise(<?= $exercicio['id'] ?>)">
                            <i class="fas fa-check-circle text-success d-none" id="check-<?= $exercicio['id'] ?>"></i>
                            <i class="far fa-circle text-muted" id="uncheck-<?= $exercicio['id'] ?>"></i>
                        </button>
                    </div>
                </div>
                
                <div class="exercise-body">
                    <div class="exercise-params">
                        <?php if ($exercicio['series']): ?>
                        <div class="param-item">
                            <span class="param-value"><?= $exercicio['series'] ?></span>
                            <span class="param-label">Séries</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($exercicio['repeticoes']): ?>
                        <div class="param-item">
                            <span class="param-value"><?= htmlspecialchars($exercicio['repeticoes']) ?></span>
                            <span class="param-label">Reps</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($exercicio['carga']): ?>
                        <div class="param-item">
                            <span class="param-value"><?= htmlspecialchars($exercicio['carga']) ?></span>
                            <span class="param-label">Carga</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($exercicio['tempo_descanso']): ?>
                        <div class="param-item">
                            <span class="param-value"><?= $exercicio['tempo_descanso'] ?>s</span>
                            <span class="param-label">Descanso</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($exercicio['tempo_execucao']): ?>
                        <div class="param-item">
                            <span class="param-value"><?= gmdate("i:s", $exercicio['tempo_execucao']) ?></span>
                            <span class="param-label">Tempo</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($exercicio['distancia']): ?>
                        <div class="param-item">
                            <span class="param-value"><?= $exercicio['distancia'] ?>m</span>
                            <span class="param-label">Distância</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($exercicio['dicas_execucao']): ?>
                    <div class="mt-3">
                        <strong>Dicas:</strong> <?= htmlspecialchars($exercicio['dicas_execucao']) ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($exercicio['observacoes_especificas']): ?>
                    <div class="mt-2">
                        <strong>Observações:</strong> <?= htmlspecialchars($exercicio['observacoes_especificas']) ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Botões de Ação do Exercício -->
                    <div class="mt-3 d-flex gap-2 flex-wrap">
                        <?php if ($exercicio['tempo_descanso']): ?>
                        <button class="btn btn-sm btn-warning" onclick="iniciarDescanso(<?= $exercicio['tempo_descanso'] ?>)">
                            <i class="fas fa-clock"></i> Descanso
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($exercicio['video_url']): ?>
                        <button class="btn btn-sm btn-info" onclick="verVideo('<?= htmlspecialchars($exercicio['video_url']) ?>')">
                            <i class="fas fa-play"></i> Vídeo
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        
        <!-- Botão de Finalizar Treino -->
        <div class="p-3">
            <button class="btn btn-success w-100 btn-lg" onclick="finalizarTreino()">
                <i class="fas fa-flag-checkered"></i> Finalizar Treino
            </button>
        </div>
        
        <!-- Espaço para os botões flutuantes -->
        <div style="height: 80px;"></div>
    </div>
    
    <!-- Botões Flutuantes -->
    <div class="floating-actions">
        <button class="fab-button" onclick="cronometro()" title="Cronômetro">
            <i class="fas fa-stopwatch"></i>
        </button>
        <button class="fab-button" onclick="compartilhar()" title="Compartilhar">
            <i class="fas fa-share-alt"></i>
        </button>
    </div>
    <?php endif; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Service Worker para PWA -->
    <script>
        // Registrar Service Worker para PWA
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js').catch(console.error);
        }
        
        // Estado do treino
        let exerciciosCompletados = [];
        let timerAtivo = null;
        let cronometroIniciado = false;
        let cronometroTempo = 0;
        
        // Carregar estado salvo
        const estadoSalvo = localStorage.getItem('treino-<?= $hash ?>');
        if (estadoSalvo) {
            exerciciosCompletados = JSON.parse(estadoSalvo);
            exerciciosCompletados.forEach(id => {
                marcarExercicioCompleto(id, false);
            });
        }
        
        function toggleExercise(exercicioId) {
            const index = exerciciosCompletados.indexOf(exercicioId);
            if (index > -1) {
                exerciciosCompletados.splice(index, 1);
                marcarExercicioIncompleto(exercicioId);
            } else {
                exerciciosCompletados.push(exercicioId);
                marcarExercicioCompleto(exercicioId, true);
            }
            
            // Salvar estado
            localStorage.setItem('treino-<?= $hash ?>', JSON.stringify(exerciciosCompletados));
        }
        
        function marcarExercicioCompleto(exercicioId, comVibracao = false) {
            const card = document.querySelector(`[data-exercise-id="${exercicioId}"]`);
            const checkIcon = document.getElementById(`check-${exercicioId}`);
            const uncheckIcon = document.getElementById(`uncheck-${exercicioId}`);
            
            card.classList.add('exercise-completed');
            checkIcon.classList.remove('d-none');
            uncheckIcon.classList.add('d-none');
            
            if (comVibracao && 'vibrate' in navigator) {
                navigator.vibrate(100);
            }
        }
        
        function marcarExercicioIncompleto(exercicioId) {
            const card = document.querySelector(`[data-exercise-id="${exercicioId}"]`);
            const checkIcon = document.getElementById(`check-${exercicioId}`);
            const uncheckIcon = document.getElementById(`uncheck-${exercicioId}`);
            
            card.classList.remove('exercise-completed');
            checkIcon.classList.add('d-none');
            uncheckIcon.classList.remove('d-none');
        }
        
        function iniciarDescanso(segundos) {
            if (timerAtivo) {
                clearInterval(timerAtivo);
            }
            
            const container = document.getElementById('timer-container');
            const display = document.getElementById('timer-display');
            
            container.classList.remove('d-none');
            
            let tempoRestante = segundos;
            
            function atualizarTimer() {
                const minutos = Math.floor(tempoRestante / 60);
                const segs = tempoRestante % 60;
                display.textContent = `${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
                
                if (tempoRestante <= 0) {
                    clearInterval(timerAtivo);
                    container.classList.add('d-none');
                    
                    // Notificação de fim do descanso
                    if ('vibrate' in navigator) {
                        navigator.vibrate([200, 100, 200]);
                    }
                    
                    alert('Tempo de descanso terminado!');
                    return;
                }
                
                tempoRestante--;
            }
            
            atualizarTimer();
            timerAtivo = setInterval(atualizarTimer, 1000);
        }
        
        function pararTimer() {
            if (timerAtivo) {
                clearInterval(timerAtivo);
                timerAtivo = null;
            }
            document.getElementById('timer-container').classList.add('d-none');
        }
        
        function cronometro() {
            // Implementar cronômetro geral do treino
            alert('Funcionalidade de cronômetro em desenvolvimento');
        }
          function compartilhar() {
            const url = window.location.href;
            const treinoNome = "<?= htmlspecialchars($treino_data['treino']['nome']) ?>";
            const professorNome = "<?= htmlspecialchars($treino_data['treino']['professor_nome']) ?>";
            
            // Verificar se o navegador suporta Web Share API
            if (navigator.share) {
                navigator.share({
                    title: `VithaGymAI - ${treinoNome}`,
                    text: `Meu treino personalizado: ${treinoNome}`,
                    url: url
                }).catch(console.error);
            } else {
                // Mostrar opções de compartilhamento
                mostrarOpcoesCompartilhamento(url, treinoNome, professorNome);
            }
        }
        
        function mostrarOpcoesCompartilhamento(url, treinoNome, professorNome) {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-share-alt"></i> Compartilhar Treino</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <div class="mb-3">
                                <div id="qr-code-share" class="d-flex justify-content-center mb-3"></div>
                                <small class="text-muted">Escaneie o QR Code para acesso rápido</small>
                            </div>
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" value="${url}" readonly id="link-input">
                                <button class="btn btn-outline-secondary" onclick="copiarLink()">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-success" onclick="compartilharWhatsApp('${url}', '${treinoNome}', '${professorNome}')">
                                    <i class="fab fa-whatsapp"></i> Compartilhar no WhatsApp
                                </button>
                                <button class="btn btn-primary" onclick="compartilharTelegram('${url}', '${treinoNome}')">
                                    <i class="fab fa-telegram"></i> Compartilhar no Telegram
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
            
            // Gerar QR Code
            if (typeof QRCode !== 'undefined') {
                QRCode.toCanvas(document.getElementById('qr-code-share'), url, {
                    width: 150,
                    margin: 2,
                    color: {
                        dark: '#000000',
                        light: '#FFFFFF'
                    }
                });
            }
            
            // Remover modal após fechar
            modal.addEventListener('hidden.bs.modal', () => {
                document.body.removeChild(modal);
            });
        }
        
        function copiarLink() {
            const linkInput = document.getElementById('link-input');
            linkInput.select();
            linkInput.setSelectionRange(0, 99999);
            
            try {
                document.execCommand('copy');
                // Feedback visual
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i>';
                btn.classList.add('btn-success');
                btn.classList.remove('btn-outline-secondary');
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-secondary');
                }, 2000);
                
            } catch (err) {
                console.error('Erro ao copiar:', err);
                alert('Erro ao copiar link');
            }
        }
        
        function compartilharWhatsApp(url, treinoNome, professorNome) {
            const mensagem = `🏋️‍♂️ *VithaGymAI - Meu Treino*\n\n📋 *Treino:* ${treinoNome}\n👨‍🏫 *Instrutor:* ${professorNome}\n\n🔗 *Acesse:* ${url}\n\n💪 _Vamos treinar juntos!_`;
            const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(mensagem)}`;
            window.open(whatsappUrl, '_blank');
        }
        
        function compartilharTelegram(url, treinoNome) {
            const mensagem = `🏋️‍♂️ VithaGymAI - ${treinoNome}\n\n${url}`;
            const telegramUrl = `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(mensagem)}`;
            window.open(telegramUrl, '_blank');
        }
        
        function verVideo(url) {
            window.open(url, '_blank');
        }
        
        function finalizarTreino() {
            const totalExercicios = <?= $treino_data['estatisticas']['total_exercicios'] ?>;
            const completados = exerciciosCompletados.length;
            const percentual = Math.round((completados / totalExercicios) * 100);
            
            if (confirm(`Você completou ${completados} de ${totalExercicios} exercícios (${percentual}%). Deseja finalizar o treino?`)) {
                // Enviar dados de execução para o servidor
                fetch('api/salvar-execucao.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        hash: '<?= $hash ?>',
                        exercicios_realizados: exerciciosCompletados,
                        tempo_total: cronometroTempo, // Implementar cronômetro
                        observacoes_aluno: ''
                    })
                }).then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Treino finalizado com sucesso! Parabéns!');
                        // Limpar estado local
                        localStorage.removeItem('treino-<?= $hash ?>');
                    } else {
                        alert('Erro ao salvar execução do treino');
                    }
                }).catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao finalizar treino');
                });
            }
        }
    </script>
</body>
</html>
