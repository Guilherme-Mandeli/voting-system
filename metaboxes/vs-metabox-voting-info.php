<?php
/**
 * Metabox de información de votación
 * 
 * @package VotingSystem\Metaboxes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registra el metabox de información de votación
 */
function vs_register_metabox_voting_info() {
    add_meta_box(
        'vs_info_votacao',
        'Informações da Votação',
        'vs_render_metabox_voting_info',
        'votacoes',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'vs_register_metabox_voting_info');

/**
 * Renderiza el metabox de información de votación
 *
 * @param WP_Post $post Objeto del post actual
 */
function vs_render_metabox_voting_info($post) {
    // Obtener datos guardados
    $ano = get_post_meta($post->ID, '_vs_ano', true);
    if (empty($ano) && $post->post_status === 'auto-draft') {
        $ano = date('Y');
    }
    
    $status = get_post_meta($post->ID, '_vs_status', true);
    if (empty($status)) {
        $status = 'aberta'; // Valor por defecto
    }
    
    $codigo = get_post_meta($post->ID, '_vs_codigo', true);
    $data_inicio = get_post_meta($post->ID, '_vs_data_inicio', true);
    $data_fim = get_post_meta($post->ID, '_vs_data_fim', true);
    $quantidade_votos = get_post_meta($post->ID, '_vs_qtd_votos', true);
    
    if ($quantidade_votos === '') {
        $quantidade_votos = 0;
    }

    // Incluir template
    include VS_PLUGIN_PATH . 'templates/admin/template-metabox-voting-info.php';
}

/**
 * Guarda los datos del metabox de información
 *
 * @param int $post_id ID del post siendo guardado
 */
function vs_save_metabox_voting_info($post_id) {
    // Verificaciones de seguridad
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (!isset($_POST['vs_info_metabox_nonce_field']) || !wp_verify_nonce($_POST['vs_info_metabox_nonce_field'], 'vs_info_metabox_nonce')) return;

    // Procesar y guardar año
    $ano_raw = sanitize_text_field($_POST['vs_ano'] ?? '');
    $ano = preg_match('/^\d{4}$/', $ano_raw) ? $ano_raw : date('Y');
    update_post_meta($post_id, '_vs_ano', $ano);

    // Procesar y guardar código
    $codigo = sanitize_text_field($_POST['vs_codigo'] ?? '');
    if (empty($codigo)) {
        $codigo_base = 'VOT-' . $ano . '-' . $post_id;
        $codigo = vs_generate_unique_code($codigo_base, $post_id);
    } else {
        $codigo = vs_generate_unique_code($codigo, $post_id);
    }
    update_post_meta($post_id, '_vs_codigo', $codigo);

    // Guardar fechas
    $data_inicio = sanitize_text_field($_POST['vs_data_inicio'] ?? '');
    update_post_meta($post_id, '_vs_data_inicio', $data_inicio);

    $data_fim = sanitize_text_field($_POST['vs_data_fim'] ?? '');
    update_post_meta($post_id, '_vs_data_fim', $data_fim);

    // Determinar status basado en fecha de fin
    $timestamp_data_fim = strtotime($data_fim . ' 00:00:00');
    $timestamp_hoje = strtotime(date('Y-m-d') . ' 00:00:00');
    
    if ($data_fim && $timestamp_data_fim < $timestamp_hoje) {
        $status = 'encerrada';
    } else {
        $status = sanitize_text_field($_POST['vs_status'] ?? 'aberta');
    }
    update_post_meta($post_id, '_vs_status', $status);
}
add_action('save_post_votacoes', 'vs_save_metabox_voting_info');

/**
 * Genera un código único para la votación
 *
 * @param string $codigo_base Código base
 * @param int $post_id ID del post
 * @return string Código único
 */
function vs_generate_unique_code($codigo_base, $post_id) {
    global $wpdb;
    
    $codigo_final = $codigo_base;
    $contador = 0;

    while (true) {
        $existe = $wpdb->get_var($wpdb->prepare("
            SELECT post_id FROM $wpdb->postmeta 
            WHERE meta_key = '_vs_codigo' 
              AND meta_value = %s
              AND post_id != %d
            LIMIT 1
        ", $codigo_final, $post_id));

        if (!$existe) {
            break;
        }

        $contador++;
        $codigo_final = $codigo_base . '-' . $contador;
    }

    return $codigo_final;
}
