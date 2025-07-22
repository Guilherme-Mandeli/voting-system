<?php

defined( 'ABSPATH' ) || exit;

function vs_register_tax_event() {
    $labels = array(
        'name'              => 'Eventos',
        'singular_name'     => 'Evento',
        'search_items'      => 'Buscar Eventos',
        'all_items'         => 'Todos os Eventos',
        'parent_item'       => 'Evento Pai',
        'parent_item_colon' => 'Evento Pai:',
        'edit_item'         => 'Editar Evento',
        'update_item'       => 'Atualizar Evento',
        'add_new_item'      => 'Adicionar Novo Evento',
        'new_item_name'     => 'Novo nome do Evento',
        'menu_name'         => 'Eventos'
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => array('slug' => 'eventos'),
        'show_in_rest'      => true
    );

    register_taxonomy('eventos', array('votacoes'), $args);
}
add_action('init', 'vs_register_tax_event');
