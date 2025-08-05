<?php
/**
 * Metabox de preguntas de votación
 * 
 * @package VotingSystem\Metaboxes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registra el metabox de preguntas
 */
function vs_register_metabox_questions() {
    add_meta_box(
        'vs_metabox_perguntas',
        'Perguntas da Votação',
        'vs_render_metabox_questions',
        'votacoes',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'vs_register_metabox_questions');

/**
 * Renderiza el metabox de preguntas
 *
 * @param WP_Post $post Objeto del post actual
 */
function vs_render_metabox_questions($post) {
    // Incluye la vista del metabox
    require_once VS_PLUGIN_PATH . 'metaboxes/view-metabox-questions.php';
    vs_render_metabox_questions_view($post);
}

/**
 * Guarda los datos del metabox al guardar el post
 *
 * @param int $post_id ID del post siendo guardado
 */
function vs_save_metabox_questions($post_id) {
    // Verificaciones de seguridad
    if (!isset($_POST['vs_nonce_questions']) || !wp_verify_nonce($_POST['vs_nonce_questions'], 'vs_salvar_perguntas')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Guardar flag permitir editar voto
    if (isset($_POST['vs_permitir_edicao']) && $_POST['vs_permitir_edicao'] == '1') {
        update_post_meta($post_id, 'vs_permitir_edicao', '1');
    } else {
        delete_post_meta($post_id, 'vs_permitir_edicao');
    }

    // Procesar y guardar preguntas
    $questions = [];

    if (isset($_POST['vs_questions']) && is_array($_POST['vs_questions'])) {
        foreach ($_POST['vs_questions'] as $index => $question_data) {
            $label = sanitize_text_field($question_data['label'] ?? '');
            $tipo = sanitize_text_field($question_data['tipo'] ?? 'texto');
            $options = isset($question_data['options']) && is_array($question_data['options']) 
                        ? array_map('sanitize_text_field', array_filter($question_data['options'])) 
                        : [];
            $obrigatoria = isset($question_data['obrigatoria']) && $question_data['obrigatoria'] ? true : false;
            $unificada = sanitize_text_field($question_data['unificada'] ?? '');
            $imported_vote_id = intval($question_data['imported_vote_id'] ?? 0);

            // Busca dados da votação anterior se o tipo for 'imported_vote' e houver um ID válido
            if ($tipo === 'imported_vote' && $imported_vote_id > 0) {
                $imported_answers = vs_get_imported_vote_data($imported_vote_id, $index);
            } else {
                $imported_answers = wp_json_encode(['perguntas' => []]);
            }

            // Solo agregar si tiene label
            if (!empty($label)) {
                $questions[] = [
                    'label' => $label,
                    'tipo' => $tipo,
                    'options' => $options,
                    'obrigatoria' => $obrigatoria,
                    'unificada' => $unificada,
                    'imported_vote_id' => $imported_vote_id,
                    'imported_answers' => $imported_answers
                ];
            }
        }
    }

    update_post_meta($post_id, 'vs_questions', $questions);
}
add_action('save_post', 'vs_save_metabox_questions');

/**
 * Callback AJAX para obtener template de pregunta
 */
function vs_ajax_get_pergunta_template() {
    $index = intval($_GET['index'] ?? 0);
    $question = ['obrigatoria' => true]; // Valor por defecto
    
    // Incluir el template
    include VS_PLUGIN_PATH . 'templates/admin/template-metabox-question-row.php';
    
    wp_die();
}
add_action('wp_ajax_vs_get_pergunta_template', 'vs_ajax_get_pergunta_template');
