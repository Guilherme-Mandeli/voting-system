<?php
/**
 * Arquivo de debug temporário - REMOVER após resolver o problema
 */

// Adicionar ao final do voting-system.php temporariamente
add_action( 'admin_notices', function() {
    if ( current_user_can( 'manage_options' ) ) {
        echo '<div class="notice notice-info"><p>';
        echo '<strong>DEBUG Voting System:</strong><br>';
        echo 'Plugin carregado: ' . ( defined( 'VS_PLUGIN_PATH' ) ? 'SIM' : 'NÃO' ) . '<br>';
        echo 'CPT registrado: ' . ( post_type_exists( 'votacoes' ) ? 'SIM' : 'NÃO' ) . '<br>';
        echo 'Hook init executado: ' . ( did_action( 'init' ) ? 'SIM' : 'NÃO' ) . '<br>';
        echo 'Função existe: ' . ( function_exists( 'vs_register_cpt_voting' ) ? 'SIM' : 'NÃO' ) . '<br>';
        echo '</p></div>';
    }
});