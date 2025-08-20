<?php
/**
 * Sincronização automática do meta '_vs_votantes' com posts 'votacao_resposta'
 */

// Hook para exclusão permanente
add_action('before_delete_post', 'vs_sync_votantes_on_delete');

function vs_sync_votantes_on_delete($post_id) {
    $post = get_post($post_id);
    
    if (!$post || $post->post_type !== 'votacao_resposta') {
        return;
    }
    
    $votacao_id = get_post_meta($post_id, 'vs_votacao_id', true);
    $user_id = $post->post_author;
    
    if ($votacao_id && $user_id) {
        vs_remove_user_from_votantes($votacao_id, $user_id);
    }
}

// Hook para mover para lixeira
add_action('wp_trash_post', 'vs_sync_votantes_on_trash');

function vs_sync_votantes_on_trash($post_id) {
    $post = get_post($post_id);
    
    if (!$post || $post->post_type !== 'votacao_resposta') {
        return;
    }
    
    $votacao_id = get_post_meta($post_id, 'vs_votacao_id', true);
    $user_id = $post->post_author;
    
    if ($votacao_id && $user_id) {
        vs_remove_user_from_votantes($votacao_id, $user_id);
    }
}

// Hook para restaurar da lixeira
add_action('untrash_post', 'vs_sync_votantes_on_restore');

function vs_sync_votantes_on_restore($post_id) {
    $post = get_post($post_id);
    
    if (!$post || $post->post_type !== 'votacao_resposta') {
        return;
    }
    
    $votacao_id = get_post_meta($post_id, 'vs_votacao_id', true);
    $user_id = $post->post_author;
    
    if ($votacao_id && $user_id) {
        vs_update_votantes($votacao_id, $user_id);
    }
}

/**
 * Remove usuário do meta '_vs_votantes' e atualiza contagem
 */
function vs_remove_user_from_votantes($votacao_id, $user_id) {
    $votantes = get_post_meta($votacao_id, '_vs_votantes', true);
    
    if (!is_array($votantes)) {
        return;
    }
    
    $key = array_search($user_id, $votantes);
    if ($key !== false) {
        unset($votantes[$key]);
        $votantes = array_values($votantes); // Reindexar array
        
        update_post_meta($votacao_id, '_vs_votantes', $votantes);
        update_post_meta($votacao_id, '_vs_qtd_votos', count($votantes));
    }
}
