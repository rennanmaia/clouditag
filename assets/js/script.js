// Funções utilitárias para o sistema CloudiTag

/* =========================================================
   THEME TOGGLE (Dark / Light Mode)
   ========================================================= */

(function() {
    // Apply saved theme immediately to avoid FOUC
    var t = localStorage.getItem('clouditag_theme') || 'light';
    document.documentElement.setAttribute('data-theme', t);
})();

function getCurrentTheme() {
    return document.documentElement.getAttribute('data-theme') || 'light';
}

function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('clouditag_theme', theme);
    updateThemeButtons(theme);
}

function toggleTheme() {
    var newTheme = getCurrentTheme() === 'dark' ? 'light' : 'dark';
    applyTheme(newTheme);
}

function updateThemeButtons(theme) {
    var isDark = theme === 'dark';
    document.querySelectorAll('.theme-toggle, .theme-toggle-float').forEach(function(btn) {
        var icon = btn.querySelector('i');
        var label = btn.querySelector('.theme-label');
        if (icon) {
            icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        }
        if (label) {
            label.textContent = isDark ? 'Modo Claro' : 'Modo Escuro';
        }
    });
}

// Exibir modal
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

// Ocultar modal
function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Fechar modal clicando fora dele
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Toggle sidebar (mobile e desktop)
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;

    const isMobile = window.innerWidth <= 900;

    if (isMobile) {
        sidebar.classList.toggle('active');
    } else {
        document.body.classList.toggle('sidebar-collapsed');
    }
}

// Criar botão hamburger global quando existir sidebar
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;

    if (!document.querySelector('.sidebar-toggle-btn')) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'sidebar-toggle-btn';
        btn.setAttribute('aria-label', 'Alternar menu');
        btn.innerHTML = '<i class="fas fa-bars"></i>';
        btn.addEventListener('click', toggleSidebar);
        document.body.appendChild(btn);
    }
});

// Confirmar exclusão
function confirmDelete(message = 'Tem certeza que deseja excluir este item?') {
    return confirm(message);
}

// Copiar texto para clipboard
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            showToast('Link copiado para a área de transferência!', 'success');
        });
    } else {
        // Fallback para navegadores mais antigos
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showToast('Link copiado para a área de transferência!', 'success');
    }
}

// Exibir toast notification
function showToast(message, type = 'info', duration = 3000) {
    // Criar elemento toast
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas fa-${getToastIcon(type)}"></i>
            <span>${message}</span>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    
    // Adicionar estilos se não existirem
    if (!document.querySelector('#toast-styles')) {
        const styles = document.createElement('style');
        styles.id = 'toast-styles';
        styles.textContent = `
            .toast {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                padding: 15px;
                z-index: 10000;
                min-width: 300px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                animation: slideInRight 0.3s ease;
            }
            
            .toast-success { border-left: 4px solid #28a745; }
            .toast-error { border-left: 4px solid #dc3545; }
            .toast-warning { border-left: 4px solid #ffc107; }
            .toast-info { border-left: 4px solid #17a2b8; }
            
            .toast-content {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .toast-content i {
                font-size: 18px;
            }
            
            .toast-success i { color: #28a745; }
            .toast-error i { color: #dc3545; }
            .toast-warning i { color: #ffc107; }
            .toast-info i { color: #17a2b8; }
            
            .toast-close {
                background: none;
                border: none;
                font-size: 20px;
                cursor: pointer;
                color: #999;
                padding: 0;
                margin-left: 15px;
            }
            
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(styles);
    }
    
    // Adicionar toast ao DOM
    document.body.appendChild(toast);
    
    // Remover automaticamente após o tempo especificado
    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.animation = 'slideInRight 0.3s ease reverse';
            setTimeout(() => toast.remove(), 300);
        }
    }, duration);
}

// Obter ícone para toast
function getToastIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-triangle',
        warning: 'exclamation-circle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Validar formulários
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    // Validar emails
    const emailFields = form.querySelectorAll('[type="email"]');
    emailFields.forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            field.classList.add('is-invalid');
            isValid = false;
        }
    });
    
    return isValid;
}

// Validar email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Gerar slug a partir do nome
function generateSlug(name) {
    return name
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

// Atualizar slug automaticamente
function setupSlugGeneration(nameFieldId, slugFieldId) {
    const nameField = document.getElementById(nameFieldId);
    const slugField = document.getElementById(slugFieldId);
    
    if (nameField && slugField) {
        nameField.addEventListener('input', function() {
            if (!slugField.dataset.manual) {
                slugField.value = generateSlug(this.value);
            }
        });
        
        slugField.addEventListener('input', function() {
            this.dataset.manual = 'true';
        });
    }
}

// Preview de imagem
function previewImage(input, previewId) {
    const file = input.files[0];
    const preview = document.getElementById(previewId);
    
    if (file && preview) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

// Sortable para reordenar elementos
function makeSortable(containerId, callback) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    let draggedElement = null;
    
    container.addEventListener('dragstart', function(e) {
        draggedElement = e.target.closest('[draggable]');
        e.target.style.opacity = '0.5';
    });
    
    container.addEventListener('dragend', function(e) {
        e.target.style.opacity = '';
        draggedElement = null;
    });
    
    container.addEventListener('dragover', function(e) {
        e.preventDefault();
    });
    
    container.addEventListener('drop', function(e) {
        e.preventDefault();
        const dropTarget = e.target.closest('[draggable]');
        
        if (dropTarget && draggedElement && dropTarget !== draggedElement) {
            const rect = dropTarget.getBoundingClientRect();
            const midY = rect.top + rect.height / 2;
            
            if (e.clientY < midY) {
                container.insertBefore(draggedElement, dropTarget);
            } else {
                container.insertBefore(draggedElement, dropTarget.nextSibling);
            }
            
            // Callback para atualizar ordem no servidor
            if (callback) {
                callback();
            }
        }
    });
}

// Formatação de telefone
function formatPhone(input) {
    let value = input.value.replace(/\D/g, '');
    
    if (value.length <= 10) {
        value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    } else {
        value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    }
    
    input.value = value;
}

// Auto-resize textarea
function autoResizeTextarea(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
}

// Inicialização quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Sync theme buttons with current theme
    updateThemeButtons(getCurrentTheme());

    // Auto-resize para textareas
    document.querySelectorAll('textarea[data-auto-resize]').forEach(textarea => {
        textarea.addEventListener('input', () => autoResizeTextarea(textarea));
        autoResizeTextarea(textarea); // Resize inicial
    });
    
    // Formatação automática para campos de telefone
    document.querySelectorAll('input[data-format="phone"]').forEach(input => {
        input.addEventListener('input', () => formatPhone(input));
    });
    
    // Remover alertas automaticamente
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
        const delay = parseInt(alert.dataset.autoDismiss) || 5000;
        setTimeout(() => {
            alert.style.animation = 'fadeOut 0.5s ease';
            setTimeout(() => alert.remove(), 500);
        }, delay);
    });
    
    // Adicionar estilo para fadeOut
    if (!document.querySelector('#fade-out-styles')) {
        const styles = document.createElement('style');
        styles.id = 'fade-out-styles';
        styles.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; transform: translateY(0); }
                to { opacity: 0; transform: translateY(-20px); }
            }
        `;
        document.head.appendChild(styles);
    }
});

// Função para compartilhar perfil
function shareProfile(url, title = 'Confira meu perfil') {
    if (navigator.share) {
        navigator.share({
            title: title,
            url: url
        });
    } else {
        copyToClipboard(url);
    }
}

// Função para abrir WhatsApp
function openWhatsApp(number, message = '') {
    const cleanNumber = number.replace(/\D/g, '');
    const encodedMessage = encodeURIComponent(message);
    const url = `https://wa.me/55${cleanNumber}?text=${encodedMessage}`;
    window.open(url, '_blank');
}

// Função para abrir Google Maps
function openMaps(address) {
    const encodedAddress = encodeURIComponent(address);
    const url = `https://www.google.com/maps/search/?api=1&query=${encodedAddress}`;
    window.open(url, '_blank');
}

// Função para conectar WiFi (para dispositivos compatíveis)
function connectWiFi(ssid, password) {
    const wifiUrl = `wifi:T:WPA;S:${ssid};P:${password};;`;
    
    // Tentar usar a API de QR Code se disponível
    if (typeof QRCode !== 'undefined') {
        showWiFiQR(wifiUrl);
    } else {
        // Fallback: copiar informações
        const info = `SSID: ${ssid}\nSenha: ${password}`;
        copyToClipboard(info);
        showToast('Informações do WiFi copiadas!', 'info');
    }
}

// Exibir QR Code do WiFi
function showWiFiQR(wifiString) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'block';
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Conectar ao WiFi</h5>
                    <button type="button" class="modal-close" onclick="this.closest('.modal').remove()">&times;</button>
                </div>
                <div class="modal-body text-center">
                    <p>Escaneie o QR Code para conectar:</p>
                    <div id="wifi-qr" style="margin: 20px 0;"></div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Gerar QR Code (assumindo que a biblioteca QRCode.js está carregada)
    if (typeof QRCode !== 'undefined') {
        new QRCode(document.getElementById('wifi-qr'), {
            text: wifiString,
            width: 200,
            height: 200
        });
    }
}

// Export das funções para uso global
window.CloudiTag = {
    showModal,
    hideModal,
    toggleSidebar,
    confirmDelete,
    copyToClipboard,
    showToast,
    validateForm,
    generateSlug,
    setupSlugGeneration,
    previewImage,
    makeSortable,
    formatPhone,
    autoResizeTextarea,
    shareProfile,
    openWhatsApp,
    openMaps,
    connectWiFi
};