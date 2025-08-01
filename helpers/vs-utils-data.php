<?php
defined( 'ABSPATH' ) || exit;


function vs_get_voting_questions( $votacao_id ) {
    $perguntas = get_post_meta( $votacao_id, 'vs_perguntas', true );
    return is_array( $perguntas ) ? $perguntas : array();
}

function vs_format_unified_answer( $unificada_val ) {
    if ( is_array( $unificada_val ) ) {
        return implode( ', ', array_map( 'sanitize_text_field', $unificada_val ) );
    }
    return sanitize_text_field( $unificada_val );
}

function vs_format_answer( $resposta ) {
    if ( is_array( $resposta ) ) {
        return implode( ', ', array_map( 'sanitize_text_field', $resposta ) );
    }
    return sanitize_text_field( $resposta );
}

/**
 * Parse AJAX linhas array into resposta_id => [indices] map.
 *
 * @param array $linhas_array
 * @return array
 */
function vs_parse_resposta_indices_map( $linhas_array ) {
    $resposta_to_indices = array();
    foreach ( $linhas_array as $linha ) {
        if ( ! is_array( $linha ) ) {
            continue;
        }

        $resposta_id   = isset( $linha['postId'] ) ? intval( $linha['postId'] ) : 0;
        $pergunta_idx  = isset( $linha['perguntaIndex'] ) ? intval( $linha['perguntaIndex'] ) : -1;

        if ( $resposta_id <= 0 || $pergunta_idx < 0 ) {
            continue;
        }

        if ( ! isset( $resposta_to_indices[ $resposta_id ] ) ) {
            $resposta_to_indices[ $resposta_id ] = array();
        }

        // Evita duplicados.
        if ( ! in_array( $pergunta_idx, $resposta_to_indices[ $resposta_id ], true ) ) {
            $resposta_to_indices[ $resposta_id ][] = $pergunta_idx;
        }
    }
    return $resposta_to_indices;
}


/**
 * Update specific indices of the 'vs_resposta_unificada' meta in a response post.
 *
 * @param int    $resposta_id      The response post ID.
 * @param array  $indices_list     List of question indices to update.
 * @param string $nova_unificada   The new unified value.
 * @return bool
 */
function vs_update_resposta_unificada_indices( $resposta_id, $indices_list, $nova_unificada ) {
    $meta_key     = 'vs_resposta_unificada';
    $unifications = get_post_meta( $resposta_id, $meta_key, true );
    if ( ! is_array( $unifications ) ) {
        $unifications = array();
    }
    foreach ( $indices_list as $idx ) {
        $unifications[ $idx ] = $nova_unificada;
    }
    return update_post_meta( $resposta_id, $meta_key, $unifications );
}

function vs_format_user_answers( $respostas, $perguntas ) {
    $respostas_formatadas = [];
    foreach ( $respostas as $index => $resposta ) {
        // Verifica se a pergunta existe no array de perguntas
        if (isset($perguntas[$index])) {
            // Obtemos o enunciado da pergunta, que estará na chave 'label'
            $texto_pergunta = $perguntas[$index]['label'] ?? 'Pergunta #' . (intval($index) + 1);

            // Processa a resposta (verificando se é um array de opções, como no caso de checkboxes ou múltiplos selects)
            $respostas_formatadas[] = [
                'question' => sanitize_text_field($texto_pergunta),
                'answer' => is_array($resposta) ? implode(', ', array_map('sanitize_text_field', $resposta)) : sanitize_text_field($resposta),
            ];
        }
    }
    return $respostas_formatadas;
}

function vs_get_existing_response( $user_id, $votacao_id ) {
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
    return get_posts($args);
}

function vs_update_response_metadata($post_id, $votacao_id, $user_id, $respostas) {
    update_post_meta($post_id, 'vs_votacao_id', $votacao_id);
    update_post_meta($post_id, 'vs_usuario_id', $user_id);
    update_post_meta($post_id, 'vs_resposta_unificada', '');
    update_post_meta($post_id, 'vs_respostas_detalhadas', $respostas);
    update_post_meta($post_id, 'vs_data_envio', current_time('mysql'));
}

function vs_update_votantes($votacao_id, $user_id) {
    $votantes = get_post_meta($votacao_id, '_vs_votantes', true);
    if (!is_array($votantes)) {
        $votantes = [];
    }
    if (!in_array($user_id, $votantes)) {
        $votantes[] = $user_id;
        update_post_meta($votacao_id, '_vs_votantes', $votantes);

        // Atualiza a quantidade de votos com o total de votantes atuais
        $qtd_votos = count($votantes);
        update_post_meta($votacao_id, '_vs_qtd_votos', $qtd_votos);
    }
    return $votantes;
}

function vs_generate_thank_you_token($user_id, $votacao_id) {
    $token = vs_generate_user_token( $user_id, $votacao_id );
    set_transient('vs_obrigado_token_' . $user_id . '_' . $votacao_id, $token, 5 * MINUTE_IN_SECONDS);
    return $token;
}

function vs_check_votacao_status($data_fim) {
    if (!$data_fim) return false;
    
    $timestamp_fim = strtotime($data_fim . ' 00:00:00');
    return $timestamp_fim <= time();
}

function vs_get_votacao_anterior_data($votacao_id) {
    if (empty($votacao_id)) {
        return wp_json_encode(['perguntas' => []]);
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
    $respostas_por_pergunta = [];

    // Processa as respostas para contagem
    foreach ($response_posts as $post) {
        $respostas_detalhadas = get_post_meta($post->ID, 'vs_respostas_detalhadas', true);
        $respostas_unificadas = get_post_meta($post->ID, 'vs_resposta_unificada', true);
        
        if (!is_array($respostas_detalhadas)) continue;
        if (!is_array($respostas_unificadas)) $respostas_unificadas = [];

        foreach ($respostas_detalhadas as $index => $resposta_original) {
            // Ignora respostas vazias
            if (is_array($resposta_original)) {
                if (empty($resposta_original)) continue;
            } else {
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

    // Busca as perguntas originais
    $perguntas = get_post_meta($votacao_id, 'vs_perguntas', true);
    if (!is_array($perguntas)) {
        $perguntas = [];
    }

    // Adiciona contagem de respostas e respostas unificadas para cada pergunta
    foreach ($perguntas as $index => &$pergunta) {
        $pergunta['total_votos'] = $contagem_respostas[$index] ?? 0;
        $pergunta['respostas_importadas'] = !empty($respostas_por_pergunta[$index]) 
            ? array_values($respostas_por_pergunta[$index]) 
            : [];
        $pergunta['pergunta_origem'] = $pergunta['label'] ?? '';
    }

    return wp_json_encode(['perguntas' => $perguntas]);
}
