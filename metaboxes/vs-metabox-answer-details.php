<?php
/**
 * Metabox de detalles de respuesta
 * 
 * @package VotingSystem\Metaboxes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registra el metabox de detalles de respuesta
 */
function vs_register_metabox_answer_details() {
    add_meta_box(
        'vs_answer_details',
        'Detalhes da Resposta',
        'vs_render_metabox_answer_details',
        'votacao_resposta',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'vs_register_metabox_answer_details');

/**
 * Renderiza el metabox de detalles de respuesta
 *
 * @param WP_Post $post Objeto del post actual
 */
function vs_render_metabox_answer_details($post) {
    if (!$post || !is_a($post, 'WP_Post')) {
        return;
    }

    // Obtener datos de la respuesta
    $votacao_id = absint(get_post_meta($post->ID, 'vs_votacao_id', true));
    $usuario_id = absint(get_post_meta($post->ID, 'vs_usuario_id', true));
    $respostas_detalhadas = get_post_meta($post->ID, 'vs_respostas_detalhadas', true);
    $resposta_unificada = get_post_meta($post->ID, 'vs_resposta_unificada', true);
    $data_submissao = get_post_meta($post->ID, 'vs_data_submissao', true);

    // Obtener información de la votación
    $votacao = null;
    $questions = [];
    if ($votacao_id) {
        $votacao = get_post($votacao_id);
        $questions = get_post_meta($votacao_id, 'vs_questions', true);
        if (!is_array($questions)) {
            $questions = [];
        }
    }

    // Obtener información del usuario
    $usuario = null;
    if ($usuario_id) {
        $usuario = get_userdata($usuario_id);
    }

    wp_nonce_field(VS_Nonce_Actions::FORM_ANSWER_DETAILS, 'vs_answer_details_nonce_field');
    ?>

    <div class="vs-answer-details-metabox">
        <table class="form-table">
            <tr>
                <th scope="row">Votação</th>
                <td>
                    <?php if ($votacao) : ?>
                        <strong><?php echo esc_html($votacao->post_title); ?></strong>
                        <br><small>ID: <?php echo esc_html($votacao_id); ?></small>
                        <br><a href="<?php echo get_edit_post_link($votacao_id); ?>" target="_blank">Editar Votação</a>
                    <?php else : ?>
                        <em>Votação não encontrada</em>
                    <?php endif; ?>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Usuário</th>
                <td>
                    <?php if ($usuario) : ?>
                        <strong><?php echo esc_html($usuario->display_name); ?></strong>
                        <br><small>Email: <?php echo esc_html($usuario->user_email); ?></small>
                        <br><small>ID: <?php echo esc_html($usuario_id); ?></small>
                        <br><a href="<?php echo get_edit_user_link($usuario_id); ?>" target="_blank">Editar Usuário</a>
                    <?php else : ?>
                        <em>Usuário não encontrado</em>
                    <?php endif; ?>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Data de Submissão</th>
                <td>
                    <?php if ($data_submissao) : ?>
                        <?php echo esc_html(date('d/m/Y H:i:s', strtotime($data_submissao))); ?>
                    <?php else : ?>
                        <em>Data não registrada</em>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <h3>Respostas Detalhadas</h3>
        <?php if (!empty($respostas_detalhadas) && is_array($respostas_detalhadas)) : ?>
            <div class="vs-detailed-answers">
                <?php foreach ($respostas_detalhadas as $index => $resposta) : ?>
                    <div class="vs-answer-item">
                        <h4>
                            <?php 
                            $question_label = isset($questions[$index]['label']) 
                                ? $questions[$index]['label'] 
                                : "Pergunta #" . ($index + 1);
                            echo esc_html($question_label);
                            ?>
                        </h4>
                        <div class="vs-answer-content">
                            <?php if (is_array($resposta)) : ?>
                                <ul>
                                    <?php foreach ($resposta as $item) : ?>
                                        <li><?php echo esc_html($item); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <p><?php echo esc_html($resposta); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (isset($resposta_unificada[$index]) && !empty($resposta_unificada[$index])) : ?>
                            <div class="vs-unified-answer" data-question-index="<?php echo esc_attr($index); ?>">
                                <div class="vs-unified-content">
                                    <strong>Resposta Unificada:</strong> 
                                    <span class="vs-unified-value"><?php echo esc_html($resposta_unificada[$index]); ?></span>
                                </div>
                                <div class="vs-unified-actions">
                                    <button type="button" 
                                            class="button button-small vs-clear-unified-btn" 
                                            data-resposta-id="<?php echo esc_attr($post->ID); ?>"
                                            data-question-index="<?php echo esc_attr($index); ?>"
                                            data-votacao-id="<?php echo esc_attr($votacao_id); ?>"
                                            title="Limpar resposta unificada">
                                        <span class="dashicons dashicons-trash"></span> Limpar
                                    </button>
                                </div>
                            </div>
                        <?php else : ?>
                            <div class="vs-no-unified-answer">
                                <button type="button" 
                                        class="button button-small vs-add-unified-metabox-btn" 
                                        data-resposta-id="<?php echo esc_attr($post->ID); ?>"
                                        data-question-index="<?php echo esc_attr($index); ?>"
                                        data-votacao-id="<?php echo esc_attr($votacao_id); ?>"
                                        title="Adicionar resposta unificada">
                                    <span class="dashicons dashicons-plus-alt"></span> Adicionar resposta unificada
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p><em>Nenhuma resposta detalhada encontrada.</em></p>
        <?php endif; ?>
    </div>

    <style>
    .vs-answer-details-metabox {
        padding: 10px 0;
    }
    
    .vs-detailed-answers {
        margin-top: 15px;
    }
    
    .vs-metabox-actions {
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
    }
    
    .vs-answer-item {
        margin-bottom: 20px;
        padding: 15px;
        border: 1px solid #e5e5e5;
        border-radius: 4px;
        background: #fafafa;
    }
    
    .vs-answer-item h4 {
        margin: 0 0 10px 0;
        color: #333;
        font-size: 14px;
        font-weight: 600;
    }
    
    .vs-answer-content {
        margin-bottom: 10px;
    }
    
    .vs-answer-content p,
    .vs-answer-content ul {
        margin: 0;
        padding: 8px;
        background: white;
        border: 1px solid #ddd;
        border-radius: 3px;
    }
    
    .vs-answer-content ul {
        padding-left: 25px;
    }
    
    .vs-unified-answer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px;
        background: #e7f3ff;
        border: 1px solid #b3d9ff;
        border-radius: 3px;
        font-size: 13px;
    }
    
    .vs-unified-content {
        flex: 1;
    }
    
    .vs-unified-actions {
        margin-left: 10px;
        flex-shrink: 0;
        display: flex;
        gap: 5px;
    }
    
    .vs-no-unified-answer {
        padding: 8px;
        text-align: center;
        border: 1px dashed #ccc;
        border-radius: 3px;
        background: #f9f9f9;
    }
    
    .vs-clear-unified-btn {
        font-size: 11px;
        padding: 2px 6px;
        height: auto;
        line-height: 1.2;
        border-color: #dc3232;
        color: #dc3232;
        background: transparent;
    }
    
    .vs-clear-unified-btn:hover {
        background: #dc3232;
        color: white;
        border-color: #dc3232;
    }
    
    .vs-add-unified-metabox-btn {
        font-size: 11px;
        padding: 4px 8px;
        height: auto;
        line-height: 1.2;
        border-color: #00a32a;
        color: #00a32a;
        background: transparent;
    }
    
    .vs-add-unified-metabox-btn:hover {
        background: #00a32a;
        color: white;
        border-color: #00a32a;
    }
    
    .vs-clear-unified-btn .dashicons,
    .vs-add-unified-metabox-btn .dashicons {
        font-size: 12px;
        width: 12px;
        height: 12px;
        margin-right: 2px;
        vertical-align: middle;
    }
    
    .vs-unified-value {
        font-weight: 600;
        color: #0073aa;
    }
    </style>
    <script>
    jQuery(document).ready(function($) {
        // Função para sincronizar limpeza entre metabox e tabela
        function syncClearUnifiedResponse(questionIndex, isFromMetabox = true) {
            if (isFromMetabox) {
                // Limpar na tabela quando limpo no metabox
                var $tableContainer = $('#votacao_resposta_detalhes .vs-unified-response[data-question-index="' + questionIndex + '"]');
                if ($tableContainer.length) {
                    var $wrapper = $tableContainer.find('.vs-unified-wrapper');
                    if ($wrapper.length) {
                        var postId = $tableContainer.data('post-id');
                        var votacaoId = $tableContainer.data('votacao-id');
                        $wrapper.html('<span class="vs-unified-content vs-empty-content"><em>—</em></span><button type="button" class="vs-add-unified-table-btn" title="Adicionar resposta unificada" data-question-index="' + questionIndex + '" data-post-id="' + postId + '" data-votacao-id="' + votacaoId + '"><span class="dashicons dashicons-plus-alt"></span></button>');
                    }
                }
            } else {
                // Limpar no metabox quando limpo na tabela
                var $metaboxContainer = $('.vs-unified-answer[data-question-index="' + questionIndex + '"]');
                if ($metaboxContainer.length) {
                    var $answerItem = $metaboxContainer.closest('.vs-answer-item');
                    var postId = $metaboxContainer.find('.vs-clear-unified-btn').data('resposta-id');
                    var votacaoId = $metaboxContainer.find('.vs-clear-unified-btn').data('votacao-id');
                    
                    $metaboxContainer.fadeOut(300, function() {
                        $(this).remove();
                        // Adicionar botão de adicionar
                        var addButtonHtml = `
                            <div class="vs-no-unified-answer">
                                <button type="button" 
                                        class="button button-small vs-add-unified-metabox-btn" 
                                        data-resposta-id="${postId}"
                                        data-question-index="${questionIndex}"
                                        data-votacao-id="${votacaoId}"
                                        title="Adicionar resposta unificada">
                                    <span class="dashicons dashicons-plus-alt"></span> Adicionar resposta unificada
                                </button>
                            </div>
                        `;
                        $answerItem.append(addButtonHtml);
                    });
                }
            }
        }

        // Handler para botões de adicionar/editar no metabox
        $(document).on('click', '.vs-add-unified-metabox-btn', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var questionIndex = $btn.data('question-index');
            var postId = $btn.data('resposta-id');
            var votacaoId = $btn.data('votacao-id');
            var isEdit = $btn.hasClass('vs-edit-unified-metabox-btn');
            
            // Usar a função global se disponível
            if (typeof window.openUnificationModal === 'function') {
                window.openUnificationModal(questionIndex, postId, votacaoId, isEdit);
            } else {
                // Se não tiver a função global, simular o clique no botão da tabela correspondente
                var $tableBtn = $('.vs-add-unified-table-btn[data-question-index="' + questionIndex + '"][data-post-id="' + postId + '"]');
                if ($tableBtn.length) {
                    $tableBtn.trigger('click');
                } else {
                    // Fallback: criar o botão temporariamente e clicar nele
                    var $tempBtn = $('<button class="vs-add-unified-table-btn" style="display:none;" data-question-index="' + questionIndex + '" data-post-id="' + postId + '" data-votacao-id="' + votacaoId + '"></button>');
                    $('body').append($tempBtn);
                    $tempBtn.trigger('click');
                    $tempBtn.remove();
                }
            }
        });

        // Event listener para limpeza no metabox
        $(document).on('click', '.vs-clear-unified-btn', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var respostaId = $btn.data('resposta-id');
            var questionIndex = $btn.data('question-index');
            var votacaoId = $btn.data('votacao-id');
            
            if (!confirm('Tem certeza que deseja limpar esta resposta unificada?')) {
                return;
            }
            
            var originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> Limpando...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vs_update_resposta_unificada',
                    nonce: '<?php echo wp_create_nonce("vs_unificacao_nonce"); ?>',
                    votacao_id: votacaoId,
                    nova_resposta_unificada: '',
                    clear_operation: 'true',
                    linhas: JSON.stringify([{
                        postId: parseInt(respostaId),
                        perguntaIndex: parseInt(questionIndex)
                    }])
                },
                success: function(response) {
                    if (response.success) {
                        // Remover do metabox e adicionar botão de adicionar
                        var $answerItem = $btn.closest('.vs-answer-item');
                        $btn.closest('.vs-unified-answer').fadeOut(300, function() {
                            $(this).remove();
                            // Adicionar botão de adicionar
                            var addButtonHtml = `
                                <div class="vs-no-unified-answer">
                                    <button type="button" 
                                            class="button button-small vs-add-unified-metabox-btn" 
                                            data-resposta-id="${respostaId}"
                                            data-question-index="${questionIndex}"
                                            data-votacao-id="${votacaoId}"
                                            title="Adicionar resposta unificada">
                                        <span class="dashicons dashicons-plus-alt"></span> Adicionar resposta unificada
                                    </button>
                                </div>
                            `;
                            $answerItem.append(addButtonHtml);
                        });
                        
                        // Sincronizar com a tabela
                        syncClearUnifiedResponse(questionIndex, true);
                        
                        // Disparar evento customizado para outras sincronizações
                        $(document).trigger('vs:unified-response-cleared', {
                            questionIndex: questionIndex,
                            source: 'metabox'
                        });
                        
                        // Mostrar mensagem de sucesso
                        if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
                            wp.data.dispatch('core/notices').createNotice(
                                'success',
                                'Resposta unificada removida com sucesso!',
                                { isDismissible: true }
                            );
                        }
                    } else {
                        alert('Erro ao limpar: ' + (response.data || 'Erro desconhecido'));
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro AJAX:', error);
                    alert('Erro de conexão ao tentar limpar a resposta unificada.');
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        });

        // Event listener para sincronização vinda da tabela
        $(document).on('vs:unified-response-cleared', function(e, data) {
            if (data.source === 'table') {
                syncClearUnifiedResponse(data.questionIndex, false);
            }
        });

        // Função para atualizar metabox quando salvo via tabela
        $(document).on('vs:unified-response-saved', function(e, data) {
            if (data.source === 'table') {
                var questionIndex = data.questionIndex;
                var value = data.value;
                var postId = data.postId;
                var votacaoId = data.votacaoId;
                
                updateMetaboxWithUnifiedAnswer(questionIndex, value, postId, votacaoId);
            }
        });

        // Função para atualizar o metabox com a resposta unificada completa
        function updateMetaboxWithUnifiedAnswer(questionIndex, value, postId, votacaoId) {
            var $answerItem = $('.vs-answer-item').eq(questionIndex);
            var $existingUnified = $answerItem.find('.vs-unified-answer');
            var $noUnified = $answerItem.find('.vs-no-unified-answer');
            
            if ($existingUnified.length) {
                // Atualizar valor existente
                $existingUnified.find('.vs-unified-value').text(value);
            } else if ($noUnified.length) {
                // Substituir botão de adicionar por resposta unificada completa
                $noUnified.fadeOut(300, function() {
                    $(this).remove();
                    var unifiedHtml = `
                        <div class="vs-unified-answer" data-question-index="${questionIndex}">
                            <div class="vs-unified-content">
                                <strong>Resposta Unificada:</strong> 
                                <span class="vs-unified-value">${escapeHtmlLocal(value)}</span>
                            </div>
                            <div class="vs-unified-actions">
                                <button type="button" 
                                        class="button button-small vs-clear-unified-btn" 
                                        data-resposta-id="${postId}"
                                        data-question-index="${questionIndex}"
                                        data-votacao-id="${votacaoId}"
                                        title="Limpar resposta unificada">
                                    <span class="dashicons dashicons-trash"></span> Limpar
                                </button>
                            </div>
                        </div>
                    `;
                    $answerItem.append(unifiedHtml);
                });
            } else {
                // Caso não exista nem unified nem no-unified, adicionar diretamente
                var unifiedHtml = `
                    <div class="vs-unified-answer" data-question-index="${questionIndex}">
                        <div class="vs-unified-content">
                            <strong>Resposta Unificada:</strong> 
                            <span class="vs-unified-value">${escapeHtmlLocal(value)}</span>
                        </div>
                        <div class="vs-unified-actions">
                            <button type="button" 
                                    class="button button-small vs-clear-unified-btn" 
                                    data-resposta-id="${postId}"
                                    data-question-index="${questionIndex}"
                                    data-votacao-id="${votacaoId}"
                                    title="Limpar resposta unificada">
                                <span class="dashicons dashicons-trash"></span> Limpar
                            </button>
                        </div>
                    </div>
                `;
                $answerItem.append(unifiedHtml);
            }
        }

        // Função auxiliar para escapar HTML
        function escapeHtmlLocal(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Implementação local da função de modal para o metabox
        function openUnificationModalLocal(questionIndex, postId, votacaoId, isEdit = false) {
            var title = isEdit ? 'Editar Resposta Unificada' : 'Adicionar Resposta Unificada';
            
            // Buscar a resposta original
            var originalResponse = '';
            var questionLabel = 'Pergunta ' + (parseInt(questionIndex) + 1);
            
            // Tentar encontrar a resposta original na tabela
            var $responseCell = $('tr[data-post-id="' + postId + '"] td').eq(parseInt(questionIndex) + 2);
            if ($responseCell.length) {
                originalResponse = $responseCell.text().trim();
                if (originalResponse.length > 100) {
                    originalResponse = originalResponse.substring(0, 100) + '...';
                }
            }
            
            // Carregar unificações existentes
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vs_ajax_get_unificacao_group_by_post_context',
                    nonce: '<?php echo wp_create_nonce("vs_unificacao_nonce"); ?>',
                    votacao_id: votacaoId,
                    question_index: questionIndex,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        var existingUnifications = response.data || [];
                        
                        var content = '<div class="unificacao-info" style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #0073aa;">';
                        content += '<h4 style="margin: 0 0 10px 0; color: #333; font-size: 14px;">Unificando resposta:</h4>';
                        content += '<p style="margin: 0 0 5px 0; font-weight: bold; color: #0073aa; font-size: 13px;">' + questionLabel + '</p>';
                        if (originalResponse) {
                            content += '<p style="margin: 0; font-style: italic; color: #666; font-size: 13px;""' + escapeHtmlLocal(originalResponse) + '"</p>';
                        }
                        content += '</div>';
                        
                        // Select para unificações existentes
                        if (existingUnifications.length > 0) {
                            content += '<div class="unificacao-existing" style="margin-bottom: 15px;">';
                            content += '<label for="select-unificacao-existente">Selecionar unificação existente:</label>';
                            content += '<select id="select-unificacao-existente" style="width: 100%; padding: 8px; margin-top: 5px;">';
                            content += '<option value="">Unificação existente...</option>';
                            
                            existingUnifications.forEach(function(unif, index) {
                                var displayText = unif.resposta_unificada;
                                if (unif.count > 1) {
                                    displayText += ' (usada em ' + unif.count + ' respostas)';
                                }
                                content += '<option value="' + escapeHtmlLocal(unif.resposta_unificada) + '">' + escapeHtmlLocal(displayText) + '</option>';
                            });
                            
                            content += '</select>';
                            content += '</div>';
                        }
                        
                        // Input para nova resposta
                        content += '<div class="unificacao-form">';
                        content += '<label for="nova-resposta-unificada">Ou digite uma nova resposta unificada:</label>';
                        content += '<input type="text" id="nova-resposta-unificada" placeholder="Digite a nova resposta unificada..." style="width: 100%; padding: 8px; margin-top: 5px;" />';
                        content += '</div>';
                        
                        content += '<div class="modal-actions" style="margin-top: 20px;">';
                        content += '<button type="button" class="modal-btn modal-btn-secondary" onclick="closeModalLocal()">Cancelar</button>';
                        content += '<button type="button" class="modal-btn modal-btn-primary" id="save-unification-local" data-question-index="' + questionIndex + '" data-post-id="' + postId + '" data-votacao-id="' + votacaoId + '">Salvar</button>';
                        content += '</div>';
                        
                        openModalLocal(title, content);
                    } else {
                        alert('Erro ao carregar unificações existentes.');
                    }
                },
                error: function() {
                    alert('Erro de conexão ao carregar unificações.');
                }
            });
        }

        // Função auxiliar para escapar HTML
        function escapeHtmlLocal(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Função para abrir modal local
        function openModalLocal(title, content) {
            // Remover modal existente se houver
            $('#vs-modal-local').remove();
            
            var modalHtml = '<div id="vs-modal-local" class="vs-modal-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 100000; display: flex; align-items: center; justify-content: center;">';
            modalHtml += '<div class="vs-modal-content" style="background: white; padding: 20px; border-radius: 5px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto;">';
            modalHtml += '<div class="vs-modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 10px;">';
            modalHtml += '<h3 style="margin: 0;">' + title + '</h3>';
            modalHtml += '<button type="button" class="vs-modal-close" onclick="closeModalLocal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>';
            modalHtml += '</div>';
            modalHtml += '<div class="vs-modal-body">' + content + '</div>';
            modalHtml += '</div>';
            modalHtml += '</div>';
            
            $('body').append(modalHtml);
        }

        // Função para fechar modal local
        function closeModalLocal() {
            $('#vs-modal-local').remove();
        }

        // Event listener para mudança no select de unificações existentes (modal local)
        $(document).on('change', '#select-unificacao-existente', function() {
            var selectedValue = $(this).val();
            $('#nova-resposta-unificada').val(selectedValue);
        });

        // Event listener para salvar unificação (modal local)
        $(document).on('click', '#save-unification-local', function() {
            var novaResposta = $('#nova-resposta-unificada').val().trim();
            
            if (!novaResposta) {
                alert('Por favor, digite uma resposta unificada ou selecione uma existente.');
                return;
            }
            
            var $btn = $(this);
            var originalText = $btn.text();
            var questionIndex = $btn.data('question-index');
            var postId = $btn.data('post-id');
            var votacaoId = $btn.data('votacao-id');
            
            $btn.prop('disabled', true).text('Salvando...');
            
            // Preparar dados para envio
            var linhas = [{
                postId: parseInt(postId),
                perguntaIndex: parseInt(questionIndex)
            }];
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vs_update_resposta_unificada',
                    nonce: '<?php echo wp_create_nonce("vs_unificacao_nonce"); ?>',
                    votacao_id: votacaoId,
                    nova_resposta_unificada: novaResposta,
                    linhas: JSON.stringify(linhas)
                },
                success: function(response) {
                    if (response.success) {
                        // Atualizar o metabox
                        updateMetaboxUnifiedResponse(questionIndex, postId, votacaoId, novaResposta);
                        
                        // Fechar modal
                        closeModalLocal();
                        
                        // Mostrar notificação de sucesso
                        if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
                            wp.data.dispatch('core/notices').createNotice(
                                'success',
                                'Resposta unificada salva com sucesso!',
                                { isDismissible: true }
                            );
                        } else {
                            alert('Resposta unificada salva com sucesso!');
                        }
                        
                        // Disparar evento para sincronização com tabela se existir
                        $(document).trigger('vs:unified-response-saved', {
                            questionIndex: questionIndex,
                            postId: postId,
                            votacaoId: votacaoId,
                            value: novaResposta,
                            source: 'metabox'
                        });
                    } else {
                        alert('Erro ao salvar: ' + (response.data || 'Erro desconhecido'));
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro AJAX:', error);
                    alert('Erro de conexão ao tentar salvar a resposta unificada.');
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        });

        // Função para atualizar a exibição no metabox
        function updateMetaboxUnifiedResponse(questionIndex, postId, votacaoId, newValue) {
            var $answerItem = $('.vs-answer-item').eq(questionIndex);
            var $existingUnified = $answerItem.find('.vs-unified-answer');
            var $noUnified = $answerItem.find('.vs-no-unified-answer');
            
            if ($existingUnified.length) {
                // Atualizar existente
                $existingUnified.find('.vs-unified-value').text(newValue);
            } else if ($noUnified.length) {
                // Substituir botão de adicionar por resposta unificada
                $noUnified.fadeOut(300, function() {
                    $(this).remove();
                    var unifiedHtml = `
                        <div class="vs-unified-answer" data-question-index="${questionIndex}">
                            <div class="vs-unified-content">
                                <strong>Resposta Unificada:</strong> 
                                <span class="vs-unified-value">${escapeHtmlLocal(newValue)}</span>
                            </div>
                            <div class="vs-unified-actions">
                                <button type="button" 
                                        class="button button-small vs-clear-unified-btn" 
                                        data-resposta-id="${postId}"
                                        data-question-index="${questionIndex}"
                                        data-votacao-id="${votacaoId}"
                                        title="Limpar resposta unificada">
                                    <span class="dashicons dashicons-trash"></span> Limpar
                                </button>
                            </div>
                        </div>
                    `;
                    $answerItem.append(unifiedHtml);
                });
            }
        }

        // Fechar modal ao clicar fora dele
        $(document).on('click', '#vs-modal-local', function(e) {
            if (e.target === this) {
                closeModalLocal();
            }
        });
    });
    </script>
    <?php
}