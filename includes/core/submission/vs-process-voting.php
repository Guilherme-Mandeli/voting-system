<?php
/**
 * Processamento de votações anteriores
 * 
 * @package VotingSystem\Core\Submission
 */

defined('ABSPATH') || exit;

/**
 * Obtém as respostas importadas de uma pergunta
 *
 * @param int $question_id ID da pergunta
 * @return array Array com as respostas importadas
 */
function vs_obter_imported_answers($question_id) {
    $respostas_json = get_post_meta($question_id, 'vs_imported_answers', true);
    return $respostas_json ? json_decode($respostas_json, true) : [];
}

/**
 * Processa as respostas unificadas de uma votação anterior para criar opções de uma nova pergunta
 *
 * @param array $question Array com dados da pergunta atual
 * @param int $votacao_id ID da votação atual
 * @return array Array com opções únicas baseadas nas respostas unificadas
 */
function vs_processar_pergunta_imported_vote($question, $votacao_id) {
    // Verifica se existem respostas importadas
    if (empty($question['imported_answers'])) {
        return [];
    }

    // Decodifica o JSON das respostas importadas
    $answers_data = json_decode($question['imported_answers'], true);
    if (!$answers_data || empty($answers_data['questions'])) {
        return [];
    }

    // Array para armazenar todas as respostas processadas
    $todas_respostas = [];

    // Processa cada pergunta e suas respostas
    foreach ($answers_data['questions'] as $question_importada) {
        if (!empty($question_importada['imported_answers'])) {
            foreach ($question_importada['imported_answers'] as $resposta) {
                $todas_respostas[] = [
                    'value' => $resposta['value'] ?? '',
                    'value_unificada' => $resposta['value_unificada'] ?? '',
                    'qtd_votos' => intval($resposta['qtd_votos'] ?? 0)
                ];
            }
        }
    }

    // Ordena as respostas por quantidade de votos (maior para menor)
    usort($todas_respostas, function($a, $b) {
        return $b['qtd_votos'] - $a['qtd_votos'];
    });

    // Atualiza o post meta com as respostas processadas
    update_post_meta($votacao_id, 'vs_imported_answers', wp_json_encode([
        'questions' => [[
            'imported_answers' => $todas_respostas
        ]]
    ]));

    return $todas_respostas;
}
