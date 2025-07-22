<?php
/**
 * Custom post type: votacao_resposta
 *
 * Stores individual user responses for a given voting (votacoes).
 * Provides admin listing columns and a meta box that shows each answer slot
 * along with its per-response unified value (stored in post meta 'vs_resposta_unificada'
 * as an associative array: [ question_index => unified_string ] ).
 *
 * @package VotingSystem\Admin\CPT
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the 'votacao_resposta' custom post type.
 *
 * @return void
 */
function vs_register_votacao_resposta_cpt() {
    register_post_type(
        'votacao_resposta',
        array(
            'labels' => array(
                'name'          => __( 'Respostas de Votação', 'voting-system' ),
                'singular_name' => __( 'Resposta de Votação', 'voting-system' ),
            ),
            'public'       => true,
            'show_ui'      => true,
            'supports'     => array( 'title', 'custom-fields' ),
            'capability_type' => 'post',
            // Ajuste outras flags (menu_position, menu_icon etc.) conforme necessidade.
        )
    );
}
add_action( 'init', 'vs_register_votacao_resposta_cpt' );

/**
 * Redirect non-logged users when viewing single votacoes or votacao_resposta.
 *
 * @return void
 */
function vs_redirect_non_logged_votacao() {
    if ( ( is_singular( 'votacoes' ) || is_singular( 'votacao_resposta' ) ) && ! is_user_logged_in() ) {
        wp_safe_redirect( wp_login_url( get_permalink() ) );
        exit;
    }
}
add_action( 'template_redirect', 'vs_redirect_non_logged_votacao' );

/* ------------------------------------------------------------------------- *
 * Admin List Table Columns
 * ------------------------------------------------------------------------- */

/**
 * Filter admin columns for 'votacao_resposta'.
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function vs_manage_votacao_resposta_columns( $columns ) {
    return array(
        'cb'        => isset( $columns['cb'] ) ? $columns['cb'] : '<input type="checkbox" />',
        'title'     => __( 'Título', 'voting-system' ),
        'usuario'   => __( 'Usuário', 'voting-system' ),
        'votacao'   => __( 'Votação', 'voting-system' ),
        'data_envio'=> __( 'Data de Envio', 'voting-system' ),
    );
}
add_filter( 'manage_votacao_resposta_posts_columns', 'vs_manage_votacao_resposta_columns' );

/**
 * Render custom column data in admin list table.
 *
 * @param string $column  Column key.
 * @param int    $post_id Current post ID.
 * @return void
 */
function vs_manage_votacao_resposta_columns_cb( $column, $post_id ) {

    if ( 'usuario' === $column ) {
        $user_id = get_post_meta( $post_id, 'vs_usuario_id', true );
        if ( $user_id ) {
            $user_info = get_userdata( $user_id );
            if ( $user_info ) {
                // Show "#ID email" (matches other admin views in your workflow).
                printf(
                    '#%d %s',
                    absint( $user_info->ID ),
                    esc_html( $user_info->user_email )
                );
            } else {
                printf( 'ID %d', absint( $user_id ) );
            }
        } else {
            echo '&#8212;'; // dash
        }
        return;
    }

    if ( 'votacao' === $column ) {
        $votacao_id = get_post_meta( $post_id, 'vs_votacao_id', true );
        if ( $votacao_id ) {
            $post_votacao = get_post( $votacao_id );
            if ( $post_votacao ) {
                printf(
                    '<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s (ID %3$d)</a>',
                    esc_url( get_edit_post_link( $votacao_id ) ),
                    esc_html( get_the_title( $post_votacao ) ),
                    absint( $votacao_id )
                );
            } else {
                printf( 'ID %d', absint( $votacao_id ) );
            }
        } else {
            echo '&#8212;';
        }
        return;
    }

    if ( 'data_envio' === $column ) {
        $data_envio = get_post_meta( $post_id, 'vs_data_envio', true );
        if ( $data_envio ) {
            // Expecting a date string parseable by strtotime().
            echo esc_html(
                date_i18n(
                    get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
                    strtotime( $data_envio )
                )
            );
        } else {
            // Fallback: use post publish date.
            echo esc_html( get_the_date( '', $post_id ) );
        }
        return;
    }
}
add_action( 'manage_votacao_resposta_posts_custom_column', 'vs_manage_votacao_resposta_columns_cb', 10, 2 );

/* ------------------------------------------------------------------------- *
 * Meta Box: Detailed Responses (per post)
 * ------------------------------------------------------------------------- */

/**
 * Register the meta box for 'votacao_resposta'.
 *
 * @return void
 */
function vs_add_votacao_resposta_metabox() {
    add_meta_box(
        'votacao_resposta_detalhes',
        __( 'Detalhes da Resposta', 'voting-system' ),
        'vs_render_votacao_resposta_metabox',
        'votacao_resposta',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'vs_add_votacao_resposta_metabox' );

/**
 * Render the per-response details meta box.
 *
 * Displays:
 * - Question label (from parent voting config).
 * - User's answer.
 * - Unified value *for this specific response*, taken from meta 'vs_resposta_unificada' array.
 *
 * @param WP_Post $post Current response post object.
 * @return void
 */
function vs_render_votacao_resposta_metabox( $post ) {

    // Load detailed answers (index => answer string|array).
    $respostas = get_post_meta( $post->ID, 'vs_respostas_detalhadas', true );

    echo '<div style="max-height: 400px; overflow-y: auto;">';

    if ( empty( $respostas ) || ! is_array( $respostas ) ) {
        echo '<p><em>Não há respostas registradas.</em></p>';
        echo '</div>';
        return;
    }

    // Load parent voting to get question labels.
    $votacao_id = get_post_meta( $post->ID, 'vs_votacao_id', true );
    $perguntas  = get_post_meta( $votacao_id, 'vs_perguntas', true );
    if ( ! is_array( $perguntas ) ) {
        $perguntas = array();
    }

    // Load per-response unified values array.
    $unifications = get_post_meta( $post->ID, 'vs_resposta_unificada', true );
    if ( ! is_array( $unifications ) ) {
        $unifications = array();
    }

    echo '<table style="width: 100%; border-collapse: collapse;">';
    echo '<thead><tr>';
    echo '<th style="border: 1px solid #ccc; padding: 8px; background: #f9f9f9;">' . esc_html__( 'Pergunta', 'voting-system' ) . '</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px; background: #f9f9f9;">' . esc_html__( 'Resposta', 'voting-system' ) . '</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px; background: #f9f9f9;">' . esc_html__( 'Resposta Unificada', 'voting-system' ) . '</th>';
    echo '</tr></thead><tbody>';

    foreach ( $respostas as $index => $resposta_value ) {

        // Question label fallback.
        $label = isset( $perguntas[ $index ]['label'] )
            ? $perguntas[ $index ]['label']
            : sprintf( 'Pergunta #%d', ( $index + 1 ) );

        // Normalize answer text (answers may be array for checkbox/select multiple).
        if ( is_array( $resposta_value ) ) {
            $resp = implode( ', ', array_map( 'sanitize_text_field', $resposta_value ) );
        } else {
            $resp = sanitize_text_field( $resposta_value );
        }

        // Per-response unified value.
        $resp_unificada = '';
        if ( isset( $unifications[ $index ] ) && '' !== trim( $unifications[ $index ] ) ) {
            $resp_unificada = $unifications[ $index ];
        }

        echo '<tr>';
        echo '<td style="border: 1px solid #ccc; padding: 8px; vertical-align: top;">' . esc_html( $label ) . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html( $resp ) . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px; text-align:center;">';

        if ( '' !== $resp_unificada ) {
            echo esc_html( $resp_unificada );
        } else {
            echo '<em>—</em>';
        }

        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}
