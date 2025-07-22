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
