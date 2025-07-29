<?php
/**
 * Template para o card de ranking
 * 
 * @package VotingSystem
 * @subpackage AdminTemplates
 */

// Impede acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza o card de ranking dinâmico
 */
function vs_render_ranking_card($votacao_id, $total_users) {
    $perguntas = vs_get_voting_questions($votacao_id);
    $question_filter = isset($_GET['question_filter']) ? sanitize_text_field($_GET['question_filter']) : 'all';
    $group_mode = isset($_GET['group_mode']) ? sanitize_text_field($_GET['group_mode']) : 'by_answer';
    
    // Valida o modo de agrupamento
    if (!in_array($group_mode, ['by_question', 'by_answer'])) {
        $group_mode = 'by_answer';
    }
    
    $ranking = vs_get_voting_ranking($votacao_id, $question_filter, $group_mode);
    $top_ranking = array_slice($ranking, 0, 5); // Limita a 5 resultados para exibição inicial
    
    // URL para exportação CSV
    $export_url = admin_url('admin-post.php') . '?' . http_build_query([
        'action' => 'export_csv_ranking',
        'votacao_id' => $votacao_id,
        'group_mode' => $group_mode,
        'question_filter' => $question_filter
    ]);
    ?>
    <div class="ranking-card">
        <div class="card-header">
            <div class="card-header-top">
                <div>
                    <p><strong>Votos totais:</strong> <?php echo esc_html($total_users); ?></p>
                    <h2>Mais votados</h2>
                </div>
                <div>
                    <a href="<?php echo esc_url($export_url); ?>" class="button button-secondary export-button">
                        <?php if ($group_mode === 'by_question') : ?>
                            Exportar por pergunta | CSV
                        <?php else : ?>
                            Exportar por resposta | CSV
                        <?php endif; ?>
                    </a>
                </div>
            </div>
            <p>Respostas mais recorrentes de:</p>

            <div class="filters-container">
                <div>
                    <label for="question_filter" class="screen-reader-text">Filtrar por pergunta</label>
                    <select id="question_filter" name="question_filter" class="question-filter">
                        <option value="all" <?php selected($question_filter, 'all'); ?>>Todas as perguntas</option>
                        <?php foreach ($perguntas as $index => $pergunta) : ?>
                            <option value="q<?php echo $index; ?>" <?php selected($question_filter, "q{$index}"); ?>>
                                <?php echo esc_html($pergunta['label'] ?? "Pergunta #" . ($index + 1)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($question_filter === 'all') : ?>
                    <div id="group-mode-container">
                        <fieldset>
                            <legend class="group-mode-legend">Modo de agrupamento:</legend>
                            <div class="group-mode-options">
                                <label class="group-mode-label">
                                    <input type="radio" name="group_mode" value="by_answer" <?php checked($group_mode, 'by_answer'); ?>>
                                    Por resposta
                                </label>
                                <label class="group-mode-label">
                                    <input type="radio" name="group_mode" value="by_question" <?php checked($group_mode, 'by_question'); ?>>
                                    Por pergunta
                                </label>
                            </div>
                        </fieldset>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($question_filter === 'all') : ?>
                <div class="filter-description">
                    <strong>Por resposta:</strong> considera respostas iguais independentemente da pergunta.<br>
                    <strong>Por pergunta:</strong> considera respostas iguais apenas dentro da mesma pergunta.
                </div>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <?php if (empty($top_ranking)) : ?>
                <p><em>Nenhuma resposta encontrada para os filtros selecionados.</em></p>
            <?php else : ?>
                <table class="vote-results-table">
                    <thead>
                        <tr>
                            <th>Resposta</th>
                            <th>Votos</th>
                            <?php if ($group_mode === 'by_question') : ?>
                                <th>Pergunta</th>
                            <?php endif; ?>
                            <th>Unificado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_ranking as $stat) : ?>
                            <tr>
                                <td><?php echo esc_html($stat['answer']); ?></td>
                                <td><?php echo esc_html($stat['count']); ?></td>
                                <?php if ($group_mode === 'by_question') : ?>
                                    <td><?php echo esc_html($stat['question_label']); ?></td>
                                <?php endif; ?>
                                <td>
                                    <span class="unified-badge <?php echo $stat['is_unified'] ? 'unified-yes' : 'unified-no'; ?>">
                                        <?php echo $stat['is_unified'] ? 'Sim' : 'Não'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="show-all-container">
                    <a href="#" id="vs-show-all-results" class="button button-primary" 
                       data-votacao-id="<?php echo esc_attr($votacao_id); ?>"
                       data-question-filter="<?php echo esc_attr($question_filter); ?>"
                       data-group-mode="<?php echo esc_attr($group_mode); ?>">
                        Ver todos os votos (<?php echo count($ranking); ?>)
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para exibir todo o ranking -->
    <div id="vs-all-ranking-modal" class="vs-modal" style="display: none;">
        <div class="vs-modal-content">
            <div class="vs-modal-header">
                <h2>Mais votados</h2>
                <span class="vs-modal-close">&times;</span>
            </div>
            
            <!-- Seção de filtros do modal -->
            <div class="vs-modal-filters">
                <div class="modal-filters-container">
                    <div>
                        <label for="modal_question_filter" class="modal-filter-label">Pergunta:</label>
                        <select id="modal_question_filter" name="modal_question_filter" class="modal-question-filter">
                            <option value="all">Todas as perguntas</option>
                            <?php foreach ($perguntas as $index => $pergunta) : ?>
                                <option value="q<?php echo $index; ?>">
                                    <?php echo esc_html($pergunta['label'] ?? "Pergunta #" . ($index + 1)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="modal-group-mode-container">
                        <fieldset>
                            <legend class="modal-group-legend">Agrupamento:</legend>
                            <div class="modal-group-options">
                                <label class="modal-group-label">
                                    <input type="radio" name="modal_group_mode" value="by_answer">
                                    Por resposta
                                </label>
                                <label class="modal-group-label">
                                    <input type="radio" name="modal_group_mode" value="by_question">
                                    Por pergunta
                                </label>
                            </div>
                        </fieldset>
                    </div>

                    <div>
                        <a href="#" id="modal-export-csv" class="button button-secondary modal-export-button">
                            <span id="modal-export-text">Exportar | CSV</span>
                        </a>
                    </div>
                </div>
                
                <div id="modal-filter-description" class="modal-filter-description">
                    <!-- Descrição será atualizada via JavaScript -->
                </div>
            </div>
            
            <div class="vs-modal-body" id="vs-modal-ranking-content">
                <!-- Conteúdo será carregado via AJAX -->
                <div class="loading">Carregando...</div>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
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
            
            var exportUrl = '<?php echo admin_url('admin-post.php'); ?>?' + $.param({
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
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'vs_get_all_ranking',
                    votacao_id: votacaoId,
                    question_filter: questionFilter,
                    group_mode: groupMode,
                    page: page,
                    nonce: '<?php echo wp_create_nonce('vs_ranking_nonce'); ?>'
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
    });
    </script>
    <?php
}
