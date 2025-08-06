<?php
/**
 * Custom post type: votacao_resposta
 *
 * Stores individual user responses for a given voting (votacoes).
 * Provides admin listing columns and a meta box that shows each answer slot
 * along with its per-response unified value (stored in post meta 'vs_resposta_unificada'
 * as an associative array: [ question_index => unified_string ] ).
 *
 * @package VotingSystem\Admin\CPT
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the 'votacao_resposta' custom post type.
 *
 * @return void
 */
function vs_register_cpt_answer() {
    register_post_type(
        'votacao_resposta',
        array(
            'labels' => array(
                'name'          => __( 'Respostas de Votação', 'voting-system' ),
                'singular_name' => __( 'Resposta de Votação', 'voting-system' ),
            ),
            'public'       => true,
            'show_ui'      => true,
            'supports'     => array( 'title', 'custom-fields' ),
            'capability_type' => 'post',
            // Adjust other flags (menu_position, menu_icon etc.) as needed.
        )
    );
}
add_action( 'init', 'vs_register_cpt_answer' );

/**
 * Restrict access to 'votacao_resposta' posts to logged-in users.
 *
 * @return void
 */
function vs_restrict_answer_to_logged_in() {
    if ( is_singular( 'votacao_resposta' ) && ! is_user_logged_in() ) {
        wp_safe_redirect( wp_login_url( get_permalink() ) );
        exit;
    }
}
add_action( 'template_redirect', 'vs_restrict_answer_to_logged_in' );

/**
 * Automatically publish 'votacao_resposta' posts when restored from trash.
 *
 * @param int $post_id The post ID that was restored.
 * @return void
 */
function vs_auto_publish_restored_answer( $post_id ) {
    $post = get_post( $post_id );
    
    // Verifica se é um post do tipo 'votacao_resposta'
    if ( ! $post || $post->post_type !== 'votacao_resposta' ) {
        return;
    }
    
    // Se o post foi restaurado como rascunho, publica automaticamente
    if ( $post->post_status === 'draft' ) {
        wp_update_post( array(
            'ID' => $post_id,
            'post_status' => 'publish'
        ) );
    }
}
add_action( 'untrashed_post', 'vs_auto_publish_restored_answer' );

/* ------------------------------------------------------------------------- *
 * Admin List Table Columns
 * ------------------------------------------------------------------------- */

/**
 * Filter admin columns for 'votacao_resposta'.
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function vs_manage_answer_columns( $columns ) {
    return array(
        'cb'        => isset( $columns['cb'] ) ? $columns['cb'] : '<input type="checkbox" />',
        'title'     => __( 'Título', 'voting-system' ),
        'usuario'   => __( 'Usuário', 'voting-system' ),
        'votacao'   => __( 'Votação', 'voting-system' ),
        'data_envio'=> __( 'Data de Envio', 'voting-system' ),
    );
}
add_filter( 'manage_votacao_resposta_posts_columns', 'vs_manage_answer_columns' );

/**
 * Render custom column data in admin list table.
 *
 * @param string $column  Column key.
 * @param int    $post_id Current post ID.
 * @return void
 */
function vs_manage_answer_columns_cb( $column, $post_id ) {

    if ( 'usuario' === $column ) {
        $user_id = get_post_meta( $post_id, 'vs_usuario_id', true );
        if ( $user_id ) {
            $user_info = get_userdata( $user_id );
            if ( $user_info ) {
                printf(
                    '#%d %s',
                    absint( $user_info->ID ),
                    esc_html( $user_info->user_email )
                );
            } else {
                printf( 'ID %d', absint( $user_id ) );
            }
        } else {
            echo '&#8212;';
        }
        return;
    }

    if ( 'votacao' === $column ) {
        $votacao_id = get_post_meta( $post_id, 'vs_votacao_id', true );
        if ( $votacao_id ) {
            $post_votacao = get_post( $votacao_id );
            if ( $post_votacao ) {
                printf(
                    '<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s (ID %3$d)</a>',
                    esc_url( get_edit_post_link( $votacao_id ) ),
                    esc_html( get_the_title( $post_votacao ) ),
                    absint( $votacao_id )
                );
            } else {
                printf( 'ID %d', absint( $votacao_id ) );
            }
        } else {
            echo '&#8212;';
        }
        return;
    }

    if ( 'data_envio' === $column ) {
        $data_envio = get_post_meta( $post_id, 'vs_data_envio', true );
        if ( $data_envio ) {
            echo esc_html(
                date_i18n(
                    get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
                    strtotime( $data_envio )
                )
            );
        } else {
            echo esc_html( get_the_date( '', $post_id ) );
        }
        return;
    }
}
add_action( 'manage_votacao_resposta_posts_custom_column', 'vs_manage_answer_columns_cb', 10, 2 );

/* ------------------------------------------------------------------------- *
 * Meta Box: Detailed Responses (per post)
 * ------------------------------------------------------------------------- */

/**
 * Register the meta box for 'votacao_resposta'.
 *
 * @return void
 */
function vs_add_answer_metabox() {
    add_meta_box(
        'votacao_resposta_detalhes',
        __( 'Detalhes da Resposta', 'voting-system' ),
        'vs_render_answer_metabox',
        'votacao_resposta',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'vs_add_answer_metabox' );

/**
 * Render the per-response details meta box.
 *
 * @param WP_Post $post Current response post object.
 * @return void
 */
function vs_render_answer_metabox( $post ) {

    $respostas = get_post_meta( $post->ID, 'vs_respostas_detalhadas', true );

    echo '<div style="max-height: 400px; overflow-y: auto;">';

    if ( empty( $respostas ) || ! is_array( $respostas ) ) {
        echo '<p><em>' . esc_html__( 'Não há respostas registradas.', 'voting-system' ) . '</em></p>';
        echo '</div>';
        return;
    }

    $votacao_id = get_post_meta( $post->ID, 'vs_votacao_id', true );
    $questions  = get_post_meta( $votacao_id, 'vs_questions', true );
    if ( ! is_array( $questions ) ) {
        $questions = array();
    }

    $unifications = get_post_meta( $post->ID, 'vs_resposta_unificada', true );
    if ( ! is_array( $unifications ) ) {
        $unifications = array();
    }

    echo '<table id="votacao_resposta_detalhes" style="width: 100%; border-collapse: collapse;">';
    echo '<thead><tr>';
    echo '<th style="border: 1px solid #ccc; padding: 8px; background: #f9f9f9;">' . esc_html__( 'Pergunta', 'voting-system' ) . '</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px; background: #f9f9f9;">' . esc_html__( 'Resposta', 'voting-system' ) . '</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px; background: #f9f9f9;">' . esc_html__( 'Resposta Unificada', 'voting-system' ) . '</th>';
    echo '</tr></thead><tbody>';

    foreach ( $respostas as $index => $resposta_value ) {

        $label = isset( $questions[ $index ]['label'] )
            ? $questions[ $index ]['label']
            : sprintf( 'Pergunta #%d', ( $index + 1 ) );

        if ( is_array( $resposta_value ) ) {
            $resp = implode( ', ', array_map( 'sanitize_text_field', $resposta_value ) );
        } else {
            $resp = sanitize_text_field( $resposta_value );
        }

        $resp_unificada = '';
        if ( isset( $unifications[ $index ] ) && '' !== trim( $unifications[ $index ] ) ) {
            $resp_unificada = $unifications[ $index ];
        }

        echo '<tr>';
        echo '<td style="border: 1px solid #ccc; padding: 8px; vertical-align: top;">' . esc_html( $label ) . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html( $resp ) . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px; text-align:center;">';

        // Container principal com identificador da pergunta
        echo '<span class="vs-unified-response" data-question-index="' . esc_attr( $index ) . '" data-post-id="' . esc_attr( $post->ID ) . '" data-votacao-id="' . esc_attr( $votacao_id ) . '">';
        
        if ( '' !== $resp_unificada ) {
            // Span com classe para conteúdo preenchido + botão limpar
            echo '<div class="vs-unified-wrapper">';
            echo '<span class="vs-unified-content vs-has-content">' . esc_html( $resp_unificada ) . '</span>';
            echo '<button type="button" class="vs-clear-unified-table-btn" title="Limpar resposta unificada">';
            echo '<span class="dashicons dashicons-trash"></span>';
            echo '</button>';
            echo '</div>';
        } else {
            // Span com classe para conteúdo vazio
            echo '<span class="vs-unified-content vs-empty-content"><em>—</em></span>';
        }
        
        echo '</span>';

        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
    
    // Adicionar CSS e JavaScript
    ?>
    <style>
    .vs-unified-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .vs-unified-content {
        flex: 1;
    }
    
    .vs-clear-unified-table-btn {
        background: transparent;
        border: 1px solid #dc3232;
        color: #dc3232;
        padding: 2px 4px;
        border-radius: 3px;
        cursor: pointer;
        font-size: 11px;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        transition: all 0.2s ease;
    }
    
    .vs-clear-unified-table-btn:hover {
        background: #dc3232;
        color: white;
        border-color: #dc3232;
    }
    
    .vs-clear-unified-table-btn .dashicons {
        font-size: 12px;
        width: 12px;
        height: 12px;
    }
    
    .vs-clear-unified-table-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .vs-unified-content.vs-has-content {
        color: #0073aa;
        font-weight: 500;
    }
    
    .vs-unified-content.vs-empty-content {
        color: #666;
        font-style: italic;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Função para sincronizar limpeza entre tabela e metabox
        function syncClearUnifiedResponse(questionIndex, isFromTable = true) {
            if (isFromTable) {
                // Limpar no metabox quando limpo na tabela
                var $metaboxContainer = $('.vs-unified-answer[data-question-index="' + questionIndex + '"]');
                if ($metaboxContainer.length) {
                    $metaboxContainer.fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            } else {
                // Limpar na tabela quando limpo no metabox
                var $tableContainer = $('#votacao_resposta_detalhes .vs-unified-response[data-question-index="' + questionIndex + '"]');
                if ($tableContainer.length) {
                    var $wrapper = $tableContainer.find('.vs-unified-wrapper');
                    if ($wrapper.length) {
                        $wrapper.replaceWith('<span class="vs-unified-content vs-empty-content"><em>—</em></span>');
                    }
                }
            }
        }

        // Handler para botões de limpar na tabela
        $(document).on('click', '.vs-clear-unified-table-btn', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $container = $btn.closest('.vs-unified-response');
            var questionIndex = $container.data('question-index');
            var postId = $container.data('post-id');
            var votacaoId = $container.data('votacao-id');
            
            if (!confirm('Tem certeza que deseja limpar esta resposta unificada?')) {
                return;
            }
            
            // Feedback visual
            var originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span>');
            
            // AJAX request
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
                        postId: parseInt(postId),
                        perguntaIndex: parseInt(questionIndex)
                    }])
                },
                success: function(response) {
                    if (response.success) {
                        // Atualizar visualmente a tabela
                        var $wrapper = $btn.closest('.vs-unified-wrapper');
                        $wrapper.replaceWith('<span class="vs-unified-content vs-empty-content"><em>—</em></span>');
                        
                        // Sincronizar com o metabox
                        syncClearUnifiedResponse(questionIndex, true);
                        
                        // Disparar evento customizado para outras sincronizações
                        $(document).trigger('vs:unified-response-cleared', {
                            questionIndex: questionIndex,
                            source: 'table'
                        });
                        
                        // Mostrar mensagem de sucesso (se disponível)
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

        // Event listener para sincronização vinda do metabox
        $(document).on('vs:unified-response-cleared', function(e, data) {
            if (data.source === 'metabox') {
                syncClearUnifiedResponse(data.questionIndex, false);
            }
        });
        
        // Função auxiliar para limpar todas as respostas unificadas (se necessário)
        window.vsClearAllUnifiedResponses = function() {
            $('.vs-unified-content.vs-has-content').each(function() {
                var $content = $(this);
                $content.removeClass('vs-has-content').addClass('vs-empty-content');
                $content.html('<em>—</em>');
            });
            
            // Também limpar no metabox
            $('.vs-unified-answer').fadeOut(300, function() {
                $(this).remove();
            });
        };
    });
    </script>
    <?php
}
