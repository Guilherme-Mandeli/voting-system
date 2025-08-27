<?php
// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Template para a página de agradecimento após votação
 * 
 * @param array $questions Array com as perguntas da votação
 * @param array $respostas Array com as respostas do usuário
 * @param int $votacao_id ID da votação
 */

// Debug: Log das variáveis recebidas
error_log('[DEBUG] template-thank-you.php - Questions: ' . print_r($questions, true));
error_log('[DEBUG] template-thank-you.php - Respostas: ' . print_r($respostas, true));
error_log('[DEBUG] template-thank-you.php - Votacao ID: ' . $votacao_id);
?>

<div class="vs-obrigado p-6 bg-white rounded-lg shadow-md max-w-xl mx-auto">
    <h2 class="text-2xl font-bold mb-4 text-green-700">Obrigado por votar!</h2>
    <p class="mb-4">Suas respostas foram registradas com sucesso. Confira abaixo:</p>

    <!-- Inicia a tabela -->
    <div class="table-container">
        <table class="table-auto w-full border-collapse border border-gray-300">
            <thead>
                <tr>
                    <th class="px-4 py-2 border-b bg-gray-100 text-left">Pergunta</th>
                    <th class="px-4 py-2 border-b bg-gray-100 text-left">Sua Resposta</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questions as $index => $question): 
                    $label = isset($question['label']) ? esc_html($question['label']) : 'Pergunta #' . ($index + 1);
                    $tipo = isset($question['tipo']) ? $question['tipo'] : 'texto';
    
                    // Recupera a resposta pelo índice das respostas
                    $resposta_raw = isset($respostas['respostas'][$index]) ? $respostas['respostas'][$index] : null;
                    
                    // Debug: Log de cada resposta
                    error_log('[DEBUG] template-thank-you.php - Question ' . $index . ' (' . $tipo . '): ' . print_r($resposta_raw, true));
                    
                    // Processa a resposta baseada no tipo de campo
                    $resposta_processada = vs_process_answer_for_display($resposta_raw, $question, $index);
                    
                    // Debug: Log da resposta processada
                    error_log('[DEBUG] template-thank-you.php - Processed answer ' . $index . ': ' . $resposta_processada);
                    
                    // Se a resposta estiver vazia, mostrar uma mensagem padrão
                    if (empty($resposta_processada)) {
                        $resposta_processada = '<em>Nenhuma resposta fornecida</em>';
                    }
                ?>
                <tr>
                    <td class='px-4 py-2 border-b'><?php echo esc_html($label); ?></td>
                    <td class='px-4 py-2 border-b'><?php echo wp_kses_post($resposta_processada); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        <a href="<?php echo esc_url(home_url()); ?>" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Voltar para o início</a>
    </div>
</div>

<?php
/**
 * Processa a resposta para exibição baseada no tipo de campo
 */
function vs_process_answer_for_display($resposta_raw, $question, $index) {
    $tipo = isset($question['tipo']) ? $question['tipo'] : 'texto';
    
    // Se a resposta for um array (múltiplas opções), transforma em string
    if (is_array($resposta_raw)) {
        return implode(', ', array_map('esc_html', $resposta_raw));
    }
    
    // Para campos radio e imported_vote, verificar se precisa converter o valor
    if (in_array($tipo, ['radio', 'imported_vote'])) {
        return vs_convert_answer_value_to_label($resposta_raw, $question);
    }
    
    // Para outros tipos, apenas sanitizar
    return esc_html($resposta_raw);
}

/**
 * Converte o valor da resposta para o label correspondente
 */
function vs_convert_answer_value_to_label($valor, $question) {
    // Se não há opções definidas, retorna o valor original
    if (!isset($question['options']) || !is_array($question['options'])) {
        return esc_html($valor);
    }
    
    $options = $question['options'];
    $valores_reais = isset($question['valores_reais']) ? $question['valores_reais'] : [];
    
    // Procura o valor nas opções
    foreach ($options as $option_index => $option_label) {
        $option_value = isset($valores_reais[$option_index]) ? $valores_reais[$option_index] : $option_label;
        
        if ($option_value == $valor) {
            return esc_html($option_label);
        }
    }
    
    // Se não encontrou correspondência, retorna o valor original
    return esc_html($valor);
}
?>