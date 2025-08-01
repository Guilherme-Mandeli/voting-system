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
    if (!isset($_POST['vs_nonce_perguntas']) || !wp_verify_nonce($_POST['vs_nonce_perguntas'], 'vs_salvar_perguntas')) {
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
    $perguntas = [];

    if (isset($_POST['vs_perguntas']) && is_array($_POST['vs_perguntas'])) {
        foreach ($_POST['vs_perguntas'] as $pergunta_data) {
            $label = sanitize_text_field($pergunta_data['label'] ?? '');
            $tipo = sanitize_text_field($pergunta_data['tipo'] ?? 'texto');
            $opcoes = isset($pergunta_data['opcoes']) && is_array($pergunta_data['opcoes']) 
                        ? array_map('sanitize_text_field', array_filter($pergunta_data['opcoes'])) 
                        : [];
            $obrigatoria = isset($pergunta_data['obrigatoria']) && $pergunta_data['obrigatoria'] ? true : false;
            $unificada = sanitize_text_field($pergunta_data['unificada'] ?? '');
            $votacao_anterior_id = intval($pergunta_data['votacao_anterior_id'] ?? 0);

            // Busca dados da votação anterior se o tipo for 'votacao_anterior' e houver um ID válido
            if ($tipo === 'votacao_anterior' && $votacao_anterior_id > 0) {
                $respostas_importadas = vs_get_votacao_anterior_data($votacao_anterior_id);
            } else {
                $respostas_importadas = wp_json_encode(['perguntas' => []]);
            }

            // Solo agregar si tiene label
            if (!empty($label)) {
                $perguntas[] = [
                    'label' => $label,
                    'tipo' => $tipo,
                    'opcoes' => $opcoes,
                    'obrigatoria' => $obrigatoria,
                    'unificada' => $unificada,
                    'votacao_anterior_id' => $votacao_anterior_id,
                    'respostas_importadas' => $respostas_importadas
                ];
            }
        }
    }

    update_post_meta($post_id, 'vs_perguntas', $perguntas);
}
add_action('save_post', 'vs_save_metabox_questions');

/**
 * Callback AJAX para obtener template de pregunta
 */
function vs_ajax_get_pergunta_template() {
    $index = intval($_GET['index'] ?? 0);
    $pergunta = ['obrigatoria' => true]; // Valor por defecto
    
    // Incluir el template
    include VS_PLUGIN_PATH . 'templates/admin/template-metabox-question-row.php';
    
    wp_die();
}
add_action('wp_ajax_vs_get_pergunta_template', 'vs_ajax_get_pergunta_template');
