<?php
defined( 'ABSPATH' ) || exit;

/**
 * Renderiza o formulário de votação com perguntas dinâmicas.
 */
function vs_render_formulario_votacao($questions, $votacao_id, $respostas = []) {
    // Debug: Log das variáveis recebidas
    error_log('[DEBUG] vs_render_formulario_votacao - Votação ID: ' . $votacao_id);
    error_log('[DEBUG] vs_render_formulario_votacao - Questions count: ' . count($questions));
    error_log('[DEBUG] vs_render_formulario_votacao - Questions: ' . print_r($questions, true));
    error_log('[DEBUG] vs_render_formulario_votacao - Respostas: ' . print_r($respostas, true));
    
    // Garantir que $questions seja um array
    if (!is_array($questions)) {
        error_log('[DEBUG] vs_render_formulario_votacao - Questions não é array, convertendo');
        $questions = [];
    }
    
    // Verificar se há perguntas
    if (empty($questions)) {
        error_log('[DEBUG] vs_render_formulario_votacao - ERRO: Nenhuma pergunta encontrada!');
        return '<div class="vs-error">Nenhuma pergunta configurada para esta votação.</div>';
    }
    
    // Garantir que $respostas seja um array
    if (!is_array($respostas)) {
        $respostas = [];
    }
    
    // Disponibilizar variáveis para o template
    $user_responses = $respostas;
    
    // Debug: Verificar se o template existe
    $template_path = VS_PLUGIN_PATH . 'templates/public/template-voting-form-fields.php';
    error_log('[DEBUG] vs_render_formulario_votacao - Template path: ' . $template_path);
    error_log('[DEBUG] vs_render_formulario_votacao - Template exists: ' . (file_exists($template_path) ? 'YES' : 'NO'));
    
    ob_start();
    include $template_path;
    $output = ob_get_clean();
    
    error_log('[DEBUG] vs_render_formulario_votacao - Output length: ' . strlen($output));
    error_log('[DEBUG] vs_render_formulario_votacao - Output preview: ' . substr($output, 0, 200));
    
    return $output;
}

/**
 * Renderiza as respostas do usuário em modo leitura.
 */
function vs_render_respostas_votacao($questions, $respostas) {
    // Garantir que sejam arrays
    if (!is_array($questions)) {
        $questions = [];
    }
    if (!is_array($respostas)) {
        $respostas = [];
    }
    
    ob_start();
    include VS_PLUGIN_PATH . 'templates/public/template-voting-responses.php';
    return ob_get_clean();
}

/**
 * Renderiza campo de texto simples
 */
function vs_render_text_field($question, $index, $user_responses = []) {
    $field_name = "vs_respostas[{$index}]";
    $current_value = isset($user_responses[$index]) ? esc_attr($user_responses[$index]) : '';
    $required = !empty($question['obrigatoria']) ? 'required' : '';
    
    echo '<input type="text" name="' . esc_attr($field_name) . '" value="' . $current_value . '" class="vs-input-text" ' . $required . '>';
}

/**
 * Renderiza campo de textarea
 */
function vs_render_textarea_field($question, $index, $user_responses = []) {
    $field_name = "vs_respostas[{$index}]";
    $current_value = isset($user_responses[$index]) ? esc_textarea($user_responses[$index]) : '';
    $required = !empty($question['obrigatoria']) ? 'required' : '';
    
    echo '<textarea name="' . esc_attr($field_name) . '" class="vs-textarea" rows="4" ' . $required . '>' . $current_value . '</textarea>';
}

/**
 * Renderiza campo numérico
 */
function vs_render_number_field($question, $index, $user_responses = []) {
    $field_name = "vs_respostas[{$index}]";
    $current_value = isset($user_responses[$index]) ? esc_attr($user_responses[$index]) : '';
    $required = !empty($question['obrigatoria']) ? 'required' : '';
    
    echo '<input type="number" name="' . esc_attr($field_name) . '" value="' . $current_value . '" class="vs-input-number" ' . $required . '>';
}

/**
 * Renderiza campo de email
 */
function vs_render_email_field($question, $index, $user_responses = []) {
    $field_name = "vs_respostas[{$index}]";
    $current_value = isset($user_responses[$index]) ? esc_attr($user_responses[$index]) : '';
    $required = !empty($question['obrigatoria']) ? 'required' : '';
    
    echo '<input type="email" name="' . esc_attr($field_name) . '" value="' . $current_value . '" class="vs-input-email" ' . $required . '>';
}

/**
 * Renderiza campo de data
 */
function vs_render_date_field($question, $index, $user_responses = []) {
    $field_name = "vs_respostas[{$index}]";
    $current_value = isset($user_responses[$index]) ? esc_attr($user_responses[$index]) : '';
    $required = !empty($question['obrigatoria']) ? 'required' : '';
    
    echo '<input type="date" name="' . esc_attr($field_name) . '" value="' . $current_value . '" class="vs-input-date" ' . $required . '>';
}

/**
 * Renderiza campos de escolha (radio, checkbox, select)
 */
function vs_render_choice_field($question, $index, $user_responses = []) {
    $field_name = "vs_respostas[{$index}]";
    $current_value = isset($user_responses[$index]) ? $user_responses[$index] : '';
    $required = !empty($question['obrigatoria']) ? 'required' : '';
    $tipo = isset($question['tipo']) ? $question['tipo'] : 'text';
    
    // Debug: Log da estrutura da pergunta
    error_log('[DEBUG] vs_render_choice_field - Question structure: ' . print_r($question, true));
    
    // Combinar opções manuais e importadas
    $opcoes = [];
    
    // Adicionar opções manuais - CORRIGIDO: usar 'options' em vez de 'opcoes'
    if (!empty($question['options']) && is_array($question['options'])) {
        foreach ($question['options'] as $opcao_index => $opcao) {
            $valor_real = isset($question['valores_reais'][$opcao_index]) ? $question['valores_reais'][$opcao_index] : $opcao;
            $opcoes[] = [
                'label' => $opcao,
                'value' => $valor_real,
                'type' => 'manual'
            ];
        }
    }
    
    // Adicionar opções importadas
    if (!empty($question['imported_answers']) && is_array($question['imported_answers'])) {
        foreach ($question['imported_answers'] as $imported) {
            $opcoes[] = [
                'label' => isset($imported['label']) ? $imported['label'] : '',
                'value' => isset($imported['value']) ? $imported['value'] : '',
                'type' => 'imported'
            ];
        }
    }
    
    // Debug: Log das opções processadas
    error_log('[DEBUG] vs_render_choice_field - Processed options: ' . print_r($opcoes, true));
    
    if (empty($opcoes)) {
        error_log('[DEBUG] vs_render_choice_field - ERRO: Nenhuma opção disponível!');
        echo '<p class="vs-error">Nenhuma opção disponível para esta pergunta.</p>';
        return;
    }
    
    switch ($tipo) {
        case 'radio':
            error_log('[DEBUG] vs_render_choice_field - Renderizando radio buttons');
            foreach ($opcoes as $opcao_index => $opcao) {
                $checked = ($current_value == $opcao['value']) ? 'checked' : '';
                $css_class = $opcao['type'] === 'imported' ? 'vs-radio imported-question' : 'vs-radio';
                
                echo '<div class="' . esc_attr($css_class) . '">';
                echo '<input type="radio" name="' . esc_attr($field_name) . '" value="' . esc_attr($opcao['value']) . '" id="' . esc_attr($field_name . '_' . $opcao_index) . '" ' . $checked . ' ' . $required . '>';
                echo '<label for="' . esc_attr($field_name . '_' . $opcao_index) . '">' . esc_html($opcao['label']) . '</label>';
                echo '<input type="hidden" name="vs-valor-real" value="' . esc_attr($opcao['value']) . '">';
                echo '</div>';
            }
            break;
            
        case 'checkbox':
            $current_values = is_array($current_value) ? $current_value : explode(', ', (string)$current_value);
            foreach ($opcoes as $opcao_index => $opcao) {
                $checked = in_array($opcao['value'], $current_values) ? 'checked' : '';
                $css_class = $opcao['type'] === 'imported' ? 'vs-checkbox imported-question' : 'vs-checkbox';
                
                echo '<div class="' . esc_attr($css_class) . '">';
                echo '<input type="checkbox" name="' . esc_attr($field_name) . '[]" value="' . esc_attr($opcao['value']) . '" id="' . esc_attr($field_name . '_' . $opcao_index) . '" ' . $checked . '>';
                echo '<label for="' . esc_attr($field_name . '_' . $opcao_index) . '">' . esc_html($opcao['label']) . '</label>';
                echo '<input type="hidden" name="vs-valor-real" value="' . esc_attr($opcao['value']) . '">';
                echo '</div>';
            }
            break;
            
        case 'select':
            echo '<select name="' . esc_attr($field_name) . '" class="vs-select" ' . $required . '>';
            echo '<option value="">Selecione uma opção</option>';
            foreach ($opcoes as $opcao_index => $opcao) {
                $selected = ($current_value == $opcao['value']) ? 'selected' : '';
                $css_class = $opcao['type'] === 'imported' ? 'imported-question' : '';
                
                echo '<option value="' . esc_attr($opcao['value']) . '" class="' . esc_attr($css_class) . '" ' . $selected . '>' . esc_html($opcao['label']) . '</option>';
            }
            echo '</select>';
            break;
    }
}

/**
 * Renderiza campo de voto importado
 */
function vs_render_imported_vote_field($question, $index, $user_responses = []) {
    $field_name = "vs_respostas[{$index}]";
    $current_value = isset($user_responses[$index]) ? $user_responses[$index] : '';
    $required = !empty($question['obrigatoria']) ? 'required' : '';
    
    // Debug: Log da estrutura da pergunta
    error_log('[DEBUG] vs_render_imported_vote_field - Question structure: ' . print_r($question, true));
    
    // Usar APENAS opções manuais definidas em options[]
    $opcoes = [];
    
    if (!empty($question['options']) && is_array($question['options'])) {
        foreach ($question['options'] as $opcao_index => $opcao) {
            $valor_real = isset($question['valores_reais'][$opcao_index]) ? $question['valores_reais'][$opcao_index] : $opcao;
            $opcoes[] = [
                'label' => $opcao,
                'value' => $valor_real
            ];
        }
    }
    
    // Debug: Log das opções processadas
    error_log('[DEBUG] vs_render_imported_vote_field - Manual options only: ' . print_r($opcoes, true));
    
    if (empty($opcoes)) {
        error_log('[DEBUG] vs_render_imported_vote_field - ERRO: Nenhuma opção manual disponível!');
        echo '<p class="vs-error">Nenhuma opção manual encontrada para esta pergunta importada.</p>';
        return;
    }
    
    echo '<select name="' . esc_attr($field_name) . '" class="vs-select vs-imported-vote" ' . $required . '>';
    echo '<option value="">Selecione uma opção</option>';
    
    foreach ($opcoes as $data) {
        $selected = ($current_value == $data['value']) ? 'selected' : '';
        echo '<option value="' . esc_attr($data['value']) . '" ' . $selected . '>' . esc_html($data['label']) . '</option>';
    }
    
    echo '</select>';
}
