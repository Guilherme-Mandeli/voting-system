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
function vs_register_cpt_answer() {
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
            // Adjust other flags (menu_position, menu_icon etc.) as needed.
        )
    );
}
add_action( 'init', 'vs_register_cpt_answer' );

/**
 * Restrict access to 'votacao_resposta' posts to logged-in users.
 *
 * @return void
 */
function vs_restrict_answer_to_logged_in() {
    if ( is_singular( 'votacao_resposta' ) && ! is_user_logged_in() ) {
        wp_safe_redirect( wp_login_url( get_permalink() ) );
        exit;
    }
}
add_action( 'template_redirect', 'vs_restrict_answer_to_logged_in' );

/**
 * Automatically publish 'votacao_resposta' posts when restored from trash.
 *
 * @param int $post_id The post ID that was restored.
 * @return void
 */
function vs_auto_publish_restored_answer( $post_id ) {
    $post = get_post( $post_id );
    
    // Verifica se é um post do tipo 'votacao_resposta'
    if ( ! $post || $post->post_type !== 'votacao_resposta' ) {
        return;
    }
    
    // Se o post foi restaurado como rascunho, publica automaticamente
    if ( $post->post_status === 'draft' ) {
        wp_update_post( array(
            'ID' => $post_id,
            'post_status' => 'publish'
        ) );
    }
}
add_action( 'untrashed_post', 'vs_auto_publish_restored_answer' );

/* ------------------------------------------------------------------------- *
 * Admin List Table Columns
 * ------------------------------------------------------------------------- */

/**
 * Filter admin columns for 'votacao_resposta'.
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function vs_manage_answer_columns( $columns ) {
    return array(
        'cb'        => isset( $columns['cb'] ) ? $columns['cb'] : '<input type="checkbox" />',
        'title'     => __( 'Título', 'voting-system' ),
        'usuario'   => __( 'Usuário', 'voting-system' ),
        'votacao'   => __( 'Votação', 'voting-system' ),
        'data_envio'=> __( 'Data de Envio', 'voting-system' ),
    );
}
add_filter( 'manage_votacao_resposta_posts_columns', 'vs_manage_answer_columns' );

/**
 * Render custom column data in admin list table.
 *
 * @param string $column  Column key.
 * @param int    $post_id Current post ID.
 * @return void
 */
function vs_manage_answer_columns_cb( $column, $post_id ) {

    if ( 'usuario' === $column ) {
        $user_id = get_post_meta( $post_id, 'vs_usuario_id', true );
        if ( $user_id ) {
            $user_info = get_userdata( $user_id );
            if ( $user_info ) {
                printf(
                    '#%d %s',
                    absint( $user_info->ID ),
                    esc_html( $user_info->user_email )
                );
            } else {
                printf( 'ID %d', absint( $user_id ) );
            }
        } else {
            echo '&#8212;';
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
            echo esc_html(
                date_i18n(
                    get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
                    strtotime( $data_envio )
                )
            );
        } else {
            echo esc_html( get_the_date( '', $post_id ) );
        }
        return;
    }
}
add_action( 'manage_votacao_resposta_posts_custom_column', 'vs_manage_answer_columns_cb', 10, 2 );

/* ------------------------------------------------------------------------- *
 * Meta Box: Detailed Responses (per post)
 * ------------------------------------------------------------------------- */

/**
 * Register the meta box for 'votacao_resposta'.
 *
 * @return void
 */
function vs_add_answer_metabox() {
    add_meta_box(
        'votacao_resposta_detalhes',
        __( 'Detalhes da Resposta', 'voting-system' ),
        'vs_render_answer_metabox',
        'votacao_resposta',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'vs_add_answer_metabox' );

/**
 * Render the per-response details meta box.
 *
 * @param WP_Post $post Current response post object.
 * @return void
 */
function vs_render_answer_metabox( $post ) {

    $respostas = get_post_meta( $post->ID, 'vs_respostas_detalhadas', true );

    echo '<div style="max-height: 400px; overflow-y: auto;">';

    if ( empty( $respostas ) || ! is_array( $respostas ) ) {
        echo '<p><em>' . esc_html__( 'Não há respostas registradas.', 'voting-system' ) . '</em></p>';
        echo '</div>';
        return;
    }

    $votacao_id = get_post_meta( $post->ID, 'vs_votacao_id', true );
    $questions  = get_post_meta( $votacao_id, 'vs_questions', true );
    if ( ! is_array( $questions ) ) {
        $questions = array();
    }

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

        $label = isset( $questions[ $index ]['label'] )
            ? $questions[ $index ]['label']
            : sprintf( 'Pergunta #%d', ( $index + 1 ) );

        if ( is_array( $resposta_value ) ) {
            $resp = implode( ', ', array_map( 'sanitize_text_field', $resposta_value ) );
        } else {
            $resp = sanitize_text_field( $resposta_value );
        }

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
