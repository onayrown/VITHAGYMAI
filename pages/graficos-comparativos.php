<?php
/**
 * SMARTBIOFIT - Gráficos Comparativos de Avaliações
 * Milestone 3: Visualização gráfica das avaliações físicas
 */

require_once '../config.php';
require_once '../database.php';

// Verificar autenticação
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'professor')) {
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

$db = Database::getInstance();
$aluno_id = $_GET['aluno_id'] ?? 0;

// Verificar se o aluno existe e pertence ao professor
$aluno = $db->fetch("
    SELECT a.*, u.nome as professor_nome 
    FROM alunos a 
    JOIN usuarios u ON a.professor_id = u.id 
    WHERE a.id = ? AND a.professor_id = ?
", [$aluno_id, $_SESSION['user_id']]);

if (!$aluno) {
    header('Location: alunos.php');
    exit;
}

// Buscar todas as avaliações do aluno
$avaliacoes = $db->fetchAll("
    SELECT * FROM avaliacoes 
    WHERE aluno_id = ? 
    ORDER BY data_avaliacao DESC
", [$aluno_id]);

// Buscar dados de composição corporal
$composicao_dados = $db->fetchAll("
    SELECT cc.*, a.data_avaliacao 
    FROM composicao_corporal cc
    JOIN avaliacoes a ON cc.avaliacao_id = a.id
    WHERE a.aluno_id = ?
    ORDER BY a.data_avaliacao ASC
", [$aluno_id]);

// Buscar dados de perimetria
$perimetria_dados = $db->fetchAll("
    SELECT p.*, a.data_avaliacao 
    FROM perimetria p
    JOIN avaliacoes a ON p.avaliacao_id = a.id
    WHERE a.aluno_id = ?
    ORDER BY a.data_avaliacao ASC
", [$aluno_id]);

// Buscar dados de VO2Max
$vo2max_dados = $db->fetchAll("
    SELECT v.*, a.data_avaliacao 
    FROM vo2max v
    JOIN avaliacoes a ON v.avaliacao_id = a.id
    WHERE a.aluno_id = ?
    ORDER BY a.data_avaliacao ASC
", [$aluno_id]);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gráficos Comparativos - <?php echo htmlspecialchars($aluno['nome']); ?> - SMARTBIOFIT</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .charts-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .chart-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .chart-header {
            display: flex;
            align-items: center;
            justify-content: between;
            margin-bottom: 1.5rem;
        }
        
        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--azul-cobalto);
            margin: 0;
        }
        
        .chart-subtitle {
            font-size: 0.9rem;
            color: #6c757d;
            margin: 0.25rem 0 0 0;
        }
        
        .chart-canvas {
            position: relative;
            height: 400px;
            margin-top: 1rem;
        }
        
        .no-data {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .stat-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 600;
            color: var(--azul-cobalto);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .stat-change {
            font-size: 0.8rem;
            margin-top: 0.5rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }
        
        .stat-change.positive {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .stat-change.negative {
            background: #ffebee;
            color: #c62828;
        }
        
        .stat-change.neutral {
            background: #f8f9fa;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .chart-canvas {
                height: 300px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
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
            <a href="alunos.php">Alunos</a> > 
            <a href="avaliacoes.php?aluno_id=<?php echo $aluno_id; ?>">Avaliações</a> > 
            <span>Gráficos Comparativos</span>
        </nav>
        
        <!-- Informações do Aluno -->
        <div class="patient-info">
            <h1>📊 Gráficos Comparativos</h1>
            <p><strong>Aluno:</strong> <?php echo htmlspecialchars($aluno['nome']); ?></p>
            <p><strong>Total de Avaliações:</strong> <?php echo count($avaliacoes); ?></p>
        </div>
        
        <?php if (empty($avaliacoes)): ?>
        <div class="card">
            <div class="card-body no-data">
                <h3>📈 Nenhuma avaliação encontrada</h3>
                <p>Este aluno ainda não possui avaliações para gerar gráficos comparativos.</p>
                <a href="avaliacoes.php?aluno_id=<?php echo $aluno_id; ?>" class="btn btn-primary">Criar Nova Avaliação</a>
            </div>
        </div>
        <?php else: ?>
        
        <!-- Estatísticas Resumidas -->
        <div class="stats-grid">
            <?php 
            // Calcular estatísticas
            $primeira_composicao = !empty($composicao_dados) ? $composicao_dados[0] : null;
            $ultima_composicao = !empty($composicao_dados) ? end($composicao_dados) : null;
            
            $primeiro_vo2 = !empty($vo2max_dados) ? $vo2max_dados[0] : null;
            $ultimo_vo2 = !empty($vo2max_dados) ? end($vo2max_dados) : null;
            
            if ($primeira_composicao && $ultima_composicao && count($composicao_dados) > 1): 
                $diff_gordura = $ultima_composicao['percentual_gordura'] - $primeira_composicao['percentual_gordura'];
                $diff_peso = $ultima_composicao['peso'] - $primeira_composicao['peso'];
            ?>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($ultima_composicao['percentual_gordura'], 1); ?>%</div>
                <div class="stat-label">% Gordura Atual</div>
                <div class="stat-change <?php echo $diff_gordura < 0 ? 'positive' : ($diff_gordura > 0 ? 'negative' : 'neutral'); ?>">
                    <?php echo $diff_gordura > 0 ? '+' : ''; ?><?php echo number_format($diff_gordura, 1); ?>%
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($ultima_composicao['peso'], 1); ?> kg</div>
                <div class="stat-label">Peso Atual</div>
                <div class="stat-change <?php echo $diff_peso < 0 ? 'positive' : ($diff_peso > 0 ? 'negative' : 'neutral'); ?>">
                    <?php echo $diff_peso > 0 ? '+' : ''; ?><?php echo number_format($diff_peso, 1); ?> kg
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($primeiro_vo2 && $ultimo_vo2 && count($vo2max_dados) > 1): 
                $diff_vo2 = $ultimo_vo2['vo2max_calculado'] - $primeiro_vo2['vo2max_calculado'];
            ?>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($ultimo_vo2['vo2max_calculado'], 1); ?></div>
                <div class="stat-label">VO2Max Atual</div>
                <div class="stat-change <?php echo $diff_vo2 > 0 ? 'positive' : ($diff_vo2 < 0 ? 'negative' : 'neutral'); ?>">
                    <?php echo $diff_vo2 > 0 ? '+' : ''; ?><?php echo number_format($diff_vo2, 1); ?> ml/kg/min
                </div>
            </div>
            <?php endif; ?>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo count($avaliacoes); ?></div>
                <div class="stat-label">Total de Avaliações</div>
                <div class="stat-change neutral">
                    Última: <?php echo date('d/m/Y', strtotime($avaliacoes[0]['data_avaliacao'])); ?>
                </div>
            </div>
        </div>
        
        <!-- Gráficos -->
        <div class="charts-container">
            
            <!-- Gráfico de Composição Corporal -->
            <?php if (!empty($composicao_dados)): ?>
            <div class="chart-card">
                <div class="chart-header">
                    <div>
                        <h3 class="chart-title">📏 Evolução da Composição Corporal</h3>
                        <p class="chart-subtitle">Percentual de gordura e peso corporal ao longo do tempo</p>
                    </div>
                </div>
                <div class="chart-canvas">
                    <canvas id="composicaoChart"></canvas>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Gráfico de Perimetria -->
            <?php if (!empty($perimetria_dados)): ?>
            <div class="chart-card">
                <div class="chart-header">
                    <div>
                        <h3 class="chart-title">📐 Evolução das Medidas Corporais</h3>
                        <p class="chart-subtitle">Principais perímetros corporais</p>
                    </div>
                </div>
                <div class="chart-canvas">
                    <canvas id="perimetriaChart"></canvas>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Gráfico de VO2Max -->
            <?php if (!empty($vo2max_dados)): ?>
            <div class="chart-card">
                <div class="chart-header">
                    <div>
                        <h3 class="chart-title">❤️ Evolução do VO2 Máximo</h3>
                        <p class="chart-subtitle">Capacidade cardiorrespiratória</p>
                    </div>
                </div>
                <div class="chart-canvas">
                    <canvas id="vo2maxChart"></canvas>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="form-actions">
            <a href="avaliacoes.php?aluno_id=<?php echo $aluno_id; ?>" class="btn btn-primary">← Voltar para Avaliações</a>
            <button onclick="window.print()" class="btn btn-secondary">🖨️ Imprimir Gráficos</button>
        </div>
        
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Configuração global dos gráficos
        Chart.defaults.font.family = 'Inter, sans-serif';
        Chart.defaults.plugins.legend.position = 'top';
        Chart.defaults.plugins.legend.align = 'start';
        
        // Cores do tema
        const colors = {
            primary: '#2F80ED',
            secondary: '#27AE60',
            warning: '#F2C94C',
            danger: '#EB5757',
            gray: '#4F4F4F',
            lightGray: '#F2F2F2'
        };
        
        <?php if (!empty($composicao_dados)): ?>
        // Dados de Composição Corporal
        const composicaoData = <?php echo json_encode($composicao_dados); ?>;
        const composicaoLabels = composicaoData.map(item => new Date(item.data_avaliacao).toLocaleDateString('pt-BR'));
        
        new Chart(document.getElementById('composicaoChart'), {
            type: 'line',
            data: {
                labels: composicaoLabels,
                datasets: [{
                    label: '% Gordura',
                    data: composicaoData.map(item => parseFloat(item.percentual_gordura)),
                    borderColor: colors.primary,
                    backgroundColor: colors.primary + '20',
                    tension: 0.4,
                    yAxisID: 'y'
                }, {
                    label: 'Peso (kg)',
                    data: composicaoData.map(item => parseFloat(item.peso)),
                    borderColor: colors.secondary,
                    backgroundColor: colors.secondary + '20',
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Data da Avaliação'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Percentual de Gordura (%)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Peso (kg)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const item = composicaoData[context.dataIndex];
                                if (context.datasetIndex === 0) {
                                    return `IMC: ${parseFloat(item.imc).toFixed(1)}`;
                                }
                                return `Massa Magra: ${parseFloat(item.massa_magra).toFixed(1)} kg`;
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
        
        <?php if (!empty($perimetria_dados)): ?>
        // Dados de Perimetria
        const perimetriaData = <?php echo json_encode($perimetria_dados); ?>;
        const perimetriaLabels = perimetriaData.map(item => new Date(item.data_avaliacao).toLocaleDateString('pt-BR'));
        
        new Chart(document.getElementById('perimetriaChart'), {
            type: 'line',
            data: {
                labels: perimetriaLabels,
                datasets: [{
                    label: 'Cintura (cm)',
                    data: perimetriaData.map(item => parseFloat(item.cintura) || null),
                    borderColor: colors.primary,
                    backgroundColor: colors.primary + '20',
                    tension: 0.4
                }, {
                    label: 'Quadril (cm)',
                    data: perimetriaData.map(item => parseFloat(item.quadril) || null),
                    borderColor: colors.secondary,
                    backgroundColor: colors.secondary + '20',
                    tension: 0.4
                }, {
                    label: 'Braço D (cm)',
                    data: perimetriaData.map(item => parseFloat(item.braco_direito) || null),
                    borderColor: colors.warning,
                    backgroundColor: colors.warning + '20',
                    tension: 0.4
                }, {
                    label: 'Coxa D (cm)',
                    data: perimetriaData.map(item => parseFloat(item.coxa_direita) || null),
                    borderColor: colors.danger,
                    backgroundColor: colors.danger + '20',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Data da Avaliação'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Medidas (cm)'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        filter: function(tooltipItem) {
                            return tooltipItem.parsed.y !== null;
                        }
                    }
                }
            }
        });
        <?php endif; ?>
        
        <?php if (!empty($vo2max_dados)): ?>
        // Dados de VO2Max
        const vo2maxData = <?php echo json_encode($vo2max_dados); ?>;
        const vo2maxLabels = vo2maxData.map(item => new Date(item.data_avaliacao).toLocaleDateString('pt-BR'));
        
        new Chart(document.getElementById('vo2maxChart'), {
            type: 'line',
            data: {
                labels: vo2maxLabels,
                datasets: [{
                    label: 'VO2Max (ml/kg/min)',
                    data: vo2maxData.map(item => parseFloat(item.vo2max_calculado)),
                    borderColor: colors.danger,
                    backgroundColor: colors.danger + '20',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Data da Avaliação'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'VO2Max (ml/kg/min)'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const item = vo2maxData[context.dataIndex];
                                return [
                                    `Classificação: ${item.classificacao}`,
                                    `Teste: ${item.tipo_teste.charAt(0).toUpperCase() + item.tipo_teste.slice(1)}`
                                ];
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
    
    <style media="print">
        .container { max-width: none; }
        .form-actions { display: none; }
        .breadcrumb { display: none; }
        .chart-canvas { height: 300px !important; }
    </style>
</body>
</html>
