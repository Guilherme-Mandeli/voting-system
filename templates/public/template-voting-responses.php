<?php
// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Template para exibição das respostas da votação
 * 
 * @param array $perguntas Array com as perguntas da votação
 * @param array $respostas Array com as respostas do usuário
 */
?>

<table class="w-full text-left border-collapse mb-4">
    <thead>
        <tr>
            <th class="border-b p-2">Pergunta</th>
            <th class="border-b p-2">Resposta</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($perguntas as $index => $pergunta):
            $label = esc_html($pergunta['label']);
            $resposta = isset($respostas['respostas'][$index]) ? $respostas['respostas'][$index] : '';

            if (is_array($resposta)) {
                $resposta = implode(', ', array_map('esc_html', $resposta));
            } else {
                $resposta = esc_html($resposta);
            }
        ?>
        <tr>
            <td class="border-b p-2"><?php echo $label; ?></td>
            <td class="border-b p-2"><?php echo $resposta; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>