<?php
/**
 * Página administrativa de unificação de respostas
 * 
 * @package VotingSystem\Admin\Pages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renderiza a página de unificação de respostas
 */
function vs_render_unificacao_page($votacao_id) {
    // Checagem de permissões
    if (!current_user_can('manage_options')) {
        wp_die('Você não tem permissão para acessar esta página.');
    }

    // Carrega configuração das perguntas para esta votação
    $questions = get_post_meta($votacao_id, 'vs_questions', true);
    if (!is_array($questions)) {
        $questions = array();
    }

    // Busca todos os posts de resposta vinculados a esta votação (excluindo lixeira)
    $args = array(
        'post_type'      => 'votacao_resposta',
        'posts_per_page' => -1,
        'post_status'    => array('publish', 'private'),
        'meta_query'     => array(
            array(
                'key'     => 'vs_votacao_id',
                'value'   => $votacao_id,
                'compare' => '=',
            ),
        ),
        'orderby' => 'ID',
        'order'   => 'ASC',
    );
    $query = new WP_Query($args);

    // Constrói agregação para Coluna 2 baseada nas unificações armazenadas por resposta
    // Agora só considera respostas que não estão na lixeira
    $agg_counts = array();
    $agg_posts = array();
    $agg_slot_map = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            $resp_post_id = get_the_ID();
            
            // Verificação adicional de segurança: pula posts que estão na lixeira
            $post_status = get_post_status($resp_post_id);
            if ($post_status === 'trash') {
                continue;
            }

            // Cada post de resposta pode ter um array de valores unificados por pergunta
            $unifications = get_post_meta($resp_post_id, 'vs_resposta_unificada', true);
            if (!is_array($unifications)) {
                $unifications = array();
            }

            // Carrega respostas detalhadas para saber quais índices de pergunta existem
            $respostas_detalhadas = get_post_meta($resp_post_id, 'vs_respostas_detalhadas', true);
            if (!is_array($respostas_detalhadas)) {
                $respostas_detalhadas = array();
            }

            // Percorre os índices respondidos; se existe unificação para esse índice, agrega
            foreach ($respostas_detalhadas as $idx => $unused_value) {
                $unification_value = isset($unifications[$idx]) ? (string) $unifications[$idx] : '';

                // Pula vazios (slots ainda não unificados)
                if ('' === trim($unification_value)) {
                    continue;
                }

                if (!isset($agg_counts[$unification_value])) {
                    $agg_counts[$unification_value] = 0;
                    $agg_posts[$unification_value] = array();
                    $agg_slot_map[$unification_value] = array();
                }

                $agg_counts[$unification_value]++;
                $agg_posts[$unification_value][] = $resp_post_id;
                $agg_slot_map[$unification_value][] = array(
                    'post_id' => $resp_post_id,
                    'index'   => $idx,
                );
            }
        }
        wp_reset_postdata();
    }

    // Remove duplicatas das listas de posts
    foreach ($agg_posts as $k => $ids) {
        $agg_posts[$k] = array_values(array_unique($ids));
    }

    // Constrói lista filtrada (apenas chaves não vazias) para saída da Coluna 2
    $agg_non_empty = array();
    foreach ($agg_counts as $k => $cnt) {
        if ('' !== trim($k)) {
            $agg_non_empty[$k] = $cnt;
        }
    }

    ?>
    <div class="wrap">
        <a href="<?php echo esc_url(admin_url('edit.php?post_type=votacoes&page=votacoes_resultados')); ?>" class="button button-secondary" style="width: fit-content;">← Voltar</a>
        <h1>
            Detalhe de Resultados da Votação #<?php echo absint($votacao_id); ?>
            <a href="<?php echo esc_url(admin_url('admin-post.php?action=export_csv_votacao&export_csv=true&votacao_id=' . $votacao_id)); ?>" class="button button-primary" style="margin-left: 15px;">Exportar para CSV</a>
        </h1>

        <?php vs_render_subpage_navigation($votacao_id, 'unificacao'); ?>

        <div class="unificacao-container" style="gap: 30px;">
            <!-- Coluna 1: Todos os slots de resposta -->
            <div class="unificacao-coluna" id="respostas-coluna">
                <h2 class="unificacao-title">Respostas Votação #<?php echo esc_html($votacao_id); ?></h2>

                <?php if (!$query->have_posts()) : ?>
                    <p>Nenhuma resposta encontrada para esta votação.</p>
                <?php else : ?>
                    <button type="button" class="button-unificacao" id="btn-unificacao-top" disabled>Unificação</button>
                    <span class="text-unificacao">Selecione uma ou mais respostas para unificar.</span> 
                    
                    <form id="form-unificacao" class="unificacao-form" style="margin-top:10px;">
                        <table class="unificacao-table" id="unificacao-table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th class="unificacao-checkbox-column"><input type="checkbox" id="select-all" /></th>
                                    <th class="unificacao-usuario-column">Usuário</th>
                                    <th class="unificacao-pergunta-column">Pergunta</th>
                                    <th class="unificacao-resposta-column">Resposta</th>
                                    <th class="unificacao-resposta-unificada-column">Resposta Unificada</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Reinicia porque consumimos a query acima na passagem de agregação
                                $query->rewind_posts();

                                while ($query->have_posts()) :
                                    $query->the_post();
                                    $post_id = get_the_ID();

                                    // Verificação adicional de segurança: pula posts que estão na lixeira
                                    $post_status = get_post_status($post_id);
                                    if ($post_status === 'trash') {
                                        continue;
                                    }

                                    // Coleta exibição do usuário (meta armazenado vs_usuario_id)
                                    $user_id = get_post_meta($post_id, 'vs_usuario_id', true);
                                    $user = $user_id ? get_userdata($user_id) : null;

                                    if ($user) {
                                        $usuario_texto = sprintf('#%d %s', $user->ID, $user->user_email);
                                    } else {
                                        $usuario_texto = '&#8212;';
                                    }

                                    $link_edicao = get_edit_post_link($post_id);

                                    // Array de respostas detalhadas (índice => string|array de resposta)
                                    $respostas_detalhadas = get_post_meta($post_id, 'vs_respostas_detalhadas', true);
                                    if (!is_array($respostas_detalhadas)) {
                                        $respostas_detalhadas = array();
                                    }

                                    // Array de valores unificados POR RESPOSTA
                                    $unifications = get_post_meta($post_id, 'vs_resposta_unificada', true);
                                    if (!is_array($unifications)) {
                                        $unifications = array();
                                    }

                                    foreach ($respostas_detalhadas as $idx => $resposta_individual) {

                                        // Label da pergunta da configuração da votação
                                        $question_label = isset($questions[$idx]['label'])
                                            ? $questions[$idx]['label']
                                            : sprintf('Pergunta #%d', ($idx + 1));

                                        // Normaliza texto da resposta
                                        if (is_array($resposta_individual)) {
                                            $resposta_texto = implode(', ', array_map('sanitize_text_field', $resposta_individual));
                                        } else {
                                            $resposta_texto = sanitize_text_field($resposta_individual);
                                        }

                                        // Valor unificado: agora vem do array meta POR RESPOSTA
                                        $unificada_texto = isset($unifications[$idx]) && '' !== trim($unifications[$idx])
                                            ? $unifications[$idx]
                                            : '—';
                                        ?>
                                        <tr class="unificacao-tr"
                                            data-post-id="<?php echo esc_attr($post_id); ?>"
                                            data-question-index="<?php echo esc_attr($idx); ?>"
                                        >
                                            <td style="text-align:center;">
                                                <input type="checkbox" name="respostas_ids[]" value="<?php echo esc_attr($post_id); ?>" />
                                            </td>
                                            <td>
                                                <?php if ($link_edicao) : ?>
                                                    <a href="<?php echo esc_url($link_edicao); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($usuario_texto); ?></a>
                                                <?php else : ?>
                                                    <?php echo esc_html($usuario_texto); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="tooltip" title="<?php echo esc_attr($question_label); ?>">
                                                    <?php echo esc_html($question_label); ?>
                                                    <span class="tooltip-text"><?php echo esc_html($question_label); ?></span>
                                                </div>
                                            </td>
                                            <td class="unificacao-resposta-column">
                                                <div class="tooltip" title="<?php echo esc_attr($resposta_texto); ?>">
                                                    <?php echo esc_html($resposta_texto); ?>
                                                    <span class="tooltip-text"><?php echo esc_html($resposta_texto); ?></span>
                                                </div>
                                            </td>
                                            <td class="unificacao-resposta-unificada-column">
                                                <div class="tooltip" title="<?php echo esc_attr($unificada_texto); ?>">
                                                    <?php echo esc_html($unificada_texto); ?>
                                                    <span class="tooltip-text"><?php echo esc_html($unificada_texto); ?></span>
                                                </div>
                                                <?php if ($unificada_texto !== '—') : ?>
                                                    <button type="button" class="vs-edit-unified-btn" 
                                                            title="Editar resposta unificada" 
                                                            data-post-id="<?php echo esc_attr($post_id); ?>"
                                                            data-question-index="<?php echo esc_attr($idx); ?>"
                                                            data-votacao-id="<?php echo esc_attr($votacao_id); ?>"
                                                            style="margin-left: 8px; background: transparent; border: 1px solid #0073aa; color: #0073aa; padding: 2px 4px; border-radius: 3px; cursor: pointer; font-size: 11px;">
                                                        <span class="dashicons dashicons-edit" style="font-size: 12px; width: 12px; height: 12px;"></span>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                endwhile;
                                wp_reset_postdata();
                                ?>
                            </tbody>
                        </table>
                    </form>

                    <button type="button" class="button-unificacao" id="btn-unificacao-bottom" style="margin-top: 10px;" disabled >Unificação</button>
                    <span class="text-unificacao">Selecione uma ou mais respostas para unificar.</span>
                <?php endif; ?>
            </div>

            <!-- Coluna 2: Valores unificados agregados (dados armazenados reais) -->
            <div class="unificacao-coluna" id="unificados-coluna">
                <h2 class="unificacao-title">Unificações de Resposta</h2>

                <?php if (empty($agg_non_empty)) : ?>
                    <p>Nenhuma unificação realizada.</p>
                    <style>.unificacao-second-table { opacity: .44; }</style>
                <?php else : ?>
                    <table class="unificacao-second-table" id="unificacao-second-table">
                        <thead>
                            <tr>
                                <th>Resposta Unificada</th>
                                <th>Contagem</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agg_non_empty as $resposta_key => $count) : ?>
                                <?php
                                $resposta_text = $resposta_key;
                                $display_text = (strlen($resposta_text) > 80)
                                    ? substr($resposta_text, 0, 77) . '...'
                                    : $resposta_text;
                                ?>
                                <tr data-resposta-key="<?php echo esc_attr($resposta_key); ?>">
                                    <td>
                                        <div class="tooltip" title="<?php echo esc_attr($resposta_text); ?>">
                                            <?php echo esc_html($display_text); ?>
                                            <span class="tooltip-text"><?php echo esc_html($resposta_text); ?></span>
                                        </div>
                                    </td>
                                    <td style="text-align:center;"><?php echo intval($count); ?></td>
                                    <td>
                                        <a href="#"
                                           class="unificacao-ver-todos"
                                           data-resposta-key="<?php echo esc_attr($resposta_key); ?>">
                                            Ver todos
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="resposta-modal" id="modal-unificacao" style="display:none;"></div>
    <div class="modal-overlay" id="modal-overlay" style="display:none;"></div>

    <?php
    // Marcador oculto para JS ler o ID da votação se necessário
    printf(
        '<div id="vs-votacao-marker" data-votacao-id="%d" style="display:none;"></div>',
        absint($votacao_id)
    );
    ?>

    <script>
    jQuery(document).ready(function($) {
        // Event listener para botão de editar na tabela de unificação
        $(document).on('click', '.vs-edit-unified-btn', function(e) {
            e.preventDefault();
            
            var postId = $(this).data('post-id');
            var questionIndex = $(this).data('question-index');
            var votacaoId = $(this).data('votacao-id');
            var currentValue = $(this).closest('td').find('.tooltip').attr('title');
            
            // Criar modal de edição
            var modalContent = '<div style="padding: 20px;">';
            modalContent += '<h3>Editar Resposta Unificada</h3>';
            modalContent += '<label for="edit-unified-input">Nova resposta unificada:</label>';
            modalContent += '<input type="text" id="edit-unified-input" value="' + currentValue + '" style="width: 100%; padding: 8px; margin: 10px 0;" />';
            modalContent += '<div style="margin-top: 20px; text-align: right;">';
            modalContent += '<button type="button" class="button" onclick="closeEditModal()">Cancelar</button>';
            modalContent += '<button type="button" class="button-primary" id="save-edit-unified" style="margin-left: 10px;">Salvar</button>';
            modalContent += '</div>';
            modalContent += '</div>';
            
            // Mostrar modal
            showEditModal(modalContent, postId, questionIndex, votacaoId);
        });
        
        // Função para mostrar modal de edição
        function showEditModal(content, postId, questionIndex, votacaoId) {
            var modal = '<div id="edit-unified-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">';
            modal += '<div style="background: white; border-radius: 8px; max-width: 500px; width: 90%;">';
            modal += content;
            modal += '</div>';
            modal += '</div>';
            
            $('body').append(modal);
            
            // Event listener para salvar
            $('#save-edit-unified').on('click', function() {
                var newValue = $('#edit-unified-input').val().trim();
                
                if (!newValue) {
                    alert('Por favor, digite uma resposta unificada.');
                    return;
                }
                
                var $btn = $(this);
                var originalText = $btn.text();
                $btn.prop('disabled', true).text('Salvando...');
                
                // AJAX para salvar
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vs_update_resposta_unificada',
                        nonce: <?php echo json_encode(wp_create_nonce("vs_unificacao_nonce")); ?>,
                        votacao_id: votacaoId,
                        nova_resposta_unificada: newValue,
                        linhas: JSON.stringify([{
                            postId: parseInt(postId),
                            perguntaIndex: parseInt(questionIndex)
                        }])
                    },
                    success: function(response) {
                        if (response.success) {
                            // Recarregar página para mostrar mudanças
                            location.reload();
                        } else {
                            alert('Erro ao salvar: ' + (response.data || 'Erro desconhecido'));
                            $btn.prop('disabled', false).text(originalText);
                        }
                    },
                    error: function() {
                        alert('Erro de conexão ao tentar salvar.');
                        $btn.prop('disabled', false).text(originalText);
                    }
                });
            });
        }
        
        // Função para fechar modal
        window.closeEditModal = function() {
            $('#edit-unified-modal').remove();
        };
        
        // Fechar modal ao clicar fora
        $(document).on('click', '#edit-unified-modal', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    });
    </script>

    <?php
}