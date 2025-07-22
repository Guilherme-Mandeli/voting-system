<?php

defined( 'ABSPATH' ) || exit;

function registrar_cpt_votacoes() {
    $labels = array(
        'name'               => 'Votações',
        'singular_name'      => 'Votação',
        'menu_name'          => 'Votações',
        'name_admin_bar'     => 'Votação',
        'add_new'            => 'Adicionar Nova',
        'add_new_item'       => 'Adicionar Nova Votação',
        'new_item'           => 'Nova Votação',
        'edit_item'          => 'Editar Votação',
        'view_item'          => 'Ver Votação',
        'all_items'          => 'Todas as Votações',
        'search_items'       => 'Buscar Votações',
        'not_found'          => 'Nenhuma votação encontrada.',
        'not_found_in_trash' => 'Nenhuma votação encontrada na lixeira.'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'rewrite'            => array('slug' => 'votacoes'),
        'supports'           => array('title', 'editor', 'excerpt'),
        'menu_icon'          => 'dashicons-megaphone',
        'show_in_rest'       => true
    );

    register_post_type('votacoes', $args);
}
add_action('init', 'registrar_cpt_votacoes');

function vs_restrict_votacoes_to_logged_in() {
    if ( is_singular('votacoes') && !is_user_logged_in() ) {
        // Redireciona para página de login e volta para o post após login
        wp_redirect( wp_login_url( get_permalink() ) );
        exit;
    }
}
add_action( 'template_redirect', 'vs_restrict_votacoes_to_logged_in' );
