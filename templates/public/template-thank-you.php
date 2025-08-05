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
?>

<div class="vs-obrigado p-6 bg-white rounded-lg shadow-md max-w-xl mx-auto">
    <h2 class="text-2xl font-bold mb-4 text-green-700">Obrigado por votar!</h2>
    <p class="mb-4">Suas respostas foram registradas com sucesso. Confira abaixo:</p>

    <!-- Inicia a tabela -->
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

                // Recupera a resposta pelo índice das respostas (array simples)
                $resposta = isset($respostas['respostas'][$index]) ? $respostas['respostas'][$index] : null;

                // Se a resposta for um array (múltiplas opções), transforma em string
                if (is_array($resposta)) {
                    $resposta = implode(', ', array_map('esc_html', $resposta));
                } else {
                    $resposta = esc_html($resposta);
                }

                // Se a resposta estiver vazia, mostrar uma mensagem padrão
                if (empty($resposta)) {
                    $resposta = '<em>Nenhuma resposta fornecida</em>';
                }
            ?>
            <tr>
                <td class='px-4 py-2 border-b'><?php echo esc_html($label); ?></td>
                <td class='px-4 py-2 border-b'><?php echo wp_kses_post($resposta); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="mt-6">
        <a href="<?php echo esc_url(home_url()); ?>" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Voltar para o início</a>
    </div>
</div>