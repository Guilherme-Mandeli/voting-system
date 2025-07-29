/**
 * Funcionalidades JavaScript para ranking administrativo
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        initRankingModal();
        initRankingFilters();
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
            
            // Atualiza a visibilidade do agrupamento
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
        // Filtros do modal
        $('#modal_question_filter').on('change', function() {
            updateModalGroupModeVisibility();
            updateModalFilterDescription();
            reloadModalRanking();
        });

        $('input[name="modal_group_mode"]').on('change', function() {
            updateModalFilterDescription();
            reloadModalRanking();
        });
    }

    function updateModalGroupModeVisibility() {
        var questionFilter = $('#modal_question_filter').val();
        if (questionFilter === 'all') {
            $('#modal-group-mode-container').show();
        } else {
            $('#modal-group-mode-container').hide();
        }
    }

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
        
        $('#modal-filter-description').html(description);
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