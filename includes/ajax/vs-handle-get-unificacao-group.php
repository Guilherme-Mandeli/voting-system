<?php
defined( 'ABSPATH' ) || exit;

/**
 * AJAX: Return responses for a given voting ID filtered by a unified answer key.
 *
 * Accepts either:
 * - A JSON string previously generated via wp_json_encode() (ex.: '["AA","s2"]')
 * - A plain string (ex.: 'AA', 'Sao Paulo')
 * - An array (fallback; we re-encode)
 *
 * @return void Outputs JSON and exits.
 */
function vs_ajax_get_unificacao_group() {
    // Security check with nonce
    if ( ! vs_verify_post_nonce( 'vs_unificacao_nonce' ) ) {
        wp_send_json_error( 'Security check failed (nonce). Please reload the page.' );
    }

    $votacao_id = isset( $_POST['votacao_id'] ) ? intval( $_POST['votacao_id'] ) : 0;

    $raw = isset( $_POST['resposta_unificada'] ) ? wp_unslash( $_POST['resposta_unificada'] ) : '';
    if ( is_array( $raw ) ) {
        $resposta_unificada_key = wp_json_encode( array_map('sanitize_text_field', array_values( $raw )) );
    } else {
        $resposta_unificada_key = sanitize_text_field( (string) $raw );
    }

    if ( ! $votacao_id || '' === $resposta_unificada_key ) {
        wp_send_json_error( 'Invalid parameters.' );
    }

    // Query all responses for the given votacao_id
    $query_args = array(
        'post_type'      => 'votacao_resposta',
        'posts_per_page' => -1,
        'post_status'    => array( 'publish', 'private' ),
        'fields'         => 'ids',
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

    $posts = get_posts( $query_args );

    if ( empty( $posts ) ) {
        wp_send_json_error( 'No responses found for this voting.' );
    }

    // Usando helper para obter perguntas
    $questions = function_exists( 'vs_get_voting_questions' )
        ? vs_get_voting_questions( $votacao_id )
        : array();

    $results = array();

    foreach ( $posts as $post_id ) {
        $user_id = get_post_meta( $post_id, 'vs_usuario_id', true );
        $user    = $user_id ? get_userdata( $user_id ) : null;
        $usuario_texto = $user
            ? $user->display_name . ' (' . $user->user_email . ')'
            : 'Usuário desconhecido';

        $respostas = get_post_meta( $post_id, 'vs_respostas_detalhadas', true );
        if ( ! is_array( $respostas ) ) {
            $respostas = array();
        }

        // Array de valores unificados POR RESPOSTA
        $unifications = get_post_meta( $post_id, 'vs_resposta_unificada', true );
        if ( ! is_array( $unifications ) ) {
            $unifications = array();
        }

        // Itera sobre cada resposta individual para verificar se foi unificada com o valor selecionado
        foreach ( $respostas as $index => $resposta ) {
            // Verifica se esta resposta específica foi unificada com o valor selecionado
            $unificada_val = isset( $unifications[ $index ] ) ? $unifications[ $index ] : '';
            
            // Pula se não há unificação para esta resposta
            if ( empty( $unificada_val ) ) {
                continue;
            }

            // Verifica se a unificação desta resposta corresponde ao valor selecionado
            $match = false;
            if ( $unificada_val === $resposta_unificada_key ) {
                $match = true;
            }

            if ( ! $match ) {
                continue;
            }

            $question_label = isset( $questions[ $index ]['label'] )
                ? $questions[ $index ]['label']
                : 'Pergunta #' . ( $index + 1 );

            // Usando helper para formatar resposta detalhada
            $resposta_text = function_exists( 'vs_format_answer' )
                ? vs_format_answer( $resposta )
                : ( is_array( $resposta )
                    ? implode( ', ', array_map( 'sanitize_text_field', $resposta ) )
                    : sanitize_text_field( $resposta )
                );

            $results[] = array(
                'usuario'  => $usuario_texto,
                'pergunta' => $question_label,
                'resposta' => $resposta_text,
            );
        }
    }

    if ( empty( $results ) ) {
        wp_send_json_error( 'No responses matched the selected unified value.' );
    }

    wp_send_json_success( array(
        'responses' => $results,
        'resposta_unificada' => $resposta_unificada_key,
    ) );
}

add_action( 'wp_ajax_vs_get_respostas_unificadas', 'vs_ajax_get_unificacao_group' );
add_action( 'wp_ajax_nopriv_vs_get_respostas_unificadas', 'vs_ajax_get_unificacao_group' );
