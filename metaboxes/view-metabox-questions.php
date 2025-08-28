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
            if (!empty($question['imported_answers'])) {
                $json_decoded = json_decode($question['imported_answers'], true);
                if ($json_decoded !== null) {
                    // Preservar manual_items e imported_items existentes
                    $existing_manual_items = $json_decoded['manual_items'] ?? [];
                    $existing_imported_items = $json_decoded['imported_items'] ?? [];
                    
                    // Garantir que a estrutura tenha todos os campos necessários
                    if (!isset($json_decoded['questions'])) {
                        $json_decoded['questions'] = [];
                    }
                    if (!isset($json_decoded['manual_items'])) {
                        $json_decoded['manual_items'] = $existing_manual_items;
                    }
                    if (!isset($json_decoded['imported_items'])) {
                        $json_decoded['imported_items'] = $existing_imported_items;
                    }
                    
                    $question['imported_answers'] = json_encode($json_decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    $question['imported_answers'] = json_encode([
                        'questions' => [],
                        'manual_items' => [],
                        'imported_items' => []
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            } else {
                $question['imported_answers'] = json_encode([
                    'questions' => [],
                    'manual_items' => [],
                    'imported_items' => []
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            
            // Converter vs_options salvos em options para renderização
            if (!empty($question['vs_options']) && is_array($question['vs_options'])) {
                $vs_options = $question['vs_options'];
                
                // Inicializar arrays se não existirem
                if (!isset($question['options'])) {
                    $question['options'] = [];
                }
                if (!isset($question['valores_reais'])) {
                    $question['valores_reais'] = [];
                }
                
                // Processar manual_items
                if (!empty($vs_options['manual_items']) && is_array($vs_options['manual_items'])) {
                    foreach ($vs_options['manual_items'] as $item) {
                        if (isset($item['text']) && !empty(trim($item['text']))) {
                            $index = count($question['options']);
                            $question['options'][$index] = $item['text'];
                            
                            // Adicionar valor real se existir
                            if (isset($item['vs_valor_real']) && !empty(trim($item['vs_valor_real']))) {
                                $question['valores_reais'][$index] = $item['vs_valor_real'];
                            }
                        }
                    }
                }
                
                // Processar imported_items
                if (!empty($vs_options['imported_items']) && is_array($vs_options['imported_items'])) {
                    foreach ($vs_options['imported_items'] as $item) {
                        if (isset($item['text']) && !empty(trim($item['text']))) {
                            $index = count($question['options']);
                            $question['options'][$index] = $item['text'];
                            
                            // Adicionar valor real se existir
                            if (isset($item['vs_valor_real']) && !empty(trim($item['vs_valor_real']))) {
                                $question['valores_reais'][$index] = $item['vs_valor_real'];
                            }
                        }
                    }
                }
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
                        // Coletar vs-options após adicionar nova pergunta
                        setTimeout(collectVsOptionsForPersistence, 100);
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
                        // Coletar vs-options após remover pergunta
                        setTimeout(collectVsOptionsForPersistence, 100);
                    }
                }

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
                                   placeholder='Opción ${optionCount + 1}'>
                            <button type='button' class='button button-small vs-remove-option'>Remover</button>
                        </div>
                    `;
                    
                    e.target.insertAdjacentHTML('beforebegin', newoptionHTML);
                    // Coletar vs-options após adicionar opção
                    setTimeout(collectVsOptionsForPersistence, 100);
                }
                
                // Remover opção
                if (e.target && e.target.classList.contains('vs-remove-option')) {
                    e.target.closest('.vs-option-item').remove();
                    // Coletar vs-options após remover opção
                    setTimeout(collectVsOptionsForPersistence, 100);
                }
            });
            
            // Função de fallback para coletar do DOM (compatibilidade)
            function collectFromDOMFallback($question, questionIndex) {
                const questionGroupId = $question.attr('data-question-group-id');
                const vsOptions = {
                    manual_items: [],
                    imported_items: []
                };
                
                // Log para debug
                console.log(`🔍 [collectFromDOMFallback] Pergunta ${questionIndex}:`, {
                    questionGroupId: questionGroupId,
                    questionIndex: questionIndex
                });
                
                // Verificar se vs-imported-answers existe
                const $importedAnswersField = $question.find('.vs-imported-answers');
                const hasImportedAnswers = $importedAnswersField.length && $importedAnswersField.val();
                
                // Seletor mais específico para evitar vazamento
                const $optionItems = $question.find('> .vs-options .vs-option-item, > .vs-columns-container .vs-option-item');
                
                console.log(`🔍 [collectFromDOMFallback] Opções encontradas:`, {
                    questionIndex: questionIndex,
                    optionsCount: $optionItems.length,
                    hasImportedAnswers: hasImportedAnswers
                });
                
                $optionItems.each(function(index) {
                    const $option = $(this);
                    const text = $option.find('input[type="text"]').val();
                    const realValue = $option.find('.vs-valor-real').val();
                    const isImported = $option.hasClass('imported_question');
                    
                    if (text && text.trim() !== '') {
                        if (isImported) {
                            // Só coletar imported_items do DOM se vs-imported-answers NÃO existir
                            if (!hasImportedAnswers) {
                                vsOptions.imported_items.push({
                                    text: text,
                                    vs_valor_real: realValue || text,
                                    question_index: questionIndex,
                                    vote_id: $option.data('vote-id'),
                                    answer_index: $option.data('answer-index')
                                });
                            }
                        } else {
                            // Manual items sempre coletados do DOM
                            vsOptions.manual_items.push({
                                text: text,
                                vs_valor_real: text,
                                question_index: questionIndex,
                                option_index: index
                            });
                        }
                    }
                });
                
                console.log(`✅ [collectFromDOMFallback] Dados coletados:`, {
                    questionIndex: questionIndex,
                    vsOptions: vsOptions
                });
                
                return vsOptions; // ✅ CORREÇÃO: Retornar os dados!
            }

            // Função para coletar vs-options e integrá-los na estrutura de questions
            function collectVsOptionsForPersistence() {
                console.log('🔍 DEBUG collectVsOptionsForPersistence - Início');
                
                $('.vs-pergunta').each(function(index) {
                    const $question = $(this);
                    
                    // Usar $question consistentemente
                    const questionGroupId = $question.attr('data-question-group-id');
                    let questionIndex = $question.find('.vs-question-index').val();
                    
                    // Se questionIndex estiver vazio, usar o índice do loop
                    if (!questionIndex || questionIndex.trim() === '') {
                        questionIndex = index;
                        console.warn(`⚠️ questionIndex vazio para pergunta ${index}, usando índice do loop:`, questionIndex);
                    }
                    
                    const questionType = $question.find('.vs-question-type').val();
                    
                    // Capturar retorno da função
                    const fallbackData = collectFromDOMFallback($question, questionIndex);
                    
                    console.log('🔍 DEBUG collectVsOptionsForPersistence - Processando pergunta:', {
                        index: index,
                        questionGroupId: questionGroupId,
                        questionIndex: questionIndex, // Agora não será vazio
                        optionsCount: $question.find('.vs-option-item').length,
                        fallbackData: fallbackData // Dados coletados
                    });
                    
                    if (questionIndex === null || !questionGroupId) return;
                    
                    // Usar escopo de grupo para isolar opções
                    const $importedAnswersField = $question.find('.vs-imported-answers');
                    let vsOptions = {
                        manual_items: [],
                        imported_items: []
                    };
                    
                    if ($importedAnswersField.length && $importedAnswersField.val()) {
                        try {
                            // Ler dados de vs-imported-answers (fonte única de verdade)
                            const importedData = JSON.parse($importedAnswersField.val());
                            
                            // Usar dados já estruturados de vs-imported-answers
                            if (importedData.manual_items && Array.isArray(importedData.manual_items)) {
                                vsOptions.manual_items = importedData.manual_items.filter(item => 
                                    item.text && item.text.trim() !== ''
                                );
                            }
                            
                            if (importedData.imported_items && Array.isArray(importedData.imported_items)) {
                                vsOptions.imported_items = importedData.imported_items.filter(item => 
                                    item.text && item.text.trim() !== ''
                                );
                            }
                            
                            console.log('✅ vs-options gerado de vs-imported-answers:', vsOptions);
                            
                        } catch (error) {
                            console.warn('Erro ao ler vs-imported-answers, usando fallback DOM:', error);
                            // FALLBACK: Se vs-imported-answers estiver corrompido, usar DOM como backup
                            vsOptions = collectFromDOMFallback($question, questionIndex);
                        }
                    } else {
                        // FALLBACK: Se não houver vs-imported-answers, coletar do DOM
                        console.warn('vs-imported-answers não encontrado, usando fallback DOM');
                        vsOptions = collectFromDOMFallback($question, questionIndex);
                    }
                    
                    // Criar/atualizar campo oculto para vs-options
                    let $vsOptionsField = $question.find('input[name="vs_questions[' + questionIndex + '][vs_options]"]');
                    if ($vsOptionsField.length === 0) {
                        $vsOptionsField = $('<input>', {
                            type: 'hidden',
                            name: 'vs_questions[' + questionIndex + '][vs_options]',
                            'data-question-group-id': questionGroupId
                        });
                        $question.append($vsOptionsField);
                    }
                    
                    // Salvar dados dos vs-options no campo oculto
                    $vsOptionsField.val(JSON.stringify(vsOptions));
                });
            }
            
            // Função auxiliar para extrair índice da pergunta
            function extractQuestionIndex($question) {
                const labelInput = $question.find('input[name*="[label]"]').first();
                if (labelInput.length) {
                    const match = labelInput.attr('name').match(/vs_questions\[(\d+)\]\[label\]/);
                    if (match) {
                        return parseInt(match[1]);
                    }
                }
                return null;
            }
            
            // Executar coleta antes do salvamento do post
            $('form#post').on('submit', function() {
                collectVsOptionsForPersistence();
            });
            
            // Executar coleta inicial ao carregar a página
            $(document).ready(function() {
                setTimeout(collectVsOptionsForPersistence, 500);
            });
            
            // Executar coleta quando campos de texto das opções são alterados
            $(document).on('input', '.vs-option-item input[type="text"]', function() {
                setTimeout(collectVsOptionsForPersistence, 300);
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
            min-height: 26px;
            line-height: 2.18181818;
            padding: 0 8px;
            font-size: 11px;
            color: #2271b1;
            border-color: #2271b1;
            background: #f6f7f7;
            vertical-align: top;
            display: inline-block;
            text-decoration: none;
            margin: 0;
            cursor: pointer;
            border-width: 1px;
            border-style: solid;
            -webkit-appearance: none;
            border-radius: 3px;
            white-space: nowrap;
            box-sizing: border-box;
        }
        
        .vs-remove-option:hover {
            background: #f0f0f1;
            border-color: #0a4b78;
            color: #0a4b78;
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
