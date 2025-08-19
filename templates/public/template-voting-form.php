<?php
// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Template para o formulário de votação
 * 
 * @param array $questions Array com as perguntas da votação
 * @param int $votacao_id ID da votação
 * @param array $respostas Array com as respostas do usuário (se existirem)
 * @param bool $ja_votou Se o usuário já votou
 * @param bool $permitir_edicao Se a votação permite edição
 * @param string $encerrada Status da votação
 */

// Debug: Log das variáveis do template
error_log('[DEBUG] template-voting-form - Votacao ID: ' . $votacao_id);
error_log('[DEBUG] template-voting-form - Ja votou: ' . ($ja_votou ? 'SIM' : 'NAO'));
error_log('[DEBUG] template-voting-form - Permitir edicao: ' . ($permitir_edicao ?? 'NAO_DEFINIDO'));
error_log('[DEBUG] template-voting-form - Questions count: ' . count($questions));
?>

<div class="vs-voting-form-container">
    <?php if ($ja_votou && $permitir_edicao !== '1'): ?>
        <div class="vs-voting-completed">
            <h3>Você já participou desta votação</h3>
            <div class="vs-responses-readonly">
                <?php vs_render_respostas_votacao($votacao_id, $questions, $respostas); ?>
            </div>
        </div>
    <?php else: ?>
        <div class="vs-voting-form-active">
            <?php if ($ja_votou && $permitir_edicao === '1'): ?>
                <div class="vs-edit-notice">
                    <p><strong>Você já votou.</strong> Você pode editar suas respostas até <?php echo esc_html($data_fim_formatted ?? 'o encerramento'); ?>.</p>
                    <button type="button" class="vs-toggle-edit-mode">Editar Respostas</button>
                </div>
                
                <div class="vs-current-responses" style="display: block;">
                    <?php vs_render_respostas_votacao($votacao_id, $questions, $respostas); ?>
                </div>
            <?php endif; ?>
            
            <div class="vs-form-container">
                <?php 
                // Debug: Log antes de renderizar o formulário
                error_log('[DEBUG] template-voting-form - Renderizando formulário');
                echo vs_render_formulario_votacao($questions, $votacao_id, $respostas); 
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
(function($) {
    $('.vs-toggle-edit-mode').on('click', function() {
        $('.vs-current-responses').toggle();
        $('.vs-form-container').toggle();
        $(this).text($(this).text() === 'Editar Respostas' ? 'Cancelar Edição' : 'Editar Respostas');
    });
    
    // Validação em tempo real
    $('.vs-form-container form').on('submit', function(e) {
        var hasErrors = false;
        $('.vs-required').each(function() {
            var $field = $(this).closest('.vs-question-container').find('input, select, textarea');
            if (!$field.val()) {
                hasErrors = true;
                $field.addClass('vs-error');
            } else {
                $field.removeClass('vs-error');
            }
        });
        
        if (hasErrors) {
            e.preventDefault();
            alert('Por favor, preencha todos os campos obrigatórios.');
        }
    });
})(jQuery);
</script>
