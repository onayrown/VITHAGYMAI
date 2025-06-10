            </div>
        </main>
    </div>

    <!-- Mobile Menu Overlay -->
    <div id="mobile-menu-overlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40 hidden">
        <div class="fixed inset-y-0 left-0 w-64 bg-white shadow-xl transform -translate-x-full transition-transform duration-300 ease-in-out" id="mobile-menu">
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between p-4 border-b border-gray-200">                    <div class="flex items-center space-x-3">
                        <img src="<?= APP_URL ?>/assets/images/logo-vithagym.png" alt="VITHA GYM" class="h-8 w-auto">
                        <span class="text-lg font-bold text-gray-900">VITHA GYM</span>
                    </div>
                    <button id="mobile-menu-close" class="p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- User Info Mobile -->
                <div class="p-4 bg-gradient-to-r from-cobalt-500 to-cobalt-600 text-white">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium"><?= htmlspecialchars($aluno['nome']) ?></p>
                            <p class="text-xs text-cobalt-100">Aluno</p>
                        </div>
                    </div>
                </div>
                
                <!-- Mobile Navigation -->
                <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto">
                    <a href="dashboard.php" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium <?= $current_page === 'dashboard' ? 'bg-cobalt-50 text-cobalt-700' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <svg class="mr-3 w-5 h-5 <?= $current_page === 'dashboard' ? 'text-cobalt-600' : 'text-gray-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Dashboard
                    </a>
                    
                    <a href="treinos.php" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium <?= $current_page === 'treinos' ? 'bg-cobalt-50 text-cobalt-700' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <svg class="mr-3 w-5 h-5 <?= $current_page === 'treinos' ? 'text-cobalt-600' : 'text-gray-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Meus Treinos
                    </a>
                    
                    <a href="avaliacoes.php" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium <?= $current_page === 'avaliacoes' ? 'bg-cobalt-50 text-cobalt-700' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <svg class="mr-3 w-5 h-5 <?= $current_page === 'avaliacoes' ? 'text-cobalt-600' : 'text-gray-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Avaliações
                    </a>
                    
                    <a href="perfil.php" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium <?= $current_page === 'perfil' ? 'bg-cobalt-50 text-cobalt-700' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <svg class="mr-3 w-5 h-5 <?= $current_page === 'perfil' ? 'text-cobalt-600' : 'text-gray-400' ?>" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                        </svg>
                        Meu Perfil
                    </a>
                    
                    <a href="notificacoes.php" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium <?= $current_page === 'notificacoes' ? 'bg-cobalt-50 text-cobalt-700' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <svg class="mr-3 w-5 h-5 <?= $current_page === 'notificacoes' ? 'text-cobalt-600' : 'text-gray-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19c-5 0-8-4-8-9s4-9 9-9 9 4 9 9c0 .285-.011.568-.033.848"></path>
                        </svg>
                        Notificações
                        <?php if ($notificacoes_nao_lidas > 0): ?>
                            <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1 min-w-[20px] h-5 flex items-center justify-center"><?= $notificacoes_nao_lidas ?></span>
                        <?php endif; ?>
                    </a>
                </nav>
                
                <div class="p-4 border-t border-gray-200">
                    <a href="logout.php" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50">
                        <svg class="mr-3 w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Sair
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Content -->
    <div class="lg:hidden min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
        <!-- Mobile content will be added by individual pages -->
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });

        // Mobile menu functionality
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileMenuClose = document.getElementById('mobile-menu-close');

        function openMobileMenu() {
            mobileMenuOverlay.classList.remove('hidden');
            setTimeout(() => {
                mobileMenu.classList.remove('-translate-x-full');
            }, 10);
        }

        function closeMobileMenu() {
            mobileMenu.classList.add('-translate-x-full');
            setTimeout(() => {
                mobileMenuOverlay.classList.add('hidden');
            }, 300);
        }

        if (mobileMenuButton) {
            mobileMenuButton.addEventListener('click', openMobileMenu);
        }
        
        if (mobileMenuClose) {
            mobileMenuClose.addEventListener('click', closeMobileMenu);
        }
        
        if (mobileMenuOverlay) {
            mobileMenuOverlay.addEventListener('click', (e) => {
                if (e.target === mobileMenuOverlay) {
                    closeMobileMenu();
                }
            });
        }

        // Hide loading overlay
        window.addEventListener('load', () => {
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => {
                    loadingOverlay.style.pointerEvents = 'none';
                }, 300);
            }
        });

        // Toast notification system
        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toast-container');
            if (!toastContainer) return;

            const toast = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
            
            toast.className = `${bgColor} text-white px-4 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300`;
            toast.innerHTML = `
                <div class="flex items-center justify-between">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            toastContainer.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
            }, 100);
            
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 5000);
        }

        // Check for URL parameters and show messages
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('sucesso')) {
            const sucesso = urlParams.get('sucesso');
            if (sucesso === 'logout') {
                showToast('Logout realizado com sucesso!', 'success');
            }
        }
        if (urlParams.has('erro')) {
            const erro = urlParams.get('erro');
            showToast(`Erro: ${erro}`, 'error');
        }
    </script>
</body>
</html>
