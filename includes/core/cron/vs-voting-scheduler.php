<?php
defined( 'ABSPATH' ) || exit;

/**
 * Sistema de agendamento para encerramento de votações
 */

// Agenda o evento diário na ativação
register_activation_hook(__FILE__, function() {
    if (!wp_next_scheduled('vs_encerrar_votacoes_diariamente')) {
        wp_schedule_event(strtotime('00:00:00'), 'daily', 'vs_encerrar_votacoes_diariamente');
    }
});

register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('vs_encerrar_votacoes_diariamente');
});

add_action('vs_encerrar_votacoes_diariamente', function() {
    $args = [
        'post_type' => 'votacoes',
        'post_status' => ['publish', 'draft', 'pending', 'future'],
        'meta_query' => [
            [
                'key' => '_vs_data_fim',
                'value' => date('Y-m-d'),
                'compare' => '<',
                'type' => 'DATE'
            ],
            [
                'key' => '_vs_status',
                'value' => 'encerrada',
                'compare' => '!='
            ],
        ],
        'fields' => 'ids',
        'posts_per_page' => -1,
    ];

    $votacoes = get_posts($args);

    foreach ($votacoes as $post_id) {
        update_post_meta($post_id, '_vs_status', 'encerrada');
    }
});
