<?php
defined('ABSPATH') || exit;

/**
 * Handler AJAX para buscar informações do evento
 */
function vs_ajax_get_event_info() {
    check_ajax_referer(VS_Nonce_Actions::AJAX_ADMIN, 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissão negada');
    }

    $vote_id = sanitize_text_field($_POST['vote_id'] ?? '');
    
    if (empty($vote_id)) {
        wp_send_json_error('Vote ID não fornecido');
    }

    // Buscar a votação pelo ID
    $votacao = get_post($vote_id);
    
    if (!$votacao || $votacao->post_type !== 'votacoes') {
        wp_send_json_error('Votação não encontrada');
    }

    // Buscar informações do evento associado
    $eventos = wp_get_post_terms($vote_id, 'eventos');
    $event_name = 'Evento sem nome';
    
    if (!empty($eventos) && !is_wp_error($eventos)) {
        $event_name = $eventos[0]->name;
    } else {
        // Fallback: usar o título da votação
        $event_name = $votacao->post_title;
    }

    wp_send_json_success([
        'event_name' => $event_name,
        'vote_id' => $vote_id,
        'vote_title' => $votacao->post_title
    ]);
}

// Registrar o hook AJAX
add_action('wp_ajax_vs_get_event_info', 'vs_ajax_get_event_info');