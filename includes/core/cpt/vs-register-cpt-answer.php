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
            // Span com classe para conteúdo preenchido + botão limpar (removido botão editar)
            echo '<div class="vs-unified-wrapper">';
            echo '<span class="vs-unified-content vs-has-content">' . esc_html( $resp_unificada ) . '</span>';
            echo '<button type="button" class="vs-clear-unified-table-btn" title="Limpar resposta unificada">';
            echo '<span class="dashicons dashicons-trash"></span>';
            echo '</button>';
            echo '</div>';
        } else {
            // Span com classe para conteúdo vazio + botão adicionar
            echo '<div class="vs-unified-wrapper">';
            echo '<span class="vs-unified-content vs-empty-content"><em>—</em></span>';
            echo '<button type="button" class="vs-add-unified-table-btn" title="Adicionar resposta unificada" data-question-index="' . esc_attr( $index ) . '" data-post-id="' . esc_attr( $post->ID ) . '" data-votacao-id="' . esc_attr( $votacao_id ) . '">';
            echo '<span class="dashicons dashicons-plus-alt"></span>';
            echo '</button>';
            echo '</div>';
        }
        
        echo '</span>';

        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
    
    // Adicionar modal de unificação
    ?>
    <div id="modal-overlay" style="display: none;"></div>
    <div id="modal-unificacao" style="display: none;">
        <div class="modal-header">
            <h3 id="modal-title">Unificar Respostas</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-content" id="modal-content">
            <!-- Conteúdo será inserido dinamicamente -->
        </div>
    </div>
    
    <?php
    // Adicionar CSS e JavaScript
    ?>
    <style>
    .vs-unified-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }
    .vs-unified-content {
        flex: 1;
    }
    .vs-clear-unified-table-btn,
    .vs-add-unified-table-btn {
        background: transparent;
        border: 1px solid #0073aa;
        color: #0073aa;
        padding: 2px 4px;
        border-radius: 3px;
        cursor: pointer;
        font-size: 11px;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        transition: all 0.2s ease;
    }
    .vs-clear-unified-table-btn {
        border-color: #dc3232;
        color: #dc3232;
    }
    .vs-clear-unified-table-btn:hover {
        background: #dc3232;
        color: white;
        border-color: #dc3232;
    }
    .vs-add-unified-table-btn:hover {
        background: #0073aa;
        color: white;
        border-color: #0073aa;
    }
    .vs-clear-unified-table-btn .dashicons,
    .vs-add-unified-table-btn .dashicons {
        font-size: 12px;
        width: 12px;
        height: 12px;
    }
    .vs-clear-unified-table-btn:disabled,
    .vs-add-unified-table-btn:disabled {
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
    /* Modal Styles */
    #modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9998;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    #modal-overlay.show {
        opacity: 1;
    }
    #modal-unificacao {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.8);
        background: white;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        z-index: 9999;
        max-width: 90%;
        max-height: 90%;
        overflow: hidden;
        opacity: 0;
        transition: all 0.3s ease;
    }
    #modal-unificacao.show {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
    }
    .modal-header {
        background: #f1f1f1;
        padding: 15px 20px;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .modal-header h3 {
        margin: 0;
        font-size: 18px;
        color: #333;
    }
    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #666;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.2s ease;
    }
    .modal-close:hover {
        background: #ddd;
        color: #333;
    }
    .modal-content {
        padding: 20px;
        max-height: 70vh;
        overflow-y: auto;
    }
    /* Estilos para o conteúdo do modal */
    .unificacao-form {
        margin-bottom: 20px;
    }
    .unificacao-form label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    .unificacao-form textarea {
        width: 100%;
        min-height: 100px;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-family: inherit;
        resize: vertical;
    }
    .unificacao-existing {
        margin-top: 20px;
    }
    .unificacao-existing h4 {
        margin-bottom: 10px;
        color: #333;
    }
    .unificacao-option {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .unificacao-option:hover {
        background: #f9f9f9;
        border-color: #0073aa;
    }
    .unificacao-option.selected {
        background: #e7f3ff;
        border-color: #0073aa;
    }
    .modal-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #ddd;
    }
    .modal-btn {
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s ease;
    }
    .modal-btn-primary {
        background: #0073aa;
        color: white;
    }
    .modal-btn-primary:hover {
        background: #005a87;
    }
    .modal-btn-secondary {
        background: #f1f1f1;
        color: #333;
        border: 1px solid #ddd;
    }
    .modal-btn-secondary:hover {
        background: #e1e1e1;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Variáveis globais para o modal
        var $modal = $('#modal-unificacao');
        var $overlay = $('#modal-overlay');
        var currentQuestionIndex = null;
        var currentPostId = null;
        var currentVotacaoId = null;
        
        // Função para abrir o modal
        function openModal(title, content) {
            $('#modal-title').text(title);
            $('#modal-content').html(content);
            
            $overlay.show().addClass('show');
            $modal.show().addClass('show');
            
            // Focar no primeiro campo editável
            setTimeout(function() {
                $modal.find('textarea, input').first().focus();
            }, 300);
        }
        
        // Função para fechar o modal
        function closeModal() {
            $modal.removeClass('show');
            $overlay.removeClass('show');
            
            setTimeout(function() {
                $modal.hide();
                $overlay.hide();
                $('#modal-content').empty();
            }, 300);
        }
        
        // Event listeners para fechar modal
        $(document).on('click', '.modal-close', closeModal);
        $(document).on('click', '#modal-overlay', closeModal);
        
        // Fechar modal com ESC
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27 && $modal.hasClass('show')) {
                closeModal();
            }
        });
        
        // Função para carregar unificações existentes
        function loadExistingUnifications(votacaoId, questionIndex, callback) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vs_get_unificacao_group',
                    nonce: '<?php echo wp_create_nonce("vs_unificacao_nonce"); ?>',
                    votacao_id: votacaoId,
                    question_index: questionIndex
                },
                success: function(response) {
                    if (response.success && response.data) {
                        callback(response.data);
                    } else {
                        callback([]);
                    }
                },
                error: function() {
                    callback([]);
                }
            });
        }

        // Nova função para carregar unificações existentes no contexto do post
        function loadExistingUnificationsForPost(votacaoId, questionIndex, postId, callback) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vs_get_unificacao_group_by_post_context',
                    nonce: '<?php echo wp_create_nonce("vs_unificacao_nonce"); ?>',
                    votacao_id: votacaoId,
                    question_index: questionIndex,
                    current_post_id: postId
                },
                success: function(response) {
                    if (response.success && response.data) {
                        callback(response.data);
                    } else {
                        callback([]);
                    }
                },
                error: function() {
                    callback([]);
                }
            });
        }
        
        // Função para mostrar notificação no post
        function showPostNotification(message, type = 'success') {
            // Remover notificações existentes
            $('.vs-post-notification').remove();
            
            // Criar nova notificação
            var notificationClass = 'vs-post-notification notice notice-' + type + ' is-dismissible';
            var notification = '<div class="' + notificationClass + '" style="margin: 10px 0; position: relative;">';
            notification += '<p>' + message + '</p>';
            notification += '<button type="button" class="notice-dismiss" onclick="$(this).parent().fadeOut();"><span class="screen-reader-text">Dispensar este aviso.</span></button>';
            notification += '</div>';
            
            // Inserir no topo do post
            if ($('#post').length) {
                $('#post').prepend(notification);
            } else if ($('.wrap').length) {
                $('.wrap').prepend(notification);
            }
            
            // Auto-remover após 5 segundos
            setTimeout(function() {
                $('.vs-post-notification').fadeOut();
            }, 5000);
        }

        // Função para abrir modal de unificação
        function openUnificationModal(questionIndex, postId, votacaoId, isEdit = false) {
            currentQuestionIndex = questionIndex;
            currentPostId = postId;
            currentVotacaoId = votacaoId;
            
            var title = isEdit ? 'Editar Resposta Unificada' : 'Adicionar Resposta Unificada';
            
            // Buscar a resposta original que está sendo unificada
            var originalResponse = '';
            var questionLabel = 'Pergunta ' + (parseInt(questionIndex) + 1);
            
            // Tentar encontrar a resposta original na tabela
            var $responseCell = $('tr[data-post-id="' + postId + '"] td').eq(parseInt(questionIndex) + 2);
            if ($responseCell.length) {
                originalResponse = $responseCell.text().trim();
                // Limitar o tamanho da resposta exibida
                if (originalResponse.length > 100) {
                    originalResponse = originalResponse.substring(0, 100) + '...';
                }
            }
            
            // Carregar unificações existentes usando a nova função específica para posts
            loadExistingUnificationsForPost(votacaoId, questionIndex, postId, function(existingUnifications) {
                var content = '<div class="unificacao-info" style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #0073aa;">';
                content += '<h4 style="margin: 0 0 10px 0; color: #333; font-size: 14px;">Unificando resposta:</h4>';
                content += '<p style="margin: 0 0 5px 0; font-weight: bold; color: #0073aa; font-size: 13px;">' + questionLabel + '</p>';
                if (originalResponse) {
                    content += '<p style="margin: 0; font-style: italic; color: #666; font-size: 13px;""' + escapeHtml(originalResponse) + '"</p>';
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
                        content += '<option value="' + escapeHtml(unif.resposta_unificada) + '">' + escapeHtml(displayText) + '</option>';
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
                content += '<button type="button" class="modal-btn modal-btn-secondary" onclick="closeModal()">Cancelar</button>';
                content += '<button type="button" class="modal-btn modal-btn-primary" id="save-unification">Salvar</button>';
                content += '</div>';
                
                openModal(title, content);
            });
        }
        
        // Função auxiliar para escapar HTML
        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
        
        // Event listener para mudança no select de unificações existentes
        $(document).on('change', '#select-unificacao-existente', function() {
            var selectedValue = $(this).val();
            $('#nova-resposta-unificada').val(selectedValue);
        });
        
        // Event listener para salvar unificação
        $(document).on('click', '#save-unification', function() {
            var novaResposta = $('#nova-resposta-unificada').val().trim();
            
            if (!novaResposta) {
                alert('Por favor, digite uma resposta unificada ou selecione uma existente.');
                return;
            }
            
            var $btn = $(this);
            var originalText = $btn.text();
            $btn.prop('disabled', true).text('Salvando...');
            
            // Preparar dados para envio
            var linhas = [{
                postId: parseInt(currentPostId),
                perguntaIndex: parseInt(currentQuestionIndex)
            }];
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vs_update_resposta_unificada',
                    nonce: '<?php echo wp_create_nonce("vs_unificacao_nonce"); ?>',
                    votacao_id: currentVotacaoId,
                    nova_resposta_unificada: novaResposta,
                    linhas: JSON.stringify(linhas)
                },
                success: function(response) {
                    if (response.success) {
                        // Atualizar a interface
                        updateUnifiedResponseDisplay(currentQuestionIndex, currentPostId, novaResposta);
                        
                        // Fechar modal
                        closeModal();
                        
                        // Mostrar notificação no post
                        showPostNotification('Resposta unificada atualizada com sucesso!', 'success');
                        
                        // Fallback para notificação do WordPress se disponível
                        if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
                            wp.data.dispatch('core/notices').createNotice(
                                'success',
                                'Resposta unificada salva com sucesso!',
                                { isDismissible: true }
                            );
                        }
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
        
        // Função para atualizar a exibição da resposta unificada
        function updateUnifiedResponseDisplay(questionIndex, postId, newValue) {
            var $container = $('.vs-unified-response[data-question-index="' + questionIndex + '"][data-post-id="' + postId + '"]');
            
            if ($container.length) {
                var newHtml = '<div class="vs-unified-wrapper">';
                newHtml += '<span class="vs-unified-content vs-has-content">' + escapeHtml(newValue) + '</span>';
                newHtml += '<button type="button" class="vs-clear-unified-table-btn" title="Limpar resposta unificada">';
                newHtml += '<span class="dashicons dashicons-trash"></span>';
                newHtml += '</button>';
                newHtml += '</div>';
                
                $container.html(newHtml);
                
                // Sincronizar com o metabox se existir
                syncWithMetabox(questionIndex, newValue);
            }
        }
        
        // Função para sincronizar com o metabox
        function syncWithMetabox(questionIndex, value) {
            // Verificar se existe um metabox de resposta unificada
            var $metaboxContainer = $('.vs-unified-answer[data-question-index="' + questionIndex + '"]');
            
            if ($metaboxContainer.length) {
                // Atualizar valor existente
                $metaboxContainer.find('.vs-unified-value').text(value);
            } else {
                // Criar novo item no metabox se não existir
                var $answerItem = $('.vs-answer-item').eq(questionIndex);
                var $noUnified = $answerItem.find('.vs-no-unified-answer');
                
                if ($answerItem.length) {
                    // Obter dados do botão existente ou usar valores padrão
                    var postId = $noUnified.find('.vs-add-unified-metabox-btn').data('resposta-id') || '';
                    var votacaoId = $noUnified.find('.vs-add-unified-metabox-btn').data('votacao-id') || '';
                    
                    // Se não conseguir obter os dados, tentar da tabela
                    if (!postId || !votacaoId) {
                        var $tableContainer = $('.vs-unified-response[data-question-index="' + questionIndex + '"]');
                        if ($tableContainer.length) {
                            postId = $tableContainer.data('post-id');
                            votacaoId = $tableContainer.data('votacao-id');
                        }
                    }
                    
                    // Remover o botão "Adicionar" se existir
                    if ($noUnified.length) {
                        $noUnified.fadeOut(300, function() {
                            $(this).remove();
                        });
                    }
                    
                    // Criar o novo elemento vs-unified-answer
                    var newItem = '<div class="vs-unified-answer" data-question-index="' + questionIndex + '">';
                    newItem += '<div class="vs-unified-content">';
                    newItem += '<strong>Resposta Unificada:</strong> ';
                    newItem += '<span class="vs-unified-value">' + escapeHtml(value) + '</span>';
                    newItem += '</div>';
                    newItem += '<div class="vs-unified-actions">';
                    newItem += '<button type="button" class="button button-small vs-clear-unified-btn" ';
                    newItem += 'data-resposta-id="' + postId + '" ';
                    newItem += 'data-question-index="' + questionIndex + '" ';
                    newItem += 'data-votacao-id="' + votacaoId + '" ';
                    newItem += 'title="Limpar resposta unificada">';
                    newItem += '<span class="dashicons dashicons-trash"></span> Limpar';
                    newItem += '</button>';
                    newItem += '</div>';
                    newItem += '</div>';
                    
                    // Adicionar o novo elemento ao answer-item
                    $answerItem.append(newItem);
                }
            }
        }
        
        // Event listeners para os botões da tabela
        $(document).on('click', '.vs-add-unified-table-btn', function(e) {
            e.preventDefault();
            
            var questionIndex = $(this).data('question-index');
            var postId = $(this).data('post-id');
            var votacaoId = $(this).data('votacao-id');
            
            openUnificationModal(questionIndex, postId, votacaoId, false);
        });
        
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
                        var postId = $tableContainer.data('post-id');
                        var votacaoId = $tableContainer.data('votacao-id');
                        
                        var newHtml = '<div class="vs-unified-wrapper">';
                        newHtml += '<span class="vs-unified-content vs-empty-content"><em>—</em></span>';
                        newHtml += '<button type="button" class="vs-add-unified-table-btn" title="Adicionar resposta unificada" data-question-index="' + questionIndex + '" data-post-id="' + postId + '" data-votacao-id="' + votacaoId + '">';
                        newHtml += '<span class="dashicons dashicons-plus-alt"></span>';
                        newHtml += '</button>';
                        newHtml += '</div>';
                        
                        $wrapper.replaceWith(newHtml);
                    }
                }
            }
        }

        // Função para atualizar o metabox com resposta unificada
        function updateMetaboxUnifiedResponse(questionIndex, value, postId, votacaoId) {
            if (!value || value.trim() === '') {
                // Se valor está vazio, remover do metabox
                var $metaboxContainer = $('.vs-unified-answer[data-question-index="' + questionIndex + '"]');
                if ($metaboxContainer.length) {
                    $metaboxContainer.fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            } else {
                // Se valor não está vazio, atualizar ou criar no metabox
                syncWithMetabox(questionIndex, value);
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
                        var newHtml = '<div class="vs-unified-wrapper">';
                        newHtml += '<span class="vs-unified-content vs-empty-content"><em>—</em></span>';
                        newHtml += '<button type="button" class="vs-add-unified-table-btn" title="Adicionar resposta unificada" data-question-index="' + questionIndex + '" data-post-id="' + postId + '" data-votacao-id="' + votacaoId + '">';
                        newHtml += '<span class="dashicons dashicons-plus-alt"></span>';
                        newHtml += '</button>';
                        newHtml += '</div>';
                        
                        $wrapper.replaceWith(newHtml);
                        
                        // Sincronizar com o metabox (valor vazio para limpar)
                        updateMetaboxUnifiedResponse(questionIndex, '', postId, votacaoId);
                        
                        // Disparar evento customizado para sincronização
                        $(document).trigger('vs:unified-response-cleared', {
                            questionIndex: questionIndex,
                            postId: postId,
                            votacaoId: votacaoId,
                            source: 'table'
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
        
        // Tornar closeModal disponível globalmente
        window.closeModal = closeModal;
    });
    </script>
    <?php
}
