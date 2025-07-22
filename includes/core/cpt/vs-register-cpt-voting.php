<?php

defined( 'ABSPATH' ) || exit;

/**
 * Register the 'votacoes' custom post type.
 */
function vs_register_cpt_voting() {
    $labels = array(
        'name'               => __( 'Votações', 'voting-system' ),
        'singular_name'      => __( 'Votação', 'voting-system' ),
        'menu_name'          => __( 'Votações', 'voting-system' ),
        'name_admin_bar'     => __( 'Votação', 'voting-system' ),
        'add_new'            => __( 'Adicionar Nova', 'voting-system' ),
        'add_new_item'       => __( 'Adicionar Nova Votação', 'voting-system' ),
        'new_item'           => __( 'Nova Votação', 'voting-system' ),
        'edit_item'          => __( 'Editar Votação', 'voting-system' ),
        'view_item'          => __( 'Ver Votação', 'voting-system' ),
        'all_items'          => __( 'Todas as Votações', 'voting-system' ),
        'search_items'       => __( 'Buscar Votações', 'voting-system' ),
        'not_found'          => __( 'Nenhuma votação encontrada.', 'voting-system' ),
        'not_found_in_trash' => __( 'Nenhuma votação encontrada na lixeira.', 'voting-system' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'rewrite'            => array( 'slug' => 'votacoes' ),
        'supports'           => array( 'title', 'editor', 'excerpt' ),
        'menu_icon'          => 'dashicons-megaphone',
        'show_in_rest'       => true,
    );

    register_post_type( 'votacoes', $args );
}
add_action( 'init', 'vs_register_cpt_voting' );

/**
 * Restrict access to 'votacoes' posts to logged-in users.
 */
function vs_restrict_voting_to_logged_in() {
    if ( is_singular( 'votacoes' ) && ! is_user_logged_in() ) {
        wp_redirect( wp_login_url( get_permalink() ) );
        exit;
    }
}
add_action( 'template_redirect', 'vs_restrict_voting_to_logged_in' );
