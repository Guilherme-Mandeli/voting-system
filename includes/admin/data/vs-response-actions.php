<?php
/**
 * Handlers para ações administrativas de respostas (trash/restore)
 * 
 * @package VotingSystem\Admin\Data
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handler para mover resposta para lixeira
 */
function vs_handle_trash_response() {
    // Verifica permissões
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Você não tem permissão para realizar esta ação.', 'Erro de Permissão', [ 'response' => 403 ] );
    }

    // Obtém e valida parâmetros
    $post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
    $votacao_id = isset( $_GET['votacao_id'] ) ? intval( $_GET['votacao_id'] ) : 0;

    if ( $post_id <= 0 ) {
        wp_die( 'ID de resposta inválido.', 'Erro', [ 'response' => 400 ] );
    }

    // Verifica nonce usando a constante
    $nonce_action = VS_Nonce_Actions::ACTION_TRASH . '_' . $post_id;
    if ( ! wp_verify_nonce( $_GET['_wpnonce'], $nonce_action ) ) {
        wp_die( 'Ação não autorizada.', 'Erro de Segurança', [ 'response' => 403 ] );
    }

    // Verifica se o post existe e é do tipo correto
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'votacao_resposta' ) {
        wp_die( 'Resposta não encontrada.', 'Erro', [ 'response' => 404 ] );
    }

    // Move para lixeira
    $result = wp_trash_post( $post_id );
    
    if ( $result ) {
        // Redireciona de volta para a página de resultados
        $redirect_url = admin_url( 'edit.php?post_type=votacoes&page=votacoes_resultados_visualizar&votacao_id=' . $votacao_id );
        $redirect_url = add_query_arg( 'message', 'trashed', $redirect_url );
        wp_redirect( $redirect_url );
        exit;
    } else {
        wp_die( 'Erro ao mover resposta para lixeira.', 'Erro', [ 'response' => 500 ] );
    }
}

/**
 * Handler para restaurar resposta da lixeira
 */
function vs_handle_restore_response() {
    // Verifica permissões
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Você não tem permissão para realizar esta ação.', 'Erro de Permissão', [ 'response' => 403 ] );
    }

    // Obtém e valida parâmetros
    $post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
    $votacao_id = isset( $_GET['votacao_id'] ) ? intval( $_GET['votacao_id'] ) : 0;

    if ( $post_id <= 0 ) {
        wp_die( 'ID de resposta inválido.', 'Erro', [ 'response' => 400 ] );
    }

    // Verifica nonce usando a constante
    $nonce_action = VS_Nonce_Actions::ACTION_RESTORE . '_' . $post_id;
    if ( ! wp_verify_nonce( $_GET['_wpnonce'], $nonce_action ) ) {
        wp_die( 'Ação não autorizada.', 'Erro de Segurança', [ 'response' => 403 ] );
    }

    // Verifica se o post existe e é do tipo correto
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'votacao_resposta' ) {
        wp_die( 'Resposta não encontrada.', 'Erro', [ 'response' => 404 ] );
    }

    // Restaura da lixeira
    $result = wp_untrash_post( $post_id );
    
    if ( $result ) {
        // Redireciona de volta para a página de resultados
        $redirect_url = admin_url( 'edit.php?post_type=votacoes&page=votacoes_resultados_visualizar&votacao_id=' . $votacao_id );
        $redirect_url = add_query_arg( 'message', 'restored', $redirect_url );
        wp_redirect( $redirect_url );
        exit;
    } else {
        wp_die( 'Erro ao restaurar resposta.', 'Erro', [ 'response' => 500 ] );
    }
}

/**
 * Handler para remover permanentemente resposta
 */
function vs_handle_delete_response() {
    // Verifica permissões
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Você não tem permissão para realizar esta ação.', 'Erro de Permissão', [ 'response' => 403 ] );
    }

    // Obtém e valida parâmetros
    $post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
    $votacao_id = isset( $_GET['votacao_id'] ) ? intval( $_GET['votacao_id'] ) : 0;

    if ( $post_id <= 0 ) {
        wp_die( 'ID de resposta inválido.', 'Erro', [ 'response' => 400 ] );
    }

    // Verifica nonce usando a constante
    $nonce_action = VS_Nonce_Actions::ACTION_DELETE . '_' . $post_id;
    if ( ! wp_verify_nonce( $_GET['_wpnonce'], $nonce_action ) ) {
        wp_die( 'Ação não autorizada.', 'Erro de Segurança', [ 'response' => 403 ] );
    }

    // Verifica se o post existe e é do tipo correto
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'votacao_resposta' ) {
        wp_die( 'Resposta não encontrada.', 'Erro', [ 'response' => 404 ] );
    }

    // Remove permanentemente
    $result = wp_delete_post( $post_id, true );
    
    if ( $result ) {
        // Remove o usuário da lista de votantes se necessário
        $voting_user_ids = get_post_meta( $votacao_id, '_vs_votantes', true );
        if ( is_array( $voting_user_ids ) ) {
            $user_id = $post->post_author;
            $key = array_search( $user_id, $voting_user_ids );
            if ( $key !== false ) {
                unset( $voting_user_ids[$key] );
                update_post_meta( $votacao_id, '_vs_votantes', array_values( $voting_user_ids ) );
            }
        }

        // Redireciona de volta para a página de resultados
        $redirect_url = admin_url( 'edit.php?post_type=votacoes&page=votacoes_resultados_visualizar&votacao_id=' . $votacao_id );
        $redirect_url = add_query_arg( 'message', 'deleted', $redirect_url );
        wp_redirect( $redirect_url );
        exit;
    } else {
        wp_die( 'Erro ao remover resposta permanentemente.', 'Erro', [ 'response' => 500 ] );
    }
}

// Registra os handlers
add_action( 'admin_post_vs_trash_response', 'vs_handle_trash_response' );
add_action( 'admin_post_vs_restore_response', 'vs_handle_restore_response' );
add_action( 'admin_post_vs_delete_response', 'vs_handle_delete_response' );