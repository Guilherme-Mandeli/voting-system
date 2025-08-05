jQuery(document).ready(function($) {
    // Função que é chamada para carregar as respostas da votação via AJAX
    function loadResponses(user_id, votacao_id) {
        $.post(vsAjax.ajaxurl, {
            action: 'vs_get_user_votes',    // Ação no backend para recuperar as respostas do CPT votacao_resposta
            user_id: user_id,
            votacao_id: votacao_id,
            vs_nonce: vsAjax.nonce
        }, function(response) {
            if (response.success) {
                var data = response.data;

                var modalContent = '<h2 class="modal-title">Respostas do Usuário ' + data.user_name + '</h2>';
                modalContent += '<p class="meta-data"><strong>Data da Resposta:</strong> ' + data.data_envio + '</p>';
                modalContent += '<table class="respostas-table"><thead><tr><th class="text-left">Pergunta</th><th class="text-left">Resposta</th></tr></thead><tbody>';

                data.respostas.forEach(function(item) {
                    modalContent += '<tr><td class="question">' + item.question + '</td><td class="answer">' + item.answer + '</td></tr>';
                });

                modalContent += '</tbody></table>';
                modalContent += '<button class="close-modal">Fechar</button>';

                var modal = jQuery('<div class="resposta-modal" style="display:none;">' + modalContent + '</div>');
                var overlay = jQuery('<div class="modal-overlay"></div>');

                // Mostrar modal e overlay
                jQuery('body').append(modal).append(overlay);
                modal.css({
                    opacity: 0,
                    top: '40%',
                    display: 'block'
                }).animate({
                    opacity: 1,
                    top: '50%'
                }, 150);
                overlay.fadeIn(150);

                // Fechar modal ao clicar no botão
                modal.find('.close-modal').click(function() {
                    modal.animate({
                        opacity: 0,
                        top: "-=50"
                    }, 125, function() {
                        modal.remove();
                    });
                    overlay.fadeOut(125, function() {
                        overlay.remove();
                    });
                });

                // Fechar modal ao clicar no overlay
                overlay.click(function() {
                    modal.animate({
                        opacity: 0,
                        top: "-=50"
                    }, 125, function() {
                        modal.remove();
                    });
                    overlay.fadeOut(125, function() {
                        overlay.remove();
                    });
                });
            } else {
                alert('Erro ao carregar as respostas: ' + response.data);
            }
        }).fail(function(xhr, textStatus, errorThrown) {
            alert('Erro de requisição Ajax: ' + errorThrown);
        });
    }

    // CORRIGIDO: Listener para botões com classe vs-view-user-votes
    $(document).on('click', '.vs-view-user-votes', function() {
        var user_id = $(this).data('user-id');
        var votacao_id = $(this).data('votacao-id');

        if (user_id && votacao_id) {
            loadResponses(user_id, votacao_id);
        } else {
            alert('Erro: IDs de usuário ou votação não encontrados.');
        }
    });

    // MANTIDO: Listener original para compatibilidade
    $(document).on('click', '.button-primary[data-user-id][data-votacao-id]', function() {
        var user_id = $(this).data('user-id');
        var votacao_id = $(this).data('votacao-id');

        loadResponses(user_id, votacao_id);
    });
});
