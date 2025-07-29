<?php
/**
 * Partial: Tabela de ranking
 * 
 * @package VotingSystem
 * @subpackage AdminTemplates
 */

// Impede acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza a tabela de ranking
 * 
 * @param array $ranking_data Dados do ranking
 * @param string $group_mode Modo de agrupamento
 * @param bool $show_all Se deve mostrar todos os resultados ou apenas o top 5
 */
function vs_render_ranking_table($ranking_data, $group_mode, $show_all = false) {
    if (empty($ranking_data)) {
        echo '<p><em>Nenhuma resposta encontrada para os filtros selecionados.</em></p>';
        return;
    }
    ?>
    <table class="vote-results-table">
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
            <?php foreach ($ranking_data as $stat) : ?>
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
    <?php
}