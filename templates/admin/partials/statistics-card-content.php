<?php
/**
 * Template parcial para o conteúdo do card de estatísticas
 * 
 * @package VotingSystem
 * @subpackage AdminTemplates
 */

// Impede acesso direto
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="statistics-card">
    <div class="card-header">
        <h2>Estatísticas da Votação</h2>
        <div class="card-actions">
            <button type="button" class="button button-secondary" id="open-statistics-modal">
                Ver Todas as Estatísticas
            </button>
            <a href="<?php echo esc_url($export_url); ?>" class="button button-primary">
                Exportar CSV
            </a>
        </div>
    </div>
    
    <div class="card-body">
        <div class="statistics-summary">
            <div class="stat-item">
                <span class="stat-label">Total de Participantes:</span>
                <span class="stat-value"><?php echo esc_html($total_users); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total de Perguntas:</span>
                <span class="stat-value"><?php echo esc_html(count($perguntas)); ?></span>
            </div>
        </div>
        
        <div class="statistics-filters">
            <div class="filter-group">
                <label for="question-filter">Filtrar por pergunta:</label>
                <select id="question-filter">
                    <option value="all" <?php selected($question_filter, 'all'); ?>>Todas as perguntas</option>
                    <?php foreach ($perguntas as $pergunta) : ?>
                        <option value="<?php echo esc_attr($pergunta['id']); ?>" <?php selected($question_filter, $pergunta['id']); ?>>
                            <?php echo esc_html($pergunta['pergunta']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="group-mode">Agrupar por:</label>
                <select id="group-mode">
                    <option value="by_answer" <?php selected($group_mode, 'by_answer'); ?>>Por resposta</option>
                    <option value="by_question" <?php selected($group_mode, 'by_question'); ?>>Por pergunta</option>
                </select>
            </div>
        </div>
        
        <?php if (!empty($top_statistics)) : ?>
            <div class="statistics-preview">
                <h3>Principais Resultados (Top 5)</h3>
                <table class="vote-results-table">
                    <thead>
                        <tr>
                            <?php if ($group_mode === 'by_question') : ?>
                                <th>Pergunta</th>
                                <th>Total de Respostas</th>
                            <?php else : ?>
                                <th>Pergunta</th>
                                <th>Resposta</th>
                                <th>Votos</th>
                                <th>Percentual</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_statistics as $stat) : ?>
                            <tr>
                                <?php if ($group_mode === 'by_question') : ?>
                                    <td><?php echo esc_html($stat['pergunta']); ?></td>
                                    <td><?php echo esc_html($stat['total_respostas']); ?></td>
                                <?php else : ?>
                                    <td><?php echo esc_html($stat['pergunta']); ?></td>
                                    <td><?php echo esc_html($stat['resposta']); ?></td>
                                    <td><?php echo esc_html($stat['votos']); ?></td>
                                    <td><?php echo esc_html($stat['percentual']); ?>%</td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <div class="no-statistics">
                <p>Nenhuma estatística disponível para os filtros selecionados.</p>
            </div>
        <?php endif; ?>
    </div>
</div>