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
 * @param int $pergunta_id ID da pergunta
 * @return array Array com as respostas importadas
 */
function vs_obter_respostas_importadas($pergunta_id) {
    $respostas_json = get_post_meta($pergunta_id, 'vs_respostas_importadas', true);
    return $respostas_json ? json_decode($respostas_json, true) : [];
}

/**
 * Processa as respostas unificadas de uma votação anterior para criar opções de uma nova pergunta
 *
 * @param array $pergunta Array com dados da pergunta atual
 * @param int $votacao_id ID da votação atual
 * @return array Array com opções únicas baseadas nas respostas unificadas
 */
function vs_processar_pergunta_votacao_anterior($pergunta, $votacao_id) {
    // Verifica se existem respostas importadas
    if (empty($pergunta['respostas_importadas'])) {
        return [];
    }

    // Decodifica o JSON das respostas importadas
    $respostas_data = json_decode($pergunta['respostas_importadas'], true);
    if (!$respostas_data || empty($respostas_data['perguntas'])) {
        return [];
    }

    // Array para armazenar todas as respostas processadas
    $todas_respostas = [];

    // Processa cada pergunta e suas respostas
    foreach ($respostas_data['perguntas'] as $pergunta_importada) {
        if (!empty($pergunta_importada['respostas_importadas'])) {
            foreach ($pergunta_importada['respostas_importadas'] as $resposta) {
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
    update_post_meta($votacao_id, 'vs_respostas_importadas', wp_json_encode([
        'perguntas' => [[
            'respostas_importadas' => $todas_respostas
        ]]
    ]));

    return $todas_respostas;
}
