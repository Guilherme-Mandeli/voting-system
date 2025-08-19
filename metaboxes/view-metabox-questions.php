<?php
/**
 * Vista del metabox de preguntas de votación
 * 
 * @package VotingSystem\Metaboxes\Views
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renderiza el contenido del metabox de preguntas
 *
 * @param WP_Post $post Objeto del post actual
 */
function vs_render_metabox_questions_view($post) {
    $questions = get_post_meta($post->ID, 'vs_questions', true);

    if (!empty($questions) && is_array($questions)) {
        foreach ($questions as &$question) {
            // Garantir que imported_answers seja um JSON válido
            if (!empty($question['imported_answers'])) {
                // Decodifica o JSON para verificar se é válido
                $json_decoded = json_decode($question['imported_answers'], true);
                if ($json_decoded !== null) {
                    // Re-codifica para garantir um JSON válido e consistente
                    $question['imported_answers'] = wp_json_encode($json_decoded);
                } else {
                    // Se o JSON não for válido, inicializa com um objeto vazio
                    $question['imported_answers'] = wp_json_encode([
                        'perguntas' => []
                    ]);
                }
            } else {
                // Se não houver respostas importadas, inicializa com um objeto vazio
                $question['imported_answers'] = wp_json_encode([
                    'perguntas' => []
                ]);
            }
        }
        unset($question); // Sem esta linha, problemas acontecem :D - Mandeli, depois de tropezentas horas debugando.
    }
    wp_nonce_field('vs_salvar_perguntas', 'vs_nonce_questions');

    // Obtem o valor guardado para permitir a edição
    $permitir_edicao = get_post_meta($post->ID, 'vs_permitir_edicao', true);

    ?>
    <div class="vs-metabox-questions">
        <!-- Opção para permitir editar voto -->
        <div class="vs-edit-permission-section">
            <p style="font-size: small; color: #555555; margin-bottom: 4px;">
                <label>
                    <input type="checkbox" name="vs_permitir_edicao" value="1" <?php checked($permitir_edicao, '1'); ?>>
                    Permitir editar voto
                </label>  
            </p>
            <p style="margin-top: 4px; padding: 6px 6px 8px 12px; border-left: 1px solid #ccc; background: #f6f7f7;">
                Habilitar esta opção permitirá que os usuários editem as respostas após a primeira submissão.
            </p>
        </div>

        <hr style="margin: 20px 0; border: none; border-top: 1px solid #ccc;" />

        <!-- Container de perguntas -->
        <div id="vs-perguntas-wrapper">
            <?php if (!empty($questions) && is_array($questions)) : ?>
                <?php foreach ($questions as $index => $question) : ?>
                    <?php vs_render_question_row_template($index, $question); ?>
                <?php endforeach; ?>
            <?php else : ?>
                <?php vs_render_question_row_template(0, ['obrigatoria' => true]); ?>
            <?php endif; ?>
        </div>

        <!-- Botão para adicionar pergunta -->
        <button type="button" class="button" id="vs-add-pergunta">
            Adicionar Pergunta
        </button>
    </div>

    <?php vs_render_metabox_questions_scripts($post); ?>
    <?php vs_render_metabox_questions_styles(); ?>
    <?php
}

/**
 * Renderiza una fila de pregunta usando el template
 *
 * @param int $index Índice de la pregunta
 * @param array $question Datos de la pregunta
 */
function vs_render_question_row_template($index, $question = []) {
    include VS_PLUGIN_PATH . 'templates/admin/template-metabox-question-row.php';
}

/**
 * Renderiza los scripts JavaScript para el metabox
 *
 * @param WP_Post $post Objeto del post actual
 */
function vs_render_metabox_questions_scripts($post) {
    $last_index = 0;
    $questions = get_post_meta($post->ID, 'vs_questions', true);
    if (!empty($questions) && is_array($questions)) {
        $last_index = count($questions) - 1;
    }
    ?>
    <script>
        (function($) {
            const wrapper = document.getElementById('vs-perguntas-wrapper');
            const addBtn = document.getElementById('vs-add-pergunta');
            
            // Função para calcular o próximo índice disponível dinamicamente
            function getNextAvailableIndex() {
                const existingQuestions = wrapper.querySelectorAll('.vs-pergunta');
                const usedIndices = [];
                
                // Extrair todos os índices já em uso
                existingQuestions.forEach(question => {
                    const labelInput = question.querySelector('input[name*="[label]"]');
                    if (labelInput) {
                        const match = labelInput.name.match(/vs_questions\[(\d+)\]\[label\]/);
                        if (match) {
                            usedIndices.push(parseInt(match[1]));
                        }
                    }
                });
                
                // Encontrar o menor índice disponível
                let nextIndex = 0;
                while (usedIndices.includes(nextIndex)) {
                    nextIndex++;
                }
                
                return nextIndex;
            }

            // Agregar nueva pregunta
            addBtn.addEventListener('click', function () {
                const nextIndex = getNextAvailableIndex();
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=vs_get_pergunta_template&index=' + nextIndex)
                    .then(res => res.text())
                    .then(html => {
                        wrapper.insertAdjacentHTML('beforeend', html);
                    })
                    .catch(error => {
                        console.error('Error al cargar template de pregunta:', error);
                    });
            });

            // Mostrar/ocultar opciones según tipo de campo
            document.addEventListener('change', function (e) {
                if (e.target && e.target.classList.contains('vs-tipo-campo')) {
                    const container = e.target.closest('.vs-pergunta');
                    const optionsDiv = container.querySelector('.vs-options-container');
                    if (['radio', 'checkbox', 'select'].includes(e.target.value)) {
                        optionsDiv.style.display = 'block';
                    } else {
                        optionsDiv.style.display = 'none';
                    }
                }
            });

            // Event delegation para botones dinámicos
            document.addEventListener('click', function (e) {
                // Remover pregunta
                if (e.target && e.target.classList.contains('vs-remove-pergunta')) {
                    const bloque = e.target.closest('.vs-pergunta');
                    if (confirm('¿Está seguro de que desea eliminar esta pregunta?')) {
                        bloque.remove();
                    }
                }

                // Remover opción
                // if (e.target && e.target.classList.contains('vs-remove-option')) {
                //     const option = e.target.closest('.vs-option-item');
                //     const pergunta = option.closest('.vs-pergunta');
                //     const container = pergunta.querySelector('.vs-columns-container');
                    
                //     // Obter o valor da opção que está sendo removida para debug
                //     const removedValue = $(option).find('.vs-valor-real').val();
                //     console.log('Removendo opção com valor:', removedValue);
                    
                //     // Contar quantas opções existem com este valor ANTES da remoção
                //     const countBeforeRemoval = $(pergunta).find('.vs-valor-real').filter(function() {
                //         return $(this).val() === removedValue;
                //     }).length;
                //     console.log('Quantidade de opções com este valor antes da remoção:', countBeforeRemoval);
                    
                //     // Remover a opção
                //     option.remove();
                    
                //     // Contar quantas opções existem com este valor APÓS a remoção
                //     const countAfterRemoval = $(pergunta).find('.vs-valor-real').filter(function() {
                //         return $(this).val() === removedValue;
                //     }).length;
                //     console.log('Quantidade de opções com este valor após a remoção:', countAfterRemoval);
                    
                //     // Atualizar checkboxes se existir container de colunas (tipo imported_vote)
                //     if (container) {
                //         const $pergunta = $(pergunta);
                //         const $container = $(container);
                        
                //         // Obter todos os valores reais das opções existentes (usando Set para evitar duplicatas)
                //         const existingValuesSet = new Set();
                //         $pergunta.find('.vs-valor-real').each(function() {
                //             const value = $(this).val();
                //             if (value && value.trim() !== '') {
                //                 existingValuesSet.add(value.trim());
                //             }
                //         });
                        
                //         const existingValues = Array.from(existingValuesSet);
                //         console.log('Valores únicos existentes após remoção:', existingValues);
                        
                //         // Verificar todos os checkboxes da tabela e marcar os que correspondem às opções existentes
                //         $container.find('.vs-select-answer').each(function() {
                //             const $checkbox = $(this);
                //             const valorResposta = $checkbox.data('valor') || '';
                //             const valorUnificado = $checkbox.data('valor-unificado') || '';
                //             const isExistingOption = existingValues.includes(valorResposta.trim()) || 
                //                                    existingValues.includes(valorUnificado.trim());
                            
                //             console.log('Checkbox - data-valor:', valorResposta, 'data-valor-unificado:', valorUnificado, 'Existe:', isExistingOption);
                            
                //             $checkbox.prop('checked', isExistingOption);
                //         });
                //     }
                // }

                // Agregar opción
                if (e.target && e.target.classList.contains('vs-add-option')) {
                    const perguntaIndexLocal = e.target.getAttribute('data-question-index');
                    const optionsContainer = e.target.closest('.vs-options');
                    const optionCount = optionsContainer.querySelectorAll('.vs-option-item').length;
                    const tipoCampo = e.target.closest('.vs-pergunta').querySelector('.vs-tipo-campo').value;
                    
                    let newoptionHTML = `
                        <div class='vs-option-item' style='margin-bottom: 5px;'>
                            <input type='text' 
                                   name='vs_questions[${perguntaIndexLocal}][options][]' 
                                   style='width: 90%;'
                                   placeholder='Opción ${optionCount + 1}'>`;

                    // Adiciona o input hidden apenas se for do tipo imported_vote
                    if (tipoCampo === 'imported_vote') {
                        newoptionHTML += `
                            <input type='hidden' 
                                   name='vs_questions[${perguntaIndexLocal}][valores_reais][]' 
                                   value='' 
                                   class='vs-valor-real'>`;
                    }

                    newoptionHTML += `
                            <button type='button' class='button button-small vs-remove-option'>Remover</button>
                        </div>
                    `;
                    
                    e.target.insertAdjacentHTML('beforebegin', newoptionHTML);
                }
            });

        })(jQuery);
    </script>
    <?php
}

/**
 * Renderiza los estilos CSS para el metabox
 */
function vs_render_metabox_questions_styles() {
    ?>
    <style>
        .vs-metabox-questions {
            padding: 10px 0;
        }
        
        .vs-edit-permission-section {
            margin-bottom: 15px;
        }
        
        .vs-pergunta {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            background: #fafafa;
            border-radius: 4px;
        }
        
        .vs-pergunta:hover {
            border-color: #ccc;
        }
        
        .vs-pergunta label {
            font-weight: 600;
            color: #333;
        }
        
        .vs-pergunta input[type="text"],
        .vs-pergunta select {
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 6px 8px;
            min-width: 125px;
        }
        
        .vs-pergunta input[type="text"]:focus,
        .vs-pergunta select:focus {
            border-color: #0073aa;
            box-shadow: 0 0 0 1px #0073aa;
            outline: none;
        }
        
        .vs-options-container {
            margin-top: 10px;
            padding: 10px;
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 3px;
        }
        
        .vs-option-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .vs-option-item input {
            flex: 1;
        }
        
        .vs-add-option {
            margin-top: 8px;
        }
        
        .vs-remove-pergunta {
            background: #dc3232;
            border-color: #dc3232;
            color: white;
        }
        
        .vs-remove-pergunta:hover {
            background: #a00;
            border-color: #a00;
        }
        
        .vs-remove-option {
            background: #f56565;
            border-color: #f56565;
            color: white;
            font-size: 11px;
            padding: 2px 8px;
        }
        
        .vs-remove-option:hover {
            background: #e53e3e;
            border-color: #e53e3e;
        }
        
        #vs-add-pergunta {
            background: #0073aa;
            border-color: #0073aa;
            color: white;
            padding: 8px 16px;
            font-weight: 500;
        }
        
        #vs-add-pergunta:hover {
            background: #005a87;
            border-color: #005a87;
        }
    </style>
    <?php
}
