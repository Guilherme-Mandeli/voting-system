/**
 * Funcionalidades JavaScript para ranking administrativo
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        initRankingModal();
        initRankingFilters();
        initCardFilters();
        updateCardGroupModeVisibility();
        updateCardFilterDescription();
    });

    function initRankingModal() {
        // Abre o modal
        $('#vs-show-all-results').on('click', function(e) {
            e.preventDefault();
            
            var votacaoId = $(this).data('votacao-id');
            var questionFilter = $(this).data('question-filter');
            var groupMode = $(this).data('group-mode');
            
            // Define os valores dos filtros no modal
            $('#modal_question_filter').val(questionFilter);
            $('input[name="modal_group_mode"][value="' + groupMode + '"]').prop('checked', true);
            
            // Atualiza a visibilidade do agrupamento no modal
            updateModalGroupModeVisibility();
            updateModalFilterDescription();
            updateModalExportUrl(votacaoId);
            
            $('#vs-all-ranking-modal').show();
            loadAllRanking(votacaoId, questionFilter, groupMode, 1);
        });

        // Fecha o modal
        $('.vs-modal-close, .vs-modal').on('click', function(e) {
            if (e.target === this) {
                $('#vs-all-ranking-modal').hide();
            }
        });
    }

    function initRankingFilters() {
        // Filtros do modal - usando event delegation para garantir que funcione
        $(document).on('change', '#modal_question_filter', function() {
            console.log('Modal question filter changed to:', $(this).val()); // Debug
            updateModalGroupModeVisibility();
            updateModalFilterDescription();
            reloadModalRanking();
        });

        $(document).on('change', 'input[name="modal_group_mode"]', function() {
            console.log('Modal group mode changed to:', $(this).val()); // Debug
            updateModalFilterDescription();
            reloadModalRanking();
        });
    }

    // Nova função para filtros do card
    function initCardFilters() {
        // Filtro de pergunta do card
        $('#question_filter').on('change', function() {
            updateCardGroupModeVisibility();
            redirectWithFilters();
        });

        // Filtros de modo de agrupamento do card
        $('input[name="group_mode"]').on('change', function() {
            redirectWithFilters();
        });
    }

    // Atualiza a visibilidade do agrupamento no card
    function updateCardGroupModeVisibility() {
        var questionFilter = $('#question_filter').val();
        console.log('Card question filter:', questionFilter); // Debug
        if (questionFilter === 'all') {
            $('#group-mode-container').show();
        } else {
            $('#group-mode-container').hide();
        }
        
        // Atualiza a descrição do card
        updateCardFilterDescription();
    }

    // Redireciona a página com os novos filtros
    function redirectWithFilters() {
        var questionFilter = $('#question_filter').val();
        var groupMode = $('input[name="group_mode"]:checked').val();
        
        // Obtém a URL atual
        var currentUrl = new URL(window.location.href);
        
        // Atualiza os parâmetros de filtro
        currentUrl.searchParams.set('question_filter', questionFilter);
        currentUrl.searchParams.set('group_mode', groupMode);
        
        // Redireciona para a nova URL
        window.location.href = currentUrl.toString();
    }

    function updateModalGroupModeVisibility() {
        var questionFilter = $('#modal_question_filter').val();
        console.log('Updating modal visibility, question filter:', questionFilter); // Debug
        
        // CORREÇÃO: O ID correto é modal_group-mode-container (com underscore)
        var container = $('#modal_group-mode-container');
        console.log('Container found:', container.length); // Debug
        
        if (questionFilter === 'all') {
            container.show();
            console.log('Showing modal group mode container'); // Debug
        } else {
            container.hide();
            console.log('Hiding modal group mode container'); // Debug
        }
    }

    // 2. Agora vamos atualizar o JavaScript para controlar ambos os elementos:
    function updateModalFilterDescription() {
        var questionFilter = $('#modal_question_filter').val();
        var groupMode = $('input[name="modal_group_mode"]:checked').val();
        var description = '';
        
        if (questionFilter === 'all') {
            if (groupMode === 'by_answer') {
                description = '<strong>Por resposta:</strong> considera respostas iguais independentemente da pergunta.';
            } else {
                description = '<strong>Por pergunta:</strong> considera respostas iguais apenas dentro da mesma pergunta.';
            }
        } else {
            description = 'Exibindo resultados apenas para a pergunta selecionada.';
        }
        
        // Atualiza o elemento do modal (se existir)
        var modalElement = $('#modal-filter-description');
        if (modalElement.length > 0) {
            modalElement.html(description);
        }
    }

    // Nova função para atualizar a descrição do card
    function updateCardFilterDescription() {
        var questionFilter = $('#question_filter').val();
        var groupMode = $('input[name="group_mode"]:checked').val();
        var description = '';
        var cardElement = $('#filter-description');
        
        if (questionFilter === 'all') {
            if (groupMode === 'by_answer') {
                description = '<strong>Por resposta:</strong> considera respostas iguais independentemente da pergunta.';
            } else {
                description = '<strong>Por pergunta:</strong> considera respostas iguais apenas dentro da mesma pergunta.';
            }
            cardElement.show().html(description);
        } else {
            cardElement.hide();
        }
    }

    // Atualiza a visibilidade do agrupamento no card
    function updateCardGroupModeVisibility() {
        var questionFilter = $('#question_filter').val();
        console.log('Card question filter:', questionFilter); // Debug
        if (questionFilter === 'all') {
            $('#group-mode-container').show();
        } else {
            $('#group-mode-container').hide();
        }
        
        // Atualiza a descrição do card também
        updateCardFilterDescription();
    }

    // Nova função para filtros do card
    function initCardFilters() {
        // Filtro de pergunta do card
        $('#question_filter').on('change', function() {
            updateCardGroupModeVisibility();
            redirectWithFilters();
        });

        // Filtros de modo de agrupamento do card
        $('input[name="group_mode"]').on('change', function() {
            updateCardFilterDescription();
            redirectWithFilters();
        });
        
        // Inicializa a descrição do card na primeira carga
        updateCardFilterDescription();
    }

    function updateModalExportUrl(votacaoId) {
        var questionFilter = $('#modal_question_filter').val();
        var groupMode = $('input[name="modal_group_mode"]:checked').val();
        
        var exportUrl = vsAdminAjax.adminUrl + 'admin-post.php?' + $.param({
            action: 'export_csv_ranking',
            votacao_id: votacaoId,
            group_mode: groupMode,
            question_filter: questionFilter
        });
        
        $('#modal-export-csv').attr('href', exportUrl);
        
        // Atualiza o texto do botão baseado no modo de agrupamento
        var exportText = '';
        if (groupMode === 'by_question') {
            exportText = 'Exportar por pergunta | CSV';
        } else {
            exportText = 'Exportar por resposta | CSV';
        }
        $('#modal-export-text').text(exportText);
    }

    function reloadModalRanking() {
        var votacaoId = $('#vs-show-all-results').data('votacao-id');
        var questionFilter = $('#modal_question_filter').val();
        var groupMode = $('input[name="modal_group_mode"]:checked').val();
        
        updateModalExportUrl(votacaoId);
        loadAllRanking(votacaoId, questionFilter, groupMode, 1);
    }

    function loadAllRanking(votacaoId, questionFilter, groupMode, page) {
        $('#vs-modal-ranking-content').html('<div class="loading">Carregando...</div>');
        
        $.ajax({
            url: vsAdminAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vs_get_all_ranking',
                votacao_id: votacaoId,
                question_filter: questionFilter,
                group_mode: groupMode,
                page: page,
                nonce: vsAdminAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#vs-modal-ranking-content').html(response.data.html);
                    
                    // Adiciona eventos de paginação
                    $('.pagination-btn').on('click', function(e) {
                        e.preventDefault();
                        var newPage = $(this).data('page');
                        loadAllRanking(votacaoId, questionFilter, groupMode, newPage);
                    });
                } else {
                    $('#vs-modal-ranking-content').html('<p>Erro ao carregar ranking: ' + response.data + '</p>');
                }
            },
            error: function() {
                $('#vs-modal-ranking-content').html('<p>Erro ao carregar ranking.</p>');
            }
        });
    }

})(jQuery);