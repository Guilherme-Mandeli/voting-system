<?php
// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Template principal para o feed de votações na home
 * 
 * @param array $eventos Lista de termos da taxonomia eventos
 * @param array $anos_disponiveis Lista de anos disponíveis
 * @param string $filtro_evento Evento selecionado no filtro
 * @param string $filtro_ano Ano selecionado no filtro
 * @param array $agrupado Array com votações agrupadas por ano e evento
 */
?>

<div class="vs-home-feed-container">
    <?php 
    // Inclui o template de filtros
    include(plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/public/template-home-feed-filtros.php'); 
    
    // Inclui o template de cards
    include(plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/public/template-home-feed-cards.php'); 
    ?>
</div>