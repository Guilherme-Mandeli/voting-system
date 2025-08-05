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
                            <div class="vs-unified-answer">
                                <strong>Resposta Unificada:</strong> 
                                <span class="vs-unified-value"><?php echo esc_html($resposta_unificada[$index]); ?></span>
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
        padding: 8px;
        background: #e7f3ff;
        border: 1px solid #b3d9ff;
        border-radius: 3px;
        font-size: 13px;
    }
    
    .vs-unified-value {
        font-weight: 600;
        color: #0073aa;
    }
    </style>
    <?php
}