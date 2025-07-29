<?php
/**
 * Helper para funcionalidades de ranking administrativo
 * 
 * @package VotingSystem
 * @subpackage AdminHelpers
 */

// Impede acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtém o ranking de votação com filtros e agrupamentos
 */
function vs_get_voting_ranking($votacao_id, $question_filter = 'all', $group_mode = 'by_answer') {
    $perguntas = vs_get_voting_questions($votacao_id);
    
    if (empty($perguntas)) {
        return [];
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
    $ranking = [];

    foreach ($response_posts as $post) {
        $respostas_detalhadas = get_post_meta($post->ID, 'vs_respostas_detalhadas', true);
        $respostas_unificadas = get_post_meta($post->ID, 'vs_resposta_unificada', true);
        
        if (!is_array($respostas_detalhadas)) continue;
        if (!is_array($respostas_unificadas)) $respostas_unificadas = [];

        foreach ($respostas_detalhadas as $index => $resposta_original) {
            // Verifica se deve filtrar por pergunta específica
            if ($question_filter !== 'all' && $question_filter !== "q{$index}") {
                continue;
            }

            // Verifica se existe resposta unificada para esta pergunta
            $tem_resposta_unificada = isset($respostas_unificadas[$index]) && !empty(trim($respostas_unificadas[$index]));
            
            // Usa resposta unificada se existir, senão usa a original
            $resposta_final = $tem_resposta_unificada
                ? $respostas_unificadas[$index]
                : $resposta_original;

            // Formata a resposta (pode ser array ou string)
            $resposta_formatada = is_array($resposta_final) 
                ? implode(', ', array_map('sanitize_text_field', $resposta_final))
                : sanitize_text_field($resposta_final);

            // Ignora respostas vazias
            if (empty(trim($resposta_formatada))) continue;

            // Cria chave única para o ranking baseada no modo de agrupamento
            if ($group_mode === 'by_answer') {
                // Por resposta: agrupa apenas pelo conteúdo da resposta
                $key = $resposta_formatada;
            } else {
                // Por pergunta: agrupa por pergunta + resposta (comportamento original)
                $key = $index . '|' . $resposta_formatada;
            }

            if (!isset($ranking[$key])) {
                $ranking[$key] = [
                    'question_index' => $index,
                    'question_label' => isset($perguntas[$index]['label']) 
                        ? $perguntas[$index]['label'] 
                        : 'Pergunta #' . ($index + 1),
                    'answer' => $resposta_formatada,
                    'count' => 0,
                    'questions' => [], // Para modo "por resposta", armazena quais perguntas têm essa resposta
                    'is_unified' => false, // Inicializa como false
                    'unified_count' => 0, // Conta quantas respostas unificadas contribuem para este ranking
                ];
            }

            $ranking[$key]['count']++;
            
            // Se esta resposta específica foi unificada, incrementa o contador
            if ($tem_resposta_unificada) {
                $ranking[$key]['unified_count']++;
            }
            
            // Se for agrupamento por resposta, adiciona a pergunta à lista
            if ($group_mode === 'by_answer') {
                if (!in_array($index, $ranking[$key]['questions'])) {
                    $ranking[$key]['questions'][] = $index;
                }
            }
        }
    }

    // Determina se cada item do ranking deve ser considerado "unificado"
    foreach ($ranking as $key => &$stat) {
        // Uma resposta é considerada "unificada" se pelo menos uma das respostas que contribuem para ela foi unificada
        $stat['is_unified'] = $stat['unified_count'] > 0;
    }

    // Ordena por quantidade de votos (decrescente)
    uasort($ranking, function($a, $b) {
        return $b['count'] - $a['count'];
    });

    // === INÍCIO SIMULAÇÃO PARA TESTE DE PAGINAÇÃO - REMOVER APÓS TESTE ===
    // Adiciona 132 itens fictícios para testar paginação
    for ($i = 1; $i <= 1432; $i++) {
        $ranking[] = [
            'question_index' => 0,
            'question_label' => 'Pergunta Teste #1',
            'answer' => 'Resposta simulada #' . $i,
            'count' => rand(1, 50),
            'questions' => [0, 1], // Simula que aparece em 2 perguntas
            'is_unified' => $i % 3 === 0, // 1/3 dos itens será unificado
            'unified_count' => $i % 3 === 0 ? rand(1, 5) : 0,
        ];
    }
    // === FIM SIMULAÇÃO PARA TESTE DE PAGINAÇÃO ===

    return array_values($ranking);
}

/**
 * Gera URL para exportação CSV do ranking
 */
// function vs_get_ranking_export_url($votacao_id, $group_mode, $question_filter) {
//     return admin_url('admin-post.php') . '?' . http_build_query([
//         'action' => 'export_csv_ranking',
//         'votacao_id' => $votacao_id,
//         'group_mode' => $group_mode,
//         'question_filter' => $question_filter
//     ]);
// }

/**
 * Processa dados para paginação do ranking
 */
// function vs_paginate_ranking($ranking, $page = 1, $per_page = 50) {
//     $total_items = count($ranking);
//     $total_pages = ceil($total_items / $per_page);
//     $offset = ($page - 1) * $per_page;
//     $paged_ranking = array_slice($ranking, $offset, $per_page);

//     return [
//         'items' => $paged_ranking,
//         'total_items' => $total_items,
//         'total_pages' => $total_pages,
//         'current_page' => $page,
//         'per_page' => $per_page
//     ];
// }

/**
 * Handler AJAX para carregar todo o ranking com paginação
 */
function vs_handle_get_all_ranking() {
    if (!wp_verify_nonce($_POST['nonce'], 'vs_ranking_nonce')) {
        wp_die('Acesso negado');
    }

    $votacao_id = intval($_POST['votacao_id']);
    $question_filter = sanitize_text_field($_POST['question_filter']);
    $group_mode = sanitize_text_field($_POST['group_mode']);
    $page = max(1, intval($_POST['page']));
    $per_page = 50;

    // Valida o modo de agrupamento
    if (!in_array($group_mode, ['by_question', 'by_answer'])) {
        $group_mode = 'by_answer';
    }

    $ranking = vs_get_voting_ranking($votacao_id, $question_filter, $group_mode);
    $total_items = count($ranking);
    $total_pages = ceil($total_items / $per_page);
    $offset = ($page - 1) * $per_page;
    $paged_ranking = array_slice($ranking, $offset, $per_page);

    $perguntas = vs_get_voting_questions($votacao_id);

    ob_start();
    ?>
    <div class="ranking-results">
        <?php if (empty($paged_ranking)) : ?>
            <p><em>Nenhuma resposta encontrada para os filtros selecionados.</em></p>
        <?php else : ?>
            <!-- Paginação superior -->
            <?php if ($total_pages > 1) : ?>
                <div class="pagination-wrapper">
                    <div class="pagination-info">
                        Exibindo <?php echo count($paged_ranking); ?> de <?php echo $total_items; ?> resultados 
                        (página <?php echo $page; ?> de <?php echo $total_pages; ?>)
                    </div>
                    <div class="pagination-controls">
                        <?php if ($page > 1) : ?>
                            <button class="button pagination-btn" data-page="<?php echo $page - 1; ?>">« Anterior</button>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++) :
                        ?>
                            <button class="button pagination-btn <?php echo $i === $page ? 'button-primary' : ''; ?>" 
                                    data-page="<?php echo $i; ?>"><?php echo $i; ?></button>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages) : ?>
                            <button class="button pagination-btn" data-page="<?php echo $page + 1; ?>">Próxima »</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else : ?>
                <div class="pagination-info single-page">
                    Exibindo <?php echo count($paged_ranking); ?> de <?php echo $total_items; ?> resultados
                </div>
            <?php endif; ?>
            
            <table class="vote-results-table widefat">
                <thead>
                    <tr>
                        <th>Resposta</th>
                        <th>Votos</th>
                        <?php if ($group_mode === 'by_question') : ?>
                            <th>Pergunta</th>
                        <?php endif; ?>
                        <th>Unificado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paged_ranking as $stat) : ?>
                        <tr>
                            <td><?php echo esc_html($stat['answer']); ?></td>
                            <td><?php echo esc_html($stat['count']); ?></td>
                            <?php if ($group_mode === 'by_question') : ?>
                                <td><?php echo esc_html($stat['question_label']); ?></td>
                            <?php endif; ?>
                            <td>
                                <span class="unified-badge <?php echo $stat['is_unified'] ? 'unified-yes' : 'unified-no'; ?>">
                                    <?php echo $stat['is_unified'] ? 'Sim' : 'Não'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Paginação inferior -->
            <?php if ($total_pages > 1) : ?>
                <div class="pagination-wrapper">
                    <div class="pagination-info">
                        Exibindo <?php echo count($paged_ranking); ?> de <?php echo $total_items; ?> resultados 
                        (página <?php echo $page; ?> de <?php echo $total_pages; ?>)
                    </div>
                    <div class="pagination-controls">
                        <?php if ($page > 1) : ?>
                            <button class="button pagination-btn" data-page="<?php echo $page - 1; ?>">« Anterior</button>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++) :
                        ?>
                            <button class="button pagination-btn <?php echo $i === $page ? 'button-primary' : ''; ?>" 
                                    data-page="<?php echo $i; ?>"><?php echo $i; ?></button>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages) : ?>
                            <button class="button pagination-btn" data-page="<?php echo $page + 1; ?>">Próxima »</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php

    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_vs_get_all_ranking', 'vs_handle_get_all_ranking');
