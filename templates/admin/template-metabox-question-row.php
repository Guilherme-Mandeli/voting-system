<?php
/**
 * Template para una fila de pregunta en el metabox
 * 
 * Variables disponibles:
 * - $index: Índice de la pregunta
 * - $pergunta: Array con datos de la pregunta
 * 
 * @package VotingSystem\Templates\Admin
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="vs-pergunta">
    <label>Pergunta:</label><br>
    <input
        type="text"
        name="vs_perguntas[<?php echo esc_attr($index); ?>][label]"
        value="<?php echo esc_attr($pergunta['label'] ?? ''); ?>"
        style="width: 100%;"
        placeholder="Digite sua pergunta aqui..."
    ><br><br>

    <label>Tipo de Resposta:</label><br>
    <select
        name="vs_perguntas[<?php echo esc_attr($index); ?>][tipo]"
        class="vs-tipo-campo"
    >
        <?php
        $tipos = [
            'texto' => 'Texto Livre', 
            'select' => 'Select', 
            'radio' => 'Radio', 
            'checkbox' => 'Checkbox'
        ];
        foreach ($tipos as $val => $label) {
            $selected = (($pergunta['tipo'] ?? '') === $val) ? 'selected' : '';
            echo "<option value='{$val}' {$selected}>{$label}</option>";
        }
        ?>
    </select>

    <!-- Campo oculto para valor unificado -->
    <input
        type="hidden"
        name="vs_perguntas[<?php echo esc_attr($index); ?>][unificada]"
        value="<?php echo esc_attr($pergunta['unificada'] ?? ''); ?>"
    >

    <!-- Container de opções (para select, radio, checkbox) -->
    <div class="vs-opcoes-container" style="display: <?php echo in_array($pergunta['tipo'] ?? '', ['radio', 'checkbox', 'select']) ? 'block' : 'none'; ?>;">
        <label>Opções:</label><br>
        <div class="vs-opcoes">
            <?php
            $opcoes = $pergunta['opcoes'] ?? [''];
            foreach ($opcoes as $opcao_index => $opcao) {
                ?>
                <div class="vs-opcao-item" style="margin-bottom: 5px;">
                    <input
                        type="text"
                        name="vs_perguntas[<?php echo esc_attr($index); ?>][opcoes][]"
                        value="<?php echo esc_attr($opcao); ?>"
                        style="width: 90%;"
                        placeholder="Opção <?php echo ($opcao_index + 1); ?>"
                    >
                    <button type="button" class="button button-small vs-remove-opcao">Remover</button>
                </div>
                <?php
            }
            ?>
            <button type="button" class="button vs-add-opcao"
                data-pergunta-index="<?php echo esc_attr($index); ?>">
                Adicionar Opção
            </button>
        </div>
    </div>

    <br>
    <label style="display: block; margin-top: 10px;">
        <input
            type="checkbox"
            name="vs_perguntas[<?php echo esc_attr($index); ?>][obrigatoria]"
            value="1"
            <?php checked(($pergunta['obrigatoria'] ?? true), true); ?>
        >
        Pergunta Obrigatória
    </label>

    <button type="button" class="button button-secondary vs-remove-pergunta" style="margin-top:10px;">
        Remover Pergunta
    </button>
</div>