jQuery(document).ready(function($) {
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
    
    // Botão Criar Respostas
    $('.vs-create-responses').on('click', function() {
        if (confirm(vsPopulateData.strings.confirm_create_responses)) {
            createResponses();
        }
    });
    
    // Botão Limpar Respostas
    $('.vs-clear-responses').on('click', function() {
        if (confirm(vsPopulateData.strings.confirm_clear_responses)) {
            clearResponses();
        }
    });
    
    function populateData() {
        const button = $('.vs-populate-data');
        const originalText = button.text();
        
        button.prop('disabled', true).text(vsPopulateData.strings.processing);
        
        $.ajax({
            url: vsPopulateData.ajaxurl,
            type: 'POST',
            data: {
                action: 'vs_populate_data',
                nonce: vsPopulateData.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessage(response.data.message || vsPopulateData.strings.error, 'error');
                }
            },
            error: function() {
                showMessage(vsPopulateData.strings.error, 'error');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    }
    
    function clearData() {
        const button = $('.vs-clear-data');
        const originalText = button.text();
        
        button.prop('disabled', true).text(vsPopulateData.strings.processing);
        
        $.ajax({
            url: vsPopulateData.ajaxurl,
            type: 'POST',
            data: {
                action: 'vs_clear_sample_data',
                nonce: vsPopulateData.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessage(response.data.message || vsPopulateData.strings.error, 'error');
                }
            },
            error: function() {
                showMessage(vsPopulateData.strings.error, 'error');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    }
    
    function createResponses() {
        const button = $('.vs-create-responses');
        const originalText = button.text();
        
        button.prop('disabled', true).text(vsPopulateData.strings.processing);
        
        $.ajax({
            url: vsPopulateData.ajaxurl,
            type: 'POST',
            data: {
                action: 'vs_create_responses',
                nonce: vsPopulateData.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessage(response.data.message || vsPopulateData.strings.error, 'error');
                }
            },
            error: function() {
                showMessage(vsPopulateData.strings.error, 'error');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    }
    
    function clearResponses() {
        const button = $('.vs-clear-responses');
        const originalText = button.text();
        
        button.prop('disabled', true).text(vsPopulateData.strings.processing);
        
        $.ajax({
            url: vsPopulateData.ajaxurl,
            type: 'POST',
            data: {
                action: 'vs_clear_responses',
                nonce: vsPopulateData.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessage(response.data.message || vsPopulateData.strings.error, 'error');
                }
            },
            error: function() {
                showMessage(vsPopulateData.strings.error, 'error');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    }
    
    function showMessage(message, type) {
        const messageDiv = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.vs-population-widget').prepend(messageDiv);
        
        setTimeout(() => {
            messageDiv.fadeOut(() => messageDiv.remove());
        }, 5000);
    }
});