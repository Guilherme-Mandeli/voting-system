/**
 * JavaScript do Widget de População de Dados
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        const $widget = $('.vs-population-widget');
        const $loading = $('.vs-loading');
        
        // Botão Popular Dados
        $('.vs-populate-data').on('click', function() {
            if (confirm(vsPopulateData.strings.confirm_populate)) {
                populateData();
            }
        });
        
        // Botão Limpar Dados
        $('.vs-clear-data').on('click', function() {
            if (confirm(vsPopulateData.strings.confirm_clear)) {
                clearData();
            }
        });
        
        // Botão Criar Respostas Automáticas
        $('.vs-create-responses').on('click', function() {
            if (confirm(vsPopulateData.strings.confirm_create_responses)) {
                createResponses();
            }
        });
        
        function populateData() {
            showLoading();
            
            $.ajax({
                url: vsPopulateData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vs_populate_data',
                    nonce: vsPopulateData.nonce
                },
                success: function(response) {
                    hideLoading();
                    
                    if (response.success) {
                        showMessage('success', response.data.message);
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showMessage('error', response.data.message || vsPopulateData.strings.error);
                    }
                },
                error: function() {
                    hideLoading();
                    showMessage('error', vsPopulateData.strings.error);
                }
            });
        }
        
        function clearData() {
            showLoading();
            
            $.ajax({
                url: vsPopulateData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vs_clear_sample_data',
                    nonce: vsPopulateData.nonce
                },
                success: function(response) {
                    hideLoading();
                    
                    if (response.success) {
                        showMessage('success', response.data.message);
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showMessage('error', response.data.message || vsPopulateData.strings.error);
                    }
                },
                error: function() {
                    hideLoading();
                    showMessage('error', vsPopulateData.strings.error);
                }
            });
        }
        
        function showLoading() {
            $loading.show();
            $widget.find('button').prop('disabled', true);
        }
        
        function hideLoading() {
            $loading.hide();
            $widget.find('button').prop('disabled', false);
        }
        
        function createResponses() {
            showLoading();
            
            $.ajax({
                url: vsPopulateData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vs_create_responses',
                    nonce: vsPopulateData.nonce
                },
                success: function(response) {
                    hideLoading();
                    
                    if (response.success) {
                        showMessage('success', response.data.message);
                        
                        // Mostra estatísticas detalhadas se disponíveis
                        if (response.data.stats_by_votacao) {
                            let detailsHtml = '<div class="vs-response-stats"><h4>📊 Estatísticas das Respostas Criadas:</h4><ul>';
                            
                            Object.values(response.data.stats_by_votacao).forEach(function(votacao) {
                                if (votacao.responses_created > 0) {
                                    detailsHtml += '<li><strong>' + votacao.title + '</strong>: ' + votacao.responses_created + ' respostas</li>';
                                }
                            });
                            
                            detailsHtml += '</ul></div>';
                            showMessage('info', detailsHtml);
                        }
                        
                        setTimeout(function() {
                            location.reload();
                        }, 4000);
                    } else {
                        showMessage('error', response.data.message || vsPopulateData.strings.error);
                    }
                },
                error: function() {
                    hideLoading();
                    showMessage('error', vsPopulateData.strings.error);
                }
            });
        }
        
        function showMessage(type, message) {
            const $message = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $widget.prepend($message);
            
            // Auto remove após 5 segundos (ou 8 para info)
            const timeout = type === 'info' ? 8000 : 5000;
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, timeout);
        }
    });

})(jQuery);