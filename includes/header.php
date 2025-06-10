<?php
/**
 * SMARTBIOFIT - Header Comum
 */

// Inclui configurações se não foram incluídas ainda
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config.php';
}

// Verifica se há sessão ativa (exceto em páginas de login)
$currentPage = basename($_SERVER['PHP_SELF']);
$publicPages = ['login.php', 'register.php', 'reset-password.php'];

if (!in_array($currentPage, $publicPages)) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

// Informações do usuário logado
$user = null;
if (isset($_SESSION['user_id'])) {
    try {
        $db = Database::getInstance();
        $user = $db->fetch("SELECT * FROM usuarios WHERE id = ? AND ativo = TRUE", [$_SESSION['user_id']]);
        
        if (!$user) {
            session_destroy();
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
        
        // Atualiza último acesso
        $db->execute("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?", [$_SESSION['user_id']]);
        
    } catch (Exception $e) {
        logError("Erro ao carregar dados do usuário: " . $e->getMessage());
    }
}

// Função para verificar permissões
function hasPermission($requiredType) {
    global $user;
    if (!$user) return false;
    
    $hierarchy = ['admin' => 3, 'professor' => 2, 'aluno' => 1];
    $userLevel = $hierarchy[$user['tipo']] ?? 0;
    $requiredLevel = $hierarchy[$requiredType] ?? 0;
    
    return $userLevel >= $requiredLevel;
}

// Função para obter o nome da página atual
function getCurrentPageTitle() {
    $page = basename($_SERVER['PHP_SELF'], '.php');
    $titles = [
        'index' => 'Dashboard',
        'login' => 'Login',
        'register' => 'Cadastro',
        'alunos' => 'Alunos',
        'avaliacoes' => 'Avaliações',
        'treinos' => 'Treinos',
        'exercicios' => 'Exercícios',
        'treino-alunos' => 'Treinos dos Alunos',
        'profile' => 'Perfil',
        'admin' => 'Administração'
    ];
    
    return $titles[$page] ?? 'SMARTBIOFIT';
}

$pageTitle = getCurrentPageTitle();
?>
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="description" content="SMARTBIOFIT - Aplicativo Web de Avaliação Física Profissional">
    <meta name="author" content="SMARTBIOFIT">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo APP_URL; ?>/assets/images/logo-smartbiofit.png">
      <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Configuração do Tailwind CSS
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'ui-sans-serif', 'system-ui'],
                    },
                    colors: {
                        'cobalt': {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        'health': {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.3s ease-out',
                        'bounce-subtle': 'bounceSubtle 0.6s ease-in-out',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        bounceSubtle: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-5px)' },
                        }
                    }
                }
            }
        }
    </script>
      <!-- Google Fonts for Premium Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Flowbite -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
      <!-- Font Awesome 6 - Multiple CDN fallbacks -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" 
          onerror="this.onerror=null;this.href='https://use.fontawesome.com/releases/v6.5.1/css/all.css';">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/webfonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin>
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
      <!-- Custom Premium Styles -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/ios-premium.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/smartbiofit-premium.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/treino-mobile-style.css">
    
    <!-- Meta tags para PWA -->
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="SMARTBIOFIT">
      <!-- Preload critical resources -->
    <link rel="preload" href="<?php echo APP_URL; ?>/assets/js/app.js" as="script">
</head>
<body class="bg-gray-50 font-sans antialiased">
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-white z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="text-center">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-cobalt-600"></div>
            <p class="mt-4 text-gray-600 font-medium">Carregando...</p>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2 max-w-sm"></div>
    
    <?php if ($user): ?>
    <!-- Mobile Header -->
    <header class="lg:hidden bg-white border-b border-gray-200 sticky top-0 z-40">        <div class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center space-x-3">
                <img src="<?php echo APP_URL; ?>/assets/images/logo-smartbiofit.png" alt="SMARTBIOFIT" class="h-16 w-auto max-w-16 rounded-lg object-contain">
                <div>
                    <h1 class="text-lg font-bold text-gray-900"><?php echo $pageTitle; ?></h1>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user['nome']); ?></p>
                </div>
            </div><button id="mobile-menu-button" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
    </header>

    <!-- Desktop Sidebar -->
    <aside class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col bg-white border-r border-gray-200">
        <div class="flex flex-col flex-grow pt-5 pb-4 overflow-y-auto">            <div class="flex items-center flex-shrink-0 px-6">
                <img src="<?php echo APP_URL; ?>/assets/images/logo-smartbiofit.png" alt="SMARTBIOFIT" class="h-12 w-auto max-w-12 rounded-xl object-contain">
                <div class="ml-3">
                    <h1 class="text-xl font-bold text-gray-900">SMARTBIOFIT</h1>
                    <p class="text-sm text-gray-500">Avaliação Física</p>
                </div>
            </div>
            
            <!-- User Info -->
            <div class="mt-6 px-6">
                <div class="bg-gradient-to-r from-cobalt-500 to-cobalt-600 rounded-xl p-4 text-white">
                    <div class="flex items-center">                        <div class="flex-shrink-0">
                            <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium"><?php echo htmlspecialchars($user['nome']); ?></p>
                            <p class="text-xs text-cobalt-100"><?php echo ucfirst($user['tipo']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="mt-8 flex-1 px-3">
                <div class="space-y-1">                    <a href="<?php echo APP_URL; ?>/index.php" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors <?php echo $currentPage === 'index.php' ? 'bg-cobalt-50 text-cobalt-700 border-r-2 border-cobalt-600' : 'text-gray-700 hover:bg-gray-50'; ?>">
                        <svg class="mr-3 w-5 h-5 <?php echo $currentPage === 'index.php' ? 'text-cobalt-600' : 'text-gray-400 group-hover:text-gray-600'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Dashboard
                    </a>

                    <?php if (hasPermission('professor')): ?>                    <a href="<?php echo APP_URL; ?>/pages/alunos.php" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors <?php echo $currentPage === 'alunos.php' ? 'bg-cobalt-50 text-cobalt-700 border-r-2 border-cobalt-600' : 'text-gray-700 hover:bg-gray-50'; ?>">
                        <svg class="mr-3 w-5 h-5 <?php echo $currentPage === 'alunos.php' ? 'text-cobalt-600' : 'text-gray-400 group-hover:text-gray-600'; ?>" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
                        </svg>
                        Alunos
                    </a>                    <a href="<?php echo APP_URL; ?>/pages/avaliacoes.php" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors <?php echo $currentPage === 'avaliacoes.php' ? 'bg-cobalt-50 text-cobalt-700 border-r-2 border-cobalt-600' : 'text-gray-700 hover:bg-gray-50'; ?>">
                        <svg class="mr-3 w-5 h-5 <?php echo $currentPage === 'avaliacoes.php' ? 'text-cobalt-600' : 'text-gray-400 group-hover:text-gray-600'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Avaliações
                    </a>

                    <!-- Treinos Dropdown -->
                    <div class="relative">                        <button id="treinos-dropdown" class="w-full group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors <?php echo in_array($currentPage, ['treinos.php', 'exercicios.php', 'treino-alunos.php']) ? 'bg-cobalt-50 text-cobalt-700' : 'text-gray-700 hover:bg-gray-50'; ?>">
                            <svg class="mr-3 w-5 h-5 <?php echo in_array($currentPage, ['treinos.php', 'exercicios.php', 'treino-alunos.php']) ? 'text-cobalt-600' : 'text-gray-400 group-hover:text-gray-600'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Treinos
                            <svg class="ml-auto w-3 h-3 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="treinos-submenu" class="hidden mt-1 space-y-1 ml-6">
                            <a href="<?php echo APP_URL; ?>/pages/treinos.php" 
                               class="block px-3 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50 <?php echo $currentPage === 'treinos.php' ? 'text-cobalt-700 bg-cobalt-50' : ''; ?>">
                                Gerenciar Treinos
                            </a>
                            <a href="<?php echo APP_URL; ?>/pages/exercicios.php" 
                               class="block px-3 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50 <?php echo $currentPage === 'exercicios.php' ? 'text-cobalt-700 bg-cobalt-50' : ''; ?>">
                                Biblioteca de Exercícios
                            </a>
                            <a href="<?php echo APP_URL; ?>/pages/treino-alunos.php" 
                               class="block px-3 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50 <?php echo $currentPage === 'treino-alunos.php' ? 'text-cobalt-700 bg-cobalt-50' : ''; ?>">
                                Treinos dos Alunos
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (hasPermission('admin')): ?>
                    <!-- Admin Dropdown -->
                    <div class="relative">                        <button id="admin-dropdown" class="w-full group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors <?php echo $currentPage === 'admin.php' ? 'bg-cobalt-50 text-cobalt-700' : 'text-gray-700 hover:bg-gray-50'; ?>">
                            <svg class="mr-3 w-5 h-5 <?php echo $currentPage === 'admin.php' ? 'text-cobalt-600' : 'text-gray-400 group-hover:text-gray-600'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Administração
                            <svg class="ml-auto w-3 h-3 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="admin-submenu" class="hidden mt-1 space-y-1 ml-6">
                            <a href="<?php echo APP_URL; ?>/pages/admin.php" 
                               class="block px-3 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50">
                                Painel Administrativo
                            </a>
                            <a href="<?php echo APP_URL; ?>/pages/admin.php?section=users" 
                               class="block px-3 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50">
                                Gerenciar Usuários
                            </a>
                            <a href="<?php echo APP_URL; ?>/pages/admin.php?section=settings" 
                               class="block px-3 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50">
                                Configurações
                            </a>
                            <a href="<?php echo APP_URL; ?>/pages/admin.php?section=logs" 
                               class="block px-3 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50">
                                Logs do Sistema
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>                    <a href="<?php echo APP_URL; ?>/pages/profile.php" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors <?php echo $currentPage === 'profile.php' ? 'bg-cobalt-50 text-cobalt-700 border-r-2 border-cobalt-600' : 'text-gray-700 hover:bg-gray-50'; ?>">
                        <svg class="mr-3 w-5 h-5 <?php echo $currentPage === 'profile.php' ? 'text-cobalt-600' : 'text-gray-400 group-hover:text-gray-600'; ?>" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                        </svg>
                        Perfil
                    </a>
                </div>

                <!-- Logout Button -->
                <div class="mt-8 pt-6 border-t border-gray-200">                    <button onclick="SessionManager.logout()" 
                            class="w-full group flex items-center px-3 py-2 text-sm font-medium text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                        <svg class="mr-3 w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Sair
                    </button>
                </div>
            </nav>
        </div>
    </aside>

    <!-- Mobile Menu Overlay -->
    <div id="mobile-menu-overlay" class="lg:hidden fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50" id="mobile-menu-backdrop"></div>
        <div class="fixed inset-y-0 left-0 max-w-xs w-full bg-white shadow-xl transform -translate-x-full transition-transform duration-300 ease-in-out" id="mobile-menu-panel">
            <div class="h-full flex flex-col">                <div class="flex items-center justify-between p-4 border-b border-gray-200">
                    <div class="flex items-center space-x-3">
                        <img src="<?php echo APP_URL; ?>/assets/images/logo-smartbiofit.png" alt="SMARTBIOFIT" class="h-12 w-auto max-w-12 rounded-lg object-contain">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">SMARTBIOFIT</h2>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user['nome']); ?></p>
                        </div>
                    </div><button id="mobile-menu-close" class="p-2 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <nav class="flex-1 px-4 py-4 space-y-2">
                    <!-- Mobile Navigation Items -->                    <a href="<?php echo APP_URL; ?>/index.php" 
                       class="flex items-center px-3 py-3 text-base font-medium rounded-lg transition-colors <?php echo $currentPage === 'index.php' ? 'bg-cobalt-50 text-cobalt-700' : 'text-gray-700 hover:bg-gray-50'; ?>">
                        <svg class="mr-3 w-5 h-5 text-cobalt-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Dashboard
                    </a>

                    <?php if (hasPermission('professor')): ?>                    <a href="<?php echo APP_URL; ?>/pages/alunos.php" 
                       class="flex items-center px-3 py-3 text-base font-medium rounded-lg transition-colors <?php echo $currentPage === 'alunos.php' ? 'bg-cobalt-50 text-cobalt-700' : 'text-gray-700 hover:bg-gray-50'; ?>">
                        <svg class="mr-3 w-5 h-5 text-cobalt-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
                        </svg>
                        Alunos
                    </a>                    <a href="<?php echo APP_URL; ?>/pages/avaliacoes.php" 
                       class="flex items-center px-3 py-3 text-base font-medium rounded-lg transition-colors <?php echo $currentPage === 'avaliacoes.php' ? 'bg-cobalt-50 text-cobalt-700' : 'text-gray-700 hover:bg-gray-50'; ?>">
                        <svg class="mr-3 w-5 h-5 text-cobalt-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Avaliações
                    </a>

                    <div class="space-y-1">
                        <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Treinos</div>
                        <a href="<?php echo APP_URL; ?>/pages/treinos.php" 
                           class="flex items-center px-6 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50">
                            Gerenciar Treinos
                        </a>
                        <a href="<?php echo APP_URL; ?>/pages/exercicios.php" 
                           class="flex items-center px-6 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50">
                            Biblioteca de Exercícios
                        </a>
                        <a href="<?php echo APP_URL; ?>/pages/treino-alunos.php" 
                           class="flex items-center px-6 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50">
                            Treinos dos Alunos
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if (hasPermission('admin')): ?>
                    <div class="space-y-1">
                        <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Administração</div>
                        <a href="<?php echo APP_URL; ?>/pages/admin.php" 
                           class="flex items-center px-6 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50">
                            Painel Administrativo
                        </a>
                        <a href="<?php echo APP_URL; ?>/pages/admin.php?section=users" 
                           class="flex items-center px-6 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50">
                            Gerenciar Usuários
                        </a>
                        <a href="<?php echo APP_URL; ?>/pages/admin.php?section=settings" 
                           class="flex items-center px-6 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50">
                            Configurações
                        </a>
                    </div>
                    <?php endif; ?>                    <a href="<?php echo APP_URL; ?>/pages/profile.php" 
                       class="flex items-center px-3 py-3 text-base font-medium rounded-lg transition-colors <?php echo $currentPage === 'profile.php' ? 'bg-cobalt-50 text-cobalt-700' : 'text-gray-700 hover:bg-gray-50'; ?>">
                        <svg class="mr-3 w-5 h-5 <?php echo $currentPage === 'profile.php' ? 'text-cobalt-600' : 'text-gray-400 group-hover:text-gray-600'; ?>" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                        </svg>
                        Perfil
                    </a>
                </nav>
                  <div class="p-4 border-t border-gray-200">
                    <button onclick="SessionManager.logout()" 
                            class="w-full flex items-center justify-center px-4 py-3 text-base font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                        <svg class="mr-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Sair
                    </button>
                </div>
            </div>
        </div>
    </div>    <!-- Bottom Navigation for Mobile -->
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-30">
        <div class="grid grid-cols-5 gap-1">
            <a href="<?php echo APP_URL; ?>/index.php" 
               class="flex flex-col items-center py-2 px-1 text-xs hover:bg-gray-50 transition-colors <?php echo $currentPage === 'index.php' ? 'text-cobalt-600' : 'text-gray-500'; ?>">
                <svg class="w-5 h-5 mb-1" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                </svg>
                <span>Início</span>
            </a>
            
            <?php if (hasPermission('professor')): ?>            <a href="<?php echo APP_URL; ?>/pages/alunos.php" 
               class="flex flex-col items-center py-2 px-1 text-xs hover:bg-gray-50 transition-colors <?php echo $currentPage === 'alunos.php' ? 'text-cobalt-600' : 'text-gray-500'; ?>">
                <svg class="w-5 h-5 mb-1" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
                </svg>
                <span>Alunos</span>
            </a>
            
            <a href="<?php echo APP_URL; ?>/pages/treinos.php" 
               class="flex flex-col items-center py-2 px-1 text-xs hover:bg-gray-50 transition-colors <?php echo in_array($currentPage, ['treinos.php', 'exercicios.php', 'treino-alunos.php']) ? 'text-cobalt-600' : 'text-gray-500'; ?>">
                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <span>Treinos</span>
            </a>
            
            <a href="<?php echo APP_URL; ?>/pages/avaliacoes.php" 
               class="flex flex-col items-center py-2 px-1 text-xs hover:bg-gray-50 transition-colors <?php echo $currentPage === 'avaliacoes.php' ? 'text-cobalt-600' : 'text-gray-500'; ?>">
                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <span>Avaliações</span>
            </a>
            <?php else: ?>            <div class="flex flex-col items-center py-2 px-1 text-xs text-gray-300">
                <svg class="w-5 h-5 mb-1" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
                </svg>
                <span>Alunos</span>
            </div>
            <div class="flex flex-col items-center py-2 px-1 text-xs text-gray-300">
                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <span>Treinos</span>
            </div>
            <div class="flex flex-col items-center py-2 px-1 text-xs text-gray-300">
                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <span>Avaliações</span>
            </div>
            <?php endif; ?>
            
            <!-- Perfil sempre visível para todos os usuários -->
            <a href="<?php echo APP_URL; ?>/pages/profile.php" 
               class="flex flex-col items-center py-2 px-1 text-xs hover:bg-gray-50 transition-colors <?php echo $currentPage === 'profile.php' ? 'text-cobalt-600' : 'text-gray-500'; ?>">
                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span>Perfil</span>
            </a>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="<?php echo $user ? 'lg:pl-64 pb-16 lg:pb-0' : ''; ?>">        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="p-4" data-aos="fade-down">
                <div class="max-w-7xl mx-auto">
                    <div class="bg-<?php echo $_SESSION['flash_type'] === 'success' ? 'green' : ($_SESSION['flash_type'] === 'error' ? 'red' : 'blue'); ?>-50 border border-<?php echo $_SESSION['flash_type'] === 'success' ? 'green' : ($_SESSION['flash_type'] === 'error' ? 'red' : 'blue'); ?>-200 text-<?php echo $_SESSION['flash_type'] === 'success' ? 'green' : ($_SESSION['flash_type'] === 'error' ? 'red' : 'blue'); ?>-800 px-4 py-3 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <?php if ($_SESSION['flash_type'] === 'success'): ?>
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            <?php elseif ($_SESSION['flash_type'] === 'error'): ?>
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            <?php else: ?>
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                            <?php endif; ?>
                            <?php 
                            echo htmlspecialchars($_SESSION['flash_message']); 
                            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
