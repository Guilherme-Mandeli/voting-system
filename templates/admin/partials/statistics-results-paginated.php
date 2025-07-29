<?php
/**
 * Template parcial para resultados paginados de estatísticas
 * 
 * @package VotingSystem
 * @subpackage AdminTemplates
 */

// Impede acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Variáveis disponíveis: $pagination_data, $perguntas, $group_mode
$statistics = $pagination_data['data'];
$current_page = $pagination_data['current_page'];
$total_pages = $pagination_data['total_pages'];
$total_items = $pagination_data['total_items'];
?>

<div class="statistics-results">
    <div class="results-header">
        <p>
            Exibindo <?php echo count($statistics); ?> de <?php echo $total_items; ?> resultados 
            (Página <?php echo $current_page; ?> de <?php echo $total_pages; ?>)
        </p>
    </div>
    
    <?php if (!empty($statistics)) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <?php if ($group_mode === 'by_question') : ?>
                        <th>Pergunta</th>
                        <th>Total de Respostas</th>
                        <th>Respostas Únicas</th>
                    <?php else : ?>
                        <th>Pergunta</th>
                        <th>Resposta</th>
                        <th>Votos</th>
                        <th>Percentual</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statistics as $stat) : ?>
                    <tr>
                        <?php if ($group_mode === 'by_question') : ?>
                            <td><?php echo esc_html($stat['pergunta']); ?></td>
                            <td><?php echo esc_html($stat['total_respostas']); ?></td>
                            <td><?php echo esc_html($stat['respostas_unicas'] ?? 'N/A'); ?></td>
                        <?php else : ?>
                            <td><?php echo esc_html($stat['pergunta']); ?></td>
                            <td>
                                <?php echo esc_html($stat['resposta']); ?>
                                <?php if (isset($stat['is_unified']) && $stat['is_unified']) : ?>
                                    <span class="unified-badge">Unificada</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($stat['votos']); ?></td>
                            <td><?php echo esc_html($stat['percentual']); ?>%</td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1) : ?>
            <div class="pagination-wrapper">
                <div class="pagination-info">
                    Página <?php echo $current_page; ?> de <?php echo $total_pages; ?>
                </div>
                <div class="pagination-controls">
                    <?php if ($current_page > 1) : ?>
                        <button type="button" class="button" data-page="<?php echo $current_page - 1; ?>">
                            « Anterior
                        </button>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++) :
                    ?>
                        <button type="button" class="button <?php echo ($i === $current_page) ? 'button-primary' : ''; ?>" data-page="<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </button>
                    <?php endfor; ?>
                    
                    <?php if ($current_page < $total_pages) : ?>
                        <button type="button" class="button" data-page="<?php echo $current_page + 1; ?>">
                            Próxima »
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php else : ?>
        <div class="no-results">
            <p>Nenhum resultado encontrado para os filtros selecionados.</p>
        </div>
    <?php endif; ?>
</div>