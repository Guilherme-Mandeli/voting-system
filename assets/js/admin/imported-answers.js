/**
 * Módulo para gestão de respostas importadas
 */
(function($) {
    'use strict';

    window.VSAdmin = window.VSAdmin || {};
    window.VSAdmin.ImportedAnswers = {
        
        currentQuestion: null,

        init: function() {
            this.bindEvents();
            this.initializeExistingAnswers();
        },

        bindEvents: function() {
            // Adicionar opção selecionada
            $(document).on('click', '.vs-add-selected', this.addSelected.bind(this));
            
            // Remover opção
            $(document).on('click', '.vs-remove-option', this.removeOption.bind(this));
            
            // Selecionar todas as respostas
            $(document).on('change', '.vs-select-all-answers', this.selectAllAnswers.bind(this));
            
            // Filtrar perguntas
            $(document).on('input', '.vs-search-question', this.filterQuestions.bind(this));
            
            // Remover seleção
            $(document).on('click', '.vs-remover-selecao', this.removeSelection.bind(this));
            
            // Ordenar tabela
            $(document).on('click', '.vs-ordenar-valor, .vs-ordenar-votos', this.handleSort.bind(this));
            
            // Importar múltiplas perguntas selecionadas
            $(document).on('click', '.vs-import-selected', this.initQuestionImport.bind(this));
            
            // Eventos personalizados
            $(document).on('vs:question-selected', this.handleQuestionSelected.bind(this));
            $(document).on('vs:update-imported-table', this.handleUpdateTable.bind(this));

            // Importar pergunta individual
            $(document).on('click', '.vs-importar-pergunta', this.importSingleQuestion.bind(this));
        },

        handleQuestionSelected: function(event, $questionContainer) {
            this.setCurrentQuestion($questionContainer);
        },

        handleUpdateTable: function(event, $questionContainer) {
            this.setCurrentQuestion($questionContainer);
            this.updateTable();
        },

        setCurrentQuestion: function($question) {
            this.currentQuestion = $question;
            // Atualizar variável global para compatibilidade
            window.currentQuestion = $question;
        },

        getCurrentQuestion: function() {
            return this.currentQuestion;
        },

        updateTable: function() {            
            if (!this.currentQuestion || !this.currentQuestion.length) {
                console.warn('currentQuestion não está definido ou é inválido');
                return;
            }

            const $jsonInput = this.currentQuestion.find('.vs-imported-answers');
            if (!$jsonInput.length) {
                console.warn('Campo .vs-imported-answers não encontrado');
                return;
            }

            const jsonData = $jsonInput.val();
            
            const $tbody = this.currentQuestion.find('.vs-imported-column tbody');
            if (!$tbody.length) {
                console.warn('Tabela .vs-imported-column tbody não encontrada');
                return;
            }

            $tbody.empty();

            if (!jsonData || jsonData.trim() === '') {
                $tbody.append($('<tr>').append(
                    $('<td>', { 
                        colspan: 5, 
                        style: 'text-align: center; padding: 20px; color: #666;' 
                    }).text('Nenhuma resposta foi importada.')
                ));
                return;
            }

            try {
                const data = JSON.parse(jsonData);
                
                if (!data || typeof data !== 'object') {
                    throw new Error('Estrutura JSON inválida');
                }

                if (!data.perguntas || !Array.isArray(data.perguntas)) {
                    $tbody.append($('<tr>').append(
                        $('<td>', { 
                            colspan: 5, 
                            style: 'text-align: center; padding: 20px; color: #666;' 
                        }).text('Nenhuma resposta foi importada.')
                    ));
                    return;
                }

                // Obter valores reais das opções existentes
                const existingValues = [];
                this.currentQuestion.find('.vs-valor-real').each(function() {
                    const value = $(this).val();
                    if (value && value.trim() !== '') {
                        existingValues.push(value.trim());
                    }
                });

                let rowNumber = 1;
                let hasAnswers = false;

                data.perguntas.forEach((pergunta, perguntaIndex) => {
                    
                    if (pergunta.imported_answers && Array.isArray(pergunta.imported_answers) && pergunta.imported_answers.length > 0) {
                        
                        pergunta.imported_answers.forEach(resposta => {
                            const valorExibir = resposta.value_unificada || resposta.value;
                            
                            if (!valorExibir || valorExibir.trim() === '') {
                                return;
                            }

                            hasAnswers = true;

                            const $tr = $('<tr>');
                            
                            $tr.append(
                                $('<td>', { 
                                    style: 'text-align: center; font-size: 12px; width: 40px;' 
                                }).text(rowNumber)
                            );

                            // Verificar se este valor já está nas opções existentes
                            const valorResposta = resposta.value || '';
                            const isExistingOption = existingValues.includes(valorResposta.trim());

                            $tr.append(
                                $('<td>', { 
                                    style: 'text-align: center; width: 50px;' 
                                }).append($('<input>', {
                                    type: 'checkbox',
                                    class: 'vs-select-answer',
                                    'data-valor': valorResposta,
                                    'data-valor-unificado': resposta.value_unificada || '',
                                    checked: isExistingOption // Marcar se já está nas opções
                                }))
                            );

                            $tr.append(
                                $('<td>', { 
                                    style: 'padding: 8px;' 
                                }).text(valorExibir)
                            );

                            $tr.append(
                                $('<td>', { 
                                    style: 'text-align: center; width: 80px;' 
                                }).text(resposta.qtd_votos || 0)
                            );

                            $tr.append(
                                $('<td>', { 
                                    style: 'padding: 8px; font-size: 12px; color: #666;' 
                                }).text(pergunta.question_source || 'N/A')
                            );

                            $tbody.append($tr);
                            rowNumber++;
                        });
                    }
                });

                if (!hasAnswers) {
                    $tbody.append($('<tr>').append(
                        $('<td>', { 
                            colspan: 5, 
                            style: 'text-align: center; padding: 20px; color: #666;' 
                        }).text('Nenhuma resposta foi importada.')
                    ));
                }

            } catch (error) {
                console.error('Erro ao processar JSON:', error);
                $tbody.append($('<tr>').append(
                    $('<td>', { 
                        colspan: 5, 
                        style: 'text-align: center; padding: 20px; color: #d63638;' 
                    }).text('Erro ao carregar respostas importadas.')
                ));
            }
        },

        addSelected: function(event) {
            const $container = $(event.target).closest('.vs-columns-container');
            const $questionContainer = $container.closest('.vs-pergunta');
            const questionIndex = $questionContainer.find('[name*="[label]"]').attr('name').match(/\[(\d+)\]/)[1];
            const $optionsContainer = $container.find('.vs-options-column .vs-options');
            
            // Obter respostas selecionadas da tabela
            const $selectedAnswers = $container.find('.vs-select-answer:checked');
            
            $selectedAnswers.each(function() {
                const $tr = $(this).closest('tr');
                const realValue = $(this).data('valor');
                const visualValue = $tr.find('td:eq(2)').text();
                const sourceQuestion = $tr.find('td:eq(4)').text();
                
                // Verificar se a opção já existe
                const exists = $optionsContainer.find('.vs-valor-real').filter(function() {
                    return $(this).val() === realValue;
                }).length > 0;
                
                if (!exists) {
                    // Estrutura HTML unificada para opções
                    const $optionItem = $('<div>', { 
                        class: 'vs-option-item', 
                        css: { marginBottom: '5px' } 
                    });
                    
                    const $textInput = $('<input>', {
                        type: 'text',
                        name: `vs_questions[${questionIndex}][options][]`,
                        value: visualValue,
                        style: 'width: 90%;'
                    });
                    
                    const $hiddenInput = $('<input>', {
                        type: 'hidden',
                        name: `vs_questions[${questionIndex}][valores_reais][]`,
                        value: realValue,
                        class: 'vs-valor-real'
                    });
                    
                    const $sourceSpan = $('<span>', {
                        class: 'vs-source-question',
                        style: 'color: #666; font-size: 0.9em; display: block; margin-top: 2px;'
                    }).text(sourceQuestion);
                    
                    const $removeButton = $('<button>', {
                        type: 'button',
                        class: 'button button-small vs-remove-option',
                        text: 'Remover',
                        style: 'margin-left: 5px;'
                    });
                    
                    // Montar estrutura unificada
                    $optionItem.append($textInput, $hiddenInput, $sourceSpan, $removeButton);
                    
                    // Inserir antes do botão "Adicionar Opção"
                    $optionsContainer.find('.vs-add-option').before($optionItem);
                }
            });
            
            // Desmarcar checkboxes após adicionar
            $selectedAnswers.prop('checked', false);
            
            // Verificar e marcar automaticamente todos os checkboxes que correspondem às opções existentes
            this.updateCheckboxesBasedOnExistingOptions($container);
        },

        // Atualizar checkboxes baseado nas opções existentes
        updateCheckboxesBasedOnExistingOptions: function($container) {
            const $questionContainer = $container.closest('.vs-pergunta');
            
            // Obter todos os valores reais das opções existentes
            const existingValues = [];
            $questionContainer.find('.vs-valor-real').each(function() {
                const value = $(this).val();
                if (value && value.trim() !== '') {
                    existingValues.push(value.trim());
                }
            });
            
            // Verificar todos os checkboxes da tabela e marcar os que correspondem às opções existentes
            $container.find('.vs-select-answer').each(function() {
                const $checkbox = $(this);
                const valorResposta = $checkbox.data('valor') || '';
                const isExistingOption = existingValues.includes(valorResposta.trim());
                
                // Marcar ou desmarcar baseado na existência da opção
                $checkbox.prop('checked', isExistingOption);
            });
        },

        removeOption: function(event) {
            const $optionItem = $(event.target).closest('.vs-option-item');
            const $container = $optionItem.closest('.vs-pergunta').find('.vs-columns-container');
            
            // Remover a opção
            $optionItem.remove();
            
            // Atualizar checkboxes após remoção
            if ($container.length) {
                this.updateCheckboxesBasedOnExistingOptions($container);
            }
        },

        selectAllAnswers: function(event) {
            const isChecked = $(event.target).prop('checked');
            $(event.target).closest('table').find('.vs-select-answer').prop('checked', isChecked);
        },

        filterQuestions: function(event) {
            const search = $(event.target).val().toLowerCase();
            $('.vs-option-item').each(function() {
                const question = $(this).find('.vs-source-question').text().toLowerCase();
                $(this).toggle(question.includes(search));
            });
        },

        removeSelection: function(event) {
            const $container = $(event.target).closest('.vs-pergunta');
            $container.find('.vs-imported-answers')
                .val('')
                .attr('vote-id-list', '');
            $container.find('.vs-votacao-selecionada').empty();
        },

        handleSort: function(event) {
            const isValue = $(event.target).hasClass('vs-ordenar-valor');
            const $tbody = $(event.target).closest('.vs-imported-column').find('table tbody');
            const rows = $tbody.find('tr').get();
            const order = $(event.target).data('ordem') || 'asc';

            rows.sort((a, b) => {
                const aVal = isValue ? 
                    $(a).find('td:eq(2)').text() : 
                    parseInt($(a).find('td:eq(3)').text());
                const bVal = isValue ? 
                    $(b).find('td:eq(2)').text() : 
                    parseInt($(b).find('td:eq(3)').text());

                return order === 'asc' ? 
                    (aVal > bVal ? 1 : -1) : 
                    (aVal < bVal ? 1 : -1);
            });

            $(event.target).data('ordem', order === 'asc' ? 'desc' : 'asc');
            $(event.target).text($(event.target).text().replace(/[▲▼]/, order === 'asc' ? '▼' : '▲'));

            $tbody.empty().append(rows);
        },

        initializeExistingAnswers: function() {
            
            const $camposImportedVote = $('.vs-tipo-campo');
            
            $camposImportedVote.each(function(index) {
                const valor = $(this).val();
                
                if (valor === 'imported_vote') {
                    
                    const $questionContainer = $(this).closest('.vs-pergunta');
                    
                    window.VSAdmin.ImportedAnswers.setCurrentQuestion($questionContainer);
                    window.VSAdmin.ImportedAnswers.updateTable();
                    
                }
            });
            
        },

        importSingleQuestion: function(event) {
            const votingId = $(event.target).data('votacao-id');
            const questionIndex = $(event.target).data('question-index');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vs_obter_perguntas_votacao',
                    votacao_id: votingId,
                    question_index: questionIndex,
                    nonce: vs_admin.nonce
                },
                success: (response) => {
                    if (response.success && response.data && response.data[questionIndex]) {
                        const questionData = response.data[questionIndex];
                        const unifiedAnswers = questionData.respostas_unificadas || [];
                        
                        const importedAnswers = unifiedAnswers.map(answer => ({
                            value: answer.value || '',
                            value_unificada: answer.value_unificada || '',
                            qtd_votos: parseInt(answer.qtd_votos || 0)
                        }));

                        const answersJson = JSON.stringify({
                            perguntas: [{
                                question_source: questionData.label || '',
                                question_index: questionIndex + 1,
                                imported_answers: importedAnswers
                            }]
                        });
                        
                        // Atualizar campo oculto
                        this.getCurrentQuestion().find('.vs-imported-answers')
                            .val(answersJson)
                            .attr('vote-id-list', votingId);

                        // Atualizar tabela existente
                        this.updateTable();

                        // Fechar modal após importação
                        this.getCurrentQuestion().find('.vs-modal').hide();
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Erro ao importar pergunta:', error);
                }
            });
        },

        // Função consolidada do question-import.js para importação múltipla
        initQuestionImport: function(event) {
            const $modal = $(event.target).closest('.vs-modal');
            const $selectedQuestions = $modal.find('.vs-select-question:checked');
            
            if ($selectedQuestions.length === 0) {
                alert('Por favor, selecione pelo menos uma pergunta para importar.');
                return;
            }

            const votingId = $selectedQuestions.first().data('votacao-id');
            const selectedQuestionIndexes = [];
            
            $selectedQuestions.each(function() {
                selectedQuestionIndexes.push($(this).data('question-index'));
            });

            // Fazer chamada AJAX para obter dados das perguntas selecionadas
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vs_obter_perguntas_votacao',
                    votacao_id: votingId,
                    nonce: vs_admin.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        const allImportedAnswers = [];
                        
                        // Processar cada pergunta selecionada
                        selectedQuestionIndexes.forEach(questionIndex => {
                            if (response.data[questionIndex]) {
                                const questionData = response.data[questionIndex];
                                const unifiedAnswers = questionData.respostas_unificadas || [];
                                
                                const importedAnswers = unifiedAnswers.map(answer => ({
                                    value: answer.value || '',
                                    value_unificada: answer.value_unificada || '',
                                    qtd_votos: parseInt(answer.qtd_votos || 0)
                                }));

                                allImportedAnswers.push({
                                    question_source: questionData.label || '',
                                    question_index: questionIndex + 1,
                                    imported_answers: importedAnswers
                                });
                            }
                        });

                        // Criar JSON unificado com todas as respostas
                        const answersJson = JSON.stringify({
                            perguntas: allImportedAnswers
                        });
                        
                        // Atualizar campo oculto
                        window.VSAdmin.ImportedAnswers.getCurrentQuestion().find('.vs-imported-answers')
                            .val(answersJson)
                            .attr('vote-id-list', votingId);

                        // Atualizar tabela existente
                        window.VSAdmin.ImportedAnswers.updateTable();

                        // Fechar modal após importação
                        $modal.hide();
                    }
                },
                error: function() {
                    alert('Erro ao importar perguntas. Tente novamente.');
                }
            });
        }
    };

})(jQuery);
