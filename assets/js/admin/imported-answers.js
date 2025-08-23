/**
 * Módulo para gestão de respostas importadas
 */
(function($) {
    'use strict';

    window.VSAdmin = window.VSAdmin || {};
    window.VSAdmin.ImportedAnswers = {
        
        currentQuestion: null,
        isUpdating: false,
        updateTimeout: null,

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

            const $tbody = this.currentQuestion.find('.vs-imported-column tbody');
            if (!$tbody.length) {
                console.warn('Tabela .vs-imported-column tbody não encontrada');
                return;
            }

            // Prevenir múltiplas execuções simultâneas
            if (this.isUpdating) {
                console.log('updateTable() já está executando, ignorando chamada duplicada');
                return;
            }

            // Implementar debounce para evitar chamadas muito próximas
            if (this.updateTimeout) {
                clearTimeout(this.updateTimeout);
            }

            this.updateTimeout = setTimeout(() => {
                this._executeUpdateTable();
            }, 50);
        },

        // Função para buscar informações do evento via AJAX
        fetchEventInfo: function(voteId) {
            return new Promise((resolve, reject) => {
                if (!voteId) {
                    reject('Vote ID não fornecido');
                    return;
                }

                $.ajax({
                    url: vs_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'vs_get_event_info',
                        vote_id: voteId,
                        nonce: vs_admin.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            resolve({
                                event_name: response.data.event_name || 'Evento sem nome',
                                vote_id: voteId
                            });
                        } else {
                            reject('Erro ao buscar informações do evento');
                        }
                    },
                    error: function() {
                        reject('Erro na requisição AJAX');
                    }
                });
            });
        },

        // Função para extrair vote_ids únicos dos dados JSON
        extractUniqueVoteIds: function(data) {
            const voteIds = new Set();
            
            if (data && data.questions && Array.isArray(data.questions)) {
                data.questions.forEach(pergunta => {
                    if (pergunta.vote_id) {
                        voteIds.add(pergunta.vote_id);
                    }
                });
            }
            
            return Array.from(voteIds);
        },

        // Função para renderizar o parágrafo informativo
        renderEventSummary: function(eventInfo, totalAnswers) {
            const $container = this.currentQuestion.find('.vs-imported-column');
            const $existingSummary = $container.find('.vs-event-summary');
            
            // Remover parágrafo existente se houver
            if ($existingSummary.length) {
                $existingSummary.remove();
            }
            
            // Criar novo parágrafo informativo
            const $summaryParagraph = $('<div>', {
                class: 'vs-event-summary',
            });
            
            let summaryText = '';
            if (eventInfo.length === 1) {
                // Um único evento
                const event = eventInfo[0];
                summaryText = `<strong>Evento:</strong> ${event.event_name} | <strong>Votação:</strong> ${event.vote_title} | <strong>Respostas:</strong> ${totalAnswers}`;
            } else if (eventInfo.length > 1) {
                // Múltiplos eventos
                const eventNames = eventInfo.map(info => info.event_name).join(', ');
                summaryText = `<strong>Eventos:</strong> ${eventNames} | <strong>Respostas:</strong> ${totalAnswers}`;
            } else {
                // Nenhum evento encontrado
                summaryText = `<strong>Respostas exibidas:</strong> ${totalAnswers}`;
            }
            
            $summaryParagraph.html(summaryText);
            
            // Inserir antes da tabela
            $container.find('table').before($summaryParagraph);
        },

        // Função para extrair informações dos eventos dos dados JSON
        extractEventInfo: function(data) {
            const eventMap = new Map();
            
            if (data && data.questions && Array.isArray(data.questions)) {
                data.questions.forEach(pergunta => {
                    const voteId = pergunta.vote_id;
                    
                    if (voteId) {
                        const eventInfo = {
                            event_id: pergunta.event_id || null,
                            event_name: pergunta.event_title || 'Evento sem nome',
                            event_slug: pergunta.event_slug || null,
                            vote_id: voteId,
                            vote_title: pergunta.vote_title || 'Votação sem título'
                        };
                        
                        const eventKey = eventInfo.event_id || voteId;
                        if (!eventMap.has(eventKey)) {
                            eventMap.set(eventKey, eventInfo);
                        }
                    }
                });
            }
            
            return Array.from(eventMap.values());
        },

        _executeUpdateTable: function() {
            this.isUpdating = true;
            
            try {
                console.log('=== INÍCIO updateTable() ===');
                console.log('currentQuestion:', this.currentQuestion);
                
                const $tbody = this.currentQuestion.find('.vs-imported-column tbody');
                const $importedAnswersField = this.currentQuestion.find('.vs-imported-answers');
                const jsonData = $importedAnswersField.val();
                
                console.log('JSON bruto:', jsonData);
                
                if (!jsonData || jsonData.trim() === '' || jsonData === '{}') {
                    console.log('Nenhum dado JSON encontrado');
                    $tbody.empty();
                    
                    // Remover parágrafo de resumo se não há perguntas
                    this.currentQuestion.find('.vs-event-summary').remove();
                    
                    $tbody.append($('<tr>').append(
                        $('<td>', { 
                            colspan: 5, 
                            style: 'text-align: center; padding: 20px; color: #666;' 
                        }).text('Nenhuma resposta foi importada.')
                    ));
                    this.isUpdating = false;
                    return;
                }
                
                let data;
                try {
                    data = JSON.parse(jsonData);
                    console.log('Dados JSON parseados:', data);
                } catch (parseError) {
                    console.error('Erro ao fazer parse do JSON:', parseError);
                    this.currentQuestion.find('.vs-event-summary').remove();
                    $tbody.append($('<tr>').append(
                        $('<td>', { 
                            colspan: 5, 
                            style: 'text-align: center; padding: 20px; color: #d63638;' 
                        }).text('Erro: Dados JSON inválidos.')
                    ));
                    this.isUpdating = false;
                    return;
                }
                
                if (!data.questions || !Array.isArray(data.questions) || data.questions.length === 0) {
                    console.log('Nenhuma pergunta encontrada nos dados');
                    $tbody.empty();
                    
                    // Remover parágrafo de resumo se não há perguntas
                    this.currentQuestion.find('.vs-event-summary').remove();
                    
                    $tbody.append($('<tr>').append(
                        $('<td>', { 
                            colspan: 5, 
                            style: 'text-align: center; padding: 20px; color: #666;' 
                        }).text('Nenhuma resposta foi importada.')
                    ));
                    this.isUpdating = false;
                    return;
                }

                // Extrair informações dos eventos diretamente dos dados JSON
                const eventInfoArray = this.extractEventInfo(data);
                console.log('Informações dos eventos extraídas:', eventInfoArray);

                // Processar dados da tabela diretamente
                this.processTableData(data, eventInfoArray);

            } catch (error) {
                console.error('Erro em _executeUpdateTable:', error);
                this.isUpdating = false;
            }
        },

        updateCheckboxStates: function() {
            const $container = $('.vs-imported-column');
            if (!$container.length) return;
            
            // Obter valores reais das opções existentes (mesma lógica da _executeUpdateTable)
            const existingValues = [];
            this.currentQuestion.find('.vs-valor-real').each(function() {
                const value = $(this).val();
                if (value && value.trim() !== '') {
                    existingValues.push(value.trim());
                }
            });
            
            $container.find('.vs-select-answer').each(function() {
                const $checkbox = $(this);
                const valor = $checkbox.data('valor');
                const isExisting = existingValues.includes(valor);
                
                if (isExisting) {
                    // Item ainda existe como vs-option-item: desabilitar e manter checked
                    $checkbox.prop('disabled', true).prop('checked', true);
                    $checkbox.attr('title', 'Esta resposta já foi importada');
                    $checkbox.addClass('vs-checkbox-disabled');
                } else {
                    // Item foi removido: habilitar e desmarcar
                    $checkbox.prop('disabled', false).prop('checked', false);
                    $checkbox.removeAttr('title');
                    $checkbox.removeClass('vs-checkbox-disabled');
                }
            });
        },

        // Processar os dados da tabela
        processTableData: function(data, eventInfoArray) {
            try {
                const $tbody = this.currentQuestion.find('.vs-imported-column tbody');
                
                // Limpar a tabela antes de processar novos dados
                $tbody.empty();
                
                // Verificar se há duplicatas nos dados JSON
                const allAnswers = [];
                data.questions.forEach(pergunta => {
                    if (pergunta.imported_answers) {
                        pergunta.imported_answers.forEach(resposta => {
                            allAnswers.push({
                                value: resposta.value_unificada || resposta.value,
                                qtd_votos: resposta.qtd_votos
                            });
                        });
                    }
                });
                
                console.log('Total de respostas nos dados JSON:', allAnswers.length);
                console.log('Respostas únicas:', [...new Set(allAnswers.map(a => a.value))].length);

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
                let processedAnswers = new Set(); // Para evitar duplicatas na renderização
                let totalDisplayedAnswers = 0;

                data.questions.forEach((pergunta, perguntaIndex) => {
                    console.log(`Processando pergunta ${perguntaIndex}:`, pergunta.question_source);
                    
                    if (pergunta.imported_answers && Array.isArray(pergunta.imported_answers) && pergunta.imported_answers.length > 0) {
                        
                        pergunta.imported_answers.forEach((resposta, respostaIndex) => {
                            const valorExibir = resposta.value_unificada || resposta.value;
                            const qtdVotos = parseInt(resposta.qtd_votos || 0);
                            const questionSource = pergunta.question_source || 'Fonte desconhecida';
                            
                            // Criar chave única para detectar duplicatas
                            const uniqueKey = `${valorExibir}_${qtdVotos}_${questionSource}`;
                            
                            if (processedAnswers.has(uniqueKey)) {
                                console.log(`Duplicata detectada e ignorada: ${uniqueKey}`);
                                return; // Pular esta resposta duplicada
                            }
                            
                            processedAnswers.add(uniqueKey);
                            hasAnswers = true;
                            totalDisplayedAnswers++;
                            
                            console.log(`Adicionando linha ${rowNumber}:`, {
                                valorExibir,
                                qtdVotos,
                                questionSource,
                                uniqueKey
                            });
                            
                            // Verificar se já existe nas opções
                            const isExisting = existingValues.includes(valorExibir);
                            
                            const $row = $('<tr>');
                            
                            // Coluna do número (primeira coluna)
                            $row.append($('<td>').text(rowNumber));
                            
                            // Coluna de seleção (segunda coluna)
                            const $checkboxCell = $('<td>');
                            const $checkbox = $('<input>', {
                                type: 'checkbox',
                                class: 'vs-select-answer',
                                'data-valor': resposta.value || valorExibir,
                                'data-valor-unificado': resposta.value_unificada || '',
                                value: valorExibir,
                                disabled: isExisting, // Desabilitar se já existe
                                checked: isExisting   // Marcar como checked se já existe
                            });

                            // Adicionar classe especial e título para checkboxes desabilitados
                            if (isExisting) {
                                $checkbox.addClass('vs-checkbox-disabled');
                                $checkbox.attr('title', 'Esta resposta já foi importada');
                                $checkboxCell.addClass('vs-existing-item');
                            }

                            $checkboxCell.append($checkbox);
                            $row.append($checkboxCell);
                            
                            // Coluna do valor (terceira coluna)
                            $row.append($('<td>').text(valorExibir));
                            
                            // Coluna da quantidade de votos (quarta coluna)
                            $row.append($('<td>').text(qtdVotos));
                            
                            // Coluna da fonte da pergunta (quinta coluna)
                            $row.append($('<td>').text(questionSource));
                            
                            $tbody.append($row);
                            rowNumber++;
                        });
                    }
                });

                // Se não há respostas válidas, mostrar mensagem
                if (!hasAnswers) {
                    $tbody.append($('<tr>').append(
                        $('<td>', { 
                            colspan: 5, 
                            style: 'text-align: center; padding: 20px; color: #666;' 
                        }).text('Nenhuma resposta foi importada.')
                    ));
                    // Remover parágrafo de resumo se não há perguntas
                    this.currentQuestion.find('.vs-event-summary').remove();
                } else {
                    // Renderizar parágrafo informativo com informações dos eventos
                    if (eventInfoArray.length > 0) {
                        this.renderEventSummary(eventInfoArray, totalDisplayedAnswers);
                    } else {
                        // Se não há informações de evento, mostrar apenas a quantidade
                        this.renderEventSummary([], totalDisplayedAnswers);
                    }
                }

                console.log(`Total de linhas renderizadas: ${$tbody.find('tr').length}`);
                console.log(`Total de respostas exibidas: ${totalDisplayedAnswers}`);
                
            } catch (error) {
                console.error('Erro ao processar dados da tabela:', error);
            } finally {
                this.isUpdating = false;
            }
        },

        addSelected: function(event) {
            const $container = $(event.target).closest('.vs-columns-container');
            const $questionContainer = $container.closest('.vs-pergunta');
            const questionIndex = $questionContainer.find('[name*="[label]"]').attr('name').match(/\[(\d+)\]/)[1];
            const $optionsContainer = $container.find('.vs-options-column .vs-options');
            const $importedAnswersField = $questionContainer.find('.vs-imported-answers');
            
            // Obter dados atuais do imported_answers
            let importedAnswersData;
            try {
                importedAnswersData = JSON.parse($importedAnswersField.val() || '{}');
                
                // Preservar manual_items e imported_items existentes
                if (!importedAnswersData.manual_items) {
                    importedAnswersData.manual_items = [];
                }
                if (!importedAnswersData.imported_items) {
                    importedAnswersData.imported_items = [];
                }
                if (!importedAnswersData.questions) {
                    importedAnswersData.questions = [];
                }
                
                // Garantir que sejam arrays válidos
                if (!Array.isArray(importedAnswersData.manual_items)) {
                    importedAnswersData.manual_items = [];
                }
                if (!Array.isArray(importedAnswersData.imported_items)) {
                    importedAnswersData.imported_items = [];
                }
                if (!Array.isArray(importedAnswersData.questions)) {
                    importedAnswersData.questions = [];
                }
            } catch (e) {
                importedAnswersData = { questions: [], manual_items: [], imported_items: [] };
            }
            
            // Inicializar arrays se não existirem
            if (!importedAnswersData.manual_items) importedAnswersData.manual_items = [];
            if (!importedAnswersData.imported_items) importedAnswersData.imported_items = [];
            if (!importedAnswersData.questions) importedAnswersData.questions = [];
            
            // Obter respostas selecionadas da tabela
            const $selectedAnswers = $container.find('.vs-select-answer:checked:not(:disabled)');
            
            $selectedAnswers.each(function() {
                const $tr = $(this).closest('tr');
                const tableDisplayValue = $tr.find('td:eq(2)').text().trim();
                const originalValue = $(this).data('valor');
                const unifiedValue = $(this).data('valor-unificado');
                const realValue = originalValue;
                const visualValue = unifiedValue || tableDisplayValue;
                const sourceQuestion = $tr.find('td:eq(4)').text();

                 console.log('Processando item:', {
                    realValue: realValue,
                    visualValue: visualValue,
                    sourceQuestion: sourceQuestion,
                    dataValor: $(this).data('valor'),
                    dataValorUnificado: $(this).data('valor-unificado')
                });
                
                // Verificar se já existe uma opção com este valor real E visual
                let isDuplicate = false;
                $optionsContainer.find('.vs-option-item').each(function() {
                    const existingRealValue = $(this).find('.vs-valor-real').val();
                    const existingVisualValue = $(this).find('input[type="text"]').val();
                    
                    // Considerar duplicata apenas se AMBOS os valores forem iguais
                    if (existingRealValue === realValue && existingVisualValue === visualValue) {
                        isDuplicate = true;
                        return false; // break
                    }
                });
                
                // Se for duplicata, pular esta opção
                if (isDuplicate) {
                    console.log(`Opção com valor real "${realValue}" já existe. Pulando...`);
                    return true; // continue do loop
                }
                
                // Obter o índice real baseado na posição DOM atual
                const currentOptionIndex = $optionsContainer.find('.vs-option-item').length;
                
                // Criar nova opção
                const $optionItem = $('<div>', {
                    class: 'vs-option-item imported_question',
                    style: 'margin-bottom: 5px;'
                });
                
                const $textInput = $('<input>', {
                    type: 'text',
                    name: `vs_questions[${questionIndex}][options][]`,
                    value: visualValue,
                    style: 'width: 90%;',
                    placeholder: `Opção ${currentOptionIndex + 1}`
                });
                
                const $hiddenInput = $('<input>', {
                    type: 'hidden',
                    name: `vs_questions[${questionIndex}][valores_reais][${currentOptionIndex}]`,
                    class: 'vs-valor-real',
                    value: realValue
                });
                
                const $valorRealTexto = $('<span>', {
                    class: 'vs-valor-real-texto',
                    css: { fontSize: '12px', color: '#666', marginLeft: '10px' },
                    text: realValue
                });
                
                const $removeButton = $('<button>', {
                    type: 'button',
                    class: 'button button-small vs-remove-option',
                    text: 'Remover'
                });
                
                // Montar estrutura unificada
                $optionItem.append($textInput, $hiddenInput, $valorRealTexto, $removeButton);
                
                // Inserir antes do botão "Adicionar Opção"
                $optionsContainer.find('.vs-add-option').before($optionItem);
                
                // Adicionar ao array de itens importados usando o índice correto
                importedAnswersData.imported_items.push(currentOptionIndex);
            });
            
            // Atualizar o campo imported_answers
            $importedAnswersField.val(JSON.stringify(importedAnswersData));
            
            // Desmarcar apenas checkboxes que foram processados com sucesso
            $selectedAnswers.each(function() {
                const $checkbox = $(this);
                const realValue = $checkbox.data('valor');
                const visualValue = $checkbox.closest('tr').find('td:eq(2)').text();
                let wasAdded = false;
                
                // Verificar se foi realmente adicionado
                $optionsContainer.find('.vs-option-item').each(function() {
                    const existingReal = $(this).find('.vs-valor-real').val();
                    const existingVisual = $(this).find('input[type="text"]').val();
                    if (existingReal === realValue && existingVisual === visualValue) {
                        wasAdded = true;
                        return false;
                    }
                });
            });
            
            // Desmarcar o checkbox "Selecionar todos" após adicionar itens selecionados
            $container.find('.vs-select-all-answers').prop('checked', false);

            // Atualizar estados dos checkboxes após adicionar itens
            this.updateCheckboxStates();
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
            
            // Verificar todos os checkboxes da tabela
            $container.find('.vs-select-answer').each(function() {
                const $checkbox = $(this);
                const valorResposta = $checkbox.data('valor') || '';
                const isExistingOption = existingValues.includes(valorResposta.trim());
                
                // APENAS marcar, nunca desmarcar checkboxes já selecionados pelo usuário
                if (isExistingOption && !$checkbox.prop('checked')) {
                    $checkbox.prop('checked', true);
                }
            });
        },

        removeOption: function(event) {
            const $optionItem = $(event.target).closest('.vs-option-item');
            const $questionContainer = $optionItem.closest('.vs-pergunta');
            const $container = $questionContainer.find('.vs-columns-container');
            const $importedAnswersField = $questionContainer.find('.vs-imported-answers');
            
            // Verificar se é uma opção importada antes de remover
            const isImportedQuestion = $optionItem.hasClass('imported_question');
            
            // Se for uma opção importada, obter os valores para desmarcar o checkbox correspondente
            let valorReal = null;
            let voteId = null;
            let questionIndex = null;
            
            if (isImportedQuestion) {
                valorReal = $optionItem.find('.vs-valor-real').val();
                
                // Obter dados para identificar a pergunta de origem
                try {
                    const importedData = JSON.parse($importedAnswersField.val() || '{}');
                    if (importedData.questions) {
                        // Encontrar a pergunta que contém esta resposta
                        for (const pergunta of importedData.questions) {
                            const foundAnswer = pergunta.imported_answers.find(answer => 
                                answer.value === valorReal || answer.value_unificada === valorReal
                            );
                            if (foundAnswer) {
                                voteId = pergunta.vote_id;
                                questionIndex = pergunta.question_index - 1; // Ajustar para índice base 0
                                break;
                            }
                        }
                    }
                } catch (e) {
                    console.error('Erro ao processar dados importados:', e);
                }
            }
            
            // Obter o índice da opção que está sendo removida
            const optionIndex = $optionItem.index();
            
            // Atualizar arrays manual_items e imported_items
            let importedAnswersData;
            try {
                importedAnswersData = JSON.parse($importedAnswersField.val() || '{}');
            } catch (e) {
                importedAnswersData = { questions: [], manual_items: [], imported_items: [] };
            }
            
            // Inicializar arrays se não existirem
            if (!importedAnswersData.manual_items) importedAnswersData.manual_items = [];
            if (!importedAnswersData.imported_items) importedAnswersData.imported_items = [];
            
            // Remover o índice dos arrays
            importedAnswersData.manual_items = importedAnswersData.manual_items.filter(index => index !== optionIndex);
            importedAnswersData.imported_items = importedAnswersData.imported_items.filter(index => index !== optionIndex);
            
            // Atualizar o campo imported_answers
            $importedAnswersField.val(JSON.stringify(importedAnswersData));
            
            // Remover a opção
            $optionItem.remove();

            // Atualizar estado dos checkboxes após remoção
            const self = this;
            setTimeout(function() {
                self.updateCheckboxStates();
            }, 100);
            
            // Sincronizar com checkboxes do modal
            if (isImportedQuestion && voteId && questionIndex !== null) {
                // Atualizar metadados de seleções
                try {
                    const importedData = JSON.parse($importedAnswersField.val() || '{}');
                    if (importedData.selected_questions && importedData.selected_questions[voteId]) {
                        // Verificar se ainda existem outras respostas desta pergunta
                        const remainingAnswers = importedData.questions.filter(p => 
                            p.vote_id === voteId && p.question_index === (questionIndex + 1)
                        );
                        
                        if (remainingAnswers.length === 0 || 
                            (remainingAnswers.length === 1 && remainingAnswers[0].imported_answers.length === 0)) {
                            // Remover pergunta dos metadados se não há mais respostas
                            importedData.selected_questions[voteId] = 
                                importedData.selected_questions[voteId].filter(idx => idx !== questionIndex);
                            
                            // Se não há mais perguntas selecionadas desta votação, remover completamente
                            if (importedData.selected_questions[voteId].length === 0) {
                                delete importedData.selected_questions[voteId];
                            }
                            
                            // Atualizar campo
                            $importedAnswersField.val(JSON.stringify(importedData));
                        }
                    }
                } catch (e) {
                    console.error('Erro ao atualizar metadados:', e);
                }
                
                // Desmarcar checkbox correspondente se o modal estiver aberto
                const $modal = $(`.vs-modal:visible`);
                if ($modal.length) {
                    const $checkbox = $modal.find(
                        `.vs-select-question[data-votacao-id="${voteId}"][data-question-index="${questionIndex}"]`
                    );
                    if ($checkbox.length) {
                        $checkbox.prop('checked', false);
                        
                        // Atualizar estado do "Selecionar todas"
                        const totalCheckboxes = $modal.find('.vs-select-question').length;
                        const checkedCheckboxes = $modal.find('.vs-select-question:checked').length;
                        $modal.find('.vs-select-all-questions').prop('checked', 
                            totalCheckboxes > 0 && checkedCheckboxes === totalCheckboxes
                        );
                        
                        // Atualizar botão de importação
                        if (window.VSAdmin.VotingModal) {
                            window.VSAdmin.VotingModal.updateImportButton($modal);
                        }
                    }
                }
                
                // Sincronizar checkboxes após remoção
                this.updateCheckboxStates();
            }
        },

        selectAllAnswers: function(event) {
            const isChecked = $(event.target).prop('checked');
            // Selecionar apenas checkboxes habilitados
            $(event.target).closest('table').find('.vs-select-answer:not(:disabled)').prop('checked', isChecked);
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
                            questions: [{
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
                                    vote_id: votingId,
                                    vote_title: questionData.vote_title || '',
                                    event_id: questionData.event_id || null,
                                    event_title: questionData.event_title || 'Evento sem nome',
                                    event_slug: questionData.event_slug || null,
                                    question_source: questionData.label || '',
                                    question_index: questionIndex + 1,
                                    imported_answers: importedAnswers
                                });
                            }
                        });

                        // JSON unificado com todas as respostas
                        const answersJson = JSON.stringify({
                            questions: allImportedAnswers,
                            selected_questions: {
                                [votingId]: selectedQuestionIndexes
                            }
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
