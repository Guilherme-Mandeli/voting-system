<?php
/**
 * Shortcode Genérico: [votacoes_display]
 *
 * Shortcode flexível para exibir votações com múltiplas opções de configuração.
 * Este shortcode substitui e unifica as funcionalidades dos outros shortcodes de votação.
 *
 * ➤ Atributos disponíveis:
 * 
 * FILTROS E LIMITAÇÃO:
 * - limit: Número máximo de votações a exibir (padrão: 10)
 * - per_page: Número de itens por página para paginação (padrão: 6)
 * - show_status: Filtro por status - 'all', 'aberta', 'encerrada', 'em-pausa' (padrão: 'all')
 * - hide_encerradas: Ocultar votações encerradas - true/false (padrão: false)
 * - only_active: Mostrar apenas votações ativas - true/false (padrão: false)
 * - exclude_ids: IDs para excluir, separados por vírgula (ex: '1,2,3')
 * - include_ids: Apenas estes IDs, separados por vírgula (ex: '4,5,6')
 * - show_uncategorized: Mostrar votações sem categoria - true/false (padrão: true)
 * 
 * EXIBIÇÃO E CONTEÚDO:
 * - show_excerpts: Mostrar resumos - true/false (padrão: true)
 * - excerpt_length: Número de palavras no resumo (padrão: 20)
 * - show_participation_badge: Badge "Já votou" - true/false (padrão: true)
 * - show_time_remaining: Mostrar tempo restante - true/false (padrão: true)
 * - show_actions: Mostrar botões de ação - 'visible', 'hidden' (padrão: 'visible')
 * 
 * LAYOUT E AGRUPAMENTO:
 * - layout: Tipo de layout - 'cards', 'list', 'grouped' (padrão: 'cards')
 * - group_by: Agrupar por - 'none', 'year', 'category', 'status' (padrão: 'none')
 * - show_filters: Mostrar filtros - true/false (padrão: false)
 * 
 * ORDENAÇÃO:
 * - orderby: Campo de ordenação - 'date', 'title', 'meta_value' (padrão: 'date')
 * - order: Direção da ordenação - 'ASC', 'DESC' (padrão: 'DESC')
 * - meta_key: Chave do meta field para ordenação (quando orderby='meta_value')
 *
 * ➤ Exemplos de uso:
 * [votacoes_display]
 * [votacoes_display limit="5" show_status="aberta" layout="list"]
 * [votacoes_display group_by="year" show_filters="true" hide_encerradas="true"]
 * [votacoes_display include_ids="1,2,3" show_actions="hidden"]
 * [votacoes_display only_active="true" show_participation_badge="false"]
 *
 */

defined('ABSPATH') || exit;

function vs_shortcode_votacoes_generic($atts) {
    // Garante que o CSS seja carregado
    vs_ensure_votacoes_generic_css();
    
    // Parse dos atributos com valores padrão
    $atts = shortcode_atts([
        // Filtros e limitação
        'limit' => '10',
        'per_page' => '6',
        'show_status' => 'all',
        'hide_encerradas' => 'false',
        'only_active' => 'false',
        'exclude_ids' => '',
        'include_ids' => '',
        'show_uncategorized' => 'true',
        
        // Exibição e conteúdo
        'show_excerpts' => 'true',
        'excerpt_length' => '20',
        'show_participation_badge' => 'true',
        'show_time_remaining' => 'true',
        'show_actions' => 'visible',
        
        // Layout e agrupamento
        'layout' => 'cards',
        'group_by' => 'none',
        'show_filters' => 'false',
        
        // Ordenação
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_key' => '',
        
        // Compatibilidade com filtros URL (para home feed)
        'enable_url_filters' => 'false',
    ], $atts, 'votacoes_display');
    
    // Converte strings para booleanos
    $show_uncategorized = filter_var($atts['show_uncategorized'], FILTER_VALIDATE_BOOLEAN);
    $hide_encerradas = filter_var($atts['hide_encerradas'], FILTER_VALIDATE_BOOLEAN);
    $only_active = filter_var($atts['only_active'], FILTER_VALIDATE_BOOLEAN);
    $show_excerpts = filter_var($atts['show_excerpts'], FILTER_VALIDATE_BOOLEAN);
    $show_participation_badge = filter_var($atts['show_participation_badge'], FILTER_VALIDATE_BOOLEAN);
    $show_time_remaining = filter_var($atts['show_time_remaining'], FILTER_VALIDATE_BOOLEAN);
    $show_filters = filter_var($atts['show_filters'], FILTER_VALIDATE_BOOLEAN);
    $enable_url_filters = filter_var($atts['enable_url_filters'], FILTER_VALIDATE_BOOLEAN);
    
    // Converte strings para inteiros
    $limit = intval($atts['limit']);
    $per_page = intval($atts['per_page']);
    $excerpt_length = intval($atts['excerpt_length']);
    
    // Processa IDs para incluir/excluir
    $exclude_ids = !empty($atts['exclude_ids']) ? array_map('intval', explode(',', $atts['exclude_ids'])) : [];
    $include_ids = !empty($atts['include_ids']) ? array_map('intval', explode(',', $atts['include_ids'])) : [];
    
    // Obtém dados para filtros (se necessário)
    $eventos = [];
    $anos_disponiveis = [];
    
    if ($show_filters || $enable_url_filters) {
        $eventos = get_terms(['taxonomy' => 'eventos', 'hide_empty' => false]);
        $anos_disponiveis = vs_get_available_years();
    }
    
    // Captura filtros da URL (se habilitado)
    $filtro_evento = '';
    $filtro_ano = '';
    
    if ($enable_url_filters) {
        $filtro_evento = sanitize_text_field($_GET['evento'] ?? '');
        $filtro_ano = sanitize_text_field($_GET['ano'] ?? '');
    }
    
    // Constrói argumentos da query
    $query_args = vs_build_generic_query_args([
        'limit' => $limit,
        'show_status' => $atts['show_status'],
        'hide_encerradas' => $hide_encerradas,
        'only_active' => $only_active,
        'exclude_ids' => $exclude_ids,
        'include_ids' => $include_ids,
        'orderby' => $atts['orderby'],
        'order' => $atts['order'],
        'meta_key' => $atts['meta_key'],
        'evento' => $filtro_evento,
        'ano' => $filtro_ano,
    ]);
    
    $query = new WP_Query($query_args);
    
    // Processa votações com informações do usuário
    $votacoes_processadas = vs_process_generic_votacoes($query, [
        'show_uncategorized' => $show_uncategorized,
        'show_excerpts' => $show_excerpts,
        'excerpt_length' => $excerpt_length,
        'show_participation_badge' => $show_participation_badge,
        'show_time_remaining' => $show_time_remaining,
        'show_actions' => $atts['show_actions'],
    ]);
    
    // Agrupa votações se necessário
    $dados_finais = vs_group_generic_votacoes($votacoes_processadas, $atts['group_by']);
    
    // Prepara dados para o template
    $template_data = [
        'votacoes' => $dados_finais,
        'atts' => $atts,
        'eventos' => $eventos,
        'anos_disponiveis' => $anos_disponiveis,
        'filtro_evento' => $filtro_evento,
        'filtro_ano' => $filtro_ano,
        'show_filters' => $show_filters,
        'layout' => $atts['layout'],
        'group_by' => $atts['group_by'],
    ];
    
    // Carrega o template apropriado
    ob_start();
    vs_load_generic_template($template_data);
    return ob_get_clean();
}

/**
 * Garante que o CSS do shortcode genérico seja carregado
 * Compatível com page builders (DIVI, Elementor, etc.) e requisições AJAX
 */
function vs_ensure_votacoes_generic_css() {
    // Usa a classe centralizada para carregar CSS
    VS_CSS_Conditional_Loader::ensure_css_for_shortcode('votacoes_display');
}

/**
 * Constrói argumentos da query para o shortcode genérico
 */
function vs_build_generic_query_args($params) {
    $args = [
        'post_type' => 'votacoes',
        'post_status' => 'publish',
        'posts_per_page' => $params['limit'],
    ];
    
    // Meta query
    $meta_query = [];
    
    // Filtro por status
    if ($params['show_status'] !== 'all') {
        $meta_query[] = [
            'key' => '_vs_status',
            'value' => $params['show_status'],
            'compare' => '=',
        ];
    }
    
    // Filtro por ano (URL)
    if (!empty($params['ano'])) {
        $meta_query[] = [
            'key' => '_vs_ano',
            'value' => $params['ano'],
            'compare' => '=',
        ];
    }
    
    // Tax query para eventos
    $tax_query = [];
    if (!empty($params['evento'])) {
        $tax_query[] = [
            'taxonomy' => 'eventos',
            'field' => 'slug',
            'terms' => $params['evento'],
        ];
    }
    
    // Incluir/excluir IDs
    if (!empty($params['include_ids'])) {
        $args['post__in'] = $params['include_ids'];
    }
    
    if (!empty($params['exclude_ids'])) {
        $args['post__not_in'] = $params['exclude_ids'];
    }
    
    // Ordenação
    if ($params['orderby'] === 'meta_value' && !empty($params['meta_key'])) {
        $args['meta_key'] = $params['meta_key'];
        $args['orderby'] = 'meta_value_num';
    } else {
        $args['orderby'] = $params['orderby'];
    }
    
    $args['order'] = $params['order'];
    
    // Adiciona queries se existirem
    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }
    
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }
    
    return $args;
}

/**
 * Processa votações com informações do usuário para o shortcode genérico
 */
function vs_process_generic_votacoes($query, $options) {
    $votacoes = [];
    $user_id = get_current_user_id();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $votacao_id = get_the_ID();
            
            // Obtém dados básicos da votação
            $status = get_post_meta($votacao_id, '_vs_status', true) ?: 'aberta';
            $data_fim = get_post_meta($votacao_id, '_vs_data_fim', true);
            $permitir_edicao = get_post_meta($votacao_id, 'vs_permitir_edicao', true);
            $ano = get_post_meta($votacao_id, '_vs_ano', true) ?: date('Y');
            
            // Verifica se está encerrada
            $is_encerrada = ($status === 'encerrada') || vs_check_votacao_status($data_fim);
            
            // Filtros de status
            if ($options['hide_encerradas'] && $is_encerrada) {
                continue;
            }
            
            if ($options['only_active'] && ($is_encerrada || $status !== 'aberta')) {
                continue;
            }
            
            // Verifica se usuário já votou
            $ja_votou = false;
            $pode_editar = false;
            
            if ($user_id && is_user_logged_in()) {
                $ja_votou = vs_user_already_voted($votacao_id, $user_id);
                $pode_editar = $ja_votou && ($permitir_edicao === '1') && !$is_encerrada;
            }
            
            // Obtém eventos (categorias)
            $eventos_terms = get_the_terms($votacao_id, 'eventos');
            $eventos_nomes = [];
            
            if ($eventos_terms && !is_wp_error($eventos_terms)) {
                foreach ($eventos_terms as $term) {
                    $eventos_nomes[] = $term->name;
                }
            }
            
            // Se não tem categoria e não deve mostrar sem categoria, pula
            if (empty($eventos_nomes) && !$options['show_uncategorized']) {
                continue;
            }
            
            // Define categoria para agrupamento
            $categoria_agrupamento = !empty($eventos_nomes) ? implode(', ', $eventos_nomes) : 'Sem Categoria';
            
            // Prepara excerpt
            $excerpt = '';
            if ($options['show_excerpts']) {
                $excerpt = get_the_excerpt() ?: wp_trim_words(get_the_content(), $options['excerpt_length']);
            }
            
            // Calcula tempo restante
            $tempo_restante = '';
            if ($options['show_time_remaining'] && $data_fim && !$is_encerrada) {
                $tempo_restante = vs_calculate_time_remaining($data_fim);
            }
            
            $votacoes[] = [
                'id' => $votacao_id,
                'title' => get_the_title(),
                'excerpt' => $excerpt,
                'link' => get_permalink(),
                'status' => $status,
                'status_display' => vs_format_status_display($status),
                'publicado' => get_post_status() === 'publish',
                'ano' => $ano,
                'categoria' => $categoria_agrupamento,
                'ja_votou' => $ja_votou,
                'pode_editar' => $pode_editar,
                'is_encerrada' => $is_encerrada,
                'data_fim' => $data_fim,
                'tempo_restante' => $tempo_restante,
                'show_participation_badge' => $options['show_participation_badge'],
                'show_actions' => $options['show_actions'],
            ];
        }
        wp_reset_postdata();
    }
    
    return $votacoes;
}

/**
 * Agrupa votações processadas conforme especificado
 */
function vs_group_generic_votacoes($votacoes, $group_by) {
    if ($group_by === 'none') {
        return $votacoes;
    }
    
    $agrupado = [];
    
    foreach ($votacoes as $votacao) {
        $chave_grupo = '';
        
        switch ($group_by) {
            case 'year':
                $chave_grupo = $votacao['ano'];
                break;
            case 'category':
                $chave_grupo = $votacao['categoria'];
                break;
            case 'status':
                $chave_grupo = $votacao['status_display'];
                break;
            default:
                $chave_grupo = 'Todas';
                break;
        }
        
        if (!isset($agrupado[$chave_grupo])) {
            $agrupado[$chave_grupo] = [];
        }
        
        $agrupado[$chave_grupo][] = $votacao;
    }
    
    // Ordenação especial para agrupamentos
    if ($group_by === 'year') {
        krsort($agrupado); // Anos decrescente
    } elseif ($group_by === 'category') {
        // Sem Categoria por último
        uksort($agrupado, function($a, $b) {
            if ($a === 'Sem Categoria') return 1;
            if ($b === 'Sem Categoria') return -1;
            return strcmp($a, $b);
        });
    }
    
    return $agrupado;
}

/**
 * Carrega o template apropriado para o shortcode genérico
 */
function vs_load_generic_template($data) {
    $template_path = plugin_dir_path(dirname(dirname(dirname(__FILE__)))) . 'templates/public/';
    
    // Define qual template usar baseado no layout
    switch ($data['layout']) {
        case 'list':
            $template_file = 'template-votacoes-generic-list.php';
            break;
        case 'grouped':
            $template_file = 'template-votacoes-generic-grouped.php';
            break;
        case 'cards':
        default:
            $template_file = 'template-votacoes-generic-cards.php';
            break;
    }
    
    // Extrai dados para o template
    extract($data);
    
    // Inclui o template
    $full_path = $template_path . $template_file;
    if (file_exists($full_path)) {
        include $full_path;
    } else {
        // Fallback para template básico
        include $template_path . 'template-votacoes-generic-fallback.php';
    }
}

// Registra o shortcode
add_shortcode('votacoes_display', 'vs_shortcode_votacoes_generic');
