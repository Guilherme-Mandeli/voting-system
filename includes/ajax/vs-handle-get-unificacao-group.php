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
function vs_ajax_get_unificacao_group_by_question() {
    // Security check with nonce
    if ( ! vs_verify_post_nonce( 'vs_unificacao_nonce' ) ) {
        wp_send_json_error( 'Security check failed (nonce). Please reload the page.' );
    }

    $votacao_id = isset( $_POST['votacao_id'] ) ? intval( $_POST['votacao_id'] ) : 0;
    $question_index = isset( $_POST['question_index'] ) ? intval( $_POST['question_index'] ) : 0;

    if ( ! $votacao_id ) {
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
        wp_send_json_success( array() );
    }

    $unified_responses = array();
    $response_counts = array();

    foreach ( $posts as $post_id ) {
        // Array de valores unificados POR RESPOSTA
        $unifications = get_post_meta( $post_id, 'vs_resposta_unificada', true );
        if ( ! is_array( $unifications ) ) {
            continue;
        }

        // Verifica se existe unificação para o índice da pergunta específica
        if ( isset( $unifications[ $question_index ] ) && ! empty( $unifications[ $question_index ] ) ) {
            $unified_value = $unifications[ $question_index ];
            
            if ( ! isset( $response_counts[ $unified_value ] ) ) {
                $response_counts[ $unified_value ] = 0;
            }
            $response_counts[ $unified_value ]++;
        }
    }

    // Converter para formato esperado pelo frontend
    foreach ( $response_counts as $resposta_unificada => $count ) {
        $unified_responses[] = array(
            'resposta_unificada' => $resposta_unificada,
            'count' => $count
        );
    }

    wp_send_json_success( $unified_responses );
}

/**
 * AJAX: Return unified responses filtered by current post context
 * Esta função busca TODAS as unificações existentes na votação,
 * independente da pergunta, para exibir no modal do post
 * 
 * @return void Outputs JSON and exits.
 */
function vs_ajax_get_unificacao_group_by_post_context() {
    // Security check with nonce
    if ( ! vs_verify_post_nonce( 'vs_unificacao_nonce' ) ) {
        wp_send_json_error( 'Security check failed (nonce). Please reload the page.' );
    }

    $votacao_id = isset( $_POST['votacao_id'] ) ? intval( $_POST['votacao_id'] ) : 0;
    $question_index = isset( $_POST['question_index'] ) ? intval( $_POST['question_index'] ) : 0;
    $current_post_id = isset( $_POST['current_post_id'] ) ? intval( $_POST['current_post_id'] ) : 0;

    if ( ! $votacao_id ) {
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
        wp_send_json_success( array() );
    }

    $unified_responses = array();
    $response_counts = array();

    foreach ( $posts as $post_id ) {
        // Array de valores unificados POR RESPOSTA
        $unifications = get_post_meta( $post_id, 'vs_resposta_unificada', true );
        if ( ! is_array( $unifications ) ) {
            continue;
        }

        // Itera sobre TODAS as perguntas da resposta, não apenas a pergunta específica
        foreach ( $unifications as $pergunta_index => $unified_value ) {
            if ( ! empty( $unified_value ) ) {
                if ( ! isset( $response_counts[ $unified_value ] ) ) {
                    $response_counts[ $unified_value ] = 0;
                }
                $response_counts[ $unified_value ]++;
            }
        }
    }

    // Converter para formato esperado pelo frontend
    foreach ( $response_counts as $resposta_unificada => $count ) {
        $unified_responses[] = array(
            'resposta_unificada' => $resposta_unificada,
            'count' => $count
        );
    }

    wp_send_json_success( $unified_responses );
}

/**
 * AJAX: Return all responses that have a specific unified answer value
 * 
 * @return void Outputs JSON and exits.
 */
function vs_ajax_get_respostas_unificadas() {
    // Security check with nonce
    if ( ! vs_verify_post_nonce( 'vs_unificacao_nonce' ) ) {
        wp_send_json_error( 'Security check failed (nonce). Please reload the page.' );
    }

    $votacao_id = isset( $_POST['votacao_id'] ) ? intval( $_POST['votacao_id'] ) : 0;
    $resposta_unificada = isset( $_POST['resposta_unificada'] ) ? sanitize_text_field( $_POST['resposta_unificada'] ) : '';

    if ( ! $votacao_id || empty( $resposta_unificada ) ) {
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
        wp_send_json_success( array(
            'resposta_unificada' => $resposta_unificada,
            'responses' => array()
        ) );
    }

    // Get voting configuration to get question labels
    $questions = get_post_meta( $votacao_id, 'vs_questions', true );
    if ( ! is_array( $questions ) ) {
        $questions = array();
    }

    $responses = array();

    foreach ( $posts as $post_id ) {
        // Array de valores unificados POR RESPOSTA
        $unifications = get_post_meta( $post_id, 'vs_resposta_unificada', true );
        if ( ! is_array( $unifications ) ) {
            continue;
        }

        // Array de respostas detalhadas
        $respostas_detalhadas = get_post_meta( $post_id, 'vs_respostas_detalhadas', true );
        if ( ! is_array( $respostas_detalhadas ) ) {
            continue;
        }

        // Get user info
        $user_id = get_post_meta( $post_id, 'vs_usuario_id', true );
        $user = $user_id ? get_userdata( $user_id ) : null;
        $usuario_texto = $user ? sprintf( '#%d %s', $user->ID, $user->user_email ) : '—';

        // Check each question for the unified answer
        foreach ( $unifications as $question_index => $unified_value ) {
            if ( $unified_value === $resposta_unificada ) {
                // Get question label
                $question_label = isset( $questions[ $question_index ]['label'] )
                    ? $questions[ $question_index ]['label']
                    : sprintf( 'Pergunta #%d', ( $question_index + 1 ) );

                // Get original response text
                $resposta_original = isset( $respostas_detalhadas[ $question_index ] )
                    ? $respostas_detalhadas[ $question_index ]
                    : '';

                if ( is_array( $resposta_original ) ) {
                    $resposta_texto = implode( ', ', array_map( 'sanitize_text_field', $resposta_original ) );
                } else {
                    $resposta_texto = sanitize_text_field( $resposta_original );
                }

                $responses[] = array(
                    'usuario' => $usuario_texto,
                    'pergunta' => $question_label,
                    'resposta' => $resposta_texto
                );
            }
        }
    }

    wp_send_json_success( array(
        'resposta_unificada' => $resposta_unificada,
        'responses' => $responses
    ) );
}

add_action( 'wp_ajax_vs_get_unificacao_group', 'vs_ajax_get_unificacao_group_by_question' );
add_action( 'wp_ajax_nopriv_vs_get_unificacao_group', 'vs_ajax_get_unificacao_group_by_question' );

// Nova action para o contexto específico do post
add_action( 'wp_ajax_vs_get_unificacao_group_by_post_context', 'vs_ajax_get_unificacao_group_by_post_context' );
add_action( 'wp_ajax_nopriv_vs_get_unificacao_group_by_post_context', 'vs_ajax_get_unificacao_group_by_post_context' );

// Add the new action
add_action( 'wp_ajax_vs_get_respostas_unificadas', 'vs_ajax_get_respostas_unificadas' );
add_action( 'wp_ajax_nopriv_vs_get_respostas_unificadas', 'vs_ajax_get_respostas_unificadas' );
