<?php
/**
 * Partial: Modal de ranking completo
 * 
 * @package VotingSystem
 * @subpackage AdminTemplates
 */

// Impede acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza o modal de ranking completo
 * 
 * @param array $perguntas Lista de perguntas
 * @param string $question_filter Filtro atual de pergunta
 * @param string $group_mode Modo de agrupamento atual
 */
function vs_render_ranking_modal($perguntas, $question_filter, $group_mode) {
    ?>
    <!-- Modal para exibir todo o ranking -->
    <div id="vs-all-ranking-modal" class="vs-modal" style="display: none;">
        <div class="vs-modal-content">
            <div class="vs-modal-header">
                <h2>Mais votados</h2>
                <span class="vs-modal-close">&times;</span>
            </div>
            
            <!-- Seção de filtros do modal -->
            <div class="vs-modal-filters">
                <div class="modal-filters-container">
                    <?php vs_render_ranking_filters($perguntas, $question_filter, $group_mode, 'modal'); ?>

                    <div>
                        <a href="#" id="modal-export-csv" class="button button-secondary modal-export-button">
                            <span id="modal-export-text">Exportar | CSV</span>
                        </a>
                    </div>
                </div>
                
                <div id="modal-filter-description" class="modal-filter-description">
                    <!-- Descrição será atualizada via JavaScript -->
                </div>
            </div>
            
            <div class="vs-modal-body" id="vs-modal-ranking-content">
                <!-- Conteúdo será carregado via AJAX -->
                <div class="loading">Carregando...</div>
            </div>
        </div>
    </div>
    <?php
}