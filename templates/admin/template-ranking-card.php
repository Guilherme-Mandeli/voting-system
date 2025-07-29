<?php
/**
 * Template para o card de ranking
 * 
 * @package VotingSystem
 * @subpackage AdminTemplates
 */

// Impede acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Inclui os partials
require_once __DIR__ . '/partials/ranking-filters.php';
require_once __DIR__ . '/partials/ranking-table.php';
require_once __DIR__ . '/partials/ranking-modal.php';

/**
 * Renderiza o card de ranking dinâmico
 */
function vs_render_ranking_card($votacao_id, $total_users) {
    $perguntas = vs_get_voting_questions($votacao_id);
    $question_filter = isset($_GET['question_filter']) ? sanitize_text_field($_GET['question_filter']) : 'all';
    $group_mode = isset($_GET['group_mode']) ? sanitize_text_field($_GET['group_mode']) : 'by_answer';
    
    // Valida o modo de agrupamento
    if (!in_array($group_mode, ['by_question', 'by_answer'])) {
        $group_mode = 'by_answer';
    }
    
    $ranking = vs_get_voting_ranking($votacao_id, $question_filter, $group_mode);
    $top_ranking = array_slice($ranking, 0, 5); // Limita a 5 resultados para exibição inicial
    
    // URL para exportação CSV
    $export_url = admin_url('admin-post.php') . '?' . http_build_query([
        'action' => 'export_csv_ranking',
        'votacao_id' => $votacao_id,
        'group_mode' => $group_mode,
        'question_filter' => $question_filter
    ]);
    ?>
    <div class="ranking-card">
        <div class="card-header">
            <div class="card-header-top">
                <div>
                    <p><strong>Votos totais:</strong> <?php echo esc_html($total_users); ?></p>
                    <h2>Mais votados</h2>
                </div>
                <div>
                    <a href="<?php echo esc_url($export_url); ?>" class="button button-secondary export-button">
                        <?php if ($group_mode === 'by_question') : ?>
                            Exportar por pergunta | CSV
                        <?php else : ?>
                            Exportar por resposta | CSV
                        <?php endif; ?>
                    </a>
                </div>
            </div>
            <p>Respostas mais recorrentes de:</p>

            <?php vs_render_ranking_filters($perguntas, $question_filter, $group_mode, 'card'); ?>
        </div>

        <div class="card-body">
            <?php if (empty($top_ranking)) : ?>
                <p><em>Nenhuma resposta encontrada para os filtros selecionados.</em></p>
            <?php else : ?>
                <?php vs_render_ranking_table($top_ranking, $group_mode); ?>

                <div class="show-all-container">
                    <a href="#" id="vs-show-all-results" class="button button-primary" 
                       data-votacao-id="<?php echo esc_attr($votacao_id); ?>"
                       data-question-filter="<?php echo esc_attr($question_filter); ?>"
                       data-group-mode="<?php echo esc_attr($group_mode); ?>">
                        Ver todos os votos (<?php echo count($ranking); ?>)
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php vs_render_ranking_modal($perguntas, $question_filter, $group_mode); ?>
    <?php
}
