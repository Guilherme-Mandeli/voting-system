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
            wp_send_json_error( 'Nonce ausente.' );
        }
        check_ajax_referer( 'vs_unificacao_nonce', 'nonce' );

        // ------------------------------------------------------------------
        // Get & sanitize core fields.
        // ------------------------------------------------------------------
        $votacao_id_raw     = isset( $_POST['votacao_id'] ) ? intval( $_POST['votacao_id'] ) : 0;
        $nova_unificada_raw = isset( $_POST['nova_resposta_unificada'] ) ? wp_unslash( $_POST['nova_resposta_unificada'] ) : '';
        $linhas_json_raw    = isset( $_POST['linhas'] ) ? wp_unslash( $_POST['linhas'] ) : '[]';
        $clear_operation    = isset( $_POST['clear_operation'] ) && $_POST['clear_operation'] === 'true';

        $nova_unificada = sanitize_text_field( $nova_unificada_raw );
        $linhas_array   = json_decode( $linhas_json_raw, true );

        // ------------------------------------------------------------------
        // Validate basics.
        // ------------------------------------------------------------------
        if ( $votacao_id_raw <= 0 ) {
            wp_send_json_error( 'ID de votação inválido.' );
        }
        
        // Permite valor vazio apenas para operações de limpeza
        if ( '' === $nova_unificada && ! $clear_operation ) {
            wp_send_json_error( 'O valor unificado não pode ser vazio.' );
        }
        
        if ( empty( $linhas_array ) || ! is_array( $linhas_array ) ) {
            wp_send_json_error( 'Nenhuma linha válida recebida.' );
        }

        // ------------------------------------------------------------------
        // Capability check.
        // ------------------------------------------------------------------
        if ( ! ( current_user_can( 'edit_post', $votacao_id_raw ) || current_user_can( 'manage_options' ) ) ) {
            wp_send_json_error( 'Você não tem permissão para modificar esta votação.' );
        }

        // ------------------------------------------------------------------
        // Build a map: resposta_post_id => [indices...].
        // ------------------------------------------------------------------
        $resposta_to_indices = vs_parse_resposta_indices_map( $linhas_array );

        if ( empty( $resposta_to_indices ) ) {
            wp_send_json_error( 'Nenhuma combinação válida de resposta/pergunta recebida.' );
        }

        // ------------------------------------------------------------------
        // Update each resposta post individually.
        // ------------------------------------------------------------------
        $updated_summary = array();

        foreach ( $resposta_to_indices as $resposta_id => $indices_list ) {
            $post_obj = get_post( $resposta_id );
            if ( ! $post_obj || 'votacao_resposta' !== $post_obj->post_type ) {
                continue;
            }

            vs_update_resposta_unificada_indices( $resposta_id, $indices_list, $nova_unificada );

            $updated_summary[ $resposta_id ] = $indices_list;
        }

        wp_send_json_success(
            array(
                'votacao_id' => $votacao_id_raw,
                'updated'    => $updated_summary,
                'unificada'  => $nova_unificada,
            )
        );
    }
endif;
