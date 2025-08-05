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
 * @param array $questions Lista de perguntas
 * @param string $question_filter Filtro atual de pergunta
 * @param string $group_mode Modo de agrupamento atual
 * @param string $context Contexto dos filtros ('card' ou 'modal')
 */
function vs_render_ranking_filters($questions, $question_filter, $group_mode, $context = 'card') {
    $prefix = $context === 'modal' ? 'modal_' : '';
    $class_prefix = $context === 'modal' ? 'modal-' : '';
    ?>
    <div class="<?php echo $class_prefix; ?>filters-container">
        <div>
            <label for="<?php echo $prefix; ?>question_filter" class="<?php echo $class_prefix; ?>filter-label">
                <?php echo $context === 'modal' ? 'Pergunta:' : 'Pergunta:'; ?>
            </label>
            <select id="<?php echo $prefix; ?>question_filter" name="<?php echo $prefix; ?>question_filter" class="<?php echo $class_prefix; ?>question-filter">
                <option value="all" <?php selected($question_filter, 'all'); ?>>Todas as perguntas</option>
                <?php foreach ($questions as $index => $question) : ?>
                    <option value="q<?php echo $index; ?>" <?php selected($question_filter, "q{$index}"); ?>>
                        <?php echo esc_html($question['label'] ?? "Pergunta #" . ($index + 1)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Container dos radio buttons sempre renderizado -->
        <div id="<?php echo $prefix; ?>group-mode-container" style="display: <?php echo $question_filter === 'all' ? 'block' : 'none'; ?>;">
            <fieldset>
                <legend class="<?php echo $class_prefix; ?>group-legend">
                    Agrupamento:
                </legend>
                <div class="<?php echo $class_prefix; ?>group-options">
                    <label class="<?php echo $class_prefix; ?>group-label">
                        <input type="radio" name="<?php echo $prefix; ?>group_mode" value="by_answer" <?php checked($group_mode, 'by_answer'); ?>>
                        Por resposta
                    </label>
                    <label class="<?php echo $class_prefix; ?>group-label">
                        <input type="radio" name="<?php echo $prefix; ?>group_mode" value="by_question" <?php checked($group_mode, 'by_question'); ?>>
                        Por pergunta
                    </label>
                </div>
            </fieldset>
        </div>

        
    </div>

    <?php if ($context === 'card' && $question_filter === 'all') : ?>
        <div id="filter-description" class="filter-description">
            <!-- Descrição será atualizada via JavaScript -->
        </div>
    <?php endif; ?>
    <?php
}