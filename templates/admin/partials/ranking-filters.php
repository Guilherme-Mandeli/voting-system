<?php
/**
 * Partial: Filtros do ranking
 * 
 * @package VotingSystem
 * @subpackage AdminTemplates
 */

// Impede acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza os filtros do ranking
 * 
 * @param array $perguntas Lista de perguntas
 * @param string $question_filter Filtro atual de pergunta
 * @param string $group_mode Modo de agrupamento atual
 * @param string $context Contexto dos filtros ('card' ou 'modal')
 */
function vs_render_ranking_filters($perguntas, $question_filter, $group_mode, $context = 'card') {
    $prefix = $context === 'modal' ? 'modal_' : '';
    $class_prefix = $context === 'modal' ? 'modal-' : '';
    ?>
    <div class="<?php echo $class_prefix; ?>filters-container">
        <div>
            <label for="<?php echo $prefix; ?>question_filter" class="<?php echo $context === 'modal' ? 'modal-filter-label' : 'screen-reader-text'; ?>">
                <?php echo $context === 'modal' ? 'Pergunta:' : 'Filtrar por pergunta'; ?>
            </label>
            <select id="<?php echo $prefix; ?>question_filter" name="<?php echo $prefix; ?>question_filter" class="<?php echo $class_prefix; ?>question-filter">
                <option value="all" <?php selected($question_filter, 'all'); ?>>Todas as perguntas</option>
                <?php foreach ($perguntas as $index => $pergunta) : ?>
                    <option value="q<?php echo $index; ?>" <?php selected($question_filter, "q{$index}"); ?>>
                        <?php echo esc_html($pergunta['label'] ?? "Pergunta #" . ($index + 1)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($question_filter === 'all' || $context === 'modal') : ?>
            <div id="<?php echo $prefix; ?>group-mode-container" <?php echo $context === 'modal' ? '' : 'style="display: ' . ($question_filter === 'all' ? 'block' : 'none') . ';"'; ?>>
                <fieldset>
                    <legend class="<?php echo $class_prefix; ?>group-<?php echo $context === 'modal' ? 'legend' : 'mode-legend'; ?>">
                        <?php echo $context === 'modal' ? 'Agrupamento:' : 'Modo de agrupamento:'; ?>
                    </legend>
                    <div class="<?php echo $class_prefix; ?>group-<?php echo $context === 'modal' ? 'options' : 'mode-options'; ?>">
                        <label class="<?php echo $class_prefix; ?>group-<?php echo $context === 'modal' ? 'label' : 'mode-label'; ?>">
                            <input type="radio" name="<?php echo $prefix; ?>group_mode" value="by_answer" <?php checked($group_mode, 'by_answer'); ?>>
                            Por resposta
                        </label>
                        <label class="<?php echo $class_prefix; ?>group-<?php echo $context === 'modal' ? 'label' : 'mode-label'; ?>">
                            <input type="radio" name="<?php echo $prefix; ?>group_mode" value="by_question" <?php checked($group_mode, 'by_question'); ?>>
                            Por pergunta
                        </label>
                    </div>
                </fieldset>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($question_filter === 'all' && $context === 'card') : ?>
        <div class="filter-description">
            <strong>Por resposta:</strong> considera respostas iguais independentemente da pergunta.<br>
            <strong>Por pergunta:</strong> considera respostas iguais apenas dentro da mesma pergunta.
        </div>
    <?php endif; ?>
    <?php
}