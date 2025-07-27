<?php
/**
 * Template principal para o feed de votações na home
 * Design inspirado em Fluent Design + Material UI
 */

defined('ABSPATH') || exit;
?>

<div class="vs-home-feed-container">
    <?php 
    // Inclui o template de filtros
    include(plugin_dir_path(__FILE__) . 'template-home-feed-filtros.php'); 
    
    // Inclui o template de cards
    include(plugin_dir_path(__FILE__) . 'template-home-feed-cards.php'); 
    ?>
</div>
