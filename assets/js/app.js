/**
 * VithaGymAI - JavaScript Base (Fixed)
 * Funcionalidades comuns e utilitários
 */

// Configuração global
const SMARTBIOFIT = {
    apiUrl: window.location.origin,
    debug: false,
    
    // Configurações de validação
    validation: {
        email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        phone: /^[\d\s\-\(\)]+$/,
        cpf: /^\d{3}\.\d{3}\.\d{3}-\d{2}$/
    }
};

// Utilitários
const Utils = {
    // Exibir loading
    showLoading: (element) => {
        if (element) {
            element.disabled = true;
            element.innerHTML = '<span class="loading"></span> Carregando...';
        }
    },
    
    // Esconder loading
    hideLoading: (element, originalText) => {
        if (element) {
            element.disabled = false;
            element.innerHTML = originalText;
        }
    },
    
    // Exibir alerta
    showAlert: (message, type = 'info', duration = 5000) => {
        const alertContainer = document.getElementById('toast-container') || document.body;
        const alertDiv = document.createElement('div');
        alertDiv.className = `bg-${type === 'success' ? 'green' : type === 'error' ? 'red' : 'blue'}-100 border border-${type === 'success' ? 'green' : type === 'error' ? 'red' : 'blue'}-400 text-${type === 'success' ? 'green' : type === 'error' ? 'red' : 'blue'}-700 px-4 py-3 rounded relative mb-4`;
        alertDiv.innerHTML = `
            <div class="flex justify-between items-center">
                <span>${message}</span>
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="ml-4 text-lg font-bold">&times;</button>
            </div>
        `;
        
        alertContainer.appendChild(alertDiv);
        
        // Remove automaticamente após o tempo especificado
        setTimeout(() => {
            if (alertDiv.parentElement) {
                alertDiv.remove();
            }
        }, duration);
    },
    
    // Validar email
    validateEmail: (email) => {
        return SMARTBIOFIT.validation.email.test(email);
    },
    
    // Requisição AJAX
    ajax: async (url, options = {}) => {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        const config = { ...defaultOptions, ...options };
        
        if (config.body && typeof config.body === 'object') {
            config.body = JSON.stringify(config.body);
        }
        
        try {
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            }
            
            return await response.text();
        } catch (error) {
            console.error('Erro na requisição:', error);
            throw error;
        }
    },
    
    // Confirmar ação
    confirm: (message, callback) => {
        if (confirm(message)) {
            callback();
        }
    }
};

// Gerenciador de sessão
const SessionManager = {
    check: async () => {
        try {
            const response = await Utils.ajax(`${SMARTBIOFIT.apiUrl}/check-session.php`);
            return response.authenticated;
        } catch (error) {
            return false;
        }
    },
    
    logout: async () => {
        try {
            console.log('SessionManager: Iniciando logout...');
            
            // Método mais simples: redirecionar diretamente para logout.php
            window.location.href = `${SMARTBIOFIT.apiUrl}/logout.php`;
            
        } catch (error) {
            console.error('Erro no logout:', error);
            // Fallback: redirecionar para login
            window.location.href = `${SMARTBIOFIT.apiUrl}/login.php`;
        }
    }
};

// Gerenciador de Menu Mobile
const MobileMenuManager = {
    init: () => {
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
        const mobileMenuClose = document.getElementById('mobile-menu-close');
        const mobileMenuBackdrop = document.getElementById('mobile-menu-backdrop');
        
        if (mobileMenuButton && mobileMenuOverlay) {
            mobileMenuButton.addEventListener('click', () => {
                mobileMenuOverlay.classList.remove('hidden');
                const panel = document.getElementById('mobile-menu-panel');
                if (panel) {
                    setTimeout(() => panel.classList.remove('-translate-x-full'), 10);
                }
            });
        }
        
        const closeMenu = () => {
            const panel = document.getElementById('mobile-menu-panel');
            if (panel) {
                panel.classList.add('-translate-x-full');
                setTimeout(() => {
                    if (mobileMenuOverlay) mobileMenuOverlay.classList.add('hidden');
                }, 300);
            }
        };
        
        if (mobileMenuClose) {
            mobileMenuClose.addEventListener('click', closeMenu);
        }
        
        if (mobileMenuBackdrop) {
            mobileMenuBackdrop.addEventListener('click', closeMenu);
        }
    }
};

// Gerenciador de Dropdown
const DropdownManager = {
    init: () => {
        // Desktop dropdowns
        const dropdownButtons = document.querySelectorAll('[id$="-dropdown"]');
          dropdownButtons.forEach(button => {
            const submenuId = button.id.replace('-dropdown', '-submenu');
            const submenu = document.getElementById(submenuId);
            const chevron = button.querySelector('svg:last-child'); // Get the last SVG (chevron) in the button
            
            if (submenu) {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    
                    // Toggle current submenu
                    const isHidden = submenu.classList.contains('hidden');
                    
                    // Close all other submenus
                    document.querySelectorAll('[id$="-submenu"]').forEach(menu => {
                        if (menu !== submenu) {
                            menu.classList.add('hidden');
                            const otherButton = document.getElementById(menu.id.replace('-submenu', '-dropdown'));
                            const otherChevron = otherButton?.querySelector('svg:last-child');
                            if (otherChevron) {
                                otherChevron.style.transform = 'rotate(0deg)';
                            }
                        }
                    });
                    
                    // Toggle current submenu
                    if (isHidden) {
                        submenu.classList.remove('hidden');
                        if (chevron) chevron.style.transform = 'rotate(180deg)';
                    } else {
                        submenu.classList.add('hidden');
                        if (chevron) chevron.style.transform = 'rotate(0deg)';
                    }
                });
            }
        });
          // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('[id$="-dropdown"]') && !e.target.closest('[id$="-submenu"]')) {
                document.querySelectorAll('[id$="-submenu"]').forEach(menu => {
                    menu.classList.add('hidden');
                    const button = document.getElementById(menu.id.replace('-submenu', '-dropdown'));
                    const chevron = button?.querySelector('svg:last-child');
                    if (chevron) {
                        chevron.style.transform = 'rotate(0deg)';
                    }
                });
            }
        });
    }
};

// PerimetryManager stub para evitar erros
const PerimetryManager = {
    init: () => {
        const perimetryForm = document.getElementById('perimetriaForm');
        if (!perimetryForm) {
            console.log('PerimetryManager: Form not found on this page');
            return;
        }
        console.log('PerimetryManager: Initialized');
    }
};

// Funções globais para compatibilidade
function showAlert(message, type = 'info') {
    Utils.showAlert(message, type);
}

function confirmDelete(message, callback) {
    Utils.confirm(message, callback);
}

// Exportar para uso global
window.SMARTBIOFIT = SMARTBIOFIT;
window.Utils = Utils;
window.SessionManager = SessionManager;
window.MobileMenuManager = MobileMenuManager;
window.DropdownManager = DropdownManager;
window.PerimetryManager = PerimetryManager;

// Inicialização principal quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    console.log('SMARTBIOFIT: Inicializando...');
    
    try {
        // Inicializar componentes principais
        MobileMenuManager.init();
        DropdownManager.init();
        PerimetryManager.init();
        
        // Auto-dismiss alerts
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                if (alert.parentElement) {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }
            }, 5000);
        });
        
        // Verificar sessão periodicamente (a cada 5 minutos)
        setInterval(async () => {
            try {
                const isAuthenticated = await SessionManager.check();
                if (!isAuthenticated && !window.location.pathname.includes('login.php')) {
                    Utils.showAlert('Sessão expirada. Redirecionando para login...', 'warning');
                    setTimeout(() => {
                        window.location.href = `${SMARTBIOFIT.apiUrl}/login.php`;
                    }, 2000);
                }
            } catch (error) {
                console.log('Erro na verificação de sessão:', error);
            }
        }, 300000); // 5 minutos
        
        console.log('SMARTBIOFIT: Todos os componentes inicializados com sucesso');
        
    } catch (error) {
        console.error('SMARTBIOFIT: Erro na inicialização:', error);
    }
});
