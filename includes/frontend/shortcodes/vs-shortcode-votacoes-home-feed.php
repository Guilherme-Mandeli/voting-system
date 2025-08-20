<?php
/**
 * Shortcode: [votacoes_home_feed]
 *
 * Exibe um feed de votações agrupado por Ano e Evento com funcionalidades avançadas.
 *
 * ➤ Novos recursos:
 * - Considera categorias (taxonomia 'eventos')
 * - Controle avançado de exibição e filtros
 * - Detecta se usuário já participou
 * - Botão "Editar voto" quando aplicável
 * - Tempo restante para votações
 * - Badges de participação
 * - Estilos Fluent Design + Material UI
 *
 * ➤ Atributos disponíveis:
 * 
 * FILTROS E LIMITAÇÃO:
 * - limit: Número máximo de votações a exibir (padrão: 20)
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
 * ➤ Filtros disponíveis:
 * - Você pode usar parâmetros na URL para filtrar por Evento e/ou Ano:
 *     ?evento=slug-do-evento&ano=2024
 *
 * ➤ Como usar:
 * [votacoes_home_feed]
 * [votacoes_home_feed show_uncategorized="false"]
 * [votacoes_home_feed limit="10" hide_encerradas="true"]
 * [votacoes_home_feed only_active="true" show_participation_badge="false"]
 * [votacoes_home_feed exclude_ids="1,2,3" show_time_remaining="false"]
 *
 * ➤ Estrutura esperada:
 * - Post Type: votacoes
 * - Taxonomia: eventos
 * - Campos personalizados:
 *   - _vs_ano (Ano da votação)
 *   - _vs_status (aberta, em-pausa, encerrada)
 *   - _vs_data_fim (Data de encerramento)
 *   - vs_permitir_edicao (1/0)
 *
 */

defined('ABSPATH') || exit;

function vs_shortcode_home_feed($atts) {
    // Garante que o CSS seja carregado
    vs_ensure_home_feed_css();
    
    // Parse dos atributos com valores padrão
    $atts = shortcode_atts([
        // Filtros e limitação
        'limit' => '20',
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
    ], $atts, 'votacoes_home_feed');
    
    // Converte strings para booleanos
    $show_uncategorized = filter_var($atts['show_uncategorized'], FILTER_VALIDATE_BOOLEAN);
    $hide_encerradas = filter_var($atts['hide_encerradas'], FILTER_VALIDATE_BOOLEAN);
    $only_active = filter_var($atts['only_active'], FILTER_VALIDATE_BOOLEAN);
    $show_excerpts = filter_var($atts['show_excerpts'], FILTER_VALIDATE_BOOLEAN);
    $show_participation_badge = filter_var($atts['show_participation_badge'], FILTER_VALIDATE_BOOLEAN);
    $show_time_remaining = filter_var($atts['show_time_remaining'], FILTER_VALIDATE_BOOLEAN);
    
    // Converte strings para inteiros
    $limit = intval($atts['limit']);
    $per_page = intval($atts['per_page']);
    $excerpt_length = intval($atts['excerpt_length']);
    
    // Processa IDs para incluir/excluir
    $exclude_ids = !empty($atts['exclude_ids']) ? array_map('intval', explode(',', $atts['exclude_ids'])) : [];
    $include_ids = !empty($atts['include_ids']) ? array_map('intval', explode(',', $atts['include_ids'])) : [];
    
    // Obtém dados para filtros
    $eventos = get_terms(['taxonomy' => 'eventos', 'hide_empty' => false]);
    $anos_disponiveis = vs_get_available_years();

    // Captura filtros da URL
    $filtro_evento = sanitize_text_field($_GET['evento'] ?? '');
    $filtro_ano = sanitize_text_field($_GET['ano'] ?? '');

    // Constrói argumentos da query
    $query_args = vs_build_home_feed_query_args([
        'limit' => $limit,
        'show_status' => $atts['show_status'],
        'hide_encerradas' => $hide_encerradas,
        'only_active' => $only_active,
        'exclude_ids' => $exclude_ids,
        'include_ids' => $include_ids,
        'evento' => $filtro_evento,
        'ano' => $filtro_ano,
    ]);

    $query = new WP_Query($query_args);

    // Processa votações com informações do usuário
    $votacoes_processadas = vs_process_votacoes_with_user_data($query, [
        'show_uncategorized' => $show_uncategorized,
        'show_excerpts' => $show_excerpts,
        'excerpt_length' => $excerpt_length,
        'show_participation_badge' => $show_participation_badge,
        'show_time_remaining' => $show_time_remaining,
        'show_actions' => $atts['show_actions'],
    ]);

    // Agrupa votações por ano e evento
    $agrupado = vs_group_processed_votacoes($votacoes_processadas);

    // Carrega o template com os dados
    ob_start();
    include(plugin_dir_path(dirname(dirname(dirname(__FILE__)))) . 'templates/public/template-home-feed.php');
    return ob_get_clean();
}

/**
 * Constrói argumentos da query para o home feed
 */
function vs_build_home_feed_query_args($params) {
    $args = [
        'post_type' => 'votacoes',
        'post_status' => 'publish',
        'posts_per_page' => $params['limit'],
        'meta_key' => '_vs_ano',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
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
 * Processa votações adicionando informações do usuário
 */
function vs_process_votacoes_with_user_data($query, $options = []) {
    // Define valores padrão para evitar warnings
    $options = array_merge([
        'hide_encerradas' => false,
        'only_active' => false,
        'show_uncategorized' => true,
        'show_excerpts' => true,
        'excerpt_length' => 20,
        'show_participation_badge' => true,
        'show_time_remaining' => true,
        'show_actions' => 'visible'
    ], $options);
    
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
            
            // Filtros de status - agora sem warnings
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
 * Agrupa votações processadas por ano e categoria
 */
function vs_group_processed_votacoes($votacoes) {
    $agrupado = [];
    
    foreach ($votacoes as $votacao) {
        $ano = $votacao['ano'];
        $categoria = $votacao['categoria'];
        
        if (!isset($agrupado[$ano])) {
            $agrupado[$ano] = [];
        }
        
        if (!isset($agrupado[$ano][$categoria])) {
            $agrupado[$ano][$categoria] = [];
        }
        
        $agrupado[$ano][$categoria][] = $votacao;
    }
    
    // Ordena anos decrescente
    krsort($agrupado);
    
    // Ordena categorias dentro de cada ano (Sem Categoria por último)
    foreach ($agrupado as $ano => &$categorias) {
        uksort($categorias, function($a, $b) {
            if ($a === 'Sem Categoria') return 1;
            if ($b === 'Sem Categoria') return -1;
            return strcmp($a, $b);
        });
    }
    
    return $agrupado;
}

add_shortcode('votacoes_home_feed', 'vs_shortcode_home_feed');

// Verifica se as funções já existem antes de declará-las (proteção contra redeclaração)
if (!function_exists('vs_calculate_time_remaining')) {
    /**
     * Calcula tempo restante para uma votação
     */
    function vs_calculate_time_remaining($data_fim) {
        if (!$data_fim) return '';
        
        $timestamp_fim = strtotime($data_fim . ' 23:59:59');
        $timestamp_agora = time();
        
        if ($timestamp_fim <= $timestamp_agora) {
            return 'Encerrada';
        }
        
        $diferenca = $timestamp_fim - $timestamp_agora;
        $dias = floor($diferenca / (60 * 60 * 24));
        
        if ($dias > 0) {
            return $dias . ' dia' . ($dias > 1 ? 's' : '') . ' restante' . ($dias > 1 ? 's' : '');
        } else {
            $horas = floor($diferenca / (60 * 60));
            if ($horas > 0) {
                return $horas . ' hora' . ($horas > 1 ? 's' : '') . ' restante' . ($horas > 1 ? 's' : '');
            } else {
                return 'Menos de 1 hora';
            }
        }
    }
}

if (!function_exists('vs_format_status_display')) {
    /**
     * Formata status para exibição
     */
    function vs_format_status_display($status) {
        $status_map = [
            'aberta' => 'Aberta',
            'em-pausa' => 'Em pausa',
            'encerrada' => 'Encerrada'
        ];
        
        return $status_map[$status] ?? ucfirst($status);
    }
}

/**
 * Garante que o CSS do home feed seja carregado
 * Compatível com page builders (DIVI, Elementor, etc.) e requisições AJAX
 */
function vs_ensure_home_feed_css() {
    // Usa a classe centralizada para carregar CSS
    VS_CSS_Conditional_Loader::ensure_css_for_shortcode('votacoes_home_feed');
}
