<?php
/**
 * Shortcode: [votacoes_home_feed]
 *
 * Exibe um feed de votações agrupado por Ano e Evento.
 *
 * ➤ O que faz:
 * - Lista todas as votações agrupadas por ano.
 * - Dentro de cada ano, separa por Evento (taxonomia).
 * - Mostra título, excerpt, status (Aberta, Em Pausa, Encerrada).
 * - Um botão "Ver Votação" é exibido apenas se a votação estiver "Aberta" e publicada.
 *
 * ➤ Filtros disponíveis:
 * - Você pode usar parâmetros na URL para filtrar por Evento e/ou Ano:
 *
 *     ?evento=slug-do-evento&ano=2024
 *
 * Exemplo prático de uso com filtro:
 *     https://dominio.com.br/pagina-com-feed/?evento=evento-x&ano=2024
 *
 * ➤ Como usar:
 * - Insira o shortcode abaixo em qualquer página ou bloco:
 *
 *     [votacoes_home_feed]
 *
 * ➤ Estrutura esperada:
 * - Post Type: votacoes
 * - Taxonomia: eventos
 * - Campos personalizados:
 *   - _vs_ano (Ano da votação)
 *   - _vs_status (Aberta, Em Pausa, Encerrada)
 *   - Excerpt do post
 *
 * ➤ CSS:
 * - O estilo do feed está em /voting-system/assets/css/vs-feed.css
 *
 * ➤ Local do arquivo:
 * - voting-system/shortcodes/feeds/home-feed.php
 *
 */

defined( 'ABSPATH' ) || exit;

function vs_shortcode_home_feed($atts) {
    // Filtros
    $eventos = get_terms(['taxonomy' => 'eventos', 'hide_empty' => false]);
    
    // Usa função helper para obter anos disponíveis
    $anos_disponiveis = vs_get_available_years();

    $filtro_evento = sanitize_text_field($_GET['evento'] ?? '');
    $filtro_ano = sanitize_text_field($_GET['ano'] ?? '');

    // Prepara filtros para função helper
    $filters = [
        'post_status' => 'publish',
        'evento' => $filtro_evento,
        'ano' => $filtro_ano,
    ];

    // Usa função helper para construir argumentos da query
    $args = vs_build_votacoes_query_args($filters);
    
    // Adiciona ordenação específica para home feed
    $args['meta_key'] = '_vs_ano';
    $args['orderby'] = 'meta_value_num';
    $args['order'] = 'DESC';

    $query = new WP_Query($args);

    // Usa função helper para agrupar votações por ano e evento
    $agrupado = vs_group_votacoes_by_year_and_event($query);

    // Carrega o template com os dados
    ob_start();
    include(plugin_dir_path(dirname(dirname(dirname(__FILE__)))) . 'templates/public/template-home-feed.php');
    return ob_get_clean();
}
add_shortcode('votacoes_home_feed', 'vs_shortcode_home_feed');
