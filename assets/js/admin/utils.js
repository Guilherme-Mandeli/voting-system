/**
 * Módulo de utilitários gerais para o admin
 */
(function($) {
    'use strict';

    window.VSAdmin = window.VSAdmin || {};
    window.VSAdmin.Utils = {
        
        init: function() {
        },
        
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        showLoadingOverlay: function($container) {
            // Remove qualquer overlay existente primeiro
            $container.find('.vs-loading-overlay').remove();
            // Cria e anexa o novo overlay de carregamento
            const $loadingOverlay = $('<div class="vs-loading-overlay"><div class="spinner"></div></div>');
            $container.append($loadingOverlay);
            return $loadingOverlay;
        },

        hideLoadingOverlay: function($modal) {
            $modal.find('.vs-loading-overlay').remove();
        },

        sanitizeHtml: function(str) {
            const temp = document.createElement('div');
            temp.textContent = str;
            return temp.innerHTML;
        },

        formatNumber: function(num) {
            return new Intl.NumberFormat().format(num);
        },

        validateEmail: function(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        showNotification: function(message, type = 'success') {
            const $notification = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            $('.wrap h1').after($notification);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $notification.fadeOut(() => $notification.remove());
            }, 5000);
        },

        confirmAction: function(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }
    };

})(jQuery);