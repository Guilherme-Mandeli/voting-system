<?php
// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode para exibir feed filtrado das votações.
 *
 * Uso:
 * [votacoes_feed evento="evento-slug" ano="2025" status="aberta" codigo="VOT-2025-15" post_status="publish" post_visibility="public"]
 *
 * Parâmetros:
 * - evento (string): slug da taxonomia 'eventos'. Exemplo: evento="meu-evento"
 * - ano (int|string): ano da votação, conforme campo custom '_vs_ano'. Exemplo: ano="2024"
 * - status (string): status custom da votação (ex: aberta, em-pausa, encerrada)
 * - codigo (string): código único da votação (_vs_codigo)
 * - post_status (string|array): status do post WordPress (default: 'publish')
 * - post_visibility (string|array): visibilidade do post (ex: 'public') - pode usar meta query se tiver esse dado salvo
 *
 * Retorna uma lista HTML simples das votações filtradas com título e link.
 *
 * Exemplo básico:
 * [votacoes_feed ano="2024" status="aberta"]
 */
function vs_shortcode_votacoes_feed($atts) {
    // Garante que o CSS seja carregado usando a classe centralizada
    VS_CSS_Conditional_Loader::ensure_css_for_shortcode('votacoes_feed');
    
    $atts = shortcode_atts([
        'evento' => '',
        'ano' => '',
        'status' => '',
        'codigo' => '',
        'post_status' => 'publish',
        'post_visibility' => '', // Para futuro uso, depende se salvar visibilidade em meta
    ], $atts, 'votacoes_feed');

    // Usa função helper para construir argumentos da query
    $args = vs_build_votacoes_query_args($atts);

    // TODO: para post_visibility, se for meta, pode adicionar meta_query

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        $votacoes = [];
        ob_start();
        include(plugin_dir_path(dirname(dirname(dirname(__FILE__)))) . 'templates/public/template-votacoes-feed.php');
        return ob_get_clean();
    }

    // Usa função helper para formatar os dados para o template
    $votacoes = vs_format_votacoes_for_template($query);

    // Carrega o template com os dados
    ob_start();
    include(plugin_dir_path(dirname(dirname(dirname(__FILE__)))) . 'templates/public/template-votacoes-feed.php');
    return ob_get_clean();
}

add_shortcode('votacoes_feed', 'vs_shortcode_votacoes_feed');
