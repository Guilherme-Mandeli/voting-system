<?php
/**
 * Ferramenta para reparar sincronização entre '_vs_votantes' e posts 'votacao_resposta'
 */

function vs_repair_votantes_sync($votacao_id = null) {
    $args = [
        'post_type' => 'votacoes',
        'post_status' => 'publish',
        'posts_per_page' => -1,
    ];
    
    if ($votacao_id) {
        $args['include'] = [$votacao_id];
    }
    
    $votacoes = get_posts($args);
    
    foreach ($votacoes as $votacao) {
        // Buscar todos os posts de resposta ativos para esta votação
        $respostas_args = [
            'post_type' => 'votacao_resposta',
            'post_status' => ['publish', 'private'],
            'meta_query' => [
                [
                    'key' => 'vs_votacao_id',
                    'value' => $votacao->ID,
                    'compare' => '=',
                ]
            ],
            'posts_per_page' => -1,
            'fields' => 'ids',
        ];
        
        $respostas = get_posts($respostas_args);
        $users_com_resposta = [];
        
        foreach ($respostas as $resposta_id) {
            $user_id = get_post_field('post_author', $resposta_id);
            if ($user_id) {
                $users_com_resposta[] = (int) $user_id;
            }
        }
        
        // Atualizar meta '_vs_votantes' com usuários reais
        $users_com_resposta = array_unique($users_com_resposta);
        update_post_meta($votacao->ID, '_vs_votantes', $users_com_resposta);
        update_post_meta($votacao->ID, '_vs_qtd_votos', count($users_com_resposta));
    }
}
