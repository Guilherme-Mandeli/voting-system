<?php
defined( 'ABSPATH' ) || exit;

/**
 * Verifica se um usuário já votou em uma votação específica.
 *
 * @param int $post_id ID da votação.
 * @param int $user_id ID do usuário.
 * @return bool True se o usuário já votou, false caso contrário.
 */
function vs_user_already_voted($post_id, $user_id) {
    $votantes = get_post_meta($post_id, '_vs_votantes', true);
    if (!is_array($votantes)) {
        $votantes = [];
    }
    return in_array($user_id, $votantes);
}

/**
 * Valida o token da página de agradecimento.
 *
 * @param int $user_id ID do usuário.
 * @param int $votacao_id ID da votação.
 * @param string $token Token a ser validado.
 * @return bool True se o token for válido, false caso contrário.
 */
function vs_validate_thank_you_token($user_id, $votacao_id, $token) {
    $transient_key = 'vs_obrigado_token_' . $user_id . '_' . $votacao_id;
    $token_salvo = get_transient($transient_key);
    return $token_salvo === $token;
}

/**
 * Gera um erro 404 e retorna uma string vazia.
 *
 * @return string String vazia.
 */
function vs_gera_404() {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    nocache_headers();
    return '';
}

/**
 * Recupera e formata as respostas de um post de resposta.
 *
 * @param int $post_resposta_id ID do post de resposta.
 * @return array Array formatado de respostas.
 */
function vs_get_formatted_responses($post_resposta_id) {
    $respostas_meta = get_post_meta($post_resposta_id, 'vs_respostas_detalhadas', true);
    
    // Adapta o formato das respostas para compatibilidade com renderizadores
    if (is_array($respostas_meta)) {
        if (array_key_exists('respostas', $respostas_meta) && is_array($respostas_meta['respostas'])) {
            return $respostas_meta;
        } else {
            return ['respostas' => $respostas_meta];
        }
    } else {
        return ['respostas' => []];
    }
}

/**
 * Recupera as respostas de um usuário para uma votação específica.
 *
 * @param int $user_id ID do usuário.
 * @param int $votacao_id ID da votação.
 * @return array Array formatado de respostas.
 */
function vs_get_user_responses($user_id, $votacao_id) {
    $args = [
        'post_type' => 'votacao_resposta',
        'author' => $user_id,
        'meta_query' => [
            [
                'key' => 'vs_votacao_id',
                'value' => $votacao_id,
                'compare' => '=',
            ]
        ],
        'posts_per_page' => 1,
        'post_status' => ['publish', 'private'],
        'fields' => 'ids',
    ];
    $posts_resposta = get_posts($args);

    if (!empty($posts_resposta)) {
        $post_resposta_id = $posts_resposta[0];
        return vs_get_formatted_responses($post_resposta_id);
    }
    
    return ['respostas' => []];
}

/**
 * Constrói os argumentos para consulta de votações com base em filtros.
 *
 * @param array $filters Array de filtros (ano, status, codigo, evento, post_status).
 * @return array Argumentos para WP_Query.
 */
function vs_build_votacoes_query_args($filters = []) {
    $meta_query = [];
    $tax_query = [];

    // Filtra por ano
    if (!empty($filters['ano'])) {
        $meta_query[] = [
            'key' => '_vs_ano',
            'value' => $filters['ano'],
            'compare' => '=',
        ];
    }

    // Filtra por status
    if (!empty($filters['status'])) {
        $meta_query[] = [
            'key' => '_vs_status',
            'value' => sanitize_text_field($filters['status']),
            'compare' => '=',
        ];
    }

    // Filtra por código
    if (!empty($filters['codigo'])) {
        $meta_query[] = [
            'key' => '_vs_codigo',
            'value' => sanitize_text_field($filters['codigo']),
            'compare' => '=',
        ];
    }

    // Filtra por taxonomia eventos
    if (!empty($filters['evento'])) {
        $tax_query[] = [
            'taxonomy' => 'eventos',
            'field' => 'slug',
            'terms' => sanitize_text_field($filters['evento']),
        ];
    }

    // Post status, pode ser string ou array
    $post_status = $filters['post_status'] ?? 'publish';
    if (is_string($post_status) && strpos($post_status, ',') !== false) {
        $post_status = array_map('trim', explode(',', $post_status));
    }

    // Monta query
    $args = [
        'post_type' => 'votacoes',
        'post_status' => $post_status,
        'posts_per_page' => -1,
    ];

    // Adiciona meta_query se houver filtros
    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }

    // Adiciona tax_query se houver filtros
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }

    return $args;
}

/**
 * Formata os dados de votações para uso em templates.
 *
 * @param WP_Query $query Objeto WP_Query com resultados de votações.
 * @return array Array formatado de votações.
 */
function vs_format_votacoes_for_template($query) {
    $votacoes = [];
    while ($query->have_posts()) {
        $query->the_post();

        $votacao_id = get_the_ID();
        $votacoes[] = [
            'titulo' => get_the_title(),
            'link' => get_permalink(),
            'ano' => get_post_meta($votacao_id, '_vs_ano', true),
            'status' => get_post_meta($votacao_id, '_vs_status', true),
            'codigo' => get_post_meta($votacao_id, '_vs_codigo', true),
        ];
    }
    wp_reset_postdata();
    
    return $votacoes;
}

/**
 * Obtém os anos disponíveis para votações.
 *
 * @return array Array de anos disponíveis, ordenados de forma decrescente.
 */
function vs_get_available_years() {
    $anos = get_posts(['post_type' => 'votacoes', 'numberposts' => -1, 'fields' => 'ids']);
    $anos_disponiveis = [];

    foreach ($anos as $post_id) {
        $ano = get_post_meta($post_id, '_vs_ano', true);
        if ($ano) $anos_disponiveis[] = $ano;
    }

    $anos_disponiveis = array_unique($anos_disponiveis);
    rsort($anos_disponiveis);
    
    return $anos_disponiveis;
}

/**
 * Agrupa votações por ano e evento.
 *
 * @param WP_Query $query Objeto WP_Query com resultados de votações.
 * @return array Array multidimensional agrupado por ano e evento.
 */
function vs_group_votacoes_by_year_and_event($query) {
    $agrupado = [];

    while ($query->have_posts()) {
        $query->the_post();
        $id = get_the_ID();
        $ano = get_post_meta($id, '_vs_ano', true);
        $eventos_post = wp_get_post_terms($id, 'eventos');
        $evento_nome = $eventos_post[0]->name ?? 'Sem Evento';
        $status = get_post_meta($id, '_vs_status', true);
        $agrupado[$ano][$evento_nome][] = [
            'ID' => $id,
            'title' => get_the_title(),
            'link' => get_permalink(),
            'excerpt' => get_the_excerpt(),
            'status' => $status,
            'publicado' => get_post_status($id) === 'publish',
        ];
    }
    wp_reset_postdata();
    
    return $agrupado;
}