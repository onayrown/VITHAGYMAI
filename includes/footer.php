</main>
    
    <!-- Modern Footer -->
    <footer class="bg-gray-900 text-white py-8 lg:ml-64">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">                <div>
                    <div class="flex items-center mb-4">
                        <img src="<?php echo APP_URL; ?>/assets/images/logo-vithagymai.png" alt="VithaGymAI" class="h-10 w-auto max-w-10 rounded-lg mr-3 object-contain">
                        <div>
                            <h3 class="text-xl font-bold">VithaGymAI</h3>
                            <p class="text-gray-400 text-sm">Avaliação Física Profissional</p>
                        </div>
                    </div>
                    <p class="text-gray-300 mb-4">
                        Aplicativo Web desenvolvido para educadores físicos modernos,
                        oferecendo ferramentas completas para avaliação e acompanhamento.
                    </p>                    <div class="flex space-x-4">
                        <div class="flex items-center text-sm text-gray-400">
                            <svg class="mr-2 w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Seguro e Confiável
                        </div>
                        <div class="flex items-center text-sm text-gray-400">
                            <svg class="mr-2 w-4 h-4 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"></path>
                            </svg>
                            Mobile First
                        </div>
                    </div>
                </div>
                
                <div class="text-right">
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-end items-center text-sm text-gray-300">
                            <span class="font-medium mr-2">Versão:</span>
                            <span class="px-2 py-1 bg-cobalt-600 rounded text-white text-xs">1.0.0</span>
                        </div>
                        <div class="flex justify-end items-center text-sm text-gray-400">
                            <span class="mr-2">Ambiente:</span>
                            <span class="px-2 py-1 bg-gray-700 rounded text-xs"><?php echo APP_ENV; ?></span>
                        </div>
                        <div class="flex justify-end items-center text-sm text-gray-400">
                            <span class="mr-2">Servidor:</span>
                            <span class="text-xs"><?php echo gethostname(); ?></span>
                        </div>
                    </div>
                    
                    <?php if (APP_DEBUG): ?>
                    <div class="bg-yellow-900 bg-opacity-50 rounded-lg p-3 mb-4">
                        <div class="text-xs text-yellow-200 space-y-1">
                            <div class="flex justify-between">
                                <span>Tempo de Resposta:</span>
                                <span class="font-mono"><?php echo number_format((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2); ?>ms</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Uso de Memória:</span>
                                <span class="font-mono"><?php echo number_format(memory_get_peak_usage(true) / 1024 / 1024, 2); ?>MB</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Debug Mode:</span>
                                <span class="text-yellow-400 font-medium">Ativo</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <hr class="border-gray-700 my-8">
            
            <div class="text-center">
                <p class="text-gray-400 text-sm">
                    © <?php echo date('Y'); ?> VithaGymAI. Todos os direitos reservados.
                </p>                <p class="text-gray-500 text-xs mt-2">
                    Desenvolvido com <svg class="inline w-4 h-4 text-red-500 mx-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                    </svg> para profissionais de educação física
                </p>
            </div>
        </div>
    </footer>

    <!-- Modern JavaScript Libraries -->
    <!-- Flowbite JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
    
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Chart.js for Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Alpine.js for Interactive Components -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
      <!-- Custom JavaScript -->
    <script src="<?php echo APP_URL; ?>/assets/js/app.js?v=<?php echo time(); ?>"></script>
    
    <!-- Premium Mobile Experience Script -->
    <script>
        // Initialize AOS
        AOS.init({
            duration: 600,
            easing: 'ease-in-out',
            once: true,
            offset: 100
        });

        // Mobile Menu Controls
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
        const mobileMenuPanel = document.getElementById('mobile-menu-panel');
        const mobileMenuClose = document.getElementById('mobile-menu-close');
        const mobileMenuBackdrop = document.getElementById('mobile-menu-backdrop');

        function openMobileMenu() {
            mobileMenuOverlay.classList.remove('hidden');
            setTimeout(() => {
                mobileMenuPanel.classList.add('translate-x-0');
                mobileMenuPanel.classList.remove('-translate-x-full');
            }, 10);
        }

        function closeMobileMenu() {
            mobileMenuPanel.classList.remove('translate-x-0');
            mobileMenuPanel.classList.add('-translate-x-full');
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
        
        if (mobileMenuBackdrop) {
            mobileMenuBackdrop.addEventListener('click', closeMobileMenu);
        }

        // Desktop Dropdown Controls
        const treinosDropdown = document.getElementById('treinos-dropdown');
        const treinosSubmenu = document.getElementById('treinos-submenu');
        const adminDropdown = document.getElementById('admin-dropdown');
        const adminSubmenu = document.getElementById('admin-submenu');

        function toggleDropdown(button, menu) {
            const isOpen = !menu.classList.contains('hidden');
            
            // Close all dropdowns first
            document.querySelectorAll('[id$="-submenu"]').forEach(submenu => {
                submenu.classList.add('hidden');
            });
            document.querySelectorAll('[id$="-dropdown"] i.fa-chevron-down').forEach(icon => {
                icon.classList.remove('rotate-180');
            });

            if (!isOpen) {
                menu.classList.remove('hidden');
                button.querySelector('i.fa-chevron-down').classList.add('rotate-180');
            }
        }

        if (treinosDropdown) {
            treinosDropdown.addEventListener('click', (e) => {
                e.preventDefault();
                toggleDropdown(treinosDropdown, treinosSubmenu);
            });
        }

        if (adminDropdown) {
            adminDropdown.addEventListener('click', (e) => {
                e.preventDefault();
                toggleDropdown(adminDropdown, adminSubmenu);
            });
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('[id$="-dropdown"]')) {
                document.querySelectorAll('[id$="-submenu"]').forEach(submenu => {
                    submenu.classList.add('hidden');
                });
                document.querySelectorAll('[id$="-dropdown"] i.fa-chevron-down').forEach(icon => {
                    icon.classList.remove('rotate-180');
                });
            }
        });

        // Toast Notification System
        window.showToast = function(message, type = 'success', duration = 5000) {
            const toastContainer = document.getElementById('toast-container');
            const toastId = 'toast-' + Date.now();
            const typeClasses = { 
                'success': 'bg-green-500 text-white',
                'error': 'bg-red-500 text-white',
                'warning': 'bg-yellow-500 text-white',
                'info': 'bg-blue-500 text-white'
            };
            
            // Simplified for debugging
            const typeIcons = {
                'success': 'success_icon_placeholder',
                'error': 'error_icon_placeholder',
                'warning': 'warning_icon_placeholder',
                'info': 'info_icon_placeholder'
            };
            
            const toast = document.createElement('div');
            toast.id = toastId;
            toast.className = `${typeClasses[type]} px-4 py-3 rounded-lg shadow-lg flex items-center space-x-2 transform translate-x-full transition-transform duration-300`;
            toast.innerHTML = `
                ${typeIcons[type]}
                <span class="flex-1">${message}</span>
                <button onclick="closeToast('${toastId}')" class="text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;

            toastContainer.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
            }, 100);

            // Auto dismiss
            setTimeout(() => {
                closeToast(toastId);
            }, duration);
        };

        window.closeToast = function(toastId) {
            const toast = document.getElementById(toastId);
            if (toast) {
                toast.classList.add('translate-x-full');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }
        };

        // Loading Overlay Control
        window.showLoading = function() {
            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.remove('opacity-0', 'pointer-events-none');
            overlay.classList.add('opacity-100');
        };

        window.hideLoading = function() {
            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.add('opacity-0', 'pointer-events-none');
            overlay.classList.remove('opacity-100');
        };

        // Auto-hide loading after page load
        window.addEventListener('load', () => {
            setTimeout(hideLoading, 500);
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Enhanced form validation
        document.querySelectorAll('form[data-validate]').forEach(form => {
            form.addEventListener('submit', function(e) {
                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('border-red-500');
                        const errorMsg = field.nextElementSibling;
                        if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                            const error = document.createElement('p');
                            error.className = 'error-message text-red-500 text-xs mt-1';
                            error.textContent = 'Este campo é obrigatório';
                            field.parentNode.insertBefore(error, field.nextSibling);
                        }
                    } else {
                        field.classList.remove('border-red-500');
                        const errorMsg = field.nextElementSibling;
                        if (errorMsg && errorMsg.classList.contains('error-message')) {
                            errorMsg.remove();
                        }
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    showToast('Por favor, preencha todos os campos obrigatórios', 'error');
                }
            });
        });
    </script>
    
    <?php if (APP_DEBUG): ?>
    <!-- Enhanced Debug Console -->
    <script>
        console.group('🚀 VithaGymAI Debug Info');
        console.log('📱 App URL:', '<?php echo APP_URL; ?>');
        console.log('👤 Current User:', <?php echo $user ? json_encode(['id' => $user['id'], 'nome' => $user['nome'], 'tipo' => $user['tipo']]) : 'null'; ?>);
        console.log('📄 Current Page:', '<?php echo $currentPage; ?>');
        console.log('🕐 Server Time:', '<?php echo date('Y-m-d H:i:s'); ?>');
        console.log('🌍 Environment:', '<?php echo APP_ENV; ?>');
        console.log('⚡ Performance:', {
            responseTime: '<?php echo isset($loadTime) ? htmlspecialchars($loadTime) : "N/A"; ?>ms',
            memoryUsage: '<?php echo isset($memoryUsage) ? htmlspecialchars($memoryUsage) : "N/A"; ?>MB'
        });
        console.groupEnd();
        // phpVersion: '<?php echo PHP_VERSION; ?>', // Temporarily commented out
        // environment: '<?php echo APP_ENV; ?>' // Temporarily commented out
    </script>
    <?php endif; ?>
    
    <!-- Service Worker para PWA (será implementado nos próximos milestones) -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('<?php echo APP_URL; ?>/sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registrado com sucesso');
                    })
                    .catch(function(error) {
                        console.log('Falha ao registrar ServiceWorker');
                    });
            });
        }
    </script>
</body>
</html>

<?php
// Log da página acessada (se usuário logado)
if ($user) {
    try {
        $db = Database::getInstance();
        $db->execute("INSERT INTO logs (usuario_id, acao, descricao, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)", [
            $user['id'],
            'page_access',
            "Acessou página: " . $currentPage,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        // Log silencioso para não quebrar a página
        logError("Erro ao registrar log de acesso: " . $e->getMessage());
    }
}
?>
