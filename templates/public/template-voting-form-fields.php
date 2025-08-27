<?php
// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Template para os campos do formulário de votação
 * 
 * @param array $questions Array com as perguntas da votação
 * @param int $votacao_id ID da votação
 * @param array $respostas Array com as respostas do usuário (se existirem)
 */
?>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="vs-form border rounded-lg p-4 bg-white shadow-md">
    <input type="hidden" name="action" value="vs_enviar_voto">
    <input type="hidden" name="vs_votacao_id" value="<?php echo esc_attr($votacao_id); ?>">
    <input type="hidden" name="vs_votacao_nonce" value="<?php echo vs_create_nonce( VS_Nonce_Actions::FORM_VOTING ); ?>">

    <?php 
    foreach ($questions as $index => $question): 
    ?>
    <div class="vs-question-container" data-type="<?php echo esc_attr($question['tipo']); ?>">
        <label class="vs-question-label">
            <?php echo esc_html($question['label']); ?>
            <?php if ($question['obrigatoria']): ?>
                <span class="vs-required">*</span>
            <?php endif; ?>
        </label>
        
        <?php
        // Expandir tipos de campo suportados
        switch ($question['tipo']) {
            case 'text':
            case 'texto': // Compatibilidade com dados antigos
                vs_render_text_field($question, $index, $user_responses);
                break;
            case 'textarea':
                vs_render_textarea_field($question, $index, $user_responses);
                break;
            case 'number':
                vs_render_number_field($question, $index, $user_responses);
                break;
            case 'email':
                vs_render_email_field($question, $index, $user_responses);
                break;
            case 'date':
                vs_render_date_field($question, $index, $user_responses);
                break;
            case 'radio':
            case 'checkbox':
            case 'select':
                vs_render_choice_field($question, $index, $user_responses);
                break;
            case 'imported_vote':
                vs_render_imported_vote_field($question, $index, $user_responses);
                break;
            default:
                echo '<p class="vs-error">Tipo de campo não suportado: ' . esc_html($question['tipo']) . '</p>';
        }
        ?>
    </div>
<?php endforeach; ?>

    <button type="submit" class="vs-button vs-btn-primary text-white px-4 py-2 rounded hover:bg-blue-700 transition">Salvar Voto</button>
</form>
