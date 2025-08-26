<?php
defined('ABSPATH') || exit;

/**
 * Handler AJAX para buscar votações
 */
function vs_ajax_buscar_votacoes() {
    check_ajax_referer(VS_Nonce_Actions::AJAX_ADMIN, 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissão negada');
    }

    // Sanitizar filtros
    $filtros_raw = $_POST['filtros'] ?? [];
    $filtros = [];
    
    if (is_array($filtros_raw)) {
        foreach ($filtros_raw as $key => $value) {
            $key = sanitize_key($key);
            $filtros[$key] = sanitize_text_field($value);
        }
    }
    
    $args = [
        'post_type' => 'votacoes',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ];

    // Aplicar filtros
    if (!empty($filtros['ano'])) {
        $args['meta_query'][] = [
            'key' => '_vs_ano',
            'value' => sanitize_text_field($filtros['ano'])
        ];
    }

    if (!empty($filtros['evento'])) {
        $args['tax_query'][] = [
            'taxonomy' => 'eventos',
            'field' => 'slug',
            'terms' => sanitize_text_field($filtros['evento'])
        ];
    }

    if (!empty($filtros['status'])) {
        $args['meta_query'][] = [
            'key' => '_vs_status',
            'value' => sanitize_text_field($filtros['status'])
        ];
    }

    if (!empty($filtros['busca'])) {
        $args['s'] = sanitize_text_field($filtros['busca']);
    }

    $query = new WP_Query($args);
    $votacoes = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // Obter informações completas do evento
            $eventos = wp_get_post_terms($post_id, 'eventos');
            $event_info = [
                'event_id' => null,
                'event_title' => 'Evento sem nome',
                'event_slug' => null
            ];
            
            if (!empty($eventos) && !is_wp_error($eventos)) {
                $event_info = [
                    'event_id' => $eventos[0]->term_id,
                    'event_title' => $eventos[0]->name,
                    'event_slug' => $eventos[0]->slug
                ];
            }
            
            $votacoes[] = [
                'id' => $post_id,
                'vote_id' => $post_id,
                'vote_title' => get_the_title(),
                'titulo' => get_the_title(), // Manter compatibilidade
                'codigo' => get_post_meta($post_id, '_vs_codigo', true),
                'ano' => get_post_meta($post_id, '_vs_ano', true),
                'status' => get_post_meta($post_id, '_vs_status', true),
                'evento' => $event_info['event_title'], // Manter compatibilidade
                'event_id' => $event_info['event_id'],
                'event_title' => $event_info['event_title'],
                'event_slug' => $event_info['event_slug'],
                'has_valid_questions' => vs_has_valid_questions($post_id)
            ];
        }
    }

    wp_reset_postdata();
    wp_send_json_success($votacoes);
}
add_action('wp_ajax_vs_buscar_votacoes', 'vs_ajax_buscar_votacoes');

/**
 * Handler AJAX para obter perguntas de uma votação
 */
function vs_ajax_obter_perguntas_votacao() {
    
    try {
        if (!check_ajax_referer(VS_Nonce_Actions::AJAX_ADMIN, 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permissão negada');
            return;
        }

        $votacao_id = isset($_POST['votacao_id']) ? intval($_POST['votacao_id']) : 0;
        
        if (!$votacao_id) {
            wp_send_json_error('ID da votação inválido');
            return;
        }

        // Buscar informações do evento ANTES de processar as perguntas
        $votacao_post = get_post($votacao_id);
        if (!$votacao_post) {
            wp_send_json_error('Votação não encontrada');
            return;
        }

        // Buscar evento associado
        $eventos = wp_get_post_terms($votacao_id, 'eventos');
        $event_info = [
            'event_id' => null,
            'event_title' => 'Evento sem nome',
            'event_slug' => null
        ];
        
        if (!empty($eventos) && !is_wp_error($eventos)) {
            $event_info = [
                'event_id' => $eventos[0]->term_id,
                'event_title' => $eventos[0]->name,
                'event_slug' => $eventos[0]->slug
            ];
        }

        $questions = get_post_meta($votacao_id, 'vs_questions', true);

        if (!$questions) {
            wp_send_json_error('Perguntas não encontradas');
            return;
        }
        
        // Filtrar perguntas sem título (label vazio)
        $questions = array_filter($questions, function($question) {
            return !empty($question['label']) && trim($question['label']) !== '';
        });
        
        // Reindexar o array para manter índices sequenciais
        $questions = array_values($questions);
        
        // Se após o filtro não há perguntas válidas, retornar erro
        if (empty($questions)) {
            wp_send_json_error('Nenhuma pergunta válida encontrada para esta votação');
            return;
        }
    

        // Busca todas as respostas da votação
        $args = [
            'post_type' => 'votacao_resposta',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'private'],
            'meta_query' => [
                [
                    'key' => 'vs_votacao_id',
                    'value' => $votacao_id,
                    'compare' => '=',
                ],
            ],
        ];

        $response_posts = get_posts($args);
        $contagem_respostas = [];

        // Processa as respostas para contagem
        foreach ($response_posts as $post) {
            $respostas_detalhadas = get_post_meta($post->ID, 'vs_respostas_detalhadas', true);
            $respostas_unificadas = get_post_meta($post->ID, 'vs_resposta_unificada', true);
            
            if (!is_array($respostas_detalhadas)) continue;
            if (!is_array($respostas_unificadas)) $respostas_unificadas = [];

            foreach ($respostas_detalhadas as $index => $resposta_original) {
                // Ignora respostas vazias
                if (is_array($resposta_original)) {
                    // Para checkboxes e selects múltiplos, verifica se há alguma opção selecionada
                    if (empty($resposta_original)) continue;
                } else {
                    // Para inputs de texto simples
                    if (empty(trim($resposta_original))) continue;
                }

                // Inicializa contagem se não existir
                if (!isset($contagem_respostas[$index])) {
                    $contagem_respostas[$index] = 0;
                    $respostas_por_pergunta[$index] = [];
                }

                $contagem_respostas[$index]++;

                // Armazena a resposta original e unificada
                $valor_original = is_array($resposta_original) ? implode(', ', $resposta_original) : $resposta_original;
                $valor_unificado = $respostas_unificadas[$index] ?? $valor_original;

                // Adiciona ou atualiza a contagem para esta resposta
                $chave_resposta = $valor_unificado;
                if (!isset($respostas_por_pergunta[$index][$chave_resposta])) {
                    $respostas_por_pergunta[$index][$chave_resposta] = [
                        'value' => $valor_original,
                        'value_unificada' => $valor_unificado,
                        'qtd_votos' => 1
                    ];
                } else {
                    $respostas_por_pergunta[$index][$chave_resposta]['qtd_votos']++;
                }
            }
        }

        // Adicionar informações do evento a cada pergunta
        foreach ($questions as $index => &$question) {
            $question['total_votos'] = $contagem_respostas[$index] ?? 0;
            $question['respostas_unificadas'] = !empty($respostas_por_pergunta[$index]) 
                ? array_values($respostas_por_pergunta[$index]) 
                : [];
            $question['vote_id'] = $votacao_id;
            $question['vote_title'] = $votacao_post->post_title;
            $question['event_id'] = $event_info['event_id'];
            $question['event_title'] = $event_info['event_title'];
            $question['event_slug'] = $event_info['event_slug'];
        }

        wp_send_json_success($questions);
    } catch (Exception $e) {
        wp_send_json_error('Erro interno do servidor: ' . $e->getMessage());
        return;
    }
}
add_action('wp_ajax_vs_obter_perguntas_votacao', 'vs_ajax_obter_perguntas_votacao');

/**
 * Verifica se uma votação tem perguntas válidas (com título)
 */
function vs_has_valid_questions($votacao_id) {
    $questions = get_post_meta($votacao_id, 'vs_questions', true);
    
    if (empty($questions) || !is_array($questions)) {
        return false;
    }
    
    // Filtrar perguntas sem título ou com título vazio
    $valid_questions = array_filter($questions, function($question) {
        return !empty($question['label']) && trim($question['label']) !== '';
    });
    
    return !empty($valid_questions);
}
