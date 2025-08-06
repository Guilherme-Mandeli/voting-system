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
    
    .vs-clear-unified-btn .dashicons {
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
                        $wrapper.replaceWith('<span class="vs-unified-content vs-empty-content"><em>—</em></span>');
                    }
                }
            } else {
                // Limpar no metabox quando limpo na tabela
                var $metaboxContainer = $('.vs-unified-answer[data-question-index="' + questionIndex + '"]');
                if ($metaboxContainer.length) {
                    $metaboxContainer.fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            }
        }

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
                        // Remover do metabox
                        $btn.closest('.vs-unified-answer').fadeOut(300, function() {
                            $(this).remove();
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
    });
    </script>
    <?php
}