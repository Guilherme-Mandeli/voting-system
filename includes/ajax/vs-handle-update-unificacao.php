<?php
/**
 * AJAX handler: Update unified answer value per (response post, question index) pair.
 *
 * Stores data in post meta 'vs_resposta_unificada' as an associative array:
 * [ question_index => unification_string, ... ].
 *
 * Expected POST:
 * - nonce                   Nonce (wp_create_nonce( 'vs_unificacao_nonce' )).
 * - votacao_id              (int) Voting post ID (used for capability check).
 * - nova_resposta_unificada (string) Value to assign.
 * - linhas                  (JSON) Array of { postId:int, perguntaIndex:int } items.
 *
 * Response JSON (success):
 * {
 *     "votacao_id": <int>,
 *     "updated": {
 *         "<resposta_post_id>": [ <idx>, <idx> ],
 *         ...
 *     },
 *     "unificada": "<string>"
 * }
 *
 * @package VotingSystem\Admin\Ajax
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_vs_update_resposta_unificada', 'vs_ajax_update_unificacao' );

if ( ! function_exists( 'vs_ajax_update_unificacao' ) ) :
    /**
     * Process AJAX request and persist unified answer values per selected row.
     *
     * @return void Outputs JSON and dies.
     */
    function vs_ajax_update_unificacao() {
        // ------------------------------------------------------------------
        // Nonce.
        // ------------------------------------------------------------------
        if ( ! isset( $_POST['nonce'] ) ) {
            error_log( 'Unificacao: nonce missing.' );
            wp_send_json_error( 'Nonce ausente.' );
        }
        check_ajax_referer( 'vs_unificacao_nonce', 'nonce' );

        // ------------------------------------------------------------------
        // Get & sanitize core fields.
        // ------------------------------------------------------------------
        $votacao_id_raw     = isset( $_POST['votacao_id'] ) ? intval( $_POST['votacao_id'] ) : 0;
        $nova_unificada_raw = isset( $_POST['nova_resposta_unificada'] ) ? wp_unslash( $_POST['nova_resposta_unificada'] ) : '';
        $linhas_json_raw    = isset( $_POST['linhas'] ) ? wp_unslash( $_POST['linhas'] ) : '[]';

        $nova_unificada = sanitize_text_field( $nova_unificada_raw );
        $linhas_array   = json_decode( $linhas_json_raw, true );

        error_log( 'Unificacao POST votacao_id=' . $votacao_id_raw );
        error_log( 'Unificacao POST valor=' . $nova_unificada );
        error_log( 'Unificacao POST linhas=' . $linhas_json_raw );

        // ------------------------------------------------------------------
        // Validate basics.
        // ------------------------------------------------------------------
        if ( $votacao_id_raw <= 0 ) {
            error_log( 'Unificacao: invalid voting ID.' );
            wp_send_json_error( 'ID de votação inválido.' );
        }
        if ( '' === $nova_unificada ) {
            error_log( 'Unificacao: empty value.' );
            wp_send_json_error( 'O valor unificado não pode ser vazio.' );
        }
        if ( empty( $linhas_array ) || ! is_array( $linhas_array ) ) {
            error_log( 'Unificacao: linhas array missing/invalid.' );
            wp_send_json_error( 'Nenhuma linha válida recebida.' );
        }

        // ------------------------------------------------------------------
        // Capability check.
        // ------------------------------------------------------------------
        if ( ! ( current_user_can( 'edit_post', $votacao_id_raw ) || current_user_can( 'manage_options' ) ) ) {
            error_log( 'Unificacao: user lacks capability for votacao ' . $votacao_id_raw );
            wp_send_json_error( 'Você não tem permissão para modificar esta votação.' );
        }

        // ------------------------------------------------------------------
        // Build a map: resposta_post_id => [indices...].
        // ------------------------------------------------------------------
        $resposta_to_indices = vs_parse_resposta_indices_map( $linhas_array );

        if ( empty( $resposta_to_indices ) ) {
            error_log( 'Unificacao: no valid pairs after parsing linhas.' );
            wp_send_json_error( 'Nenhuma combinação válida de resposta/pergunta recebida.' );
        }

        // ------------------------------------------------------------------
        // Update each resposta post individually.
        // ------------------------------------------------------------------
        $updated_summary = array();

        foreach ( $resposta_to_indices as $resposta_id => $indices_list ) {

            $post_obj = get_post( $resposta_id );
            if ( ! $post_obj || 'votacao_resposta' !== $post_obj->post_type ) {
                error_log( 'Unificacao: post ' . $resposta_id . ' invalid type.' );
                continue;
            }

            vs_update_resposta_unificada_indices( $resposta_id, $indices_list, $nova_unificada );

            $updated_summary[ $resposta_id ] = $indices_list;

            error_log(
                sprintf(
                    'Unificacao: resposta %d updated idx(s) %s => %s',
                    $resposta_id,
                    implode( ',', $indices_list ),
                    $nova_unificada
                )
            );
        }

        // ------------------------------------------------------------------
        // (Opcional) Atualizar meta global vs_questions no post de votação?
        // ------------------------------------------------------------------
        // Comportamento desativado por padrão para evitar sobrescrever globalmente.
        // Caso queira habilitar, descomente abaixo e forneça um array de índices global.
        /*
        $questions = get_post_meta( $votacao_id_raw, 'vs_questions', true );
        if ( is_array( $questions ) ) {
            // Colete todos os índices únicos atualizados (somando todos os usuários)
            $all_indices = array();
            foreach ( $updated_summary as $resposta_id => $idxs ) {
                foreach ( $idxs as $i ) {
                    $all_indices[ $i ] = true;
                }
            }
            foreach ( array_keys( $all_indices ) as $i ) {
                if ( isset( $questions[ $i ] ) && is_array( $questions[ $i ] ) ) {
                    $questions[ $i ]['unificada'] = $nova_unificada;
                }
            }
            update_post_meta( $votacao_id_raw, 'vs_questions', $questions );
        }
        */

        error_log( '=== Fim vs_ajax_update_unificacao ===' );

        wp_send_json_success(
            array(
                'votacao_id' => $votacao_id_raw,
                'updated'    => $updated_summary,
                'unificada'  => $nova_unificada,
            )
        );
    }
endif;
