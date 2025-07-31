<?php
/**
 * Plugin Name: Voting System
 * Plugin URI: https://github.com/guilhermemandeli/voting-system
 * Description: Sistema completo de votações personalizado para WordPress com interface administrativa, shortcodes e sistema de unificação de respostas.
 * Version: 2.250731.1
 * Author: Guilherme Mandeli
 * Author URI: https://github.com/guilhermemandeli
 * Text Domain: voting-system
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 */

// Evita acesso direto
defined( 'ABSPATH' ) || exit;

// Define constantes do plugin
define( 'VS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'VS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'VS_PLUGIN_VERSION', '2.0.0' );
define( 'VS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Carrega o plugin imediatamente
 */
function vs_load_plugin() {
    // Carrega textdomain
    load_plugin_textdomain( 'voting-system', false, dirname( VS_PLUGIN_BASENAME ) . '/languages' );
    
    // Carrega o bootstrap
    require_once VS_PLUGIN_PATH . 'bootstrap.php';
}
add_action( 'plugins_loaded', 'vs_load_plugin', 10 );

/**
 * Ativação do plugin
 */
function vs_activate_plugin() {
    // Força o carregamento do bootstrap
    require_once VS_PLUGIN_PATH . 'bootstrap.php';
    
    // Força o registro do CPT
    if ( function_exists( 'vs_register_cpt_voting' ) ) {
        vs_register_cpt_voting();
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'vs_activate_plugin' );

/**
 * Desativação do plugin
 */
function vs_deactivate_plugin() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'vs_deactivate_plugin' );

/**
 * Debug temporário - REMOVER após resolver
 */
add_action( 'admin_notices', function() {
    if ( current_user_can( 'manage_options' ) && isset( $_GET['debug_vs'] ) ) {
        echo '<div class="notice notice-info"><p>';
        echo '<strong>DEBUG Voting System:</strong><br>';
        echo 'Plugin carregado: ' . ( defined( 'VS_PLUGIN_PATH' ) ? 'SIM' : 'NÃO' ) . '<br>';
        echo 'CPT registrado: ' . ( post_type_exists( 'votacoes' ) ? 'SIM' : 'NÃO' ) . '<br>';
        echo 'Função existe: ' . ( function_exists( 'vs_register_cpt_voting' ) ? 'SIM' : 'NÃO' ) . '<br>';
        echo 'Bootstrap carregado: ' . ( class_exists( 'VS_Bootstrap' ) ? 'SIM' : 'NÃO' ) . '<br>';
        echo '</p></div>';
    }
});
