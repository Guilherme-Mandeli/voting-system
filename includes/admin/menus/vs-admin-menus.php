<?php
/**
 * Registra menus administrativos do sistema de votação
 * 
 * @package VotingSystem\Admin\Menus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registra menus administrativos
 */
function vs_register_admin_menus() {
    // Página principal de resultados (lista de votações + links para detalhes)
    add_submenu_page(
        'edit.php?post_type=votacoes',
        __( 'Resultados das Votações', 'voting-system' ),
        __( 'Resultados', 'voting-system' ),
        'manage_options',
        'votacoes_resultados',
        'vs_render_results_page'
    );

    // Página de detalhes oculta (navegada programaticamente)
    add_submenu_page(
        null,
        __( 'Visualizar Resultado', 'voting-system' ),
        __( 'Visualizar Resultado', 'voting-system' ),
        'manage_options',
        'votacoes_resultados_visualizar',
        'vs_render_results_detail_page'
    );
}
add_action( 'admin_menu', 'vs_register_admin_menus' );
