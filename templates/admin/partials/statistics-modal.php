<?php
/**
 * Template parcial para o modal de estatísticas
 * 
 * @package VotingSystem
 * @subpackage AdminTemplates
 */

// Impede acesso direto
if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="vs-statistics-modal" class="vs-modal" style="display: none;">
    <div class="vs-modal-content">
        <div class="vs-modal-header">
            <h2>Todas as Estatísticas - Votação #<?php echo esc_html($votacao_id); ?></h2>
            <span class="vs-modal-close">&times;</span>
        </div>
        
        <div class="vs-modal-body">
            <div class="modal-filters">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="modal-question-filter">Filtrar por pergunta:</label>
                        <select id="modal-question-filter">
                            <option value="all">Todas as perguntas</option>
                            <?php foreach ($perguntas as $pergunta) : ?>
                                <option value="<?php echo esc_attr($pergunta['id']); ?>">
                                    <?php echo esc_html($pergunta['pergunta']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="modal-group-mode">Agrupar por:</label>
                        <select id="modal-group-mode">
                            <option value="by_answer">Por resposta</option>
                            <option value="by_question">Por pergunta</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="button" class="button button-primary" id="apply-modal-filters">
                            Aplicar Filtros
                        </button>
                    </div>
                </div>
                
                <div class="export-row">
                    <a href="#" id="modal-export-link" class="button button-secondary">
                        Exportar Resultados Filtrados
                    </a>
                </div>
            </div>
            
            <div id="modal-statistics-content">
                <div class="loading-spinner">
                    <p>Carregando estatísticas...</p>
                </div>
            </div>
        </div>
        
        <div class="vs-modal-footer">
            <button type="button" class="button button-secondary vs-modal-close">Fechar</button>
        </div>
    </div>
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // Dados para o JavaScript
    window.vsStatisticsData = {
        votacaoId: <?php echo json_encode($votacao_id); ?>,
        ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
        nonce: '<?php echo wp_create_nonce('vs_statistics_nonce'); ?>',
        adminUrl: '<?php echo admin_url(); ?>'
    };
});
</script>