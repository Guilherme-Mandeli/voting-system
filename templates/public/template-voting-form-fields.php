<?php
// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Template para os campos do formulário de votação
 * 
 * @param array $perguntas Array com as perguntas da votação
 * @param int $votacao_id ID da votação
 * @param array $respostas Array com as respostas do usuário (se existirem)
 */
?>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="vs-form border rounded-lg p-4 bg-white shadow-md">
    <input type="hidden" name="action" value="vs_enviar_voto">
    <input type="hidden" name="vs_votacao_id" value="<?php echo esc_attr($votacao_id); ?>">
    <input type="hidden" name="vs_votacao_nonce" value="<?php echo vs_create_nonce( VS_Nonce_Actions::FORM_VOTING ); ?>">

    <?php foreach ($perguntas as $index => $pergunta): ?>
        <?php
        $label = esc_html($pergunta['label']);
        $tipo  = esc_attr($pergunta['tipo']);
        $opcoes = $pergunta['opcoes'] ?? [];
        $obrigatoria = isset($pergunta['obrigatoria']) && $pergunta['obrigatoria'] ? true : false;

        // Preenchimento das respostas anteriores
        $valor_anterior = isset($respostas['respostas'][$index]) ? $respostas['respostas'][$index] : '';
        ?>

        <div class="mb-4">
            <label class="block font-semibold mb-2"><?php echo $label; ?></label>

            <?php switch ($tipo):
                case 'texto': 
                    $valor = esc_attr(is_array($valor_anterior) ? '' : $valor_anterior);
                    ?>
                    <input type="text" name="respostas[<?php echo $index; ?>]" value="<?php echo $valor; ?>" class="w-full border rounded p-2" <?php echo ($obrigatoria ? 'required' : ''); ?>>
                    <?php break;

                case 'select': ?>
                    <select name="respostas[<?php echo $index; ?>]" class="w-full border rounded p-2" <?php echo ($obrigatoria ? 'required' : ''); ?>>
                        <option value="">Selecionar...</option>
                        <?php foreach ($opcoes as $opcao): 
                            $selected = ($opcao === $valor_anterior) ? 'selected' : '';
                            ?>
                            <option value="<?php echo esc_attr($opcao); ?>" <?php echo $selected; ?>><?php echo esc_html($opcao); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php break;

                case 'radio': 
                    foreach ($opcoes as $i => $opcao):
                        $checked = ($opcao === $valor_anterior) ? 'checked' : '';
                        $required = ($obrigatoria && $i === 0) ? 'required' : '';
                        ?>
                        <label class="block"><input type="radio" name="respostas[<?php echo $index; ?>]" value="<?php echo esc_attr($opcao); ?>" <?php echo $checked; ?> <?php echo $required; ?>> <?php echo esc_html($opcao); ?></label>
                    <?php endforeach;
                    break;

                case 'checkbox': 
                    $valor_anterior = is_array($valor_anterior) ? $valor_anterior : [];
                    foreach ($opcoes as $opcao):
                        $checked = in_array($opcao, $valor_anterior) ? 'checked' : '';
                        ?>
                        <label class="block"><input type="checkbox" name="respostas[<?php echo $index; ?>][]" value="<?php echo esc_attr($opcao); ?>" <?php echo $checked; ?>> <?php echo esc_html($opcao); ?></label>
                    <?php endforeach;
                    if ($obrigatoria): ?>
                        <p class="text-sm text-gray-500 italic">Essa pergunta é obrigatória.</p>
                    <?php endif;
                    break;
            endswitch; ?>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="vs-button bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Salvar Voto</button>
</form>